<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin') — {{ config('app.name', 'IronPulse Gym') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|jetbrains-mono:500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-slate-100/90 text-slate-900" x-data="{ sidebarOpen: false }">
@php
    $branchContext = app(\App\Services\BranchContext::class);
    $accessibleBranches = $branchContext->accessibleBranches();
    $currentBranch = $branchContext->currentBranch();
    $user = auth()->user();
    $roleLabel = str($user->getRoleNames()->first() ?? 'staff')->replace('-', ' ')->title();
@endphp

<div class="min-h-screen flex">
    {{-- Mobile Backdrop --}}
    <div x-show="sidebarOpen"
         x-transition.opacity.duration.200ms
         @click="sidebarOpen = false"
         class="fixed inset-0 z-30 bg-slate-950/60 backdrop-blur-xs lg:hidden"
         style="display: none;"></div>

    {{-- Sidebar --}}
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-40 w-64 bg-slate-950 text-slate-100 flex flex-col transition-transform duration-200 ease-out lg:translate-x-0 lg:static lg:inset-auto border-r border-slate-800/80 shadow-xl">
        {{-- Brand --}}
        <div class="h-16 flex items-center justify-between px-5 border-b border-slate-800/80">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 group">
                <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 text-slate-950 font-extrabold flex items-center justify-center text-base shadow-lg shadow-emerald-500/25 group-hover:scale-105 transition">
                    IP
                </span>
                <div>
                    <div class="font-bold text-sm tracking-tight text-white leading-tight">{{ config('app.name', 'IronPulse Gym') }}</div>
                    <div class="text-[11px] text-emerald-400 font-medium">Management Console</div>
                </div>
            </a>
            <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-6 text-sm">
            <div>
                <div class="px-3 mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-500">Overview</div>
                <div class="space-y-1">
                    <a href="{{ route('admin.dashboard') }}"
                       class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('admin.dashboard') ? 'bg-gradient-to-r from-emerald-500/20 to-emerald-500/5 text-emerald-400 font-semibold border border-emerald-500/25 shadow-sm' : 'text-slate-300 hover:bg-slate-900 hover:text-white hover:translate-x-0.5' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span>Dashboard</span>
                    </a>

                    @can('branches.view')
                    <a href="{{ route('admin.branches.index') }}"
                       class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('admin.branches.*') ? 'bg-gradient-to-r from-emerald-500/20 to-emerald-500/5 text-emerald-400 font-semibold border border-emerald-500/25 shadow-sm' : 'text-slate-300 hover:bg-slate-900 hover:text-white hover:translate-x-0.5' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span>Branches</span>
                    </a>
                    @endcan
                </div>
            </div>

            <div>
                <div class="px-3 mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-500">Operations</div>
                <div class="space-y-1">
                    @can('members.view')
                    <a href="{{ route('admin.members.index') }}"
                       class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('admin.members.*') ? 'bg-gradient-to-r from-emerald-500/20 to-emerald-500/5 text-emerald-400 font-semibold border border-emerald-500/25 shadow-sm' : 'text-slate-300 hover:bg-slate-900 hover:text-white hover:translate-x-0.5' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Members</span>
                    </a>
                    @endcan

                    @can('plans.view')
                    <a href="{{ route('admin.plans.index') }}"
                       class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('admin.plans.*') ? 'bg-gradient-to-r from-emerald-500/20 to-emerald-500/5 text-emerald-400 font-semibold border border-emerald-500/25 shadow-sm' : 'text-slate-300 hover:bg-slate-900 hover:text-white hover:translate-x-0.5' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Membership Plans</span>
                    </a>
                    @endcan

                    @can('payments.view')
                    <a href="{{ route('admin.payments.index') }}"
                       class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('admin.payments.*') ? 'bg-gradient-to-r from-emerald-500/20 to-emerald-500/5 text-emerald-400 font-semibold border border-emerald-500/25 shadow-sm' : 'text-slate-300 hover:bg-slate-900 hover:text-white hover:translate-x-0.5' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span>Payments &amp; Billing</span>
                    </a>
                    @endcan

                    @can('attendance.view')
                    <a href="{{ route('admin.attendance.index') }}"
                       class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('admin.attendance.*') ? 'bg-gradient-to-r from-emerald-500/20 to-emerald-500/5 text-emerald-400 font-semibold border border-emerald-500/25 shadow-sm' : 'text-slate-300 hover:bg-slate-900 hover:text-white hover:translate-x-0.5' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                        <span>QR Check-in</span>
                    </a>
                    @endcan

                    @can('classes.view')
                    <a href="{{ route('admin.classes.index') }}"
                       class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('admin.classes.*') ? 'bg-gradient-to-r from-emerald-500/20 to-emerald-500/5 text-emerald-400 font-semibold border border-emerald-500/25 shadow-sm' : 'text-slate-300 hover:bg-slate-900 hover:text-white hover:translate-x-0.5' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>Trainers &amp; Classes</span>
                    </a>
                    @endcan

                    @canany(['workouts.view', 'diets.view'])
                    <a href="{{ route('admin.programs.index') }}"
                       class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('admin.programs.*') ? 'bg-gradient-to-r from-emerald-500/20 to-emerald-500/5 text-emerald-400 font-semibold border border-emerald-500/25 shadow-sm' : 'text-slate-300 hover:bg-slate-900 hover:text-white hover:translate-x-0.5' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                        <span>Workout &amp; Diet Plans</span>
                    </a>
                    @endcanany

                    @canany(['equipment.view', 'inventory.view'])
                    <a href="{{ route('admin.inventory.index') }}"
                       class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('admin.inventory.*') ? 'bg-gradient-to-r from-emerald-500/20 to-emerald-500/5 text-emerald-400 font-semibold border border-emerald-500/25 shadow-sm' : 'text-slate-300 hover:bg-slate-900 hover:text-white hover:translate-x-0.5' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        <span>Equipment &amp; Inventory</span>
                    </a>
                    @endcanany
                </div>
            </div>

            <div>
                <div class="px-3 mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-500">Insights &amp; Config</div>
                <div class="space-y-1">
                    @can('reports.view')
                    <a href="{{ route('admin.reports.index') }}"
                       class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('admin.reports.*') ? 'bg-gradient-to-r from-emerald-500/20 to-emerald-500/5 text-emerald-400 font-semibold border border-emerald-500/25 shadow-sm' : 'text-slate-300 hover:bg-slate-900 hover:text-white hover:translate-x-0.5' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        <span>Reports &amp; Exports</span>
                    </a>
                    @endcan

                    @can('settings.manage')
                    <a href="{{ route('admin.settings.index') }}"
                       class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('admin.settings.*') ? 'bg-gradient-to-r from-emerald-500/20 to-emerald-500/5 text-emerald-400 font-semibold border border-emerald-500/25 shadow-sm' : 'text-slate-300 hover:bg-slate-900 hover:text-white hover:translate-x-0.5' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Settings &amp; Gateways</span>
                    </a>
                    @endcan
                </div>
            </div>
        </nav>

        {{-- Footer Role Info --}}
        <div class="p-4 border-t border-slate-800/80 bg-slate-900/50 text-xs">
            <div class="flex items-center justify-between gap-2">
                <span class="truncate font-semibold text-slate-200">{{ $user->name }}</span>
                <span class="px-2 py-0.5 rounded-full bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 font-semibold text-[10px] shrink-0">{{ $roleLabel }}</span>
            </div>
        </div>
    </aside>

    {{-- Main Content --}}
    <div class="flex-1 flex flex-col min-w-0">
        {{-- Frosted Sticky Top Bar --}}
        <header class="h-16 glass-header border-b border-slate-200/80 flex items-center justify-between px-4 sm:px-6 sticky top-0 z-20">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-xl text-slate-600 hover:bg-slate-100 btn-press">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="text-lg font-bold tracking-tight text-slate-900 truncate">@yield('header', 'Dashboard')</h1>
            </div>

            <div class="flex items-center gap-3">
                {{-- Branch Switcher --}}
                @if ($user->isSuperAdmin() || $accessibleBranches->count() > 1)
                    <form method="POST" action="{{ route('admin.switch-branch') }}" class="flex items-center">
                        @csrf
                        <label for="branch_switcher" class="sr-only">Active Branch</label>
                        <select id="branch_switcher"
                                name="branch_id"
                                onchange="this.form.submit()"
                                class="text-xs font-semibold rounded-xl border-slate-200 bg-slate-100/80 text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 py-2 pl-3 pr-8 transition">
                            @if ($user->isSuperAdmin())
                                <option value="all" @selected($currentBranch === null)>All Branches ({{ $accessibleBranches->count() }})</option>
                            @endif
                            @foreach ($accessibleBranches as $branch)
                                <option value="{{ $branch->id }}" @selected($currentBranch?->id === $branch->id)>
                                    {{ $branch->name }} ({{ $branch->code }})
                                </option>
                            @endforeach
                        </select>
                    </form>
                @elseif ($currentBranch)
                    <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse-glow"></span>
                        {{ $currentBranch->name }}
                    </span>
                @endif

                {{-- User Dropdown --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                            class="flex items-center gap-2 text-sm font-semibold text-slate-700 hover:text-slate-900 px-2.5 py-1.5 rounded-xl hover:bg-slate-100 transition">
                        <span class="w-8 h-8 rounded-xl bg-gradient-to-br from-slate-800 to-slate-950 text-emerald-400 flex items-center justify-center font-bold text-xs shadow-sm">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </span>
                        <span class="hidden md:inline">{{ $user->name }}</span>
                        <svg class="w-4 h-4 text-slate-400 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="open"
                         @click.outside="open = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         style="display: none;"
                         class="absolute right-0 mt-2 w-48 bg-white rounded-2xl shadow-xl border border-slate-200/80 py-1.5 z-30">
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">Profile Settings</a>
                        <a href="{{ route('portal.home') }}" class="block px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">Member Portal View</a>
                        <hr class="my-1 border-slate-100">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50">Log Out</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- Floating Auto-Dismissing Toast Notifications --}}
        @if (session('success') || session('error'))
            <div x-data="{ show: true }"
                 x-init="setTimeout(() => show = false, 4500)"
                 x-show="show"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="fixed bottom-5 right-5 z-50 max-w-sm">
                <div class="rounded-2xl shadow-xl border px-4 py-3.5 text-sm font-medium flex items-center gap-3 {{ session('success') ? 'bg-slate-900 text-white border-emerald-500/40' : 'bg-rose-600 text-white border-rose-400' }}">
                    <span class="w-2.5 h-2.5 rounded-full shrink-0 {{ session('success') ? 'bg-emerald-400 animate-pulse-glow' : 'bg-white' }}"></span>
                    <span>{{ session('success') ?? session('error') }}</span>
                    <button @click="show = false" class="ml-2 opacity-70 hover:opacity-100 text-xs">✕</button>
                </div>
            </div>
        @endif

        {{-- Page Content --}}
        <main class="flex-1 px-4 sm:px-6 py-6 animate-fade-in-up">
            @yield('content')
            {{ $slot ?? '' }}
        </main>
    </div>
</div>

@livewireScripts
</body>
</html>
