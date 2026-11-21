<div class="space-y-4">
    {{-- Summary Strip --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Collected This Month</div>
            <div class="mt-1 text-2xl font-bold text-slate-900">{{ $currencySymbol }}{{ number_format((float) $paidThisMonth, 2) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Pending Verification</div>
            <div class="mt-1 text-2xl font-bold {{ $pendingCount > 0 ? 'text-amber-600' : 'text-slate-900' }}">{{ $pendingCount }}</div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="bg-white rounded-xl border border-slate-200 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-sm">
        <div class="flex flex-1 flex-wrap gap-3">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search invoice #, ref #, or member..."
                   class="w-full sm:max-w-xs rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            <select wire:model.live="statusFilter" class="rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="all">All Statuses</option>
                <option value="paid">Paid</option>
                <option value="pending">Pending Verification</option>
                <option value="refunded">Refunded</option>
            </select>
            <select wire:model.live="methodFilter" class="rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="all">All Methods</option>
                <option value="cash">Cash</option>
                <option value="gcash">GCash</option>
                <option value="maya">Maya</option>
                <option value="card">Card</option>
                <option value="bank_transfer">Bank Transfer</option>
            </select>
        </div>

        @if ($canRecord)
            <button wire:click="openRecordModal" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition">
                + Record Payment
            </button>
        @endif
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left">Invoice</th>
                        <th class="px-5 py-3 text-left">Member</th>
                        <th class="px-5 py-3 text-left">Method / Ref</th>
                        <th class="px-5 py-3 text-right">Amount</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($payments as $payment)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-5 py-3.5">
                                <div class="font-mono font-semibold text-slate-900 text-xs">{{ $payment->invoice_number }}</div>
                                <div class="text-[11px] text-slate-500">{{ ($payment->paid_at ?? $payment->created_at)->format('M d, Y h:i A') }}</div>
                            </td>
                            <td class="px-5 py-3.5">
                                <a href="{{ route('admin.members.show', $payment->member) }}" class="font-medium text-slate-900 hover:text-emerald-700">
                                    {{ $payment->member->user->name }}
                                </a>
                                <div class="text-xs text-slate-500">{{ $payment->subscription?->plan?->name ?? 'General Payment' }}</div>
                            </td>
                            <td class="px-5 py-3.5 text-xs">
                                <span class="uppercase font-medium text-slate-700">{{ str_replace('_', ' ', $payment->payment_method) }}</span>
                                @if ($payment->reference_number)
                                    <div class="text-slate-500 font-mono">{{ $payment->reference_number }}</div>
                                @endif
                                @if ($payment->proof_path)
                                    <a href="{{ asset('storage/' . $payment->proof_path) }}" target="_blank" class="text-emerald-600 hover:underline">View Proof ↗</a>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right font-semibold text-slate-900">
                                {{ $currencySymbol }}{{ number_format((float) $payment->amount, 2) }}
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $payment->status === 'paid' ? 'bg-emerald-100 text-emerald-800' : ($payment->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-700') }}">
                                    {{ ucfirst($payment->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap space-x-2 text-xs">
                                @if ($payment->status === 'pending' && $canVerify)
                                    <button wire:click="verifyPayment({{ $payment->id }})" class="px-2.5 py-1 rounded-md bg-emerald-600 text-white font-medium hover:bg-emerald-700">Verify</button>
                                @endif
                                @if ($payment->status === 'paid' && $canRefund)
                                    <button wire:click="refundPayment({{ $payment->id }})" wire:confirm="Mark {{ $payment->invoice_number }} as refunded?" class="text-rose-600 hover:underline font-medium">Refund</button>
                                @endif
                                <a href="{{ route('invoices.download', $payment) }}" class="text-slate-700 hover:text-slate-900 underline font-medium">PDF</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-slate-500">No payments recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($payments->hasPages())
            <div class="px-5 py-3 border-t border-slate-100">{{ $payments->links() }}</div>
        @endif
    </div>

    {{-- Record Payment Modal --}}
    @if ($showRecordModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-slate-900">Record Manual Payment</h3>
                    <button wire:click="$set('showRecordModal', false)" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                <form wire:submit="recordPayment" class="p-6 space-y-4 text-sm">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Member *</label>
                        <select wire:model.live="member_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">Select member...</option>
                            @foreach ($members as $m)
                                <option value="{{ $m->id }}">{{ $m->user->name }} ({{ $m->member_code }})</option>
                            @endforeach
                        </select>
                        @error('member_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    @if ($selectedMemberSubs->isNotEmpty())
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Subscription</label>
                            <select wire:model="subscription_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">General Payment</option>
                                @foreach ($selectedMemberSubs as $sub)
                                    <option value="{{ $sub->id }}">{{ $sub->plan->name }} (Bal: {{ $currencySymbol }}{{ number_format($sub->balance(), 2) }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Amount ({{ $currencySymbol }}) *</label>
                            <input type="number" step="0.01" wire:model="amount" min="1" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            @error('amount') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Method *</label>
                            <select wire:model="payment_method" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
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
                        <input type="text" wire:model="reference_number" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Notes</label>
                        <input type="text" wire:model="notes" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                        <button type="button" wire:click="$set('showRecordModal', false)" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm font-medium">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Save Payment</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
