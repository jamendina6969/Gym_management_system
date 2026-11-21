<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function download(Request $request, Payment $payment): Response
    {
        $user = $request->user();

        $isOwner = $user->member && (int) $payment->member_id === (int) $user->member->id;
        $isAuthorizedStaff = $user->can('payments.view') && (
            $user->isSuperAdmin() ||
            $user->accessibleBranches()->contains('id', $payment->branch_id)
        );

        abort_unless($isOwner || $isAuthorizedStaff, 403);

        $payment->load(['member.user', 'branch', 'subscription.plan', 'recordedBy', 'verifiedBy']);

        $pdf = Pdf::loadView('pdf.invoice', [
            'payment' => $payment,
            'gymName' => config('app.name', 'IronPulse Gym'),
            'currencySymbol' => config('app.currency_symbol', '₱'),
        ])->setPaper('a4');

        return $pdf->download($payment->invoice_number . '.pdf');
    }
}
