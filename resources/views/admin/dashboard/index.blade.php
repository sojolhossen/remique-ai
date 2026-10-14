@extends('layouts.admin')

@section('title', 'Operations Dashboard')
@section('page_title', 'Enterprise Operations Overview')
@section('page_description', 'Real-time telemetry, WhatsApp message flow, and scheduled reminder dispatch')

@section('content')
<div class="space-y-6">

    <!-- KPI Metric Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Customers Card -->
        <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Authorized Customers</span>
                <span class="p-2 rounded-lg bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-extrabold text-slate-900 dark:text-white">{{ $totalCustomers }}</span>
                <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">{{ $activeCustomers }} active</span>
            </div>
            <div class="mt-2 text-xs text-slate-500 dark:text-slate-400 flex items-center gap-2">
                <span>{{ $pendingCustomers }} pending OTP</span>
                <span>•</span>
                <span>{{ $suspendedCustomers }} suspended</span>
            </div>
        </div>

        <!-- Reminders Card -->
        <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Scheduled Reminders</span>
                <span class="p-2 rounded-lg bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-extrabold text-slate-900 dark:text-white">{{ $scheduledCount }}</span>
                <span class="text-xs font-medium text-amber-600 dark:text-amber-400">{{ $dueTodayCount }} due today</span>
            </div>
            <div class="mt-2 text-xs text-slate-500 dark:text-slate-400 flex items-center gap-2">
                <span>{{ $completedCount }} completed</span>
                <span>•</span>
                <span>{{ $cancelledCount }} cancelled</span>
            </div>
        </div>

        <!-- WhatsApp Delivery Rate -->
        <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Delivery Success (30d)</span>
                <span class="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.698c.99.539 1.83.826 2.796.826 3.183 0 5.768-2.586 5.768-5.766 0-3.18-2.585-5.613-5.768-5.613zm8.01 5.766c0 4.425-3.596 8.021-8.01 8.021-1.398 0-2.735-.365-3.904-1.026l-4.527 1.188 1.21-4.417c-.742-1.229-1.159-2.656-1.159-4.166 0-4.425 3.596-8.02 8.01-8.02s8.38 3.595 8.38 8.42z"/></svg>
                </span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-extrabold text-slate-900 dark:text-white">{{ $deliverySuccessRate }}%</span>
                <span class="text-xs font-medium text-slate-500">of {{ $totalAttempts }} attempts</span>
            </div>
            <div class="mt-2 text-xs text-slate-500 dark:text-slate-400 flex items-center gap-2">
                <span class="text-emerald-600 font-semibold">{{ $deliveredAttempts }} sent</span>
                <span>•</span>
                <span class="{{ $failedAttempts > 0 ? 'text-rose-500 font-semibold' : '' }}">{{ $failedAttempts }} failed</span>
            </div>
        </div>

        <!-- Memory Vault Storage -->
        <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Memory Vault Usage</span>
                <span class="p-2 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                </span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-extrabold text-slate-900 dark:text-white">{{ $vaultMetrics['formatted_size'] }}</span>
                <span class="text-xs font-medium text-slate-500">{{ $vaultMetrics['total_count'] }} files</span>
            </div>
            <div class="mt-2 text-xs text-slate-500 dark:text-slate-400 flex items-center gap-2">
                <span>{{ $vaultMetrics['images_count'] }} images</span>
                <span>•</span>
                <span>{{ $vaultMetrics['documents_count'] }} PDFs</span>
            </div>
        </div>
    </div>

    <!-- Secondary Row: WhatsApp Message Traffic & AI Engine Health -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- WhatsApp Traffic Panel -->
        <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">WhatsApp Cloud API Activity</h2>
                    <p class="text-xs text-slate-500">Inbound interactions vs outbound reminder alerts</p>
                </div>
                <a href="{{ route('admin.whatsapp.index') }}" class="text-xs font-semibold text-amber-600 hover:text-amber-700">View message logs &rarr;</a>
            </div>

            <div class="grid grid-cols-2 gap-4 mt-4">
                <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                    <span class="text-xs text-slate-500">Inbound Messages</span>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ $inboundMessagesCount }}</div>
                    <span class="text-[11px] text-slate-400">From authorized & guest senders</span>
                </div>
                <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                    <span class="text-xs text-slate-500">Outbound Dispatches</span>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ $outboundMessagesCount }}</div>
                    <span class="text-[11px] text-slate-400">Free-form alerts + templates</span>
                </div>
            </div>

            <div class="mt-4 p-3 rounded-lg bg-amber-500/10 border border-amber-500/20 text-xs text-amber-800 dark:text-amber-300 flex items-center justify-between">
                <span>Webhook endpoint: <code class="font-mono font-semibold">{{ url('/whatsapp/webhook') }}</code></span>
                <span class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-700 dark:text-amber-300 font-bold">HMAC Protected</span>
            </div>
        </div>

        <!-- AI Engine Panel -->
        <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">NVIDIA NIM Natural Language Engine</h2>
                    <p class="text-xs text-slate-500">Model: openai/gpt-oss-20b (Bengali, Banglish & English)</p>
                </div>
                <a href="{{ route('admin.ai.index') }}" class="text-xs font-semibold text-amber-600 hover:text-amber-700">Open playground &rarr;</a>
            </div>

            <div class="grid grid-cols-3 gap-3 mt-4 text-center">
                <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                    <span class="text-xs text-slate-500">Total Requests</span>
                    <div class="text-xl font-bold text-slate-900 dark:text-white mt-1">{{ $totalAiRequests }}</div>
                </div>
                <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                    <span class="text-xs text-slate-500">Avg Latency</span>
                    <div class="text-xl font-bold text-slate-900 dark:text-white mt-1">{{ $avgAiDuration }} ms</div>
                </div>
                <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                    <span class="text-xs text-slate-500">Error Count</span>
                    <div class="text-xl font-bold {{ $failedAiRequests > 0 ? 'text-rose-500' : 'text-slate-900 dark:text-white' }} mt-1">{{ $failedAiRequests }}</div>
                </div>
            </div>

            <div class="mt-4 text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between">
                <span>Multi-intent supported: Reminders, Scheduling, Vault File Retrieval</span>
                <span class="inline-flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 font-semibold">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Ready
                </span>
            </div>
        </div>
    </div>

    <!-- Third Row: Failed Reminders Queue & Audit Log Feed -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Failed Reminders Queue -->
        <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Failed Delivery Queue</h2>
                    <p class="text-xs text-slate-500">Reminders that encountered provider errors or rate limits</p>
                </div>
                <span class="px-2 py-0.5 rounded text-xs font-bold {{ $failedQueue->count() > 0 ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-800' }}">
                    {{ $failedQueue->count() }} Pending Review
                </span>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($failedQueue as $attempt)
                    <div class="p-3 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-xs font-semibold text-slate-900 dark:text-white truncate">
                                {{ $attempt->reminder->title ?? 'Untitled Reminder' }}
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                Customer: {{ $attempt->reminder->user->name ?? 'Unknown' }} ({{ $attempt->identity->phone_number ?? 'No Phone' }})
                            </div>
                            <div class="text-[11px] text-rose-600 dark:text-rose-400 font-mono mt-1">
                                [{{ $attempt->error_code }}] {{ Str::limit($attempt->error_message, 60) }}
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.whatsapp.retry', $attempt) }}">
                            @csrf
                            <button type="submit" class="px-2.5 py-1 text-xs font-semibold rounded bg-amber-500 hover:bg-amber-600 text-slate-950 transition-colors shrink-0 shadow-xs">
                                Retry Now
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="py-8 text-center text-xs text-slate-500 dark:text-slate-400">
                        <svg class="w-8 h-8 text-emerald-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        All reminder deliveries are healthy. No active failures.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Audit Log Feed -->
        <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Security & Audit Trail</h2>
                    <p class="text-xs text-slate-500">Immutable record of high-impact events</p>
                </div>
                <a href="{{ route('admin.audit.index') }}" class="text-xs font-semibold text-amber-600 hover:text-amber-700">View all logs &rarr;</a>
            </div>

            <div class="mt-4 space-y-2.5">
                @forelse ($recentAuditLogs as $log)
                    <div class="p-2.5 rounded-lg border border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs">
                        <div class="min-w-0 pr-3">
                            <span class="font-mono font-bold text-amber-600 dark:text-amber-400">{{ $log->action }}</span>
                            <span class="text-slate-500 ml-1.5">{{ Str::limit($log->reason ?? $log->target_type, 45) }}</span>
                            <div class="text-[10px] text-slate-400 mt-0.5">
                                By {{ $log->user->name ?? 'System' }} • {{ $log->created_at->diffForHumans() }}
                            </div>
                        </div>
                        <span class="font-mono text-[10px] text-slate-400 shrink-0">{{ $log->ip_address }}</span>
                    </div>
                @empty
                    <div class="py-8 text-center text-xs text-slate-500">No audit events recorded yet.</div>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection
