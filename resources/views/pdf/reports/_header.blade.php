{{-- Report brand band. Expects: $branding, $docType, $subtitle, $period --}}
<div class="brand-band">
    <table>
        <tr>
            <td style="width:60%">
                @if(!empty($branding['logo_pdf']))
                    <img src="{{ $branding['logo_pdf'] }}" class="brand-logo" alt="">
                @endif
                <div class="brand-name">{{ $branding['business_name'] ?? config('app.name') }}</div>
                <div class="brand-sub">{{ $subtitle }}</div>
            </td>
            <td class="doc-meta" style="width:40%">
                <div class="doc-type">{{ $docType }}</div>
                <div class="doc-period">{{ $period }}</div>
                <div class="doc-date">Generated {{ now()->format('d M Y') }}</div>
            </td>
        </tr>
    </table>
</div>
