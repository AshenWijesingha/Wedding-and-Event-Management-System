<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Occupancy Report — {{ $period }}</title>
    @include('pdf.reports._styles', [
        'primary' => $branding['colors']['primary'] ?? '#4f46e5',
        'accent' => $branding['colors']['accent'] ?? '#10b981',
    ])
</head>
<body>
<div class="doc-footer">
    <div class="name">{{ $branding['business_name'] ?? config('app.name') }}</div>
    <div>Occupancy Report &middot; {{ $period }} &middot; Generated {{ now()->format('d M Y H:i') }}</div>
</div>
<div class="page">
    @include('pdf.reports._header', ['branding' => $branding, 'docType' => 'OCCUPANCY', 'subtitle' => 'Occupancy Report', 'period' => $period])

    @php
        $V = collect($venueOccupancy)->sortByDesc('occupancy_pct')->values();
        $maxPct = max($V->max('occupancy_pct') ?? 0, 0.0001);
        $avgPct = $V->count() ? $V->avg('occupancy_pct') : 0;
        $totalDays = $V->sum('booked_days');
        $top = $V->first();
        $active = $V->filter(fn ($v) => ($v['booked_days'] ?? 0) > 0)->count();
    @endphp

    @if($V->count())
        <table class="insights">
            <tr>
                <td>
                    <div class="insight">
                        <div class="i-label">Top Venue</div>
                        <div class="i-value" style="font-size:12px">{{ $top['venue'] ?? '—' }}</div>
                        <div class="i-sub">{{ rtrim(rtrim(number_format($top['occupancy_pct'] ?? 0, 1), '0'), '.') }}% occupied</div>
                    </div>
                </td>
                <td>
                    <div class="insight">
                        <div class="i-label">Avg Occupancy</div>
                        <div class="i-value">{{ rtrim(rtrim(number_format($avgPct, 1), '0'), '.') }}%</div>
                        <div class="i-sub">{{ $active }} of {{ $V->count() }} venues booked</div>
                    </div>
                </td>
                <td>
                    <div class="insight">
                        <div class="i-label">Total Booked Days</div>
                        <div class="i-value">{{ number_format($totalDays) }}</div>
                        <div class="i-sub">across all venues</div>
                    </div>
                </td>
            </tr>
        </table>
    @endif

    <h2 class="section">Venue Utilization <span class="section-hint">— ranked by occupancy</span></h2>
    @if($V->count())
        <table class="meter">
            @foreach($V as $v)
                @php
                    $rel = round((($v['occupancy_pct'] ?? 0) / $maxPct) * 100);
                    $tier = $rel >= 66 ? 'high' : ($rel >= 33 ? 'mid' : 'low');
                    $shown = ($v['booked_days'] ?? 0) > 0 ? max($rel, 3) : 0;
                @endphp
                <tr>
                    <td class="m-name">{{ $v['venue'] }}</td>
                    <td class="m-bar">
                        <div class="bar-track">
                            <div class="bar-fill {{ $tier }}" style="width: {{ $shown }}%;"></div>
                        </div>
                    </td>
                    <td class="m-val">{{ rtrim(rtrim(number_format($v['occupancy_pct'] ?? 0, 1), '0'), '.') }}%</td>
                    <td class="m-days">{{ $v['booked_days'] }} day(s)</td>
                </tr>
            @endforeach
        </table>
    @else
        <p style="text-align:center;color:#9ca3af;padding:24px 0">No venue data for this period.</p>
    @endif
</div>
</body>
</html>
