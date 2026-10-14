<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reminder extends Model
{
    use HasFactory;

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public const RECURRENCE_NONE = 'none';
    public const RECURRENCE_DAILY = 'daily';
    public const RECURRENCE_WEEKLY = 'weekly';
    public const RECURRENCE_MONTHLY = 'monthly';
    public const RECURRENCE_YEARLY = 'yearly';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'due_at',
        'status',
        'priority',
        'recurrence',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Owning user relationship.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Delivery attempts relationship.
     */
    public function deliveryAttempts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ReminderDeliveryAttempt::class);
    }

    /**
     * Scopes
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SCHEDULED)
                     ->where('due_at', '>=', Carbon::now('UTC'));
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SCHEDULED)
                     ->where('due_at', '<', Carbon::now('UTC'));
    }

    /**
     * Check if reminder is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_SCHEDULED && $this->due_at->isPast();
    }

    /**
     * State transition: Mark as completed.
     * Prevents invalid state transitions.
     */
    public function markAsCompleted(): bool
    {
        if ($this->status === self::STATUS_COMPLETED) {
            return false;
        }

        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = Carbon::now('UTC');
        $this->cancelled_at = null;

        return $this->save();
    }

    /**
     * State transition: Mark as cancelled.
     */
    public function markAsCancelled(): bool
    {
        if ($this->status === self::STATUS_CANCELLED) {
            return false;
        }

        $this->status = self::STATUS_CANCELLED;
        $this->cancelled_at = Carbon::now('UTC');
        $this->completed_at = null;

        return $this->save();
    }

    /**
     * State transition: Reopen / reschedule.
     */
    public function reopen(?\DateTimeInterface $newDueAt = null): bool
    {
        $this->status = self::STATUS_SCHEDULED;
        if ($newDueAt) {
            $this->due_at = $newDueAt;
        }
        $this->completed_at = null;
        $this->cancelled_at = null;

        return $this->save();
    }
}
