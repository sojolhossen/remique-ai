@extends('layouts.app')

@section('title', 'Overview')
@section('page_title', 'Dashboard Overview')

@section('content')
<div class="space-y-6">

    <!-- Welcome & Quick AI Prompt Card -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-900 via-indigo-800 to-slate-900 p-6 sm:p-8 text-white shadow-xl shadow-indigo-950/20">
        <div class="relative z-10 max-w-2xl">
            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-white/10 text-indigo-200 text-xs font-semibold mb-3 backdrop-blur-xs">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>NVIDIA NIM Engine Powered &bull; GPT-OSS-20B</span>
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                Hello, {{ $user->name }}!
            </h2>
            <p class="mt-2 text-sm text-indigo-100/90 leading-relaxed">
                Create reminders naturally in Bengali, Banglish, or English. Type what you need and Remi AI will schedule it for your timezone (<span class="font-semibold">{{ $user->timezone ?: 'UTC' }}</span>).
            </p>

            <!-- Quick Action Links -->
            <div class="mt-5 flex flex-wrap items-center gap-3">
                <a href="{{ route('reminders.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white text-indigo-900 font-semibold text-xs sm:text-sm shadow-md hover:bg-indigo-50 transition-all hover:scale-[1.02]">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    <span>Create with AI</span>
                </a>
                <a href="{{ route('reminders.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/10 text-white font-medium text-xs sm:text-sm hover:bg-white/20 transition-all">
                    <span>View All Reminders</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </a>
            </div>
        </div>

        <!-- Subtle decorative glow -->
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- Live Metric Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        <!-- Total Reminders -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                <span class="text-xs font-semibold uppercase tracking-wider">Total</span>
                <div class="p-2 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">{{ $metrics['total_reminders'] }}</span>
                <span class="text-xs text-slate-400">reminders</span>
            </div>
        </div>

        <!-- Upcoming Reminders -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                <span class="text-xs font-semibold uppercase tracking-wider">Upcoming</span>
                <div class="p-2 rounded-lg bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">{{ $metrics['upcoming_reminders'] }}</span>
                <span class="text-xs text-slate-400">scheduled</span>
            </div>
        </div>

        <!-- Completed Reminders -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                <span class="text-xs font-semibold uppercase tracking-wider">Completed</span>
                <div class="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">{{ $metrics['completed_reminders'] }}</span>
                <span class="text-xs text-slate-400">done</span>
            </div>
        </div>

        <!-- Overdue Reminders -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                <span class="text-xs font-semibold uppercase tracking-wider">Overdue</span>
                <div class="p-2 rounded-lg bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-bold {{ $metrics['overdue_reminders'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' }}">
                    {{ $metrics['overdue_reminders'] }}
                </span>
                <span class="text-xs text-slate-400">pending</span>
            </div>
        </div>
    </div>

    <!-- Two-column Section: Next Upcoming & Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Next Upcoming Reminders -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <h3 class="font-bold text-slate-900 dark:text-white text-base">Next Upcoming Reminders</h3>
                    </div>
                    <a href="{{ route('reminders.index', ['date_filter' => 'upcoming']) }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                        View All
                    </a>
                </div>

                @if ($metrics['next_reminders']->isEmpty())
                    <!-- Empty State -->
                    <div class="py-12 text-center">
                        <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-200">No upcoming reminders</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xs mx-auto">You're all caught up! Create a new reminder with AI or manually.</p>
                        <a href="{{ route('reminders.create') }}" class="mt-4 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100">
                            + Add Reminder
                        </a>
                    </div>
                @else
                    <div class="divide-y divide-slate-100 dark:divide-slate-800 mt-2">
                        @foreach ($metrics['next_reminders'] as $reminder)
                            @php
                                $dueLocal = $reminder->due_at->copy()->setTimezone($user->timezone ?: 'UTC');
                            @endphp
                            <div class="py-3.5 flex items-start justify-between gap-3 group">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-sm font-semibold text-slate-900 dark:text-white truncate">
                                            {{ $reminder->title }}
                                        </h4>
                                        <span class="text-[10px] uppercase font-bold px-1.5 py-0.5 rounded
                                            {{ $reminder->priority === 'urgent' ? 'bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300' :
                                               ($reminder->priority === 'high' ? 'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300' :
                                               'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300') }}">
                                            {{ $reminder->priority }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-3 mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            {{ $dueLocal->format('M d, Y') }} at {{ $dueLocal->format('h:i A') }}
                                        </span>
                                        <span class="text-slate-300 dark:text-slate-700">&bull;</span>
                                        <span class="text-indigo-600 dark:text-indigo-400 font-medium">
                                            {{ $dueLocal->diffForHumans() }}
                                        </span>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('reminders.complete', $reminder) }}">
                                    @csrf
                                    <button type="submit" title="Mark Complete"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/50 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Recent Activity Log -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="font-bold text-slate-900 dark:text-white text-base">Recent Activity</h3>
                    </div>
                    <span class="text-xs text-slate-400">Live Database</span>
                </div>

                @if ($metrics['recent_activity']->isEmpty())
                    <div class="py-12 text-center">
                        <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-200">No activity yet</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xs mx-auto">Your reminder creation and completion history will appear here in real-time.</p>
                    </div>
                @else
                    <div class="divide-y divide-slate-100 dark:divide-slate-800 mt-2">
                        @foreach ($metrics['recent_activity'] as $activity)
                            <div class="py-3 flex items-center justify-between gap-3 text-xs">
                                <div class="min-w-0 flex-1 flex items-center gap-2.5">
                                    <span class="w-2 h-2 rounded-full
                                        {{ $activity->status === 'completed' ? 'bg-emerald-500' :
                                           ($activity->status === 'cancelled' ? 'bg-slate-400' : 'bg-indigo-500') }}">
                                    </span>
                                    <span class="font-medium text-slate-800 dark:text-slate-200 truncate">
                                        {{ $activity->title }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase
                                        {{ $activity->status === 'completed' ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300' :
                                           ($activity->status === 'cancelled' ? 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400' :
                                           'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300') }}">
                                        {{ $activity->status }}
                                    </span>
                                    <span class="text-slate-400">
                                        {{ $activity->updated_at->diffForHumans() }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

    </div>

</div>
@endsection
