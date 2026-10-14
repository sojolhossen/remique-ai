@extends('layouts.admin')

@section('title', 'Customer Management')
@section('page_title', 'Authorized Customer Directory')
@section('page_description', 'Enforce tenant isolation, manage verified WhatsApp identities and account lifecycle')

@section('content')
<div class="space-y-6">

    <!-- Action Bar & Filters -->
    <div class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.customers.index') }}" class="flex flex-1 items-center gap-3 w-full sm:w-auto">
            <div class="relative flex-1">
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Search by name, email, or WhatsApp number..."
                       class="w-full pl-9 pr-4 py-2 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>

            <select name="status" class="text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500">
                <option value="">All Statuses</option>
                <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                <option value="suspended" {{ $status === 'suspended' ? 'selected' : '' }}>Suspended</option>
                <option value="blocked" {{ $status === 'blocked' ? 'selected' : '' }}>Blocked</option>
            </select>

            <button type="submit" class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-slate-800 hover:bg-slate-700 text-white dark:bg-slate-700 dark:hover:bg-slate-600 transition-colors">
                Filter
            </button>
            @if (!empty($search) || !empty($status))
                <a href="{{ route('admin.customers.index') }}" class="text-xs text-slate-500 hover:underline">Reset</a>
            @endif
        </form>

        <a href="{{ route('admin.customers.create') }}" class="w-full sm:w-auto px-4 py-2 rounded-lg text-xs font-bold text-slate-950 bg-amber-500 hover:bg-amber-400 transition-colors shadow-xs flex items-center justify-center gap-1.5 shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span>Register Customer</span>
        </a>
    </div>

    <!-- Customers Table -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 uppercase tracking-wider text-[10px] text-slate-500 font-bold">
                    <tr>
                        <th class="py-3 px-4">Customer</th>
                        <th class="py-3 px-4">Verified WhatsApp</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Reminders</th>
                        <th class="py-3 px-4">Vault Files</th>
                        <th class="py-3 px-4">Joined</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($customers as $customer)
                        @php
                            $primaryWa = $customer->whatsappIdentities->first();
                        @endphp
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-3.5 px-4">
                                <a href="{{ route('admin.customers.show', $customer) }}" class="font-bold text-slate-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400">
                                    {{ $customer->name }}
                                </a>
                                <div class="text-[11px] text-slate-500">{{ $customer->email }}</div>
                            </td>
                            <td class="py-3.5 px-4 font-mono">
                                @if ($primaryWa)
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $primaryWa->phone_number }}</span>
                                        @if ($primaryWa->isActive())
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400">VERIFIED</span>
                                        @elseif ($primaryWa->isPending())
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400">OTP PENDING</span>
                                        @else
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400">{{ strtoupper($primaryWa->status) }}</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-slate-400 italic">None linked</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                @if ($customer->status === 'active')
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">Active</span>
                                @elseif ($customer->status === 'suspended')
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300">Suspended</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300">Blocked</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-medium text-slate-700 dark:text-slate-300">
                                {{ $customer->reminders_count }}
                            </td>
                            <td class="py-3.5 px-4 font-medium text-slate-700 dark:text-slate-300">
                                {{ $customer->vault_files_count }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-500 text-[11px]">
                                {{ $customer->created_at->format('M d, Y') }}
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <a href="{{ route('admin.customers.show', $customer) }}" class="px-2.5 py-1 text-xs font-semibold rounded bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors">
                                    Manage &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-xs text-slate-500">
                                No customer records found matching the filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($customers->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $customers->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
