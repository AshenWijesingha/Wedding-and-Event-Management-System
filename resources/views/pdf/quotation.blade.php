<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation {{ $quotation->quotation_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 13px; color: #1f2937; line-height: 1.5; }
        .page { padding: 40px; max-width: 800px; margin: 0 auto; }

        /* Header */
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 36px; padding-bottom: 24px; border-bottom: 3px solid #4f46e5; }
        .company-name { font-size: 22px; font-weight: 700; color: #4f46e5; }
        .company-sub { font-size: 12px; color: #6b7280; margin-top: 2px; }
        .doc-title { text-align: right; }
        .doc-title h1 { font-size: 28px; font-weight: 700; color: #111827; letter-spacing: -0.5px; }
        .doc-title .doc-number { font-size: 14px; color: #4f46e5; font-weight: 600; margin-top: 4px; }
        .doc-title .doc-date { font-size: 12px; color: #6b7280; }

        /* Status badge */
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-draft { background: #fef3c7; color: #92400e; }
        .status-sent { background: #dbeafe; color: #1e40af; }
        .status-viewed { background: #e0e7ff; color: #3730a3; }
        .status-accepted { background: #d1fae5; color: #065f46; }
        .status-expired { background: #fee2e2; color: #991b1b; }

        /* Two-column section */
        .two-col { display: flex; gap: 40px; margin-bottom: 32px; }
        .col { flex: 1; }
        .section-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; margin-bottom: 6px; }
        .section-value { font-size: 14px; font-weight: 600; color: #111827; }
        .section-sub { font-size: 12px; color: #4b5563; margin-top: 2px; }

        /* Event details */
        .event-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px 20px; margin-bottom: 28px; }
        .event-box-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #6b7280; margin-bottom: 12px; }
        .event-grid { display: flex; gap: 20px; flex-wrap: wrap; }
        .event-item label { font-size: 11px; color: #9ca3af; display: block; }
        .event-item span { font-size: 13px; font-weight: 600; color: #374151; }

        /* Items table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        thead th { background: #4f46e5; color: white; padding: 10px 12px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        thead th:last-child { text-align: right; }
        tbody td { padding: 10px 12px; border-bottom: 1px solid #f3f4f6; font-size: 13px; }
        tbody td:last-child { text-align: right; font-weight: 500; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:nth-child(even) { background: #f9fafb; }

        /* Totals */
        .totals { margin-left: auto; width: 260px; margin-bottom: 28px; }
        .totals-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; }
        .totals-row.subtotal { border-top: 1px solid #e5e7eb; }
        .totals-row.discount { color: #059669; }
        .totals-row.tax { color: #6b7280; }
        .totals-row.total { border-top: 2px solid #4f46e5; padding-top: 10px; font-size: 16px; font-weight: 700; color: #4f46e5; }
        .totals-label { color: #6b7280; }

        /* Validity */
        .validity { text-align: center; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 10px; margin-bottom: 24px; font-size: 12px; color: #1e40af; }

        /* Terms */
        .terms { margin-top: 24px; padding-top: 18px; border-top: 1px solid #e5e7eb; }
        .terms h3 { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 8px; }
        .terms p { font-size: 11px; color: #6b7280; line-height: 1.6; }

        /* Footer */
        .footer { margin-top: 32px; padding-top: 16px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 11px; color: #9ca3af; }
    </style>
</head>
<body>
<div class="page">

    <!-- Header -->
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
            <h1>QUOTATION</h1>
            <div class="doc-number">{{ $quotation->quotation_number }}</div>
            <div class="doc-date">Issued: {{ $quotation->created_at->format('d M Y') }}</div>
            <div style="margin-top:6px">
                <span class="status-badge status-{{ $quotation->status }}">{{ ucfirst($quotation->status) }}</span>
            </div>
        </div>
    </div>

    <!-- Client & Venue -->
    <div class="two-col">
        <div class="col">
            <div class="section-label">Prepared For</div>
            @if($quotation->client)
                <div class="section-value">{{ $quotation->client->full_name }}</div>
                <div class="section-sub">{{ $quotation->client->email }}</div>
                @if($quotation->client->phone)
                    <div class="section-sub">{{ $quotation->client->phone }}</div>
                @endif
            @endif
        </div>
        <div class="col">
            <div class="section-label">Venue</div>
            @if($quotation->venue)
                <div class="section-value">{{ $quotation->venue->name }}</div>
                @if($quotation->venue->address)
                    <div class="section-sub">{{ $quotation->venue->address }}</div>
                @endif
            @endif
        </div>
        <div class="col">
            <div class="section-label">Valid Until</div>
            <div class="section-value">{{ $quotation->valid_until?->format('d M Y') ?? '—' }}</div>
            @if($quotation->valid_until && $quotation->valid_until->isFuture())
                <div class="section-sub" style="color:#059669">Still valid</div>
            @else
                <div class="section-sub" style="color:#dc2626">Expired</div>
            @endif
        </div>
    </div>

    <!-- Event Details -->
    <div class="event-box">
        <div class="event-box-title">Event Details</div>
        <div class="event-grid">
            <div class="event-item">
                <label>Event Date</label>
                <span>{{ $quotation->event_date?->format('l, d F Y') ?? '—' }}</span>
            </div>
            <div class="event-item">
                <label>Guest Count</label>
                <span>{{ number_format($quotation->guest_count) }} guests</span>
            </div>
            @if($quotation->package)
                <div class="event-item">
                    <label>Package</label>
                    <span>{{ $quotation->package->name }}</span>
                </div>
            @endif
        </div>
    </div>

    <!-- Items Table -->
    <table>
        <thead>
            <tr>
                <th style="width:50%">Description</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quotation->items ?? [] as $item)
                <tr>
                    <td>{{ $item['description'] }}</td>
                    <td>{{ $item['quantity'] ?? 1 }}</td>
                    <td>
                        @if(isset($item['unit_price']))
                            {{ number_format($item['unit_price'], 2) }}
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ number_format($item['total'] ?? $item['amount'] ?? (($item['unit_price'] ?? 0) * ($item['quantity'] ?? 1)), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals">
        <div class="totals-row subtotal">
            <span class="totals-label">Subtotal</span>
            <span>{{ number_format($quotation->subtotal, 2) }}</span>
        </div>
        @if($quotation->discount_amount > 0)
            <div class="totals-row discount">
                <span class="totals-label">Discount</span>
                <span>- {{ number_format($quotation->discount_amount, 2) }}</span>
            </div>
        @endif
        @if($quotation->tax_amount > 0)
            <div class="totals-row tax">
                <span class="totals-label">Tax</span>
                <span>{{ number_format($quotation->tax_amount, 2) }}</span>
            </div>
        @endif
        <div class="totals-row total">
            <span>Total</span>
            <span>{{ number_format($quotation->total_amount, 2) }}</span>
        </div>
    </div>

    <!-- Notes -->
    @if($quotation->notes)
        <div style="margin-bottom:20px; background:#fffbeb; border-left:3px solid #f59e0b; padding:12px 16px; border-radius:0 6px 6px 0;">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#92400e;margin-bottom:4px">Notes</div>
            <p style="font-size:12px;color:#78350f;">{{ $quotation->notes }}</p>
        </div>
    @endif

    <!-- Terms -->
    @if($quotation->terms_and_conditions)
        <div class="terms">
            <h3>Terms &amp; Conditions</h3>
            <p>{{ $quotation->terms_and_conditions }}</p>
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        This quotation was generated by {{ $branding['business_name'] ?? config('app.name') }} on {{ now()->format('d M Y') }}.
        For questions, contact us at {{ $branding['contact']['email'] ?? '' }}.
    </div>
</div>
</body>
</html>
