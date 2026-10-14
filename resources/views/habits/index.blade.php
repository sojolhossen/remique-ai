@extends('layouts.app')

@section('title', 'Daily Habits & Streaks')

@section('content')
<div class="space-y-6" x-data="{
    showAddModal: false,
    testingDigest: false,
    digestSuccess: null,
    newHabit: {
        name: '',
        frequency: 'daily',
        reminder_time: '08:00',
        description: ''
    },
    prefillHabit(name, time) {
        this.newHabit.name = name;
        this.newHabit.reminder_time = time;
        this.showAddModal = true;
    },
    toggleHabit(habitId, el) {
        fetch('/habits/' + habitId + '/toggle', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            }
        })
        .catch(err => {
            console.error('Error toggling habit:', err);
        });
    },
    sendMorningDigest() {
        this.testingDigest = true;
        this.digestSuccess = null;
        fetch('{{ route('habits.send-test-digest') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            this.testingDigest = false;
            this.digestSuccess = data;
        })
        .catch(err => {
            this.testingDigest = false;
            this.digestSuccess = { success: false, message: 'Failed to send: ' + err };
        });
    }
}">

    <!-- Top Action Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Daily Habits & Streaks</h1>
                <span class="px-2 py-0.5 rounded text-xs font-bold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">🔥 Habit Tracker</span>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Build positive routines, maintain daily streaks, and stay disciplined with Remi AI.</p>
        </div>

        <div class="flex items-center gap-2.5">
            <!-- Test Morning Briefing Button -->
            <button type="button"
                    @click="sendMorningDigest()"
                    :disabled="testingDigest"
                    class="py-2.5 px-3.5 rounded-xl text-xs font-bold border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 transition-colors flex items-center gap-2 shadow-xs">
                <svg x-show="testingDigest" class="animate-spin w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                <span x-show="!testingDigest">☀️</span>
                <span x-text="testingDigest ? 'Dispatching...' : 'Send Morning Briefing'"></span>
            </button>

            <!-- Add Habit CTA Button -->
            <button type="button"
                    @click="showAddModal = true"
                    class="py-2.5 px-4 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white transition-colors flex items-center gap-2 shadow-sm shadow-indigo-600/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>New Habit</span>
            </button>
        </div>
    </div>

    <!-- Notification / Flash Messages -->
    @if (session('status'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-sm font-semibold flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                <span>{{ session('status') }}</span>
            </div>
            <button @click="$el.parentElement.remove()" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline">Dismiss</button>
        </div>
    @endif

    <!-- Alert for Morning Digest Test -->
    <template x-if="digestSuccess">
        <div class="p-4 rounded-xl border text-sm font-semibold flex items-center justify-between transition-all"
             :class="digestSuccess.success ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-700 dark:text-emerald-400' : 'bg-rose-500/10 border-rose-500/20 text-rose-700 dark:text-rose-400'">
            <div class="flex items-center gap-2">
                <span x-text="digestSuccess.success ? '✓' : '⚠️'"></span>
                <span x-text="digestSuccess.message"></span>
            </div>
            <button @click="digestSuccess = null" class="text-xs hover:underline">Dismiss</button>
        </div>
    </template>

    <!-- Overview Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Today's Progress Card -->
        <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Today's Progress</span>
                <span class="p-2 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ $completedToday }} / {{ $totalHabits }}</span>
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Done</span>
                </div>
                <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 mt-2 overflow-hidden">
                    <div class="bg-indigo-600 h-2 rounded-full transition-all duration-500" style="width: {{ $completionRate }}%"></div>
                </div>
            </div>
        </div>

        <!-- Total Streaks Card -->
        <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Active Streaks</span>
                <span class="p-2 rounded-lg bg-amber-50 dark:bg-amber-950/60 text-amber-500">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.316.492-.593 1.05-.823 1.58-.292.67-.53 1.353-.706 1.968-.13.456-.237.876-.308 1.22a7.994 7.994 0 00-1.748-1.57 1 1 0 00-1.542.868 8 8 0 1010.59 1.137 7.98 7.98 0 00-1.92-3.864c-.463-.538-1.026-1.018-1.631-1.398-.242-.152-.45-.347-.45-.347z" clip-rule="evenodd" /></svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-bold text-amber-500">{{ $totalStreak }} Days</span>
                </div>
                <p class="text-xs text-slate-400 mt-1">Accumulated across all habits</p>
            </div>
        </div>

        <!-- Longest Streak Card -->
        <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Best Record</span>
                <span class="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ $longestStreak }} Days</span>
                </div>
                <p class="text-xs text-slate-400 mt-1">Personal longest consistency</p>
            </div>
        </div>

        <!-- Daily Briefing Status Card -->
        <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Morning Briefing</span>
                <span class="p-2 rounded-lg bg-sky-50 dark:bg-sky-950/60 text-sky-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <span class="text-sm font-bold text-slate-900 dark:text-white">Active at {{ auth()->user()->getMorningDigestTime() }}</span>
                </div>
                <a href="{{ route('settings.edit') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline mt-1 inline-block">Configure timing</a>
            </div>
        </div>
    </div>

    <!-- Habits List Table / Cards -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-white">My Active Habits</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Click the checkbox to complete a habit for today, or chat with Remi in Telegram: <code class="bg-slate-100 dark:bg-slate-800 px-1 py-0.5 rounded text-indigo-600 dark:text-indigo-400">done water</code></p>
            </div>
        </div>

        @if ($habits->isEmpty())
            <!-- Empty State with Pre-fill Suggestions -->
            <div class="p-10 text-center space-y-4">
                <div class="w-16 h-16 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center mx-auto text-2xl">
                    🔥
                </div>
                <div class="max-w-md mx-auto space-y-1">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">No habits added yet</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Consistency creates success! Choose one of the popular habits below to get started in 1-click:</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 max-w-lg mx-auto pt-2">
                    <button type="button" @click="prefillHabit('Drink 2L Water', '09:00')"
                            class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:border-indigo-500 dark:hover:border-indigo-500 hover:bg-slate-50 dark:hover:bg-slate-800/60 text-left transition-all group">
                        <span class="text-xl">💧</span>
                        <div class="font-bold text-xs text-slate-900 dark:text-white mt-1 group-hover:text-indigo-600 dark:group-hover:text-indigo-400">Drink 2L Water</div>
                        <span class="text-[10px] text-slate-400">Daily Health</span>
                    </button>

                    <button type="button" @click="prefillHabit('20 Mins Workout', '07:30')"
                            class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:border-indigo-500 dark:hover:border-indigo-500 hover:bg-slate-50 dark:hover:bg-slate-800/60 text-left transition-all group">
                        <span class="text-xl">🏃‍♂️</span>
                        <div class="font-bold text-xs text-slate-900 dark:text-white mt-1 group-hover:text-indigo-600 dark:group-hover:text-indigo-400">20 Mins Workout</div>
                        <span class="text-[10px] text-slate-400">Morning Fitness</span>
                    </button>

                    <button type="button" @click="prefillHabit('Read 15 Pages', '21:00')"
                            class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:border-indigo-500 dark:hover:border-indigo-500 hover:bg-slate-50 dark:hover:bg-slate-800/60 text-left transition-all group">
                        <span class="text-xl">📚</span>
                        <div class="font-bold text-xs text-slate-900 dark:text-white mt-1 group-hover:text-indigo-600 dark:group-hover:text-indigo-400">Read 15 Pages</div>
                        <span class="text-[10px] text-slate-400">Mind Growth</span>
                    </button>
                </div>
            </div>
        @else
            <!-- Habit Items -->
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach ($habits as $habit)
                    <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                        <!-- Habit Info & Toggle -->
                        <div class="flex items-center gap-4">
                            <!-- Toggle Button Checkbox -->
                            <button type="button"
                                    @click="toggleHabit({{ $habit->id }}, $el)"
                                    class="w-8 h-8 rounded-xl flex items-center justify-center transition-all shadow-xs border {{ $habit->is_completed_today ? 'bg-emerald-500 border-emerald-500 text-white hover:bg-emerald-600' : 'bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-transparent hover:border-indigo-500' }}">
                                <svg class="w-5 h-5 {{ $habit->is_completed_today ? 'text-white' : 'text-slate-300 dark:text-slate-600 opacity-0 hover:opacity-100' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>

                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-bold text-slate-900 dark:text-white {{ $habit->is_completed_today ? 'line-through text-slate-400 dark:text-slate-500' : '' }}">
                                        {{ $habit->name }}
                                    </h3>
                                    @if ($habit->current_streak > 0)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                                            🔥 {{ $habit->current_streak }}d streak
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-3 text-[11px] text-slate-400 mt-1">
                                    <span class="capitalize">{{ $habit->frequency }}</span>
                                    @if ($habit->reminder_time)
                                        <span>• Alarm {{ \Carbon\Carbon::parse($habit->reminder_time)->format('h:i A') }}</span>
                                    @endif
                                    @if ($habit->description)
                                        <span class="hidden md:inline">• {{ Str::limit($habit->description, 40) }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- 7-Day History Pills and Actions -->
                        <div class="flex items-center justify-between sm:justify-end gap-5">
                            <!-- 7-day pill indicators -->
                            <div class="flex items-center gap-1.5">
                                @foreach ($habit->history_days as $day)
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="text-[9px] font-semibold text-slate-400 {{ $day['is_today'] ? 'text-indigo-600 dark:text-indigo-400 font-bold' : '' }}">
                                            {{ substr($day['day_name'], 0, 1) }}
                                        </span>
                                        <div class="w-6 h-6 rounded-lg flex items-center justify-center text-[10px] font-bold border {{ $day['is_completed'] ? 'bg-emerald-500 text-white border-emerald-500 shadow-xs' : ($day['is_today'] ? 'border-dashed border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-400' : 'border-slate-200 dark:border-slate-800 text-slate-300 dark:text-slate-600') }}">
                                            @if ($day['is_completed'])
                                                ✓
                                            @else
                                                {{ $day['day_num'] }}
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Delete Action -->
                            <form action="{{ route('habits.destroy', $habit) }}" method="POST" onsubmit="return confirm('Delete this habit?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Add Habit Modal -->
    <div x-show="showAddModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showAddModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                 @click="showAddModal = false" aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showAddModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 dark:border-slate-800">

                <form action="{{ route('habits.store') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2">
                            <span class="p-1.5 rounded-lg bg-amber-500/10 text-amber-500 text-sm">🔥</span>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Create New Habit</h3>
                        </div>
                        <button type="button" @click="showAddModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Habit Name <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" x-model="newHabit.name" required placeholder="e.g. Drink 2L Water, Morning Run, Read 10 Pages"
                                   class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Frequency</label>
                                <select name="frequency" x-model="newHabit.frequency" class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="daily">Everyday</option>
                                    <option value="weekdays">Weekdays Only</option>
                                    <option value="weekends">Weekends Only</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Reminder Time</label>
                                <input type="time" name="reminder_time" x-model="newHabit.reminder_time"
                                       class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Motivation / Notes (Optional)</label>
                            <textarea name="description" x-model="newHabit.description" rows="2" placeholder="Why is this habit important to you?"
                                      class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2">
                        <button type="button" @click="showAddModal = false" class="py-2 px-4 rounded-lg text-xs font-bold border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="py-2 px-4 rounded-lg text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white transition-colors shadow-sm shadow-indigo-600/20">
                            Save Habit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
