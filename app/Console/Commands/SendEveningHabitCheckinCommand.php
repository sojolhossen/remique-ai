<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Habits\HabitService;
use App\Services\Telegram\TelegramBotService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendEveningHabitCheckinCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remi:send-habit-checkin {--user= : Specific user ID} {--force : Force send checkin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatches evening habit check-in alert to users with incomplete daily habits';

    /**
     * Execute the console command.
     */
    public function handle(HabitService $habitService, TelegramBotService $botService): int
    {
        $userId = $this->option('user');
        $force = (bool) $this->option('force');

        $query = User::where('status', 'active');
        if ($userId) {
            $query->where('id', $userId);
        }

        $users = $query->get();
        $sentCount = 0;

        foreach ($users as $user) {
            $userTz = $user->timezone ?: 'Asia/Dhaka';
            $nowInUserTz = Carbon::now($userTz);

            // Default check-in time is 20:00 (8 PM)
            if (! $force && $nowInUserTz->hour !== 20) {
                continue;
            }

            $cacheKey = "habit_checkin_sent_{$user->id}_{$nowInUserTz->toDateString()}";
            if (! $force && Cache::has($cacheKey)) {
                continue;
            }

            $habits = $habitService->getUserHabitsWithStatus($user, $nowInUserTz);
            $pendingHabits = $habits->where('is_completed_today', false);

            if ($pendingHabits->isEmpty()) {
                continue; // All habits completed, no need to disturb!
            }

            $telegram = $user->primaryTelegramIdentity;
            if (! $telegram || ! $telegram->chat_id) {
                continue;
            }

            $name = htmlspecialchars($user->name);
            $msg = "🌙 <b>Evening Habit Check-in, {$name}!</b>\n\n";
            $msg .= "Here are your pending habits for today:\n";
            foreach ($pendingHabits as $h) {
                $streakBadge = $h->current_streak > 0 ? "🔥 {$h->current_streak}d streak at stake!" : "";
                $msg .= "• ⏳ <b>{$h->name}</b> {$streakBadge}\n";
            }
            $msg .= "\n<i>Don't break your streak! Reply 'done [habit name]' once you finish!</i>";

            try {
                $botService->sendMessage($telegram->chat_id, $msg);
                Cache::put($cacheKey, true, now()->addHours(15));
                $sentCount++;
                $this->line("• Sent habit check-in to: {$user->name}");
            } catch (\Throwable $e) {
                $this->error("Failed to send checkin to {$user->name}: " . $e->getMessage());
            }
        }

        $this->info("✓ Habit check-in finished. Total sent: {$sentCount}");

        return self::SUCCESS;
    }
}
