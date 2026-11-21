<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrPassController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $member = $user->member;

        if (! $member) {
            $member = Member::create([
                'user_id' => $user->id,
                'home_branch_id' => Branch::active()->value('id'),
                'status' => 'pending',
            ]);
        }

        $member->load(['homeBranch', 'activeSubscription.plan']);

        $qrSvg = QrCode::format('svg')
            ->size(220)
            ->margin(1)
            ->generate($member->qr_token);

        $recentAttendances = $member->attendances()
            ->with('branch')
            ->limit(15)
            ->get();

        return view('portal.qr-pass', [
            'member' => $member,
            'activeSubscription' => $member->activeSubscription,
            'qrSvg' => $qrSvg,
            'recentAttendances' => $recentAttendances,
        ]);
    }
}
