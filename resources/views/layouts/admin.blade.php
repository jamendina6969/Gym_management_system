<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin') — {{ config('app.name', 'IronPulse Gym') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-slate-100 text-slate-900" x-data="{ sidebarOpen: false }">
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
         x-transition.opacity
         @click="sidebarOpen = false"
         class="fixed inset-0 z-30 bg-slate-900/60 lg:hidden"
         style="display: none;"></div>

    {{-- Sidebar --}}
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-40 w-64 bg-slate-900 text-slate-100 flex flex-col transition-transform duration-200 ease-in-out lg:translate-x-0 lg:static lg:inset-auto">
        {{-- Brand --}}
        <div class="h-16 flex items-center justify-between px-5 border-b border-slate-800">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-lg bg-emerald-500 text-slate-950 font-bold flex items-center justify-center text-lg shadow-sm">
                    IP
                </span>
                <div>
                    <div class="font-semibold text-sm tracking-tight text-white leading-tight">{{ config('app.name', 'IronPulse Gym') }}</div>
                    <div class="text-[11px] text-emerald-400 font-medium">Management Console</div>
                </div>
            </a>
            <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-6 text-sm">
            <div>
                <div class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Overview</div>
                <div class="space-y-1">
                    <a href="{{ route('admin.dashboard') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition {{ request()->routeIs('admin.dashboard') ? 'bg-emerald-500/15 text-emerald-400 font-medium' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span>Dashboard</span>
                    </a>

                    @can('branches.view')
                    <a href="{{ route('admin.branches.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition {{ request()->routeIs('admin.branches.*') ? 'bg-emerald-500/15 text-emerald-400 font-medium' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span>Branches</span>
                    </a>
                    @endcan
                </div>
            </div>

            <div>
                <div class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Operations</div>
                <div class="space-y-1">
                    @can('members.view')
                    <a href="{{ route('admin.members.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition {{ request()->routeIs('admin.members.*') ? 'bg-emerald-500/15 text-emerald-400 font-medium' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Members</span>
                    </a>
                    @endcan

                    @can('plans.view')
                    <a href="{{ route('admin.plans.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition {{ request()->routeIs('admin.plans.*') ? 'bg-emerald-500/15 text-emerald-400 font-medium' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Membership Plans</span>
                    </a>
                    @endcan

                    @can('payments.view')
                    <a href="{{ route('admin.payments.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition {{ request()->routeIs('admin.payments.*') ? 'bg-emerald-500/15 text-emerald-400 font-medium' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span>Payments &amp; Billing</span>
                    </a>
                    @endcan

                    @can('attendance.view')
                    <a href="{{ route('admin.attendance.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition {{ request()->routeIs('admin.attendance.*') ? 'bg-emerald-500/15 text-emerald-400 font-medium' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                        <span>QR Check-in</span>
                    </a>
                    @endcan

                    @can('classes.view')
                    <a href="{{ route('admin.classes.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition {{ request()->routeIs('admin.classes.*') ? 'bg-emerald-500/15 text-emerald-400 font-medium' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>Trainers &amp; Classes</span>
                    </a>
                    @endcan

                    @canany(['workouts.view', 'diets.view'])
                    <a href="{{ route('admin.programs.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition {{ request()->routeIs('admin.programs.*') ? 'bg-emerald-500/15 text-emerald-400 font-medium' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                        <span>Workout &amp; Diet Plans</span>
                    </a>
                    @endcanany

                    @foreach ([
                        ['label' => 'Equipment & Inventory', 'phase' => 'P6'],
                    ] as $item)
                        <div class="flex items-center justify-between px-3 py-2 rounded-lg text-slate-500 cursor-not-allowed" title="Coming in Phase {{ substr($item['phase'], 1) }}">
                            <span>{{ $item['label'] }}</span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-800 text-slate-400 font-mono">{{ $item['phase'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <div class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Insights & Config</div>
                <div class="space-y-1">
                    <div class="flex items-center justify-between px-3 py-2 rounded-lg text-slate-500 cursor-not-allowed">
                        <span>Reports & Exports</span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-800 text-slate-400 font-mono">P7</span>
                    </div>
                    <div class="flex items-center justify-between px-3 py-2 rounded-lg text-slate-500 cursor-not-allowed">
                        <span>Settings & Gateways</span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-800 text-slate-400 font-mono">P8</span>
                    </div>
                </div>
            </div>
        </nav>

        {{-- Footer Role Info --}}
        <div class="p-4 border-t border-slate-800 text-xs text-slate-400">
            <div class="flex items-center justify-between">
                <span class="truncate font-medium text-slate-200">{{ $user->name }}</span>
                <span class="px-2 py-0.5 rounded-full bg-slate-800 text-emerald-400 font-medium text-[11px]">{{ $roleLabel }}</span>
            </div>
        </div>
    </aside>

    {{-- Main Content --}}
    <div class="flex-1 flex flex-col min-w-0">
        {{-- Top Bar --}}
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 sm:px-6 sticky top-0 z-20">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="text-lg font-semibold text-slate-900 truncate">@yield('header', 'Dashboard')</h1>
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
                                class="text-sm rounded-lg border-slate-300 bg-slate-50 text-slate-700 focus:border-emerald-500 focus:ring-emerald-500 py-1.5 pl-3 pr-8">
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
                    <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        {{ $currentBranch->name }}
                    </span>
                @endif

                {{-- User Dropdown --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                            class="flex items-center gap-2 text-sm font-medium text-slate-700 hover:text-slate-900 px-2.5 py-1.5 rounded-lg hover:bg-slate-100">
                        <span class="w-8 h-8 rounded-full bg-slate-900 text-emerald-400 flex items-center justify-center font-semibold text-xs">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </span>
                        <span class="hidden md:inline">{{ $user->name }}</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="open"
                         @click.outside="open = false"
                         x-transition
                         style="display: none;"
                         class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-slate-200 py-1 z-30">
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Profile Settings</a>
                        <a href="{{ route('portal.home') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Member Portal View</a>
                        <hr class="my-1 border-slate-100">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-rose-600 hover:bg-rose-50">Log Out</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- Flash Messages --}}
        <div class="px-4 sm:px-6 pt-4">
            @if (session('success'))
                <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800 flex items-center justify-between">
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-800 flex items-center justify-between">
                    <span>{{ session('error') }}</span>
                </div>
            @endif
        </div>

        {{-- Page Content --}}
        <main class="flex-1 px-4 sm:px-6 pb-8">
            @yield('content')
            {{ $slot ?? '' }}
        </main>
    </div>
</div>

@livewireScripts
</body>
</html>
