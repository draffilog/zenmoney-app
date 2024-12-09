<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TelegramService;
use Telegram\Bot\Api;

class RunTelegramBotCommand extends Command
{
    protected $signature = 'telegram:bot:run';
    protected $description = 'Run Telegram bot';

    public function handle(TelegramService $telegramService)
    {
        $this->info('Starting Telegram bot...');

        $telegram = new Api(config('services.telegram.bot_token'));

        $telegram->deleteWebhook();

        $lastUpdateId = 0;

        while (true) {
            try {
                $updates = $telegram->getUpdates(['offset' => $lastUpdateId + 1]);

                foreach ($updates as $update) {
                    \Log::info('Received update', ['update' => $update]);

                    if ($update['message'] ?? null) {
                        $chatId = $update['message']['chat']['id'];
                        $text = $update['message']['text'];
                        $telegramService->handleMessage($chatId, $text);
                    } elseif ($update['callback_query'] ?? null) {
                        $chatId = $update['callback_query']['message']['chat']['id'];
                        $data = $update['callback_query']['data'];
                        $telegramService->handleCallback($chatId, $data);
                    }

                    $lastUpdateId = $update['update_id'];
                }

                sleep(2);
            } catch (\Exception $e) {
                \Log::error('Error: ' . $e->getMessage());
                sleep(5);
            }
        }
    }
}
