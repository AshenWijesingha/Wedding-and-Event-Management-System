@component('mail::message')
# Booking Confirmed

Hello {{ optional($booking->client)->full_name ?? 'there' }},

Your booking **{{ $booking->booking_number }}** is now confirmed.

- **Event type:** {{ ucfirst((string) $booking->event_type) }}
@if($booking->event_date)
- **Date:** {{ $booking->event_date->format('M d, Y') }}
@endif
- **Guests:** {{ $booking->guest_count }}

@component('mail::button', ['url' => url('/portal/bookings')])
View Booking
@endcomponent

We look forward to your event,<br>
{{ optional($booking->tenant)->name ?? config('app.name') }}
@endcomponent
