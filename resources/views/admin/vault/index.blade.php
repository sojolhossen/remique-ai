@extends('layouts.admin')

@section('title', 'Memory Vault Management')
@section('page_title', 'Personal Memory Vault Governance')
@section('page_description', 'Private storage metrics, metadata inspection, and compliance lifecycle')

@section('content')
<div class="space-y-6">

    <!-- Global Storage Breakdown -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-slate-200 dark:border-slate-800">
            <span class="text-xs text-slate-500 font-semibold uppercase">Total Disk Footprint</span>
            <div class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ $globalMetrics['formatted_size'] }}</div>
            <span class="text-xs text-slate-400">{{ number_format($globalMetrics['total_bytes']) }} bytes</span>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-slate-200 dark:border-slate-800">
            <span class="text-xs text-slate-500 font-semibold uppercase">Total Files Stored</span>
            <div class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ $globalMetrics['total_count'] }}</div>
            <span class="text-xs text-emerald-600 font-semibold">In private vault</span>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-slate-200 dark:border-slate-800">
            <span class="text-xs text-slate-500 font-semibold uppercase">Images (JPEG/PNG/WEBP)</span>
            <div class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ $globalMetrics['images_count'] }}</div>
            <span class="text-xs text-slate-400">Validated MIME types</span>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-slate-200 dark:border-slate-800">
            <span class="text-xs text-slate-500 font-semibold uppercase">Documents (PDF)</span>
            <div class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ $globalMetrics['documents_count'] }}</div>
            <span class="text-xs text-slate-400">Validated PDF headers</span>
        </div>
    </div>

    <!-- Privacy Notice Banner -->
    <div class="p-4 rounded-xl bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs text-slate-600 dark:text-slate-400 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
            <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            <span><strong>Zero-Knowledge Content Guarantee:</strong> Customer private files are isolated in private server storage under opaque UUID keys. Direct browsing of customer media contents is blocked by default to preserve personal privacy.</span>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.vault.index') }}" class="flex flex-1 items-center gap-3 w-full sm:w-auto">
            <div class="relative flex-1">
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Search filename, caption, or owner..."
                       class="w-full pl-9 pr-4 py-2 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>

            <select name="mime_type" class="text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
                <option value="">All Types</option>
                <option value="image/jpeg" {{ $mime === 'image/jpeg' ? 'selected' : '' }}>JPEG Image</option>
                <option value="image/png" {{ $mime === 'image/png' ? 'selected' : '' }}>PNG Image</option>
                <option value="image/webp" {{ $mime === 'image/webp' ? 'selected' : '' }}>WEBP Image</option>
                <option value="application/pdf" {{ $mime === 'application/pdf' ? 'selected' : '' }}>PDF Document</option>
            </select>

            <button type="submit" class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-slate-800 hover:bg-slate-700 text-white dark:bg-slate-700 transition-colors">
                Filter
            </button>
            @if (!empty($search) || !empty($mime))
                <a href="{{ route('admin.vault.index') }}" class="text-xs text-slate-500 hover:underline">Reset</a>
            @endif
        </form>
    </div>

    <!-- Vault Files Metadata Table -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 uppercase tracking-wider text-[10px] text-slate-500 font-bold">
                    <tr>
                        <th class="py-3 px-4">File ID</th>
                        <th class="py-3 px-4">Owner</th>
                        <th class="py-3 px-4">Original Filename</th>
                        <th class="py-3 px-4">Format</th>
                        <th class="py-3 px-4">Size</th>
                        <th class="py-3 px-4">Checksum (SHA-256)</th>
                        <th class="py-3 px-4">Uploaded</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($files as $file)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-3 px-4 font-mono text-slate-400">#{{ $file->id }}</td>
                            <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">
                                {{ $file->user->name ?? 'Unknown' }}
                            </td>
                            <td class="py-3 px-4 max-w-xs truncate">
                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $file->original_filename }}</span>
                                @if ($file->caption)
                                    <div class="text-[11px] text-slate-500 truncate italic">"{{ $file->caption }}"</div>
                                @endif
                            </td>
                            <td class="py-3 px-4 font-mono uppercase text-[10px] text-slate-600 dark:text-slate-400">
                                {{ explode('/', $file->mime_type)[1] ?? $file->mime_type }}
                            </td>
                            <td class="py-3 px-4 font-mono">
                                {{ $file->formattedSize() }}
                            </td>
                            <td class="py-3 px-4 font-mono text-[10px] text-slate-400">
                                {{ substr($file->checksum_sha256, 0, 16) }}...
                            </td>
                            <td class="py-3 px-4 text-slate-500 text-[11px]">
                                {{ $file->created_at->format('M d, Y') }}
                            </td>
                            <td class="py-3 px-4 text-right">
                                @if ($file->status !== 'deleted')
                                    <form method="POST" action="{{ route('admin.vault.delete-file', $file) }}" class="inline" onsubmit="return prompt('Enter audit reason to delete this file metadata:');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="reason" value="Super Admin compliance file deletion">
                                        <button type="submit" class="px-2.5 py-1 text-xs font-semibold rounded bg-rose-50 text-rose-700 hover:bg-rose-100">
                                            Delete
                                        </button>
                                    </form>
                                @else
                                    <span class="text-rose-600 font-bold uppercase text-[10px]">DELETED</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-xs text-slate-500">
                                No Memory Vault files recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($files->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $files->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
