<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGatewayInterface;
use App\Models\PaymentGateway;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class PaymentGatewayManager
{
    public function resolveConfig(string $driver, ?int $branchId = null): ?PaymentGateway
    {
        if ($branchId !== null) {
            $branchConfig = PaymentGateway::query()
                ->where('branch_id', $branchId)
                ->where('driver', $driver)
                ->where('is_enabled', true)
                ->first();

            if ($branchConfig) {
                return $branchConfig;
            }
        }

        return PaymentGateway::query()
            ->whereNull('branch_id')
            ->where('driver', $driver)
            ->where('is_enabled', true)
            ->first();
    }

    public function driver(string $driver, ?int $branchId = null): PaymentGatewayInterface
    {
        $config = $this->resolveConfig($driver, $branchId);
        if (! $config) {
            throw new InvalidArgumentException("Payment gateway [{$driver}] is not enabled.");
        }

        return match ($driver) {
            'paymongo' => new PayMongoGateway($config),
            'stripe' => new StripeGateway($config),
            default => throw new InvalidArgumentException("Unsupported payment gateway [{$driver}]."),
        };
    }

    /**
     * @return Collection<int, PaymentGateway>
     */
    public function enabledOnlineGateways(?int $branchId = null): Collection
    {
        $drivers = ['paymongo', 'stripe'];
        $enabled = collect();

        foreach ($drivers as $driver) {
            $cfg = $this->resolveConfig($driver, $branchId);
            if ($cfg) {
                $enabled->push($cfg);
            }
        }

        return $enabled;
    }
}
