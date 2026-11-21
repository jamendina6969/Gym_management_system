<?php

namespace App\Livewire\Admin\Payments;

use App\Models\Member;
use App\Models\Payment;
use App\Services\BranchContext;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class PaymentIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public string $methodFilter = 'all';

    public bool $showRecordModal = false;
    public ?int $member_id = null;
    public ?int $subscription_id = null;
    public string $amount = '';
    public string $payment_method = 'cash';
    public string $reference_number = '';
    public string $notes = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openRecordModal(): void
    {
        abort_unless(Auth::user()->can('payments.record'), 403);

        $this->reset(['member_id', 'subscription_id', 'amount', 'reference_number', 'notes']);
        $this->payment_method = 'cash';
        $this->resetValidation();
        $this->showRecordModal = true;
    }

    public function updatedMemberId($value): void
    {
        $this->subscription_id = null;
        if ($value) {
            $member = Member::with('latestSubscription')->find($value);
            if ($member?->latestSubscription && $member->latestSubscription->balance() > 0) {
                $this->subscription_id = $member->latestSubscription->id;
                $this->amount = (string) $member->latestSubscription->balance();
            }
        }
    }

    public function recordPayment(): void
    {
        abort_unless(Auth::user()->can('payments.record'), 403);

        $validated = $this->validate([
            'member_id' => ['required', 'exists:members,id'],
            'subscription_id' => ['nullable', 'exists:subscriptions,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'in:cash,card,gcash,maya,bank_transfer'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $member = Member::findOrFail($validated['member_id']);
        $branchId = $member->home_branch_id ?? app(BranchContext::class)->currentBranchId() ?? Auth::user()->accessibleBranches()->first()?->id;

        $payment = Payment::create([
            'branch_id' => $branchId,
            'member_id' => $member->id,
            'subscription_id' => $validated['subscription_id'] ?: null,
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'gateway' => 'manual',
            'reference_number' => $validated['reference_number'] ?: null,
            'status' => 'paid',
            'paid_at' => now(),
            'recorded_by' => Auth::id(),
            'verified_by' => Auth::id(),
            'notes' => $validated['notes'] ?: null,
        ]);

        $payment->markAsPaid(Auth::user());

        $this->showRecordModal = false;
        session()->flash('success', 'Payment recorded (' . $payment->invoice_number . ').');
    }

    public function verifyPayment(int $paymentId): void
    {
        abort_unless(Auth::user()->can('payments.verify'), 403);

        $payment = Payment::findOrFail($paymentId);
        $payment->markAsPaid(Auth::user());
        session()->flash('success', 'Payment ' . $payment->invoice_number . ' verified.');
    }

    public function refundPayment(int $paymentId): void
    {
        abort_unless(Auth::user()->can('payments.refund'), 403);

        $payment = Payment::findOrFail($paymentId);
        $payment->markAsRefunded();
        session()->flash('success', 'Payment ' . $payment->invoice_number . ' marked as refunded.');
    }

    public function render()
    {
        $branchContext = app(BranchContext::class);
        $scopedBranchIds = $branchContext->scopedBranchIds();

        $payments = Payment::query()
            ->with(['member.user', 'branch', 'subscription.plan'])
            ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereIn('branch_id', $scopedBranchIds))
            ->when($this->search !== '', function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('invoice_number', 'like', $term)
                        ->orWhere('reference_number', 'like', $term)
                        ->orWhereHas('member.user', fn ($u) => $u->where('name', 'like', $term));
                });
            })
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->methodFilter !== 'all', fn ($q) => $q->where('payment_method', $this->methodFilter))
            ->latest()
            ->paginate(12);

        $members = Member::query()
            ->with('user')
            ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereIn('home_branch_id', $scopedBranchIds))
            ->latest()
            ->limit(100)
            ->get();

        $selectedMemberSubs = $this->member_id
            ? Member::find($this->member_id)?->subscriptions()->with('plan')->get() ?? collect()
            : collect();

        $paidThisMonth = Payment::query()
            ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereIn('branch_id', $scopedBranchIds))
            ->where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');

        $pendingCount = Payment::query()
            ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereIn('branch_id', $scopedBranchIds))
            ->where('status', 'pending')
            ->count();

        return view('livewire.admin.payments.payment-index', [
            'payments' => $payments,
            'members' => $members,
            'selectedMemberSubs' => $selectedMemberSubs,
            'paidThisMonth' => $paidThisMonth,
            'pendingCount' => $pendingCount,
            'currencySymbol' => config('app.currency_symbol', '₱'),
            'canRecord' => Auth::user()->can('payments.record'),
            'canVerify' => Auth::user()->can('payments.verify'),
            'canRefund' => Auth::user()->can('payments.refund'),
        ]);
    }
}
