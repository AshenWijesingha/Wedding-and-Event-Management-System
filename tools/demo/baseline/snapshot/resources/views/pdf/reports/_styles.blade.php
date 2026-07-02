{{-- Shared report stylesheet. dompdf-safe (no flexbox/grid). Brand colours come
     from BrandingService. Expects: $primary, $accent --}}
@php
    $primary = $primary ?? '#4f46e5';
    $accent  = $accent  ?? '#10b981';
@endphp
<style>
    @page { margin: 0; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 12px; color: #1f2937; line-height: 1.5; }
    .page { padding: 30px 36px 68px; }

    /* Brand band header (table, not flex) */
    .brand-band { width: 100%; background: {{ $primary }}; border-radius: 10px; color: #fff; margin-bottom: 24px; }
    .brand-band table { width: 100%; border-collapse: collapse; }
    .brand-band td { padding: 18px 22px; vertical-align: middle; }
    .brand-logo { height: 34px; margin-bottom: 6px; }
    .brand-name { font-size: 19px; font-weight: 700; color: #fff; }
    .brand-sub { font-size: 11px; color: rgba(255,255,255,0.82); margin-top: 2px; }
    .doc-meta { text-align: right; }
    .doc-meta .doc-type { font-size: 23px; font-weight: 700; color: #fff; letter-spacing: 1px; }
    .doc-meta .doc-period { font-size: 12px; color: #fff; font-weight: 600; margin-top: 4px; }
    .doc-meta .doc-date { font-size: 10.5px; color: rgba(255,255,255,0.82); margin-top: 2px; }

    /* Stat cards (inline-block is dompdf-safe) */
    .cards { width: 100%; margin-bottom: 14px; }
    .card { display: inline-block; width: 31.5%; margin-right: 2%; background: #f9fafb; border: 1px solid #e9ebef; border-top: 3px solid {{ $primary }}; border-radius: 8px; padding: 12px 14px; vertical-align: top; }
    .card:last-child { margin-right: 0; }
    .card-label { display: block; font-size: 9.5px; text-transform: uppercase; letter-spacing: 0.6px; color: #9ca3af; }
    .card-value { display: block; font-size: 18px; font-weight: 700; margin-top: 4px; color: #111827; }
    .card-value.green { color: #059669; }
    .card-value.red { color: #dc2626; }
    .card-value.orange { color: #ea580c; }
    .card-value.indigo { color: {{ $primary }}; }

    h2.section { font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: {{ $primary }}; margin: 16px 0 9px; }
    .section-hint { font-size: 9.5px; color: #9ca3af; font-weight: 400; text-transform: none; letter-spacing: 0; }

    /* Insight strip — small KPI chips that fill the row under the cards */
    .insights { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    .insights td { width: 33.33%; padding: 0 8px; vertical-align: top; }
    .insights td:first-child { padding-left: 0; }
    .insights td:last-child { padding-right: 0; }
    .insight { background: #fff; border: 1px solid #e9ebef; border-left: 4px solid {{ $accent }}; border-radius: 8px; padding: 10px 12px; }
    .insight .i-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.6px; color: #9ca3af; }
    .insight .i-value { font-size: 14px; font-weight: 700; color: #111827; margin-top: 2px; }
    .insight .i-sub { font-size: 9.5px; color: #6b7280; margin-top: 1px; }

    /* Horizontal bar chart (label | track | value) */
    .chart { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .chart td { padding: 3px 0; vertical-align: middle; border: none; }
    .chart .c-label { width: 78px; font-size: 10.5px; color: #374151; padding-right: 12px; white-space: nowrap; }
    .chart .c-value { width: 132px; text-align: right; font-size: 10.5px; font-weight: 600; color: #111827; padding-left: 12px; font-variant-numeric: tabular-nums; }
    .bar-track { background: #eef0f3; border-radius: 6px; height: 15px; width: 100%; }
    .bar-fill { background: {{ $primary }}; height: 15px; border-radius: 6px; }
    .bar-fill.accent { background: {{ $accent }}; }
    .bar-fill.zero { background: #e5e7eb; }

    /* Segmented (stacked) bar for composition */
    .seg-track { height: 15px; width: 100%; background: #f1f3f5; border-radius: 6px; }
    .seg { display: inline-block; height: 15px; vertical-align: top; }
    /* Status uses a FIXED semantic palette (independent of brand colour) so the
       four categories stay distinguishable whatever the tenant primary is. */
    .seg.confirmed { background: #2563eb; }
    .seg.completed { background: #059669; }
    .seg.cancelled { background: #ef4444; }
    .seg.other { background: #cbd5e1; }

    /* Legend */
    .legend { margin: 0 0 8px; font-size: 9.5px; color: #6b7280; }
    .legend .dot { display: inline-block; width: 9px; height: 9px; border-radius: 2px; margin: 0 5px 0 14px; vertical-align: middle; }
    .legend .dot:first-child { margin-left: 0; }
    .dot.confirmed { background: #2563eb; }
    .dot.completed { background: #059669; }
    .dot.cancelled { background: #ef4444; }
    .dot.other { background: #cbd5e1; }

    /* Occupancy meter rows */
    .meter { width: 100%; border-collapse: collapse; }
    .meter td { padding: 5px 0; vertical-align: middle; border-bottom: 1px solid #f1f3f5; }
    .meter .m-name { font-size: 11px; color: #374151; padding-right: 12px; }
    .meter .m-bar { width: 46%; }
    .meter .m-val { width: 70px; text-align: right; font-size: 11px; font-weight: 700; padding-left: 12px; font-variant-numeric: tabular-nums; }
    .meter .m-days { width: 84px; text-align: right; font-size: 10px; color: #9ca3af; padding-left: 10px; }
    .bar-fill.high { background: #059669; }
    .bar-fill.mid { background: #ea580c; }
    .bar-fill.low { background: #cbd5e1; }

    table.data { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    table.data thead { display: table-header-group; }
    table.data thead th { background: {{ $primary }}; color: #fff; padding: 8px 10px; text-align: left; font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    table.data thead th.num { text-align: right; }
    table.data tbody tr { page-break-inside: avoid; }
    table.data tbody td { padding: 7px 10px; border-bottom: 1px solid #eef0f3; font-size: 12px; }
    table.data tbody td.num { text-align: right; font-variant-numeric: tabular-nums; }
    table.data tbody tr:nth-child(even) td { background: #f9fafb; }

    .doc-footer {
        position: fixed; bottom: 0; left: 0; right: 0; height: 58px;
        padding: 13px 36px 0; border-top: 2px solid {{ $accent }};
        text-align: center; font-size: 9.5px; color: #9ca3af;
    }
    .doc-footer .name { color: {{ $primary }}; font-weight: 700; font-size: 10.5px; }
</style>
