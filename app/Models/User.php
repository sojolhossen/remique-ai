<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'preferred_language',
        'timezone',
        'theme',
        'preferred_channel',
        'notification_preferences',
    ];

    /**
     * Default model attribute values.
     */
    protected $attributes = [
        'role' => 'customer',
        'status' => 'active',
        'preferred_language' => 'en',
        'timezone' => 'UTC',
        'theme' => 'light',
        'preferred_channel' => 'all',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notification_preferences' => 'array',
        ];
    }

    /**
     * Get the reminders owned by the user.
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    /**
     * Get AI interaction logs associated with the user.
     */
    public function aiLogs(): HasMany
    {
        return $this->hasMany(AiInteractionLog::class);
    }

    /**
     * Get WhatsApp identities linked to this user.
     */
    public function whatsappIdentities(): HasMany
    {
        return $this->hasMany(WhatsAppIdentity::class);
    }

    /**
     * Get the active or verified WhatsApp identity.
     */
    public function primaryWhatsAppIdentity()
    {
        return $this->hasOne(WhatsAppIdentity::class)->whereIn('status', ['active', 'verified'])->latestOfMany();
    }

    /**
     * Get Telegram identities linked to this user.
     */
    public function telegramIdentities(): HasMany
    {
        return $this->hasMany(TelegramIdentity::class);
    }

    /**
     * Get the active or verified Telegram identity.
     */
    public function primaryTelegramIdentity()
    {
        return $this->hasOne(TelegramIdentity::class)->where('status', 'active')->latestOfMany();
    }

    /**
     * Get vault files owned by the user.
     */
    public function vaultFiles(): HasMany
    {
        return $this->hasMany(VaultFile::class);
    }

    /**
     * Check if user account is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if user has super admin privileges.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Check if user is a standard customer.
     */
    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }
}
