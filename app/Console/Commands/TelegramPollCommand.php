<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramBotService;
use App\Services\Telegram\TelegramMessageProcessor;
use Illuminate\Console\Command;

class TelegramPollCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:poll {--once : Run once and exit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll Telegram updates continuously for local development (no ngrok required)';

    /**
     * Execute the console command.
     */
    public function handle(
        TelegramBotService $botService,
        TelegramMessageProcessor $processor
    ): int {
        if (! $botService->isConfigured()) {
            $this->error('TELEGRAM_BOT_TOKEN is not configured in .env');
            return Command::FAILURE;
        }

        $botUsername = config('telegram.bot_username', 'Remique_bot');
        $this->info("🤖 Remique Telegram Polling Worker active!");
        $this->line("• Bot: @{$botUsername}");
        $this->line("• Mode: Long-Polling (local dev)");
        $this->line("• Press Ctrl+C to stop.\n");

        // Clear webhook so getUpdates works
        $botService->deleteWebhook();

        $offset = 0;
        $runOnce = (bool) $this->option('once');
        $lastReminderCheck = 0;

        while (true) {
            // Check and deliver due reminders in real-time (every 5 seconds, zero delay)
            if (time() - $lastReminderCheck >= 5) {
                $lastReminderCheck = time();
                try {
                    \Illuminate\Support\Facades\Artisan::call('remi:send-due-reminders', ['--limit' => 20]);
                } catch (\Throwable $e) {
                    // Suppress transient scheduler errors
                }
            }

            $updates = $botService->getUpdates($offset, limit: 10, timeout: 5);

            foreach ($updates as $update) {
                $updateId = $update['update_id'] ?? 0;
                $offset = max($offset, $updateId + 1);

                $text = $update['message']['text'] ?? '[media/file]';
                $from = $update['message']['from']['username'] ?? $update['message']['from']['first_name'] ?? 'user';
                $this->info("[" . date('H:i:s') . "] Inbound from @{$from}: {$text}");

                try {
                    $processor->processUpdate($update);
                    $this->line("  ↳ Processed successfully");
                } catch (\Throwable $e) {
                    $this->error("  ↳ Processing failed: " . $e->getMessage());
                }
            }

            if ($runOnce) {
                break;
            }

            usleep(250000); // 0.25s pause
        }

        return Command::SUCCESS;
    }
}
