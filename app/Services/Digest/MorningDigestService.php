<?php

namespace App\Services\Digest;

use App\Models\Reminder;
use App\Models\User;
use App\Services\Habits\HabitService;
use App\Services\Telegram\TelegramBotService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MorningDigestService
{
    public function __construct(
        protected HabitService $habitService,
        protected TelegramBotService $telegramBot
    ) {}

    /**
     * Generate structured morning briefing payload for a user.
     */
    public function generateDigest(User $user): array
    {
        $timezone = $user->timezone ?: 'Asia/Dhaka';
        $now = Carbon::now($timezone);
        $todayStr = $now->toDateString();

        // 1. Fetch today's reminders
        $todayReminders = Reminder::where('user_id', $user->id)
            ->whereDate('due_at', $todayStr)
            ->whereIn('status', ['pending', 'scheduled'])
            ->orderBy('due_at')
            ->get();

        // 2. Fetch overdue reminders (due before today but still pending)
        $overdueReminders = Reminder::where('user_id', $user->id)
            ->where('due_at', '<', $now->copy()->startOfDay())
            ->whereIn('status', ['pending', 'scheduled'])
            ->orderBy('due_at')
            ->get();

        // 3. Fetch user's habits with today's status
        $habits = $this->habitService->getUserHabitsWithStatus($user, $now);

        // 4. Determine language/tone
        $isBangla = in_array($user->preferred_language, ['bn', 'bengali']);

        return [
            'user' => $user,
            'date_formatted' => $now->isoFormat('dddd, D MMMM YYYY'),
            'time_formatted' => $now->format('h:i A'),
            'today_reminders' => $todayReminders,
            'overdue_reminders' => $overdueReminders,
            'habits' => $habits,
            'is_bangla' => $isBangla,
            'stats' => [
                'total_reminders' => $todayReminders->count(),
                'total_habits' => $habits->count(),
                'completed_habits' => $habits->where('is_completed_today', true)->count(),
                'overdue_count' => $overdueReminders->count(),
            ],
        ];
    }

    /**
     * Build formatted text message suitable for Telegram or WhatsApp.
     */
    public function formatTextMessage(array $digest): string
    {
        $user = $digest['user'];
        $name = htmlspecialchars($user->name);
        $dateStr = $digest['date_formatted'];
        $stats = $digest['stats'];

        $msg = "☀️ <b>Daily Morning Briefing | শুভ সকাল, {$name}!</b>\n";
        $msg .= "📅 <i>{$dateStr}</i>\n\n";

        // Summary bar
        $msg .= "📊 <b>Today at a glance:</b>\n";
        $msg .= "• Reminders: <b>{$stats['total_reminders']}</b> scheduled";
        if ($stats['overdue_count'] > 0) {
            $msg .= " | ⚠️ <b>{$stats['overdue_count']}</b> overdue";
        }
        $msg .= "\n• Habits: <b>{$stats['completed_habits']}/{$stats['total_habits']}</b> completed\n\n";

        // Today's Reminders
        $msg .= "⏰ <b>Today's Reminders:</b>\n";
        if ($digest['today_reminders']->isEmpty()) {
            $msg .= "<i>No reminders scheduled for today. Enjoy your day!</i>\n";
        } else {
            foreach ($digest['today_reminders'] as $r) {
                $time = Carbon::parse($r->due_at)->setTimezone($user->timezone)->format('h:i A');
                $priorityBadge = match ($r->priority) {
                    'urgent' => '🔴 [URGENT]',
                    'high' => '🟠',
                    'medium' => '🟡',
                    default => '⚪',
                };
                $msg .= "• <b>{$time}</b> — {$priorityBadge} {$r->title}\n";
            }
        }
        $msg .= "\n";

        // Overdue alert
        if (! $digest['overdue_reminders']->isEmpty()) {
            $msg .= "⚠️ <b>Overdue Pending Tasks:</b>\n";
            foreach ($digest['overdue_reminders']->take(3) as $r) {
                $time = Carbon::parse($r->due_at)->setTimezone($user->timezone)->format('M d, h:i A');
                $msg .= "• {$r->title} <i>({$time})</i>\n";
            }
            $msg .= "\n";
        }

        // Habits & Streaks
        if (! $digest['habits']->isEmpty()) {
            $msg .= "🔥 <b>Habit Tracker & Streaks:</b>\n";
            foreach ($digest['habits'] as $h) {
                $statusIcon = $h->is_completed_today ? '✅' : '⏳';
                $streakBadge = $h->current_streak > 0 ? "🔥 {$h->current_streak}d streak" : "0d";
                $msg .= "• {$statusIcon} <b>{$h->name}</b> ({$streakBadge})\n";
            }
            $msg .= "\n<i>Reply 'done [habit name]' to check off a habit anytime!</i>\n";
        }

        $msg .= "\n✨ <i>Make today count with Remique AI!</i>";

        return $msg;
    }

    /**
     * Send morning briefing to a user via their connected Telegram identity.
     */
    public function sendToUser(User $user): bool
    {
        $telegram = $user->primaryTelegramIdentity;
        if (! $telegram || ! $telegram->chat_id) {
            return false;
        }

        try {
            $digest = $this->generateDigest($user);
            $text = $this->formatTextMessage($digest);

            $this->telegramBot->sendMessage($telegram->chat_id, $text);

            return true;
        } catch (\Throwable $e) {
            Log::error("Failed to send morning digest to user {$user->id}: " . $e->getMessage());
            return false;
        }
    }
}
