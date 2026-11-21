@extends('layouts.member')

@section('title', 'My QR Pass')

@section('content')
<div class="space-y-6">
    {{-- Digital Pass Card --}}
    <div class="max-w-md mx-auto bg-white rounded-3xl border border-slate-200 shadow-md overflow-hidden">
        <div class="bg-slate-900 text-white px-6 py-5 flex items-center justify-between">
            <div>
                <div class="text-[11px] uppercase tracking-wider text-emerald-400 font-semibold">Digital Gym Pass</div>
                <div class="text-lg font-bold mt-0.5">{{ $member->user->name }}</div>
            </div>
            <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $member->status === 'active' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-amber-500/20 text-amber-300' }}">
                {{ ucfirst($member->status) }}
            </span>
        </div>

        <div class="p-6 flex flex-col items-center text-center">
            <div class="p-4 bg-white rounded-2xl border-2 border-slate-100 shadow-inner">
                {!! $qrSvg !!}
            </div>

            <div class="mt-4 font-mono text-base font-bold text-slate-900 tracking-wider">
                {{ $member->member_code }}
            </div>
            <div class="text-xs text-slate-500 mt-1">
                Home Branch: {{ $member->homeBranch?->name ?? 'Unassigned' }}
            </div>

            @if ($activeSubscription)
                <div class="mt-4 w-full rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-xs text-emerald-900">
                    <div class="font-semibold">{{ $activeSubscription->plan->name }}</div>
                    <div class="mt-0.5">Valid until {{ $activeSubscription->ends_at->format('M d, Y') }} ({{ $activeSubscription->daysRemaining() }} days left)</div>
                </div>
            @else
                <div class="mt-4 w-full rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-xs text-amber-900">
                    No active membership plan. <a href="{{ route('portal.membership') }}" class="underline font-semibold">Activate or renew →</a>
                </div>
            @endif
        </div>
    </div>

    {{-- Attendance History --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="font-semibold text-slate-900 text-sm">Recent Gym Visits</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-2.5 text-left">Date &amp; Time</th>
                        <th class="px-4 py-2.5 text-left">Branch</th>
                        <th class="px-4 py-2.5 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($recentAttendances as $visit)
                        <tr>
                            <td class="px-4 py-3 text-slate-800 font-medium">
                                {{ $visit->checked_in_at->format('M d, Y · h:i A') }}
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $visit->branch?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded-full font-medium {{ $visit->status === 'allowed' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-700' }}">
                                    {{ ucfirst($visit->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-slate-500">No check-ins recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
