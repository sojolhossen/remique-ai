@extends('layouts.admin')

@section('title', 'System Health & Maintenance')
@section('page_title', 'System Settings & Server Environment')
@section('page_description', 'Operational host details, database version, and cache management')

@section('content')
<div class="space-y-6">

    <!-- Host Information Grid -->
    <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white pb-3 border-b border-slate-100 dark:border-slate-800">
            Runtime Environment
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-xs">
            <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                <span class="text-slate-400 uppercase text-[10px] font-bold">Application Name</span>
                <div class="font-bold text-slate-800 dark:text-slate-200 mt-1">{{ $systemInfo['app_name'] }}</div>
            </div>

            <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                <span class="text-slate-400 uppercase text-[10px] font-bold">Environment / Debug</span>
                <div class="font-bold text-slate-800 dark:text-slate-200 mt-1">{{ $systemInfo['app_env'] }} (Debug: {{ $systemInfo['app_debug'] }})</div>
            </div>

            <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                <span class="text-slate-400 uppercase text-[10px] font-bold">PHP Version</span>
                <div class="font-mono font-bold text-slate-800 dark:text-slate-200 mt-1">{{ $systemInfo['php_version'] }}</div>
            </div>

            <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                <span class="text-slate-400 uppercase text-[10px] font-bold">MySQL Server Version</span>
                <div class="font-mono font-bold text-slate-800 dark:text-slate-200 mt-1">{{ $systemInfo['mysql_version'] }}</div>
            </div>

            <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                <span class="text-slate-400 uppercase text-[10px] font-bold">Default Timezone</span>
                <div class="font-bold text-slate-800 dark:text-slate-200 mt-1">{{ $systemInfo['default_timezone'] }}</div>
            </div>

            <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                <span class="text-slate-400 uppercase text-[10px] font-bold">Filesystem Storage Disk</span>
                <div class="font-mono font-bold text-slate-800 dark:text-slate-200 mt-1">{{ $systemInfo['storage_disk'] }}</div>
            </div>
        </div>
    </div>

    <!-- Cron & Scheduler Configuration Instructions -->
    <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-3">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white">cPanel & Shared-Hosting Cron Setup</h3>
        <p class="text-xs text-slate-500">
            Remique uses the native Laravel Scheduler with atomic MySQL locks. Add this entry to your hosting cPanel Cron Jobs to execute every minute:
        </p>

        <div class="p-3.5 rounded-lg bg-slate-950 font-mono text-xs text-amber-400 select-all border border-slate-800">
            * * * * * cd {{ base_path() }} && php artisan schedule:run >> /dev/null 2>&1
        </div>
        <p class="text-[11px] text-slate-400">
            If your cPanel provider restricts cron frequency to every 5 minutes, configure <code class="font-mono">php artisan remi:send-due-reminders --limit=100</code> to catch up overdue intervals safely.
        </p>
    </div>

    <!-- Administrative Maintenance Controls -->
    <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Maintenance Operations</h3>

        <div class="flex items-center justify-between p-4 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
            <div>
                <div class="text-xs font-bold text-slate-800 dark:text-slate-200">Flush Application Cache</div>
                <div class="text-[11px] text-slate-500">Clears compiled application and configuration cache. Will be recorded in audit log.</div>
            </div>
            <form method="POST" action="{{ route('admin.system.clear-cache') }}">
                @csrf
                <button type="submit" class="px-3.5 py-2 text-xs font-bold rounded-lg bg-slate-800 hover:bg-slate-700 text-white dark:bg-slate-700 shadow-xs">
                    Clear Cache
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
