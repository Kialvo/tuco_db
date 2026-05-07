@component('mail::message')
# Hi {{ $userName }},

An administrator has reset your password for your **{{ config('app.name') }}** account.

Use the temporary password below to log in. You will be asked to choose a new password as soon as you sign in.

**Temporary password:** `{{ $temporaryPassword }}`

@component('mail::button', ['url' => route('login')])
Log in now
@endcomponent

If you did not expect this email, please contact your administrator.

Thanks,
{{ config('app.name') }}
@endcomponent
