@extends('layouts.admin')

@section('title', 'Reminders Oversight & Delivery')
@section('page_title', 'Reminders & Delivery Engine')
@section('page_description', 'System-wide reminder registry, atomic scheduler queue, and failure resolution')

@section('content')
<div class="space-y-6">

    <!-- Filters & Search -->
    <div class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.reminders.index') }}" class="flex flex-1 items-center gap-3 w-full sm:w-auto">
            <div class="relative flex-1">
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Search title, description, or customer..."
                       class="w-full pl-9 pr-4 py-2 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>

            <select name="status" class="text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
                <option value="">All Statuses</option>
                <option value="scheduled" {{ $status === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>

            <button type="submit" class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-slate-800 hover:bg-slate-700 text-white dark:bg-slate-700 transition-colors">
                Filter
            </button>
            @if (!empty($search) || !empty($status))
                <a href="{{ route('admin.reminders.index') }}" class="text-xs text-slate-500 hover:underline">Reset</a>
            @endif
        </form>
    </div>

    <!-- Reminders Table -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 uppercase tracking-wider text-[10px] text-slate-500 font-bold">
                    <tr>
                        <th class="py-3 px-4">ID</th>
                        <th class="py-3 px-4">Customer</th>
                        <th class="py-3 px-4">Title & Details</th>
                        <th class="py-3 px-4">Due Time (UTC)</th>
                        <th class="py-3 px-4">Recurrence</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($reminders as $reminder)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors" x-data="{ rescheduleOpen: false }">
                            <td class="py-3 px-4 font-mono text-slate-400">#{{ $reminder->id }}</td>
                            <td class="py-3 px-4">
                                <a href="{{ route('admin.customers.show', $reminder->user) }}" class="font-bold text-slate-900 dark:text-white hover:underline">
                                    {{ $reminder->user->name ?? 'Unknown' }}
                                </a>
                                <div class="text-[11px] text-slate-500">{{ $reminder->user->timezone }}</div>
                            </td>
                            <td class="py-3 px-4 max-w-xs">
                                <div class="font-semibold text-slate-900 dark:text-white">{{ $reminder->title }}</div>
                                @if ($reminder->description)
                                    <div class="text-[11px] text-slate-500 truncate">{{ $reminder->description }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-4 font-mono">
                                {{ $reminder->due_at->toDateTimeString() }}
                            </td>
                            <td class="py-3 px-4 capitalize">
                                {{ $reminder->recurrence }}
                            </td>
                            <td class="py-3 px-4">
                                @if ($reminder->status === 'completed')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">COMPLETED</span>
                                @elseif ($reminder->status === 'scheduled')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">SCHEDULED</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">CANCELLED</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right space-x-1">
                                <!-- Reschedule Toggle -->
                                <button type="button" @click="rescheduleOpen = !rescheduleOpen" class="px-2.5 py-1 text-xs font-semibold rounded bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300">
                                    Reschedule
                                </button>

                                @if ($reminder->status === 'scheduled')
                                    <form method="POST" action="{{ route('admin.reminders.cancel', $reminder) }}" class="inline" onsubmit="return prompt('Enter required audit reason to cancel this reminder:');">
                                        @csrf
                                        <input type="hidden" name="reason" value="Super Admin cancelled through console">
                                        <button type="submit" class="px-2.5 py-1 text-xs font-semibold rounded bg-rose-50 text-rose-700 hover:bg-rose-100">
                                            Cancel
                                        </button>
                                    </form>
                                @endif

                                <!-- Reschedule dropdown panel -->
                                <div x-show="rescheduleOpen" x-cloak class="mt-2 p-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-left space-y-2">
                                    <form method="POST" action="{{ route('admin.reminders.reschedule', $reminder) }}">
                                        @csrf
                                        <div class="text-[11px] font-bold text-slate-700 dark:text-slate-300">Reschedule Reminder (UTC)</div>
                                        <input type="datetime-local" name="due_at" required class="text-xs p-1.5 rounded border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 w-full mt-1">
                                        <input type="text" name="reason" placeholder="Required audit reason..." required minlength="5" class="text-xs p-1.5 rounded border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 w-full mt-1">
                                        <div class="flex justify-end gap-2 mt-2">
                                            <button type="button" @click="rescheduleOpen = false" class="text-xs text-slate-500">Close</button>
                                            <button type="submit" class="px-3 py-1 rounded text-xs font-bold bg-amber-500 text-slate-950">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-xs text-slate-500">
                                No reminders found matching query.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($reminders->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $reminders->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
