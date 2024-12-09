<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TelegramService;
use Telegram\Bot\Api;
use Illuminate\Support\Facades\Log;

class RunTelegramBotCommand extends Command
{
    protected $signature = 'telegram:bot:run';
    protected $description = 'Run Telegram bot';

    public function handle(TelegramService $telegramService)
    {
        $this->info('Starting Telegram bot...');

        $telegram = new Api(config('services.telegram.bot_token'));

        $telegram->deleteWebhook();

        $offset = 155340318;

        while (true) {
            try {
                $updates = $telegram->getUpdates(['offset' => $offset, 'timeout' => 30]);

                foreach ($updates as $update) {
                    Log::info('Processing update', ['update_id' => $update->update_id]);
                    $offset = $update->update_id + 1;

                    if (isset($update['message'])) {
                        $telegramService->handleMessage($update['message']);
                    } elseif (isset($update['callback_query'])) {
                        $telegramService->handleCallback(
                            $update['callback_query']['message']['chat']['id'],
                            $update['callback_query']['data']
                        );
                    }
                }

                usleep(100000);
            } catch (\Exception $e) {
                Log::error($e->getMessage());
                continue;
            }
        }
    }
}
