<?php

namespace App\Livewire\Admin\Attendance;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Member;
use App\Services\BranchContext;
use App\Services\CheckInService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class CheckInConsole extends Component
{
    use WithPagination;

    public ?int $activeBranchId = null;
    public string $scanInput = '';
    public string $memberSearch = '';
    public string $dateFilter = '';

    /** @var array{allowed: bool, message: string, member_name?: string, member_code?: string, plan_name?: string, days_left?: int}|null */
    public ?array $lastResult = null;

    public function mount(BranchContext $branchContext): void
    {
        $this->activeBranchId = $branchContext->currentBranchId()
            ?? $branchContext->accessibleBranches()->first()?->id;
        $this->dateFilter = now()->toDateString();
    }

    public function scanCode(?string $code = null, string $method = 'qr'): void
    {
        abort_unless(Auth::user()->can('attendance.checkin'), 403);

        $identifier = trim($code ?? $this->scanInput);
        $this->scanInput = '';

        if ($identifier === '') {
            return;
        }

        $branch = $this->resolveActiveBranch();
        if (! $branch) {
            $this->lastResult = [
                'allowed' => false,
                'message' => 'Please select a branch before scanning.',
            ];

            return;
        }

        $service = app(CheckInService::class);
        $member = $service->resolveMember($identifier);

        if (! $member) {
            $this->lastResult = [
                'allowed' => false,
                'message' => "No member found for code \"{$identifier}\".",
            ];

            return;
        }

        $this->processCheckIn($member, $branch, $method);
    }

    public function manualCheckIn(int $memberId): void
    {
        abort_unless(Auth::user()->can('attendance.checkin'), 403);

        $branch = $this->resolveActiveBranch();
        if (! $branch) {
            return;
        }

        $member = Member::findOrFail($memberId);
        $this->processCheckIn($member, $branch, 'manual');
        $this->memberSearch = '';
    }

    public function checkOut(int $attendanceId): void
    {
        abort_unless(Auth::user()->can('attendance.checkin'), 403);

        $attendance = Attendance::findOrFail($attendanceId);
        app(CheckInService::class)->checkOut($attendance);
    }

    protected function processCheckIn(Member $member, Branch $branch, string $method): void
    {
        $result = app(CheckInService::class)->attemptCheckIn($member, $branch, Auth::user(), $method);
        $sub = $member->activeSubscription;

        $this->lastResult = [
            'allowed' => $result['allowed'],
            'message' => $result['message'],
            'member_name' => $member->user->name,
            'member_code' => $member->member_code,
            'plan_name' => $sub?->plan?->name,
            'days_left' => $sub?->daysRemaining(),
        ];
    }

    protected function resolveActiveBranch(): ?Branch
    {
        $accessible = app(BranchContext::class)->accessibleBranches();
        if ($this->activeBranchId && $accessible->contains('id', $this->activeBranchId)) {
            return $accessible->firstWhere('id', $this->activeBranchId);
        }

        return $accessible->first();
    }

    public function render()
    {
        $branchContext = app(BranchContext::class);
        $accessibleBranches = $branchContext->accessibleBranches();
        $branchId = $this->activeBranchId ?? $accessibleBranches->first()?->id;

        $searchResults = $this->memberSearch !== '' && strlen($this->memberSearch) >= 2
            ? Member::query()
                ->with(['user', 'activeSubscription.plan'])
                ->where(function ($q) {
                    $term = '%' . $this->memberSearch . '%';
                    $q->where('member_code', 'like', $term)
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term));
                })
                ->limit(6)
                ->get()
            : collect();

        $attendances = Attendance::query()
            ->with(['member.user', 'branch', 'staff'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($this->dateFilter !== '', fn ($q) => $q->whereDate('checked_in_at', $this->dateFilter))
            ->latest('checked_in_at')
            ->paginate(15);

        $todayAllowedCount = Attendance::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereDate('checked_in_at', $this->dateFilter ?: now()->toDateString())
            ->where('status', 'allowed')
            ->count();

        $currentlyInsideCount = Attendance::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereDate('checked_in_at', now()->toDateString())
            ->where('status', 'allowed')
            ->whereNull('checked_out_at')
            ->count();

        return view('livewire.admin.attendance.check-in-console', [
            'accessibleBranches' => $accessibleBranches,
            'searchResults' => $searchResults,
            'attendances' => $attendances,
            'todayAllowedCount' => $todayAllowedCount,
            'currentlyInsideCount' => $currentlyInsideCount,
            'canCheckIn' => Auth::user()->can('attendance.checkin'),
        ]);
    }
}
