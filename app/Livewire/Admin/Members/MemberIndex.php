<?php

namespace App\Livewire\Admin\Members;

use App\Models\Member;
use App\Models\MemberInvitation;
use App\Models\User;
use App\Notifications\MemberInviteNotification;
use App\Services\BranchContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class MemberIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';

    public bool $showCreateModal = false;
    public ?string $lastInviteUrl = null;

    // Walk-in member creation fields
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public ?int $home_branch_id = null;
    public string $gender = '';
    public string $date_of_birth = '';
    public string $address = '';
    public string $emergency_contact_name = '';
    public string $emergency_contact_phone = '';
    public string $health_notes = '';
    public bool $approve_immediately = true;
    public bool $send_invite = true;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        abort_unless(Auth::user()->can('members.create'), 403);

        $this->resetForm();
        $branchContext = app(BranchContext::class);
        $this->home_branch_id = $branchContext->currentBranchId() ?? $branchContext->accessibleBranches()->first()?->id;
        $this->showCreateModal = true;
    }

    public function createWalkInMember(): void
    {
        abort_unless(Auth::user()->can('members.create'), 403);

        $branchContext = app(BranchContext::class);
        $allowedBranchIds = $branchContext->accessibleBranches()->pluck('id')->all();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'home_branch_id' => ['required', Rule::in($allowedBranchIds)],
            'gender' => ['nullable', 'in:male,female,other'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:500'],
            'emergency_contact_name' => ['nullable', 'string', 'max:120'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'health_notes' => ['nullable', 'string', 'max:1000'],
            'approve_immediately' => ['boolean'],
            'send_invite' => ['boolean'],
        ]);

        $invitation = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => strtolower($validated['email']),
                'phone' => $validated['phone'] ?: null,
                'password' => Hash::make(Str::random(32)),
                'is_active' => true,
            ]);

            Role::findOrCreate('member', 'web');
            $user->assignRole('member');

            Member::create([
                'user_id' => $user->id,
                'home_branch_id' => $validated['home_branch_id'],
                'gender' => $validated['gender'] ?: null,
                'date_of_birth' => $validated['date_of_birth'] ?: null,
                'address' => $validated['address'] ?: null,
                'emergency_contact_name' => $validated['emergency_contact_name'] ?: null,
                'emergency_contact_phone' => $validated['emergency_contact_phone'] ?: null,
                'health_notes' => $validated['health_notes'] ?: null,
                'status' => $validated['approve_immediately'] ? 'active' : 'pending',
                'approved_at' => $validated['approve_immediately'] ? now() : null,
                'approved_by' => $validated['approve_immediately'] ? Auth::id() : null,
            ]);

            $invitation = MemberInvitation::createForUser($user);

            if ($validated['send_invite']) {
                $user->notify(new MemberInviteNotification($invitation));
            }

            return $invitation;
        });

        $this->lastInviteUrl = route('invite.show', $invitation->token);
        $this->showCreateModal = false;
        $this->resetForm();
        session()->flash('success', 'Walk-in member registered! Invite link generated so they can set their own password.');
    }

    public function approveMember(int $memberId): void
    {
        abort_unless(Auth::user()->can('members.approve'), 403);

        $member = Member::findOrFail($memberId);
        $this->authorizeBranch($member->home_branch_id);

        $member->approve(Auth::user());
        session()->flash('success', $member->user->name . ' has been approved and activated.');
    }

    protected function authorizeBranch(?int $branchId): void
    {
        $user = Auth::user();
        if ($user->isSuperAdmin() || $branchId === null) {
            return;
        }

        abort_unless($user->accessibleBranches()->contains('id', $branchId), 403);
    }

    protected function resetForm(): void
    {
        $this->reset([
            'name',
            'email',
            'phone',
            'home_branch_id',
            'gender',
            'date_of_birth',
            'address',
            'emergency_contact_name',
            'emergency_contact_phone',
            'health_notes',
        ]);
        $this->approve_immediately = true;
        $this->send_invite = true;
        $this->resetValidation();
    }

    public function render()
    {
        $branchContext = app(BranchContext::class);
        $scopedBranchIds = $branchContext->scopedBranchIds();

        $members = Member::query()
            ->with(['user', 'homeBranch', 'activeSubscription.plan'])
            ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereIn('home_branch_id', $scopedBranchIds))
            ->when($this->search !== '', function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('member_code', 'like', $term)
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term)->orWhere('phone', 'like', $term));
                });
            })
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(12);

        return view('livewire.admin.members.member-index', [
            'members' => $members,
            'branches' => $branchContext->accessibleBranches(),
            'canCreate' => Auth::user()->can('members.create'),
            'canApprove' => Auth::user()->can('members.approve'),
        ]);
    }
}
