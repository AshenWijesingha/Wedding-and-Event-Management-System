<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 12px; color: #1f2937; line-height: 1.5; }
    .page { padding: 36px; }

    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; padding-bottom: 18px; border-bottom: 3px solid #4f46e5; }
    .company-name { font-size: 20px; font-weight: 700; color: #4f46e5; }
    .company-sub { font-size: 12px; color: #6b7280; margin-top: 2px; }
    .doc-title { text-align: right; }
    .doc-title h1 { font-size: 24px; font-weight: 700; color: #111827; letter-spacing: -0.5px; }
    .doc-period { font-size: 13px; color: #4f46e5; font-weight: 600; margin-top: 4px; }
    .doc-date { font-size: 11px; color: #6b7280; }

    .cards { width: 100%; margin-bottom: 26px; }
    .card { display: inline-block; width: 31%; margin-right: 2%; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px 14px; vertical-align: top; }
    .card:last-child { margin-right: 0; }
    .card-label { display: block; font-size: 10px; text-transform: uppercase; letter-spacing: 0.6px; color: #9ca3af; }
    .card-value { display: block; font-size: 18px; font-weight: 700; margin-top: 4px; color: #111827; }
    .card-value.green { color: #059669; }
    .card-value.red { color: #dc2626; }
    .card-value.orange { color: #ea580c; }
    .card-value.indigo { color: #4f46e5; }

    h2.section { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #6b7280; margin: 22px 0 10px; }

    table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    thead th { background: #4f46e5; color: #fff; padding: 8px 10px; text-align: left; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    thead th.num { text-align: right; }
    tbody td { padding: 7px 10px; border-bottom: 1px solid #f3f4f6; font-size: 12px; }
    tbody td.num { text-align: right; }
    tbody tr:nth-child(even) { background: #f9fafb; }

    .footer { margin-top: 28px; padding-top: 14px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 10px; color: #9ca3af; }
</style>
