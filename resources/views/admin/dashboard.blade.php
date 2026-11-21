@extends('layouts.admin')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('content')
<div class="space-y-6">
    {{-- Branch Scope Banner --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 shadow-sm">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wider text-emerald-600">Active Scope</div>
            <h2 class="text-xl font-bold text-slate-900 mt-0.5">
                {{ $currentBranch ? $currentBranch->name . ' (' . $currentBranch->code . ')' : 'All Branches (Consolidated)' }}
            </h2>
            <p class="text-sm text-slate-500 mt-0.5">
                {{ $currentBranch && $currentBranch->address ? $currentBranch->address : 'Combined metrics across ' . $stats['branches'] . ' active ' . str('branch')->plural($stats['branches']) . '.' }}
            </p>
        </div>
        <div class="flex gap-2">
            @can('attendance.checkin')
                <a href="{{ route('admin.attendance.index') }}" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700 transition">QR Check-in</a>
            @endcan
            @can('reports.view')
                <a href="{{ route('admin.reports.index') }}" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800 transition">View Reports</a>
            @endcan
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Active Members</div>
            <div class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['members'] }}</div>
            <div class="mt-1 text-xs {{ ($stats['pending_members'] ?? 0) > 0 ? 'text-amber-600 font-medium' : 'text-slate-500' }}">
                {{ $stats['pending_members'] ?? 0 }} pending approval
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Revenue (This Month)</div>
            <div class="mt-2 text-3xl font-bold text-slate-900">{{ config('app.currency_symbol', '₱') }}{{ number_format($stats['revenue_month'], 2) }}</div>
            <div class="mt-1 text-xs {{ ($stats['pending_payments'] ?? 0) > 0 ? 'text-amber-600 font-medium' : 'text-slate-500' }}">
                {{ $stats['pending_payments'] ?? 0 }} payments pending review
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Check-ins Today</div>
            <div class="mt-2 text-3xl font-bold text-emerald-600">{{ $stats['checkins_today'] }}</div>
            <div class="mt-1 text-xs text-slate-500">Allowed front-desk entries</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Expiring (7 Days)</div>
            <div class="mt-2 text-3xl font-bold {{ ($stats['expiring_count'] ?? 0) > 0 ? 'text-amber-600' : 'text-slate-900' }}">{{ $stats['expiring_count'] ?? 0 }}</div>
            <div class="mt-1 text-xs text-slate-500">Subscriptions due for renewal</div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900 mb-3">Revenue Trend (Last 6 Months)</h3>
            <div class="h-56"><canvas id="revenueChart"></canvas></div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900 mb-3">Daily Check-ins (Last 7 Days)</h3>
            <div class="h-56"><canvas id="checkinChart"></canvas></div>
        </div>
    </div>

    {{-- Bottom Grids: Expiring Memberships & Upcoming Classes --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-900">Memberships Expiring Soon</h3>
            </div>
            <div class="divide-y divide-slate-100 text-xs">
                @forelse ($expiringSoon as $sub)
                    <div class="px-5 py-3 flex items-center justify-between">
                        <div>
                            <a href="{{ route('admin.members.show', $sub->member) }}" class="font-semibold text-slate-900 hover:text-emerald-700">{{ $sub->member->user->name }}</a>
                            <div class="text-slate-500">{{ $sub->plan->name }}</div>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 font-semibold">
                            Ends {{ $sub->ends_at->format('M d') }} ({{ $sub->daysRemaining() }}d)
                        </span>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-slate-500">No memberships expiring in the next 7 days.</div>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-900">Upcoming Class Occupancy</h3>
            </div>
            <div class="divide-y divide-slate-100 text-xs">
                @forelse ($upcomingClasses as $session)
                    @php $pct = $session->capacity > 0 ? min(100, round(($session->confirmed_bookings_count / $session->capacity) * 100)) : 0; @endphp
                    <div class="px-5 py-3 space-y-1.5">
                        <div class="flex justify-between">
                            <span class="font-semibold text-slate-900">{{ $session->gymClass->name }} <span class="font-normal text-slate-500">· {{ $session->starts_at->format('D h:i A') }}</span></span>
                            <span class="font-mono text-slate-700">{{ $session->confirmed_bookings_count }}/{{ $session->capacity }}</span>
                        </div>
                        <div class="w-full h-1.5 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full {{ $pct >= 90 ? 'bg-amber-500' : 'bg-emerald-500' }}" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-slate-500">No upcoming classes scheduled.</div>
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

    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: data.revenueLabels,
            datasets: [{
                label: 'Revenue ({{ config("app.currency_symbol", "₱") }})',
                data: data.revenueValues,
                backgroundColor: '#10b981',
                borderRadius: 6
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    new Chart(document.getElementById('checkinChart'), {
        type: 'line',
        data: {
            labels: data.checkinLabels,
            datasets: [{
                label: 'Check-ins',
                data: data.checkinValues,
                borderColor: '#0f172a',
                backgroundColor: 'rgba(16, 185, 129, 0.15)',
                fill: true,
                tension: 0.35
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
});
</script>
@endsection
