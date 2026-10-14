<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{
          darkMode: localStorage.getItem('remi_theme') === 'dark' || (!('remi_theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches),
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
      x-init="if (darkMode) document.documentElement.classList.add('dark');"
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Remique — AI-Powered WhatsApp Personal Assistant & Memory Vault</title>
    <meta name="description" content="Remique is an enterprise-grade AI WhatsApp personal assistant. Natural language reminders in Bengali, Banglish, and English, official Meta WhatsApp Cloud API, and secure private document vaults.">
    <meta name="keywords" content="Remique, AI reminder, WhatsApp assistant, Bengali AI, Banglish reminders, NVIDIA NIM, personal memory vault">

    <!-- Open Graph / Meta -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Remique — Intelligent AI WhatsApp Reminder & Personal Vault">
    <meta property="og:description" content="Natural language reminders and secure personal memory vault powered by NVIDIA NIM on WhatsApp.">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 min-h-screen flex flex-col transition-colors duration-200 antialiased selection:bg-amber-500 selection:text-white">

    <!-- Top Navigation -->
    <header class="sticky top-0 z-40 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-amber-400 flex items-center justify-center text-slate-950 font-black shadow-md shadow-amber-500/20">
                    R
                </div>
                <div>
                    <span class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">Remique</span>
                    <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300 border border-amber-300 dark:border-amber-800">AI ASSISTANT</span>
                </div>
            </div>

            <nav class="flex items-center gap-4">
                <!-- Dark Mode Toggle -->
                <button @click="toggleTheme()"
                        type="button"
                        class="p-2 rounded-lg text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        aria-label="Toggle color theme"
                        :aria-label="darkMode ? 'Switch to light mode' : 'Switch to dark mode'">
                    <svg x-show="darkMode" x-cloak class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 9h-1m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <svg x-show="!darkMode" class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                </button>

                @auth
                    <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-all">
                        Open Workspace
                    </a>
                    @if (auth()->user()->isSuperAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="px-3 py-2 rounded-xl text-xs font-bold text-amber-950 bg-amber-400 hover:bg-amber-300 shadow-xs transition-all">
                            Admin Console
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="text-xs font-semibold text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 px-3 py-2 transition-colors">
                        Sign In
                    </a>
                    <a href="{{ route('register') }}" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-all hover:shadow-indigo-500/20">
                        Get Started
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <main class="flex-1">
        <section class="relative overflow-hidden py-20 lg:py-28">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 text-xs font-semibold mb-6">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Powered by NVIDIA NIM & Meta WhatsApp Cloud API</span>
                </div>

                <h1 class="text-4xl sm:text-6xl font-extrabold tracking-tight text-slate-900 dark:text-white max-w-4xl mx-auto leading-tight">
                    Your Personal AI Assistant on <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-500 to-teal-400">WhatsApp</span>
                </h1>

                <p class="mt-6 text-lg sm:text-xl text-slate-600 dark:text-slate-400 max-w-2xl mx-auto font-normal">
                    Set natural language reminders in Bengali, Banglish, or English. Store receipts, invoices, and documents in your private zero-knowledge vault with instant natural language retrieval.
                </p>

                <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="w-full sm:w-auto px-8 py-3.5 rounded-xl font-bold text-sm text-white bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-600/30 transition-all">
                            Go to Dashboard →
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="w-full sm:w-auto px-8 py-3.5 rounded-xl font-bold text-sm text-white bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-600/30 transition-all">
                            Create Account →
                        </a>
                        <a href="{{ route('login') }}" class="w-full sm:w-auto px-6 py-3.5 rounded-xl font-semibold text-sm text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                            Sign In
                        </a>
                    @endauth
                </div>

                <!-- Chat Simulation Preview -->
                <div class="mt-16 max-w-xl mx-auto bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 p-6 text-left">
                    <div class="flex items-center gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                        <div class="w-10 h-10 rounded-full bg-emerald-600 flex items-center justify-center text-white font-bold text-sm">
                            R
                        </div>
                        <div>
                            <div class="font-bold text-sm text-slate-900 dark:text-white">Remique Assistant</div>
                            <div class="text-[11px] text-emerald-600 font-medium flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Official WhatsApp Business
                            </div>
                        </div>
                    </div>

                    <div class="py-4 space-y-3 font-sans text-xs">
                        <!-- Inbound User Message (Banglish) -->
                        <div class="flex justify-end">
                            <div class="bg-emerald-500 text-white rounded-2xl rounded-tr-xs px-4 py-2.5 max-w-[85%] shadow-xs">
                                Kal shokal 9 tay doctor appointment er reminder dao
                                <div class="text-[10px] text-emerald-100 text-right mt-1">10:42 AM ✓✓</div>
                            </div>
                        </div>

                        <!-- Bot Response -->
                        <div class="flex justify-start">
                            <div class="bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded-2xl rounded-tl-xs px-4 py-2.5 max-w-[85%] shadow-xs border border-slate-200 dark:border-slate-700">
                                🔔 Reminder set!<br><br>
                                📌 <strong>Doctor appointment</strong><br>
                                ⏰ Tomorrow at 09:00 AM<br>
                                🎯 Language: Banglish detected
                                <div class="text-[10px] text-slate-400 text-right mt-1">10:42 AM</div>
                            </div>
                        </div>

                        <!-- Inbound File Storage -->
                        <div class="flex justify-end">
                            <div class="bg-emerald-500 text-white rounded-2xl rounded-tr-xs px-4 py-2 max-w-[85%] shadow-xs flex items-center gap-2">
                                <svg class="w-4 h-4 text-emerald-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <span>march_internet_bill.pdf #finance</span>
                            </div>
                        </div>

                        <!-- Bot File Confirmation -->
                        <div class="flex justify-start">
                            <div class="bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded-2xl rounded-tl-xs px-4 py-2.5 max-w-[85%] shadow-xs border border-slate-200 dark:border-slate-700">
                                📁 Saved to your private Memory Vault!<br>
                                <strong>march_internet_bill.pdf</strong> (Category: Finance)<br>
                                <span class="text-slate-500">Ask anytime: "Show me my internet bill"</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Core Feature Pillars -->
        <section class="py-16 bg-white dark:bg-slate-900 border-y border-slate-200 dark:border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto">
                    <h2 class="text-xs font-bold text-amber-600 dark:text-amber-400 uppercase tracking-widest">Enterprise Architecture</h2>
                    <p class="mt-2 text-3xl font-extrabold text-slate-900 dark:text-white">Designed for Reliability, Security & Speed</p>
                </div>

                <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Feature 1 -->
                    <div class="p-6 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                        <div class="w-12 h-12 rounded-xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Trilingual Natural Language</h3>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                            Understands reminders typed in Bengali script (আজ রাত ৯টায়), Banglish (Aj raat 9 tay), and English without complex syntax rules.
                        </p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="p-6 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                        <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Private Memory Vault</h3>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                            Upload photos, warranties, and PDFs. Stored under opaque UUID keys with SHA-256 checksums, magic-byte inspection, and strict customer isolation.
                        </p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="p-6 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                        <div class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">HMAC-SHA256 Webhook Security</h3>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                            Full cryptographic signature validation, message deduplication by provider wamid, and 24-hour customer care window compliance.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500 dark:text-slate-400">
            <div class="flex items-center gap-2">
                <span class="font-bold text-slate-800 dark:text-slate-200">Remique Enterprise</span>
                <span>© 2026. All rights reserved.</span>
            </div>
            <div class="flex items-center gap-6">
                <span>Bengali • Banglish • English</span>
                <span>WCAG 2.2 AA Aligned</span>
                <span>NVIDIA NIM</span>
            </div>
        </div>
    </footer>

</body>
</html>
