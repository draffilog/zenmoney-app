<?php

namespace App\Services;

use App\Models\Chat;
use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\Cache;

class TelegramBotService
{
    protected $bot;
    protected $zenMoneyService;

    public function __construct(ZenMoneyService $zenMoneyService)
    {
        $this->zenMoneyService = $zenMoneyService;
    }

    public function handleUpdate(array $update)
    {
        $message = $update['message'] ?? null;
        $callbackQuery = $update['callback_query'] ?? null;

        if ($message && isset($message['text'])) {
            // Проверяем, ожидаем ли ввод суммы и комментария
            if ($this->isAwaitingInput($message['chat']['id'])) {
                $this->handleExpenseInput($message);
                return;
            }

            // Обработка команд
            if (strpos($message['text'], '/') === 0) {
                $this->handleCommand($message);
                return;
            }
        }

        if ($callbackQuery) {
            $this->handleCallbackQuery($callbackQuery);
        }
    }

    protected function handleExpenseInput($message)
    {
        $chatId = $message['chat']['id'];
        $input = $message['text'];

        $awaitingData = $this->getAwaitingData($chatId);
        if (!$awaitingData) {
            return;
        }

        // Парсим ввод пользователя (сумма и комментарий)
        list($amount, $comment) = $this->parseExpenseInput($input);

        try {
            // Создаем транзакцию в ZenMoney
            $transaction = $this->zenMoneyService->createTransaction([
                'amount' => -$amount, // отрицательная сумма для расхода
                'category' => $awaitingData['category'],
                'comment' => $comment,
                'account_id' => $awaitingData['account_id']
            ]);

            // Получаем обновленный баланс
            $balance = $this->zenMoneyService->getAccountBalance($awaitingData['account_id']);

            // Отправляем подтверждение пользователю
            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => sprintf(
                    "%s, потрачено %.2f на '%s'.\nСчет '%s', баланс %.2f",
                    $awaitingData['category_name'],
                    $amount,
                    $comment,
                    $awaitingData['account_name'],
                    $balance
                ),
                'reply_markup' => $this->getMainMenu()
            ]);

            // Очищаем состояние ожидания
            $this->clearAwaitingInput($chatId);
        } catch (\Exception $e) {
            \Log::error('Error creating transaction: ' . $e->getMessage());
            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Произошла ошибка при создании транзакции. Попробуйте еще раз.'
            ]);
        }
    }

    protected function isAwaitingInput($chatId)
    {
        // Проверяем наличие данных ожидания в хранилище
        return Cache::has("telegram_awaiting_input:{$chatId}");
    }

    protected function getAwaitingData($chatId)
    {
        return Cache::get("telegram_awaiting_input:{$chatId}");
    }

    protected function setAwaitingInput($chatId, $data)
    {
        Cache::put("telegram_awaiting_input:{$chatId}", $data, now()->addMinutes(30));
    }

    protected function clearAwaitingInput($chatId)
    {
        Cache::forget("telegram_awaiting_input:{$chatId}");
    }
}
