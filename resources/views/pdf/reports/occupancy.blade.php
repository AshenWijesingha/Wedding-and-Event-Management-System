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

    <h2 class="section">Venue Utilization</h2>
    <table class="data">
        <thead>
            <tr><th>Venue</th><th class="num">Booked Days</th><th class="num">Occupancy %</th></tr>
        </thead>
        <tbody>
            @forelse($venueOccupancy as $v)
                <tr>
                    <td>{{ $v['venue'] }}</td>
                    <td class="num">{{ $v['booked_days'] }}</td>
                    <td class="num">{{ $v['occupancy_pct'] }}%</td>
                </tr>
            @empty
                <tr><td colspan="3" style="text-align:center;color:#9ca3af">No venue data for this period.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
</body>
</html>
