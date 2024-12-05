<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class SetTelegramWebhook extends Command
{
    protected $signature = 'telegram:webhook {url?}';
    protected $description = 'Set Telegram webhook URL';

    public function handle()
    {
        $webhookUrl = $this->argument('url') ?? config('app.url');
        $webhookUrl = rtrim($webhookUrl, '/') . '/telegram/webhook';

        if (!str_starts_with($webhookUrl, 'https://')) {
            $this->error('Webhook URL must use HTTPS. Please provide a valid HTTPS URL.');
            $this->info('You can use ngrok to create a HTTPS URL for local development:');
            $this->info('1. Run: ngrok http 8000');
            $this->info('2. Then run: php artisan telegram:webhook https://your-ngrok-url');
            return 1;
        }

        try {
            $this->info("Setting webhook to: $webhookUrl");

            $response = Telegram::setWebhook(['url' => $webhookUrl]);

            $this->info("Response: " . json_encode($response, JSON_PRETTY_PRINT));

            // Verify the webhook
            $webhookInfo = Telegram::getWebhookInfo();
            $this->info("Webhook Info: " . json_encode($webhookInfo, JSON_PRETTY_PRINT));

        } catch (\Exception $e) {
            $this->error("Failed to set webhook: " . $e->getMessage());
            return 1;
        }
    }
}
