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

        // Получаем родительскую категорию
        $parentCategory = ExpenseCategory::where('code', $categoryCode)->first();
        if (!$parentCategory) {
            return;
        }

        // Получаем подкатегории, которые привязаны к чату
        $subcategories = ExpenseCategory::where('parent_code', $parentCategory->code)
            ->whereIn('id', $chat->expenseCategories->pluck('id'))
            ->get();

        $keyboard = Keyboard::make()->inline();

        foreach ($subcategories as $category) {
            $keyboard->row([
                Keyboard::inlineButton([
                    'text' => $category->name,
                    'callback_data' => 'cat_' . $category->code
                ])
            ]);
        }

        $keyboard->row([
            Keyboard::inlineButton(['text' => 'Назад', 'callback_data' => 'expenses'])
        ]);

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => 'Выберите подкатегорию расхода:',
            'reply_markup' => $keyboard
        ]);
    }
}
