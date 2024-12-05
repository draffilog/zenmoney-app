<?php

namespace App\Http\Controllers;

use App\Services\TelegramBotService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Http\Request;

class TelegramController extends Controller
{
    protected $telegramService;

    public function __construct(TelegramBotService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    public function handleWebhook(Request $request)
    {
        try {
            $this->telegramService->handleUpdate($request->all());
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            \Log::error('Telegram webhook error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
