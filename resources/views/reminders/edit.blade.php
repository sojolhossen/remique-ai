@extends('layouts.app')

@section('title', 'Edit Reminder')
@section('page_title', 'Edit Reminder')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-slate-900 p-6 sm:p-8 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
        <div class="flex items-center justify-between pb-5 border-b border-slate-100 dark:border-slate-800 mb-6">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit Reminder</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Timezone: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $user->timezone ?: 'UTC' }}</span>
                </p>
            </div>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold uppercase tracking-wider
                {{ $reminder->status === 'completed' ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300' :
                   ($reminder->status === 'cancelled' ? 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400' :
                   'bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300') }}">
                {{ $reminder->status }}
            </span>
        </div>

        <form method="POST" action="{{ route('reminders.update', $reminder) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="title" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    Reminder Title <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="title" name="title" value="{{ old('title', $reminder->title) }}" required autofocus
                       class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
            </div>

            <div>
                <label for="description" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    Description / Notes (Optional)
                </label>
                <textarea id="description" name="description" rows="3"
                          class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">{{ old('description', $reminder->description) }}</textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="date" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Date <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" id="date" name="date" value="{{ old('date', $date) }}" required
                           class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                </div>

                <div>
                    <label for="time" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Time <span class="text-rose-500">*</span>
                    </label>
                    <input type="time" id="time" name="time" value="{{ old('time', $time) }}" required
                           class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="priority" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Priority Level
                    </label>
                    <select id="priority" name="priority"
                            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                        <option value="low" {{ old('priority', $reminder->priority) === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ old('priority', $reminder->priority) === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ old('priority', $reminder->priority) === 'high' ? 'selected' : '' }}>High</option>
                        <option value="urgent" {{ old('priority', $reminder->priority) === 'urgent' ? 'selected' : '' }}>Urgent</option>
                    </select>
                </div>

                <div>
                    <label for="recurrence" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Recurrence
                    </label>
                    <select id="recurrence" name="recurrence"
                            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                        <option value="none" {{ old('recurrence', $reminder->recurrence) === 'none' ? 'selected' : '' }}>Does not repeat</option>
                        <option value="daily" {{ old('recurrence', $reminder->recurrence) === 'daily' ? 'selected' : '' }}>Daily</option>
                        <option value="weekly" {{ old('recurrence', $reminder->recurrence) === 'weekly' ? 'selected' : '' }}>Weekly</option>
                        <option value="monthly" {{ old('recurrence', $reminder->recurrence) === 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="yearly" {{ old('recurrence', $reminder->recurrence) === 'yearly' ? 'selected' : '' }}>Yearly</option>
                    </select>
                </div>
            </div>

            <div class="pt-4 flex items-center justify-between border-t border-slate-100 dark:border-slate-800">
                <a href="{{ route('reminders.index') }}" class="text-xs font-medium text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                    &larr; Back to reminders
                </a>

                <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs sm:text-sm shadow-md shadow-indigo-600/20 transition-all">
                    Update Reminder
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
