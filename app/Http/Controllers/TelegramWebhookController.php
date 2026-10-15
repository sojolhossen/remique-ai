<?php

namespace App\Http\Controllers;

use App\Services\Telegram\TelegramMessageProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __construct(
        protected TelegramMessageProcessor $processor
    ) {}

    /**
     * Handle incoming Telegram webhook updates.
     */
    public function handle(Request $request): JsonResponse
    {
        // 1. Verify Secret Token if configured
        $configuredSecret = config('telegram.webhook_secret');
        if (! empty($configuredSecret)) {
            $headerSecret = $request->header('X-Telegram-Bot-Api-Secret-Token');
            if (! hash_equals($configuredSecret, (string) $headerSecret)) {
                Log::warning('Telegram webhook rejected: invalid secret token header.');
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        // 2. Process payload
        $update = $request->all();
        $updateId = $update['update_id'] ?? null;

        // Idempotency check: prevent duplicate execution if Telegram retries due to upstream latency
        if ($updateId) {
            $lockKey = "tg_processed_update_{$updateId}";
            if (! \Illuminate\Support\Facades\Cache::add($lockKey, true, now()->addMinutes(30))) {
                Log::info("Telegram update #{$updateId} already processed. Skipping duplicate retry.");
                return response()->json(['ok' => true]);
            }
        }

        if (! empty($update)) {
            try {
                $this->processor->processUpdate($update);
            } catch (\Throwable $e) {
                Log::error('Telegram update processing error: ' . $e->getMessage());
            }

            // Immediately check & flush due reminders
            try {
                \Illuminate\Support\Facades\Artisan::call('remi:send-due-reminders', ['--limit' => 20]);
            } catch (\Throwable $e) {
                // Silently ignore transient scheduler errors
            }
        }

        return response()->json(['ok' => true]);
    }
}
