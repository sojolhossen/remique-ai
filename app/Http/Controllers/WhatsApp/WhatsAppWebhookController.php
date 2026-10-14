<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\WhatsApp\WhatsAppMessageProcessor;
use App\Services\WhatsApp\WhatsAppWebhookVerifier;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        protected WhatsAppWebhookVerifier $verifier,
        protected WhatsAppMessageProcessor $processor
    ) {}

    /**
     * Handle Meta Webhook verification handshake (GET).
     */
    public function verify(Request $request): Response
    {
        $challenge = $this->verifier->verifyChallenge($request);

        if ($challenge !== null) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    /**
     * Handle incoming Meta Webhook notifications (POST).
     */
    public function handle(Request $request): Response
    {
        // 1. Verify HMAC-SHA256 signature
        if (! $this->verifier->verifySignature($request)) {
            Log::warning('WhatsApp webhook signature verification failed from IP: ' . $request->ip());

            AuditLog::record(
                'whatsapp.webhook_signature_failed',
                'Webhook',
                'Invalid HMAC-SHA256 signature on inbound webhook request',
                ['ip' => $request->ip()]
            );

            return response('Unauthorized', 401);
        }

        $payload = $request->json()->all();

        // 2. Validate basic structure
        if (empty($payload['entry']) || ! is_array($payload['entry'])) {
            return response('EVENT_RECEIVED', 200);
        }

        // 3. Process entries safely
        try {
            foreach ($payload['entry'] as $entry) {
                if (empty($entry['changes']) || ! is_array($entry['changes'])) {
                    continue;
                }

                foreach ($entry['changes'] as $change) {
                    $value = $change['value'] ?? [];

                    // Case A: Incoming Messages
                    if (! empty($value['messages']) && is_array($value['messages'])) {
                        foreach ($value['messages'] as $message) {
                            $this->processor->processInbound($message, $payload);
                        }
                    }

                    // Case B: Delivery Status Updates (sent, delivered, read, failed)
                    if (! empty($value['statuses']) && is_array($value['statuses'])) {
                        foreach ($value['statuses'] as $status) {
                            $this->processor->processStatus($status);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('Error processing WhatsApp webhook payload: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Fast 200 OK acknowledgement for Meta Cloud API
        return response('EVENT_RECEIVED', 200);
    }
}
