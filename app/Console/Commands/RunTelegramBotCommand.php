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
                        $telegramService->handleCommand($update['message']);
                    } elseif ($update['callback_query'] ?? null) {
                        $telegramService->handleCallback($update['callback_query']);
                    }

                    $lastUpdateId = $update['update_id'];
                }

                sleep(1);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
                sleep(5);
            }
        }
    }
}
