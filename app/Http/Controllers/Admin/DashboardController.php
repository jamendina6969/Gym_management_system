<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Member;
use App\Models\Payment;
use App\Models\User;
use App\Services\BranchContext;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(BranchContext $branchContext): View
    {
        $currentBranch = $branchContext->currentBranch();
        $scopedBranchIds = $branchContext->scopedBranchIds();

        $branchCount = Branch::query()->active()->count();
        $staffCount = User::role(['super-admin', 'branch-manager', 'staff', 'trainer'])
            ->when($currentBranch, fn ($q) => $q->whereHas('branches', fn ($bq) => $bq->where('branches.id', $currentBranch->id)))
            ->count();

        $activeMembers = Member::query()
            ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereIn('home_branch_id', $scopedBranchIds))
            ->where('status', 'active')
            ->count();

        $pendingMembers = Member::query()
            ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereIn('home_branch_id', $scopedBranchIds))
            ->where('status', 'pending')
            ->count();

        $revenueMonth = Payment::query()
            ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereIn('branch_id', $scopedBranchIds))
            ->where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');

        $pendingPayments = Payment::query()
            ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereIn('branch_id', $scopedBranchIds))
            ->where('status', 'pending')
            ->count();

        $checkinsToday = Attendance::query()
            ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereIn('branch_id', $scopedBranchIds))
            ->whereDate('checked_in_at', now()->toDateString())
            ->where('status', 'allowed')
            ->count();

        return view('admin.dashboard', [
            'currentBranch' => $currentBranch,
            'scopedBranchIds' => $scopedBranchIds,
            'stats' => [
                'branches' => $branchCount,
                'staff' => $staffCount,
                'members' => $activeMembers,
                'pending_members' => $pendingMembers,
                'pending_payments' => $pendingPayments,
                'checkins_today' => $checkinsToday,
                'revenue_month' => (float) $revenueMonth,
            ],
        ]);
    }
}
