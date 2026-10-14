<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppIdentity extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_identities';

    protected $fillable = [
        'user_id',
        'phone_number',
        'display_name',
        'status',
        'verification_token',
        'verification_expires_at',
        'verified_at',
        'verified_by_user_id',
        'manual_verification_reason',
        'consent_recorded_at',
        'last_inbound_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'verification_expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'consent_recorded_at' => 'datetime',
            'last_inbound_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class);
    }

    public function deliveryAttempts(): HasMany
    {
        return $this->hasMany(ReminderDeliveryAttempt::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->verified_at !== null;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending_verification';
    }

    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Check if customer is within the Meta 24-hour customer service window.
     */
    public function isWithin24HourServiceWindow(): bool
    {
        if (!$this->last_inbound_at) {
            return false;
        }

        return $this->last_inbound_at->greaterThanOrEqualTo(Carbon::now()->subHours(24));
    }

    /**
     * Normalize a phone number to E.164 format.
     */
    public static function normalizePhoneNumber(string $number): string
    {
        // Strip non-numeric characters except leading +
        $cleaned = preg_replace('/[^\d+]/', '', trim($number));

        // If starts with 00, replace with +
        if (str_starts_with($cleaned, '00')) {
            $cleaned = '+' . substr($cleaned, 2);
        }

        // If local Bangladesh number like 017XXXXXXXX, prepend +88
        if (preg_match('/^01[3-9]\d{8}$/', $cleaned)) {
            $cleaned = '+88' . $cleaned;
        }

        // Ensure leading +
        if (!str_starts_with($cleaned, '+')) {
            $cleaned = '+' . $cleaned;
        }

        return $cleaned;
    }
}
