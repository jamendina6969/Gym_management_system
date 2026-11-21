<div class="space-y-6">
    {{-- Current Membership Status --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Current Membership</div>
            @if ($activeSubscription)
                <h2 class="text-xl font-bold text-slate-900 mt-1">{{ $activeSubscription->plan->name }}</h2>
                <p class="text-xs text-slate-500 mt-1">
                    Valid until <strong>{{ $activeSubscription->ends_at->format('M d, Y') }}</strong>
                    ({{ $activeSubscription->daysRemaining() }} days left)
                    · {{ $activeSubscription->plan->access_scope === 'all_access' ? 'All-Access' : ($member->homeBranch?->name ?? 'Home Branch') }}
                </p>
            @else
                <h2 class="text-xl font-bold text-slate-900 mt-1">No Active Plan</h2>
                <p class="text-xs text-slate-500 mt-1">
                    Account Status: <strong class="uppercase">{{ $member->status }}</strong> · Choose a plan below to activate or renew.
                </p>
            @endif
        </div>
        <span class="px-3 py-1 rounded-full text-xs font-semibold self-start sm:self-center {{ $member->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
            {{ ucfirst($member->status) }}
        </span>
    </div>

    {{-- Available Plans --}}
    <div>
        <h3 class="text-base font-semibold text-slate-900 mb-3">Available Plans</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @forelse ($plans as $plan)
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <h4 class="font-semibold text-slate-900">{{ $plan->name }}</h4>
                            @if ($plan->access_scope === 'all_access')
                                <span class="px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 text-[10px] font-semibold">All-Access</span>
                            @endif
                        </div>
                        <div class="mt-3">
                            <span class="text-2xl font-bold text-slate-900">{{ $currencySymbol }}{{ number_format((float) $plan->price, 2) }}</span>
                            <span class="text-xs text-slate-500">/ {{ $plan->duration_days }} days</span>
                        </div>
                        @if ($plan->description)
                            <p class="text-xs text-slate-500 mt-2">{{ $plan->description }}</p>
                        @endif
                        <div class="text-xs text-slate-600 mt-3">
                            Class credits: <strong>{{ $plan->class_credits === null ? 'Unlimited' : $plan->class_credits }}</strong>
                        </div>
                    </div>

                    <button wire:click="selectPlan({{ $plan->id }})" class="mt-4 w-full py-2 rounded-lg bg-slate-900 text-white text-xs font-semibold hover:bg-emerald-600 transition">
                        {{ $activeSubscription ? 'Renew / Upgrade' : 'Choose Plan' }}
                    </button>
                </div>
            @empty
                <div class="col-span-full bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-500 text-sm">
                    No active plans available right now. Please contact the front desk.
                </div>
            @endforelse
        </div>
    </div>

    {{-- Payment History --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-900 text-sm">My Payment History &amp; Receipts</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-2.5 text-left">Invoice</th>
                        <th class="px-4 py-2.5 text-left">Plan</th>
                        <th class="px-4 py-2.5 text-right">Amount</th>
                        <th class="px-4 py-2.5 text-center">Status</th>
                        <th class="px-4 py-2.5 text-right">Receipt</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($member->payments as $payment)
                        <tr>
                            <td class="px-4 py-3 font-mono font-medium text-slate-800">{{ $payment->invoice_number }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $payment->subscription?->plan?->name ?? 'Gym Payment' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ $currencySymbol }}{{ number_format((float) $payment->amount, 2) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded-full font-medium {{ $payment->status === 'paid' ? 'bg-emerald-100 text-emerald-800' : ($payment->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-700') }}">
                                    {{ ucfirst($payment->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('invoices.download', $payment) }}" class="text-emerald-700 hover:underline font-medium">Download PDF</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-500">No payments yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Checkout Modal --}}
    @if ($showCheckoutModal && $selectedPlan)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-slate-900">Subscribe to {{ $selectedPlan->name }}</h3>
                    <button wire:click="$set('showCheckoutModal', false)" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form wire:submit="submitManualPayment" class="p-6 space-y-4 text-sm">
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-slate-900">{{ $selectedPlan->name }}</div>
                            <div class="text-xs text-slate-500">{{ $selectedPlan->duration_days }} days</div>
                        </div>
                        <div class="text-lg font-bold text-emerald-700">{{ $currencySymbol }}{{ number_format((float) $selectedPlan->price, 2) }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Payment Method *</label>
                        <select wire:model.live="payment_method" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="gcash">GCash Transfer</option>
                            <option value="maya">Maya Transfer</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cash">Pay Cash at Front Desk</option>
                        </select>
                    </div>

                    @if ($payment_method !== 'cash')
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Reference Number *</label>
                            <input type="text" wire:model="reference_number" placeholder="e.g. GCash Ref #123456789" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            @error('reference_number') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Upload Proof of Payment (JPG/PNG/PDF, max 4MB)</label>
                            <input type="file" wire:model="proof" class="w-full text-xs text-slate-600">
                            @error('proof') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Notes (optional)</label>
                        <input type="text" wire:model="notes" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                        <button type="button" wire:click="$set('showCheckoutModal', false)" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm font-medium">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Submit Payment</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
