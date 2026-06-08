<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inquiry {{ $inquiry->inquiry_number }}</title>
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

        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; background: #fef3c7; color: #92400e; }

        .two-col { display: flex; gap: 40px; margin-bottom: 32px; }
        .col { flex: 1; }
        .section-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; margin-bottom: 6px; }
        .section-value { font-size: 14px; font-weight: 600; color: #111827; }
        .section-sub { font-size: 12px; color: #4b5563; margin-top: 2px; }

        .event-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px 20px; margin-bottom: 28px; }
        .event-box-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #6b7280; margin-bottom: 12px; }
        .event-grid { display: flex; gap: 28px; flex-wrap: wrap; }
        .event-item { margin-bottom: 8px; }
        .event-item label { font-size: 11px; color: #9ca3af; display: block; }
        .event-item span { font-size: 13px; font-weight: 600; color: #374151; }

        .message-box { margin-top: 8px; padding: 16px 20px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; }
        .message-box h3 { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 8px; }
        .message-box p { font-size: 12px; color: #4b5563; line-height: 1.6; }

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
            <h1>INQUIRY</h1>
            <div class="doc-number">{{ $inquiry->inquiry_number }}</div>
            <div class="doc-date">Received: {{ $inquiry->created_at->format('d M Y') }}</div>
            <div style="margin-top:6px">
                <span class="status-badge">{{ ucfirst(str_replace('_', ' ', $inquiry->status)) }}</span>
            </div>
        </div>
    </div>

    <div class="two-col">
        <div class="col">
            <div class="section-label">Client</div>
            @if($inquiry->client)
                <div class="section-value">{{ $inquiry->client->full_name }}</div>
                <div class="section-sub">{{ $inquiry->client->email }}</div>
                @if($inquiry->client->phone)
                    <div class="section-sub">{{ $inquiry->client->phone }}</div>
                @endif
            @else
                <div class="section-value">&mdash;</div>
            @endif
        </div>
        <div class="col">
            <div class="section-label">Venue Interest</div>
            <div class="section-value">{{ $inquiry->venue?->name ?? '—' }}</div>
        </div>
        <div class="col">
            <div class="section-label">Source</div>
            <div class="section-value">{{ ucfirst(str_replace('_', ' ', $inquiry->source ?? 'website')) }}</div>
        </div>
    </div>

    <div class="event-box">
        <div class="event-box-title">Event Details</div>
        <div class="event-grid">
            <div class="event-item">
                <label>Event Type</label>
                <span>{{ ucfirst(str_replace('_', ' ', $inquiry->event_type ?? '—')) }}</span>
            </div>
            <div class="event-item">
                <label>Preferred Date</label>
                <span>{{ $inquiry->preferred_date?->format('l, d F Y') ?? '—' }}</span>
            </div>
            @if($inquiry->alternate_date)
                <div class="event-item">
                    <label>Alternate Date</label>
                    <span>{{ $inquiry->alternate_date->format('d F Y') }}</span>
                </div>
            @endif
            <div class="event-item">
                <label>Guest Count</label>
                <span>{{ $inquiry->guest_count ? number_format($inquiry->guest_count).' guests' : '—' }}</span>
            </div>
            @if($inquiry->budget_range_min || $inquiry->budget_range_max)
                <div class="event-item">
                    <label>Budget Range</label>
                    <span>{{ number_format($inquiry->budget_range_min ?? 0, 2) }} &ndash; {{ number_format($inquiry->budget_range_max ?? 0, 2) }}</span>
                </div>
            @endif
            @if($inquiry->package)
                <div class="event-item">
                    <label>Package</label>
                    <span>{{ $inquiry->package->name }}</span>
                </div>
            @endif
        </div>
    </div>

    @if($inquiry->message)
        <div class="message-box">
            <h3>Message</h3>
            <p>{{ $inquiry->message }}</p>
        </div>
    @endif

    @if($inquiry->notes)
        <div class="message-box" style="margin-top:16px">
            <h3>Internal Notes</h3>
            <p>{{ $inquiry->notes }}</p>
        </div>
    @endif

    <div class="footer">
        {{ $branding['business_name'] ?? config('app.name') }} &middot; Inquiry {{ $inquiry->inquiry_number }}
    </div>
</div>
@if(!empty($print))
    <script>window.addEventListener('load', function () { window.print(); });</script>
@endif
</body>
</html>
