<?php

namespace App\Services;

use App\Models\TelegramChat;
use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;
use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected $telegram;
    protected $zenMoneyService;
    protected $awaitingInput = [];

    public function __construct(Api $telegram, ZenMoneyService $zenMoneyService)
    {
        $this->telegram = $telegram;
        $this->zenMoneyService = $zenMoneyService;
    }

    public function handleCommand($message)
    {
        $chatId = $message['chat']['id'];

        if (($message['text'] ?? '') === '/s') {
            $this->showMainMenu($chatId);
        }
    }

    public function handleCallback($chatId, $data)
    {
        Log::info('Handling callback', [
            'chat_id' => $chatId,
            'data' => $data,
            'awaiting_input' => $this->awaitingInput
        ]);

        // Обработка категорий расходов
        if (str_starts_with($data, 'cat_')) {
            $categoryId = substr($data, 4);
            $this->showSubcategories($chatId, $categoryId, 'expense');
            return;
        }

        // Обработка категорий доходов
        if (str_starts_with($data, 'income_cat_')) {
            $categoryId = substr($data, 10);
            $this->showSubcategories($chatId, $categoryId, 'income');
            return;
        }

        switch ($data) {
            case 'balance':
                $this->showBalance($chatId);
                break;
            case 'expenses':
                $this->showExpenseCategories($chatId);
                break;
            case 'income':
                $this->showIncomeCategories($chatId);
                break;
            case 'back':
                $this->showMainMenu($chatId);
                break;
            case 'exit':
                $this->exitMenu($chatId);
                break;
        }
    }

    protected function showBalance($chatId)
    {
        $chat = TelegramChat::where('telegram_chat_id', $chatId)
            ->with('zenmoneyAccount')
            ->first();

        if (!$chat) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Чат не настроен. Обратитесь к администратору.'
            ]);
            return;
        }

        $balance = $this->zenMoneyService->getBalance($chat->zenmoneyAccount->code_zenmoney_account);

        $keyboard = Keyboard::make()
            ->inline()
            ->row([
                Keyboard::inlineButton(['text' => 'Назад', 'callback_data' => 'back'])
            ]);

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "Счет \"{$chat->zenmoneyAccount->name}\"\nБаланс: {$balance}",
            'reply_markup' => $keyboard
        ]);
    }

    protected function showExpenseCategories($chatId)
    {
        $chat = TelegramChat::where('telegram_chat_id', $chatId)
            ->first();

        if (!$chat) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Чат не настроен. Обратитесь к администратору.'
            ]);
            return;
        }

        // Получаем только категории верхнего уровня (папки)
        $categories = $chat->getAvailableCategories();

        if ($categories->isEmpty()) {
            // Если категорий нет, попробуем добавить их
            $chat->addDefaultExpenseCategories();
            $categories = $chat->getAvailableCategories();

            if ($categories->isEmpty()) {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Нет доступных категорий расходов. Обратитесь к администратору.'
                ]);
                return;
            }
        }

        $keyboard = Keyboard::make()->inline();

        foreach ($categories as $category) {
            $keyboard->row([
                Keyboard::inlineButton([
                    'text' => $category['name'],
                    'callback_data' => 'cat_' . $category['code']
                ])
            ]);
        }

        $keyboard->row([
            Keyboard::inlineButton(['text' => 'Назад', 'callback_data' => 'back'])
        ]);

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => 'Выберите категорию расхода:',
            'reply_markup' => $keyboard
        ]);
    }

    public function showMainMenu($chatId)
    {
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => 'Расходы', 'callback_data' => 'expenses'],
                    ['text' => 'Доходы', 'callback_data' => 'income'],
                    ['text' => 'Баланс', 'callback_data' => 'balance']
                ],
                [
                    ['text' => 'Выход', 'callback_data' => 'exit']
                ]
            ]
        ];

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => 'Выберите действие:',
            'reply_markup' => json_encode($keyboard)
        ]);
    }

    protected function exitMenu($chatId)
    {
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => 'До свидания! Для начала работы отправьте /s',
            'reply_markup' => Keyboard::remove(['selective' => false])
        ]);
    }

    protected function showSubcategories($chatId, $categoryCode, $type = 'expense')
    {
        // Заменяем dd() на логирование
        Log::info('Showing subcategories', [
            'params' => [
                'chat_id' => $chatId,
                'category_code' => $categoryCode,
                'type' => $type
            ],
            'chat' => TelegramChat::where('telegram_chat_id', $chatId)->first()?->toArray(),
            'category' => ExpenseCategory::where('code', $categoryCode)->first()?->toArray(),
            'subcategories' => TelegramChat::where('telegram_chat_id', $chatId)
                ->first()
                ?->getAvailableCategories($categoryCode)
                ?->toArray()
        ]);

        $chat = TelegramChat::where('telegram_chat_id', $chatId)->first();
        if (!$chat) {
            return;
        }

        $category = ExpenseCategory::where('code', $categoryCode)->first();
        if (!$category) {
            return;
        }

        // Если это конечная категория (категория второго уровня)
        if ($category->type === 'category') {
            $this->awaitingInput[$chatId] = [
                'category_code' => $categoryCode,
                'type' => $type
            ];

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Категория \"{$category->getFullPath()}\"\nВведите сумму и комментарий (например: 100.50 " .
                    ($type === 'income' ? 'зарплата' : 'обед') . ")"
            ]);
            return;
        }

        // Если это папка (категория первого уровня), показываем подкатегории
        $subcategories = $chat->getAvailableCategories($categoryCode);

        $keyboard = Keyboard::make()->inline();

        foreach ($subcategories as $subCategory) {
            $keyboard->row([
                Keyboard::inlineButton([
                    'text' => $subCategory['name'],
                    'callback_data' => ($type === 'income' ? 'income_cat_' : 'cat_') . $subCategory['code']
                ])
            ]);
        }

        $keyboard->row([
            Keyboard::inlineButton(['text' => 'Назад', 'callback_data' => $type === 'income' ? 'income' : 'expenses'])
        ]);

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "Категория \"{$category->name}\"\nВыберите подкатегорию:",
            'reply_markup' => $keyboard
        ]);
    }

    public function handleMessage($message)
    {
        if (!isset($message['text'])) {
            return;
        }

        Log::info('Handling message', [
            'chat_id' => $message['chat']['id'],
            'text' => $message['text']
        ]);

        if ($message['text'] === '/s') {
            $this->showMainMenu($message['chat']['id']);
            return;
        }

        if (!isset($this->awaitingInput[$message['chat']['id']])) {
            return;
        }

        try {
            $parts = explode(' ', $message['text'], 2);
            $amount = floatval($parts[0]);
            $comment = $parts[1] ?? '';

            if ($amount <= 0) {
                $this->telegram->sendMessage([
                    'chat_id' => $message['chat']['id'],
                    'text' => 'Пожалуйста, введите корректную сумму'
                ]);
                return;
            }

            $type = $this->awaitingInput[$message['chat']['id']]['type'] ?? 'expense';
            $chat = TelegramChat::where('telegram_chat_id', $message['chat']['id'])->first();

            if (!$chat) {
                throw new \Exception('Чат не настроен');
            }

            // Создаем транзакцию в зависимости от типа
            if ($type === 'income') {
                $result = $this->zenMoneyService->createIncomeTransaction(
                    $chat->zenmoney_account_id,
                    $amount,
                    $comment,
                    $chat->transit_account_id
                );
            } else {
                $result = $this->zenMoneyService->createExpenseTransaction(
                    $chat->zenmoney_account_id,
                    $amount,
                    $comment,
                    $categoryCode
                );
            }

            $balance = $this->zenMoneyService->getBalance($chat->zenmoney_account_id);

            $this->telegram->sendMessage([
                'chat_id' => $message['chat']['id'],
                'text' => sprintf(
                    "✅ %s записан\nСумма: %.2f\nКомментарий: %s\nТекущий баланс: %.2f",
                    $type === 'income' ? 'Доход' : 'Расход',
                    $amount,
                    $comment,
                    $balance
                )
            ]);

            unset($this->awaitingInput[$message['chat']['id']]);

        } catch (\Exception $e) {
            Log::error('Error creating transaction: ' . $e->getMessage(), [
                'chat_id' => $message['chat']['id'],
                'text' => $message['text']
            ]);

            $this->telegram->sendMessage([
                'chat_id' => $message['chat']['id'],
                'text' => 'Произошла ошибка при сохранении: ' . $e->getMessage()
            ]);
        }
    }

    protected function showIncomeCategories($chatId)
    {
        $chat = TelegramChat::where('telegram_chat_id', $chatId)
            ->first();

        if (!$chat) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Чат не настроен. Обратитесь к администратору.'
            ]);
            return;
        }

        // Запрашиваем сумму для перевода
        $this->awaitingInput[$chatId] = [
            'type' => 'income'
        ];

        $keyboard = Keyboard::make()->inline();
        $keyboard->row([
            Keyboard::inlineButton(['text' => 'Назад', 'callback_data' => 'back'])
        ]);

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => 'Введите сумму дохода и комментарий (например: 1000 Зарплата):',
            'reply_markup' => $keyboard
        ]);
    }
}
