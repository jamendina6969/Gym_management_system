<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BranchSwitchController extends Controller
{
    public function __invoke(Request $request, BranchContext $branchContext): RedirectResponse
    {
        $branchId = $request->input('branch_id');
        $branchId = ($branchId === '' || $branchId === null || $branchId === 'all')
            ? null
            : (int) $branchId;

        if (! $branchContext->setBranch($branchId, $request->user())) {
            return back()->with('error', 'You do not have access to that branch.');
        }

        return back()->with('success', $branchId ? 'Switched branch.' : 'Viewing all branches.');
    }
}
