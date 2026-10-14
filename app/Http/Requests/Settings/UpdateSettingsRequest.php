<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'timezone' => ['required', 'string', 'timezone:all'],
            'preferred_language' => ['required', 'string', 'in:en,bn'],
            'theme' => ['required', 'string', 'in:light,dark,system'],
            'preferred_channel' => ['nullable', 'string', 'in:whatsapp,telegram,all'],
            'notification_email' => ['nullable', 'boolean'],
            'notification_sound' => ['nullable', 'boolean'],
        ];
    }
}
