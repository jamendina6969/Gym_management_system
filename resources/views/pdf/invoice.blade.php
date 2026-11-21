<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $payment->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; margin: 0; padding: 24px; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #0f172a; padding-bottom: 14px; margin-bottom: 24px; }
        .brand { font-size: 20px; font-weight: bold; color: #0f172a; }
        .muted { color: #64748b; font-size: 11px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .badge-paid { background: #d1fae5; color: #065f46; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-refunded { background: #fee2e2; color: #991b1b; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th { background: #f1f5f9; text-align: left; padding: 9px 10px; font-size: 11px; text-transform: uppercase; color: #475569; border-bottom: 1px solid #cbd5e1; }
        td { padding: 10px; border-bottom: 1px solid #e2e8f0; }
        .right { text-align: right; }
        .total-row td { font-weight: bold; font-size: 14px; border-top: 2px solid #0f172a; border-bottom: none; }
        .grid { width: 100%; margin-bottom: 16px; }
        .grid td { border: none; padding: 2px 0; vertical-align: top; }
        .footer { margin-top: 40px; border-top: 1px solid #e2e8f0; padding-top: 12px; text-align: center; color: #64748b; font-size: 10px; }
    </style>
</head>
<body>
    <table class="grid" style="border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 20px;">
        <tr>
            <td>
                <div class="brand">{{ $gymName }}</div>
                @if ($payment->branch)
                    <div class="muted">{{ $payment->branch->name }} ({{ $payment->branch->code }})</div>
                    @if ($payment->branch->address)
                        <div class="muted">{{ $payment->branch->address }}</div>
                    @endif
                    @if ($payment->branch->phone)
                        <div class="muted">{{ $payment->branch->phone }}</div>
                    @endif
                @endif
            </td>
            <td class="right">
                <div style="font-size: 16px; font-weight: bold;">OFFICIAL RECEIPT</div>
                <div class="muted" style="margin-top: 4px;">{{ $payment->invoice_number }}</div>
                <div class="muted">Date: {{ ($payment->paid_at ?? $payment->created_at)->format('M d, Y h:i A') }}</div>
                <div style="margin-top: 6px;">
                    <span class="badge badge-{{ $payment->status }}">{{ strtoupper($payment->status) }}</span>
                </div>
            </td>
        </tr>
    </table>

    <table class="grid">
        <tr>
            <td style="width: 55%;">
                <div style="font-weight: bold; margin-bottom: 4px;">Billed To:</div>
                <div>{{ $payment->member->user->name }}</div>
                <div class="muted">Member ID: {{ $payment->member->member_code }}</div>
                <div class="muted">{{ $payment->member->user->email }}</div>
                @if ($payment->member->user->phone)
                    <div class="muted">{{ $payment->member->user->phone }}</div>
                @endif
            </td>
            <td class="right">
                <div style="font-weight: bold; margin-bottom: 4px;">Payment Details:</div>
                <div class="muted">Method: {{ strtoupper(str_replace('_', ' ', $payment->payment_method)) }}</div>
                <div class="muted">Gateway: {{ strtoupper($payment->gateway) }}</div>
                @if ($payment->reference_number)
                    <div class="muted">Ref #: {{ $payment->reference_number }}</div>
                @endif
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Period</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    @if ($payment->subscription && $payment->subscription->plan)
                        <strong>{{ $payment->subscription->plan->name }}</strong>
                        <div class="muted">{{ ucfirst(str_replace('_', ' ', $payment->subscription->plan->access_scope)) }} Membership</div>
                    @else
                        <strong>Gym Payment</strong>
                    @endif
                    @if ($payment->notes)
                        <div class="muted">{{ $payment->notes }}</div>
                    @endif
                </td>
                <td>
                    @if ($payment->subscription)
                        {{ $payment->subscription->starts_at->format('M d, Y') }} – {{ $payment->subscription->ends_at->format('M d, Y') }}
                    @else
                        —
                    @endif
                </td>
                <td class="right">{{ $currencySymbol }}{{ number_format((float) $payment->amount, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="2" class="right">Total Paid ({{ $payment->currency }})</td>
                <td class="right">{{ $currencySymbol }}{{ number_format((float) $payment->amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    @if ($payment->subscription && $payment->subscription->balance() > 0)
        <div style="margin-top: 12px; padding: 10px; background: #fef3c7; border-radius: 6px; font-size: 11px; color: #92400e;">
            Remaining Subscription Balance: <strong>{{ $currencySymbol }}{{ number_format($payment->subscription->balance(), 2) }}</strong>
            (Paid {{ $currencySymbol }}{{ number_format((float) $payment->subscription->amount_paid, 2) }} of {{ $currencySymbol }}{{ number_format((float) $payment->subscription->price, 2) }})
        </div>
    @endif

    <div class="footer">
        Thank you for training with {{ $gymName }}! This is a computer-generated receipt.
    </div>
</body>
</html>
