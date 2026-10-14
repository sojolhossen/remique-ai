<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\UpdateSettingsRequest;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Show the settings and preferences view.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $timezones = DateTimeZone::listIdentifiers();

        $telegramIdentity = $user->primaryTelegramIdentity;
        $telegramToken = null;

        if (! $telegramIdentity) {
            $pending = \App\Models\TelegramIdentity::where('user_id', $user->id)
                ->where('status', 'pending_verification')
                ->where('verification_expires_at', '>', now())
                ->first();

            if (! $pending) {
                $token = strtoupper(\Illuminate\Support\Str::random(6));
                $pending = \App\Models\TelegramIdentity::create([
                    'user_id' => $user->id,
                    'chat_id' => 'pending_' . $user->id . '_' . time(),
                    'status' => 'pending_verification',
                    'verification_token' => $token,
                    'verification_expires_at' => now()->addDays(7),
                ]);
            }
            $telegramToken = $pending->verification_token;
        }

        return view('settings.index', [
            'user' => $user,
            'timezones' => $timezones,
            'telegramIdentity' => $telegramIdentity,
            'telegramToken' => $telegramToken,
            'botUsername' => config('telegram.bot_username', 'RemiqueAiBot'),
        ]);
    }

    /**
     * Update user profile, language, timezone, theme, and notification preferences.
     */
    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $user = $request->user();

        $updateData = [
            'name' => trim($request->name),
            'timezone' => $request->timezone,
            'preferred_language' => $request->preferred_language,
            'theme' => $request->theme,
            'notification_preferences' => [
                'email' => (bool) $request->boolean('notification_email'),
                'sound' => (bool) $request->boolean('notification_sound'),
            ],
        ];

        if ($request->filled('preferred_channel')) {
            $updateData['preferred_channel'] = $request->preferred_channel;
        }

        $user->update($updateData);

        return back()->with('status', 'Settings updated successfully.');
    }

    /**
     * Update user password securely.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('status', 'Password changed successfully.');
    }
}
