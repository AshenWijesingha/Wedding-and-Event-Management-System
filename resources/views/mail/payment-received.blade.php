@component('mail::message')
# Payment Received

Hello {{ optional($payment->client)->full_name ?? 'there' }},

We have received your payment **{{ $payment->payment_number }}**.

- **Amount:** {{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}
@if($payment->payment_date)
- **Date:** {{ \Illuminate\Support\Carbon::parse($payment->payment_date)->format('M d, Y') }}
@endif
@if(optional($payment->booking)->booking_number)
- **Booking:** {{ $payment->booking->booking_number }}
@endif

@component('mail::button', ['url' => url('/portal/payments')])
View Payments
@endcomponent

Thank you,<br>
{{ optional($payment->tenant)->name ?? config('app.name') }}
@endcomponent
