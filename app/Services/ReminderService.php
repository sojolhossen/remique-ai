<?php

namespace App\Services;

use App\Models\Reminder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReminderService
{
    /**
     * Get paginated reminders for a user with comprehensive filtering and search.
     */
    public function getPaginatedReminders(User $user, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $userTz = $user->timezone ?: 'UTC';
        $nowUtc = Carbon::now('UTC');

        $query = Reminder::where('user_id', $user->id);

        // Search in title and description
        if (! empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                  ->orWhere('description', 'like', $search);
            });
        }

        // Status filter
        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        // Priority filter
        if (! empty($filters['priority']) && $filters['priority'] !== 'all') {
            $query->where('priority', $filters['priority']);
        }

        // Date range filter
        if (! empty($filters['date_filter']) && $filters['date_filter'] !== 'all') {
            $nowInUserTz = Carbon::now($userTz);
            switch ($filters['date_filter']) {
                case 'today':
                    $startOfTodayUtc = $nowInUserTz->copy()->startOfDay()->setTimezone('UTC');
                    $endOfTodayUtc = $nowInUserTz->copy()->endOfDay()->setTimezone('UTC');
                    $query->whereBetween('due_at', [$startOfTodayUtc, $endOfTodayUtc]);
                    break;

                case 'tomorrow':
                    $startOfTomorrowUtc = $nowInUserTz->copy()->addDay()->startOfDay()->setTimezone('UTC');
                    $endOfTomorrowUtc = $nowInUserTz->copy()->addDay()->endOfDay()->setTimezone('UTC');
                    $query->whereBetween('due_at', [$startOfTomorrowUtc, $endOfTomorrowUtc]);
                    break;

                case 'this_week':
                    $startOfWeekUtc = $nowInUserTz->copy()->startOfWeek()->setTimezone('UTC');
                    $endOfWeekUtc = $nowInUserTz->copy()->endOfWeek()->setTimezone('UTC');
                    $query->whereBetween('due_at', [$startOfWeekUtc, $endOfWeekUtc]);
                    break;

                case 'upcoming':
                    $query->where('status', Reminder::STATUS_SCHEDULED)
                          ->where('due_at', '>=', $nowUtc);
                    break;

                case 'overdue':
                    $query->where('status', Reminder::STATUS_SCHEDULED)
                          ->where('due_at', '<', $nowUtc);
                    break;
            }
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'due_at_asc';
        switch ($sortBy) {
            case 'due_at_desc':
                $query->orderBy('due_at', 'desc');
                break;
            case 'created_at_desc':
                $query->orderBy('created_at', 'desc');
                break;
            case 'priority_desc':
                $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')");
                break;
            case 'due_at_asc':
            default:
                $query->orderBy('due_at', 'asc');
                break;
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Get live database-backed metrics for the user dashboard.
     */
    public function getDashboardMetrics(User $user): array
    {
        $nowUtc = Carbon::now('UTC');

        $baseQuery = Reminder::where('user_id', $user->id);

        $total = (clone $baseQuery)->count();
        $completed = (clone $baseQuery)->where('status', Reminder::STATUS_COMPLETED)->count();
        $scheduled = (clone $baseQuery)->where('status', Reminder::STATUS_SCHEDULED)->count();
        $upcoming = (clone $baseQuery)->where('status', Reminder::STATUS_SCHEDULED)
                                     ->where('due_at', '>=', $nowUtc)
                                     ->count();
        $overdue = (clone $baseQuery)->where('status', Reminder::STATUS_SCHEDULED)
                                    ->where('due_at', '<', $nowUtc)
                                    ->count();

        $nextReminders = (clone $baseQuery)
            ->where('status', Reminder::STATUS_SCHEDULED)
            ->where('due_at', '>=', $nowUtc)
            ->orderBy('due_at', 'asc')
            ->limit(4)
            ->get();

        $recentActivity = (clone $baseQuery)
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        return [
            'total_reminders' => $total,
            'completed_reminders' => $completed,
            'scheduled_reminders' => $scheduled,
            'upcoming_reminders' => $upcoming,
            'overdue_reminders' => $overdue,
            'next_reminders' => $nextReminders,
            'recent_activity' => $recentActivity,
        ];
    }

    /**
     * Create a new reminder, converting local date/time to UTC.
     */
    public function createReminder(User $user, array $data): Reminder
    {
        $dueAtUtc = $this->parseToUtc($data['date'], $data['time'], $user->timezone ?: 'UTC');

        return DB::transaction(function () use ($user, $data, $dueAtUtc) {
            return Reminder::create([
                'user_id' => $user->id,
                'title' => trim($data['title']),
                'description' => ! empty($data['description']) ? trim($data['description']) : null,
                'due_at' => $dueAtUtc,
                'status' => Reminder::STATUS_SCHEDULED,
                'priority' => $data['priority'] ?? Reminder::PRIORITY_MEDIUM,
                'recurrence' => $data['recurrence'] ?? Reminder::RECURRENCE_NONE,
            ]);
        });
    }

    /**
     * Update an existing reminder.
     */
    public function updateReminder(Reminder $reminder, array $data, ?string $userTimezone = null): Reminder
    {
        $timezone = $userTimezone ?: ($reminder->user?->timezone ?: 'UTC');
        $dueAtUtc = $this->parseToUtc($data['date'], $data['time'], $timezone);

        return DB::transaction(function () use ($reminder, $data, $dueAtUtc) {
            $reminder->update([
                'title' => trim($data['title']),
                'description' => ! empty($data['description']) ? trim($data['description']) : null,
                'due_at' => $dueAtUtc,
                'priority' => $data['priority'] ?? $reminder->priority,
                'recurrence' => $data['recurrence'] ?? $reminder->recurrence,
            ]);

            return $reminder->fresh();
        });
    }

    /**
     * Convert date and time in user timezone to UTC Carbon instance.
     */
    public function parseToUtc(string $date, string $time, string $userTimezone): Carbon
    {
        $timeClean = trim($time);
        if (strlen($timeClean) === 5) {
            $timeClean .= ':00';
        }

        try {
            return Carbon::createFromFormat('Y-m-d H:i:s', "{$date} {$timeClean}", $userTimezone)->setTimezone('UTC');
        } catch (\Throwable $e) {
            throw new InvalidArgumentException("Invalid date or time provided: {$date} {$timeClean}");
        }
    }
}
