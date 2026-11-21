@extends('layouts.admin')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('content')
<div class="space-y-6">
    {{-- Branch Context Banner --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 shadow-sm">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wider text-emerald-600">Active Scope</div>
            <h2 class="text-xl font-bold text-slate-900 mt-0.5">
                {{ $currentBranch ? $currentBranch->name . ' (' . $currentBranch->code . ')' : 'All Branches (Consolidated)' }}
            </h2>
            <p class="text-sm text-slate-500 mt-0.5">
                @if ($currentBranch && $currentBranch->address)
                    {{ $currentBranch->address }}
                @else
                    Viewing combined metrics across {{ $stats['branches'] }} active {{ str('branch')->plural($stats['branches']) }}.
                @endif
            </p>
        </div>
        @can('branches.view')
            <a href="{{ route('admin.branches.index') }}"
               class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-medium hover:bg-slate-800 transition">
                Manage Branches
            </a>
        @endcan
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Active Branches</div>
            <div class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['branches'] }}</div>
            <div class="mt-1 text-xs text-emerald-600 font-medium">Multi-branch ready</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Team & Trainers</div>
            <div class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['staff'] }}</div>
            <div class="mt-1 text-xs text-slate-500">Staff assigned in scope</div>
        </div>

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
    </div>

    {{-- Phase 1 Setup Checklist --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
        <h3 class="text-base font-semibold text-slate-900">System Foundation Ready</h3>
        <p class="text-sm text-slate-500 mt-1">Phase 1 core architecture is live. Upcoming modules will appear in the sidebar as each phase is completed.</p>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
            <div class="p-3.5 rounded-lg bg-emerald-50 border border-emerald-200">
                <div class="font-semibold text-emerald-900">Roles & Permissions</div>
                <div class="text-xs text-emerald-700 mt-1">Super Admin, Branch Manager, Staff, Trainer, Member</div>
            </div>
            <div class="p-3.5 rounded-lg bg-emerald-50 border border-emerald-200">
                <div class="font-semibold text-emerald-900">Multi-Branch Scoping</div>
                <div class="text-xs text-emerald-700 mt-1">Branch switcher & per-branch self-signup toggles</div>
            </div>
            <div class="p-3.5 rounded-lg bg-emerald-50 border border-emerald-200">
                <div class="font-semibold text-emerald-900">PH Locale Configured</div>
                <div class="text-xs text-emerald-700 mt-1">Currency: {{ config('app.currency') }} ({{ config('app.currency_symbol') }}) · {{ config('app.timezone') }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
