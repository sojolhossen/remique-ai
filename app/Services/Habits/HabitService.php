<?php

namespace App\Services\Habits;

use App\Models\Habit;
use App\Models\HabitLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class HabitService
{
    /**
     * Get user's active habits enriched with today's status and last 7 days history.
     */
    public function getUserHabitsWithStatus(User $user, ?Carbon $date = null): Collection
    {
        $timezone = $user->timezone ?: 'Asia/Dhaka';
        $today = $date ?: Carbon::today($timezone);
        $todayStr = $today->toDateString();

        $habits = Habit::where('user_id', $user->id)
            ->where('is_active', true)
            ->with(['logs' => function ($q) use ($today) {
                $q->where('logged_date', '>=', $today->copy()->subDays(6)->toDateString())
                  ->where('logged_date', '<=', $today->toDateString());
            }])
            ->get();

        return $habits->map(function (Habit $habit) use ($today, $todayStr) {
            $loggedMap = $habit->logs->pluck('status', 'logged_date')->all();

            $isCompletedToday = isset($loggedMap[$todayStr]) && $loggedMap[$todayStr] === 'completed';

            // Build 7-day pill history (Mon -> Sun or last 7 days)
            $historyDays = [];
            for ($i = 6; $i >= 0; $i--) {
                $d = $today->copy()->subDays($i);
                $dStr = $d->toDateString();
                $historyDays[] = [
                    'date' => $dStr,
                    'day_name' => $d->format('D'),
                    'day_num' => $d->format('j'),
                    'is_today' => $i === 0,
                    'is_completed' => isset($loggedMap[$dStr]) && $loggedMap[$dStr] === 'completed',
                ];
            }

            $habit->is_completed_today = $isCompletedToday;
            $habit->history_days = $historyDays;

            return $habit;
        });
    }

    /**
     * Create a new habit.
     */
    public function createHabit(User $user, array $data): Habit
    {
        return Habit::create([
            'user_id' => $user->id,
            'name' => trim($data['name']),
            'description' => ! empty($data['description']) ? trim($data['description']) : null,
            'frequency' => $data['frequency'] ?? 'daily',
            'reminder_time' => ! empty($data['reminder_time']) ? $data['reminder_time'] : null,
            'current_streak' => 0,
            'longest_streak' => 0,
            'is_active' => true,
        ]);
    }

    /**
     * Toggle habit completion for today.
     */
    public function toggleHabit(User $user, Habit $habit, ?Carbon $date = null): array
    {
        $timezone = $user->timezone ?: 'Asia/Dhaka';
        $today = $date ?: Carbon::today($timezone);
        $todayStr = $today->toDateString();

        $existing = HabitLog::where('habit_id', $habit->id)
            ->whereDate('logged_date', $todayStr)
            ->first();

        if ($existing) {
            $existing->delete();
            $newStreak = $habit->recalculateStreak($today);
            return [
                'status' => 'uncompleted',
                'habit' => $habit->fresh(),
                'streak' => $newStreak,
                'message' => "Marked '{$habit->name}' as pending for today.",
            ];
        }

        HabitLog::create([
            'habit_id' => $habit->id,
            'user_id' => $user->id,
            'logged_date' => $todayStr,
            'status' => 'completed',
        ]);

        $newStreak = $habit->recalculateStreak($today);

        return [
            'status' => 'completed',
            'habit' => $habit->fresh(),
            'streak' => $newStreak,
            'message' => "Awesome! '{$habit->name}' completed! 🔥 {$newStreak} days streak!",
        ];
    }

    /**
     * Find a habit by natural language keyword from chat and mark it completed.
     */
    public function findAndCompleteByName(User $user, string $queryText, ?Carbon $date = null): ?array
    {
        $timezone = $user->timezone ?: 'Asia/Dhaka';
        $today = $date ?: Carbon::today($timezone);
        $cleanQuery = mb_strtolower(trim($queryText));

        $activeHabits = Habit::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        $matchedHabit = null;

        foreach ($activeHabits as $habit) {
            $habitNameLower = mb_strtolower($habit->name);
            // Check direct match or word match
            if (str_contains($cleanQuery, $habitNameLower) || str_contains($habitNameLower, $cleanQuery)) {
                $matchedHabit = $habit;
                break;
            }
            // Check individual words
            $words = explode(' ', $habitNameLower);
            foreach ($words as $w) {
                if (mb_strlen($w) >= 3 && str_contains($cleanQuery, $w)) {
                    $matchedHabit = $habit;
                    break 2;
                }
            }
        }

        if (! $matchedHabit) {
            return null;
        }

        $res = $this->toggleHabit($user, $matchedHabit, $today);

        return [
            'habit' => $matchedHabit,
            'streak' => $res['streak'],
            'status' => $res['status'],
        ];
    }
}
