<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Payment;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayMongoGateway implements PaymentGatewayInterface
{
    public function __construct(protected PaymentGateway $config)
    {
    }

    public function driver(): string
    {
        return 'paymongo';
    }

    public function createCheckoutSession(Payment $payment, string $successUrl, string $cancelUrl): array
    {
        if (empty($this->config->secret_key)) {
            throw new RuntimeException('PayMongo secret key is not configured.');
        }

        $payment->loadMissing(['subscription.plan', 'member.user']);

        $amountCentavos = (int) round((float) $payment->amount * 100);
        $methods = $this->config->meta['payment_methods'] ?? ['gcash', 'paymaya', 'grab_pay', 'card', 'qrph'];

        $response = Http::withBasicAuth($this->config->secret_key, '')
            ->acceptJson()
            ->post('https://api.paymongo.com/v1/checkout_sessions', [
                'data' => [
                    'attributes' => [
                        'billing' => [
                            'name' => $payment->member->user->name,
                            'email' => $payment->member->user->email,
                            'phone' => $payment->member->user->phone ?? '',
                        ],
                        'send_email_receipt' => true,
                        'show_description' => true,
                        'show_line_items' => true,
                        'description' => 'Membership Payment ' . $payment->invoice_number,
                        'line_items' => [
                            [
                                'currency' => strtoupper($payment->currency ?: 'PHP'),
                                'amount' => $amountCentavos,
                                'name' => $payment->subscription?->plan?->name ?? 'Gym Membership',
                                'quantity' => 1,
                            ],
                        ],
                        'payment_method_types' => $methods,
                        'reference_number' => $payment->invoice_number,
                        'metadata' => [
                            'payment_id' => (string) $payment->id,
                            'invoice_number' => $payment->invoice_number,
                        ],
                        'success_url' => $successUrl,
                        'cancel_url' => $cancelUrl,
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('PayMongo error: ' . $response->body());
        }

        $data = $response->json('data');

        return [
            'checkout_url' => (string) ($data['attributes']['checkout_url'] ?? ''),
            'reference' => (string) ($data['id'] ?? ''),
        ];
    }

    public function verifyWebhookSignature(Request $request, ?string $webhookSecret): bool
    {
        if (empty($webhookSecret)) {
            return false;
        }

        $header = (string) $request->header('Paymongo-Signature', '');
        if ($header === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $header) as $segment) {
            [$k, $v] = array_pad(explode('=', trim($segment), 2), 2, '');
            $parts[$k] = $v;
        }

        $timestamp = $parts['t'] ?? '';
        $expectedSig = $this->config->mode === 'live' ? ($parts['li'] ?? '') : ($parts['te'] ?? '');

        if ($timestamp === '' || $expectedSig === '') {
            return false;
        }

        $computed = hash_hmac('sha256', $timestamp . '.' . $request->getContent(), $webhookSecret);

        return hash_equals($computed, $expectedSig);
    }

    public function parseWebhook(Request $request): array
    {
        $payload = $request->json()->all();
        $eventId = (string) ($payload['data']['id'] ?? '');
        $eventType = (string) ($payload['data']['attributes']['type'] ?? 'unknown');
        $resource = $payload['data']['attributes']['data'] ?? [];
        $attributes = $resource['attributes'] ?? [];

        $paid = in_array($eventType, ['checkout_session.payment.paid', 'payment.paid'], true);
        $paymentId = isset($attributes['metadata']['payment_id']) ? (int) $attributes['metadata']['payment_id'] : null;

        return [
            'event_id' => $eventId,
            'event_type' => $eventType,
            'paid' => $paid,
            'gateway_reference' => $resource['id'] ?? null,
            'payment_id' => $paymentId,
        ];
    }
}
