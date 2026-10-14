@extends('layouts.admin')

@section('title', "Customer: {$customer->name}")
@section('page_title', "Customer: {$customer->name}")
@section('page_description', "Account ID #{$customer->id} • Status: " . ucfirst($customer->status))

@section('content')
<div class="space-y-6">

    <!-- Top Profile & Status Bar -->
    <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-extrabold text-slate-900 dark:text-white">{{ $customer->name }}</h2>
                @if ($customer->status === 'active')
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400">ACTIVE</span>
                @elseif ($customer->status === 'suspended')
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400">SUSPENDED</span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-400">BLOCKED</span>
                @endif
            </div>
            <div class="text-xs text-slate-500 mt-1 flex flex-wrap items-center gap-3">
                <span>{{ $customer->email }}</span>
                <span>•</span>
                <span>Timezone: <strong>{{ $customer->timezone }}</strong></span>
                <span>•</span>
                <span>Language: <strong>{{ strtoupper($customer->preferred_language) }}</strong></span>
                <span>•</span>
                <span>Joined {{ $customer->created_at->format('M d, Y') }}</span>
            </div>
        </div>

        <!-- Status Change Dropdown Form -->
        <form method="POST" action="{{ route('admin.customers.status', $customer) }}" class="flex items-center gap-2">
            @csrf
            <select name="status" class="text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 font-semibold">
                <option value="active" {{ $customer->status === 'active' ? 'selected' : '' }}>Set Active</option>
                <option value="suspended" {{ $customer->status === 'suspended' ? 'selected' : '' }}>Set Suspended</option>
                <option value="blocked" {{ $customer->status === 'blocked' ? 'selected' : '' }}>Set Blocked</option>
            </select>
            <input type="text" name="reason" placeholder="Optional audit reason..." class="text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 w-44">
            <button type="submit" class="px-3 py-2 text-xs font-bold rounded-lg bg-slate-800 hover:bg-slate-700 text-white dark:bg-slate-700 dark:hover:bg-slate-600 transition-colors">
                Apply
            </button>
        </form>
    </div>

    <!-- Metrics Cards Row -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-slate-200 dark:border-slate-800">
            <span class="text-xs text-slate-500 font-semibold uppercase">Total Reminders</span>
            <div class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ $remindersCount }}</div>
            <span class="text-xs text-emerald-600 font-medium">{{ $completedRemindersCount }} completed</span>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-slate-200 dark:border-slate-800">
            <span class="text-xs text-slate-500 font-semibold uppercase">Memory Vault Files</span>
            <div class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ $vaultFilesCount }}</div>
            <span class="text-xs text-slate-500">Stored privately</span>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-slate-200 dark:border-slate-800">
            <span class="text-xs text-slate-500 font-semibold uppercase">Storage Used</span>
            <div class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">
                {{ number_format($storageUsed / 1024 / 1024, 2) }} MB
            </div>
            <span class="text-xs text-slate-500">Limit: 100 MB</span>
        </div>
    </div>

    <!-- WhatsApp Number Identities & Verification Management -->
    <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Authorized WhatsApp Identities</h3>
                <p class="text-xs text-slate-500">Only verified numbers are authorized to issue commands and retrieve memory vault files.</p>
            </div>
        </div>

        <div class="space-y-4">
            @forelse ($customer->whatsappIdentities as $identity)
                <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2.5">
                            <span class="font-mono text-sm font-bold text-slate-900 dark:text-white">{{ $identity->phone_number }}</span>
                            @if ($identity->isActive())
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">ACTIVE & VERIFIED</span>
                            @elseif ($identity->isPending())
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">PENDING VERIFICATION</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">{{ strtoupper($identity->status) }}</span>
                            @endif
                        </div>

                        <div class="text-xs text-slate-500 flex flex-wrap items-center gap-3">
                            @if ($identity->verified_at)
                                <span>Verified on: {{ $identity->verified_at->format('M d, Y h:i A') }}</span>
                            @endif
                            @if ($identity->last_inbound_at)
                                <span>Last Inbound: {{ $identity->last_inbound_at->diffForHumans() }}</span>
                                <span>(24h Window: <strong>{{ $identity->isWithin24HourServiceWindow() ? 'Open' : 'Expired' }}</strong>)</span>
                            @endif
                            @if ($identity->manual_verification_reason)
                                <span class="italic text-amber-600">Manual Override: "{{ $identity->manual_verification_reason }}"</span>
                            @endif
                        </div>

                        <!-- Active OTP display if pending -->
                        @if ($identity->isPending() && $identity->verification_token)
                            <div class="mt-2 p-2.5 rounded-lg bg-amber-500/10 border border-amber-500/20 text-xs text-amber-800 dark:text-amber-300 flex items-center gap-3">
                                <span>Active Linking Token / OTP:</span>
                                <span class="font-mono font-bold text-sm bg-white dark:bg-slate-900 px-2.5 py-0.5 rounded border border-amber-500/30 text-amber-600 dark:text-amber-400">
                                    {{ $identity->verification_token }}
                                </span>
                                <span class="text-[11px] text-slate-500">Expires {{ $identity->verification_expires_at?->diffForHumans() }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        <!-- Manual Verification Override Modal Form -->
                        @if ($identity->isPending())
                            <form method="POST" action="{{ route('admin.customers.manual-verify', $identity) }}" class="flex items-center gap-1.5" onsubmit="return confirm('Emergency override: Are you sure you want to verify this number without customer OTP proof?');">
                                @csrf
                                <input type="text" name="reason" placeholder="Required override reason..." required minlength="6"
                                       class="text-xs py-1.5 px-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 w-48">
                                <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-amber-500 hover:bg-amber-400 text-slate-950 transition-colors shadow-xs">
                                    Manual Override
                                </button>
                            </form>
                        @endif

                        <!-- Revoke Authorization -->
                        @if ($identity->status !== 'revoked')
                            <form method="POST" action="{{ route('admin.customers.revoke-identity', $identity) }}" onsubmit="return confirm('Revoke authorization for {{ $identity->phone_number }}?');">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-400 transition-colors">
                                    Revoke
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-6 text-center text-xs text-slate-500">
                    No WhatsApp numbers linked to this account yet.
                </div>
            @endforelse
        </div>

        <!-- Link New Number Form -->
        <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">Link New WhatsApp Number</h4>
            <form method="POST" action="{{ route('admin.customers.link-whatsapp', $customer) }}" class="flex flex-col sm:flex-row items-center gap-2">
                @csrf
                <input type="text" name="phone_number" placeholder="+88017XXXXXXXX" required
                       class="w-full sm:w-64 text-xs font-mono py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500">
                <button type="submit" class="w-full sm:w-auto px-4 py-2 rounded-lg text-xs font-bold text-slate-950 bg-amber-500 hover:bg-amber-400 transition-colors shadow-xs">
                    Generate Linking OTP
                </button>
            </form>
        </div>
    </div>

    <!-- Recent Reminders Table -->
    <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-3">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Recent Reminders</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="text-slate-500 uppercase text-[10px] border-b border-slate-200 dark:border-slate-800 font-bold">
                    <tr>
                        <th class="py-2.5">Title</th>
                        <th class="py-2.5">Due Time (UTC)</th>
                        <th class="py-2.5">Recurrence</th>
                        <th class="py-2.5">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($customer->reminders as $rem)
                        <tr>
                            <td class="py-2.5 font-medium text-slate-900 dark:text-white">{{ $rem->title }}</td>
                            <td class="py-2.5 font-mono text-slate-600 dark:text-slate-400">{{ $rem->due_at->toDateTimeString() }}</td>
                            <td class="py-2.5 capitalize">{{ $rem->recurrence }}</td>
                            <td class="py-2.5">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $rem->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ strtoupper($rem->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-4 text-center text-slate-500">No reminders created.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Customer Audit History -->
    <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-3">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Customer Audit History</h3>
        <div class="space-y-2">
            @forelse ($customerAuditLogs as $log)
                <div class="p-2.5 rounded-lg border border-slate-100 dark:border-slate-800 text-xs flex items-center justify-between">
                    <div>
                        <span class="font-mono font-bold text-amber-600">{{ $log->action }}</span>
                        <span class="text-slate-600 dark:text-slate-300 ml-2">{{ $log->reason }}</span>
                    </div>
                    <span class="text-[10px] text-slate-400">{{ $log->created_at->format('M d, Y h:i A') }}</span>
                </div>
            @empty
                <div class="text-xs text-slate-500">No administrative events for this customer yet.</div>
            @endforelse
        </div>
    </div>

</div>
@endsection
