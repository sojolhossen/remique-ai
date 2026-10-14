@extends('layouts.admin')

@section('title', 'WhatsApp Business Platform Management')
@section('page_title', 'WhatsApp Cloud API Management')
@section('page_description', 'Official Meta Cloud API configuration, real-time connectivity testing, and message stream')

@section('content')
<div class="space-y-6" x-data="{
    testing: false,
    testResult: null,
    runConnectivityTest() {
        this.testing = true;
        this.testResult = null;
        fetch('{{ route('admin.whatsapp.test-connectivity') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            this.testing = false;
            this.testResult = data;
        })
        .catch(err => {
            this.testing = false;
            this.testResult = {
                success: false,
                message: 'Failed to contact server: ' + err,
                status_code: 500
            };
        });
    }
}">

    <!-- Cloud API Configuration & Connectivity Status -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Configuration Card -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.698c.99.539 1.83.826 2.796.826 3.183 0 5.768-2.586 5.768-5.766 0-3.18-2.585-5.613-5.768-5.613zm8.01 5.766c0 4.425-3.596 8.021-8.01 8.021-1.398 0-2.735-.365-3.904-1.026l-4.527 1.188 1.21-4.417c-.742-1.229-1.159-2.656-1.159-4.166 0-4.425 3.596-8.02 8.01-8.02s8.38 3.595 8.38 8.42z"/></svg>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Official Cloud API Credentials</h3>
                </div>
                @if ($configStatus['is_configured'])
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400">CONFIGURED</span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400">CREDENTIALS MISSING</span>
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                    <span class="text-slate-400 uppercase text-[10px] font-bold">Phone Number ID</span>
                    <div class="font-mono font-bold text-slate-800 dark:text-slate-200 mt-1">{{ $configStatus['phone_number_id'] }}</div>
                </div>

                <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                    <span class="text-slate-400 uppercase text-[10px] font-bold">Business Account ID (WABA)</span>
                    <div class="font-mono font-bold text-slate-800 dark:text-slate-200 mt-1">{{ $configStatus['business_account_id'] }}</div>
                </div>

                <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                    <span class="text-slate-400 uppercase text-[10px] font-bold">Access Token Status</span>
                    <div class="font-semibold text-slate-800 dark:text-slate-200 mt-1 flex items-center gap-1.5">
                        @if ($configStatus['token_set'])
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Set in .env (Masked)
                        @else
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span> Unset
                        @endif
                    </div>
                </div>

                <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                    <span class="text-slate-400 uppercase text-[10px] font-bold">App Secret (HMAC-SHA256)</span>
                    <div class="font-semibold text-slate-800 dark:text-slate-200 mt-1 flex items-center gap-1.5">
                        @if ($configStatus['app_secret_set'])
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Signature Verification Active
                        @else
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span> Bypassed in local/testing
                        @endif
                    </div>
                </div>
            </div>

            <!-- Webhook instructions -->
            <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-800 space-y-2 text-xs">
                <div class="font-bold text-slate-800 dark:text-slate-200">Meta Webhook Configuration</div>
                <div class="text-slate-500">
                    In your Meta Developer App, configure the Webhook URL and Verification Token below:
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                    <div>
                        <span class="text-[10px] text-slate-400 font-bold uppercase">Callback URL</span>
                        <div class="font-mono text-slate-900 dark:text-slate-100 select-all p-2 bg-white dark:bg-slate-900 rounded border border-slate-200 dark:border-slate-700 mt-0.5">
                            {{ $configStatus['webhook_url'] }}
                        </div>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 font-bold uppercase">Verify Token</span>
                        <div class="font-mono text-slate-900 dark:text-slate-100 select-all p-2 bg-white dark:bg-slate-900 rounded border border-slate-200 dark:border-slate-700 mt-0.5">
                            {{ $configStatus['verify_token'] }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Connectivity Test Card -->
        <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col justify-between space-y-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Authorized Connectivity Test</h3>
                <p class="text-xs text-slate-500 mt-1">
                    Performs an authenticated GET query against Meta Graph API endpoint <code class="font-mono">/v21.0/{phone_number_id}</code> to verify tokens and quality rating.
                </p>

                <!-- Live Test Result Output Box -->
                <div x-show="testResult" x-cloak class="mt-4 p-3.5 rounded-lg text-xs space-y-2"
                     :class="testResult?.success ? 'bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200' : 'bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-900 dark:text-rose-200'">
                    <div class="font-bold flex items-center gap-1.5">
                        <span x-text="testResult?.success ? '✅ Meta API Connected' : '❌ Connection Rejected'"></span>
                        <span class="font-mono text-[11px]" x-text="'(Status ' + (testResult?.status_code || 0) + ')'"></span>
                    </div>
                    <div x-text="testResult?.message"></div>
                    <template x-if="testResult?.details">
                        <pre class="font-mono text-[10px] p-2 rounded bg-white/70 dark:bg-slate-900/80 overflow-x-auto" x-text="JSON.stringify(testResult.details, null, 2)"></pre>
                    </template>
                </div>
            </div>

            <button type="button"
                    @click="runConnectivityTest()"
                    :disabled="testing"
                    class="w-full py-2.5 px-4 rounded-lg text-xs font-bold text-slate-950 bg-amber-500 hover:bg-amber-400 disabled:opacity-50 transition-colors shadow-xs flex items-center justify-center gap-2">
                <svg x-show="testing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                <span x-text="testing ? 'Contacting Meta API...' : 'Run Connectivity Test'"></span>
            </button>
        </div>
    </div>

    <!-- Approved Templates Section -->
    <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-3">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Registered WhatsApp Templates</h3>
                <p class="text-xs text-slate-500">Required by Meta policies for scheduled reminders sent outside the 24-hour customer service window.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="text-slate-500 uppercase text-[10px] border-b border-slate-200 dark:border-slate-800 font-bold">
                    <tr>
                        <th class="py-2.5">Template Name</th>
                        <th class="py-2.5">Category</th>
                        <th class="py-2.5">Language</th>
                        <th class="py-2.5">Status</th>
                        <th class="py-2.5">Sample Body</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($templates as $tmpl)
                        <tr>
                            <td class="py-2.5 font-mono font-bold text-slate-900 dark:text-white">{{ $tmpl->name }}</td>
                            <td class="py-2.5 text-slate-600 dark:text-slate-400">{{ $tmpl->category }}</td>
                            <td class="py-2.5 font-mono">{{ $tmpl->language }}</td>
                            <td class="py-2.5">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400">
                                    {{ $tmpl->status }}
                                </span>
                            </td>
                            <td class="py-2.5 text-slate-600 dark:text-slate-400 italic">"{{ $tmpl->body_text }}"</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-4 text-center text-slate-500">No templates registered.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Message Logs Stream -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden space-y-4 p-6">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pb-3 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Message Activity Stream</h3>
                <p class="text-xs text-slate-500">Deduplicated inbound user commands & outbound reminder alerts</p>
            </div>

            <!-- Filters -->
            <form method="GET" action="{{ route('admin.whatsapp.index') }}" class="flex items-center gap-2">
                <select name="direction" class="text-xs py-1.5 px-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
                    <option value="">All Directions</option>
                    <option value="inbound" {{ $direction === 'inbound' ? 'selected' : '' }}>Inbound</option>
                    <option value="outbound" {{ $direction === 'outbound' ? 'selected' : '' }}>Outbound</option>
                </select>

                <select name="status" class="text-xs py-1.5 px-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
                    <option value="">All Statuses</option>
                    <option value="received" {{ $status === 'received' ? 'selected' : '' }}>Received</option>
                    <option value="sent" {{ $status === 'sent' ? 'selected' : '' }}>Sent</option>
                    <option value="delivered" {{ $status === 'delivered' ? 'selected' : '' }}>Delivered</option>
                    <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>

                <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-800 text-white dark:bg-slate-700">Filter</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="text-slate-500 uppercase text-[10px] border-b border-slate-200 dark:border-slate-800 font-bold">
                    <tr>
                        <th class="py-2.5">Direction</th>
                        <th class="py-2.5">From</th>
                        <th class="py-2.5">To</th>
                        <th class="py-2.5">Type</th>
                        <th class="py-2.5">Content / Description</th>
                        <th class="py-2.5">Status</th>
                        <th class="py-2.5">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($messages as $msg)
                        <tr>
                            <td class="py-3">
                                @if ($msg->direction === 'inbound')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300">INBOUND</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">OUTBOUND</span>
                                @endif
                            </td>
                            <td class="py-3 font-mono font-semibold">{{ $msg->from_number }}</td>
                            <td class="py-3 font-mono font-semibold">{{ $msg->to_number }}</td>
                            <td class="py-3 capitalize">{{ $msg->message_type }}</td>
                            <td class="py-3 max-w-xs truncate text-slate-700 dark:text-slate-300">{{ $msg->body ?? 'Media Attachment' }}</td>
                            <td class="py-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $msg->status === 'failed' ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
                                    {{ strtoupper($msg->status) }}
                                </span>
                            </td>
                            <td class="py-3 text-[11px] text-slate-500">{{ $msg->created_at->format('M d, H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-8 text-center text-slate-500">No message logs recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($messages->hasPages())
            <div class="pt-3 border-t border-slate-200 dark:border-slate-800">
                {{ $messages->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
