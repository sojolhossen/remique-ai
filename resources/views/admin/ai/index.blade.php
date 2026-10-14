@extends('layouts.admin')

@section('title', 'AI Engine & Model Management')
@section('page_title', 'AI Engine Settings & Telemetry')
@section('page_description', 'Configure AI providers (Google Gemini & NVIDIA NIM), switch active models, test live connectivity, and monitor latency.')

@section('content')
<div class="space-y-6" x-data="{
    activeProvider: '{{ $activeProvider }}',
    promptText: 'আগামীকাল সকাল ৯টায় ক্লায়েন্টকে কল করার কথা মনে করিয়ে দিও।',
    playgroundProvider: '{{ $activeProvider }}',
    runningPlayground: false,
    aiResponse: null,
    
    // Model testing states
    testingGemini: false,
    geminiStatus: null,
    geminiSelectedModel: '{{ $providers['gemini']['current_model'] }}',
    
    testingNvidia: false,
    nvidiaStatus: null,
    nvidiaSelectedModel: '{{ $providers['nvidia_nim']['current_model'] }}',
    
    // Switch active provider
    switchProvider(provider, model) {
        const formData = new FormData();
        formData.append('active_provider', provider);
        if (provider === 'gemini') {
            formData.append('gemini_model', model || this.geminiSelectedModel);
        } else {
            formData.append('nvidia_model', model || this.nvidiaSelectedModel);
        }
        
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
                this.activeProvider = provider;
                this.playgroundProvider = provider;
                window.location.reload();
            } else {
                alert('Failed to update provider: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(err => alert('Network error: ' + err));
    },

    // Check single model health
    testModel(provider, model) {
        if (provider === 'gemini') {
            this.testingGemini = true;
            this.geminiStatus = null;
        } else {
            this.testingNvidia = true;
            this.nvidiaStatus = null;
        }

        fetch('{{ route('admin.ai.test-model') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ provider: provider, model: model })
        })
        .then(res => res.json())
        .then(data => {
            if (provider === 'gemini') {
                this.testingGemini = false;
                this.geminiStatus = data;
            } else {
                this.testingNvidia = false;
                this.nvidiaStatus = data;
            }
        })
        .catch(err => {
            const errObj = { success: false, message: 'Request failed: ' + err, latency_ms: 0 };
            if (provider === 'gemini') {
                this.testingGemini = false;
                this.geminiStatus = errObj;
            } else {
                this.testingNvidia = false;
                this.nvidiaStatus = errObj;
            }
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
                provider: this.playgroundProvider,
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

    <!-- Provider Selection & Control Grid -->
    <div>
        <div class="flex items-center justify-between mb-3">
            <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-white">AI Engine Providers & Model Control</h2>
                <p class="text-xs text-slate-500">Select which AI model handles live reminders and check real-time connectivity status.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-500">Currently Active:</span>
                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-500 text-slate-950 uppercase tracking-wider" x-text="activeProvider === 'gemini' ? 'Google Gemini' : 'NVIDIA NIM'"></span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Google Gemini Card -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 border transition-all duration-200 shadow-xs flex flex-col justify-between"
                 :class="activeProvider === 'gemini' ? 'border-amber-500 ring-2 ring-amber-500/20 shadow-md' : 'border-slate-200 dark:border-slate-800 opacity-90 hover:opacity-100'">
                
                <div class="space-y-4">
                    <!-- Card Header -->
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-500 flex items-center justify-center font-black text-lg">
                                G
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Google Gemini</h3>
                                    <span class="text-[10px] px-1.5 py-0.5 rounded font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">RECOMMENDED</span>
                                </div>
                                <p class="text-xs text-slate-500">Native JSON schema mode & ultra-low latency (<1.5s)</p>
                            </div>
                        </div>

                        <div>
                            <template x-if="activeProvider === 'gemini'">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-400">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    ACTIVE
                                </span>
                            </template>
                            <template x-if="activeProvider !== 'gemini'">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                    STANDBY / OFF
                                </span>
                            </template>
                        </div>
                    </div>

                    <!-- Model Selector -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Select Model</label>
                        <select x-model="geminiSelectedModel" class="w-full text-xs font-medium py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500">
                            @foreach ($providers['gemini']['models'] as $modelKey => $modelLabel)
                                <option value="{{ $modelKey }}">{{ $modelLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Details -->
                    <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 space-y-2 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">API Key Status:</span>
                            <span class="font-mono text-slate-700 dark:text-slate-300">{{ $providers['gemini']['api_key_masked'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Default Model:</span>
                            <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400" x-text="geminiSelectedModel"></span>
                        </div>
                    </div>

                    <!-- Health Check Result Display -->
                    <template x-if="geminiStatus">
                        <div class="p-3 rounded-lg text-xs font-medium border"
                             :class="geminiStatus.success ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-300 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300' : 'bg-rose-50 dark:bg-rose-950/40 border-rose-300 dark:border-rose-800 text-rose-800 dark:text-rose-300'">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-bold flex items-center gap-1.5">
                                    <span x-text="geminiStatus.success ? '✓' : '✗'"></span>
                                    <span x-text="geminiStatus.success ? 'Model Operational' : 'Connection Error'"></span>
                                </span>
                                <span class="font-mono text-[11px]" x-show="geminiStatus.latency_ms > 0" x-text="geminiStatus.latency_ms + ' ms'"></span>
                            </div>
                            <p class="text-[11px]" x-text="geminiStatus.message"></p>
                        </div>
                    </template>
                </div>

                <!-- Action Buttons -->
                <div class="pt-4 mt-4 border-t border-slate-100 dark:border-slate-800 flex items-center gap-2">
                    <button type="button" 
                            @click="testModel('gemini', geminiSelectedModel)"
                            :disabled="testingGemini"
                            class="flex-1 py-2 px-3 rounded-lg text-xs font-bold border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-200 transition-colors flex items-center justify-center gap-2">
                        <svg x-show="testingGemini" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        <span x-text="testingGemini ? 'Testing Gemini...' : 'Check Model Status'"></span>
                    </button>

                    <template x-if="activeProvider !== 'gemini'">
                        <button type="button"
                                @click="switchProvider('gemini', geminiSelectedModel)"
                                class="flex-1 py-2 px-3 rounded-lg text-xs font-bold bg-amber-500 hover:bg-amber-400 text-slate-950 transition-colors shadow-xs flex items-center justify-center gap-1.5">
                            <span>Turn ON & Activate</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                        </button>
                    </template>
                    <template x-if="activeProvider === 'gemini'">
                        <button type="button"
                                @click="switchProvider('gemini', geminiSelectedModel)"
                                class="flex-1 py-2 px-3 rounded-lg text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition-colors shadow-xs flex items-center justify-center gap-1.5">
                            <span>Update Active Model</span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- NVIDIA NIM Card -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 border transition-all duration-200 shadow-xs flex flex-col justify-between"
                 :class="activeProvider === 'nvidia_nim' ? 'border-amber-500 ring-2 ring-amber-500/20 shadow-md' : 'border-slate-200 dark:border-slate-800 opacity-90 hover:opacity-100'">
                
                <div class="space-y-4">
                    <!-- Card Header -->
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center font-black text-lg">
                                N
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">NVIDIA NIM</h3>
                                <p class="text-xs text-slate-500">OpenAI-compatible inference on enterprise GPUs</p>
                            </div>
                        </div>

                        <div>
                            <template x-if="activeProvider === 'nvidia_nim'">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-400">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    ACTIVE
                                </span>
                            </template>
                            <template x-if="activeProvider !== 'nvidia_nim'">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                    STANDBY / OFF
                                </span>
                            </template>
                        </div>
                    </div>

                    <!-- Model Selector -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Select Model</label>
                        <select x-model="nvidiaSelectedModel" class="w-full text-xs font-medium py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500">
                            @foreach ($providers['nvidia_nim']['models'] as $modelKey => $modelLabel)
                                <option value="{{ $modelKey }}">{{ $modelLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Details -->
                    <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 space-y-2 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">API Key Status:</span>
                            <span class="font-mono text-slate-700 dark:text-slate-300">{{ $providers['nvidia_nim']['api_key_masked'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Default Model:</span>
                            <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400" x-text="nvidiaSelectedModel"></span>
                        </div>
                    </div>

                    <!-- Health Check Result Display -->
                    <template x-if="nvidiaStatus">
                        <div class="p-3 rounded-lg text-xs font-medium border"
                             :class="nvidiaStatus.success ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-300 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300' : 'bg-rose-50 dark:bg-rose-950/40 border-rose-300 dark:border-rose-800 text-rose-800 dark:text-rose-300'">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-bold flex items-center gap-1.5">
                                    <span x-text="nvidiaStatus.success ? '✓' : '✗'"></span>
                                    <span x-text="nvidiaStatus.success ? 'Model Operational' : 'Connection Error'"></span>
                                </span>
                                <span class="font-mono text-[11px]" x-show="nvidiaStatus.latency_ms > 0" x-text="nvidiaStatus.latency_ms + ' ms'"></span>
                            </div>
                            <p class="text-[11px]" x-text="nvidiaStatus.message"></p>
                        </div>
                    </template>
                </div>

                <!-- Action Buttons -->
                <div class="pt-4 mt-4 border-t border-slate-100 dark:border-slate-800 flex items-center gap-2">
                    <button type="button" 
                            @click="testModel('nvidia_nim', nvidiaSelectedModel)"
                            :disabled="testingNvidia"
                            class="flex-1 py-2 px-3 rounded-lg text-xs font-bold border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-200 transition-colors flex items-center justify-center gap-2">
                        <svg x-show="testingNvidia" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        <span x-text="testingNvidia ? 'Testing NVIDIA...' : 'Check Model Status'"></span>
                    </button>

                    <template x-if="activeProvider !== 'nvidia_nim'">
                        <button type="button"
                                @click="switchProvider('nvidia_nim', nvidiaSelectedModel)"
                                class="flex-1 py-2 px-3 rounded-lg text-xs font-bold bg-amber-500 hover:bg-amber-400 text-slate-950 transition-colors shadow-xs flex items-center justify-center gap-1.5">
                            <span>Turn ON & Activate</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                        </button>
                    </template>
                    <template x-if="activeProvider === 'nvidia_nim'">
                        <button type="button"
                                @click="switchProvider('nvidia_nim', nvidiaSelectedModel)"
                                class="flex-1 py-2 px-3 rounded-lg text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition-colors shadow-xs flex items-center justify-center gap-1.5">
                            <span>Update Active Model</span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Diagnostic Telemetry Row -->
    <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
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

        @if (!empty($errorBreakdown))
            <div>
                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Failure Categories Distribution</span>
                <div class="mt-2 space-y-1 text-xs">
                    @foreach ($errorBreakdown as $category => $count)
                        <div class="flex items-center justify-between p-2 rounded bg-slate-50 dark:bg-slate-800">
                            <span class="font-mono text-rose-600 dark:text-rose-400">{{ $category }}</span>
                            <span class="font-bold">{{ $count }} incident(s)</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Interactive AI Playground -->
    <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Multilingual Intent Extraction Playground</h3>
                <p class="text-xs text-slate-500">Test how Remi interprets natural language in Bengali, Banglish, and English with your selected model.</p>
            </div>
            
            <div class="flex items-center gap-2">
                <select x-model="playgroundProvider" class="text-xs font-semibold py-1 px-2 rounded border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    <option value="gemini">Test with Google Gemini</option>
                    <option value="nvidia_nim">Test with NVIDIA NIM</option>
                </select>
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
                    <span x-text="runningPlayground ? 'Parsing with ' + (playgroundProvider === 'gemini' ? 'Gemini' : 'NVIDIA') + '...' : 'Parse Input with ' + (playgroundProvider === 'gemini' ? 'Google Gemini' : 'NVIDIA NIM')"></span>
                </button>
            </div>

            <!-- Parsed Output -->
            <div class="p-4 rounded-lg bg-slate-950 text-slate-100 border border-slate-800 overflow-x-auto text-xs font-mono h-64 overflow-y-auto">
                <template x-if="runningPlayground">
                    <div class="text-amber-400 flex items-center gap-2 py-12 justify-center">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        Connecting to <span class="capitalize" x-text="playgroundProvider"></span>...
                    </div>
                </template>
                <template x-if="!runningPlayground && aiResponse">
                    <div>
                        <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-800 text-[11px] text-slate-400">
                            <span>Provider: <strong class="text-amber-400" x-text="aiResponse.provider || playgroundProvider"></strong></span>
                            <span>Model: <strong class="text-indigo-400" x-text="aiResponse.model || 'active'"></strong></span>
                            <span>Latency: <strong class="text-emerald-400" x-text="(aiResponse.duration_ms || 0) + ' ms'"></strong></span>
                        </div>
                        <pre x-text="JSON.stringify(aiResponse, null, 2)"></pre>
                    </div>
                </template>
                <template x-if="!runningPlayground && !aiResponse">
                    <div class="text-slate-500 py-16 text-center">Click "Parse Input" to inspect live structured AI output.</div>
                </template>
            </div>
        </div>
    </div>

</div>
@endsection
