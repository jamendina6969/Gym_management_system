<?php

namespace App\Contracts;

use App\Models\Payment;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    public function driver(): string;

    /**
     * @return array{checkout_url: string, reference: string}
     */
    public function createCheckoutSession(Payment $payment, string $successUrl, string $cancelUrl): array;

    public function verifyWebhookSignature(Request $request, ?string $webhookSecret): bool;

    /**
     * @return array{event_id: string, event_type: string, paid: bool, gateway_reference: ?string, payment_id: ?int}
     */
    public function parseWebhook(Request $request): array;
}
