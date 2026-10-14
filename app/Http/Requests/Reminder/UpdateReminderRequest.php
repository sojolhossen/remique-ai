<?php

namespace App\Http\Requests\Reminder;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reminder = $this->route('reminder');
        return $reminder && $this->user()?->can('update', $reminder);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/'],
            'priority' => ['required', 'string', 'in:low,medium,high,urgent'],
            'recurrence' => ['nullable', 'string', 'in:none,daily,weekly,monthly,yearly'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $date = $this->input('date');
            $time = $this->input('time');

            if ($date && $time) {
                $userTz = $this->user()?->timezone ?: 'UTC';
                $cleanTime = strlen(trim($time)) === 5 ? trim($time) . ':00' : trim($time);

                try {
                    $dueInUserTz = Carbon::createFromFormat('Y-m-d H:i:s', "{$date} {$cleanTime}", $userTz);
                    $nowInUserTz = Carbon::now($userTz);

                    if ($dueInUserTz->lt($nowInUserTz->subMinute())) {
                        $validator->errors()->add('date', 'The reminder date and time cannot be in the past.');
                    }
                } catch (\Throwable $e) {
                    $validator->errors()->add('date', 'The provided date and time could not be parsed.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Please enter a reminder title.',
            'date.required' => 'Please choose a reminder date.',
            'time.required' => 'Please choose a reminder time.',
        ];
    }
}
