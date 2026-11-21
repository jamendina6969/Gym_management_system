@extends('layouts.admin')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('content')
<div class="space-y-6">
    {{-- Hero Scope & Quick Actions Banner --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 text-white rounded-2xl p-6 shadow-lg border border-slate-800 animate-fade-in-up">
        <div class="pointer-events-none absolute -right-12 -top-12 w-56 h-56 rounded-full bg-emerald-500/20 blur-3xl"></div>
        <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-widest text-emerald-400">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse-glow"></span>
                    Active Scope
                </div>
                <h2 class="text-2xl font-extrabold tracking-tight mt-1">
                    {{ $currentBranch ? $currentBranch->name . ' (' . $currentBranch->code . ')' : 'All Branches (Consolidated)' }}
                </h2>
                <p class="text-xs sm:text-sm text-slate-400 mt-1">
                    {{ $currentBranch && $currentBranch->address ? $currentBranch->address : 'Combined real-time performance across ' . $stats['branches'] . ' active ' . str('branch')->plural($stats['branches']) . '.' }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                @can('attendance.checkin')
                    <a href="{{ route('admin.attendance.index') }}" class="px-4 py-2.5 rounded-xl bg-emerald-500 text-slate-950 text-xs font-bold hover:bg-emerald-400 shadow-lg shadow-emerald-500/25 btn-press">
                        QR Check-in Scanner
                    </a>
                @endcan
                @can('members.view')
                    <a href="{{ route('admin.members.index') }}" class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/15 text-white text-xs font-semibold border border-white/15 btn-press">
                        + Members
                    </a>
                @endcan
                @can('payments.view')
                    <a href="{{ route('admin.payments.index') }}" class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/15 text-white text-xs font-semibold border border-white/15 btn-press">
                        Billing
                    </a>
                @endcan
                @can('reports.view')
                    <a href="{{ route('admin.reports.index') }}" class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/15 text-white text-xs font-semibold border border-white/15 btn-press">
                        Reports
                    </a>
                @endcan
            </div>
        </div>
    </div>

    {{-- Animated KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm card-hover animate-fade-in-up stagger-1">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Members</span>
                <span class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </span>
            </div>
            <div class="mt-3 text-3xl font-extrabold text-slate-900" x-data="countUp({{ (int) $stats['members'] }})" x-text="display">{{ $stats['members'] }}</div>
            <div class="mt-1.5 text-xs {{ ($stats['pending_members'] ?? 0) > 0 ? 'text-amber-600 font-semibold' : 'text-slate-500' }}">
                {{ $stats['pending_members'] ?? 0 }} pending approval
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm card-hover animate-fade-in-up stagger-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Revenue (Month)</span>
                <span class="w-9 h-9 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center font-bold text-sm">
                    {{ config('app.currency_symbol', '₱') }}
                </span>
            </div>
            <div class="mt-3 text-3xl font-extrabold text-slate-900" x-data="countUp({{ (float) $stats['revenue_month'] }}, 2, '{{ config('app.currency_symbol', '₱') }}')" x-text="display">{{ config('app.currency_symbol', '₱') }}{{ number_format($stats['revenue_month'], 2) }}</div>
            <div class="mt-1.5 text-xs {{ ($stats['pending_payments'] ?? 0) > 0 ? 'text-amber-600 font-semibold' : 'text-slate-500' }}">
                {{ $stats['pending_payments'] ?? 0 }} payments pending review
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm card-hover animate-fade-in-up stagger-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Check-ins Today</span>
                <span class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="mt-3 text-3xl font-extrabold text-emerald-600" x-data="countUp({{ (int) $stats['checkins_today'] }})" x-text="display">{{ $stats['checkins_today'] }}</div>
            <div class="mt-1.5 text-xs text-slate-500">Allowed front-desk entries</div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm card-hover animate-fade-in-up stagger-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Expiring (7 Days)</span>
                <span class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="mt-3 text-3xl font-extrabold {{ ($stats['expiring_count'] ?? 0) > 0 ? 'text-amber-600' : 'text-slate-900' }}" x-data="countUp({{ (int) ($stats['expiring_count'] ?? 0) }})" x-text="display">{{ $stats['expiring_count'] ?? 0 }}</div>
            <div class="mt-1.5 text-xs text-slate-500">Subscriptions due for renewal</div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm card-hover animate-fade-in-up stagger-2">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-slate-900">Revenue Trend (Last 6 Months)</h3>
                <span class="text-[11px] font-mono text-slate-400">{{ config('app.currency', 'PHP') }}</span>
            </div>
            <div class="h-60"><canvas id="revenueChart"></canvas></div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm card-hover animate-fade-in-up stagger-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-slate-900">Daily Check-ins (Last 7 Days)</h3>
                <span class="text-[11px] font-semibold text-emerald-600">Live Attendance</span>
            </div>
            <div class="h-60"><canvas id="checkinChart"></canvas></div>
        </div>
    </div>

    {{-- Bottom Grids --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden animate-fade-in-up stagger-3">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">Memberships Expiring Soon</h3>
                <span class="text-[11px] text-slate-400">Next 7 days</span>
            </div>
            <div class="divide-y divide-slate-100 text-xs">
                @forelse ($expiringSoon as $sub)
                    <div class="px-5 py-3.5 flex items-center justify-between hover:bg-slate-50/80 transition">
                        <div>
                            <a href="{{ route('admin.members.show', $sub->member) }}" class="font-semibold text-slate-900 hover:text-emerald-700">{{ $sub->member->user->name }}</a>
                            <div class="text-slate-500">{{ $sub->plan->name }}</div>
                        </div>
                        <span class="px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 font-semibold">
                            Ends {{ $sub->ends_at->format('M d') }} ({{ $sub->daysRemaining() }}d)
                        </span>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-slate-400">No memberships expiring in the next 7 days.</div>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden animate-fade-in-up stagger-4">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">Upcoming Class Occupancy</h3>
                <a href="{{ route('admin.classes.index') }}" class="text-[11px] font-semibold text-emerald-600 hover:underline">Manage →</a>
            </div>
            <div class="divide-y divide-slate-100 text-xs">
                @forelse ($upcomingClasses as $session)
                    @php $pct = $session->capacity > 0 ? min(100, round(($session->confirmed_bookings_count / $session->capacity) * 100)) : 0; @endphp
                    <div class="px-5 py-3.5 space-y-2 hover:bg-slate-50/80 transition">
                        <div class="flex justify-between">
                            <span class="font-semibold text-slate-900">{{ $session->gymClass->name }} <span class="font-normal text-slate-500">· {{ $session->starts_at->format('D h:i A') }}</span></span>
                            <span class="font-mono font-semibold text-slate-700">{{ $session->confirmed_bookings_count }}/{{ $session->capacity }}</span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700 {{ $pct >= 90 ? 'bg-amber-500' : 'bg-gradient-to-r from-emerald-500 to-teal-400' }}" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-slate-400">No upcoming classes scheduled.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof Chart === 'undefined') return;
    const data = @json($chartData);

    const revCtx = document.getElementById('revenueChart').getContext('2d');
    const revGrad = revCtx.createLinearGradient(0, 0, 0, 240);
    revGrad.addColorStop(0, '#10b981');
    revGrad.addColorStop(1, '#0d9488');

    new Chart(revCtx, {
        type: 'bar',
        data: {
            labels: data.revenueLabels,
            datasets: [{
                label: 'Revenue ({{ config("app.currency_symbol", "₱") }})',
                data: data.revenueValues,
                backgroundColor: revGrad,
                borderRadius: 8,
                maxBarThickness: 38
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 700, easing: 'easeOutQuart' },
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                x: { grid: { display: false } }
            }
        }
    });

    const chkCtx = document.getElementById('checkinChart').getContext('2d');
    const chkGrad = chkCtx.createLinearGradient(0, 0, 0, 240);
    chkGrad.addColorStop(0, 'rgba(16, 185, 129, 0.30)');
    chkGrad.addColorStop(1, 'rgba(16, 185, 129, 0.01)');

    new Chart(chkCtx, {
        type: 'line',
        data: {
            labels: data.checkinLabels,
            datasets: [{
                label: 'Check-ins',
                data: data.checkinValues,
                borderColor: '#10b981',
                borderWidth: 2.5,
                pointBackgroundColor: '#0f172a',
                pointRadius: 4,
                pointHoverRadius: 6,
                backgroundColor: chkGrad,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 750, easing: 'easeOutQuart' },
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f1f5f9' } },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>
@endsection
