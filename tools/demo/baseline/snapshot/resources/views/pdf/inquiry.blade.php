<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inquiry {{ $inquiry->inquiry_number }}</title>
    @include('pdf.partials.styles', [
        'primary' => $branding['colors']['primary'] ?? '#4f46e5',
        'accent' => $branding['colors']['accent'] ?? '#10b981',
    ])
</head>
<body>
@include('pdf.partials.footer', ['branding' => $branding, 'note' => 'Inquiry '.$inquiry->inquiry_number])
<div class="page">

    @include('pdf.partials.header', [
        'branding' => $branding,
        'docType' => 'INQUIRY',
        'docNumber' => $inquiry->inquiry_number,
        'docDateLabel' => 'Received',
        'docDate' => $inquiry->created_at->format('d M Y'),
        'statusClass' => 'neutral',
        'statusText' => ucfirst(str_replace('_', ' ', $inquiry->status)),
    ])

    <table class="info-table">
        <tr>
            <td style="width:40%">
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
            </td>
            <td style="width:35%">
                <div class="section-label">Venue Interest</div>
                <div class="section-value">{{ $inquiry->venue?->name ?? '—' }}</div>
            </td>
            <td style="width:25%">
                <div class="section-label">Source</div>
                <div class="section-value">{{ ucfirst(str_replace('_', ' ', $inquiry->source ?? 'website')) }}</div>
            </td>
        </tr>
    </table>

    <div class="detail-box">
        <div class="detail-box-title">Event Details</div>
        <table class="detail-grid">
            <tr>
                <td style="width:34%">
                    <label>Event Type</label>
                    <span>{{ ucfirst(str_replace('_', ' ', $inquiry->event_type ?? '—')) }}</span>
                </td>
                <td style="width:33%">
                    <label>Preferred Date</label>
                    <span>{{ $inquiry->preferred_date?->format('l, d F Y') ?? '—' }}</span>
                </td>
                <td style="width:33%">
                    <label>Guest Count</label>
                    <span>{{ $inquiry->guest_count ? number_format($inquiry->guest_count).' guests' : '—' }}</span>
                </td>
            </tr>
            <tr>
                @if($inquiry->alternate_date)
                    <td>
                        <label>Alternate Date</label>
                        <span>{{ $inquiry->alternate_date->format('d F Y') }}</span>
                    </td>
                @endif
                @if($inquiry->budget_range_min || $inquiry->budget_range_max)
                    <td>
                        <label>Budget Range</label>
                        <span>{{ number_format($inquiry->budget_range_min ?? 0, 2) }} &ndash; {{ number_format($inquiry->budget_range_max ?? 0, 2) }}</span>
                    </td>
                @endif
                @if($inquiry->package)
                    <td>
                        <label>Package</label>
                        <span>{{ $inquiry->package->name }}</span>
                    </td>
                @endif
            </tr>
        </table>
    </div>

    @if($inquiry->message)
        <div class="panel">
            <h3>Message</h3>
            <p>{{ $inquiry->message }}</p>
        </div>
    @endif

    @if($inquiry->notes)
        <div class="panel">
            <h3>Internal Notes</h3>
            <p>{{ $inquiry->notes }}</p>
        </div>
    @endif
</div>
@if(!empty($print))
    <script>window.addEventListener('load', function () { window.print(); });</script>
@endif
</body>
</html>
