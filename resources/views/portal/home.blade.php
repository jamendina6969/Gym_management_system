@extends('layouts.member')

@section('title', 'My Portal')

@section('content')
<div class="space-y-6">
    {{-- Athletic Hero Welcome Card --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 text-white rounded-3xl p-6 sm:p-8 shadow-xl border border-slate-800 animate-fade-in-up">
        <div class="pointer-events-none absolute -right-10 -top-10 w-60 h-60 rounded-full bg-emerald-500/20 blur-3xl"></div>
        <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-emerald-400 text-xs font-bold uppercase tracking-widest">Member Portal</p>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight mt-1">Welcome back, {{ $user->name }}</h1>
                <p class="text-slate-400 text-sm mt-1">Your membership, QR check-in pass, group classes, and personal plans in one place.</p>
            </div>
            <div class="inline-flex items-center gap-2.5 px-4 py-2.5 rounded-2xl bg-white/10 border border-white/15 text-xs font-semibold backdrop-blur self-start sm:self-center">
                @if ($activeSubscription)
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse-glow"></span>
                    <span>{{ $activeSubscription->plan->name }} · {{ $activeSubscription->daysRemaining() }}d left</span>
                @else
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                    <span>Status: {{ ucfirst($member?->status ?? 'pending') }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Interactive Quick Access Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('portal.qr-pass') }}" class="group block bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm card-hover animate-fade-in-up stagger-1">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3 group-hover:scale-110 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            </div>
            <div class="font-bold text-slate-900">My QR Pass →</div>
            <p class="text-xs text-slate-500 mt-1">Scan at the front desk &amp; view visit history.</p>
        </a>

        <a href="{{ route('portal.membership') }}" class="group block bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm card-hover animate-fade-in-up stagger-2">
            <div class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center mb-3 font-bold group-hover:scale-110 transition">{{ $currencySymbol }}</div>
            <div class="font-bold text-slate-900">Membership &amp; Pay →</div>
            <p class="text-xs text-slate-500 mt-1">Renew online, upload proof, download PDF receipts.</p>
        </a>

        <a href="{{ route('portal.classes') }}" class="group block bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm card-hover animate-fade-in-up stagger-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-3 group-hover:scale-110 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div class="font-bold text-slate-900">Book Classes →</div>
            <p class="text-xs text-slate-500 mt-1">Reserve HIIT, Yoga &amp; manage waitlists.</p>
        </a>

        <a href="{{ route('portal.programs') }}" class="group block bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm card-hover animate-fade-in-up stagger-4">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center mb-3 group-hover:scale-110 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <div class="font-bold text-slate-900">Workout &amp; Diet →</div>
            <p class="text-xs text-slate-500 mt-1">Follow your coach's training &amp; macro plan.</p>
        </a>
    </div>

    {{-- Active Branches --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm animate-fade-in-up stagger-4">
        <h2 class="text-sm font-bold text-slate-900 mb-3">Our Gym Locations</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach ($activeBranches as $branch)
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 hover:border-emerald-300 transition">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-900 text-sm">{{ $branch->name }}</span>
                        <span class="px-2 py-0.5 rounded bg-slate-900 text-emerald-400 font-mono text-[10px] font-semibold">{{ $branch->code }}</span>
                    </div>
                    @if ($branch->address)
                        <div class="text-xs text-slate-600 mt-1.5">{{ $branch->address }}</div>
                    @endif
                    @if ($branch->opening_hours)
                        <div class="text-[11px] text-slate-500 mt-2">
                            Mon–Fri: {{ $branch->opening_hours['mon_fri'] ?? '—' }} · Sat: {{ $branch->opening_hours['sat'] ?? '—' }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
