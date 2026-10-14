@extends('layouts.admin')

@section('title', 'Security & Audit Logs')
@section('page_title', 'Security & Administrative Audit Logs')
@section('page_description', 'Immutable, append-only security record tracking high-impact actions and overrides')

@section('content')
<div class="space-y-6">

    <!-- Filters & Search -->
    <div class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.audit.index') }}" class="flex flex-1 items-center gap-3 w-full sm:w-auto">
            <div class="relative flex-1">
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Search action, reason, target, or IP..."
                       class="w-full pl-9 pr-4 py-2 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>

            <select name="action" class="text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
                <option value="">All Action Types</option>
                @foreach ($distinctActions as $act)
                    <option value="{{ $act }}" {{ $action === $act ? 'selected' : '' }}>{{ $act }}</option>
                @endforeach
            </select>

            <button type="submit" class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-slate-800 hover:bg-slate-700 text-white dark:bg-slate-700 transition-colors">
                Filter
            </button>
            @if (!empty($search) || !empty($action))
                <a href="{{ route('admin.audit.index') }}" class="text-xs text-slate-500 hover:underline">Reset</a>
            @endif
        </form>
    </div>

    <!-- Audit Logs Table -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 uppercase tracking-wider text-[10px] text-slate-500 font-bold">
                    <tr>
                        <th class="py-3 px-4">Timestamp</th>
                        <th class="py-3 px-4">Actor</th>
                        <th class="py-3 px-4">Action</th>
                        <th class="py-3 px-4">Target</th>
                        <th class="py-3 px-4">Reason / Notes</th>
                        <th class="py-3 px-4">IP Address</th>
                        <th class="py-3 px-4">Metadata</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-3 px-4 font-mono text-slate-500 text-[11px] whitespace-nowrap">
                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white whitespace-nowrap">
                                {{ $log->user->name ?? 'System' }}
                            </td>
                            <td class="py-3 px-4 font-mono font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap">
                                {{ $log->action }}
                            </td>
                            <td class="py-3 px-4 text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                @if ($log->target_type)
                                    <span class="font-mono text-[11px] bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">
                                        {{ $log->target_type }}{{ $log->target_id ? " #{$log->target_id}" : '' }}
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-3 px-4 max-w-xs text-slate-700 dark:text-slate-300">
                                {{ $log->reason ?? '—' }}
                            </td>
                            <td class="py-3 px-4 font-mono text-[11px] text-slate-400 whitespace-nowrap">
                                {{ $log->ip_address }}
                            </td>
                            <td class="py-3 px-4 font-mono text-[10px] text-slate-500 max-w-xs truncate">
                                @if ($log->metadata)
                                    {{ json_encode($log->metadata) }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-xs text-slate-500">
                                No audit log records found matching your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
