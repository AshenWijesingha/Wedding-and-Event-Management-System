<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt {{ $payment->payment_number }}</title>
    @include('pdf.partials.styles', [
        'primary' => $branding['colors']['primary'] ?? '#4f46e5',
        'accent' => $branding['colors']['accent'] ?? '#10b981',
    ])
</head>
<body>
@include('pdf.partials.footer', ['branding' => $branding, 'note' => 'Receipt '.$payment->payment_number])
<div class="page">

    @include('pdf.partials.header', [
        'branding' => $branding,
        'docType' => 'RECEIPT',
        'docNumber' => $payment->payment_number,
        'docDateLabel' => 'Date',
        'docDate' => $payment->payment_date?->format('d M Y') ?? $payment->created_at->format('d M Y'),
        'statusClass' => $payment->status,
        'statusText' => ucfirst($payment->status),
    ])

    <table class="info-table">
        <tr>
            <td style="width:50%">
                <div class="section-label">Received From</div>
                @if($payment->client)
                    <div class="section-value">{{ $payment->client->full_name }}</div>
                    <div class="section-sub">{{ $payment->client->email }}</div>
                @else
                    <div class="section-value">&mdash;</div>
                @endif
            </td>
            <td style="width:50%">
                <div class="section-label">Booking</div>
                <div class="section-value">{{ $payment->booking?->booking_number ?? '—' }}</div>
                @if($payment->booking)
                    <div class="section-sub">Balance: {{ number_format((float) $payment->booking->balance_amount, 2) }} {{ $payment->currency }}</div>
                @endif
            </td>
        </tr>
    </table>

    <div class="callout amount">
        <div class="label">Amount Paid</div>
        <div class="value">{{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}</div>
    </div>

    <table class="items">
        <tbody>
            <tr><td style="width:40%;color:#6b7280">Payment Number</td><td class="num" style="font-weight:600">{{ $payment->payment_number }}</td></tr>
            <tr><td style="color:#6b7280">Date</td><td class="num" style="font-weight:600">{{ $payment->payment_date?->format('d M Y') ?? '—' }}</td></tr>
            <tr><td style="color:#6b7280">Method</td><td class="num" style="font-weight:600">{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td></tr>
            @if($payment->installment_name)
                <tr><td style="color:#6b7280">For</td><td class="num" style="font-weight:600">{{ $payment->installment_name }}</td></tr>
            @endif
            @if($payment->reference_number)
                <tr><td style="color:#6b7280">Reference</td><td class="num" style="font-weight:600">{{ $payment->reference_number }}</td></tr>
            @endif
            @if($payment->gateway_payment_id)
                <tr><td style="color:#6b7280">Gateway Reference</td><td class="num" style="font-weight:600">{{ $payment->gateway_payment_id }}</td></tr>
            @endif
            <tr><td style="color:#6b7280">Status</td><td class="num" style="font-weight:600">{{ ucfirst($payment->status) }}</td></tr>
        </tbody>
    </table>

    @if($payment->notes)
        <div class="callout note">
            <div class="t">Notes</div>
            <p>{{ $payment->notes }}</p>
        </div>
    @endif
</div>
@if(!empty($print))
    <script>window.addEventListener('load', function () { window.print(); });</script>
@endif
</body>
</html>
