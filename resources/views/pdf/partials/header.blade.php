{{-- Brand header band. Expects:
     $branding, $docType, $docNumber (nullable), $docDateLabel, $docDate,
     $statusClass (nullable), $statusText (nullable) --}}
@php
    $contact = $branding['contact'] ?? [];
    $contactLine = collect([$contact['address'] ?? null, $contact['city'] ?? null, $contact['country'] ?? null])
        ->filter()->implode(', ');
@endphp
<div class="brand-band">
    <table>
        <tr>
            <td style="width:60%">
                @if(!empty($branding['logo_pdf']))
                    <img src="{{ $branding['logo_pdf'] }}" class="brand-logo" alt="">
                @endif
                <div class="brand-name">{{ $branding['business_name'] ?? config('app.name') }}</div>
                @if(!empty($branding['tagline']))
                    <div class="brand-tagline">{{ $branding['tagline'] }}</div>
                @endif
                <div class="brand-contact">
                    @if($contactLine){{ $contactLine }}<br>@endif
                    @if(!empty($contact['phone'])){{ $contact['phone'] }}@endif
                    @if(!empty($contact['phone']) && !empty($contact['email'])) &middot; @endif
                    @if(!empty($contact['email'])){{ $contact['email'] }}@endif
                </div>
            </td>
            <td class="doc-meta" style="width:40%">
                <div class="doc-type">{{ $docType }}</div>
                @if(!empty($docNumber))
                    <div class="doc-number">{{ $docNumber }}</div>
                @endif
                <div class="doc-date">{{ $docDateLabel }} {{ $docDate }}</div>
                @if(!empty($statusText))
                    <span class="status-badge status-{{ $statusClass ?? 'neutral' }}">{{ $statusText }}</span>
                @endif
            </td>
        </tr>
    </table>
</div>
