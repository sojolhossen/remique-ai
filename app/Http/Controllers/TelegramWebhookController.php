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
        if (! empty($update)) {
            try {
                $this->processor->processUpdate($update);
            } catch (\Throwable $e) {
                Log::error('Telegram update processing error: ' . $e->getMessage());
            }
        }

        return response()->json(['ok' => true]);
    }
}
