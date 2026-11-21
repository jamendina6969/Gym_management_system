<?php

namespace App\Livewire\Admin\Plans;

use App\Models\MembershipPlan;
use App\Services\BranchContext;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class PlanIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $scopeFilter = 'all';

    public bool $showModal = false;
    public ?int $editingPlanId = null;

    public string $name = '';
    public string $description = '';
    public string $billing_cycle = 'monthly';
    public int $duration_days = 30;
    public string $price = '1500.00';
    public ?string $class_credits = '';
    public string $access_scope = 'single_branch';
    public ?int $branch_id = null;
    public bool $is_active = true;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedBillingCycle(string $value): void
    {
        $this->duration_days = match ($value) {
            'daily' => 1,
            'weekly' => 7,
            'monthly' => 30,
            'quarterly' => 90,
            'annual' => 365,
            default => $this->duration_days ?: 30,
        };
    }

    public function openCreateModal(): void
    {
        abort_unless(Auth::user()->can('plans.manage'), 403);

        $this->resetForm();
        $branchContext = app(BranchContext::class);
        $this->branch_id = $branchContext->currentBranchId() ?? $branchContext->accessibleBranches()->first()?->id;
        $this->showModal = true;
    }

    public function openEditModal(int $planId): void
    {
        abort_unless(Auth::user()->can('plans.manage'), 403);

        $plan = MembershipPlan::findOrFail($planId);
        $this->editingPlanId = $plan->id;
        $this->name = $plan->name;
        $this->description = (string) $plan->description;
        $this->billing_cycle = $plan->billing_cycle;
        $this->duration_days = (int) $plan->duration_days;
        $this->price = (string) $plan->price;
        $this->class_credits = $plan->class_credits !== null ? (string) $plan->class_credits : '';
        $this->access_scope = $plan->access_scope;
        $this->branch_id = $plan->branch_id;
        $this->is_active = (bool) $plan->is_active;

        $this->showModal = true;
    }

    public function save(): void
    {
        abort_unless(Auth::user()->can('plans.manage'), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'billing_cycle' => ['required', 'in:daily,weekly,monthly,quarterly,annual,custom'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'class_credits' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'access_scope' => ['required', 'in:single_branch,all_access'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'is_active' => ['boolean'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?: null,
            'billing_cycle' => $validated['billing_cycle'],
            'duration_days' => (int) $validated['duration_days'],
            'price' => $validated['price'],
            'class_credits' => $this->class_credits === '' || $this->class_credits === null ? null : (int) $this->class_credits,
            'access_scope' => $validated['access_scope'],
            'branch_id' => $validated['access_scope'] === 'all_access' ? null : $validated['branch_id'],
            'is_active' => $validated['is_active'],
        ];

        if ($this->editingPlanId) {
            MembershipPlan::findOrFail($this->editingPlanId)->update($payload);
            session()->flash('success', 'Membership plan updated.');
        } else {
            MembershipPlan::create($payload);
            session()->flash('success', 'Membership plan created.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function toggleActive(int $planId): void
    {
        abort_unless(Auth::user()->can('plans.manage'), 403);

        $plan = MembershipPlan::findOrFail($planId);
        $plan->update(['is_active' => ! $plan->is_active]);
    }

    public function deletePlan(int $planId): void
    {
        abort_unless(Auth::user()->can('plans.manage'), 403);

        MembershipPlan::findOrFail($planId)->delete();
        session()->flash('success', 'Membership plan archived.');
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingPlanId',
            'name',
            'description',
            'class_credits',
            'branch_id',
        ]);
        $this->billing_cycle = 'monthly';
        $this->duration_days = 30;
        $this->price = '1500.00';
        $this->access_scope = 'single_branch';
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render()
    {
        $branchContext = app(BranchContext::class);
        $currentBranchId = $branchContext->currentBranchId();

        $plans = MembershipPlan::query()
            ->with('branch')
            ->withCount('subscriptions')
            ->when($currentBranchId !== null, fn ($q) => $q->availableForBranch($currentBranchId))
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->when($this->scopeFilter !== 'all', fn ($q) => $q->where('access_scope', $this->scopeFilter))
            ->orderBy('price')
            ->paginate(10);

        return view('livewire.admin.plans.plan-index', [
            'plans' => $plans,
            'branches' => $branchContext->accessibleBranches(),
            'canManage' => Auth::user()->can('plans.manage'),
            'currencySymbol' => config('app.currency_symbol', '₱'),
        ]);
    }
}
