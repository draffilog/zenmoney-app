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

        $chatId = $chatId;
        $data = $data;

        if (str_starts_with($data, 'cat_')) {
            $categoryId = substr($data, 4);
            $this->showSubcategories($chatId, $categoryId);
            return;
        }

        switch ($data) {
            case 'balance':
                $this->showBalance($chatId);
                break;
            case 'expenses':
                $this->showExpenseCategories($chatId);
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
                    ['text' => 'Баланс', 'callback_data' => 'balance'],
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

    protected function showSubcategories($chatId, $categoryCode)
    {
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
                'category_code' => $categoryCode
            ];

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Категория \"{$category->getFullPath()}\"\nВведите сумму и комментарий (например: 100.50 обед)"
            ]);
            return;
        }

        // Если это папка (категория п��рвого уровня), показываем подкатегории
        $subcategories = $chat->getAvailableCategories($categoryCode);

        $keyboard = Keyboard::make()->inline();

        foreach ($subcategories as $subCategory) {
            $keyboard->row([
                Keyboard::inlineButton([
                    'text' => $subCategory['name'],
                    'callback_data' => 'cat_' . $subCategory['code']
                ])
            ]);
        }

        $keyboard->row([
            Keyboard::inlineButton(['text' => 'Назад', 'callback_data' => 'expenses'])
        ]);

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "Категория \"{$category->name}\"\nВыберите подкатегорию:",
            'reply_markup' => $keyboard
        ]);
    }

    public function handleMessage($chatId, $text)
    {
        Log::info('Handling message', [
            'chat_id' => $chatId,
            'text' => $text
        ]);

        // Обработка команды /s
        if ($text === '/s') {
            $this->showMainMenu($chatId);
            return;
        }

        // Остальная логика обработки сообщений (для сумм расходов)
        if (!isset($this->awaitingInput[$chatId]) || !isset($this->awaitingInput[$chatId]['category_code'])) {
            return;
        }

        try {
            // Разбираем сообщение на сумму и комментарий
            $parts = explode(' ', $text, 2);
            $amount = floatval($parts[0]);
            $comment = $parts[1] ?? '';

            if ($amount <= 0) {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Пожалуйста, введите корректную сумму'
                ]);
                return;
            }

            $categoryCode = $this->awaitingInput[$chatId]['category_code'];

            // Получаем чат из базы данных
            $chat = TelegramChat::where('telegram_chat_id', $chatId)->first();
            if (!$chat) {
                throw new \Exception('Чат не настроен');
            }

            // Создаем транзакцию в ZenMoney
            $result = $this->zenMoneyService->createExpenseTransaction(
                $chat->zenmoney_account_id,
                $amount,
                $comment,
                $categoryCode
            );

            // Получаем обновленный баланс
            $balance = $this->zenMoneyService->getBalance($chat->zenmoney_account_id);

            // Получаем название категории
            $category = ExpenseCategory::where('code', $categoryCode)->first();

            // Отправляем подтверждение
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => sprintf(
                    "✅ Расход записан\nКатегория: %s\nСумма: %.2f\nКомментарий: %s\nТекущий баланс: %.2f",
                    $category->name,
                    $amount,
                    $comment,
                    $balance
                )
            ]);

            // Очищаем состояние ожидания
            unset($this->awaitingInput[$chatId]);

        } catch (\Exception $e) {
            Log::error('Error creating expense: ' . $e->getMessage(), [
                'chat_id' => $chatId,
                'text' => $text,
                'category' => $categoryCode ?? null
            ]);

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Произошла ошибка при сохранении рас��ода: ' . $e->getMessage()
            ]);
        }
    }
}
