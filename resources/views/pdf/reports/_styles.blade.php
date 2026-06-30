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
    .page { padding: 32px 36px 84px; }

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
    .cards { width: 100%; margin-bottom: 22px; }
    .card { display: inline-block; width: 31.5%; margin-right: 2%; background: #f9fafb; border: 1px solid #e9ebef; border-top: 3px solid {{ $primary }}; border-radius: 8px; padding: 12px 14px; vertical-align: top; }
    .card:last-child { margin-right: 0; }
    .card-label { display: block; font-size: 9.5px; text-transform: uppercase; letter-spacing: 0.6px; color: #9ca3af; }
    .card-value { display: block; font-size: 18px; font-weight: 700; margin-top: 4px; color: #111827; }
    .card-value.green { color: #059669; }
    .card-value.red { color: #dc2626; }
    .card-value.orange { color: #ea580c; }
    .card-value.indigo { color: {{ $primary }}; }

    h2.section { font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: {{ $primary }}; margin: 22px 0 10px; }

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
