<?php

namespace App\Services\WhatsApp;

use App\Models\AuditLog;
use App\Models\Reminder;
use App\Models\WhatsAppIdentity;
use App\Models\WhatsAppMessage;
use App\Services\AI\NvidiaNimService;
use App\Services\Vault\VaultService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class WhatsAppMessageProcessor
{
    public function __construct(
        protected WhatsAppCloudApiService $apiService,
        protected NvidiaNimService $aiService,
        protected VaultService $vaultService
    ) {}

    /**
     * Process an incoming verified webhook message event.
     */
    public function processInbound(array $rawMessage, array $rawPayload): void
    {
        $wamid = $rawMessage['id'] ?? null;
        $fromNumber = WhatsAppIdentity::normalizePhoneNumber($rawMessage['from'] ?? '');
        $messageType = $rawMessage['type'] ?? 'text';

        if (empty($fromNumber) || empty($wamid)) {
            Log::warning('WhatsApp inbound message missing sender or ID', ['raw' => $rawMessage]);
            return;
        }

        // Deduplication check: if message wamid already exists in database, skip processing
        if (WhatsAppMessage::where('wamid', $wamid)->exists()) {
            Log::info("WhatsApp message {$wamid} already processed. Skipping duplicate.");
            return;
        }

        // 1. Authorize sender
        $identity = WhatsAppIdentity::with('user')->where('phone_number', $fromNumber)->first();

        // Log message record
        $loggedMessage = WhatsAppMessage::create([
            'wamid' => $wamid,
            'whatsapp_identity_id' => $identity?->id,
            'direction' => 'inbound',
            'from_number' => $fromNumber,
            'to_number' => (string) config('whatsapp.phone_number_id'),
            'message_type' => $messageType,
            'body' => $this->extractBody($rawMessage),
            'media_id' => $rawMessage[$messageType]['id'] ?? null,
            'mime_type' => $rawMessage[$messageType]['mime_type'] ?? null,
            'status' => 'received',
            'raw_payload' => $rawPayload,
        ]);

        // Branch A: Unknown sender
        if (! $identity) {
            $this->handleUnknownSender($fromNumber);
            return;
        }

        // Branch B: Pending verification (waiting for OTP/token)
        if ($identity->isPending()) {
            $this->handlePendingVerification($identity, $rawMessage);
            return;
        }

        // Branch C: Blocked or suspended sender
        if ($identity->isBlocked() || $identity->isSuspended()) {
            $this->handleUnauthorizedStatus($identity);
            return;
        }

        // Branch D: Active authorized customer
        if (! $identity->isActive() || ! $identity->user) {
            return;
        }

        // Update 24-hour service window timestamp
        $identity->update(['last_inbound_at' => Carbon::now()]);

        // Handle media ingestion vs text command
        if (in_array($messageType, ['image', 'document'])) {
            $this->handleMediaIngestion($identity, $rawMessage, $loggedMessage);
        } elseif ($messageType === 'text') {
            $this->handleTextCommand($identity, $rawMessage);
        } else {
            $this->apiService->sendText(
                $identity->phone_number,
                "Remique currently supports text messages, images, and documents."
            );
        }
    }

    /**
     * Handle incoming delivery status events (sent, delivered, read, failed).
     */
    public function processStatus(array $statusPayload): void
    {
        $wamid = $statusPayload['id'] ?? null;
        $status = $statusPayload['status'] ?? null;

        if (! $wamid || ! $status) {
            return;
        }

        $message = WhatsAppMessage::where('wamid', $wamid)->first();
        if (! $message) {
            return;
        }

        $message->status = $status;
        if (! empty($statusPayload['errors'])) {
            $message->error_code = (string) ($statusPayload['errors'][0]['code'] ?? '');
            $message->error_message = $statusPayload['errors'][0]['title'] ?? 'Delivery failed';
        }
        $message->save();

        // Update delivery attempt if tied to this message
        if ($message->deliveryAttempt) {
            $attempt = $message->deliveryAttempt;
            $attempt->status = $status;
            if ($status === 'delivered') {
                $attempt->delivered_at = Carbon::now();
            } elseif ($status === 'read') {
                $attempt->read_at = Carbon::now();
            } elseif ($status === 'failed') {
                $attempt->error_code = $message->error_code;
                $attempt->error_message = $message->error_message;
            }
            $attempt->save();
        }
    }

    protected function handleUnknownSender(string $fromNumber): void
    {
        Log::info("Inbound WhatsApp message from unknown sender: {$fromNumber}");

        if (config('whatsapp.send_onboarding_replies', true)) {
            $reply = "Welcome to Remique. Your WhatsApp number is not yet authorized. Please contact your administrator to link your account.";
            $this->apiService->sendText($fromNumber, $reply);
        }
    }

    protected function handlePendingVerification(WhatsAppIdentity $identity, array $rawMessage): void
    {
        $throttleKey = 'whatsapp_verify:' . $identity->id;

        // Check if identity is currently locked due to too many failed attempts
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = max(1, (int) ceil($seconds / 60));
            Log::warning("WhatsApp verification brute-force attempt blocked for identity {$identity->id} ({$identity->phone_number})");

            $this->apiService->sendText(
                $identity->phone_number,
                "⚠️ Too many failed verification attempts. Verification is locked for {$minutes} minutes for security. Please contact your administrator."
            );
            return;
        }

        $text = trim($this->extractBody($rawMessage) ?? '');

        // Extract OTP or token from message
        $enteredCode = strtoupper(trim($text));
        if (preg_match('/^(?:verify|token|otp|code)[:\s]+([A-Za-z0-9]+)$/i', $text, $matches)) {
            $enteredCode = strtoupper($matches[1]);
        }

        if (
            ! empty($identity->verification_token) &&
            hash_equals($identity->verification_token, $enteredCode) &&
            $identity->verification_expires_at &&
            $identity->verification_expires_at->isFuture()
        ) {
            RateLimiter::clear($throttleKey);

            $identity->update([
                'status' => 'active',
                'verified_at' => Carbon::now(),
                'consent_recorded_at' => Carbon::now(),
                'verification_token' => null,
                'verification_expires_at' => null,
                'last_inbound_at' => Carbon::now(),
            ]);

            AuditLog::record(
                'whatsapp.number_verified',
                $identity,
                'Customer verified WhatsApp number via valid OTP',
                ['phone_number' => $identity->phone_number],
                $identity->user_id
            );

            $this->apiService->sendText(
                $identity->phone_number,
                "✅ Your WhatsApp number is now verified! Welcome to Remique.\n\nYou can set reminders in Bengali, Banglish, or English (e.g. 'Kal shokal 9 tay meeting') or send files to store in your private Memory Vault."
            );
        } else {
            RateLimiter::hit($throttleKey, 3600); // 1 hour window
            $remaining = RateLimiter::remaining($throttleKey, 5);

            if ($remaining <= 0) {
                AuditLog::record(
                    'whatsapp.verification_locked',
                    $identity,
                    'WhatsApp identity verification locked due to excessive failed attempts',
                    ['phone_number' => $identity->phone_number],
                    $identity->user_id
                );

                $this->apiService->sendText(
                    $identity->phone_number,
                    "⚠️ Too many failed attempts. Account verification is locked for 60 minutes for security. Please contact your administrator."
                );
            } else {
                $this->apiService->sendText(
                    $identity->phone_number,
                    "⚠️ Verification failed. The code provided is invalid or has expired ({$remaining} attempts remaining). Please contact your administrator for a new verification code."
                );
            }
        }
    }

    protected function handleUnauthorizedStatus(WhatsAppIdentity $identity): void
    {
        $status = ucfirst($identity->status);
        $this->apiService->sendText(
            $identity->phone_number,
            "Your Remique account is currently {$status}. Please contact your administrator for assistance."
        );
    }

    protected function handleMediaIngestion(WhatsAppIdentity $identity, array $rawMessage, WhatsAppMessage $loggedMessage): void
    {
        $type = $rawMessage['type'];
        $mediaId = $rawMessage[$type]['id'] ?? null;
        $mimeType = $rawMessage[$type]['mime_type'] ?? 'application/octet-stream';
        $caption = $rawMessage[$type]['caption'] ?? null;
        $filename = $rawMessage[$type]['filename'] ?? null;

        if (! $mediaId) {
            $this->apiService->sendText($identity->phone_number, "Unable to process received file: missing media ID.");
            return;
        }

        // Download media from WhatsApp Cloud API
        $downloaded = $this->apiService->downloadMedia($mediaId);
        if (! $downloaded) {
            $this->apiService->sendText($identity->phone_number, "❌ Failed to download file from WhatsApp. Please try again.");
            return;
        }

        // Store into Memory Vault
        $vaultFile = $this->vaultService->storeMedia(
            $identity->user,
            $downloaded['contents'],
            $downloaded['mime_type'] ?: $mimeType,
            $filename,
            $caption,
            $loggedMessage->id
        );

        if ($vaultFile) {
            $loggedMessage->update(['status' => 'delivered']);
            $this->apiService->sendText(
                $identity->phone_number,
                "📁 File saved to your private Memory Vault!\n\nName: {$vaultFile->original_filename}\nSize: {$vaultFile->formattedSize()}\n\nYou can retrieve it anytime by describing it."
            );
        } else {
            $this->apiService->sendText(
                $identity->phone_number,
                "❌ Could not store file. Supported formats: JPEG, PNG, WEBP, PDF (max 15MB)."
            );
        }
    }

    protected function handleTextCommand(WhatsAppIdentity $identity, array $rawMessage): void
    {
        $text = trim($rawMessage['text']['body'] ?? '');
        $user = $identity->user;
        $userTz = $user->timezone ?? 'Asia/Dhaka';

        // Call NVIDIA NIM service
        $interpretation = $this->aiService->parseReminderPrompt($text, $userTz, $user->id);

        if (! $interpretation->success) {
            $this->apiService->sendText(
                $identity->phone_number,
                "I couldn't process your request: " . ($interpretation->errorMessage ?? 'Please try again.')
            );
            return;
        }

        // Handle based on intent
        switch ($interpretation->intent) {
            case 'create_reminder':
                $this->executeCreateReminder($identity, $interpretation, $userTz);
                break;

            case 'list_reminders':
                $this->executeListReminders($identity);
                break;

            case 'cancel_reminder':
                $this->executeCancelReminder($identity, $interpretation);
                break;

            case 'reschedule_reminder':
                $this->executeRescheduleReminder($identity, $interpretation, $userTz);
                break;

            case 'complete_reminder':
                $this->executeCompleteReminder($identity, $interpretation);
                break;

            case 'retrieve_file':
                $this->executeRetrieveFile($identity, $interpretation);
                break;

            default:
                $this->apiService->sendText(
                    $identity->phone_number,
                    "Hello! I am Remique AI. You can ask me to:\n• Set reminders ('Kal shokal 9 tay meeting')\n• List reminders ('আজকের সব reminder দেখাও')\n• Cancel reminders ('ওই reminder বাতিল করো')\n• Retrieve files ('আমার PDF invoice-টা দাও')"
                );
                break;
        }
    }

    protected function executeCreateReminder(WhatsAppIdentity $identity, $result, string $userTz): void
    {
        if ($result->hasMissingInfo) {
            $question = $result->clarificationQuestion ?? "Could you please specify what date and time you'd like this reminder for?";
            $this->apiService->sendText($identity->phone_number, "❓ " . $question);
            return;
        }

        try {
            // Build UTC due_at timestamp
            $date = $result->date ?? Carbon::now($userTz)->toDateString();
            $time = $result->time ?? '09:00:00';
            $localDateTime = Carbon::createFromFormat('Y-m-d H:i:s', "{$date} {$time}", $userTz);
            $utcDueAt = $localDateTime->copy()->setTimezone('UTC');

            $reminder = Reminder::create([
                'user_id' => $identity->user_id,
                'title' => $result->title,
                'description' => $result->description,
                'due_at' => $utcDueAt,
                'status' => 'scheduled',
                'priority' => $result->priority ?? 'medium',
                'recurrence' => $result->recurrence ?? 'none',
            ]);

            $recurrenceText = $reminder->recurrence !== 'none' ? " (Repeats: {$reminder->recurrence})" : '';
            $formattedTime = $localDateTime->format('M d, Y h:i A');

            $this->apiService->sendText(
                $identity->phone_number,
                "✅ Reminder set!\n\n📌 Title: {$reminder->title}\n⏰ When: {$formattedTime} ({$userTz}){$recurrenceText}"
            );
        } catch (\Throwable $e) {
            Log::error('Failed to create reminder via WhatsApp: ' . $e->getMessage());
            $this->apiService->sendText($identity->phone_number, "⚠️ Failed to save reminder. Please specify a valid date and time.");
        }
    }

    protected function executeListReminders(WhatsAppIdentity $identity): void
    {
        $userTz = $identity->user->timezone ?? 'Asia/Dhaka';
        $reminders = Reminder::forUser($identity->user_id)
            ->scheduled()
            ->orderBy('due_at')
            ->limit(5)
            ->get();

        if ($reminders->isEmpty()) {
            $this->apiService->sendText($identity->phone_number, "📋 You have no upcoming reminders scheduled.");
            return;
        }

        $list = "📋 Your Upcoming Reminders:\n\n";
        foreach ($reminders as $idx => $rem) {
            $localTime = $rem->due_at->copy()->setTimezone($userTz)->format('M d, Y h:i A');
            $rec = $rem->recurrence !== 'none' ? " [{$rem->recurrence}]" : '';
            $num = $idx + 1;
            $list .= "{$num}. {$rem->title}\n   ⏰ {$localTime}{$rec}\n";
        }

        $this->apiService->sendText($identity->phone_number, trim($list));
    }

    protected function executeCancelReminder(WhatsAppIdentity $identity, $result): void
    {
        $identifier = $result->targetReminderIdentifier ?? $result->title;

        $qb = Reminder::forUser($identity->user_id)->scheduled();
        if ($identifier) {
            $qb->where('title', 'like', "%{$identifier}%");
        }

        $reminder = $qb->latest('due_at')->first();

        if (! $reminder) {
            $this->apiService->sendText($identity->phone_number, "Could not find any scheduled reminder matching your request to cancel.");
            return;
        }

        $reminder->markAsCancelled();
        $this->apiService->sendText($identity->phone_number, "🗑️ Cancelled reminder: '{$reminder->title}'");
    }

    protected function executeRescheduleReminder(WhatsAppIdentity $identity, $result, string $userTz): void
    {
        $identifier = $result->targetReminderIdentifier ?? $result->title;

        $qb = Reminder::forUser($identity->user_id)->scheduled();
        if ($identifier) {
            $qb->where('title', 'like', "%{$identifier}%");
        }
        $reminder = $qb->latest('due_at')->first();

        if (! $reminder) {
            $this->apiService->sendText($identity->phone_number, "Could not find the reminder you want to reschedule.");
            return;
        }

        if (empty($result->time) && empty($result->date)) {
            $this->apiService->sendText($identity->phone_number, "❓ What new date or time would you like for '{$reminder->title}'?");
            return;
        }

        $currentLocal = $reminder->due_at->copy()->setTimezone($userTz);
        $newDate = $result->date ?? $currentLocal->toDateString();
        $newTime = $result->time ?? $currentLocal->toTimeString();

        $newDueAt = Carbon::createFromFormat('Y-m-d H:i:s', "{$newDate} {$newTime}", $userTz)->setTimezone('UTC');
        $reminder->due_at = $newDueAt;
        $reminder->save();

        $formatted = Carbon::createFromFormat('Y-m-d H:i:s', "{$newDate} {$newTime}", $userTz)->format('M d, Y h:i A');
        $this->apiService->sendText($identity->phone_number, "🕒 Rescheduled '{$reminder->title}' to {$formatted} ({$userTz}).");
    }

    protected function executeCompleteReminder(WhatsAppIdentity $identity, $result): void
    {
        $identifier = $result->targetReminderIdentifier ?? $result->title;

        $qb = Reminder::forUser($identity->user_id)->scheduled();
        if ($identifier) {
            $qb->where('title', 'like', "%{$identifier}%");
        }
        $reminder = $qb->latest('due_at')->first();

        if (! $reminder) {
            $this->apiService->sendText($identity->phone_number, "Could not find any active reminder matching your request to complete.");
            return;
        }

        $reminder->markAsCompleted();
        $this->apiService->sendText($identity->phone_number, "🎉 Marked '{$reminder->title}' as completed!");
    }

    protected function executeRetrieveFile(WhatsAppIdentity $identity, $result): void
    {
        $query = $result->searchQuery ?? $result->title ?? '';
        $matches = $this->vaultService->searchFiles($identity->user, $query, $result->fileType, limit: 5);

        if ($matches->isEmpty()) {
            $this->apiService->sendText($identity->phone_number, "🔍 Could not find any files matching '{$query}' in your Memory Vault.");
            return;
        }

        // If exactly 1 match, send the file directly!
        if ($matches->count() === 1) {
            $file = $matches->first();
            $filePath = $this->vaultService->getFilePathForUser($identity->user, $file->id);

            if (! $filePath) {
                $this->apiService->sendText($identity->phone_number, "File reference exists but physical file could not be read.");
                return;
            }

            // Upload media to Meta Cloud API to obtain media ID for outbound send
            $mediaId = $this->apiService->uploadMedia($filePath, $file->mime_type);

            if ($mediaId) {
                if ($file->isImage()) {
                    $this->apiService->sendImage($identity->phone_number, $mediaId, $file->caption ?? $file->original_filename, isMediaId: true);
                } else {
                    $this->apiService->sendDocument($identity->phone_number, $mediaId, $file->caption, $file->original_filename, isMediaId: true);
                }
            } else {
                $this->apiService->sendText($identity->phone_number, "Found '{$file->original_filename}' but could not transmit file over WhatsApp at this moment.");
            }
            return;
        }

        // If multiple matches, provide disambiguation list
        $msg = "🔍 I found {$matches->count()} matching files in your Memory Vault:\n\n";
        foreach ($matches as $i => $m) {
            $num = $i + 1;
            $uploadedDate = $m->created_at->format('M d, Y');
            $msg .= "{$num}. {$m->original_filename} ({$m->formattedSize()}, {$uploadedDate})\n";
        }
        $msg .= "\nPlease specify which one you'd like by name.";

        $this->apiService->sendText($identity->phone_number, $msg);
    }

    protected function extractBody(array $rawMessage): ?string
    {
        $type = $rawMessage['type'] ?? 'text';
        if ($type === 'text') {
            return $rawMessage['text']['body'] ?? null;
        }
        if (in_array($type, ['image', 'document'])) {
            return $rawMessage[$type]['caption'] ?? null;
        }
        return null;
    }
}
