<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppTemplate extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'name',
        'category',
        'language',
        'status',
        'body_text',
        'parameters',
    ];

    protected function casts(): array
    {
        return [
            'parameters' => 'array',
        ];
    }
}
