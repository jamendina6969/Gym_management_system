<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Member Portal') — {{ config('app.name', 'IronPulse Gym') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|jetbrains-mono:500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-slate-100/80 text-slate-900 pb-20 md:pb-8">
@php
    $user = auth()->user();
@endphp

{{-- Top Navigation --}}
<header class="bg-slate-950/90 backdrop-blur-md text-white sticky top-0 z-20 border-b border-slate-800/80 shadow-md">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
        <a href="{{ route('portal.home') }}" class="flex items-center gap-2.5 group">
            <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 text-slate-950 font-extrabold flex items-center justify-center text-sm shadow-lg shadow-emerald-500/25 group-hover:scale-105 transition">IP</span>
            <span class="font-bold tracking-tight">{{ config('app.name', 'IronPulse Gym') }}</span>
        </a>

        <nav class="hidden md:flex items-center gap-1 text-xs font-semibold">
            @foreach ([
                ['route' => 'portal.home', 'label' => 'Home'],
                ['route' => 'portal.membership', 'label' => 'Membership'],
                ['route' => 'portal.qr-pass', 'label' => 'My QR Pass'],
                ['route' => 'portal.classes', 'label' => 'Classes'],
                ['route' => 'portal.programs', 'label' => 'Workouts & Diet'],
            ] as $nav)
                <a href="{{ route($nav['route']) }}"
                   class="px-3 py-2 rounded-xl transition {{ request()->routeIs($nav['route']) ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/25' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                    {{ $nav['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="flex items-center gap-3">
            @if ($user->isStaff())
                <a href="{{ route('admin.dashboard') }}" class="text-xs px-3 py-1.5 rounded-xl bg-emerald-500/20 text-emerald-300 hover:bg-emerald-500/30 border border-emerald-500/30 font-semibold transition">
                    Admin Console
                </a>
            @endif

            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="flex items-center gap-2 text-sm text-slate-200 hover:text-white">
                    <span class="w-8 h-8 rounded-xl bg-slate-800 border border-slate-700 text-emerald-400 flex items-center justify-center font-bold text-xs">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </span>
                </button>
                <div x-show="open" @click.outside="open = false"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     style="display: none;"
                     class="absolute right-0 mt-2 w-48 bg-white rounded-2xl shadow-xl border border-slate-200 py-1.5 text-slate-700 z-30">
                    <div class="px-4 py-2 border-b border-slate-100">
                        <div class="text-xs font-bold text-slate-900 truncate">{{ $user->name }}</div>
                        <div class="text-[11px] text-slate-500 truncate">{{ $user->email }}</div>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-xs font-medium hover:bg-slate-50">Profile Settings</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50">Log Out</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

{{-- Floating Toast --}}
@if (session('success') || session('error'))
    <div x-data="{ show: true }"
         x-init="setTimeout(() => show = false, 4500)"
         x-show="show"
         x-transition
         class="fixed bottom-20 md:bottom-5 right-5 z-50 max-w-sm">
        <div class="rounded-2xl shadow-xl border px-4 py-3.5 text-sm font-medium flex items-center gap-3 {{ session('success') ? 'bg-slate-900 text-white border-emerald-500/40' : 'bg-rose-600 text-white border-rose-400' }}">
            <span class="w-2.5 h-2.5 rounded-full shrink-0 {{ session('success') ? 'bg-emerald-400 animate-pulse-glow' : 'bg-white' }}"></span>
            <span>{{ session('success') ?? session('error') }}</span>
            <button @click="show = false" class="ml-2 opacity-70 hover:opacity-100 text-xs">✕</button>
        </div>
    </div>
@endif

<main class="max-w-5xl mx-auto px-4 sm:px-6 py-6 animate-fade-in-up">
    @yield('content')
    {{ $slot ?? '' }}
</main>

{{-- Mobile Bottom Navigation --}}
<nav class="md:hidden fixed bottom-0 inset-x-0 bg-white/95 backdrop-blur-md border-t border-slate-200 z-20">
    <div class="grid grid-cols-5 h-16 text-[10px] font-semibold">
        <a href="{{ route('portal.home') }}" class="flex flex-col items-center justify-center gap-1 {{ request()->routeIs('portal.home') ? 'text-emerald-600' : 'text-slate-500' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <span>Home</span>
        </a>
        <a href="{{ route('portal.membership') }}" class="flex flex-col items-center justify-center gap-1 {{ request()->routeIs('portal.membership') ? 'text-emerald-600' : 'text-slate-500' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            <span>Plan</span>
        </a>
        <a href="{{ route('portal.qr-pass') }}" class="flex flex-col items-center justify-center gap-1 {{ request()->routeIs('portal.qr-pass') ? 'text-emerald-600' : 'text-slate-500' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            <span>QR Pass</span>
        </a>
        <a href="{{ route('portal.classes') }}" class="flex flex-col items-center justify-center gap-1 {{ request()->routeIs('portal.classes') ? 'text-emerald-600' : 'text-slate-500' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span>Classes</span>
        </a>
        <a href="{{ route('portal.programs') }}" class="flex flex-col items-center justify-center gap-1 {{ request()->routeIs('portal.programs') ? 'text-emerald-600' : 'text-slate-500' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <span>Plans</span>
        </a>
    </div>
</nav>

@livewireScripts
</body>
</html>
