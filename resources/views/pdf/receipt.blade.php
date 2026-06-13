<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt {{ $payment->payment_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 13px; color: #1f2937; line-height: 1.5; }
        .page { padding: 40px; max-width: 800px; margin: 0 auto; }

        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 36px; padding-bottom: 24px; border-bottom: 3px solid #4f46e5; }
        .company-name { font-size: 22px; font-weight: 700; color: #4f46e5; }
        .company-sub { font-size: 12px; color: #6b7280; margin-top: 2px; }
        .doc-title { text-align: right; }
        .doc-title h1 { font-size: 28px; font-weight: 700; color: #111827; letter-spacing: -0.5px; }
        .doc-title .doc-number { font-size: 14px; color: #4f46e5; font-weight: 600; margin-top: 4px; }
        .doc-title .doc-date { font-size: 12px; color: #6b7280; }

        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-failed { background: #fee2e2; color: #991b1b; }
        .status-refunded { background: #e0e7ff; color: #3730a3; }

        .two-col { display: flex; gap: 40px; margin-bottom: 32px; }
        .col { flex: 1; }
        .section-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; margin-bottom: 6px; }
        .section-value { font-size: 14px; font-weight: 600; color: #111827; }
        .section-sub { font-size: 12px; color: #4b5563; margin-top: 2px; }

        .amount-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px 24px; margin-bottom: 28px; text-align: center; }
        .amount-box .label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; }
        .amount-box .value { font-size: 32px; font-weight: 700; color: #4f46e5; margin-top: 4px; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        tbody td { padding: 10px 12px; border-bottom: 1px solid #f3f4f6; font-size: 13px; }
        tbody td:first-child { color: #6b7280; width: 40%; }
        tbody td:last-child { text-align: right; font-weight: 600; }
        tbody tr:last-child td { border-bottom: none; }

        .footer { margin-top: 32px; padding-top: 16px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 11px; color: #9ca3af; }
    </style>
</head>
<body>
<div class="page">

    <div class="header">
        <div>
            <div class="company-name">{{ $branding['business_name'] ?? config('app.name') }}</div>
            @if(!empty($branding['address']))
                <div class="company-sub">{{ $branding['address'] }}</div>
            @endif
            @if(!empty($branding['phone']))
                <div class="company-sub">{{ $branding['phone'] }}</div>
            @endif
        </div>
        <div class="doc-title">
            <h1>RECEIPT</h1>
            <div class="doc-number">{{ $payment->payment_number }}</div>
            <div class="doc-date">{{ $payment->payment_date?->format('d M Y') ?? $payment->created_at->format('d M Y') }}</div>
            <div style="margin-top:6px">
                <span class="status-badge status-{{ $payment->status }}">{{ ucfirst($payment->status) }}</span>
            </div>
        </div>
    </div>

    <div class="two-col">
        <div class="col">
            <div class="section-label">Received From</div>
            @if($payment->client)
                <div class="section-value">{{ $payment->client->full_name }}</div>
                <div class="section-sub">{{ $payment->client->email }}</div>
            @endif
        </div>
        <div class="col">
            <div class="section-label">Booking</div>
            <div class="section-value">{{ $payment->booking?->booking_number ?? '—' }}</div>
            @if($payment->booking)
                <div class="section-sub">Balance: {{ number_format((float) $payment->booking->balance_amount, 2) }} {{ $payment->currency }}</div>
            @endif
        </div>
    </div>

    <div class="amount-box">
        <div class="label">Amount Paid</div>
        <div class="value">{{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}</div>
    </div>

    <table>
        <tbody>
            <tr><td>Payment Number</td><td>{{ $payment->payment_number }}</td></tr>
            <tr><td>Date</td><td>{{ $payment->payment_date?->format('d M Y') ?? '—' }}</td></tr>
            <tr><td>Method</td><td>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td></tr>
            @if($payment->installment_name)
                <tr><td>For</td><td>{{ $payment->installment_name }}</td></tr>
            @endif
            @if($payment->reference_number)
                <tr><td>Reference</td><td>{{ $payment->reference_number }}</td></tr>
            @endif
            @if($payment->gateway_payment_id)
                <tr><td>Gateway Reference</td><td>{{ $payment->gateway_payment_id }}</td></tr>
            @endif
            <tr><td>Status</td><td>{{ ucfirst($payment->status) }}</td></tr>
        </tbody>
    </table>

    @if($payment->notes)
        <div style="margin-bottom:20px; background:#fffbeb; border-left:3px solid #f59e0b; padding:12px 16px; border-radius:0 6px 6px 0;">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#92400e;margin-bottom:4px">Notes</div>
            <p style="font-size:12px;color:#78350f;">{{ $payment->notes }}</p>
        </div>
    @endif

    <div class="footer">
        This receipt was generated by {{ $branding['business_name'] ?? config('app.name') }} on {{ now()->format('d M Y') }}.
    </div>
</div>
@if(!empty($print))
    <script>window.addEventListener('load', function () { window.print(); });</script>
@endif
</body>
</html>
