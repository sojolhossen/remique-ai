<?php

namespace App\Services\Telegram;

use App\Models\AuditLog;
use App\Models\Reminder;
use App\Models\TelegramIdentity;
use App\Models\TelegramMessage;
use App\Models\User;
use App\Models\VaultFile;
use App\Services\AI\Contracts\AiServiceInterface;
use App\Services\ReminderService;
use App\Services\Vault\VaultService;
use Carbon\Carbon;
use App\Services\Digest\MorningDigestService;
use App\Services\Habits\HabitService;
use Illuminate\Support\Facades\Log;

class TelegramMessageProcessor
{
    public function __construct(
        protected TelegramBotService $botService,
        protected AiServiceInterface $aiService,
        protected ReminderService $reminderService,
        protected VaultService $vaultService,
        protected ?HabitService $habitService = null,
        protected ?MorningDigestService $digestService = null
    ) {
        $this->habitService = $habitService ?? app(HabitService::class);
        $this->digestService = $digestService ?? app(MorningDigestService::class);
    }

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
            $this->handleTextCommand($user, $identity, $chatId, $text, $message['message_id'] ?? null);
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

        $smartFileName = ! empty($caption)
            ? \Illuminate\Support\Str::slug(\Illuminate\Support\Str::limit($caption, 35), '_') . '.jpg'
            : 'Photo_' . Carbon::now()->format('Y-m-d_Hi') . '.jpg';

        // Store into Memory Vault
        $vaultFile = $this->vaultService->storeMedia(
            $user,
            $binary,
            'image/jpeg',
            $smartFileName,
            $caption
        );

