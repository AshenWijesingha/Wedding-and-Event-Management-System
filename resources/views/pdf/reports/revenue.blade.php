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

    @php
        $M = collect($months);
        $maxRev = max($M->max('revenue') ?? 0, 1);
        $activeMonths = $M->filter(fn ($m) => $m['revenue'] > 0);
        $peak = $M->sortByDesc('revenue')->first();
        $avgActive = $activeMonths->count() ? $activeMonths->avg('revenue') : 0;
        $billed = ($totals['collected'] ?? 0) + ($totals['outstanding'] ?? 0);
        $collectionRate = $billed > 0 ? round(($totals['collected'] / $billed) * 100) : 0;
        $methods = collect($byMethod);
        $maxMethod = max($methods->max('total') ?? 0, 1);
        $methodTotal = max($methods->sum('total'), 1);
    @endphp

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

    <table class="insights">
        <tr>
            <td>
                <div class="insight">
                    <div class="i-label">Collection Rate</div>
                    <div class="i-value">{{ $collectionRate }}%</div>
                    <div class="i-sub">of {{ number_format($billed, 0) }} billed collected</div>
                </div>
            </td>
            <td>
                <div class="insight">
                    <div class="i-label">Peak Month</div>
                    <div class="i-value">{{ $peak['label'] ?? '—' }}</div>
                    <div class="i-sub">{{ number_format($peak['revenue'] ?? 0, 0) }} collected</div>
                </div>
            </td>
            <td>
                <div class="insight">
                    <div class="i-label">Avg / Active Month</div>
                    <div class="i-value">{{ number_format($avgActive, 0) }}</div>
                    <div class="i-sub">{{ $activeMonths->count() }} month(s) with revenue</div>
                </div>
            </td>
        </tr>
    </table>

    <h2 class="section">Monthly Revenue <span class="section-hint">— collected payments</span></h2>
    <table class="chart">
        @foreach($months as $m)
            @php $pct = round(($m['revenue'] / $maxRev) * 100); @endphp
            <tr>
                <td class="c-label">{{ $m['label'] }}</td>
                <td>
                    <div class="bar-track">
                        <div class="bar-fill {{ $m['revenue'] > 0 ? '' : 'zero' }}" style="width: {{ max($pct, $m['revenue'] > 0 ? 2 : 0) }}%;"></div>
                    </div>
                </td>
                <td class="c-value">{{ number_format($m['revenue'], 0) }} <span style="color:#9ca3af;font-weight:400">({{ $m['count'] }})</span></td>
            </tr>
        @endforeach
    </table>

    @if($methods->count())
        <h2 class="section">By Payment Method <span class="section-hint">— share of collected</span></h2>
        <table class="chart">
            @foreach($methods->sortByDesc('total') as $m)
                @php $pct = round(($m['total'] / $maxMethod) * 100); $share = round(($m['total'] / $methodTotal) * 100); @endphp
                <tr>
                    <td class="c-label">{{ ucwords(str_replace('_', ' ', $m['method'] ?? '—')) }}</td>
                    <td>
                        <div class="bar-track">
                            <div class="bar-fill accent" style="width: {{ max($pct, 2) }}%;"></div>
                        </div>
                    </td>
                    <td class="c-value">{{ number_format($m['total'], 0) }} <span style="color:#9ca3af;font-weight:400">({{ $share }}%)</span></td>
                </tr>
            @endforeach
        </table>
    @endif
</div>
</body>
</html>
