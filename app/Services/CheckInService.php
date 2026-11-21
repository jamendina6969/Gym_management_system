<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Member;
use App\Models\User;

class CheckInService
{
    public function resolveMember(string $identifier): ?Member
    {
        $trimmed = trim($identifier);
        if ($trimmed === '') {
            return null;
        }

        return Member::query()
            ->with(['user', 'homeBranch', 'activeSubscription.plan'])
            ->where('qr_token', $trimmed)
            ->orWhere('member_code', strtoupper($trimmed))
            ->first();
    }

    /**
     * @return array{allowed: bool, message: string, attendance: Attendance, member: Member, duplicate?: bool}
     */
    public function attemptCheckIn(Member $member, Branch $branch, ?User $staff = null, string $method = 'qr'): array
    {
        $member->loadMissing(['user', 'homeBranch', 'activeSubscription.plan']);

        // Prevent duplicate accidental double-scans within 2 minutes
        $recent = Attendance::query()
            ->where('member_id', $member->id)
            ->where('branch_id', $branch->id)
            ->where('status', 'allowed')
            ->where('checked_in_at', '>=', now()->subMinutes(2))
            ->latest('checked_in_at')
            ->first();

        if ($recent) {
            return [
                'allowed' => true,
                'duplicate' => true,
                'message' => $member->user->name . ' already checked in moments ago.',
                'attendance' => $recent,
                'member' => $member,
            ];
        }

        $denialReason = $this->validateAccess($member, $branch);

        $attendance = Attendance::create([
            'branch_id' => $branch->id,
            'member_id' => $member->id,
            'checked_in_at' => now(),
            'method' => $method,
            'status' => $denialReason === null ? 'allowed' : 'denied',
            'denial_reason' => $denialReason,
            'checked_in_by' => $staff?->id,
        ]);

        return [
            'allowed' => $denialReason === null,
            'message' => $denialReason ?? ('Welcome, ' . $member->user->name . '! Check-in recorded.'),
            'attendance' => $attendance,
            'member' => $member,
        ];
    }

    protected function validateAccess(Member $member, Branch $branch): ?string
    {
        if ($member->status === 'pending') {
            return 'Account is pending approval or payment verification.';
        }

        if ($member->status === 'frozen') {
            return 'Membership is currently frozen.';
        }

        if ($member->status === 'suspended') {
            return 'Account is suspended. Please contact management.';
        }

        $subscription = $member->activeSubscription;
        if (! $subscription || $subscription->ends_at->isPast()) {
            return 'Membership has expired or no active plan found.';
        }

        if ($subscription->plan->access_scope !== 'all_access') {
            $allowedBranchId = $subscription->branch_id ?? $member->home_branch_id;
            if ((int) $allowedBranchId !== (int) $branch->id) {
                $homeName = $member->homeBranch?->name ?? 'their home branch';

                return "Single-branch plan is restricted to {$homeName}.";
            }
        }

        return null;
    }

    public function checkOut(Attendance $attendance): void
    {
        if ($attendance->checked_out_at === null) {
            $attendance->update(['checked_out_at' => now()]);
        }
    }
}
