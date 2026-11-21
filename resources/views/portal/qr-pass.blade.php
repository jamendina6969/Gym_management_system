@extends('layouts.member')

@section('title', 'My QR Pass')

@section('content')
<div class="space-y-6">
    {{-- Holographic Shimmer Digital Pass Card --}}
    <div class="max-w-md mx-auto bg-white rounded-3xl border border-slate-200/80 shadow-xl overflow-hidden animate-scale-in">
        <div class="pass-shimmer text-white px-6 py-5 flex items-center justify-between">
            <div>
                <div class="text-[10px] uppercase tracking-widest text-emerald-400 font-bold">Digital Gym Pass</div>
                <div class="text-xl font-extrabold mt-0.5 tracking-tight">{{ $member->user->name }}</div>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold {{ $member->status === 'active' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-400/30' : 'bg-amber-500/20 text-amber-300 border border-amber-400/30' }}">
                <span class="w-2 h-2 rounded-full {{ $member->status === 'active' ? 'bg-emerald-400 animate-pulse-glow' : 'bg-amber-400' }}"></span>
                {{ ucfirst($member->status) }}
            </span>
        </div>

        <div class="p-7 flex flex-col items-center text-center">
            <div class="p-4 bg-white rounded-2xl border-2 border-emerald-100 shadow-lg hover:scale-[1.02] transition duration-200">
                {!! $qrSvg !!}
            </div>

            <div class="mt-4 font-mono text-lg font-extrabold text-slate-900 tracking-widest">
                {{ $member->member_code }}
            </div>
            <div class="text-xs text-slate-500 mt-1">
                Home Branch: <strong class="text-slate-700">{{ $member->homeBranch?->name ?? 'Unassigned' }}</strong>
            </div>

            @if ($activeSubscription)
                <div class="mt-5 w-full rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-xs text-emerald-900">
                    <div class="font-bold">{{ $activeSubscription->plan->name }}</div>
                    <div class="mt-0.5">Valid until {{ $activeSubscription->ends_at->format('M d, Y') }} ({{ $activeSubscription->daysRemaining() }} days left)</div>
                </div>
            @else
                <div class="mt-5 w-full rounded-2xl bg-amber-50 border border-amber-200 px-4 py-3 text-xs text-amber-900">
                    No active membership plan. <a href="{{ route('portal.membership') }}" class="underline font-bold">Activate or renew →</a>
                </div>
            @endif
        </div>
    </div>

    {{-- Attendance History --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden animate-fade-in-up stagger-2">
        <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="font-bold text-slate-900 text-sm">Recent Gym Visits</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase">
                    <tr>
                        <th class="px-5 py-3 text-left">Date &amp; Time</th>
                        <th class="px-5 py-3 text-left">Branch</th>
                        <th class="px-5 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($recentAttendances as $visit)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-5 py-3.5 text-slate-800 font-semibold">
                                {{ $visit->checked_in_at->format('M d, Y · h:i A') }}
                            </td>
                            <td class="px-5 py-3.5 text-slate-600">{{ $visit->branch?->name ?? '—' }}</td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="px-2.5 py-0.5 rounded-full font-semibold {{ $visit->status === 'allowed' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-700' }}">
                                    {{ ucfirst($visit->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-8 text-center text-slate-400">No check-ins recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
