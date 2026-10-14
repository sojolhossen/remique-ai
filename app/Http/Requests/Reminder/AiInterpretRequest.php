<?php

namespace App\Http\Requests\Reminder;

use Illuminate\Foundation\Http\FormRequest;

class AiInterpretRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isActive();
    }

    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'prompt.required' => 'Please enter what you want to be reminded about.',
            'prompt.min' => 'Please enter at least 3 characters.',
            'prompt.max' => 'Your request is too long. Please keep it under 1000 characters.',
        ];
    }
}
