<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Report — {{ $period }}</title>
    @include('pdf.reports._styles', [
        'primary' => $branding['colors']['primary'] ?? '#4f46e5',
        'accent' => $branding['colors']['accent'] ?? '#10b981',
    ])
</head>
<body>
<div class="doc-footer">
    <div class="name">{{ $branding['business_name'] ?? config('app.name') }}</div>
    <div>Booking Report &middot; {{ $period }} &middot; Generated {{ now()->format('d M Y H:i') }}</div>
</div>
<div class="page">
    @include('pdf.reports._header', ['branding' => $branding, 'docType' => 'BOOKINGS', 'subtitle' => 'Booking Report', 'period' => $period])

    @php
        $M = collect($months);
        $maxTotal = max($M->max('total') ?? 0, 1);
        $busiest = $M->sortByDesc('total')->first();
        $confRate = ($totals['total'] ?? 0) > 0 ? round(((($totals['confirmed'] ?? 0)) / $totals['total']) * 100) : 0;
        $cancelRate = ($totals['total'] ?? 0) > 0 ? round((($totals['cancelled'] ?? 0) / $totals['total']) * 100) : 0;
        $netBookings = max(($totals['total'] ?? 0) - ($totals['cancelled'] ?? 0), 0);
        $avgValue = $netBookings > 0 ? ($totals['revenue'] ?? 0) / $netBookings : 0;
        $types = collect($byEventType);
        $maxType = max($types->max('count') ?? 0, 1);
        $typeTotal = max($types->sum('count'), 1);
        $w = fn ($n) => round(($n / $maxTotal) * 100, 1);
    @endphp

    <div class="cards">
        <div class="card">
            <span class="card-label">Total Bookings</span>
            <span class="card-value indigo">{{ $totals['total'] }}</span>
        </div>
        <div class="card">
            <span class="card-label">Confirmed</span>
            <span class="card-value green">{{ $totals['confirmed'] }}</span>
        </div>
        <div class="card">
            <span class="card-label">Cancelled</span>
            <span class="card-value red">{{ $totals['cancelled'] }}</span>
        </div>
    </div>

    <table class="insights">
        <tr>
            <td>
                <div class="insight">
                    <div class="i-label">Confirmation Rate</div>
                    <div class="i-value">{{ $confRate }}%</div>
                    <div class="i-sub">{{ $cancelRate }}% cancelled</div>
                </div>
            </td>
            <td>
                <div class="insight">
                    <div class="i-label">Busiest Month</div>
                    <div class="i-value">{{ $busiest['label'] ?? '—' }}</div>
                    <div class="i-sub">{{ $busiest['total'] ?? 0 }} booking(s)</div>
                </div>
            </td>
            <td>
                <div class="insight">
                    <div class="i-label">Avg Booking Value</div>
                    <div class="i-value">{{ number_format($avgValue, 0) }}</div>
                    <div class="i-sub">{{ number_format($totals['revenue'], 0) }} over {{ $netBookings }}</div>
                </div>
            </td>
        </tr>
    </table>

    <h2 class="section">Monthly Bookings <span class="section-hint">— by status</span></h2>
    <div class="legend">
        <span class="dot confirmed"></span>Confirmed
        <span class="dot completed"></span>Completed
        <span class="dot cancelled"></span>Cancelled
        <span class="dot other"></span>Other
    </div>
    <table class="chart">
        @foreach($months as $m)
            @php $other = max($m['total'] - $m['confirmed'] - $m['completed'] - $m['cancelled'], 0); @endphp
            <tr>
                <td class="c-label">{{ $m['label'] }}</td>
                <td>
                    <div class="seg-track">
                        @if($m['confirmed'])<div class="seg confirmed" style="width: {{ $w($m['confirmed']) }}%;"></div>@endif
                        @if($m['completed'])<div class="seg completed" style="width: {{ $w($m['completed']) }}%;"></div>@endif
                        @if($m['cancelled'])<div class="seg cancelled" style="width: {{ $w($m['cancelled']) }}%;"></div>@endif
                        @if($other)<div class="seg other" style="width: {{ $w($other) }}%;"></div>@endif
                    </div>
                </td>
                <td class="c-value">{{ $m['total'] }}</td>
            </tr>
        @endforeach
    </table>

    @if($types->count())
        <h2 class="section">By Event Type <span class="section-hint">— share of bookings</span></h2>
        <table class="chart">
            @foreach($types->sortByDesc('count') as $t)
                @php $pct = round(($t['count'] / $maxType) * 100); $share = round(($t['count'] / $typeTotal) * 100); @endphp
                <tr>
                    <td class="c-label">{{ ucwords(str_replace('_', ' ', $t['type'] ?? '—')) }}</td>
                    <td>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: {{ max($pct, 2) }}%;"></div>
                        </div>
                    </td>
                    <td class="c-value">{{ $t['count'] }} <span style="color:#9ca3af;font-weight:400">({{ $share }}%)</span></td>
                </tr>
            @endforeach
        </table>
    @endif
</div>
</body>
</html>
