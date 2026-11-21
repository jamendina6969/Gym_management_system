@extends('layouts.member')

@section('title', 'My Portal')

@section('content')
<div class="space-y-6">
    {{-- Welcome Card --}}
    <div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white rounded-2xl p-6 shadow-md">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-emerald-400 text-xs font-semibold uppercase tracking-wider">Member Portal</p>
                <h1 class="text-2xl font-bold mt-1">Welcome back, {{ $user->name }}</h1>
                <p class="text-slate-300 text-sm mt-1">Manage your membership, QR check-in pass, classes, and workout plans.</p>
            </div>
            <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white/10 border border-white/15 text-xs">
                @if ($activeSubscription)
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span>{{ $activeSubscription->plan->name }} · {{ $activeSubscription->daysRemaining() }}d left</span>
                @else
                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                    <span>Status: {{ ucfirst($member?->status ?? 'pending') }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Quick Modules --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <a href="{{ route('portal.membership') }}" class="block bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:border-emerald-400 transition">
            <div class="text-xs font-semibold uppercase tracking-wider text-emerald-600">Membership</div>
            <div class="mt-2 text-lg font-semibold text-slate-900">Plan &amp; Renewals →</div>
            <p class="text-xs text-slate-500 mt-1">View active subscription, choose a plan, upload payment proof, and download PDF receipts.</p>
        </a>

        <a href="{{ route('portal.qr-pass') }}" class="block bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:border-emerald-400 transition">
            <div class="text-xs font-semibold uppercase tracking-wider text-emerald-600">Check-in</div>
            <div class="mt-2 text-lg font-semibold text-slate-900">Personal QR Pass →</div>
            <p class="text-xs text-slate-500 mt-1">Scan at the front desk for instant gym entry and view your attendance history.</p>
        </a>

        <a href="{{ route('portal.classes') }}" class="block bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:border-emerald-400 transition">
            <div class="text-xs font-semibold uppercase tracking-wider text-emerald-600">Training</div>
            <div class="mt-2 text-lg font-semibold text-slate-900">Classes &amp; Bookings →</div>
            <p class="text-xs text-slate-500 mt-1">Book group sessions, manage waitlists, and track your class credits.</p>
        </a>
    </div>

    {{-- Active Branches --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <h2 class="text-sm font-semibold text-slate-900 mb-3">Our Gym Locations</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach ($activeBranches as $branch)
                <div class="p-4 rounded-lg bg-slate-50 border border-slate-200">
                    <div class="font-semibold text-slate-900 text-sm">{{ $branch->name }}</div>
                    @if ($branch->address)
                        <div class="text-xs text-slate-600 mt-1">{{ $branch->address }}</div>
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
