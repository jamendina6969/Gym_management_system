<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
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
        $memberCount = User::role('member')->count();

        return view('admin.dashboard', [
            'currentBranch' => $currentBranch,
            'scopedBranchIds' => $scopedBranchIds,
            'stats' => [
                'branches' => $branchCount,
                'staff' => $staffCount,
                'members' => $memberCount,
                'checkins_today' => 0,
                'revenue_month' => 0,
            ],
        ]);
    }
}
