@extends('layouts.admin')

@section('title', 'AI Engine & Diagnostics')
@section('page_title', 'AI Engine Settings & Telemetry')
@section('page_description', 'NVIDIA NIM endpoint configuration, latency telemetry, and interactive test playground')

@section('content')
<div class="space-y-6" x-data="{
    promptText: 'আগামীকাল সকাল ৯টায় ক্লায়েন্টকে কল করার কথা মনে করিয়ে দিও।',
    running: false,
    aiResponse: null,
    runPlayground() {
        this.running = true;
        this.aiResponse = null;
        fetch('{{ route('admin.ai.test-playground') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                prompt: this.promptText,
                timezone: 'Asia/Dhaka'
            })
        })
        .then(res => res.json())
        .then(data => {
            this.running = false;
            this.aiResponse = data;
        })
        .catch(err => {
            this.running = false;
            this.aiResponse = {
                success: false,
                error_message: 'Error contacting playground: ' + err
            };
        });
    }
}">

    <!-- AI Config & Diagnostics Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Configuration Card -->
        <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 rounded-lg bg-indigo-500/10 text-indigo-500 font-bold text-xs">AI</span>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Active Model Configuration</h3>
                </div>
                @if ($config['is_configured'])
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400">ACTIVE</span>
                @else
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400">KEY UNSET</span>
                @endif
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <span class="text-slate-400 font-bold uppercase text-[10px]">Provider</span>
                    <div class="font-semibold text-slate-900 dark:text-white">{{ $config['provider'] }}</div>
                </div>

                <div>
                    <span class="text-slate-400 font-bold uppercase text-[10px]">Model Identifier</span>
                    <div class="font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ $config['model'] }}</div>
                </div>

                <div>
                    <span class="text-slate-400 font-bold uppercase text-[10px]">Base URL</span>
                    <div class="font-mono text-slate-700 dark:text-slate-300 break-all">{{ $config['base_url'] }}</div>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-1">
                    <div>
                        <span class="text-slate-400 font-bold uppercase text-[10px]">Timeout</span>
                        <div class="font-semibold text-slate-900 dark:text-white">{{ $config['timeout'] }} seconds</div>
                    </div>
                    <div>
                        <span class="text-slate-400 font-bold uppercase text-[10px]">Max Retries</span>
                        <div class="font-semibold text-slate-900 dark:text-white">{{ $config['max_retries'] }}</div>
                    </div>
                </div>

                <div>
                    <span class="text-slate-400 font-bold uppercase text-[10px]">API Key Status</span>
                    <div class="font-mono text-slate-700 dark:text-slate-300">{{ $config['api_key_masked'] }}</div>
                </div>
            </div>
        </div>

        <!-- Metrics & Telemetry Card -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Diagnostic Telemetry</h3>
                <span class="text-xs text-slate-400">Aggregated from {{ $totalLogs }} logged interactions</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] text-slate-400 uppercase font-bold">Total Invocations</span>
                    <div class="text-xl font-bold text-slate-900 dark:text-white mt-1">{{ $totalLogs }}</div>
                </div>

                <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] text-slate-400 uppercase font-bold">Success Rate</span>
                    <div class="text-xl font-bold text-emerald-600 mt-1">
                        {{ $totalLogs > 0 ? round(($successfulLogs / $totalLogs) * 100, 1) : 100 }}%
                    </div>
                </div>

                <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] text-slate-400 uppercase font-bold">Average Latency</span>
                    <div class="text-xl font-bold text-slate-900 dark:text-white mt-1">{{ $avgDuration }} ms</div>
                </div>

                <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] text-slate-400 uppercase font-bold">Tokens Processed</span>
                    <div class="text-xl font-bold text-slate-900 dark:text-white mt-1">{{ number_format($totalTokens) }}</div>
                </div>
            </div>

            <!-- Error Breakdown -->
            <div>
                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Failure Categories Distribution</span>
                <div class="mt-2 space-y-1 text-xs">
                    @forelse ($errorBreakdown as $category => $count)
                        <div class="flex items-center justify-between p-2 rounded bg-slate-50 dark:bg-slate-800">
                            <span class="font-mono text-rose-600 dark:text-rose-400">{{ $category }}</span>
                            <span class="font-bold">{{ $count }} incident(s)</span>
                        </div>
                    @empty
                        <div class="text-slate-400 text-xs italic py-2">No errors recorded. All AI calls succeeded.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Interactive AI Playground -->
    <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Multilingual Intent Extraction Playground</h3>
                <p class="text-xs text-slate-500">Test how Remique interprets natural language in Bengali, Banglish, and English without saving reminder records.</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="promptText = 'আগামীকাল সকাল ৯টায় ক্লায়েন্টকে কল করার কথা মনে করিয়ে দিও।'" class="text-[11px] px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">Bengali</button>
                <button type="button" @click="promptText = 'Kal shokal 8 tay meeting ache, remind korio.'" class="text-[11px] px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">Banglish</button>
                <button type="button" @click="promptText = 'Remind me to pay the internet bill on the 5th of every month.'" class="text-[11px] px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">English</button>
                <button type="button" @click="promptText = 'গত সপ্তাহে পাঠানো X89 earbuds-এর ছবিটা আমাকে দাও।'" class="text-[11px] px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">Vault Search</button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="space-y-3">
                <textarea x-model="promptText" rows="6" class="w-full text-xs p-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500 font-sans"></textarea>
                <button type="button" @click="runPlayground()" :disabled="running" class="w-full py-2.5 rounded-lg text-xs font-bold text-slate-950 bg-amber-500 hover:bg-amber-400 disabled:opacity-50 transition-colors shadow-xs flex items-center justify-center gap-2">
                    <svg x-show="running" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    <span x-text="running ? 'Parsing intent via NVIDIA NIM...' : 'Parse Input with NVIDIA NIM'"></span>
                </button>
            </div>

            <!-- Parsed Output -->
            <div class="p-4 rounded-lg bg-slate-950 text-slate-100 border border-slate-800 overflow-x-auto text-xs font-mono h-64 overflow-y-auto">
                <template x-if="running">
                    <div class="text-amber-400 flex items-center gap-2 py-12 justify-center">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        Connecting to NVIDIA NIM...
                    </div>
                </template>
                <template x-if="!running && aiResponse">
                    <pre x-text="JSON.stringify(aiResponse, null, 2)"></pre>
                </template>
                <template x-if="!running && !aiResponse">
                    <div class="text-slate-500 py-16 text-center">Click "Parse Input with NVIDIA NIM" to inspect raw structured output.</div>
                </template>
            </div>
        </div>
    </div>

</div>
@endsection
