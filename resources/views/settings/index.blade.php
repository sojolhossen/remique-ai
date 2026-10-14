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

                <!-- Preferred Delivery Channel -->
                <div class="pt-2">
                    <label for="preferred_channel" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Preferred Reminder Channel
                    </label>
                    <select id="preferred_channel" name="preferred_channel"
                            class="w-full sm:w-1/2 px-3 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500">
                        <option value="all" {{ old('preferred_channel', $user->preferred_channel ?? 'all') === 'all' ? 'selected' : '' }}>
                            Deliver Everywhere (WhatsApp & Telegram)
                        </option>
                        <option value="telegram" {{ old('preferred_channel', $user->preferred_channel ?? 'all') === 'telegram' ? 'selected' : '' }}>
                            Telegram Bot Only
                        </option>
                        <option value="whatsapp" {{ old('preferred_channel', $user->preferred_channel ?? 'all') === 'whatsapp' ? 'selected' : '' }}>
                            WhatsApp Cloud API Only
                        </option>
                    </select>
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

    <!-- Telegram Bot Integration Card -->
    <div class="bg-white dark:bg-slate-900 p-6 sm:p-8 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
        <div class="flex items-center justify-between pb-5 border-b border-slate-100 dark:border-slate-800 mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-sky-500 text-white flex items-center justify-center font-bold shadow-md shadow-sky-500/20">
                    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Telegram Personal Assistant</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Chat naturally in Bengali, Banglish, or English directly on Telegram</p>
                </div>
            </div>

            @if ($telegramIdentity && $telegramIdentity->isActive())
                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800">
                    CONNECTED
                </span>
            @else
                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-300 dark:border-amber-800">
                    NOT CONNECTED
                </span>
            @endif
        </div>

        @if ($telegramIdentity && $telegramIdentity->isActive())
            <div class="space-y-4">
                <div class="p-4 rounded-xl bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/60 text-xs text-emerald-900 dark:text-emerald-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <div class="font-bold text-sm">Linked Chat: {{ $telegramIdentity->first_name ?? 'User' }} @if($telegramIdentity->username)({{ '@' . $telegramIdentity->username }})@endif</div>
                        <div class="text-[11px] opacity-80 mt-0.5">Chat ID: <code class="font-mono">{{ $telegramIdentity->chat_id }}</code> • Verified on: {{ $telegramIdentity->verified_at?->format('M d, Y') }}</div>
                    </div>
                    <a href="https://t.me/{{ $botUsername }}" target="_blank"
                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-sky-500 hover:bg-sky-600 text-white font-bold text-xs shadow-xs transition-colors shrink-0">
                        <span>Open Bot in Telegram</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    </a>
                </div>
            </div>
        @else
            <div class="space-y-5">
                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                    Connect your Telegram account to receive instant free reminders and store private documents straight from your Telegram app.
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Option 1: 1-Click Deep Link -->
                    <div class="p-4 rounded-xl bg-sky-50 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800/60 flex flex-col justify-between">
                        <div>
                            <span class="text-xs font-bold text-sky-900 dark:text-sky-200 block mb-1">Option 1: One-Click Connection</span>
                            <span class="text-[11px] text-sky-700 dark:text-sky-400">Click the button below to launch Telegram and link your account instantly.</span>
                        </div>
                        <div class="pt-4">
                            <a href="https://t.me/{{ $botUsername }}?start={{ $telegramToken }}" target="_blank"
                               class="inline-flex items-center justify-center gap-2 w-full py-2.5 px-4 rounded-xl bg-sky-500 hover:bg-sky-600 text-white font-bold text-xs shadow-sm shadow-sky-500/20 transition-all">
                                <span>Connect with @ {{ $botUsername }}</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </a>
                        </div>
                    </div>

                    <!-- Option 2: Manual Code -->
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 flex flex-col justify-between">
                        <div>
                            <span class="text-xs font-bold text-slate-900 dark:text-slate-200 block mb-1">Option 2: Enter Verification Code</span>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400">Search for <strong>@ {{ $botUsername }}</strong> on Telegram and send this code:</span>
                        </div>
                        <div class="pt-3">
                            <div class="text-center py-2 px-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-300 dark:border-slate-700 font-mono text-base font-extrabold tracking-widest text-indigo-600 dark:text-indigo-400 select-all">
                                {{ $telegramToken }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
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
