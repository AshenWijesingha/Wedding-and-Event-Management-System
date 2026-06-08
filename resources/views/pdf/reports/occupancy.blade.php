<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Occupancy Report — {{ $period }}</title>
    @include('pdf.reports._styles')
</head>
<body>
<div class="page">
    <div class="header">
        <div>
            <div class="company-name">{{ config('app.name') }}</div>
            <div class="company-sub">Occupancy Report</div>
        </div>
        <div class="doc-title">
            <h1>OCCUPANCY</h1>
            <div class="doc-period">{{ $period }}</div>
            <div class="doc-date">Generated {{ now()->format('d M Y') }}</div>
        </div>
    </div>

    <h2 class="section">Venue Utilization</h2>
    <table>
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

    <div class="footer">{{ config('app.name') }} — generated {{ now()->format('d M Y H:i') }}</div>
</div>
</body>
</html>
