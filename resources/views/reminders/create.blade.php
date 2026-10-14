@extends('layouts.app')

@section('title', 'Create Reminder')
@section('page_title', 'Create Reminder')

@section('content')
<div x-data="{
    activeTab: '{{ $aiConfigured ? 'ai' : 'manual' }}',
    prompt: '',
    loading: false,
    errorMessage: '',
    interpreted: null,
    previewTitle: '',
    previewDescription: '',
    previewDate: '{{ $defaultDate }}',
    previewTime: '{{ $defaultTime }}',
    previewPriority: 'medium',
    previewRecurrence: 'none',

    setExample(text) {
        this.prompt = text;
        this.interpret();
    },

    async interpret() {
        if (!this.prompt.trim()) return;
        this.loading = true;
        this.errorMessage = '';
        this.interpreted = null;

        try {
            const res = await fetch('{{ route('reminders.ai-interpret') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({ prompt: this.prompt })
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                this.errorMessage = data.error_message || 'Could not interpret reminder. Please try again or use manual creation.';
                return;
            }

            this.interpreted = data;
            this.previewTitle = data.title || '';
            this.previewDescription = data.description || '';
            this.previewDate = data.date || '{{ $defaultDate }}';
            this.previewTime = (data.time ? data.time.substring(0, 5) : '{{ $defaultTime }}');
            this.previewPriority = data.priority || 'medium';
            this.previewRecurrence = data.recurrence || 'none';

        } catch (e) {
            this.errorMessage = 'Network error contacting Remi AI. Please use manual reminder entry below.';
        } finally {
            this.loading = false;
        }
    }
}" class="max-w-4xl mx-auto space-y-6">

    <!-- Tab Selector -->
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
        <div class="flex items-center gap-2">
            <button @click="activeTab = 'ai'"
                    :class="activeTab === 'ai' ? 'bg-indigo-600 text-white font-semibold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-slate-800'"
                    type="button"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <span>AI Natural Language</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded bg-white/20">Bengali / Banglish / English</span>
            </button>

            <button @click="activeTab = 'manual'"
                    :class="activeTab === 'manual' ? 'bg-indigo-600 text-white font-semibold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-slate-800'"
                    type="button"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                <span>Manual Form</span>
            </button>
        </div>

        <span class="text-xs text-slate-500 dark:text-slate-400 hidden sm:inline">
            Timezone: <strong class="text-slate-700 dark:text-slate-300">{{ $user->timezone ?: 'UTC' }}</strong>
        </span>
    </div>

    <!-- TAB 1: AI Natural Language Creator -->
    <div x-show="activeTab === 'ai'" x-cloak class="space-y-6">

        @if (!$aiConfigured)
            <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-200 text-xs">
                <div class="font-bold flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    NVIDIA NIM API Key Notice
                </div>
                <p class="mt-1">
                    The NVIDIA NIM API key is not yet set in <code class="font-mono bg-amber-100 dark:bg-amber-900/60 px-1 py-0.5 rounded">.env</code>. You can configure <code class="font-mono">NVIDIA_NIM_API_KEY</code>, or use the <strong>Manual Form</strong> tab right now to create reminders immediately.
                </p>
            </div>
        @endif

        <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
            <div>
                <label for="ai-prompt-input" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    Describe your reminder in natural words:
                </label>
                <div class="relative">
                    <textarea id="ai-prompt-input"
                              x-model="prompt"
                              @keydown.enter.prevent="if (!event.shiftKey) interpret()"
                              rows="3"
                              placeholder="e.g. কাল সকাল ৯টায় আমাকে ডাক্তার দেখানোর কথা মনে করিয়ে দিও, or 'Remind me tomorrow at 9 AM to call the client'"
                              class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm leading-relaxed transition-all"></textarea>
                </div>
            </div>

            <!-- Example chips for 1-click test -->
            <div>
                <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Try quick examples (click to test):</div>
                <div class="flex flex-wrap gap-2">
                    <button type="button"
                            @click="setExample('আগামীকাল সকাল ৯টায় আমাকে ডাক্তার দেখানোর কথা মনে করিয়ে দিও।')"
                            class="px-2.5 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-indigo-50 hover:text-indigo-600 dark:hover:bg-indigo-950/60 dark:hover:text-indigo-400 text-slate-700 dark:text-slate-300 text-xs transition-colors border border-slate-200 dark:border-slate-700">
                        🇧🇩 <strong>Bengali:</strong> আগামীকাল সকাল ৯টায় আমাকে ডাক্তার দেখানোর কথা মনে করিয়ে দিও।
                    </button>
                    <button type="button"
                            @click="setExample('kal shokal 9 tay amake doctor er kotha mone koriye dio.')"
                            class="px-2.5 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-indigo-50 hover:text-indigo-600 dark:hover:bg-indigo-950/60 dark:hover:text-indigo-400 text-slate-700 dark:text-slate-300 text-xs transition-colors border border-slate-200 dark:border-slate-700">
                        🇧🇩 <strong>Banglish:</strong> kal shokal 9 tay amake doctor er kotha mone koriye dio.
                    </button>
                    <button type="button"
                            @click="setExample('Remind me tomorrow at 9 AM to call the client.')"
                            class="px-2.5 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-indigo-50 hover:text-indigo-600 dark:hover:bg-indigo-950/60 dark:hover:text-indigo-400 text-slate-700 dark:text-slate-300 text-xs transition-colors border border-slate-200 dark:border-slate-700">
                        🇬🇧 <strong>English:</strong> Remind me tomorrow at 9 AM to call the client.
                    </button>
                </div>
            </div>

            <!-- Interpret Button -->
            <div class="flex items-center justify-between pt-2">
                <span class="text-xs text-slate-400">Press Enter or click the button</span>
                <button type="button"
                        @click="interpret()"
                        :disabled="loading || !prompt.trim()"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-semibold text-xs sm:text-sm shadow-md shadow-indigo-600/20 transition-all cursor-pointer">
                    <svg x-show="loading" x-cloak class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    <span x-text="loading ? 'Remi AI is interpreting...' : 'Interpret with Remi AI'"></span>
                </button>
            </div>
        </div>

        <!-- Error Alert -->
        <div x-show="errorMessage" x-cloak class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs flex items-start gap-2">
            <svg class="w-4 h-4 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span x-text="errorMessage"></span>
        </div>

        <!-- Structured Interpretation Preview & Confirmation Card -->
        <div x-show="interpreted" x-cloak class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-indigo-200 dark:border-indigo-900/60 shadow-lg shadow-indigo-500/5 space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm">Interpretation Preview</h3>
                    <span class="text-[10px] font-semibold uppercase px-2 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300"
                          x-text="'Detected: ' + (interpreted?.detected_language || 'english')"></span>
                </div>
                <span class="text-xs text-slate-400" x-text="'Model: openai/gpt-oss-20b'"></span>
            </div>

            <!-- Ambiguity / Missing Info Banner if needed -->
            <div x-show="interpreted?.has_missing_info" x-cloak class="p-3.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-200 text-xs space-y-1">
                <div class="font-bold flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Clarification Needed</span>
                </div>
                <p x-text="interpreted?.clarification_question || 'Some reminder details were ambiguous. Please check the fields below.'"></p>
            </div>

            <!-- Confirmation Form (Submits to standard persistent Laravel controller) -->
            <form method="POST" action="{{ route('reminders.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">Reminder Title</label>
                    <input type="text" name="title" x-model="previewTitle" required
                           class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">Description (Optional)</label>
                    <input type="text" name="description" x-model="previewDescription"
                           class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500"
                           placeholder="Additional notes...">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">Date</label>
                        <input type="date" name="date" x-model="previewDate" required
                               class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">Time</label>
                        <input type="time" name="time" x-model="previewTime" required
                               class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">Priority</label>
                        <select name="priority" x-model="previewPriority"
                                class="w-full px-3 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">Recurrence</label>
                        <select name="recurrence" x-model="previewRecurrence"
                                class="w-full px-3 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="none">Does not repeat</option>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                </div>

                <div class="pt-3 flex items-center justify-end gap-3">
                    <button type="button" @click="interpreted = null" class="px-4 py-2 text-xs font-medium text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                        Cancel Preview
                    </button>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs sm:text-sm shadow-md shadow-emerald-600/20 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>Confirm & Save Reminder</span>
                    </button>
                </div>
            </form>
        </div>

    </div>

    <!-- TAB 2: Manual Form Creator -->
    <div x-show="activeTab === 'manual'" x-cloak class="bg-white dark:bg-slate-900 p-6 sm:p-8 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
        <div class="mb-6">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Manual Reminder Entry</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Directly schedule your reminder with precise date and time</p>
        </div>

        <form method="POST" action="{{ route('reminders.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="manual-title" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    Reminder Title <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="manual-title" name="title" value="{{ old('title') }}" required autofocus
                       placeholder="e.g. Call client regarding project proposal"
                       class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
            </div>

            <div>
                <label for="manual-description" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    Description / Notes (Optional)
                </label>
                <textarea id="manual-description" name="description" rows="3"
                          placeholder="Add any additional details, link, or context..."
                          class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">{{ old('description') }}</textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="manual-date" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Date <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" id="manual-date" name="date" value="{{ old('date', $defaultDate) }}" required
                           class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                </div>

                <div>
                    <label for="manual-time" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Time <span class="text-rose-500">*</span>
                    </label>
                    <input type="time" id="manual-time" name="time" value="{{ old('time', $defaultTime) }}" required
                           class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="manual-priority" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Priority Level
                    </label>
                    <select id="manual-priority" name="priority"
                            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                        <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ old('priority', 'medium') === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High</option>
                        <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                    </select>
                </div>

                <div>
                    <label for="manual-recurrence" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Recurrence
                    </label>
                    <select id="manual-recurrence" name="recurrence"
                            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                        <option value="none" {{ old('recurrence', 'none') === 'none' ? 'selected' : '' }}>Does not repeat</option>
                        <option value="daily" {{ old('recurrence') === 'daily' ? 'selected' : '' }}>Daily</option>
                        <option value="weekly" {{ old('recurrence') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                        <option value="monthly" {{ old('recurrence') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="yearly" {{ old('recurrence') === 'yearly' ? 'selected' : '' }}>Yearly</option>
                    </select>
                </div>
            </div>

            <div class="pt-4 flex items-center justify-between border-t border-slate-100 dark:border-slate-800">
                <a href="{{ route('reminders.index') }}" class="text-xs font-medium text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                    &larr; Back to reminders
                </a>

                <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs sm:text-sm shadow-md shadow-indigo-600/20 transition-all">
                    Create Reminder
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
