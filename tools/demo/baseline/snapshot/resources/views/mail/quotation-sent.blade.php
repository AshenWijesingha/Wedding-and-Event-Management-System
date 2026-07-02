@component('mail::message')
# Your Quotation

Hello {{ optional($quotation->client)->full_name ?? 'there' }},

Please find your quotation **{{ $quotation->quotation_number }}** below.

- **Total:** {{ number_format((float) $quotation->total_amount, 2) }}
@if($quotation->valid_until)
- **Valid until:** {{ $quotation->valid_until->format('M d, Y') }}
@endif

@component('mail::button', ['url' => url('/portal/quotations')])
View Quotation
@endcomponent

Thank you,<br>
{{ optional($quotation->tenant)->name ?? config('app.name') }}
@endcomponent
