<?php

namespace App\Livewire\Admin\Branches;

use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class BranchIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';

    public bool $showModal = false;
    public ?int $editingBranchId = null;

    public string $name = '';
    public string $code = '';
    public string $address = '';
    public string $phone = '';
    public string $email = '';
    public string $hoursMonFri = '06:00 - 22:00';
    public string $hoursSat = '07:00 - 20:00';
    public string $hoursSun = '08:00 - 18:00';
    public bool $allow_self_registration = true;
    public bool $is_active = true;

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
        abort_unless(Auth::user()->can('branches.manage'), 403);

        $this->resetForm();
        $this->showModal = true;
    }

    public function openEditModal(int $branchId): void
    {
        abort_unless(Auth::user()->can('branches.manage'), 403);

        $branch = Branch::findOrFail($branchId);
        $this->editingBranchId = $branch->id;
        $this->name = $branch->name;
        $this->code = $branch->code;
        $this->address = (string) $branch->address;
        $this->phone = (string) $branch->phone;
        $this->email = (string) $branch->email;
        $this->hoursMonFri = $branch->opening_hours['mon_fri'] ?? '06:00 - 22:00';
        $this->hoursSat = $branch->opening_hours['sat'] ?? '07:00 - 20:00';
        $this->hoursSun = $branch->opening_hours['sun'] ?? '08:00 - 18:00';
        $this->allow_self_registration = (bool) $branch->allow_self_registration;
        $this->is_active = (bool) $branch->is_active;

        $this->showModal = true;
    }

    public function save(): void
    {
        abort_unless(Auth::user()->can('branches.manage'), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required',
                'string',
                'max:20',
                'alpha_dash',
                Rule::unique('branches', 'code')->ignore($this->editingBranchId),
            ],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'hoursMonFri' => ['nullable', 'string', 'max:50'],
            'hoursSat' => ['nullable', 'string', 'max:50'],
            'hoursSun' => ['nullable', 'string', 'max:50'],
            'allow_self_registration' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'address' => $validated['address'] ?: null,
            'phone' => $validated['phone'] ?: null,
            'email' => $validated['email'] ?: null,
            'opening_hours' => [
                'mon_fri' => $validated['hoursMonFri'] ?: '06:00 - 22:00',
                'sat' => $validated['hoursSat'] ?: '07:00 - 20:00',
                'sun' => $validated['hoursSun'] ?: '08:00 - 18:00',
            ],
            'allow_self_registration' => $validated['allow_self_registration'],
            'is_active' => $validated['is_active'],
        ];

        if ($this->editingBranchId) {
            Branch::findOrFail($this->editingBranchId)->update($payload);
            session()->flash('success', 'Branch updated successfully.');
        } else {
            Branch::create($payload);
            session()->flash('success', 'Branch created successfully.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function toggleActive(int $branchId): void
    {
        abort_unless(Auth::user()->can('branches.manage'), 403);

        $branch = Branch::findOrFail($branchId);
        $branch->update(['is_active' => ! $branch->is_active]);
    }

    public function toggleSelfRegistration(int $branchId): void
    {
        abort_unless(Auth::user()->can('branches.manage'), 403);

        $branch = Branch::findOrFail($branchId);
        $branch->update(['allow_self_registration' => ! $branch->allow_self_registration]);
    }

    public function deleteBranch(int $branchId): void
    {
        abort_unless(Auth::user()->can('branches.manage'), 403);

        if (Branch::count() <= 1) {
            session()->flash('error', 'You must keep at least one branch.');

            return;
        }

        Branch::findOrFail($branchId)->delete();
        session()->flash('success', 'Branch archived.');
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingBranchId',
            'name',
            'code',
            'address',
            'phone',
            'email',
        ]);
        $this->hoursMonFri = '06:00 - 22:00';
        $this->hoursSat = '07:00 - 20:00';
        $this->hoursSun = '08:00 - 18:00';
        $this->allow_self_registration = true;
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render()
    {
        $user = Auth::user();

        $branches = Branch::query()
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->whereIn('id', $user->branches()->pluck('branches.id')))
            ->when($this->search !== '', function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('code', 'like', $term)
                        ->orWhere('address', 'like', $term);
                });
            })
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->withCount('users')
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.admin.branches.branch-index', [
            'branches' => $branches,
            'canManage' => $user->can('branches.manage'),
        ]);
    }
}
