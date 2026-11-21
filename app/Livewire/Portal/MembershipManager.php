<?php

namespace App\Livewire\Portal;

use App\Models\Branch;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

class MembershipManager extends Component
{
    use WithFileUploads;

    public bool $showCheckoutModal = false;
    public ?int $selectedPlanId = null;
    public string $payment_method = 'gcash';
    public string $reference_number = '';
    public $proof = null;
    public string $notes = '';

    protected function ensureMemberProfile(): Member
    {
        $user = Auth::user();
        if ($user->member) {
            return $user->member;
        }

        $branchId = Branch::active()->value('id');

        return Member::create([
            'user_id' => $user->id,
            'home_branch_id' => $branchId,
            'status' => 'pending',
        ]);
    }

    public function selectPlan(int $planId): void
    {
        $this->selectedPlanId = $planId;
        $this->payment_method = 'gcash';
        $this->reference_number = '';
        $this->proof = null;
        $this->notes = '';
        $this->resetValidation();
        $this->showCheckoutModal = true;
    }

    public function submitManualPayment(): void
    {
        $validated = $this->validate([
            'selectedPlanId' => ['required', 'exists:membership_plans,id'],
            'payment_method' => ['required', 'in:gcash,maya,bank_transfer,cash'],
            'reference_number' => ['required_unless:payment_method,cash', 'nullable', 'string', 'max:100'],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'notes' => ['nullable', 'string', 'max:400'],
        ]);

        $member = $this->ensureMemberProfile();
        $plan = MembershipPlan::active()->findOrFail($validated['selectedPlanId']);
        $branchId = $member->home_branch_id ?? Branch::active()->value('id');

        $proofPath = $this->proof ? $this->proof->store('payment-proofs', 'public') : null;

        DB::transaction(function () use ($member, $plan, $branchId, $validated, $proofPath) {
            $activeSub = $member->activeSubscription;
            $startsAt = $activeSub && $activeSub->ends_at->isFuture()
                ? $activeSub->ends_at->copy()->addDay()
                : now();
            $endsAt = $startsAt->copy()->addDays($plan->duration_days);

            $subscription = Subscription::create([
                'member_id' => $member->id,
                'membership_plan_id' => $plan->id,
                'branch_id' => $branchId,
                'starts_at' => $startsAt->toDateString(),
                'ends_at' => $endsAt->toDateString(),
                'price' => $plan->price,
                'amount_paid' => 0,
                'remaining_class_credits' => $plan->class_credits,
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            Payment::create([
                'branch_id' => $branchId,
                'member_id' => $member->id,
                'subscription_id' => $subscription->id,
                'amount' => $plan->price,
                'payment_method' => $validated['payment_method'],
                'gateway' => 'manual',
                'reference_number' => $validated['reference_number'] ?: null,
                'proof_path' => $proofPath,
                'status' => 'pending',
                'notes' => $validated['notes'] ?: null,
            ]);
        });

        $this->showCheckoutModal = false;
        session()->flash('success', 'Payment submitted! Your membership will activate as soon as staff verifies your payment.');
    }

    public function render()
    {
        $member = $this->ensureMemberProfile();
        $member->load(['homeBranch', 'subscriptions.plan', 'payments.subscription.plan']);

        $plans = MembershipPlan::query()
            ->active()
            ->availableForBranch($member->home_branch_id)
            ->orderBy('price')
            ->get();

        $selectedPlan = $this->selectedPlanId ? MembershipPlan::find($this->selectedPlanId) : null;

        return view('livewire.portal.membership-manager', [
            'member' => $member,
            'activeSubscription' => $member->activeSubscription()->with('plan')->first(),
            'plans' => $plans,
            'selectedPlan' => $selectedPlan,
            'currencySymbol' => config('app.currency_symbol', '₱'),
        ]);
    }
}
