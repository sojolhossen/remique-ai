<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramIdentity extends Model
{
    use HasFactory;

    protected $table = 'telegram_identities';

    protected $fillable = [
        'user_id',
        'chat_id',
        'username',
        'first_name',
        'status',
        'verification_token',
        'verification_expires_at',
        'verified_at',
        'last_inbound_at',
    ];

    protected function casts(): array
    {
        return [
            'verification_expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'last_inbound_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
}
