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
        $userName = $user->name ? explode(' ', $user->name)[0] : 'ভাই';
        $userTz = $user->timezone ?: 'Asia/Dhaka';

        // 1. Natural Human-like Conversational Queries & Chat
        if ($this->handleConversationalChat($user, $chatId, $lowerText, $userName)) {
            return;
        }

        // 2. Check for vault file retrieval intent
        $fileKeywords = ['dekhaw', 'dekhao', 'dakhaw', 'khuje dao', 'khuje daw', 'send my', 'show my', 'get my', 'find my', 'invoice', 'receipt', 'bill', 'file dao'];
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
                        $this->botService->sendDocument($chatId, $path, "📄 এই যে {$userName} ভাই, আপনার ফাইল: {$match->original_filename}");
                    } else {
                        $this->botService->sendPhoto($chatId, $path, "🖼️ এই যে {$userName} ভাই, আপনার ফাইল: {$match->original_filename}");
                    }
                    return;
                }
            } else {
                $this->botService->sendMessage($chatId, "🔍 {$userName} ভাই, এই নামে আপনার মেমোরি ভল্টে কোনো ফাইল খুঁজে পাওয়া যায়নি। আপনি কোনো ছবি বা PDF পাঠালে আমি তা নিরাপদে রেখে দেব।");
                return;
            }
        }

        // 3. List reminders intent
        if (str_contains($lowerText, 'reminder dekhao') || str_contains($lowerText, 'list') || str_contains($lowerText, 'ki ki reminder') || str_contains($lowerText, 'sob reminder')) {
            $this->respondWithReminderList($user, $chatId, $userTz, $userName);
            return;
        }

        // 4. Natural language reminder parsing (via NVIDIA NIM or smart local engine)
        $reminderData = null;

        $interpretation = $this->aiService->parseReminderPrompt($text, $userTz, $user->id);
        if ($interpretation->conversationalReply && $interpretation->intent === 'general_chat') {
            $this->botService->sendMessage($chatId, $interpretation->conversationalReply);
            return;
        }
        if ($interpretation->success && ! $interpretation->hasMissingInfo) {
            $reminderData = [
                'title' => $interpretation->title ?? 'Reminder',
                'date' => $interpretation->date ?? Carbon::now($userTz)->toDateString(),
                'time' => $interpretation->time ?? '09:00:00',
                'recurrence' => $interpretation->recurrence ?? 'none',
                'priority' => $interpretation->priority ?? 'medium',
                'lang' => ucfirst($interpretation->detectedLanguage ?? 'auto'),
            ];
        }

        // If AI not configured or didn't extract, try smart local extractor
        if (! $reminderData) {
            $reminderData = $this->extractLocalReminder($lowerText, $userTz);
        }

        if ($reminderData) {
            $dueAtInUserTz = Carbon::createFromFormat('Y-m-d H:i:s', "{$reminderData['date']} {$reminderData['time']}", $userTz);
            $dueAtUtc = $dueAtInUserTz->copy()->setTimezone('UTC');

            $reminder = Reminder::create([
                'user_id' => $user->id,
                'title' => $reminderData['title'],
                'due_at' => $dueAtUtc,
                'status' => 'scheduled',
                'priority' => $reminderData['priority'],
                'recurrence' => $reminderData['recurrence'],
            ]);

            $formattedTime = $dueAtInUserTz->format('M d, Y h:i A');
            $reply = "🔔 <b>Reminder Scheduled!</b>\n\n" .
                     "অবশ্যই {$userName} ভাই! রিমাইন্ডার সেট করে দিয়েছি:\n" .
                     "📌 <b>কাজের নাম:</b> {$reminder->title}\n" .
                     "⏰ <b>সময়:</b> {$formattedTime} ({$userTz})\n" .
                     "🔁 <b>পুনরাবৃত্তি:</b> " . ucfirst($reminder->recurrence) . "\n\n" .
                     "<i>সময় হলেই আপনাকে টেলিগ্রামে মনে করিয়ে দেবো! আর কোনো কাজ থাকলে বলতে পারেন।</i> 😊";

            $this->botService->sendMessage($chatId, $reply);
            return;
        }

        // 5. Friendly conversational fallback for any other natural input
        $this->botService->sendMessage(
            $chatId,
            "হ্যাঁ {$userName} ভাই! 😊 আমি আপনার কথা বুঝতে পেরেছি।\n\nআপনি মানুষের সাথে যেভাবে চ্যাট করেন, সেভাবেই আমাকে সব বলতে পারেন। যেমন:\n• <i>'কাল সকাল ৯টায় মিটিং আছে মনে করায় দিও'</i>\n• <i>'আজ রাত ১০টায় ঔষধ খেতে হবে'</i>\n• <i>'আমার সব রিমাইন্ডার দেখাও'</i>\n• অথবা যেকোনো রসিদ বা জরুরি ছবি/PDF পাঠালে তা আমি ভল্টে গুছিয়ে রাখবো।\n\nবলুন ভাই, আপনাকে আর কীভাবে সাহায্য করতে পারি?"
        );
    }

    /**
     * Handle human-like conversational chitchat and greetings.
     */
    protected function handleConversationalChat(User $user, string $chatId, string $lowerText, string $userName): bool
    {
        // Check for "talk normally" / "chat like human" inquiry
        if (
            str_contains($lowerText, 'normaly') ||
            str_contains($lowerText, 'chat kori') ||
            str_contains($lowerText, 'kotha bolte') ||
            str_contains($lowerText, 'user er sathe') ||
            str_contains($lowerText, 'manusher moto')
        ) {
            $this->botService->sendMessage(
                $chatId,
                "হ্যাঁ {$userName} ভাই! আপনি একদম রক্ত-মাংসের মানুষের মতো করেই আমার সাথে সাধারণ ভাষায় কথা বলতে পারবেন। 😊\n\nকোনো ফরমাল কমান্ড লাগবে না — আপনি যেভাবে বন্ধুদের সাথে চ্যাট করেন, আমাকেও সেভাবেই কথা বলুন।\n\nযেমন আপনি বলতে পারেন:\n• <i>'কাল সকাল ৯টায় ক্লায়েন্টকে কল দিতে হবে মনে করাই দিস'</i>\n• <i>'আজ রাত ১০টায় ঔষধ'</i>\n• <i>'আমার কী কী কাজ বাকি আছে দেখা তো'</i>\n\nআমি আপনার পুরো কথা বুঝে সেভাবে কাজ করে দেবো। কেমন আছেন বলুন?"
            );
            return true;
        }

        // Greetings & Well-being
        $wellBeingPatterns = ['kemon aso', 'kemon acho', 'kemon achen', 'kemon ado', 'valoni', 'bhalo achi', 'bhalo aso', 'are vai', 'vai kemon', 'bhai kemon'];
        foreach ($wellBeingPatterns as $p) {
            if (str_contains($lowerText, $p)) {
                $this->botService->sendMessage(
                    $chatId,
                    "আলহামদুলিল্লাহ {$userName} ভাই, আমি খুব ভালো আছি! 😊 আপনি কেমন আছেন? আপনার দিনকাল কেমন কাটছে?\n\nআপনার যেকোনো কাজের কথা বা রিমাইন্ডার থাকলে আমাকে নিশ্চিন্তে বলতে পারেন!"
                );
                return true;
            }
        }

        // Simple Hellos & Hi
        $helloPatterns = ['hello', 'hi', 'hy', 'hey', 'helo', 'heya', 'salam', 'assalamu', 'assalamualaikum'];
        foreach ($helloPatterns as $p) {
            if ($lowerText === $p || str_starts_with($lowerText, $p . ' ') || str_ends_with($lowerText, ' ' . $p)) {
                $this->botService->sendMessage(
                    $chatId,
                    "হ্যালো {$userName} ভাই! 👋 ওয়ালাইকুম আসসালাম। কেমন আছেন?\n\nআমি আপনার পার্সোনাল এআই অ্যাসিস্ট্যান্ট। আজ আপনাকে কীভাবে সাহায্য করতে পারি বলুন?"
                );
                return true;
            }
        }

        // Chitchat / What are you doing / What's up
        $activityPatterns = ['ki koro', 'ki korteso', 'ki korso', 'ki obostha', 'ki obstha', 'ki khobor', 'kire'];
        foreach ($activityPatterns as $p) {
            if (str_contains($lowerText, $p)) {
                $this->botService->sendMessage(
                    $chatId,
                    "এইতো {$userName} ভাই! আপনার সব রিমাইন্ডার আর মেমোরি ভল্ট পাহারা দিচ্ছি। 🤖\n\nআপনার আজকের কী কী প্ল্যান? কোনো রিমাইন্ডার সেট করে রাখতে হবে?"
                );
                return true;
            }
        }

        // Identity / Capabilities
        $identityPatterns = ['ke tumi', 'tumi ke', 'who are you', 'help', 'tumi ki korte paro', 'ki ki korte paro'];
        foreach ($identityPatterns as $p) {
            if (str_contains($lowerText, $p)) {
                $this->botService->sendMessage(
                    $chatId,
                    "আমি <b>Remique</b> — আপনার পার্সোনাল এআই অ্যাসিস্ট্যান্ট! 🤖✨\n\nআপনি স্বাভাবিক বাংলায় বা বাংলিশে আমার সাথে কথা বলে:\n1️⃣ ⏰ <b>রিমাইন্ডার সেট করতে পারেন:</b> যেমন <i>'কাল সকাল ৯টায় মিটিং'</i> বা <i>'আজ রাত ১০টায় ঔষধ'</i>\n2️⃣ 📋 <b>রিমাইন্ডার দেখতে পারেন:</b> <i>'আমার সব রিমাইন্ডার দেখাও'</i>\n3️⃣ 📁 <b>প্রাইভেট মেমোরি ভল্ট:</b> যেকোনো ছবি, ভাউচার বা PDF পাঠিয়ে দিলে আমি তা আজীবনের জন্য নিরাপদে রাখবো এবং চাইলে খুঁজে দেবো।\n\nআপনি একদম রক্ত-মাংসের মানুষের মতোই স্বাভাবিক ভাষায় যেকোনো নির্দেশ দিতে পারেন!"
                );
                return true;
            }
        }

        // Thanks
        $thanksPatterns = ['dhonnobad', 'thanks', 'thank you', 'shukriya', 'onek dhonnobad'];
        foreach ($thanksPatterns as $p) {
            if (str_contains($lowerText, $p)) {
                $this->botService->sendMessage(
                    $chatId,
                    "আপনাকে অনেক স্বাগতম {$userName} ভাই! 😊 যেকোনো প্রয়োজনে আমি সবসময় আপনার পাশে আছি।"
                );
                return true;
            }
        }

        return false;
    }

    /**
     * Respond with upcoming reminders list.
     */
    protected function respondWithReminderList(User $user, string $chatId, string $userTz, string $userName): void
    {
        $reminders = Reminder::forUser($user->id)
            ->scheduled()
            ->orderBy('due_at')
            ->limit(5)
            ->get();

        if ($reminders->isEmpty()) {
            $this->botService->sendMessage($chatId, "📋 {$userName} ভাই, আপনার এই মুহূর্তে কোনো পেন্ডিং রিমাইন্ডার নেই।\n\nনতুন রিমাইন্ডার সেট করতে চাইলে স্বাভাবিকভাবে লিখুন: <i>'কাল সকাল ১০টায় মিটিং'</i>");
            return;
        }

        $list = "📋 <b>{$userName} ভাই, আপনার আসন্ন রিমাইন্ডারসমূহ:</b>\n\n";
        foreach ($reminders as $idx => $rem) {
            $localTime = $rem->due_at->copy()->setTimezone($userTz)->format('M d, Y h:i A');
            $rec = $rem->recurrence !== 'none' ? " [{$rem->recurrence}]" : '';
            $num = $idx + 1;
            $list .= "<b>{$num}. {$rem->title}</b>\n   ⏰ {$localTime}{$rec}\n\n";
        }

        $this->botService->sendMessage($chatId, trim($list));
    }

    /**
     * Smart local NLP reminder extractor for Bengali & Banglish.
     */
    protected function extractLocalReminder(string $text, string $userTz): ?array
    {
        $date = null;
        $time = null;

        // Date detection
        if (str_contains($text, 'kal') || str_contains($text, 'কাল') || str_contains($text, 'tomorrow') || str_contains($text, 'agamikal') || str_contains($text, 'আগামীকাল')) {
            $date = Carbon::now($userTz)->addDay()->toDateString();
        } elseif (str_contains($text, 'porshu') || str_contains($text, 'পরশু')) {
            $date = Carbon::now($userTz)->addDays(2)->toDateString();
        } elseif (str_contains($text, 'aj') || str_contains($text, 'আজ') || str_contains($text, 'today') || str_contains($text, 'ajke') || str_contains($text, 'আজকে')) {
            $date = Carbon::now($userTz)->toDateString();
        }

        // Time detection
        if (preg_match('/(?:shokal|সকাল)\s*(\d{1,2})/u', $text, $m)) {
            $hour = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $time = "{$hour}:00:00";
        } elseif (preg_match('/(?:dupur|দুপুর)\s*(\d{1,2})/u', $text, $m)) {
            $h = (int) $m[1];
            $h = $h < 12 ? $h + 12 : $h;
            $time = str_pad($h, 2, '0', STR_PAD_LEFT) . ":00:00";
        } elseif (preg_match('/(?:bikal|বিকাল)\s*(\d{1,2})/u', $text, $m)) {
            $h = (int) $m[1];
            $h = $h < 12 ? $h + 12 : $h;
            $time = str_pad($h, 2, '0', STR_PAD_LEFT) . ":00:00";
        } elseif (preg_match('/(?:rat|রাত)\s*(\d{1,2})/u', $text, $m)) {
            $h = (int) $m[1];
            $h = $h < 12 ? $h + 12 : $h;
            $time = str_pad($h, 2, '0', STR_PAD_LEFT) . ":00:00";
        } elseif (preg_match('/(\d{1,2})\s*(?:tay|ta|টায়|টায়|টা)/u', $text, $m)) {
            $h = (int) $m[1];
            if ($h <= 6) {
                $h += 12; // e.g. 3 tay -> 15:00
            }
            $time = str_pad($h, 2, '0', STR_PAD_LEFT) . ":00:00";
        } elseif (preg_match('/(\d{1,2})(?::(\d{2}))?\s*(am|pm)/i', $text, $m)) {
            $h = (int) $m[1];
            $min = isset($m[2]) && $m[2] !== '' ? $m[2] : '00';
            if (strtolower($m[3]) === 'pm' && $h < 12) {
                $h += 12;
            }
            $time = str_pad($h, 2, '0', STR_PAD_LEFT) . ":{$min}:00";
        }

        // If date or time found, clean up title
        if ($date || $time) {
            $cleanTitle = preg_replace('/(kal|কাল|porshu|পরশু|aj|আজ|ajke|আজকে|tomorrow|today|shokal|সকাল|dupur|দুপুর|bikal|বিকাল|rat|রাত|\d{1,2}\s*(?:tay|ta|টায়|টায়|টা)|remind|korio|dio|korba|koro|mone|koriye|reminder|ache)/ui', '', $text);
            $cleanTitle = trim(preg_replace('/\s+/', ' ', $cleanTitle));
            if (empty($cleanTitle) || strlen($cleanTitle) < 2) {
                $cleanTitle = 'Reminder';
            }

            return [
                'title' => ucfirst($cleanTitle),
                'date' => $date ?: Carbon::now($userTz)->toDateString(),
                'time' => $time ?: '09:00:00',
                'priority' => 'medium',
                'recurrence' => 'none',
                'lang' => 'Banglish',
            ];
        }

        return null;
    }
}
