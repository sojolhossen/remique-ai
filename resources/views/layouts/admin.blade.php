<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{
          darkMode: localStorage.getItem('remi_theme') === 'dark' || (!('remi_theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches),
          sidebarOpen: false,
          toggleTheme() {
              this.darkMode = !this.darkMode;
              localStorage.setItem('remi_theme', this.darkMode ? 'dark' : 'light');
              if (this.darkMode) {
                  document.documentElement.classList.add('dark');
              } else {
                  document.documentElement.classList.remove('dark');
              }
          }
      }"
      x-init="$watch('darkMode', val => {
          if (val) document.documentElement.classList.add('dark');
          else document.documentElement.classList.remove('dark');
      }); if (darkMode) document.documentElement.classList.add('dark');"
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin Console') — Remique Enterprise</title>

    <!-- Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        code, pre, .font-mono { font-family: 'JetBrains Mono', monospace; }
        [x-cloak] { display: none !important; }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 min-h-screen antialiased flex flex-col transition-colors duration-200">

    <div class="flex h-screen overflow-hidden">
        <!-- Mobile Sidebar Backdrop -->
        <div x-show="sidebarOpen"
             x-cloak
             @click="sidebarOpen = false"
             x-transition:enter="transition-opacity ease-linear duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-40 bg-slate-900/60 lg:hidden backdrop-blur-xs"></div>

        <!-- Super Admin Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
               class="fixed lg:static inset-y-0 left-0 z-50 w-64 bg-slate-900 text-slate-300 flex flex-col justify-between transition-transform duration-200 ease-in-out border-r border-slate-800">

            <div class="flex flex-col h-full overflow-y-auto">
                <!-- Brand Header -->
                <div class="h-16 flex items-center justify-between px-6 border-b border-slate-800/80 bg-slate-950/40">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-amber-500 flex items-center justify-center text-slate-950 font-bold shadow-md shadow-amber-500/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <div>
                            <span class="font-extrabold text-white text-base tracking-tight">Remique</span>
                            <span class="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-amber-500/10 text-amber-400 border border-amber-500/20 ml-1">Admin</span>
                        </div>
                    </a>

                    <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- Navigation Modules -->
                <nav class="p-4 space-y-1.5 flex-1">
                    <div class="px-3 pb-1.5 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Core Administration</div>

                    <!-- Overview -->
                    <a href="{{ route('admin.dashboard') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-amber-500/15 text-amber-400 font-semibold border-l-2 border-amber-500' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        <span>Overview</span>
                    </a>

                    <!-- Customer Management -->
                    <a href="{{ route('admin.customers.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.customers.*') ? 'bg-amber-500/15 text-amber-400 font-semibold border-l-2 border-amber-500' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span>Customers & Access</span>
                    </a>

                    <!-- WhatsApp Platform -->
                    <a href="{{ route('admin.whatsapp.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.whatsapp.*') ? 'bg-amber-500/15 text-amber-400 font-semibold border-l-2 border-amber-500' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.698c.99.539 1.83.826 2.796.826 3.183 0 5.768-2.586 5.768-5.766 0-3.18-2.585-5.613-5.768-5.613zm8.01 5.766c0 4.425-3.596 8.021-8.01 8.021-1.398 0-2.735-.365-3.904-1.026l-4.527 1.188 1.21-4.417c-.742-1.229-1.159-2.656-1.159-4.166 0-4.425 3.596-8.02 8.01-8.02s8.38 3.595 8.38 8.42z"/></svg>
                        <span>WhatsApp Cloud API</span>
                    </a>

                    <!-- Reminders Oversight -->
                    <a href="{{ route('admin.reminders.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.reminders.*') ? 'bg-amber-500/15 text-amber-400 font-semibold border-l-2 border-amber-500' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Reminders Delivery</span>
                    </a>

                    <!-- Memory Vault -->
                    <a href="{{ route('admin.vault.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.vault.*') ? 'bg-amber-500/15 text-amber-400 font-semibold border-l-2 border-amber-500' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                        <span>Memory Vault Files</span>
                    </a>

                    <div class="pt-4 px-3 pb-1.5 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Governance & Intelligence</div>

                    <!-- AI Settings & Playground -->
                    <a href="{{ route('admin.ai.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.ai.*') ? 'bg-amber-500/15 text-amber-400 font-semibold border-l-2 border-amber-500' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        <span>AI Engine (NVIDIA NIM)</span>
                    </a>

                    <!-- Audit Logs & Security -->
                    <a href="{{ route('admin.audit.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.audit.*') ? 'bg-amber-500/15 text-amber-400 font-semibold border-l-2 border-amber-500' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span>Security & Audit Logs</span>
                    </a>

                    <!-- System Health -->
                    <a href="{{ route('admin.system.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.system.*') ? 'bg-amber-500/15 text-amber-400 font-semibold border-l-2 border-amber-500' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path></svg>
                        <span>System Settings</span>
                    </a>
                </nav>

                <!-- Footer with User Workspace switch -->
                <div class="p-4 border-t border-slate-800 bg-slate-950/60">
                    <a href="{{ route('dashboard') }}" class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-800 transition-colors">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z"></path></svg>
                            User Workspace
                        </span>
                        <span class="text-[10px] bg-slate-800 px-1.5 py-0.5 rounded text-slate-400">Exit Admin</span>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Admin Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Top Navigation Bar -->
            <header class="h-16 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-6 z-10 shrink-0">
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = true" class="lg:hidden text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 p-1">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <div>
                        <h1 class="text-lg font-bold text-slate-900 dark:text-white leading-tight">@yield('page_title', 'Enterprise Admin')</h1>
                        <p class="text-xs text-slate-500 dark:text-slate-400">@yield('page_description', 'Operational management and compliance')</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Dark mode toggle -->
                    <button @click="toggleTheme()" class="p-2 rounded-lg text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" title="Toggle theme">
                        <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                        <svg x-show="darkMode" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </button>

                    <!-- Super Admin Badge -->
                    <div class="flex items-center gap-2 pl-3 border-l border-slate-200 dark:border-slate-800">
                        <div class="w-8 h-8 rounded-full bg-amber-500/20 text-amber-600 dark:text-amber-400 font-bold flex items-center justify-center text-xs border border-amber-500/30">
                            {{ substr(auth()->user()?->name ?? 'Admin', 0, 2) }}
                        </div>
                        <div class="hidden sm:block text-left">
                            <div class="text-xs font-semibold text-slate-800 dark:text-slate-200">{{ auth()->user()?->name ?? 'Administrator' }}</div>
                            <div class="text-[10px] text-amber-600 dark:text-amber-400 font-bold uppercase tracking-wider">Super Admin</div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Alerts Banner -->
            <div class="px-6 pt-4">
                @if (session('success'))
                    <div class="mb-4 p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 text-sm flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            <span>{{ session('success') }}</span>
                        </div>
                        <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 p-1">&times;</button>
                    </div>
                @endif

                @if (session('error') || (isset($errors) && $errors->any()))
                    <div class="mb-4 p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 text-sm flex items-start justify-between">
                        <div class="flex items-start gap-2.5">
                            <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                            <div>
                                @if (session('error'))
                                    <div>{{ session('error') }}</div>
                                @endif
                                @if (isset($errors) && $errors->any())
                                    <ul class="list-disc list-inside space-y-0.5 text-xs mt-1">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                        <button type="button" @click="$el.parentElement.remove()" class="text-rose-600 hover:text-rose-800 p-1">&times;</button>
                    </div>
                @endif
            </div>

            <!-- Page Body -->
            <main class="flex-1 overflow-y-auto px-6 pb-12">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
