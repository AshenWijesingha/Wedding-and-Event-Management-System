{{-- Shared, dompdf-safe stylesheet for customer-facing documents (quotation,
     inquiry, receipt). dompdf has NO flexbox/grid support, so every multi-column
     layout here is built with tables or inline-block. Brand colours are injected
     from BrandingService (already sanitised to a CSS-colour shape).
     Expects: $primary, $accent --}}
@php
    $primary = $primary ?? '#4f46e5';
    $accent  = $accent  ?? '#10b981';
@endphp
<style>
    @page { margin: 0; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { width: 100%; }
    body {
        font-family: 'DejaVu Sans', Arial, sans-serif;
        font-size: 12.5px;
        color: #1f2937;
        line-height: 1.5;
    }
    .page { padding: 30px 40px 70px; }

    /* ---- Header band -------------------------------------------------- */
    .brand-band {
        width: 100%;
        background: {{ $primary }};
        border-radius: 10px;
        color: #ffffff;
        margin-bottom: 20px;
    }
    .brand-band table { width: 100%; border-collapse: collapse; }
    .brand-band td { padding: 16px 22px; vertical-align: middle; }
    .brand-logo { height: 38px; margin-bottom: 6px; }
    .brand-name { font-size: 21px; font-weight: 700; color: #ffffff; letter-spacing: -0.2px; }
    .brand-tagline { font-size: 11px; color: rgba(255,255,255,0.82); margin-top: 2px; }
    .brand-contact { font-size: 10.5px; color: rgba(255,255,255,0.82); margin-top: 6px; line-height: 1.45; }
    .doc-meta { text-align: right; }
    .doc-meta .doc-type { font-size: 26px; font-weight: 700; color: #ffffff; letter-spacing: 1px; }
    .doc-meta .doc-number { font-size: 13px; color: #ffffff; font-weight: 600; margin-top: 4px; }
    .doc-meta .doc-date { font-size: 11px; color: rgba(255,255,255,0.82); margin-top: 2px; }

    /* ---- Status badge ------------------------------------------------- */
    .status-badge { display: inline-block; padding: 3px 11px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; margin-top: 8px; }
    .status-draft, .status-pending { background: #fef3c7; color: #92400e; }
    .status-sent { background: #dbeafe; color: #1e40af; }
    .status-viewed { background: #e0e7ff; color: #3730a3; }
    .status-accepted, .status-completed { background: #d1fae5; color: #065f46; }
    .status-expired, .status-failed { background: #fee2e2; color: #991b1b; }
    .status-refunded { background: #e0e7ff; color: #3730a3; }
    .status-neutral { background: #f1f5f9; color: #475569; }

    /* ---- Info columns (table, not flex) ------------------------------- */
    .info-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
    .info-table td { vertical-align: top; padding-right: 24px; }
    .info-table td:last-child { padding-right: 0; }
    .section-label { font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; margin-bottom: 5px; }
    .section-value { font-size: 13.5px; font-weight: 600; color: #111827; }
    .section-sub { font-size: 11.5px; color: #4b5563; margin-top: 2px; }

    /* ---- Boxed detail block ------------------------------------------- */
    .detail-box { background: #f9fafb; border: 1px solid #e9ebef; border-left: 4px solid {{ $primary }}; border-radius: 8px; padding: 14px 18px; margin-bottom: 18px; }
    .detail-box-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: {{ $primary }}; margin-bottom: 12px; }
    .detail-grid { width: 100%; border-collapse: collapse; }
    .detail-grid td { vertical-align: top; padding: 0 18px 8px 0; }
    .detail-grid label { font-size: 10px; color: #9ca3af; display: block; }
    .detail-grid span { font-size: 12.5px; font-weight: 600; color: #374151; }

    /* ---- Line items table --------------------------------------------- */
    table.items { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
    table.items thead { display: table-header-group; }
    table.items thead th { background: {{ $primary }}; color: #ffffff; padding: 8px 12px; text-align: left; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    table.items thead th.num { text-align: right; }
    table.items tbody tr { page-break-inside: avoid; }
    table.items tbody td { padding: 6px 12px; border-bottom: 1px solid #eef0f3; font-size: 12.5px; }
    table.items tbody td.num { text-align: right; font-variant-numeric: tabular-nums; }
    table.items tbody tr:nth-child(even) td { background: #f9fafb; }
    table.items .item-sub { font-size: 10.5px; color: #6b7280; margin-top: 2px; }

    /* ---- Totals (table, not flex) ------------------------------------- */
    .totals { width: 270px; margin-left: auto; margin-bottom: 16px; }
    .totals table { width: 100%; border-collapse: collapse; }
    .totals td { padding: 5px 0; font-size: 12.5px; }
    .totals td.label { color: #6b7280; }
    .totals td.value { text-align: right; font-weight: 600; color: #111827; }
    .totals tr.subtotal td { border-top: 1px solid #e5e7eb; padding-top: 8px; }
    .totals tr.discount td.value { color: #059669; }
    .totals tr.total td { border-top: 2px solid {{ $primary }}; padding-top: 9px; font-size: 16px; font-weight: 700; color: {{ $primary }}; }

    /* ---- Callouts ----------------------------------------------------- */
    .callout { border-radius: 8px; padding: 11px 16px; margin-bottom: 14px; }
    .callout.amount { background: #f9fafb; border: 1px solid #e9ebef; text-align: center; padding: 20px 24px; }
    .callout.amount .label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; }
    .callout.amount .value { font-size: 30px; font-weight: 700; color: {{ $primary }}; margin-top: 4px; }
    .callout.note { background: #fffbeb; border-left: 4px solid #f59e0b; }
    .callout.note .t { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #92400e; margin-bottom: 4px; }
    .callout.note p { font-size: 11.5px; color: #78350f; }
    .callout.info { background: #eff6ff; border: 1px solid #bfdbfe; text-align: center; font-size: 11.5px; color: #1e40af; }

    .panel { background: #f9fafb; border: 1px solid #e9ebef; border-radius: 8px; padding: 16px 20px; margin-bottom: 16px; }
    .panel h3 { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 8px; }
    .panel p { font-size: 11.5px; color: #4b5563; line-height: 1.6; }

    .terms { margin-top: 14px; padding-top: 14px; border-top: 1px solid #e5e7eb; }
    .terms h3 { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 8px; }
    .terms p { font-size: 10.5px; color: #6b7280; line-height: 1.6; }

    /* ---- Fixed footer on every page ----------------------------------- */
    .doc-footer {
        position: fixed;
        bottom: 0; left: 0; right: 0;
        height: 50px;
        padding: 12px 40px 0;
        border-top: 2px solid {{ $accent }};
        text-align: center;
        font-size: 10px;
        color: #9ca3af;
    }
    .doc-footer .thanks { color: {{ $primary }}; font-weight: 700; font-size: 11px; }
</style>
