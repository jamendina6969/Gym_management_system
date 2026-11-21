<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'IronPulse Gym') }} — Modern Gym Management</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|jetbrains-mono:500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-950 text-white min-h-screen flex flex-col relative overflow-x-hidden">
    {{-- Ambient Radial Glows --}}
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -top-40 left-1/4 w-[520px] h-[520px] rounded-full bg-emerald-500/15 blur-3xl"></div>
        <div class="absolute bottom-0 right-1/4 w-[440px] h-[440px] rounded-full bg-cyan-500/10 blur-3xl"></div>
    </div>

    {{-- Navbar --}}
    <header class="relative z-20 border-b border-white/10 bg-slate-950/75 backdrop-blur-md sticky top-0">
        <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 group">
                <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 text-slate-950 font-extrabold flex items-center justify-center shadow-lg shadow-emerald-500/25 group-hover:scale-105 transition">IP</span>
                <span class="font-extrabold tracking-tight text-lg">{{ config('app.name', 'IronPulse Gym') }}</span>
            </a>

            <div class="flex items-center gap-3 text-sm">
                @auth
                    <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-xl bg-emerald-500 text-slate-950 font-semibold hover:bg-emerald-400 shadow-lg shadow-emerald-500/20 btn-press">
                        Open Dashboard →
                    </a>
                @else
                    <a href="{{ route('login') }}" class="px-4 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-white/5 transition font-medium">Log in</a>
                    <a href="{{ route('register') }}" class="px-4 py-2 rounded-xl bg-emerald-500 text-slate-950 font-semibold hover:bg-emerald-400 shadow-lg shadow-emerald-500/25 btn-press">
                        Join Now
                    </a>
                @endauth
            </div>
        </div>
    </header>

    {{-- Hero Section --}}
    <main class="relative z-10 flex-1 flex items-center">
        <div class="max-w-6xl mx-auto px-6 py-16 lg:py-24 grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            <div class="lg:col-span-7 animate-fade-in-up">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs font-semibold">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse-glow"></span>
                    Multi-Branch Gym Operations &amp; Member Portal
                </div>

                <h1 class="mt-6 text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight leading-[1.08]">
                    Train Smarter.<br>
                    <span class="bg-gradient-to-r from-emerald-400 via-teal-300 to-cyan-400 bg-clip-text text-transparent animate-gradient-text">
                        Run Every Branch
                    </span>
                    Effortlessly.
                </h1>

                <p class="mt-5 text-slate-400 text-base sm:text-lg leading-relaxed max-w-xl">
                    Instant QR check-ins, PayMongo (GCash, Maya, QR Ph) &amp; Stripe payments, live class waitlists, custom workout &amp; diet plans, equipment tracking, and real-time analytics.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="px-6 py-3.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 text-slate-950 font-bold text-sm hover:from-emerald-400 hover:to-teal-400 shadow-xl shadow-emerald-500/25 btn-press">
                        Register as Member →
                    </a>
                    <a href="{{ route('login') }}" class="px-6 py-3.5 rounded-xl bg-white/5 hover:bg-white/10 text-white font-semibold text-sm border border-white/15 backdrop-blur btn-press">
                        Staff &amp; Member Login
                    </a>
                </div>

                {{-- Quick Stats Strip --}}
                <div class="mt-10 pt-8 border-t border-white/10 grid grid-cols-3 gap-6 max-w-md">
                    <div class="animate-fade-in-up stagger-2">
                        <div class="text-2xl font-extrabold text-white">QR</div>
                        <div class="text-xs text-slate-400 mt-0.5">Instant Check-in</div>
                    </div>
                    <div class="animate-fade-in-up stagger-3">
                        <div class="text-2xl font-extrabold text-emerald-400">₱ PHP</div>
                        <div class="text-xs text-slate-400 mt-0.5">GCash, Maya &amp; Card</div>
                    </div>
                    <div class="animate-fade-in-up stagger-4">
                        <div class="text-2xl font-extrabold text-white">Multi</div>
                        <div class="text-xs text-slate-400 mt-0.5">Branch Ready</div>
                    </div>
                </div>
            </div>

            {{-- Interactive Feature Cards --}}
            <div class="lg:col-span-5 space-y-4">
                <div class="p-5 rounded-2xl bg-gradient-to-br from-slate-900/90 to-slate-900/60 border border-emerald-500/30 shadow-2xl shadow-emerald-950/50 backdrop-blur animate-float">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-emerald-400">Live Front Desk</span>
                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-[11px] font-semibold">Access Allowed</span>
                    </div>
                    <div class="mt-3 flex items-center gap-3">
                        <div class="w-11 h-11 rounded-xl bg-emerald-500/20 border border-emerald-400/30 flex items-center justify-center text-emerald-300 font-bold">JD</div>
                        <div>
                            <div class="font-bold text-sm text-white">Juan Dela Cruz</div>
                            <div class="text-xs text-slate-400 font-mono">IP-2026-0001 · All-Access VIP</div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="p-5 rounded-2xl bg-slate-900/80 border border-white/10 hover:border-emerald-500/40 transition-all duration-200 hover:-translate-y-1 animate-fade-in-up stagger-2">
                        <div class="w-9 h-9 rounded-lg bg-emerald-500/15 text-emerald-400 flex items-center justify-center mb-3 font-bold text-sm">₱</div>
                        <div class="text-white font-bold text-sm">Flexible Billing</div>
                        <p class="text-xs text-slate-400 mt-1">PayMongo, Stripe, manual proof verification &amp; PDF receipts.</p>
                    </div>
                    <div class="p-5 rounded-2xl bg-slate-900/80 border border-white/10 hover:border-cyan-500/40 transition-all duration-200 hover:-translate-y-1 animate-fade-in-up stagger-3">
                        <div class="w-9 h-9 rounded-lg bg-cyan-500/15 text-cyan-400 flex items-center justify-center mb-3 font-bold text-sm">⚡</div>
                        <div class="text-white font-bold text-sm">Classes &amp; Plans</div>
                        <p class="text-xs text-slate-400 mt-1">Auto-promoting waitlists plus custom workout &amp; diet plans.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
