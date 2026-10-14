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

        return view('settings.index', [
            'user' => $user,
            'timezones' => $timezones,
        ]);
    }

    /**
     * Update user profile, language, timezone, theme, and notification preferences.
     */
    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->update([
            'name' => trim($request->name),
            'timezone' => $request->timezone,
            'preferred_language' => $request->preferred_language,
            'theme' => $request->theme,
            'notification_preferences' => [
                'email' => (bool) $request->boolean('notification_email'),
                'sound' => (bool) $request->boolean('notification_sound'),
            ],
        ]);

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
