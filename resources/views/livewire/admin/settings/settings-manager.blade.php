<div class="space-y-4">
    <div class="bg-white rounded-xl border border-slate-200 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-sm">
        <div class="flex gap-2">
            <button wire:click="$set('tab', 'general')" class="px-3.5 py-2 rounded-lg text-xs font-semibold {{ $tab === 'general' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">General &amp; Reminders</button>
            <button wire:click="$set('tab', 'gateways')" class="px-3.5 py-2 rounded-lg text-xs font-semibold {{ $tab === 'gateways' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">Online Payment Gateways</button>
        </div>
        <span class="text-xs text-slate-500">Scope: <strong class="text-slate-800">{{ $scopeLabel }}</strong></span>
    </div>

    @if ($tab === 'general')
        <form wire:submit="saveGeneral" class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm max-w-xl space-y-4 text-sm">
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Gym Name</label>
                <input type="text" wire:model="gym_name" class="w-full rounded-lg border-slate-300 text-sm">
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Currency Symbol</label>
                    <input type="text" wire:model="currency_symbol" class="w-full rounded-lg border-slate-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Expiry Reminder (Days)</label>
                    <input type="number" wire:model="expiry_reminder_days" min="1" max="60" class="w-full rounded-lg border-slate-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Tax Rate (%)</label>
                    <input type="number" step="0.01" wire:model="tax_rate" min="0" class="w-full rounded-lg border-slate-300 text-sm">
                </div>
            </div>
            <div class="pt-3 border-t">
                <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700">Save Settings</button>
            </div>
        </form>
    @endif

    @if ($tab === 'gateways')
        <form wire:submit="saveGateways" class="space-y-6">
            {{-- PayMongo --}}
            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-4 text-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-slate-900">PayMongo (Recommended for PH)</h3>
                        <p class="text-xs text-slate-500">Accepts GCash, Maya, GrabPay, QR Ph, and Visa/Mastercard via Checkout Sessions.</p>
                    </div>
                    <label class="inline-flex items-center gap-2 text-xs font-semibold">
                        <input type="checkbox" wire:model="pm_enabled" class="rounded border-slate-300 text-emerald-600">
                        Enabled
                    </label>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Mode</label>
                        <select wire:model="pm_mode" class="w-full rounded-lg border-slate-300 text-xs">
                            <option value="test">Test Mode</option>
                            <option value="live">Live Mode</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Public Key</label>
                        <input type="text" wire:model="pm_public_key" placeholder="pk_test_..." class="w-full rounded-lg border-slate-300 text-xs font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Secret Key (leave blank to keep current)</label>
                        <input type="password" wire:model="pm_secret_key" placeholder="sk_test_..." class="w-full rounded-lg border-slate-300 text-xs font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Webhook Signing Secret</label>
                        <input type="password" wire:model="pm_webhook_secret" placeholder="whsk_..." class="w-full rounded-lg border-slate-300 text-xs font-mono">
                    </div>
                </div>
                <div class="text-[11px] text-slate-500 bg-slate-50 p-2.5 rounded-lg font-mono">
                    Webhook URL: {{ $paymongoWebhookUrl }}
                </div>
            </div>

            {{-- Stripe --}}
            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-4 text-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-slate-900">Stripe</h3>
                        <p class="text-xs text-slate-500">International credit &amp; debit cards via Stripe Checkout.</p>
                    </div>
                    <label class="inline-flex items-center gap-2 text-xs font-semibold">
                        <input type="checkbox" wire:model="st_enabled" class="rounded border-slate-300 text-emerald-600">
                        Enabled
                    </label>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Mode</label>
                        <select wire:model="st_mode" class="w-full rounded-lg border-slate-300 text-xs">
                            <option value="test">Test Mode</option>
                            <option value="live">Live Mode</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Publishable Key</label>
                        <input type="text" wire:model="st_public_key" placeholder="pk_test_..." class="w-full rounded-lg border-slate-300 text-xs font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Secret Key (leave blank to keep current)</label>
                        <input type="password" wire:model="st_secret_key" placeholder="sk_test_..." class="w-full rounded-lg border-slate-300 text-xs font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Webhook Signing Secret</label>
                        <input type="password" wire:model="st_webhook_secret" placeholder="whsec_..." class="w-full rounded-lg border-slate-300 text-xs font-mono">
                    </div>
                </div>
                <div class="text-[11px] text-slate-500 bg-slate-50 p-2.5 rounded-lg font-mono">
                    Webhook URL: {{ $stripeWebhookUrl }}
                </div>
            </div>

            <button type="submit" class="px-5 py-2.5 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700">
                Save Gateway Configuration
            </button>
        </form>
    @endif
</div>
