<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderDeliveryAttempt extends Model
{
    use HasFactory;

    protected $table = 'reminder_delivery_attempts';

    protected $fillable = [
        'reminder_id',
        'whatsapp_identity_id',
        'idempotency_key',
        'channel',
        'status',
        'is_template',
        'template_name',
        'whatsapp_message_id',
        'attempt_number',
        'error_code',
        'error_message',
        'scheduled_for',
        'processing_started_at',
        'sent_at',
        'delivered_at',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_template' => 'boolean',
            'scheduled_for' => 'datetime',
            'processing_started_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function reminder(): BelongsTo
    {
        return $this->belongsTo(Reminder::class);
    }

    public function identity(): BelongsTo
    {
        return $this->belongsTo(WhatsAppIdentity::class, 'whatsapp_identity_id');
    }

    public function whatsappMessage(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMessage::class, 'whatsapp_message_id');
    }
}
