<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Reminder;
use App\Models\ReminderDeliveryAttempt;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\WhatsAppCloudApiService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendScheduledReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remi:send-due-reminders {--limit=50 : Maximum reminders to process in this batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find due reminders and deliver them atomically via WhatsApp with 24h window compliance';

    protected \App\Services\Telegram\TelegramBotService $telegramService;

    public function __construct(
        protected WhatsAppCloudApiService $apiService,
        ?\App\Services\Telegram\TelegramBotService $telegramService = null
    ) {
        $this->telegramService = $telegramService ?? app(\App\Services\Telegram\TelegramBotService::class);
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $this->info("Checking for due reminders (batch limit: {$limit})...");

        // 1. Recover any stalled delivery attempts left processing due to past worker crashes
        $this->recoverStalledAttempts();

        // 2. Fetch due scheduled reminders
        $nowUtc = Carbon::now('UTC');
        $dueReminders = Reminder::with(['user.whatsappIdentities', 'user.telegramIdentities'])
            ->where('status', Reminder::STATUS_SCHEDULED)
            ->where('due_at', '<=', $nowUtc)
            ->orderBy('due_at', 'asc')
            ->limit($limit)
            ->get();

        if ($dueReminders->isEmpty()) {
            $this->info('No reminders are currently due.');
            return self::SUCCESS;
        }

        $this->info("Found {$dueReminders->count()} due reminder(s) to process.");

        foreach ($dueReminders as $reminder) {
            $this->processReminder($reminder);
        }

        return self::SUCCESS;
    }

    /**
     * Recover attempts that got stuck in processing state for more than 10 minutes.
     */
    protected function recoverStalledAttempts(): void
    {
        $cutoff = Carbon::now()->subMinutes(10);
        $stalled = ReminderDeliveryAttempt::where('status', 'processing')
            ->where('processing_started_at', '<', $cutoff)
            ->get();

        foreach ($stalled as $attempt) {
            $attempt->status = 'failed';
            $attempt->error_code = 'PROCESSOR_TIMEOUT';
            $attempt->error_message = 'Process timed out or worker restarted. Recovered for retry.';
            $attempt->save();

            Log::warning("Recovered stalled reminder delivery attempt #{$attempt->id}");
        }
    }

    /**
     * Process an individual due reminder atomically.
     */
    protected function processReminder(Reminder $reminder): void
    {
        $user = $reminder->user;
        if (! $user || ! $user->isActive()) {
            $this->warn("Skipping reminder #{$reminder->id}: user missing or inactive.");
            return;
        }

        $identity = $user->primaryWhatsAppIdentity;
        $telegramIdentity = $user->primaryTelegramIdentity;

        if ((! $identity || ! $identity->isActive()) && (! $telegramIdentity || ! $telegramIdentity->isActive())) {
            $this->warn("Skipping reminder #{$reminder->id}: user #{$user->id} has no active WhatsApp or Telegram channels linked.");
            return;
        }

        $userTz = $user->timezone ?? 'Asia/Dhaka';
        $localTime = $reminder->due_at->copy()->setTimezone($userTz)->format('M d, Y h:i A');

        $telegramDelivered = false;
        // Deliver via Telegram if active
        if ($telegramIdentity && $telegramIdentity->isActive() && in_array($user->preferred_channel ?? 'all', ['telegram', 'all'])) {
            $telegramText = "🔔 <b>REMINDER ALERT</b>\n\n<b>{$reminder->title}</b>\nScheduled: {$localTime} ({$userTz})";
            if (! empty($reminder->description)) {
                $telegramText .= "\n\n<i>Notes: {$reminder->description}</i>";
            }
            try {
                $this->telegramService->sendMessage($telegramIdentity->chat_id, $telegramText);
                $this->info("Delivered reminder #{$reminder->id} via Telegram chat #{$telegramIdentity->chat_id}");
                $telegramDelivered = true;
            } catch (\Throwable $e) {
                Log::error("Failed to send Telegram reminder #{$reminder->id}: " . $e->getMessage());
            }
        }

        $shouldSendWhatsApp = $identity && $identity->isActive() && $this->apiService->isConfigured() && in_array($user->preferred_channel ?? 'all', ['whatsapp', 'all']);

        if (! $shouldSendWhatsApp) {
            if ($telegramDelivered) {
                // Delivery completed via Telegram
                if ($reminder->recurrence === Reminder::RECURRENCE_NONE) {
                    $reminder->markAsCompleted();
                } else {
                    $nextDue = $this->calculateNextOccurrence($reminder->due_at, $reminder->recurrence);
                    $reminder->update(['due_at' => $nextDue]);
                }
            }
            return;
        }

        $dueTimestamp = $reminder->due_at->timestamp;
        $idempotencyKey = "reminder_{$reminder->id}_{$dueTimestamp}";

        // Atomic check and claim
        $attempt = ReminderDeliveryAttempt::firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                'reminder_id' => $reminder->id,
                'whatsapp_identity_id' => $identity->id,
                'channel' => 'whatsapp',
                'status' => 'pending',
                'attempt_number' => 1,
                'scheduled_for' => $reminder->due_at,
            ]
        );

        // If already sent or delivered, skip duplicate
        if (in_array($attempt->status, ['sent', 'delivered', 'read'])) {
            return;
        }

        // If already processing in another concurrent runner, skip
        if ($attempt->status === 'processing' && $attempt->processing_started_at && $attempt->processing_started_at->isAfter(Carbon::now()->subMinutes(5))) {
            return;
        }

        // If retries exhausted (>= 3 attempts)
        if ($attempt->status === 'exhausted' || $attempt->attempt_number > 3) {
            $this->warn("Reminder #{$reminder->id} delivery exhausted after maximum retries.");
            return;
        }

        // Mark as processing
        $attempt->update([
            'status' => 'processing',
            'processing_started_at' => Carbon::now(),
            'attempt_number' => $attempt->attempt_number + ($attempt->status === 'failed' ? 1 : 0),
        ]);

        // Determine Meta 24-hour customer service window status
        $withinWindow = $identity->isWithin24HourServiceWindow();
        $userTz = $user->timezone ?? 'Asia/Dhaka';
        $localTime = $reminder->due_at->copy()->setTimezone($userTz)->format('M d, Y h:i A');

        $sendResult = null;
        $isTemplate = false;
        $templateName = null;

        if ($withinWindow) {
            // Free-form message permitted
            $messageText = "🔔 REMINDER: {$reminder->title}\n\nScheduled for: {$localTime} ({$userTz})";
            if (! empty($reminder->description)) {
                $messageText .= "\nNotes: {$reminder->description}";
            }
            if ($reminder->recurrence !== Reminder::RECURRENCE_NONE) {
                $messageText .= "\n(Recurring: {$reminder->recurrence})";
            }

            $sendResult = $this->apiService->sendText($identity->phone_number, $messageText);
        } else {
            // Outside 24h window: Must use approved template
            $isTemplate = true;
            $templateName = config('whatsapp.default_reminder_template', 'remi_reminder_alert');
            $language = config('whatsapp.default_template_language', 'en_US');
            $params = [
                $user->name,
                $reminder->title,
                "{$localTime} ({$userTz})",
            ];

            $sendResult = $this->apiService->sendTemplate($identity->phone_number, $templateName, $language, $params);
        }

        // Handle delivery outcome
        if ($sendResult['success']) {
            $wamid = $sendResult['wamid'] ?? null;

            // Log outbound WhatsApp message
            $loggedMessage = WhatsAppMessage::create([
                'wamid' => $wamid,
                'whatsapp_identity_id' => $identity->id,
                'direction' => 'outbound',
                'from_number' => (string) config('whatsapp.phone_number_id'),
                'to_number' => $identity->phone_number,
                'message_type' => $isTemplate ? 'template' : 'text',
                'body' => $isTemplate ? "Template: {$templateName}" : ($messageText ?? ''),
                'status' => 'accepted',
                'raw_payload' => $sendResult['raw'] ?? null,
            ]);

            $attempt->update([
                'status' => 'sent',
                'is_template' => $isTemplate,
                'template_name' => $templateName,
                'whatsapp_message_id' => $loggedMessage->id,
                'sent_at' => Carbon::now(),
                'error_code' => null,
                'error_message' => null,
            ]);

            // Handle recurrence or completion
            if ($reminder->recurrence === Reminder::RECURRENCE_NONE) {
                $reminder->markAsCompleted();
            } else {
                $nextDue = $this->calculateNextOccurrence($reminder->due_at, $reminder->recurrence);
                $reminder->update(['due_at' => $nextDue]);
                $this->info("Rolled recurring reminder #{$reminder->id} ({$reminder->recurrence}) to {$nextDue->toIso8601String()}");
            }

            $this->info("Delivered reminder #{$reminder->id} to {$identity->phone_number} (Template: " . ($isTemplate ? 'Yes' : 'No') . ")");
        } else {
            // Send failed
            $errorCode = $sendResult['error_code'] ?? 'SEND_FAILED';
            $errorMessage = $sendResult['error_message'] ?? 'Unknown send failure';
            $isExhausted = $attempt->attempt_number >= 3;

            $attempt->update([
                'status' => $isExhausted ? 'exhausted' : 'failed',
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
            ]);

            Log::error("Failed to deliver reminder #{$reminder->id} to {$identity->phone_number}: [{$errorCode}] {$errorMessage}");

            if ($telegramDelivered) {
                // Since Telegram already successfully delivered, mark the reminder as completed / rolled
                if ($reminder->recurrence === Reminder::RECURRENCE_NONE) {
                    $reminder->markAsCompleted();
                } else {
                    $nextDue = $this->calculateNextOccurrence($reminder->due_at, $reminder->recurrence);
                    $reminder->update(['due_at' => $nextDue]);
                }
                $this->info("Reminder #{$reminder->id} marked completed via Telegram delivery despite WhatsApp send failure.");
            } else {
                AuditLog::record(
                    'reminder.delivery_failed',
                    $reminder,
                    "Delivery attempt #{$attempt->attempt_number} failed: {$errorMessage}",
                    [
                        'error_code' => $errorCode,
                        'phone_number' => $identity->phone_number,
                        'is_exhausted' => $isExhausted,
                    ],
                    $user->id
                );
            }
        }
    }

    /**
     * Calculate next occurrence timestamp for a recurring reminder.
     */
    protected function calculateNextOccurrence(Carbon $currentDueAt, string $recurrence): Carbon
    {
        $next = $currentDueAt->copy();
        return match ($recurrence) {
            Reminder::RECURRENCE_DAILY => $next->addDay(),
            Reminder::RECURRENCE_WEEKLY => $next->addWeek(),
            Reminder::RECURRENCE_MONTHLY => $next->addMonth(),
            Reminder::RECURRENCE_YEARLY => $next->addYear(),
            default => $next->addDay(),
        };
    }
}
