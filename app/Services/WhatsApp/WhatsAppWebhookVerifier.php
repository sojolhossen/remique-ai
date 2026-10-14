<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookVerifier
{
    /**
     * Verify the Meta Webhook setup challenge (GET request).
     */
    public function verifyChallenge(Request $request): ?string
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $expectedToken = config('whatsapp.verify_token');

        if ($mode === 'subscribe' && !empty($expectedToken) && hash_equals($expectedToken, (string) $token)) {
            Log::info('WhatsApp Webhook verification challenge succeeded.');
            return (string) $challenge;
        }

        Log::warning('WhatsApp Webhook verification challenge failed.', [
            'mode' => $mode,
            'token_match' => !empty($expectedToken) && hash_equals($expectedToken, (string) $token),
        ]);

        return null;
    }

    /**
     * Verify the HMAC-SHA256 signature of incoming Meta Webhook payload (POST request).
     */
    public function verifySignature(Request $request): bool
    {
        $signatureHeader = $request->header('X-Hub-Signature-256');
        $appSecret = config('whatsapp.app_secret');

        // In local or test environments without an app secret set, signature check can be bypassed if explicitly configured
        if (empty($appSecret)) {
            if (app()->environment('local', 'testing')) {
                return true;
            }
            Log::error('WhatsApp app_secret is not configured. Webhook rejected for security.');
            return false;
        }

        if (empty($signatureHeader) || !str_starts_with($signatureHeader, 'sha256=')) {
            Log::warning('WhatsApp Webhook missing or invalid X-Hub-Signature-256 header.');
            return false;
        }

        $expectedSignature = substr($signatureHeader, 7);
        $rawPayload = $request->getContent();
        $calculatedSignature = hash_hmac('sha256', $rawPayload, $appSecret);

        $valid = hash_equals($calculatedSignature, $expectedSignature);

        if (!$valid) {
            Log::warning('WhatsApp Webhook signature mismatch.');
        }

        return $valid;
    }
}
