<?php

namespace App\Livewire\Admin\Members;

use App\Models\Member;
use App\Models\MemberInvitation;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\Subscription;
use App\Notifications\MemberInviteNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class MemberShow extends Component
{
    public Member $member;

    // Assign / Renew subscription modal
    public bool $showSubModal = false;
    public ?int $selectedPlanId = null;
    public string $starts_at = '';
    public string $initial_payment = '';
    public string $payment_method = 'cash';
    public string $reference_number = '';
    public string $sub_notes = '';

    // Record payment modal
    public bool $showPaymentModal = false;
    public ?int $paymentSubscriptionId = null;
    public string $payment_amount = '';
    public string $pay_method = 'cash';
    public string $pay_reference = '';
    public string $pay_notes = '';

    public ?string $inviteLink = null;

    public function mount(Member $member): void
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin() && $member->home_branch_id) {
            abort_unless($user->accessibleBranches()->contains('id', $member->home_branch_id), 403);
        }

        $this->member = $member;
        $this->starts_at = now()->toDateString();
    }

    public function approveMember(): void
    {
        abort_unless(Auth::user()->can('members.approve'), 403);

        $this->member->approve(Auth::user());
        $this->member->refresh();
        session()->flash('success', 'Member approved and activated.');
    }

    public function regenerateInvite(): void
    {
        abort_unless(Auth::user()->can('members.edit'), 403);

        $invitation = MemberInvitation::createForUser($this->member->user);
        $this->member->user->notify(new MemberInviteNotification($invitation));
        $this->inviteLink = route('invite.show', $invitation->token);
        session()->flash('success', 'New invite link generated and emailed to member.');
    }

    public function openSubModal(): void
    {
        abort_unless(Auth::user()->can('subscriptions.manage'), 403);

        $this->resetValidation();
        $activeSub = $this->member->activeSubscription;
        $this->starts_at = $activeSub && $activeSub->ends_at->isFuture()
            ? $activeSub->ends_at->copy()->addDay()->toDateString()
            : now()->toDateString();
        $this->selectedPlanId = null;
        $this->initial_payment = '';
        $this->payment_method = 'cash';
        $this->reference_number = '';
        $this->sub_notes = '';
        $this->showSubModal = true;
    }

    public function updatedSelectedPlanId($planId): void
    {
        if ($planId) {
            $plan = MembershipPlan::find($planId);
            if ($plan) {
                $this->initial_payment = (string) $plan->price;
            }
        }
    }

    public function assignSubscription(): void
    {
        abort_unless(Auth::user()->can('subscriptions.manage'), 403);

        $validated = $this->validate([
            'selectedPlanId' => ['required', 'exists:membership_plans,id'],
            'starts_at' => ['required', 'date'],
            'initial_payment' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,card,gcash,maya,bank_transfer'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'sub_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $plan = MembershipPlan::findOrFail($validated['selectedPlanId']);
        $start = Carbon::parse($validated['starts_at']);
        $end = $start->copy()->addDays($plan->duration_days);
        $branchId = $this->member->home_branch_id ?? Auth::user()->accessibleBranches()->first()?->id;

        DB::transaction(function () use ($validated, $plan, $start, $end, $branchId) {
            $initialPay = (float) ($validated['initial_payment'] ?: 0);

            $subscription = Subscription::create([
                'member_id' => $this->member->id,
                'membership_plan_id' => $plan->id,
                'branch_id' => $branchId,
                'starts_at' => $start->toDateString(),
                'ends_at' => $end->toDateString(),
                'price' => $plan->price,
                'amount_paid' => 0,
                'remaining_class_credits' => $plan->class_credits,
                'status' => $initialPay > 0 || (float) $plan->price === 0.0 ? 'active' : 'pending',
                'notes' => $validated['sub_notes'] ?: null,
                'created_by' => Auth::id(),
            ]);

            if ($initialPay > 0) {
                $payment = Payment::create([
                    'branch_id' => $branchId,
                    'member_id' => $this->member->id,
                    'subscription_id' => $subscription->id,
                    'amount' => $initialPay,
                    'payment_method' => $validated['payment_method'],
                    'gateway' => 'manual',
                    'reference_number' => $validated['reference_number'] ?: null,
                    'status' => 'paid',
                    'paid_at' => now(),
                    'recorded_by' => Auth::id(),
                    'verified_by' => Auth::id(),
                ]);

                $payment->markAsPaid(Auth::user());
            } else {
                $this->member->syncStatusFromSubscriptions();
            }
        });

        $this->showSubModal = false;
        $this->member->refresh();
        session()->flash('success', 'Subscription assigned successfully.');
    }

    public function freezeSubscription(int $subscriptionId): void
    {
        abort_unless(Auth::user()->can('subscriptions.manage'), 403);

        $sub = $this->member->subscriptions()->findOrFail($subscriptionId);
        $sub->freeze();
        $this->member->refresh();
        session()->flash('success', 'Subscription frozen.');
    }

    public function unfreezeSubscription(int $subscriptionId): void
    {
        abort_unless(Auth::user()->can('subscriptions.manage'), 403);

        $sub = $this->member->subscriptions()->findOrFail($subscriptionId);
        $sub->unfreeze();
        $this->member->refresh();
        session()->flash('success', 'Subscription unfrozen and expiry date extended.');
    }

    public function cancelSubscription(int $subscriptionId): void
    {
        abort_unless(Auth::user()->can('subscriptions.manage'), 403);

        $sub = $this->member->subscriptions()->findOrFail($subscriptionId);
        $sub->cancel();
        $this->member->refresh();
        session()->flash('success', 'Subscription cancelled.');
    }

    public function openPaymentModal(?int $subscriptionId = null): void
    {
        abort_unless(Auth::user()->can('payments.record'), 403);

        $this->resetValidation();
        $this->paymentSubscriptionId = $subscriptionId;
        $this->pay_method = 'cash';
        $this->pay_reference = '';
        $this->pay_notes = '';

        if ($subscriptionId) {
            $sub = $this->member->subscriptions()->find($subscriptionId);
            $this->payment_amount = $sub ? (string) $sub->balance() : '';
        } else {
            $this->payment_amount = '';
        }

        $this->showPaymentModal = true;
    }

    public function recordPayment(): void
    {
        abort_unless(Auth::user()->can('payments.record'), 403);

        $validated = $this->validate([
            'paymentSubscriptionId' => ['nullable', 'exists:subscriptions,id'],
            'payment_amount' => ['required', 'numeric', 'min:1'],
            'pay_method' => ['required', 'in:cash,card,gcash,maya,bank_transfer'],
            'pay_reference' => ['nullable', 'string', 'max:100'],
            'pay_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $branchId = $this->member->home_branch_id ?? Auth::user()->accessibleBranches()->first()?->id;

        $payment = Payment::create([
            'branch_id' => $branchId,
            'member_id' => $this->member->id,
            'subscription_id' => $validated['paymentSubscriptionId'] ?: null,
            'amount' => $validated['payment_amount'],
            'payment_method' => $validated['pay_method'],
            'gateway' => 'manual',
            'reference_number' => $validated['pay_reference'] ?: null,
            'status' => 'paid',
            'paid_at' => now(),
            'recorded_by' => Auth::id(),
            'verified_by' => Auth::id(),
            'notes' => $validated['pay_notes'] ?: null,
        ]);

        $payment->markAsPaid(Auth::user());

        $this->showPaymentModal = false;
        $this->member->refresh();
        session()->flash('success', 'Payment recorded (' . $payment->invoice_number . ').');
    }

    public function verifyPayment(int $paymentId): void
    {
        abort_unless(Auth::user()->can('payments.verify'), 403);

        $payment = $this->member->payments()->findOrFail($paymentId);
        $payment->markAsPaid(Auth::user());
        $this->member->refresh();
        session()->flash('success', 'Payment verified and subscription updated.');
    }

    public function render()
    {
        $this->member->load(['user', 'homeBranch', 'subscriptions.plan', 'payments.subscription.plan']);

        $availablePlans = MembershipPlan::query()
            ->active()
            ->availableForBranch($this->member->home_branch_id)
            ->orderBy('price')
            ->get();

        return view('livewire.admin.members.member-show', [
            'availablePlans' => $availablePlans,
            'currencySymbol' => config('app.currency_symbol', '₱'),
            'canManageSubs' => Auth::user()->can('subscriptions.manage'),
            'canRecordPay' => Auth::user()->can('payments.record'),
            'canVerifyPay' => Auth::user()->can('payments.verify'),
            'canApprove' => Auth::user()->can('members.approve'),
        ]);
    }
}
