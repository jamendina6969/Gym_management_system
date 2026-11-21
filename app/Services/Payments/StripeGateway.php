<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Payment;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class StripeGateway implements PaymentGatewayInterface
{
    public function __construct(protected PaymentGateway $config)
    {
    }

    public function driver(): string
    {
        return 'stripe';
    }

    public function createCheckoutSession(Payment $payment, string $successUrl, string $cancelUrl): array
    {
        if (empty($this->config->secret_key)) {
            throw new RuntimeException('Stripe secret key is not configured.');
        }

        $payment->loadMissing(['subscription.plan', 'member.user']);

        $amountCents = (int) round((float) $payment->amount * 100);

        $response = Http::withToken($this->config->secret_key)
            ->asForm()
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'customer_email' => $payment->member->user->email,
                'client_reference_id' => (string) $payment->id,
                'metadata[payment_id]' => (string) $payment->id,
                'metadata[invoice_number]' => $payment->invoice_number,
                'line_items[0][price_data][currency]' => strtolower($payment->currency ?: 'php'),
                'line_items[0][price_data][unit_amount]' => $amountCents,
                'line_items[0][price_data][product_data][name]' => $payment->subscription?->plan?->name ?? 'Gym Membership',
                'line_items[0][quantity]' => 1,
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Stripe error: ' . $response->body());
        }

        $data = $response->json();

        return [
            'checkout_url' => (string) ($data['url'] ?? ''),
            'reference' => (string) ($data['id'] ?? ''),
        ];
    }

    public function verifyWebhookSignature(Request $request, ?string $webhookSecret): bool
    {
        if (empty($webhookSecret)) {
            return false;
        }

        $header = (string) $request->header('Stripe-Signature', '');
        if ($header === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $header) as $segment) {
            [$k, $v] = array_pad(explode('=', trim($segment), 2), 2, '');
            $parts[$k] = $v;
        }

        $timestamp = $parts['t'] ?? '';
        $sig = $parts['v1'] ?? '';

        if ($timestamp === '' || $sig === '') {
            return false;
        }

        $computed = hash_hmac('sha256', $timestamp . '.' . $request->getContent(), $webhookSecret);

        return hash_equals($computed, $sig);
    }

    public function parseWebhook(Request $request): array
    {
        $payload = $request->json()->all();
        $eventId = (string) ($payload['id'] ?? '');
        $eventType = (string) ($payload['type'] ?? 'unknown');
        $obj = $payload['data']['object'] ?? [];

        $paid = $eventType === 'checkout.session.completed' && (($obj['payment_status'] ?? '') === 'paid');
        $paymentId = isset($obj['metadata']['payment_id'])
            ? (int) $obj['metadata']['payment_id']
            : (isset($obj['client_reference_id']) ? (int) $obj['client_reference_id'] : null);

        return [
            'event_id' => $eventId,
            'event_type' => $eventType,
            'paid' => $paid,
            'gateway_reference' => $obj['id'] ?? null,
            'payment_id' => $paymentId,
        ];
    }
}
