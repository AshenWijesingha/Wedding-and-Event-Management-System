{{-- Fixed footer rendered on every page. Expects: $branding, $note (optional) --}}
<div class="doc-footer">
    <div class="thanks">{{ $branding['business_name'] ?? config('app.name') }}</div>
    <div>{{ $note ?? 'Thank you for your business.' }} &middot; Generated {{ now()->format('d M Y') }}</div>
</div>
