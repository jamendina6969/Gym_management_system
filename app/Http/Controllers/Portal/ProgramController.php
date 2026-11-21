<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function __invoke(Request $request): View
    {
        $member = $request->user()->member;

        $assignments = $member
            ? $member->planAssignments()
                ->with(['workoutPlan.items', 'dietPlan.items', 'assigner'])
                ->get()
            : collect();

        $activeAssignment = $assignments->firstWhere('status', 'active');

        return view('portal.programs', [
            'activeAssignment' => $activeAssignment,
            'assignments' => $assignments,
        ]);
    }
}