        if ($vaultFile) {
            $displayName = ! empty($vaultFile->caption) ? $vaultFile->caption : $vaultFile->original_filename;
            $this->botService->sendMessage(
                $chatId,
                "📁 <b>Photo saved to Memory Vault!</b>\n\n<b>Name:</b> {$displayName}\n<b>Size:</b> {$vaultFile->formattedSize()}\n\n<i>Ask me anytime: 'Show me my {$vaultFile->category}'</i>"
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

    protected function handleTextCommand(
        User $user,
        TelegramIdentity $identity,
        string $chatId,
        string $text,
        mixed $messageId = null
    ): void {
        $userTz = $user->timezone ?: 'Asia/Dhaka';

        // Instant visual feedback on Telegram ("Remi is typing...")
        $this->botService->sendChatAction($chatId, 'typing');

        // 1. Record incoming user message into conversation history
        $inbound = TelegramMessage::create([
            'telegram_identity_id' => $identity->id,
            'chat_id' => $chatId,
            'direction' => 'inbound',
            'role' => 'user',
            'message_id' => $messageId ? (string) $messageId : null,
            'body' => $text,
        ]);

        $trimmedText = trim($text);

        // A. Quick command: Daily Morning Briefing (/briefing, /today, "ajker briefing", "shokal er briefing")
        if (preg_match('/^(\/briefing|\/today|briefing|morning briefing|ajker briefing|shokaler briefing|আজকের ব্রিফিং|মর্নিং ব্রিফিং)$/iu', $trimmedText)) {
            $digest = $this->digestService->generateDigest($user);
            $formatted = $this->digestService->formatTextMessage($digest);
            $this->sendReply($identity, $chatId, $formatted);
            return;
        }

        // B. Quick command: Habit List (/habits, "habits", "habit list", "হ্যাবিট দেখাও")
        if (preg_match('/^(\/habits|habits|habit list|my habits|হ্যাবিট|হ্যাবিট দেখাও|হ্যাবিট লিস্ট)$/iu', $trimmedText)) {
            $habits = $this->habitService->getUserHabitsWithStatus($user);
            if ($habits->isEmpty()) {
                $this->sendReply($identity, $chatId, "🔥 <b>No habits created yet!</b>\n\nVisit your Remique dashboard to add your daily habits, or add one from the web app at /habits.");
            } else {
                $msg = "🔥 <b>Your Daily Habits & Streaks:</b>\n\n";
                foreach ($habits as $h) {
                    $status = $h->is_completed_today ? '✅ Completed' : '⏳ Pending';
                    $streak = $h->current_streak > 0 ? "• 🔥 {$h->current_streak}d streak" : "";
                    $msg .= "{$status} — <b>{$h->name}</b> {$streak}\n";
                }
                $msg .= "\n<i>Reply 'done [habit name]' to check off!</i>";
                $this->sendReply($identity, $chatId, $msg);
            }
            return;
        }

        // C. Quick command: Mark habit done ("done workout", "complete water", "পানি শেষ", "ব্যায়াম শেষ")
        if (preg_match('/^(?:done|complete|finished|check|শেষ|করেছি)\s+(.+)$/iu', $trimmedText, $matches) ||
            preg_match('/^(.+)\s+(?:done|complete|finished|check|শেষ|করেছি)$/iu', $trimmedText, $matches)) {
            $habitKeyword = trim($matches[1]);
            $res = $this->habitService->findAndCompleteByName($user, $habitKeyword);
            if ($res) {
                $habit = $res['habit'];
                $streak = $res['streak'];
                $statusVerb = $res['status'] === 'completed' ? 'marked as completed' : 'reopened as pending';
                $streakMsg = $res['status'] === 'completed' ? "🔥 Current streak: <b>{$streak} days</b>\n\nKeep up the consistency!" : "Streak updated.";
                $msg = "🎉 <b>Habit Status Updated!</b>\n\n'<b>{$habit->name}</b>' {$statusVerb} for today!\n{$streakMsg}";
                $this->sendReply($identity, $chatId, $msg);
                return;
            }
        }

        // D. Quick command: Reminder list (/reminders, /list, "reminder list", "reminders", "রিমাইন্ডার দেখাও", "রিমাইন্ডার লিস্ট", "কত সময় বাকি", "koto somoy baki")
        if (preg_match('/^(\/reminders|\/list|reminders|reminder list|my reminders|রিমাইন্ডার|রিমাইন্ডার দেখাও|রিমাইন্ডার লিস্ট|রিমাইন্ডার গুলো দেখাও|কত সময় বাকি|কত সময় বাকি আছে|koto somoy baki|koto time baki)$/iu', $trimmedText)) {
            $reminders = Reminder::forUser($user->id)
                ->scheduled()
                ->orderBy('due_at')
                ->limit(10)
                ->get();

            $lang = $this->resolveLanguage($trimmedText, null);

            if ($reminders->isEmpty()) {
                $emptyReply = match ($lang) {
                    'bengali' => "📋 আপনার এই মুহূর্তে কোনো পেন্ডিং রিমাইন্ডার শিডিউল করা নেই।",
                    'banglish' => "📋 Apnar ei muhurte kono pending reminder schedule kora nei.",
                    default => "📋 You do not have any pending reminders scheduled at this moment.",
                };
                $this->sendReply($identity, $chatId, $emptyReply);
                return;
            }

            $header = match ($lang) {
                'bengali' => "📋 <b>আপনার আসন্ন রিমাইন্ডারসমূহ:</b>\n\n",
                'banglish' => "📋 <b>Apnar pending reminder list:</b>\n\n",
                default => "📋 <b>Your upcoming reminders:</b>\n\n",
            };

            $list = $header;
            foreach ($reminders as $idx => $rem) {
                $localTime = $rem->due_at->copy()->setTimezone($userTz)->format('M d, Y h:i A');
                $rec = $rem->recurrence !== 'none' ? " [{$rem->recurrence}]" : '';
                $num = $idx + 1;
                $remLabel = match ($lang) {
                    'bengali' => 'বাকি আছে:',
                    'banglish' => 'Baki ache:',
                    default => 'Remaining:',
                };
                $timeLeft = $rem->getTimeRemainingFormatted($lang, $userTz);
                $list .= "<b>{$num}. {$rem->title}</b>\n   ⏰ {$localTime}{$rec}\n   ⏳ <b>{$remLabel}</b> {$timeLeft}\n\n";
            }

            $this->sendReply($identity, $chatId, trim($list));
            return;
        }

        // E. Quick command: Common short acknowledgments & night greetings (0s latency, no AI needed)
        if (preg_match('/^(ok|okay|k|thik ache|theek ache|accha|acha|humm|hmm|ha|haa|ji|হুম|আচ্ছা|ঠিক আছে|ধন্যবাদ|thanks|thank you|ty|shuvo ratri|shubho ratri|শুভ রাত্রি|good night|gn|bye|বিদায়)$/iu', $trimmedText)) {
            $lang = $this->resolveLanguage($trimmedText, null);
            $isNight = preg_match('/(shuvo ratri|shubho ratri|শুভ রাত্রি|good night|gn)/iu', $trimmedText);

            if ($isNight) {
                $reply = match ($lang) {
                    'bengali' => "🌙 <b>শুভ রাত্রি!</b> নিশ্চিন্তে ঘুমান, আপনার রিমাইন্ডারের খেয়াল আমি রাখব।",
                    'banglish' => "🌙 <b>Shuvo ratri!</b> Shanti te ghuman, apnar reminder er kheyal ami rakhbo.",
                    default => "🌙 <b>Good night!</b> Sleep well, Remi has your schedule covered.",
                };
            } elseif (preg_match('/(ধন্যবাদ|thanks|thank you|ty)/iu', $trimmedText)) {
                $reply = match ($lang) {
                    'bengali' => "আপনাকেও অনেক ধন্যবাদ! আপনার কোনো রিমাইন্ডার প্রয়োজন হলে যেকোনো সময় বলবেন।",
                    'banglish' => "Apnakeo dhonnobad! Kono reminder ba help lagle bolben.",
                    default => "You're welcome! Let me know if you need any reminders.",
                };
            } else {
                $reply = match ($lang) {
                    'bengali' => "ঠিক আছে! আপনার কোনো রিমাইন্ডার বা সহায়তা প্রয়োজন হলে যেকোনো সময় জানাবেন।",
                    'banglish' => "Thik ache! Kono reminder lagle bolben.",
                    default => "Sure thing! Let me know if you need anything else.",
                };
            }
            $this->sendReply($identity, $chatId, $reply);
            return;
        }

        // 2. Enrich recently uploaded file if user provides description in follow-up message
        if (preg_match('/(ছবি|ফাইল|ডকুমেন্ট|রশিদ|নাম|photo|image|doc|slip|receipt|রেখে দাও|সেভ করো|মিষ্টি|বিড়াল)/iu', $text)) {
            $recentFile = VaultFile::forUser($user->id)
                ->where('created_at', '>=', Carbon::now()->subMinutes(5))
                ->latest('id')
                ->first();

            if ($recentFile && empty($recentFile->caption)) {
                $recentFile->update([
                    'caption' => $text,
                    'search_text' => trim("{$recentFile->search_text} {$text}"),
                ]);
            }
        }

        // 3. Send instant typing action so user sees immediate response
        $this->botService->sendChatAction($chatId, 'typing');

        // 4. Fetch recent conversation history (last 2 turns for ultra low latency)
        $history = TelegramMessage::where('telegram_identity_id', $identity->id)
            ->where('id', '<', $inbound->id)
            ->latest('id')
            ->limit(2)
            ->get()
            ->reverse()
            ->map(fn($m) => [
                'role' => $m->role === 'assistant' ? 'assistant' : 'user',
                'content' => $m->body,
            ])
            ->values()
            ->toArray();

        // 5. Direct AI interpretation for user inputs with multi-turn memory
        $interpretation = $this->aiService->parseReminderPrompt($text, $userTz, $user->id, $history);

        if (! $interpretation->success) {
            $lang = $this->resolveLanguage($text, null);

            // Throttle error notifications to prevent spamming user multiple times
            $throttleKey = "tg_err_throttle_{$identity->id}";
            if (\Illuminate\Support\Facades\Cache::has($throttleKey)) {
                Log::warning("Throttled duplicate AI error message for user {$user->id}");
                return;
            }
            \Illuminate\Support\Facades\Cache::put($throttleKey, true, now()->addMinutes(2));

            // If the user's message was short or conversational, reply gracefully without technical errors
            if (mb_strlen($trimmedText) <= 20 && ! preg_match('/\d|কাল|আজ|সকাল|রাত|tay|shokal|rat|meeting|remind/iu', $trimmedText)) {
                $politeReply = match ($lang) {
                    'bengali' => 'আমি আপনার বার্তাটি পেয়েছি! কোনো রিমাইন্ডার সেট বা দেখতে চাইলে সময়সহ বলুন (যেমন: "কাল সকাল ৯টায় মিটিং")।',
                    'banglish' => 'Ami apnar message peyechi! Kono reminder set korte chaile shomoy shoho bolun (e.g. "Kal shokal 9 tay meeting").',
                    default => 'Message received! If you would like to set or check reminders, please let me know.',
                };
                $this->sendReply($identity, $chatId, $politeReply);
                return;
            }

            // User likely intended to set or manage a reminder
            $userFriendlyError = match ($lang) {
                'bengali' => "⚠️ এআই সার্ভারে সংযোগে সামান্য বিলম্ব হচ্ছে। দয়া করে কিছুক্ষণ পর আবার চেষ্টা করুন (যেমন: 'কাল সকাল ৯টায় মিটিং')।",
                'banglish' => "⚠️ AI service e samanno deri hochhe. Please ektu por abar bolun (e.g. 'Kal shokal 9 tay meeting').",
                default => "⚠️ AI service is experiencing a brief delay. Please try again shortly.",
            };

            $this->sendReply($identity, $chatId, $userFriendlyError);
            return;
        }

        $lang = $this->resolveLanguage($text, $interpretation->detectedLanguage);

        // 4. Intent: Retrieve file from vault
        if ($interpretation->intent === 'retrieve_file') {
            $query = $interpretation->searchQuery ?: $text;
            $results = $this->vaultService->searchFiles($user, $query, limit: 3);
            if ($results->isNotEmpty()) {
                $match = $results->first();
                $path = $this->vaultService->getFilePathForUser($user, $match->id);
                if ($path && file_exists($path)) {
                    $defaultName = match ($lang) {
                        'bengali' => 'সংরক্ষিত ছবি',
                        'banglish' => 'Saved photo',
                        default => 'Stored photo',
                    };
                    $displayName = ! empty($match->caption)
                        ? $match->caption
                        : ($match->original_filename !== 'file_0.jpg' ? $match->original_filename : $defaultName);

                    $caption = match ($lang) {
                        'bengali' => "📄 <b>{$displayName}</b>\n\nআপনার মেমোরি ভল্ট থেকে ফাইলটি খুঁজে পাওয়া গেছে!",
                        'banglish' => "📄 <b>{$displayName}</b>\n\nApnar memory vault theke file ti khuje pawa geche!",
                        default => "📄 <b>{$displayName}</b>\n\nFound from your Memory Vault!",
                    };

                    if ($match->isPdf()) {
                        $this->botService->sendDocument($chatId, $path, $caption);
                    } else {
                        $this->botService->sendPhoto($chatId, $path, $caption);
                    }

                    TelegramMessage::create([
                        'telegram_identity_id' => $identity->id,
                        'chat_id' => $chatId,
                        'direction' => 'outbound',
                        'role' => 'assistant',
                        'body' => "[File sent: {$displayName}]",
                    ]);
                    return;
                }
            }

            $totalFiles = $user->vaultFiles()->count();
            if ($totalFiles === 0) {
                $reply = match ($lang) {
                    'bengali' => "❌ আপনার মেমোরি ভল্টে এই মুহূর্তে কোনো ফাইল বা ডকুমেন্টস সেভ করা নেই।\n\nআপনি এখানে যেকোনো সময় ছবি বা PDF পাঠালে আমি তা আপনার ভল্টে সুরক্ষিতভাবে গুছিয়ে রাখব।",
                    'banglish' => "❌ Apnar memory vault e ei muhurte kono file ba document save kora nei.\n\nApni je kono shomoy photo ba PDF pathale ami vault e secure vabe rekhe dibo.",
                    default => "❌ You do not have any files saved in your Memory Vault yet.\n\nSend any photo or document here anytime to store it securely.",
                };
            } else {
                $reply = match ($lang) {
                    'bengali' => "🔍 দুঃখিত, আপনার মেমোরি ভল্টে '{$query}' সম্পর্কিত কোনো ফাইল খুঁজে পাওয়া যায়নি। অনুগ্রহ করে ফাইলের সঠিক বিষয় বা নাম উল্লেখ করুন।",
                    'banglish' => "🔍 Sorry, apnar memory vault e '{$query}' er kono file pawa jayni. Please file er thik naam ba topic bolun.",
                    default => "🔍 Sorry, no file matching '{$query}' was found in your Memory Vault. Please provide a more specific description.",
                };
            }

            $this->sendReply($identity, $chatId, $reply);
            return;
        }

        // 5. Intent: List reminders
        if ($interpretation->intent === 'list_reminders') {
            $qb = Reminder::forUser($user->id)->scheduled();
            if (! empty($interpretation->targetReminderIdentifier)) {
                $qb->where('title', 'like', "%{$interpretation->targetReminderIdentifier}%");
            }
            $reminders = $qb->orderBy('due_at')->limit(10)->get();

            // If queried for a specific title but not found, fallback to all upcoming
            if ($reminders->isEmpty() && ! empty($interpretation->targetReminderIdentifier)) {
                $reminders = Reminder::forUser($user->id)->scheduled()->orderBy('due_at')->limit(5)->get();
            }

            if ($reminders->isEmpty()) {
                $emptyReply = match ($lang) {
                    'bengali' => "📋 আপনার এই মুহূর্তে কোনো পেন্ডিং রিমাইন্ডার শিডিউল করা নেই।",
                    'banglish' => "📋 Apnar ei muhurte kono pending reminder schedule kora nei.",
                    default => "📋 You do not have any pending reminders scheduled at this moment.",
                };
                $this->sendReply($identity, $chatId, $emptyReply);
                return;
            }

            $header = match ($lang) {
                'bengali' => "📋 <b>আপনার আসন্ন রিমাইন্ডারসমূহ:</b>\n\n",
                'banglish' => "📋 <b>Apnar pending reminder list:</b>\n\n",
                default => "📋 <b>Your upcoming reminders:</b>\n\n",
            };

            $list = $header;
            foreach ($reminders as $idx => $rem) {
                $localTime = $rem->due_at->copy()->setTimezone($userTz)->format('M d, Y h:i A');
                $rec = $rem->recurrence !== 'none' ? " [{$rem->recurrence}]" : '';
                $num = $idx + 1;
                $remLabel = match ($lang) {
                    'bengali' => 'বাকি আছে:',
                    'banglish' => 'Baki ache:',
                    default => 'Remaining:',
                };
                $timeLeft = $rem->getTimeRemainingFormatted($lang, $userTz);
                $list .= "<b>{$num}. {$rem->title}</b>\n   ⏰ {$localTime}{$rec}\n   ⏳ <b>{$remLabel}</b> {$timeLeft}\n\n";
            }

            $this->sendReply($identity, $chatId, trim($list));
            return;
        }

        // 6. Intent: Complete reminder
        if ($interpretation->intent === 'complete_reminder') {
            $identifier = $interpretation->targetReminderIdentifier ?? $interpretation->title;
            $qb = Reminder::forUser($user->id)->scheduled();
            if ($identifier) {
                $qb->where('title', 'like', "%{$identifier}%");
            }
            $reminder = $qb->latest('due_at')->first();
            if ($reminder) {
                $reminder->markAsCompleted();
                $defaultReply = match ($lang) {
                    'bengali' => "✅ '<b>{$reminder->title}</b>' রিমাইন্ডারটি সম্পন্ন হিসেবে চিহ্নিত করা হলো!",
                    'banglish' => "✅ '<b>{$reminder->title}</b>' reminder ti complete hishebe mark kora hoyeche!",
                    default => "✅ Reminder '<b>{$reminder->title}</b>' marked as completed!",
                };
                $reply = $interpretation->conversationalReply ?: $defaultReply;
                $this->sendReply($identity, $chatId, $reply);
            } else {
                $notFound = match ($lang) {
                    'bengali' => "সম্পন্ন করার মতো কোনো সক্রিয় রিমাইন্ডার পাওয়া যায়নি।",
                    'banglish' => "Complete korar moto kono active reminder pawa jayni.",
                    default => "No matching active reminder found to mark as completed.",
                };
                $this->sendReply($identity, $chatId, $interpretation->conversationalReply ?: $notFound);
            }
            return;
        }

        // 7. Intent: Reschedule reminder
        if ($interpretation->intent === 'reschedule_reminder') {
            $identifier = $interpretation->targetReminderIdentifier ?? $interpretation->title;
            $qb = Reminder::forUser($user->id)->scheduled();
            if ($identifier) {
                $qb->where('title', 'like', "%{$identifier}%");
            }
            $reminder = $qb->latest('due_at')->first();
            if ($reminder) {
                $currentLocal = $reminder->due_at->copy()->setTimezone($userTz);
                $newDate = $interpretation->date ?? $currentLocal->toDateString();
                $newTime = $interpretation->time ?? $currentLocal->toTimeString();
                $newDueAt = Carbon::parse("{$newDate} {$newTime}", $userTz)->setTimezone('UTC');
                $reminder->due_at = $newDueAt;
                $reminder->save();

                $formatted = Carbon::parse("{$newDate} {$newTime}", $userTz)->format('M d, Y h:i A');
                $newRemaining = $reminder->getTimeRemainingFormatted($lang, $userTz);

                $msg = match ($lang) {
                    'bengali' => "🕒 '<b>{$reminder->title}</b>' রিমাইন্ডারটি নতুন সময়ে শিডিউল করা হয়েছে:\n⏰ <b>নতুন সময়:</b> {$formatted} ({$userTz})\n⏳ <b>বাকি আছে:</b> {$newRemaining}",
                    'banglish' => "🕒 '<b>{$reminder->title}</b>' reminder ti reschedule kora hoyeche:\n⏰ <b>New time:</b> {$formatted} ({$userTz})\n⏳ <b>Baki ache:</b> {$newRemaining}",
                    default => "🕒 Rescheduled '<b>{$reminder->title}</b>':\n⏰ <b>New time:</b> {$formatted} ({$userTz})\n⏳ <b>Remaining:</b> {$newRemaining}",
                };
                $this->sendReply($identity, $chatId, $msg);
            } else {
                $notFound = match ($lang) {
                    'bengali' => "রিস্কেডিউল করার মতো কোনো রিমাইন্ডার পাওয়া যায়নি।",
                    'banglish' => "Reschedule korar moto kono reminder pawa jayni.",
                    default => "Could not find the reminder you want to reschedule.",
                };
                $this->sendReply($identity, $chatId, $interpretation->conversationalReply ?: $notFound);
            }
            return;
        }

        // 8. Intent: Cancel reminder
        if ($interpretation->intent === 'cancel_reminder') {
            $identifier = $interpretation->targetReminderIdentifier ?? $interpretation->title;
            $qb = Reminder::forUser($user->id)->scheduled();
            if ($identifier) {
                $qb->where('title', 'like', "%{$identifier}%");
            }
            $reminder = $qb->latest('due_at')->first();
            if ($reminder) {
                $reminder->markAsCancelled();
                $defaultSuccess = match ($lang) {
                    'bengali' => "🗑️ '{$reminder->title}' রিমাইন্ডারটি বাতিল করা হয়েছে।",
                    'banglish' => "🗑️ '{$reminder->title}' reminder ti cancel kora hoyeche.",
                    default => "🗑️ Reminder '{$reminder->title}' has been cancelled.",
                };
                $reply = $interpretation->conversationalReply ?: $defaultSuccess;
                $this->sendReply($identity, $chatId, $reply);
            } else {
                $defaultNotFound = match ($lang) {
                    'bengali' => "বাতিল করার মতো কোনো রিমাইন্ডার পাওয়া যায়নি।",
                    'banglish' => "Cancel korar moto kono reminder pawa jayni.",
                    default => "No matching reminder found to cancel.",
                };
                $reply = $interpretation->conversationalReply ?: $defaultNotFound;
                $this->sendReply($identity, $chatId, $reply);
            }
            return;
        }

        // 9. Intent: Create reminder
        if ($interpretation->intent === 'create_reminder' && ! $interpretation->hasMissingInfo) {
            $date = $interpretation->date ?? Carbon::now($userTz)->toDateString();
            $time = $interpretation->time ?? '09:00:00';
            $dueAtInUserTz = Carbon::parse("{$date} {$time}", $userTz);
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
            $timeRemaining = $reminder->getTimeRemainingFormatted($lang, $userTz);
            $aiReply = $interpretation->conversationalReply;

            if ($lang === 'bengali') {
                $header = "🔔 <b>রিমাইন্ডার শিডিউল করা হয়েছে!</b>";
                $titleLabel = "বিষয়:";
                $timeLabel = "সময়:";
                $remLabel = "বাকি আছে:";
                $recLabel = "পুনরাবৃত্তি:";
                $recValue = match ($reminder->recurrence) {
                    'daily' => 'প্রতিদিন',
                    'weekly' => 'সাপ্তাহিক',
                    'monthly' => 'মাসিক',
                    'yearly' => 'বাৎসরিক',
                    default => 'একবার',
                };
            } elseif ($lang === 'banglish') {
                $header = "🔔 <b>Reminder Set Kora Hoyeche!</b>";
                $titleLabel = "Title:";
                $timeLabel = "Shomoy:";
                $remLabel = "Baki ache:";
                $recLabel = "Recurrence:";
                $recValue = match ($reminder->recurrence) {
                    'daily' => 'Protidin',
                    'weekly' => 'Saptahik',
                    'monthly' => 'Mashik',
                    'yearly' => 'Bochor e ekbar',
                    default => 'Ekbar',
                };
            } else {
                $header = "🔔 <b>Reminder Scheduled!</b>";
                $titleLabel = "Title:";
                $timeLabel = "Due:";
                $remLabel = "Time remaining:";
                $recLabel = "Recurrence:";
                $recValue = ucfirst($reminder->recurrence);
            }

            $reply = "{$header}\n\n" .
                     ($aiReply ? "{$aiReply}\n\n" : '') .
                     "📌 <b>{$titleLabel}</b> {$reminder->title}\n" .
                     "⏰ <b>{$timeLabel}</b> {$formattedTime} ({$userTz})\n" .
                     "⏳ <b>{$remLabel}</b> {$timeRemaining}\n" .
                     "🔁 <b>{$recLabel}</b> {$recValue}";

            $this->sendReply($identity, $chatId, $reply);
            return;
        }

        // 8. Clarification question if information is missing
        if ($interpretation->hasMissingInfo && $interpretation->clarificationQuestion) {
            $this->sendReply($identity, $chatId, $interpretation->clarificationQuestion);
            return;
        }

        // 9. Pure AI-generated conversational reply
        if (! empty($interpretation->conversationalReply)) {
            $this->sendReply($identity, $chatId, $interpretation->conversationalReply);
            return;
        }

        // Fallback
        $fallback = match ($lang) {
            'bengali' => "আমি আপনার কথাটি বুঝতে পেরেছি। আপনার কোনো রিমাইন্ডার বা সাহায্য প্রয়োজন হলে বলুন।",
            'banglish' => "Ami apnar kotha bujhte perechi. Apnar kono reminder ba help lagle bolun.",
            default => "I understand. Let me know if you need any reminders or assistance.",
        };
        $this->sendReply($identity, $chatId, $fallback);
    }

    /**
     * Determine effective communication mode: 'bengali', 'banglish', or 'english'.
     */
    protected function resolveLanguage(string $userText, ?string $aiDetectedLang): string
    {
        if (preg_match('/[\x{0980}-\x{09FF}]/u', $userText)) {
            return 'bengali';
        }

        if ($aiDetectedLang === 'banglish' || $aiDetectedLang === 'bengali') {
            return 'banglish';
        }

        if (preg_match('/\b(ami|tumi|apni|kemon|aso|achen|koro|korio|hobe|hoyeche|shokal|rat|khobor|ki|na|valo|bhalo|bolun|bolte|paro|ekta|akta|mone|rakho|rakhba|dekhao|dao|amar|amake|tomar|apnar|kal|ajke|porshu)\b/i', $userText)) {
            return 'banglish';
        }

        return 'english';
    }

    protected function sendReply(TelegramIdentity $identity, string $chatId, string $replyText): void
    {
        $this->botService->sendMessage($chatId, $replyText);

        TelegramMessage::create([
            'telegram_identity_id' => $identity->id,
            'chat_id' => $chatId,
            'direction' => 'outbound',
            'role' => 'assistant',
            'body' => strip_tags($replyText),
        ]);
    }
}
