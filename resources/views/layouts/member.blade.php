<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Member Portal') — {{ config('app.name', 'IronPulse Gym') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-900 pb-20 md:pb-0">
@php
    $user = auth()->user();
@endphp

{{-- Top Navigation --}}
<header class="bg-slate-900 text-white sticky top-0 z-20 border-b border-slate-800">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
        <a href="{{ route('portal.home') }}" class="flex items-center gap-2.5">
            <span class="w-8 h-8 rounded-lg bg-emerald-500 text-slate-950 font-bold flex items-center justify-center text-sm">IP</span>
            <span class="font-semibold tracking-tight">{{ config('app.name', 'IronPulse Gym') }}</span>
        </a>

        <nav class="hidden md:flex items-center gap-6 text-sm">
            <a href="{{ route('portal.home') }}" class="{{ request()->routeIs('portal.home') ? 'text-emerald-400 font-medium' : 'text-slate-300 hover:text-white' }}">Home</a>
            <a href="{{ route('portal.membership') }}" class="{{ request()->routeIs('portal.membership') ? 'text-emerald-400 font-medium' : 'text-slate-300 hover:text-white' }}">Membership &amp; Payments</a>
            <a href="{{ route('portal.qr-pass') }}" class="{{ request()->routeIs('portal.qr-pass') ? 'text-emerald-400 font-medium' : 'text-slate-300 hover:text-white' }}">My QR Pass</a>
            <a href="{{ route('portal.classes') }}" class="{{ request()->routeIs('portal.classes') ? 'text-emerald-400 font-medium' : 'text-slate-300 hover:text-white' }}">Classes</a>
            <a href="{{ route('portal.programs') }}" class="{{ request()->routeIs('portal.programs') ? 'text-emerald-400 font-medium' : 'text-slate-300 hover:text-white' }}">Workouts &amp; Diet</a>
        </nav>

        <div class="flex items-center gap-3">
            @if ($user->isStaff())
                <a href="{{ route('admin.dashboard') }}" class="text-xs px-2.5 py-1 rounded-md bg-emerald-500/20 text-emerald-300 hover:bg-emerald-500/30 font-medium">
                    Admin Console
                </a>
            @endif

            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="flex items-center gap-2 text-sm text-slate-200 hover:text-white">
                    <span class="w-8 h-8 rounded-full bg-slate-800 border border-slate-700 text-emerald-400 flex items-center justify-center font-semibold text-xs">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </span>
                </button>
                <div x-show="open" @click.outside="open = false" x-transition style="display: none;"
                     class="absolute right-0 mt-2 w-44 bg-white rounded-xl shadow-lg border border-slate-200 py-1 text-slate-700 z-30">
                    <div class="px-4 py-2 border-b border-slate-100">
                        <div class="text-xs font-semibold text-slate-900 truncate">{{ $user->name }}</div>
                        <div class="text-[11px] text-slate-500 truncate">{{ $user->email }}</div>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm hover:bg-slate-50">Profile</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-rose-600 hover:bg-rose-50">Log Out</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

<main class="max-w-5xl mx-auto px-4 sm:px-6 py-6">
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-800">
            {{ session('error') }}
        </div>
    @endif

    @yield('content')
    {{ $slot ?? '' }}
</main>

{{-- Mobile Bottom Navigation --}}
<nav class="md:hidden fixed bottom-0 inset-x-0 bg-white border-t border-slate-200 z-20">
    <div class="grid grid-cols-4 h-16 text-[11px] font-medium">
        <a href="{{ route('portal.home') }}" class="flex flex-col items-center justify-center gap-1 {{ request()->routeIs('portal.home') ? 'text-emerald-600' : 'text-slate-500' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <span>Home</span>
        </a>
        <a href="{{ route('portal.qr-pass') }}" class="flex flex-col items-center justify-center gap-1 {{ request()->routeIs('portal.qr-pass') ? 'text-emerald-600' : 'text-slate-500' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            <span>QR Pass</span>
        </a>
        <a href="{{ route('portal.classes') }}" class="flex flex-col items-center justify-center gap-1 {{ request()->routeIs('portal.classes') ? 'text-emerald-600' : 'text-slate-500' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span>Classes</span>
        </a>
        <a href="{{ route('profile.edit') }}" class="flex flex-col items-center justify-center gap-1 text-slate-500">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span>Profile</span>
        </a>
    </div>
</nav>

@livewireScripts
</body>
</html>
