<?php

namespace App\Http\Controllers;

use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramController extends Controller
{
    protected $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    public function handle(Request $request)
    {
        Log::info('Received update', ['update' => $request->all()]);

        try {
            $update = Telegram::commandsHandler(true);

            // Обработка обычных сообщений (сумма расхода)
            if ($message = $update->getMessage()) {
                if ($message->has('text') && !$message->has('entities')) {
                    $chatId = $message->getChat()->getId();
                    $text = $message->getText();

                    $this->telegramService->handleMessage($chatId, $text);
                    return response()->json(['status' => 'success']);
                }
            }

            // Обработка callback query (выбор категории)
            if ($callbackQuery = $update->getCallbackQuery()) {
                $chatId = $callbackQuery->getMessage()->getChat()->getId();
                $data = $callbackQuery->getData();

                $this->telegramService->handleCallback($chatId, $data);
                return response()->json(['status' => 'success']);
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error processing update: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function setWebhook()
    {
        $url = config('services.telegram.webhook_url');

        try {
            $response = Telegram::setWebhook(['url' => $url]);
            return response()->json(['success' => true, 'description' => 'Webhook was set']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'description' => $e->getMessage()]);
        }
    }

    public function removeWebhook()
    {
        try {
            $response = Telegram::removeWebhook();
            return response()->json(['success' => true, 'description' => 'Webhook was removed']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'description' => $e->getMessage()]);
        }
    }
}
