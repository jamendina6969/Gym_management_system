<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'IronPulse Gym') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|jetbrains-mono:500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-950 text-slate-900 relative overflow-x-hidden">
    {{-- Ambient Background Glow --}}
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-emerald-500/20 blur-3xl"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 rounded-full bg-cyan-500/15 blur-3xl"></div>
    </div>

    <div class="relative min-h-screen flex flex-col sm:justify-center items-center px-4 py-10">
        <a href="{{ route('home') }}" class="flex items-center gap-3 mb-6 group animate-fade-in-up">
            <span class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 text-slate-950 font-extrabold text-lg flex items-center justify-center shadow-lg shadow-emerald-500/30 group-hover:scale-105 transition">
                IP
            </span>
            <div>
                <div class="text-white font-extrabold text-xl tracking-tight leading-none">{{ config('app.name', 'IronPulse Gym') }}</div>
                <div class="text-emerald-400 text-xs font-medium mt-0.5">Gym Management &amp; Member Portal</div>
            </div>
        </a>

        <div class="w-full sm:max-w-md px-6 py-7 bg-white/95 backdrop-blur-xl shadow-2xl shadow-black/40 border border-slate-200/80 rounded-2xl animate-scale-in">
            {{ $slot }}
        </div>

        <p class="mt-6 text-xs text-slate-500 animate-fade-in-up stagger-2">
            &copy; {{ date('Y') }} {{ config('app.name', 'IronPulse Gym') }} · All rights reserved.
        </p>
    </div>
</body>
</html>
