<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramWebhookCommand extends Command
{
    protected $signature = 'telegram:webhook {url?}';
    protected $description = 'Set Telegram webhook URL';

    public function handle()
    {
        try {
            $url = $this->argument('url');

            if (!$url) {
                // Если URL не указан, показываем текущую информацию
                $info = Telegram::getWebhookInfo();
                $this->info(json_encode($info, JSON_PRETTY_PRINT));
                return;
            }

            // Устанавливаем вебхук
            $result = Telegram::setWebhook(['url' => $url]);

            if ($result) {
                $this->info("Webhook successfully set to: $url");
            } else {
                $this->error("Failed to set webhook");
            }

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
