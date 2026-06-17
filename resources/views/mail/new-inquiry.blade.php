@component('mail::message')
# New Inquiry Received

A new inquiry has come in{{ $inquiry->inquiry_number ? ' ('.$inquiry->inquiry_number.')' : '' }}.

@if(optional($inquiry->client)->full_name)
- **From:** {{ $inquiry->client->full_name }}
@endif
@if(optional($inquiry->client)->email)
- **Email:** {{ $inquiry->client->email }}
@endif
@if($inquiry->event_type)
- **Event type:** {{ ucfirst((string) $inquiry->event_type) }}
@endif
@if($inquiry->preferred_date)
- **Preferred date:** {{ $inquiry->preferred_date->format('M d, Y') }}
@endif

> {{ $inquiry->message }}

@component('mail::button', ['url' => url('/admin/inquiries/'.$inquiry->id)])
Open Inquiry
@endcomponent
@endcomponent
