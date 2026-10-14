@extends('layouts.app')

@section('title', 'Settings')
@section('page_title', 'Account & System Settings')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Profile & Preferences Form -->
    <div class="bg-white dark:bg-slate-900 p-6 sm:p-8 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
        <div class="pb-5 border-b border-slate-100 dark:border-slate-800 mb-6">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Profile & Preferences</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Configure your timezone, interface language, and theme</p>
        </div>

        <form method="POST" action="{{ route('settings.update') }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Full Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Email Address
                    </label>
                    <input type="email" value="{{ $user->email }}" disabled
                           class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 text-slate-500 text-sm cursor-not-allowed">
                    <span class="text-[10px] text-slate-400 mt-1 block">Email address cannot be changed directly</span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Timezone -->
                <div>
                    <label for="timezone" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Default Timezone <span class="text-rose-500">*</span>
                    </label>
                    <select id="timezone" name="timezone" required
                            class="w-full px-3 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500">
                        @foreach ($timezones as $tz)
                            <option value="{{ $tz }}" {{ old('timezone', $user->timezone) === $tz ? 'selected' : '' }}>
                                {{ $tz }}
                            </option>
                        @endforeach
                    </select>
                    <span class="text-[10px] text-slate-400 mt-1 block">Used by Remi AI to interpret relative dates</span>
                </div>

                <!-- Preferred Language -->
                <div>
                    <label for="preferred_language" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Interface Language
                    </label>
                    <select id="preferred_language" name="preferred_language"
                            class="w-full px-3 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500">
                        <option value="en" {{ old('preferred_language', $user->preferred_language) === 'en' ? 'selected' : '' }}>English (Default)</option>
                        <option value="bn" {{ old('preferred_language', $user->preferred_language) === 'bn' ? 'selected' : '' }}>বাংলা (Bengali)</option>
                    </select>
                </div>

                <!-- Theme Preference -->
                <div>
                    <label for="theme" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Theme Preference
                    </label>
                    <select id="theme" name="theme"
                            class="w-full px-3 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500">
                        <option value="light" {{ old('theme', $user->theme) === 'light' ? 'selected' : '' }}>Light Theme (Default)</option>
                        <option value="dark" {{ old('theme', $user->theme) === 'dark' ? 'selected' : '' }}>Dark Theme</option>
                        <option value="system" {{ old('theme', $user->theme) === 'system' ? 'selected' : '' }}>Sync with System</option>
                    </select>
                </div>
            </div>

            <!-- Notifications Preferences -->
            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-3">
                <span class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Notification Channels
                </span>

                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-700 dark:text-slate-300">
                        <input type="checkbox" name="notification_email" value="1"
                               {{ old('notification_email', $user->notification_preferences['email'] ?? true) ? 'checked' : '' }}
                               class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500">
                        <span>Email Notifications</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-700 dark:text-slate-300">
                        <input type="checkbox" name="notification_sound" value="1"
                               {{ old('notification_sound', $user->notification_preferences['sound'] ?? true) ? 'checked' : '' }}
                               class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500">
                        <span>Audible Reminders / Sounds</span>
                    </label>
                </div>

                <!-- WhatsApp Integration Notice (Clean Extension Point for Prompt 2) -->
                <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                        <span>WhatsApp Assistant Delivery (Meta Cloud API): <strong class="text-slate-700 dark:text-slate-300">Scheduled for Prompt 2 Release</strong></span>
                    </div>
                    <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300">Coming Soon</span>
                </div>
            </div>

            <div class="pt-4 flex justify-end">
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs sm:text-sm shadow-md shadow-indigo-600/20 transition-all">
                    Save Preferences
                </button>
            </div>
        </form>
    </div>

    <!-- Security & Password Update -->
    <div class="bg-white dark:bg-slate-900 p-6 sm:p-8 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
        <div class="pb-5 border-b border-slate-100 dark:border-slate-800 mb-6">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Security & Password</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Ensure your account is protected with a strong password</p>
        </div>

        <form method="POST" action="{{ route('settings.password') }}" class="space-y-4 max-w-lg">
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    Current Password <span class="text-rose-500">*</span>
                </label>
                <input type="password" id="current_password" name="current_password" required
                       class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label for="new_password" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    New Password <span class="text-rose-500">*</span>
                </label>
                <input type="password" id="new_password" name="password" required
                       class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500"
                       placeholder="At least 8 characters">
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    Confirm New Password <span class="text-rose-500">*</span>
                </label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                       class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500"
                       placeholder="Confirm new password">
            </div>

            <div class="pt-2">
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-900 dark:bg-slate-700 dark:hover:bg-slate-600 text-white font-semibold text-xs sm:text-sm shadow-sm transition-all">
                    Update Password
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
