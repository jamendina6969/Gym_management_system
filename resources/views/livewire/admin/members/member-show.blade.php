<div class="space-y-6">
    {{-- Header Card --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="flex items-start gap-4">
            <div class="w-14 h-14 rounded-2xl bg-slate-900 text-emerald-400 font-bold text-xl flex items-center justify-center shrink-0">
                {{ strtoupper(substr($member->user->name, 0, 2)) }}
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-xl font-bold text-slate-900">{{ $member->user->name }}</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $member->status === 'active' ? 'bg-emerald-100 text-emerald-800' : ($member->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-700') }}">
                        {{ ucfirst($member->status) }}
                    </span>
                </div>
                <div class="text-xs text-slate-500 mt-1 space-x-2">
                    <span class="font-mono font-semibold text-slate-700">{{ $member->member_code }}</span>
                    <span>·</span>
                    <span>{{ $member->user->email }}</span>
                    @if ($member->user->phone)
                        <span>·</span>
                        <span>{{ $member->user->phone }}</span>
                    @endif
                </div>
                <div class="text-xs text-slate-500 mt-1">
                    Home Branch: <strong class="text-slate-700">{{ $member->homeBranch?->name ?? 'Unassigned' }}</strong>
                    @if ($member->waiver_accepted_at)
                        · Waiver signed {{ $member->waiver_accepted_at->format('M d, Y') }}
                    @endif
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($member->status === 'pending' && $canApprove)
                <button wire:click="approveMember" class="px-3.5 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700">
                    Approve Member
                </button>
            @endif
            @if ($canManageSubs)
                <button wire:click="openSubModal" class="px-3.5 py-2 rounded-lg bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800">
                    + Assign / Renew Plan
                </button>
            @endif
            @if ($canRecordPay)
                <button wire:click="openPaymentModal" class="px-3.5 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50">
                    Record Payment
                </button>
            @endif
            <button wire:click="regenerateInvite" class="px-3.5 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-medium hover:bg-slate-50">
                Send Invite Link
            </button>
        </div>
    </div>

    @if ($inviteLink)
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-xs text-emerald-900">
            <div class="font-semibold mb-1">Password Setup Invite Link</div>
            <input type="text" readonly value="{{ $inviteLink }}" class="w-full font-mono text-xs rounded-lg border-emerald-300 bg-white">
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column: Emergency & Health Info --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4 text-sm">
            <h3 class="font-semibold text-slate-900 border-b border-slate-100 pb-2">Profile Details</h3>
            <div>
                <div class="text-xs text-slate-500">Emergency Contact</div>
                <div class="font-medium text-slate-800 mt-0.5">
                    {{ $member->emergency_contact_name ?: 'Not set' }}
                    @if ($member->emergency_contact_phone)
                        <div class="text-xs text-slate-500">{{ $member->emergency_contact_phone }}</div>
                    @endif
                </div>
            </div>
            <div>
                <div class="text-xs text-slate-500">Address</div>
                <div class="text-slate-700 mt-0.5">{{ $member->address ?: 'Not set' }}</div>
            </div>
            <div>
                <div class="text-xs text-slate-500">Health / Medical Notes</div>
                <div class="text-slate-700 mt-0.5">{{ $member->health_notes ?: 'None recorded' }}</div>
            </div>
        </div>

        {{-- Right Column: Subscriptions & Payments --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Subscriptions --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-semibold text-slate-900 text-sm">Subscriptions</h3>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($member->subscriptions as $sub)
                        <div class="p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-sm">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-slate-900">{{ $sub->plan->name }}</span>
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium {{ $sub->status === 'active' ? 'bg-emerald-100 text-emerald-800' : ($sub->status === 'frozen' ? 'bg-sky-100 text-sky-800' : ($sub->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600')) }}">
                                        {{ ucfirst($sub->status) }}
                                    </span>
                                </div>
                                <div class="text-xs text-slate-500 mt-1">
                                    {{ $sub->starts_at->format('M d, Y') }} → {{ $sub->ends_at->format('M d, Y') }}
                                    · Paid {{ $currencySymbol }}{{ number_format((float) $sub->amount_paid, 2) }} / {{ $currencySymbol }}{{ number_format((float) $sub->price, 2) }}
                                    @if ($sub->balance() > 0)
                                        <span class="text-rose-600 font-semibold">(Bal: {{ $currencySymbol }}{{ number_format($sub->balance(), 2) }})</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                @if ($sub->balance() > 0 && $canRecordPay)
                                    <button wire:click="openPaymentModal({{ $sub->id }})" class="px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-medium">
                                        Pay Balance
                                    </button>
                                @endif
                                @if ($canManageSubs && $sub->status === 'active')
                                    <button wire:click="freezeSubscription({{ $sub->id }})" class="px-2.5 py-1 rounded-md bg-sky-50 text-sky-700 hover:bg-sky-100 font-medium">Freeze</button>
                                    <button wire:click="cancelSubscription({{ $sub->id }})" wire:confirm="Cancel this subscription?" class="px-2.5 py-1 rounded-md bg-rose-50 text-rose-700 hover:bg-rose-100 font-medium">Cancel</button>
                                @elseif ($canManageSubs && $sub->status === 'frozen')
                                    <button wire:click="unfreezeSubscription({{ $sub->id }})" class="px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-medium">Unfreeze</button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-slate-500 text-sm">No subscriptions yet.</div>
                    @endforelse
                </div>
            </div>

            {{-- Payments --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h3 class="font-semibold text-slate-900 text-sm">Payment History</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-xs">
                        <thead class="bg-slate-50 text-slate-500 uppercase">
                            <tr>
                                <th class="px-4 py-2.5 text-left">Invoice</th>
                                <th class="px-4 py-2.5 text-left">Method</th>
                                <th class="px-4 py-2.5 text-right">Amount</th>
                                <th class="px-4 py-2.5 text-center">Status</th>
                                <th class="px-4 py-2.5 text-right">Receipt</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($member->payments as $payment)
                                <tr>
                                    <td class="px-4 py-3 font-mono font-medium text-slate-800">
                                        {{ $payment->invoice_number }}
                                        <div class="font-sans text-[11px] text-slate-500">{{ ($payment->paid_at ?? $payment->created_at)->format('M d, Y') }}</div>
                                    </td>
                                    <td class="px-4 py-3 uppercase text-slate-600">{{ str_replace('_', ' ', $payment->payment_method) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ $currencySymbol }}{{ number_format((float) $payment->amount, 2) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full font-medium {{ $payment->status === 'paid' ? 'bg-emerald-100 text-emerald-800' : ($payment->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-700') }}">
                                            {{ ucfirst($payment->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right space-x-2">
                                        @if ($payment->status === 'pending' && $canVerifyPay)
                                            <button wire:click="verifyPayment({{ $payment->id }})" class="text-emerald-700 hover:underline font-semibold">Verify</button>
                                        @endif
                                        <a href="{{ route('invoices.download', $payment) }}" class="text-slate-600 hover:text-slate-900 underline">PDF</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-slate-500">No payments recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Assign Subscription Modal --}}
    @if ($showSubModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-slate-900">Assign / Renew Membership Plan</h3>
                    <button wire:click="$set('showSubModal', false)" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                <form wire:submit="assignSubscription" class="p-6 space-y-4 text-sm">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Membership Plan *</label>
                        <select wire:model.live="selectedPlanId" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">Choose a plan...</option>
                            @foreach ($availablePlans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }} — {{ $currencySymbol }}{{ number_format((float) $plan->price, 2) }} ({{ $plan->duration_days }}d)</option>
                            @endforeach
                        </select>
                        @error('selectedPlanId') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Start Date *</label>
                            <input type="date" wire:model="starts_at" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Initial Payment ({{ $currencySymbol }})</label>
                            <input type="number" step="0.01" wire:model="initial_payment" min="0" placeholder="0.00" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Payment Method</label>
                            <select wire:model="payment_method" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="cash">Cash</option>
                                <option value="gcash">GCash</option>
                                <option value="maya">Maya</option>
                                <option value="card">Card</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Reference #</label>
                            <input type="text" wire:model="reference_number" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                        <button type="button" wire:click="$set('showSubModal', false)" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm font-medium">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Assign Plan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Record Payment Modal --}}
    @if ($showPaymentModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-slate-900">Record Payment</h3>
                    <button wire:click="$set('showPaymentModal', false)" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                <form wire:submit="recordPayment" class="p-6 space-y-4 text-sm">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Apply to Subscription (optional)</label>
                        <select wire:model="paymentSubscriptionId" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">General Payment</option>
                            @foreach ($member->subscriptions as $sub)
                                <option value="{{ $sub->id }}">{{ $sub->plan->name }} (Bal: {{ $currencySymbol }}{{ number_format($sub->balance(), 2) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Amount ({{ $currencySymbol }}) *</label>
                            <input type="number" step="0.01" wire:model="payment_amount" min="1" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            @error('payment_amount') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Method *</label>
                            <select wire:model="pay_method" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="cash">Cash</option>
                                <option value="gcash">GCash</option>
                                <option value="maya">Maya</option>
                                <option value="card">Card</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Reference #</label>
                        <input type="text" wire:model="pay_reference" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Notes</label>
                        <input type="text" wire:model="pay_notes" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                        <button type="button" wire:click="$set('showPaymentModal', false)" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm font-medium">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Save Payment</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
