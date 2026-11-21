<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\ClassSchedule;
use App\Models\Member;
use App\Models\Payment;
use App\Models\ProductSale;
use App\Models\Subscription;
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

        $membershipRevenueMonth = (float) Payment::query()
            ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereIn('branch_id', $scopedBranchIds))
            ->where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');

        $retailRevenueMonth = (float) ProductSale::query()
            ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereIn('branch_id', $scopedBranchIds))
            ->whereMonth('sold_at', now()->month)
            ->whereYear('sold_at', now()->year)
            ->sum('total_amount');

        $pendingPayments = Payment::query()
            ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereIn('branch_id', $scopedBranchIds))
            ->where('status', 'pending')
            ->count();

        $checkinsToday = Attendance::query()
            ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereIn('branch_id', $scopedBranchIds))
            ->whereDate('checked_in_at', now()->toDateString())
            ->where('status', 'allowed')
            ->count();

        $expiringSoon = Subscription::query()
            ->with(['member.user', 'plan'])
            ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereIn('branch_id', $scopedBranchIds))
            ->where('status', 'active')
            ->whereBetween('ends_at', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->orderBy('ends_at')
            ->limit(8)
            ->get();

        $upcomingClasses = ClassSchedule::query()
            ->with(['gymClass', 'trainer.user'])
            ->withCount('confirmedBookings')
            ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereIn('branch_id', $scopedBranchIds))
            ->where('status', 'scheduled')
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->limit(5)
            ->get();

        // 6-month revenue chart data
        $revenueLabels = [];
        $revenueValues = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $revenueLabels[] = $month->format('M Y');

            $paySum = (float) Payment::query()
                ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereIn('branch_id', $scopedBranchIds))
                ->where('status', 'paid')
                ->whereYear('paid_at', $month->year)
                ->whereMonth('paid_at', $month->month)
                ->sum('amount');

            $saleSum = (float) ProductSale::query()
                ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereIn('branch_id', $scopedBranchIds))
                ->whereYear('sold_at', $month->year)
                ->whereMonth('sold_at', $month->month)
                ->sum('total_amount');

            $revenueValues[] = round($paySum + $saleSum, 2);
        }

        // 7-day check-in chart data
        $checkinLabels = [];
        $checkinValues = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $checkinLabels[] = $day->format('D');
            $checkinValues[] = Attendance::query()
                ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereIn('branch_id', $scopedBranchIds))
                ->whereDate('checked_in_at', $day->toDateString())
                ->where('status', 'allowed')
                ->count();
        }

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
                'revenue_month' => $membershipRevenueMonth + $retailRevenueMonth,
                'expiring_count' => $expiringSoon->count(),
            ],
            'expiringSoon' => $expiringSoon,
            'upcomingClasses' => $upcomingClasses,
            'chartData' => [
                'revenueLabels' => $revenueLabels,
                'revenueValues' => $revenueValues,
                'checkinLabels' => $checkinLabels,
                'checkinValues' => $checkinValues,
            ],
        ]);
    }
}
