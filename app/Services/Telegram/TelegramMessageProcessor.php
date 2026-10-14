<?php

namespace App\Services\Telegram;

use App\Models\AuditLog;
use App\Models\Reminder;
use App\Models\TelegramIdentity;
use App\Models\User;
use App\Services\AI\NvidiaNimService;
use App\Services\ReminderService;
use App\Services\Vault\VaultService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TelegramMessageProcessor
{
    public function __construct(
        protected TelegramBotService $botService,
        protected NvidiaNimService $aiService,
        protected ReminderService $reminderService,
        protected VaultService $vaultService
    ) {}

    /**
     * Process an incoming Telegram webhook update.
     */
    public function processUpdate(array $update): void
    {
        $message = $update['message'] ?? null;
        if (! $message) {
            return;
        }

        $chatId = (string) ($message['chat']['id'] ?? '');
        if (empty($chatId)) {
            return;
        }

        $from = $message['from'] ?? [];
        $username = $from['username'] ?? null;
        $firstName = $from['first_name'] ?? null;
        $text = trim($message['text'] ?? $message['caption'] ?? '');

        // 1. Look up existing TelegramIdentity
        $identity = TelegramIdentity::with('user')->where('chat_id', $chatId)->first();

        // 2. Handle /start command (including deep link /start <token>)
        if (str_starts_with($text, '/start')) {
            $this->handleStartCommand($chatId, $username, $firstName, $text, $identity);
            return;
        }

        // 3. Handle unlinked or pending verification
        if (! $identity || $identity->isPending()) {
            $this->handleVerificationAttempt($chatId, $username, $firstName, $text, $identity);
            return;
        }

        if ($identity->isBlocked() || ! $identity->isActive() || ! $identity->user) {
            $this->botService->sendMessage(
                $chatId,
                "⚠️ Your Remique Telegram access is currently inactive or restricted. Please check your web dashboard."
            );
            return;
        }

        // Update inbound activity timestamp
        $identity->update(['last_inbound_at' => Carbon::now()]);
        $user = $identity->user;

        // 4. Handle incoming Photo
        if (! empty($message['photo'])) {
            $this->handlePhotoUpload($user, $chatId, $message['photo'], $text);
            return;
        }

        // 5. Handle incoming Document (PDF, etc.)
        if (! empty($message['document'])) {
            $this->handleDocumentUpload($user, $chatId, $message['document'], $text);
            return;
        }

        // 6. Handle natural language text commands
        if (! empty($text)) {
            $this->handleTextCommand($user, $chatId, $text);
        }
    }

    protected function handleStartCommand(
        string $chatId,
        ?string $username,
        ?string $firstName,
        string $text,
        ?TelegramIdentity $identity
    ): void {
        $parts = explode(' ', $text);
        $token = isset($parts[1]) ? strtoupper(trim($parts[1])) : null;

        if ($token) {
            // Attempt to bind token directly
            $pendingIdentity = TelegramIdentity::where('verification_token', $token)
                ->where('status', 'pending_verification')
                ->where('verification_expires_at', '>', Carbon::now())
                ->first();

            if ($pendingIdentity) {
                $pendingIdentity->update([
                    'chat_id' => $chatId,
                    'username' => $username,
                    'first_name' => $firstName,
                    'status' => 'active',
                    'verified_at' => Carbon::now(),
                    'verification_token' => null,
                    'verification_expires_at' => null,
                    'last_inbound_at' => Carbon::now(),
                ]);

                AuditLog::record(
                    'telegram.account_linked',
                    $pendingIdentity,
                    'Telegram account linked and verified via deep link token',
                    ['chat_id' => $chatId, 'username' => $username],
                    $pendingIdentity->user_id
                );

                $this->botService->sendMessage(
                    $chatId,
                    "🎉 <b>Welcome to Remique, {$firstName}!</b>\n\nYour Telegram account is now successfully linked to your Remique workspace.\n\n• <b>Natural Reminders:</b> Type in Bengali, Banglish, or English (e.g. <i>'Kal shokal 9 tay meeting'</i> or <i>'আজ রাত ১০টায় ঔষধ'</i>).\n• <b>Private Vault:</b> Send receipts, bills, and documents here to store securely."
                );
                return;
            }
        }

        if ($identity && $identity->isActive()) {
            $this->botService->sendMessage(
                $chatId,
                "👋 Hello {$firstName}! Your Remique assistant is active.\n\nSend any reminder prompt or document anytime."
            );
            return;
        }

        // Prompt user to link
        $this->botService->sendMessage(
            $chatId,
            "👋 <b>Welcome to Remique AI!</b>\n\nTo link this Telegram chat with your account, please enter the 6-character verification code from your Remique Web Settings, or open the direct link from your dashboard.\n\n<i>Reply with your code here to verify.</i>"
        );
    }

    protected function handleVerificationAttempt(
        string $chatId,
        ?string $username,
        ?string $firstName,
        string $text,
        ?TelegramIdentity $identity
    ): void {
        $code = strtoupper(trim($text));

        // Search for identity with this token
        $pendingIdentity = TelegramIdentity::where('verification_token', $code)
            ->where('verification_expires_at', '>', Carbon::now())
            ->first();

        if ($pendingIdentity) {
            $pendingIdentity->update([
                'chat_id' => $chatId,
                'username' => $username,
                'first_name' => $firstName,
                'status' => 'active',
                'verified_at' => Carbon::now(),
                'verification_token' => null,
                'verification_expires_at' => null,
                'last_inbound_at' => Carbon::now(),
            ]);

            AuditLog::record(
                'telegram.account_linked',
                $pendingIdentity,
                'Telegram account verified via entered token',
                ['chat_id' => $chatId],
                $pendingIdentity->user_id
            );

            $this->botService->sendMessage(
                $chatId,
                "✅ <b>Verification Successful!</b>\n\nYour Telegram account is now connected to Remique. You can now set reminders and store private documents anytime."
            );
            return;
        }

        $this->botService->sendMessage(
            $chatId,
            "⚠️ Invalid or expired verification code. Please check your Remique Web Settings for your active code."
        );
    }

    protected function handlePhotoUpload(User $user, string $chatId, array $photoSizes, string $caption): void
    {
        // Get highest resolution photo (last element in array)
        $largest = end($photoSizes);
        $fileId = $largest['file_id'] ?? null;

        if (! $fileId) {
            $this->botService->sendMessage($chatId, "❌ Failed to read photo data.");
            return;
        }

        $fileInfo = $this->botService->getFile($fileId);
        $filePath = $fileInfo['file_path'] ?? null;

        if (! $filePath) {
            $this->botService->sendMessage($chatId, "❌ Could not retrieve photo from Telegram.");
            return;
        }

        $binary = $this->botService->downloadFile($filePath);
        if (! $binary) {
            $this->botService->sendMessage($chatId, "❌ Could not download photo.");
            return;
        }

        // Store into Memory Vault
        $vaultFile = $this->vaultService->storeMedia(
            $user,
            $binary,
            'image/jpeg',
            basename($filePath),
            $caption
        );

        if ($vaultFile) {
            $this->botService->sendMessage(
                $chatId,
                "📁 <b>Photo saved to Memory Vault!</b>\n\n<b>Name:</b> {$vaultFile->original_filename}\n<b>Size:</b> {$vaultFile->formattedSize()}\n\n<i>Ask me anytime: 'Show me my {$vaultFile->category}'</i>"
            );
        } else {
            $this->botService->sendMessage(
                $chatId,
                "❌ Could not store photo. Supported formats: JPEG, PNG, WEBP, PDF."
            );
        }
    }

    protected function handleDocumentUpload(User $user, string $chatId, array $document, string $caption): void
    {
        $fileId = $document['file_id'] ?? null;
        $fileName = $document['file_name'] ?? 'document.pdf';
        $mimeType = $document['mime_type'] ?? 'application/pdf';

        if (! $fileId) {
            $this->botService->sendMessage($chatId, "❌ Missing document ID.");
            return;
        }

        $fileInfo = $this->botService->getFile($fileId);
        $filePath = $fileInfo['file_path'] ?? null;

        if (! $filePath) {
            $this->botService->sendMessage($chatId, "❌ Could not locate document.");
            return;
        }

        $binary = $this->botService->downloadFile($filePath);
        if (! $binary) {
            $this->botService->sendMessage($chatId, "❌ Could not download document.");
            return;
        }

        $vaultFile = $this->vaultService->storeMedia(
            $user,
            $binary,
            $mimeType,
            $fileName,
            $caption
        );

        if ($vaultFile) {
            $this->botService->sendMessage(
                $chatId,
                "📁 <b>Document saved to Memory Vault!</b>\n\n<b>Name:</b> {$vaultFile->original_filename}\n<b>Size:</b> {$vaultFile->formattedSize()}\n<b>Category:</b> " . ucfirst($vaultFile->category) . "\n\n<i>You can retrieve it anytime by describing it.</i>"
            );
        } else {
            $this->botService->sendMessage(
                $chatId,
                "❌ Document rejected. Supported formats: PDF, JPEG, PNG, WEBP (Max 15MB)."
            );
        }
    }

    protected function handleTextCommand(User $user, string $chatId, string $text): void
    {
        $userTz = $user->timezone ?: 'Asia/Dhaka';

        // 1. Direct AI interpretation for ALL user inputs (No preset messages)
        $interpretation = $this->aiService->parseReminderPrompt($text, $userTz, $user->id);

        if (! $interpretation->success) {
            $errorMessage = $interpretation->errorMessage
                ?: "AI সার্ভিস এই মুহূর্তে রেসপন্স করছে না। দয়া করে আপনার .env ফাইলে NVIDIA_NIM_API_KEY কনফিগার করা আছে কি না যাচাই করুন।";
            $this->botService->sendMessage($chatId, "⚠️ " . $errorMessage);
            return;
        }

        // 2. Intent: Retrieve file from vault
        if ($interpretation->intent === 'retrieve_file') {
            $query = $interpretation->searchQuery ?: $text;
            $results = $this->vaultService->searchFiles($user, $query, limit: 3);
            if ($results->isNotEmpty()) {
                $match = $results->first();
                $path = $this->vaultService->getFilePathForUser($user, $match->id);
                if ($path && file_exists($path)) {
                    $caption = $interpretation->conversationalReply ?: "📄 {$match->original_filename}";
                    if ($match->isPdf()) {
                        $this->botService->sendDocument($chatId, $path, $caption);
                    } else {
                        $this->botService->sendPhoto($chatId, $path, $caption);
                    }
                    return;
                }
            }

            $reply = $interpretation->conversationalReply ?: "আপনার মেমোরি ভল্টে '{$query}' সম্পর্কিত কোনো ফাইল খুঁজে পাওয়া যায়নি।";
            $this->botService->sendMessage($chatId, $reply);
            return;
        }

        // 3. Intent: List reminders
        if ($interpretation->intent === 'list_reminders') {
            $reminders = Reminder::forUser($user->id)
                ->scheduled()
                ->orderBy('due_at')
                ->limit(5)
                ->get();

            if ($reminders->isEmpty()) {
                $this->botService->sendMessage($chatId, "📋 আপনার এই মুহূর্তে কোনো পেন্ডিং রিমাইন্ডার শিডিউল করা নেই।");
                return;
            }

            $list = "📋 <b>আপনার আসন্ন রিমাইন্ডারসমূহ:</b>\n\n";
            foreach ($reminders as $idx => $rem) {
                $localTime = $rem->due_at->copy()->setTimezone($userTz)->format('M d, Y h:i A');
                $rec = $rem->recurrence !== 'none' ? " [{$rem->recurrence}]" : '';
                $num = $idx + 1;
                $list .= "<b>{$num}. {$rem->title}</b>\n   ⏰ {$localTime}{$rec}\n\n";
            }

            $this->botService->sendMessage($chatId, trim($list));
            return;
        }

        // 4. Intent: Cancel reminder
        if ($interpretation->intent === 'cancel_reminder') {
            $identifier = $interpretation->targetReminderIdentifier ?? $interpretation->title;
            $qb = Reminder::forUser($user->id)->scheduled();
            if ($identifier) {
                $qb->where('title', 'like', "%{$identifier}%");
            }
            $reminder = $qb->latest('due_at')->first();
            if ($reminder) {
                $reminder->markAsCancelled();
                $reply = $interpretation->conversationalReply ?: "🗑️ '{$reminder->title}' রিমাইন্ডারটি বাতিল করা হয়েছে।";
                $this->botService->sendMessage($chatId, $reply);
            } else {
                $reply = $interpretation->conversationalReply ?: "বাতিল করার মতো কোনো রিমাইন্ডার পাওয়া যায়নি।";
                $this->botService->sendMessage($chatId, $reply);
            }
            return;
        }

        // 5. Intent: Create reminder
        if ($interpretation->intent === 'create_reminder' && ! $interpretation->hasMissingInfo) {
            $date = $interpretation->date ?? Carbon::now($userTz)->toDateString();
            $time = $interpretation->time ?? '09:00:00';
            $dueAtInUserTz = Carbon::createFromFormat('Y-m-d H:i:s', "{$date} {$time}", $userTz);
            $dueAtUtc = $dueAtInUserTz->copy()->setTimezone('UTC');

            $reminder = Reminder::create([
                'user_id' => $user->id,
                'title' => $interpretation->title ?? 'Reminder',
                'description' => $interpretation->description,
                'due_at' => $dueAtUtc,
                'status' => 'scheduled',
                'priority' => $interpretation->priority ?? 'medium',
                'recurrence' => $interpretation->recurrence ?? 'none',
            ]);

            $formattedTime = $dueAtInUserTz->format('M d, Y h:i A');
            $aiReply = $interpretation->conversationalReply;

            $reply = "🔔 <b>Reminder Scheduled!</b>\n\n" .
                     ($aiReply ? "{$aiReply}\n\n" : '') .
                     "📌 <b>Title:</b> {$reminder->title}\n" .
                     "⏰ <b>Due:</b> {$formattedTime} ({$userTz})\n" .
                     "🔁 <b>Recurrence:</b> " . ucfirst($reminder->recurrence);

            $this->botService->sendMessage($chatId, $reply);
            return;
        }

        // 6. Clarification question if information is missing
        if ($interpretation->hasMissingInfo && $interpretation->clarificationQuestion) {
            $this->botService->sendMessage($chatId, $interpretation->clarificationQuestion);
            return;
        }

        // 7. Pure AI-generated conversational reply
        if (! empty($interpretation->conversationalReply)) {
            $this->botService->sendMessage($chatId, $interpretation->conversationalReply);
            return;
        }

        // Fallback
        $this->botService->sendMessage($chatId, "আমি আপনার কথাটি বুঝতে পেরেছি। আপনার কোনো রিমাইন্ডার বা সাহায্য প্রয়োজন হলে বলুন।");
    }
}
