<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WhatsAppMessage extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'wamid',
        'whatsapp_identity_id',
        'direction',
        'from_number',
        'to_number',
        'message_type',
        'body',
        'media_id',
        'mime_type',
        'status',
        'error_code',
        'error_message',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
        ];
    }

    public function identity(): BelongsTo
    {
        return $this->belongsTo(WhatsAppIdentity::class, 'whatsapp_identity_id');
    }

    public function vaultFile(): HasOne
    {
        return $this->hasOne(VaultFile::class, 'whatsapp_message_id');
    }

    public function deliveryAttempt(): HasOne
    {
        return $this->hasOne(ReminderDeliveryAttempt::class, 'whatsapp_message_id');
    }
}
