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

    <div class="cards">
        <div class="card">
            <span class="card-label">Total</span>
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
    <div class="cards" style="margin-top:10px">
        <div class="card" style="width:100%">
            <span class="card-label">Revenue (excl. cancelled)</span>
            <span class="card-value indigo">{{ number_format($totals['revenue'], 2) }}</span>
        </div>
    </div>

    <h2 class="section">Monthly Breakdown</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Month</th>
                <th class="num">Total</th>
                <th class="num">Confirmed</th>
                <th class="num">Completed</th>
                <th class="num">Cancelled</th>
            </tr>
        </thead>
        <tbody>
            @foreach($months as $m)
                <tr>
                    <td>{{ $m['label'] }}</td>
                    <td class="num">{{ $m['total'] }}</td>
                    <td class="num">{{ $m['confirmed'] }}</td>
                    <td class="num">{{ $m['completed'] }}</td>
                    <td class="num">{{ $m['cancelled'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if(count($byEventType))
        <h2 class="section">By Event Type</h2>
        <table class="data">
            <thead>
                <tr><th>Event Type</th><th class="num">Count</th><th class="num">Revenue</th></tr>
            </thead>
            <tbody>
                @foreach($byEventType as $t)
                    <tr>
                        <td>{{ ucwords(str_replace('_', ' ', $t['type'] ?? '—')) }}</td>
                        <td class="num">{{ $t['count'] }}</td>
                        <td class="num">{{ number_format($t['revenue'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
</body>
</html>
