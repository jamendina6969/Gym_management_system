<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user()->load(['member.homeBranch', 'member.activeSubscription.plan']);
        $activeBranches = Branch::query()->active()->orderBy('name')->get();

        return view('portal.home', [
            'user' => $user,
            'member' => $user->member,
            'activeSubscription' => $user->member?->activeSubscription,
            'activeBranches' => $activeBranches,
            'currencySymbol' => config('app.currency_symbol', '₱'),
        ]);
    }
}
