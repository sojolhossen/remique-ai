@extends('layouts.admin')

@section('title', 'Register New Customer')
@section('page_title', 'Register Customer Account')
@section('page_description', 'Create a new authorized tenant account with initial WhatsApp number')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs">
        <form method="POST" action="{{ route('admin.customers.store') }}" class="space-y-4">
            @csrf

            <!-- Name -->
            <div>
                <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Full Name</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                       class="w-full text-xs py-2.5 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500">
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required
                       class="w-full text-xs py-2.5 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500">
            </div>

            <!-- Initial Password -->
            <div>
                <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Initial Password</label>
                <input type="password" id="password" name="password" required minlength="8"
                       class="w-full text-xs py-2.5 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500">
                <span class="text-[11px] text-slate-500">Minimum 8 characters. Customer can change this anytime.</span>
            </div>

            <!-- Initial WhatsApp Number -->
            <div>
                <label for="whatsapp_number" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">WhatsApp Phone Number</label>
                <input type="text" id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number') }}" placeholder="+88017XXXXXXXX"
                       class="w-full text-xs font-mono py-2.5 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500">
                <span class="text-[11px] text-slate-500">Will be normalized to E.164. A single-use verification token will be generated.</span>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <!-- Timezone -->
                <div>
                    <label for="timezone" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Timezone</label>
                    <select id="timezone" name="timezone" class="w-full text-xs py-2.5 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <option value="Asia/Dhaka" selected>Asia/Dhaka (GMT+6)</option>
                        <option value="UTC">UTC (Universal)</option>
                        <option value="America/New_York">America/New_York (EST)</option>
                        <option value="Europe/London">Europe/London (GMT)</option>
                    </select>
                </div>

                <!-- Language -->
                <div>
                    <label for="preferred_language" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Preferred Language</label>
                    <select id="preferred_language" name="preferred_language" class="w-full text-xs py-2.5 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <option value="en" selected>English</option>
                        <option value="bn">বাংলা (Bengali)</option>
                    </select>
                </div>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-200 dark:border-slate-800">
                <a href="{{ route('admin.customers.index') }}" class="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-800">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-lg text-xs font-bold text-slate-950 bg-amber-500 hover:bg-amber-400 transition-colors shadow-xs">
                    Save Customer Record
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
