@extends('layouts.admin')

@section('title', 'AI Engine & Telemetry')
@section('page_title', 'AI Engine Settings & Telemetry')
@section('page_description', 'NVIDIA NIM endpoint configuration, model selection, live health check, and interactive playground')

@section('content')
<div class="space-y-6" x-data="{
    promptText: 'আগামীকাল সকাল ৯টায় ক্লায়েন্টকে কল করার কথা মনে করিয়ে দিও।',
    runningPlayground: false,
    aiResponse: null,
    
    // Model testing
    selectedModel: '{{ $config['model'] }}',
    testingModel: false,
    modelStatus: null,
    
    // Update active model
    saveModel(model) {
        const formData = new FormData();
        formData.append('nvidia_model', model || this.selectedModel);
        
        fetch('{{ route('admin.ai.update-settings') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Failed to update model: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(err => alert('Network error: ' + err));
    },

    // Check single model health
    testModel(model) {
        this.testingModel = true;
        this.modelStatus = null;

        fetch('{{ route('admin.ai.test-model') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ model: model || this.selectedModel })
        })
        .then(res => res.json())
        .then(data => {
            this.testingModel = false;
            this.modelStatus = data;
        })
        .catch(err => {
            this.testingModel = false;
            this.modelStatus = { success: false, message: 'Request failed: ' + err, latency_ms: 0 };
        });
    },

    // Run Playground
    runPlayground() {
        this.runningPlayground = true;
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
            this.runningPlayground = false;
            this.aiResponse = data;
        })
        .catch(err => {
            this.runningPlayground = false;
            this.aiResponse = {
                success: false,
                error_message: 'Error contacting playground: ' + err
            };
        });
    }
}">

    @if (session('status'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-sm font-semibold flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                <span>{{ session('status') }}</span>
            </div>
            <button @click="$el.parentElement.remove()" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline">Dismiss</button>
        </div>
    @endif

    <!-- AI Config & Diagnostics Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Configuration Card -->
        <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-4 flex flex-col justify-between">
            <div class="space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-500 font-bold text-xs">AI</span>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Active NVIDIA NIM Engine</h3>
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

                    <div class="space-y-1">
                        <span class="text-slate-400 font-bold uppercase text-[10px]">Model Selection</span>
                        <select x-model="selectedModel" class="w-full text-xs font-semibold py-1.5 px-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500">
                            @foreach ($config['available_models'] as $mKey => $mLabel)
                                <option value="{{ $mKey }}" @selected($config['model'] === $mKey)>{{ $mLabel }}</option>
                            @endforeach
                        </select>
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

                <!-- Health Check Result Display -->
                <template x-if="modelStatus">
                    <div class="p-3 rounded-lg text-xs font-medium border"
                         :class="modelStatus.success ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-300 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300' : 'bg-rose-50 dark:bg-rose-950/40 border-rose-300 dark:border-rose-800 text-rose-800 dark:text-rose-300'">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-bold flex items-center gap-1.5">
                                <span x-text="modelStatus.success ? '✓' : '✗'"></span>
                                <span x-text="modelStatus.success ? 'Model Operational' : 'Connection Error'"></span>
                            </span>
                            <span class="font-mono text-[11px]" x-show="modelStatus.latency_ms > 0" x-text="modelStatus.latency_ms + ' ms'"></span>
                        </div>
                        <p class="text-[11px]" x-text="modelStatus.message"></p>
                    </div>
                </template>
            </div>

            <!-- Action buttons -->
            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center gap-2">
                <button type="button" 
                        @click="testModel(selectedModel)"
                        :disabled="testingModel"
                        class="flex-1 py-2 px-3 rounded-lg text-xs font-bold border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-200 transition-colors flex items-center justify-center gap-2">
                    <svg x-show="testingModel" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    <span x-text="testingModel ? 'Testing...' : 'Check Model Status'"></span>
                </button>
                <button type="button"
                        @click="saveModel(selectedModel)"
                        class="py-2 px-3 rounded-lg text-xs font-bold bg-amber-500 hover:bg-amber-400 text-slate-950 transition-colors shadow-xs">
                    Save Model
                </button>
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
                <p class="text-xs text-slate-500">Test how Remi interprets natural language in Bengali, Banglish, and English using NVIDIA NIM.</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="promptText = 'আগামীকাল সকাল ৯টায় ক্লায়েন্টকে কল করার কথা মনে করিয়ে দিও।'" class="text-[11px] px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">Bengali</button>
                <button type="button" @click="promptText = 'Kal shokal 8 tay meeting ache, remind korio.'" class="text-[11px] px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">Banglish</button>
                <button type="button" @click="promptText = 'Remind me to pay the internet bill on the 5th of every month.'" class="text-[11px] px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">English</button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="space-y-3">
                <textarea x-model="promptText" rows="6" class="w-full text-xs p-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500 font-sans"></textarea>
                <button type="button" @click="runPlayground()" :disabled="runningPlayground" class="w-full py-2.5 rounded-lg text-xs font-bold text-slate-950 bg-amber-500 hover:bg-amber-400 disabled:opacity-50 transition-colors shadow-xs flex items-center justify-center gap-2">
                    <svg x-show="runningPlayground" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    <span x-text="runningPlayground ? 'Parsing intent via NVIDIA NIM...' : 'Parse Input with NVIDIA NIM'"></span>
                </button>
            </div>

            <!-- Parsed Output -->
            <div class="p-4 rounded-lg bg-slate-950 text-slate-100 border border-slate-800 overflow-x-auto text-xs font-mono h-64 overflow-y-auto">
                <template x-if="runningPlayground">
                    <div class="text-amber-400 flex items-center gap-2 py-12 justify-center">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        Connecting to NVIDIA NIM...
                    </div>
                </template>
                <template x-if="!runningPlayground && aiResponse">
                    <div>
                        <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-800 text-[11px] text-slate-400">
                            <span>Provider: <strong class="text-amber-400" x-text="aiResponse.provider || 'NVIDIA NIM'"></strong></span>
                            <span>Model: <strong class="text-indigo-400" x-text="aiResponse.model || selectedModel"></strong></span>
                            <span>Latency: <strong class="text-emerald-400" x-text="(aiResponse.duration_ms || 0) + ' ms'"></strong></span>
                        </div>
                        <pre x-text="JSON.stringify(aiResponse, null, 2)"></pre>
                    </div>
                </template>
                <template x-if="!runningPlayground && !aiResponse">
                    <div class="text-slate-500 py-16 text-center">Click "Parse Input with NVIDIA NIM" to inspect raw structured output.</div>
                </template>
            </div>
        </div>
    </div>

</div>
@endsection
