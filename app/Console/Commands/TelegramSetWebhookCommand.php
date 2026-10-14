<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramBotService;
use Illuminate\Console\Command;

class TelegramSetWebhookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:set-webhook {url? : The HTTPS webhook URL (defaults to APP_URL/telegram/webhook)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register or inspect the Telegram Bot webhook with Telegram API';

    /**
     * Execute the console command.
     */
    public function handle(TelegramBotService $botService): int
    {
        if (! $botService->isConfigured()) {
            $this->error('TELEGRAM_BOT_TOKEN is not configured in .env');
            return Command::FAILURE;
        }

        $url = $this->argument('url');
        if (! $url) {
            $appUrl = rtrim(config('app.url'), '/');
            $url = "{$appUrl}/telegram/webhook";
        }

        $secret = config('telegram.webhook_secret');

        $this->info("Setting Telegram Webhook to: {$url}");
        $result = $botService->setWebhook($url, $secret);

        if ($result['ok'] ?? false) {
            $this->info("✅ Webhook successfully set! Description: " . ($result['description'] ?? 'OK'));
            return Command::SUCCESS;
        }

        $this->error("❌ Failed to set webhook: " . ($result['description'] ?? 'Unknown error'));
        return Command::FAILURE;
    }
}
