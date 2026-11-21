<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'IronPulse Gym') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-950 text-white min-h-screen flex flex-col">
    <header class="border-b border-slate-800/80">
        <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-lg bg-emerald-500 text-slate-950 font-extrabold flex items-center justify-center">IP</span>
                <span class="font-bold tracking-tight text-lg">{{ config('app.name', 'IronPulse Gym') }}</span>
            </div>
            <div class="flex items-center gap-3 text-sm">
                @auth
                    <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-lg bg-emerald-500 text-slate-950 font-semibold hover:bg-emerald-400 transition">
                        Go to Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="px-4 py-2 rounded-lg text-slate-300 hover:text-white transition">Log in</a>
                    <a href="{{ route('register') }}" class="px-4 py-2 rounded-lg bg-emerald-500 text-slate-950 font-semibold hover:bg-emerald-400 transition">Join Now</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="flex-1 flex items-center">
        <div class="max-w-6xl mx-auto px-6 py-20 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold">
                    Multi-Branch Gym Management & Member Portal
                </span>
                <h1 class="mt-5 text-4xl sm:text-5xl font-extrabold tracking-tight leading-tight">
                    Train Smarter.<br>
                    <span class="text-emerald-400">Run Every Branch</span> Effortlessly.
                </h1>
                <p class="mt-5 text-slate-400 text-base leading-relaxed max-w-xl">
                    Memberships, instant QR check-ins, GCash &amp; card payments, class bookings, workout &amp; diet plans, and real-time analytics — all in one platform.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="px-6 py-3 rounded-xl bg-emerald-500 text-slate-950 font-semibold hover:bg-emerald-400 transition">
                        Register as Member
                    </a>
                    <a href="{{ route('login') }}" class="px-6 py-3 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-700 border border-slate-700 transition">
                        Staff &amp; Member Login
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800">
                    <div class="text-emerald-400 font-bold text-lg">QR Check-in</div>
                    <p class="text-xs text-slate-400 mt-1.5">Fast webcam front-desk scanning with automatic expiry validation.</p>
                </div>
                <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800">
                    <div class="text-emerald-400 font-bold text-lg">Flexible Payments</div>
                    <p class="text-xs text-slate-400 mt-1.5">PayMongo (GCash, Maya, cards), Stripe, or manual front-desk receipts.</p>
                </div>
                <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800">
                    <div class="text-emerald-400 font-bold text-lg">Classes &amp; Plans</div>
                    <p class="text-xs text-slate-400 mt-1.5">Live class schedules, waitlists, and personalized workout &amp; diet plans.</p>
                </div>
                <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800">
                    <div class="text-emerald-400 font-bold text-lg">Multi-Branch</div>
                    <p class="text-xs text-slate-400 mt-1.5">Scale from one studio to a nationwide chain with branch-scoped roles.</p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
