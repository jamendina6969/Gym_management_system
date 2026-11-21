<?php

namespace App\Livewire\Admin\Settings;

use App\Models\PaymentGateway;
use App\Models\Setting;
use App\Services\BranchContext;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SettingsManager extends Component
{
    public string $tab = 'general'; // general, gateways

    // General Settings
    public string $gym_name = '';
    public string $currency_symbol = '₱';
    public int $expiry_reminder_days = 7;
    public string $tax_rate = '0';

    // PayMongo
    public bool $pm_enabled = false;
    public string $pm_mode = 'test';
    public string $pm_public_key = '';
    public string $pm_secret_key = '';
    public string $pm_webhook_secret = '';

    // Stripe
    public bool $st_enabled = false;
    public string $st_mode = 'test';
    public string $st_public_key = '';
    public string $st_secret_key = '';
    public string $st_webhook_secret = '';

    public function mount(): void
    {
        $branchId = $this->scopeBranchId();

        $this->gym_name = (string) Setting::getValue('gym_name', $branchId, config('app.name', 'IronPulse Gym'));
        $this->currency_symbol = (string) Setting::getValue('currency_symbol', $branchId, config('app.currency_symbol', '₱'));
        $this->expiry_reminder_days = (int) Setting::getValue('expiry_reminder_days', $branchId, 7);
        $this->tax_rate = (string) Setting::getValue('tax_rate', $branchId, '0');

        $pm = PaymentGateway::where('branch_id', $branchId)->where('driver', 'paymongo')->first();
        if ($pm) {
            $this->pm_enabled = (bool) $pm->is_enabled;
            $this->pm_mode = $pm->mode;
            $this->pm_public_key = (string) $pm->public_key;
        }

        $st = PaymentGateway::where('branch_id', $branchId)->where('driver', 'stripe')->first();
        if ($st) {
            $this->st_enabled = (bool) $st->is_enabled;
            $this->st_mode = $st->mode;
            $this->st_public_key = (string) $st->public_key;
        }
    }

    protected function scopeBranchId(): ?int
    {
        $branchContext = app(BranchContext::class);

        return Auth::user()->isSuperAdmin() ? $branchContext->currentBranchId() : ($branchContext->currentBranchId() ?? Auth::user()->primaryBranch()?->id);
    }

    public function saveGeneral(): void
    {
        abort_unless(Auth::user()->can('settings.manage'), 403);

        $validated = $this->validate([
            'gym_name' => ['required', 'string', 'max:120'],
            'currency_symbol' => ['required', 'string', 'max:5'],
            'expiry_reminder_days' => ['required', 'integer', 'min:1', 'max:60'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:50'],
        ]);

        $branchId = $this->scopeBranchId();
        Setting::setValue('gym_name', $validated['gym_name'], $branchId, 'general');
        Setting::setValue('currency_symbol', $validated['currency_symbol'], $branchId, 'general');
        Setting::setValue('expiry_reminder_days', (string) $validated['expiry_reminder_days'], $branchId, 'notifications');
        Setting::setValue('tax_rate', (string) $validated['tax_rate'], $branchId, 'billing');

        session()->flash('success', 'Settings saved.');
    }

    public function saveGateways(): void
    {
        abort_unless(Auth::user()->can('settings.manage'), 403);

        $branchId = $this->scopeBranchId();

        $this->upsertGateway($branchId, 'paymongo', 'PayMongo (GCash / Maya / Card / QR Ph)', [
            'is_enabled' => $this->pm_enabled,
            'mode' => $this->pm_mode,
            'public_key' => $this->pm_public_key ?: null,
            'secret_key' => $this->pm_secret_key ?: null,
            'webhook_secret' => $this->pm_webhook_secret ?: null,
            'meta' => ['payment_methods' => ['gcash', 'paymaya', 'grab_pay', 'card', 'qrph']],
        ]);

        $this->upsertGateway($branchId, 'stripe', 'Stripe (International Cards)', [
            'is_enabled' => $this->st_enabled,
            'mode' => $this->st_mode,
            'public_key' => $this->st_public_key ?: null,
            'secret_key' => $this->st_secret_key ?: null,
            'webhook_secret' => $this->st_webhook_secret ?: null,
        ]);

        $this->reset(['pm_secret_key', 'pm_webhook_secret', 'st_secret_key', 'st_webhook_secret']);
        session()->flash('success', 'Payment gateway settings saved (keys stored encrypted).');
    }

    protected function upsertGateway(?int $branchId, string $driver, string $displayName, array $data): void
    {
        $existing = PaymentGateway::where('branch_id', $branchId)->where('driver', $driver)->first();

        $payload = [
            'display_name' => $displayName,
            'is_enabled' => $data['is_enabled'],
            'mode' => $data['mode'],
            'public_key' => $data['public_key'] ?? $existing?->public_key,
            'secret_key' => $data['secret_key'] ?? $existing?->secret_key,
            'webhook_secret' => $data['webhook_secret'] ?? $existing?->webhook_secret,
            'meta' => $data['meta'] ?? $existing?->meta,
        ];

        PaymentGateway::updateOrCreate(
            ['branch_id' => $branchId, 'driver' => $driver],
            $payload
        );
    }

    public function render()
    {
        $branchId = $this->scopeBranchId();
        $branchQuery = $branchId ? ('?branch_id=' . $branchId) : '';

        return view('livewire.admin.settings.settings-manager', [
            'scopeLabel' => $branchId ? app(BranchContext::class)->currentBranch()?->name : 'Global (All Branches Default)',
            'paymongoWebhookUrl' => url('/webhooks/payments/paymongo') . $branchQuery,
            'stripeWebhookUrl' => url('/webhooks/payments/stripe') . $branchQuery,
        ]);
    }
}
