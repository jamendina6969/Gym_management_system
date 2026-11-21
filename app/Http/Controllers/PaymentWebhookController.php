<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\WebhookEvent;
use App\Notifications\PaymentReceiptNotification;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function handle(Request $request, string $driver, PaymentGatewayManager $manager): JsonResponse
    {
        if (! in_array($driver, ['paymongo', 'stripe'], true)) {
            return response()->json(['error' => 'Unsupported gateway'], 404);
        }

        $branchId = $request->query('branch_id') ? (int) $request->query('branch_id') : null;
        $config = $manager->resolveConfig($driver, $branchId);

        if (! $config) {
            return response()->json(['error' => 'Gateway not enabled'], 400);
        }

        $gateway = $manager->driver($driver, $branchId);

        if (! $gateway->verifyWebhookSignature($request, $config->webhook_secret)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $parsed = $gateway->parseWebhook($request);
        $eventId = $parsed['event_id'];

        if ($eventId === '') {
            return response()->json(['error' => 'Missing event id'], 422);
        }

        // Idempotency check
        if (WebhookEvent::where('event_id', $eventId)->exists()) {
            return response()->json(['status' => 'already_processed']);
        }

        $event = WebhookEvent::create([
            'driver' => $driver,
            'event_id' => $eventId,
            'event_type' => $parsed['event_type'],
            'payload' => $request->json()->all(),
            'status' => 'ignored',
            'processed_at' => now(),
        ]);

        if (! $parsed['paid']) {
            return response()->json(['status' => 'ignored']);
        }

        $payment = null;
        if ($parsed['payment_id']) {
            $payment = Payment::with('member.user')->find($parsed['payment_id']);
        }
        if (! $payment && $parsed['gateway_reference']) {
            $payment = Payment::with('member.user')
                ->where('gateway_reference', $parsed['gateway_reference'])
                ->first();
        }

        if (! $payment) {
            $event->update(['status' => 'failed', 'error_message' => 'Matching payment not found']);

            return response()->json(['status' => 'payment_not_found'], 200);
        }

        if ($payment->status !== 'paid') {
            $payment->markAsPaid();
            $payment->member?->user?->notify(new PaymentReceiptNotification($payment));
        }

        $event->update(['status' => 'processed']);

        return response()->json(['status' => 'processed']);
    }
}
