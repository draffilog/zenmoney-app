<?php

namespace App\Services;

use App\Models\TelegramChat;
use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;
use App\Models\ExpenseCategory;

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

    public function handleCallback($callback)
    {
        $chatId = $callback['message']['chat']['id'];
        $data = $callback['data'];

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
        $keyboard = Keyboard::make()
            ->inline()
            ->row([
                Keyboard::inlineButton(['text' => 'Расходы', 'callback_data' => 'expenses']),
                Keyboard::inlineButton(['text' => 'Баланс', 'callback_data' => 'balance']),
                Keyboard::inlineButton(['text' => 'Выход', 'callback_data' => 'exit'])
            ]);

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => 'Выберите действие:',
            'reply_markup' => $keyboard
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

        // Если это папка (категория первого уровня), показываем подкатегории
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

    public function handleMessage($message)
    {
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';

        if (isset($this->awaitingInput[$chatId])) {
            $this->handleExpenseInput($chatId, $text);
            return;
        }

        if ($text === '/s') {
            $this->showMainMenu($chatId);
        }
    }

    protected function handleExpenseInput($chatId, $text)
    {
        $inputData = $this->awaitingInput[$chatId];

        if (preg_match('/^(\d+(?:\.\d{1,2})?)\s*(.*)$/', trim($text), $matches)) {
            $amount = floatval($matches[1]);
            $comment = trim($matches[2] ?? '');

            try {
                $chat = TelegramChat::where('telegram_chat_id', $chatId)->first();
                $category = ExpenseCategory::where('code', $inputData['category_code'])->first();

                $transaction = $this->zenMoneyService->createTransaction([
                    'income' => 0,
                    'outcome' => $amount,
                    'outcomeAccount' => $chat->zenmoneyAccount->code_zenmoney_account,
                    'tag' => [$category->code],
                    'comment' => $comment,
                ]);

                $balance = $this->zenMoneyService->getBalance($chat->zenmoneyAccount->code_zenmoney_account);

                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => sprintf(
                        "%s, потрачено %.2f на '%s'.\nСчет '%s', баланс %.2f",
                        $category->getFullPath(),
                        $amount,
                        $comment,
                        $chat->zenmoneyAccount->name,
                        $balance
                    )
                ]);

            } catch (\Exception $e) {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Ошибка при создании транзакции: ' . $e->getMessage()
                ]);
            }
        } else {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Неверный формат. Пожалуйста, введите сумму и комментарий (например: 100.50 обед)'
            ]);
            return;
        }

        unset($this->awaitingInput[$chatId]);
    }
}
