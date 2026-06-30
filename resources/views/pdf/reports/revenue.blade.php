<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Revenue Report — {{ $period }}</title>
    @include('pdf.reports._styles', [
        'primary' => $branding['colors']['primary'] ?? '#4f46e5',
        'accent' => $branding['colors']['accent'] ?? '#10b981',
    ])
</head>
<body>
<div class="doc-footer">
    <div class="name">{{ $branding['business_name'] ?? config('app.name') }}</div>
    <div>Revenue Report &middot; {{ $period }} &middot; Generated {{ now()->format('d M Y H:i') }}</div>
</div>
<div class="page">
    @include('pdf.reports._header', ['branding' => $branding, 'docType' => 'REVENUE', 'subtitle' => 'Revenue Report', 'period' => $period])

    <div class="cards">
        <div class="card">
            <span class="card-label">Collected</span>
            <span class="card-value green">{{ number_format($totals['collected'], 2) }}</span>
        </div>
        <div class="card">
            <span class="card-label">Outstanding</span>
            <span class="card-value red">{{ number_format($totals['outstanding'], 2) }}</span>
        </div>
        <div class="card">
            <span class="card-label">Refunded</span>
            <span class="card-value orange">{{ number_format($totals['refunded'], 2) }}</span>
        </div>
    </div>

    <h2 class="section">Monthly Breakdown</h2>
    <table class="data">
        <thead>
            <tr><th>Month</th><th class="num">Revenue</th><th class="num">Payments</th></tr>
        </thead>
        <tbody>
            @foreach($months as $m)
                <tr>
                    <td>{{ $m['label'] }}</td>
                    <td class="num">{{ number_format($m['revenue'], 2) }}</td>
                    <td class="num">{{ $m['count'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if(count($byMethod))
        <h2 class="section">By Payment Method</h2>
        <table class="data">
            <thead>
                <tr><th>Method</th><th class="num">Total</th><th class="num">Transactions</th></tr>
            </thead>
            <tbody>
                @foreach($byMethod as $m)
                    <tr>
                        <td>{{ ucwords(str_replace('_', ' ', $m['method'] ?? '—')) }}</td>
                        <td class="num">{{ number_format($m['total'], 2) }}</td>
                        <td class="num">{{ $m['count'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
</body>
</html>
