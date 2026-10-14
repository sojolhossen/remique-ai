<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Habit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'frequency',
        'current_streak',
        'longest_streak',
        'reminder_time',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'current_streak' => 'integer',
        'longest_streak' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(HabitLog::class);
    }

    /**
     * Check if this habit is completed for a specific date (defaults to today in user's timezone).
     */
    public function isCompletedOn(string|Carbon $date): bool
    {
        $dateStr = $date instanceof Carbon ? $date->toDateString() : $date;

        return $this->logs()->whereDate('logged_date', $dateStr)->exists();
    }

    /**
     * Recalculate streak from actual logs history.
     */
    public function recalculateStreak(?Carbon $today = null): int
    {
        $today = $today ?: Carbon::today($this->user?->timezone ?: 'Asia/Dhaka');
        
        $loggedDates = $this->logs()
            ->where('status', 'completed')
            ->orderByDesc('logged_date')
            ->pluck('logged_date')
            ->map(fn($d) => $d instanceof Carbon ? $d->toDateString() : (string) $d)
            ->all();

        if (empty($loggedDates)) {
            $this->update(['current_streak' => 0]);
            return 0;
        }

        $streak = 0;
        $checkDate = $today->copy();

        // If today is not completed yet, check if yesterday was completed to keep streak alive
        if (! in_array($checkDate->toDateString(), $loggedDates)) {
            $checkDate->subDay();
        }

        while (in_array($checkDate->toDateString(), $loggedDates)) {
            $streak++;
            $checkDate->subDay();
        }

        $longest = max($streak, (int) $this->longest_streak);

        $this->update([
            'current_streak' => $streak,
            'longest_streak' => $longest,
        ]);

        return $streak;
    }
}
