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
        $lowerText = mb_strtolower(trim($text));

        // 1. Friendly Conversational Greetings & Bot Info
        $greetingPatterns = [
            'kemon aso', 'kemon acho', 'kemon achen', 'ki khobor', 'ki obstha',
            'hello', 'hi', 'hey', 'salam', 'assalamu', 'ke tumi', 'tumi ke', 'who are you', 'help',
        ];
        foreach ($greetingPatterns as $pattern) {
            if (str_contains($lowerText, $pattern)) {
                $this->botService->sendMessage(
                    $chatId,
                    "আলহামদুলিল্লাহ, ভালো আছি! 😊\n\nআমি <b>Remique</b>, আপনার পার্সোনাল এআই অ্যাসিস্ট্যান্ট।\n\nআপনি আমাকে যেকোনো স্বাভাবিক ভাষায় বলতে পারেন:\n• ⏰ <b>রিমাইন্ডার সেট করতে:</b> <i>'কাল সকাল ৯টায় মিটিং'</i> বা <i>'আজ রাত ১০টায় ঔষধ'</i>\n• 📋 <b>রিমাইন্ডার দেখতে:</b> <i>'আমার সব রিমাইন্ডার দেখাও'</i>\n• 📁 <b>মেমোরি ভল্ট:</b> যেকোনো ছবি বা PDF ফাইল পাঠালে আমি তা নিরাপদে জমা রাখবো এবং পরবর্তীতে খুঁজে দেব।\n\nআপনাকে আজ কীভাবে সাহায্য করতে পারি?"
                );
                return;
            }
        }

        // 2. Check for vault file retrieval intent
        $fileKeywords = ['dekhaw', 'dekhao', 'dakhaw', 'khuje dao', 'khuje daw', 'send my', 'show my', 'get my', 'find my', 'invoice', 'receipt', 'bill'];
        $isSearchIntent = false;
        foreach ($fileKeywords as $kw) {
            if (str_contains($lowerText, $kw)) {
                $isSearchIntent = true;
                break;
            }
        }

        if ($isSearchIntent) {
            $results = $this->vaultService->searchFiles($user, $text, limit: 3);
            if ($results->isNotEmpty()) {
                $match = $results->first();
                $path = $this->vaultService->getFilePathForUser($user, $match->id);
                if ($path && file_exists($path)) {
                    if ($match->isPdf()) {
                        $this->botService->sendDocument($chatId, $path, "📄 Here is your file: {$match->original_filename}");
                    } else {
                        $this->botService->sendPhoto($chatId, $path, "🖼️ Here is your file: {$match->original_filename}");
                    }
                    return;
                }
            }
        }

        // 3. Natural language parsing via NVIDIA NIM
        $userTz = $user->timezone ?: 'Asia/Dhaka';
        $interpretation = $this->aiService->parseReminderPrompt($text, $userTz, $user->id);

        // Handle list reminders intent
        if ($interpretation->intent === 'list_reminders' || str_contains($lowerText, 'reminder dekhao') || str_contains($lowerText, 'list')) {
            $reminders = Reminder::forUser($user->id)
                ->scheduled()
                ->orderBy('due_at')
                ->limit(5)
                ->get();

            if ($reminders->isEmpty()) {
                $this->botService->sendMessage($chatId, "📋 আপনার কোনো পেন্ডিং রিমাইন্ডার শিডিউল করা নেই।\n\nনতুন রিমাইন্ডার সেট করতে লিখুন: <i>'কাল সকাল ১০টায় প্রজেক্ট রিভিউ'</i>");
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

        if (! $interpretation->success || $interpretation->hasMissingInfo) {
            $clarification = $interpretation->clarificationQuestion
                ?: "আমি রিমাইন্ডারের সময় বুঝতে পারিনি। যেমন এভাবে লিখুন:\n• <i>'কাল সকাল ৯টায় মিটিং'</i>\n• <i>'আজ রাত ১০টায় ঔষধ খেতে হবে'</i>\n• <i>'Remind me tomorrow at 3pm to call client'</i>";

            $this->botService->sendMessage($chatId, $clarification);
            return;
        }

        // 4. Persist Reminder
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
        $langDetected = ucfirst($interpretation->detectedLanguage ?? 'auto');

        $reply = "🔔 <b>Reminder Scheduled!</b>\n\n" .
                 "📌 <b>Title:</b> {$reminder->title}\n" .
                 "⏰ <b>Due:</b> {$formattedTime} ({$userTz})\n" .
                 "🔁 <b>Recurrence:</b> " . ucfirst($reminder->recurrence) . "\n" .
                 "🎯 <b>Language:</b> {$langDetected}\n\n" .
                 "<i>সময়মতো টেলিগ্রামে আপনাকে জানিয়ে দেব!</i>";

        $this->botService->sendMessage($chatId, $reply);
    }
}
