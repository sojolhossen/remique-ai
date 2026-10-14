<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramMessage extends Model
{
    use HasFactory;

    protected $table = 'telegram_messages';

    protected $fillable = [
        'telegram_identity_id',
        'chat_id',
        'direction',
        'role',
        'message_id',
        'body',
    ];

    public function identity(): BelongsTo
    {
        return $this->belongsTo(TelegramIdentity::class, 'telegram_identity_id');
    }
}
