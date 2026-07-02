<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quotation {{ $quotation->quotation_number }}</title>
    @include('pdf.partials.styles', [
        'primary' => $branding['colors']['primary'] ?? '#4f46e5',
        'accent' => $branding['colors']['accent'] ?? '#10b981',
    ])
</head>
<body>
@include('pdf.partials.footer', ['branding' => $branding, 'note' => 'Quotation '.$quotation->quotation_number])
<div class="page">

    @include('pdf.partials.header', [
        'branding' => $branding,
        'docType' => 'QUOTATION',
        'docNumber' => $quotation->quotation_number,
        'docDateLabel' => 'Issued',
        'docDate' => $quotation->created_at->format('d M Y'),
        'statusClass' => $quotation->status,
        'statusText' => ucfirst($quotation->status),
    ])

    {{-- Client / Venue / Validity --}}
    <table class="info-table">
        <tr>
            <td style="width:40%">
                <div class="section-label">Prepared For</div>
                @if($quotation->client)
                    <div class="section-value">{{ $quotation->client->full_name }}</div>
                    <div class="section-sub">{{ $quotation->client->email }}</div>
                    @if($quotation->client->phone)
                        <div class="section-sub">{{ $quotation->client->phone }}</div>
                    @endif
                @else
                    <div class="section-value">&mdash;</div>
                @endif
            </td>
            <td style="width:35%">
                <div class="section-label">Venue</div>
                @if($quotation->venue)
                    <div class="section-value">{{ $quotation->venue->name }}</div>
                    @if($quotation->venue->address)
                        <div class="section-sub">{{ $quotation->venue->address }}</div>
                    @endif
                @else
                    <div class="section-value">&mdash;</div>
                @endif
            </td>
            <td style="width:25%">
                <div class="section-label">Valid Until</div>
                <div class="section-value">{{ $quotation->valid_until?->format('d M Y') ?? '—' }}</div>
                @if($quotation->valid_until && $quotation->valid_until->isFuture())
                    <div class="section-sub" style="color:#059669">Still valid</div>
                @elseif($quotation->valid_until)
                    <div class="section-sub" style="color:#dc2626">Expired</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- Event details --}}
    <div class="detail-box">
        <div class="detail-box-title">Event Details</div>
        <table class="detail-grid">
            <tr>
                <td style="width:34%">
                    <label>Event Date</label>
                    <span>{{ $quotation->event_date?->format('l, d F Y') ?? '—' }}</span>
                </td>
                <td style="width:33%">
                    <label>Guest Count</label>
                    <span>{{ $quotation->guest_count ? number_format($quotation->guest_count).' guests' : '—' }}</span>
                </td>
                <td style="width:33%">
                    <label>Package</label>
                    <span>{{ $quotation->package->name ?? '—' }}</span>
                </td>
            </tr>
        </table>
    </div>

    {{-- Items --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width:52%">Description</th>
                <th class="num">Qty</th>
                <th class="num">Unit Price</th>
                <th class="num">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quotation->items ?? [] as $item)
                <tr>
                    <td>
                        {{ $item['name'] ?? $item['description'] ?? '' }}
                        @if(!empty($item['description']) && !empty($item['name']) && $item['description'] !== $item['name'])
                            <div class="item-sub">{{ $item['description'] }}</div>
                        @endif
                    </td>
                    <td class="num">{{ $item['quantity'] ?? 1 }}</td>
                    <td class="num">{{ isset($item['unit_price']) ? number_format($item['unit_price'], 2) : '—' }}</td>
                    <td class="num">{{ number_format($item['total'] ?? $item['amount'] ?? (($item['unit_price'] ?? 0) * ($item['quantity'] ?? 1)), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="totals">
        <table>
            <tr class="subtotal">
                <td class="label">Subtotal</td>
                <td class="value">{{ number_format($quotation->subtotal, 2) }}</td>
            </tr>
            @if($quotation->discount_amount > 0)
                <tr class="discount">
                    <td class="label">Discount</td>
                    <td class="value">- {{ number_format($quotation->discount_amount, 2) }}</td>
                </tr>
            @endif
            @if($quotation->tax_amount > 0)
                <tr>
                    <td class="label">Tax</td>
                    <td class="value">{{ number_format($quotation->tax_amount, 2) }}</td>
                </tr>
            @endif
            <tr class="total">
                <td class="label">Total</td>
                <td class="value">{{ number_format($quotation->total_amount, 2) }}</td>
            </tr>
        </table>
    </div>

    @if($quotation->valid_until)
        <div class="callout info">
            This quotation is valid until {{ $quotation->valid_until->format('d F Y') }}. We look forward to making your event memorable.
        </div>
    @endif

    @if($quotation->notes)
        <div class="callout note">
            <div class="t">Notes</div>
            <p>{{ $quotation->notes }}</p>
        </div>
    @endif

    @if($quotation->terms_and_conditions)
        <div class="terms">
            <h3>Terms &amp; Conditions</h3>
            <p>{{ $quotation->terms_and_conditions }}</p>
        </div>
    @endif
</div>
@if(!empty($print))
    <script>window.addEventListener('load', function () { window.print(); });</script>
@endif
</body>
</html>
