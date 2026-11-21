<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $gymName }} Report ({{ $from }} to {{ $to }})</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; padding: 20px; }
        h1 { font-size: 18px; margin: 0 0 4px 0; }
        .muted { color: #64748b; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th { background: #f1f5f9; text-align: left; padding: 7px; border-bottom: 1px solid #cbd5e1; font-size: 10px; text-transform: uppercase; }
        td { padding: 7px; border-bottom: 1px solid #e2e8f0; }
        .right { text-align: right; }
        .summary td { border: 1px solid #e2e8f0; background: #f8fafc; padding: 10px; }
    </style>
</head>
<body>
    <h1>{{ $gymName }} — Financial &amp; Operations Report</h1>
    <div class="muted">Scope: {{ $currentBranch ? $currentBranch->name : 'All Branches' }} · Period: {{ $from }} to {{ $to }}</div>

    <table class="summary">
        <tr>
            <td><div class="muted">Total Revenue</div><strong>{{ $currencySymbol }}{{ number_format($totalRevenue, 2) }}</strong></td>
            <td><div class="muted">Membership Rev</div><strong>{{ $currencySymbol }}{{ number_format($membershipRevenue, 2) }}</strong></td>
            <td><div class="muted">Retail Rev</div><strong>{{ $currencySymbol }}{{ number_format($retailRevenue, 2) }}</strong></td>
            <td><div class="muted">New Members / Check-ins</div><strong>{{ $newMembers }} / {{ $checkins }}</strong></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Date</th>
                <th>Member</th>
                <th>Method</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($payments as $p)
                <tr>
                    <td>{{ $p->invoice_number }}</td>
                    <td>{{ ($p->paid_at ?? $p->created_at)->format('Y-m-d') }}</td>
                    <td>{{ $p->member->user->name }}</td>
                    <td>{{ strtoupper(str_replace('_', ' ', $p->payment_method)) }}</td>
                    <td class="right">{{ $currencySymbol }}{{ number_format((float) $p->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
