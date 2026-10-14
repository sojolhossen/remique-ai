@extends('layouts.app')

@section('title', 'My Reminders')
@section('page_title', 'My Reminders')

@section('content')
<div class="space-y-6">

    <!-- Header Actions Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">All Reminders</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Times displayed in your timezone: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $user->timezone ?: 'UTC' }}</span>
            </p>
        </div>

        <a href="{{ route('reminders.create') }}"
           class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs sm:text-sm shadow-sm shadow-indigo-600/20 hover:shadow-md transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <span>Create Reminder</span>
        </a>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
        <form method="GET" action="{{ route('reminders.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">

            <!-- Search input -->
            <div class="lg:col-span-2 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                       placeholder="Search reminders by title..."
                       class="w-full pl-9 pr-3.5 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-xs">
            </div>

            <!-- Status Filter -->
            <div>
                <select name="status" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-xs">
                    <option value="all" {{ ($filters['status'] ?? 'all') === 'all' ? 'selected' : '' }}>All Statuses</option>
                    <option value="scheduled" {{ ($filters['status'] ?? '') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                    <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <!-- Date Range Filter -->
            <div>
                <select name="date_filter" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-xs">
                    <option value="all" {{ ($filters['date_filter'] ?? 'all') === 'all' ? 'selected' : '' }}>All Dates</option>
                    <option value="today" {{ ($filters['date_filter'] ?? '') === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="tomorrow" {{ ($filters['date_filter'] ?? '') === 'tomorrow' ? 'selected' : '' }}>Tomorrow</option>
                    <option value="this_week" {{ ($filters['date_filter'] ?? '') === 'this_week' ? 'selected' : '' }}>This Week</option>
                    <option value="upcoming" {{ ($filters['date_filter'] ?? '') === 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                    <option value="overdue" {{ ($filters['date_filter'] ?? '') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                </select>
            </div>

            <!-- Sort By / Submit -->
            <div class="flex items-center gap-2">
                <select name="sort_by" class="flex-1 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-xs">
                    <option value="due_at_asc" {{ ($filters['sort_by'] ?? 'due_at_asc') === 'due_at_asc' ? 'selected' : '' }}>Due Date (Soonest)</option>
                    <option value="due_at_desc" {{ ($filters['sort_by'] ?? '') === 'due_at_desc' ? 'selected' : '' }}>Due Date (Latest)</option>
                    <option value="priority_desc" {{ ($filters['sort_by'] ?? '') === 'priority_desc' ? 'selected' : '' }}>Priority (Highest)</option>
                    <option value="created_at_desc" {{ ($filters['sort_by'] ?? '') === 'created_at_desc' ? 'selected' : '' }}>Recently Added</option>
                </select>

                <button type="submit" class="px-3.5 py-2 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold transition-colors">
                    Filter
                </button>

                @if (!empty($filters['search']) || !empty($filters['status']) || !empty($filters['date_filter']) || !empty($filters['sort_by']))
                    <a href="{{ route('reminders.index') }}" title="Reset Filters" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </a>
                @endif
            </div>

        </form>
    </div>

    <!-- Reminders Table / Cards -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
        @if ($reminders->isEmpty())
            <!-- Empty state -->
            <div class="py-16 px-4 text-center">
                <div class="w-14 h-14 mx-auto rounded-full bg-indigo-50 dark:bg-indigo-950/60 flex items-center justify-center text-indigo-500 mb-4">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">No reminders found</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">
                    {{ !empty($filters['search']) ? 'No reminders match your search filters.' : 'You do not have any reminders in this view.' }}
                </p>
                <div class="mt-5">
                    <a href="{{ route('reminders.create') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs shadow-sm transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <span>Create New Reminder</span>
                    </a>
                </div>
            </div>
        @else
            <!-- Desktop Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-900/50 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            <th class="py-3 px-4">Title & Details</th>
                            <th class="py-3 px-4">Due Date & Time</th>
                            <th class="py-3 px-4">Priority</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                        @foreach ($reminders as $reminder)
                            @php
                                $dueLocal = $reminder->due_at->copy()->setTimezone($user->timezone ?: 'UTC');
                                $isOverdue = $reminder->isOverdue();
                            @endphp
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors">
                                <!-- Title & Description -->
                                <td class="py-3.5 px-4 max-w-md">
                                    <div class="font-semibold text-slate-900 dark:text-white text-sm">
                                        <a href="{{ route('reminders.edit', $reminder) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                            {{ $reminder->title }}
                                        </a>
                                    </div>
                                    @if ($reminder->description)
                                        <p class="text-slate-500 dark:text-slate-400 text-xs mt-0.5 line-clamp-1">
                                            {{ $reminder->description }}
                                        </p>
                                    @endif
                                    @if ($reminder->recurrence !== 'none')
                                        <span class="inline-flex items-center gap-1 text-[10px] text-indigo-600 dark:text-indigo-400 font-medium mt-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                            Repeats {{ $reminder->recurrence }}
                                        </span>
                                    @endif
                                </td>

                                <!-- Due Date & Time -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="font-medium text-slate-800 dark:text-slate-200">
                                        {{ $dueLocal->format('M d, Y') }}
                                    </div>
                                    <div class="text-slate-500 dark:text-slate-400 flex items-center gap-1.5 mt-0.5">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <span>{{ $dueLocal->format('h:i A') }}</span>
                                        @if ($reminder->status === 'scheduled')
                                            <span class="text-[10px] {{ $isOverdue ? 'text-rose-600 dark:text-rose-400 font-bold' : 'text-slate-400' }}">
                                                ({{ $dueLocal->diffForHumans() }})
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Priority -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                                        {{ $reminder->priority === 'urgent' ? 'bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300' :
                                           ($reminder->priority === 'high' ? 'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300' :
                                           ($reminder->priority === 'low' ? 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' :
                                           'bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300')) }}">
                                        {{ $reminder->priority }}
                                    </span>
                                </td>

                                <!-- Status Badge -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if ($reminder->status === 'completed')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            Completed
                                        </span>
                                    @elseif ($reminder->status === 'cancelled')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400">
                                            Cancelled
                                        </span>
                                    @elseif ($isOverdue)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                            Overdue
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                            Scheduled
                                        </span>
                                    @endif
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-1.5">

                                        @if ($reminder->status === 'scheduled')
                                            <!-- Complete Action -->
                                            <form method="POST" action="{{ route('reminders.complete', $reminder) }}">
                                                @csrf
                                                <button type="submit" title="Mark Complete"
                                                        class="p-1.5 rounded-md text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/50 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                </button>
                                            </form>

                                            <!-- Cancel Action with confirm -->
                                            <form method="POST" action="{{ route('reminders.cancel', $reminder) }}" onsubmit="return confirm('Cancel this scheduled reminder?');">
                                                @csrf
                                                <button type="submit" title="Cancel Reminder"
                                                        class="p-1.5 rounded-md text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                                </button>
                                            </form>
                                        @else
                                            <!-- Reopen Action -->
                                            <form method="POST" action="{{ route('reminders.reopen', $reminder) }}">
                                                @csrf
                                                <button type="submit" title="Reactivate Reminder"
                                                        class="p-1.5 rounded-md text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                </button>
                                            </form>
                                        @endif

                                        <!-- Edit Action -->
                                        <a href="{{ route('reminders.edit', $reminder) }}" title="Edit Reminder"
                                           class="p-1.5 rounded-md text-slate-400 hover:text-indigo-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        </a>

                                        <!-- Delete Action with confirm -->
                                        <form method="POST" action="{{ route('reminders.destroy', $reminder) }}" onsubmit="return confirm('Permanently delete this reminder? This action cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Delete Reminder"
                                                    class="p-1.5 rounded-md text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $reminders->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
