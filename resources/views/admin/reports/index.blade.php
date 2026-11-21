@extends('layouts.admin')

@section('title', 'Reports & Exports')
@section('header', 'Reports & Exports')

@section('content')
<div class="space-y-6">
    {{-- Date Filter & Export Actions --}}
    <div class="bg-white rounded-xl border border-slate-200 p-4 flex flex-col lg:flex-row lg:items-end justify-between gap-4 shadow-sm">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-wrap items-end gap-3 text-sm">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">From</label>
                <input type="date" name="from" value="{{ $from }}" class="rounded-lg border-slate-300 text-xs">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">To</label>
                <input type="date" name="to" value="{{ $to }}" class="rounded-lg border-slate-300 text-xs">
            </div>
            <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800">Apply Filter</button>
        </form>

        @can('reports.export')
            <div class="flex gap-2">
                <a href="{{ route('admin.reports.export-csv', ['from' => $from, 'to' => $to]) }}"
                   class="px-3.5 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50">
                    Export CSV
                </a>
                <a href="{{ route('admin.reports.export-pdf', ['from' => $from, 'to' => $to]) }}"
                   class="px-3.5 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700">
                    Download PDF Report
                </a>
            </div>
        @endcan
    </div>

    {{-- Summary KPIs --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-xs text-slate-500 uppercase font-medium">Total Revenue</div>
            <div class="mt-2 text-2xl font-bold text-emerald-700">{{ $currencySymbol }}{{ number_format($totalRevenue, 2) }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Plans: {{ $currencySymbol }}{{ number_format($membershipRevenue, 2) }} · Retail: {{ $currencySymbol }}{{ number_format($retailRevenue, 2) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-xs text-slate-500 uppercase font-medium">New Members</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ $newMembers }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Joined in period</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-xs text-slate-500 uppercase font-medium">Total Check-ins</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ $checkins }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Allowed visits</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-xs text-slate-500 uppercase font-medium">Class Bookings</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ $bookingsCount }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Confirmed / attended</div>
        </div>
    </div>

    {{-- Revenue by Payment Method --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900 mb-3">Membership Revenue by Payment Method</h3>
        @if ($byMethod->isEmpty())
            <p class="text-xs text-slate-500">No paid transactions in this date range.</p>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                @foreach ($byMethod as $method => $amount)
                    <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200">
                        <div class="uppercase text-slate-500 font-semibold">{{ str_replace('_', ' ', $method) }}</div>
                        <div class="text-base font-bold text-slate-900 mt-1">{{ $currencySymbol }}{{ number_format($amount, 2) }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Detailed Paid Transactions --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="text-sm font-semibold text-slate-900">Paid Transactions ({{ $payments->count() }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase">
                    <tr>
                        <th class="px-5 py-3 text-left">Invoice</th>
                        <th class="px-5 py-3 text-left">Date</th>
                        <th class="px-5 py-3 text-left">Member</th>
                        <th class="px-5 py-3 text-left">Method</th>
                        <th class="px-5 py-3 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($payments as $p)
                        <tr>
                            <td class="px-5 py-3 font-mono font-semibold text-slate-900">{{ $p->invoice_number }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ ($p->paid_at ?? $p->created_at)->format('M d, Y') }}</td>
                            <td class="px-5 py-3 text-slate-800">{{ $p->member->user->name }}</td>
                            <td class="px-5 py-3 uppercase text-slate-600">{{ str_replace('_', ' ', $p->payment_method) }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-slate-900">{{ $currencySymbol }}{{ number_format((float) $p->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">No transactions found in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
