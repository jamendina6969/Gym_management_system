<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class OnlineCheckoutController extends Controller
{
    public function checkout(Request $request, MembershipPlan $plan, PaymentGatewayManager $manager): RedirectResponse
    {
        $validated = $request->validate([
            'driver' => ['required', 'in:paymongo,stripe'],
        ]);

        $user = $request->user();
        $member = $user->member ?? Member::create([
            'user_id' => $user->id,
            'home_branch_id' => Branch::active()->value('id'),
            'status' => 'pending',
        ]);

        $branchId = $member->home_branch_id ?? Branch::active()->value('id');
        $gateway = $manager->driver($validated['driver'], $branchId);

        $payment = DB::transaction(function () use ($member, $plan, $branchId, $validated, $user) {
            $activeSub = $member->activeSubscription;
            $startsAt = $activeSub && $activeSub->ends_at->isFuture()
                ? $activeSub->ends_at->copy()->addDay()
                : now();

            $sub = Subscription::create([
                'member_id' => $member->id,
                'membership_plan_id' => $plan->id,
                'branch_id' => $branchId,
                'starts_at' => $startsAt->toDateString(),
                'ends_at' => $startsAt->copy()->addDays($plan->duration_days)->toDateString(),
                'price' => $plan->price,
                'amount_paid' => 0,
                'remaining_class_credits' => $plan->class_credits,
                'status' => 'pending',
                'created_by' => $user->id,
            ]);

            return Payment::create([
                'branch_id' => $branchId,
                'member_id' => $member->id,
                'subscription_id' => $sub->id,
                'amount' => $plan->price,
                'payment_method' => $validated['driver'],
                'gateway' => $validated['driver'],
                'status' => 'pending',
            ]);
        });

        try {
            $session = $gateway->createCheckoutSession(
                $payment,
                route('portal.checkout.success', $payment),
                route('portal.checkout.cancel', $payment)
            );

            $payment->update(['gateway_reference' => $session['reference']]);

            return redirect()->away($session['checkout_url']);
        } catch (Throwable $e) {
            return redirect()->route('portal.membership')
                ->with('error', 'Online checkout unavailable: ' . $e->getMessage());
        }
    }

    public function success(Payment $payment): RedirectResponse
    {
        return redirect()->route('portal.membership')
            ->with('success', 'Thank you! Your payment (' . $payment->invoice_number . ') is being confirmed and your membership will activate momentarily.');
    }

    public function cancel(Payment $payment): RedirectResponse
    {
        return redirect()->route('portal.membership')
            ->with('error', 'Checkout was cancelled. You can retry anytime.');
    }
}
