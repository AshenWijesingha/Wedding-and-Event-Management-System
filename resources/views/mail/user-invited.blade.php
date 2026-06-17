@component('mail::message')
# You're Invited

Hello {{ $user->name }},

An account has been created for you at **{{ optional($user->tenant)->name ?? config('app.name') }}**.

- **Email:** {{ $user->email }}
@if($tempPassword)
- **Temporary password:** {{ $tempPassword }}

Please sign in and change your password.
@else
Use the password you were given to sign in.
@endif

@component('mail::button', ['url' => $loginUrl])
Sign In
@endcomponent

Welcome aboard,<br>
{{ optional($user->tenant)->name ?? config('app.name') }}
@endcomponent
