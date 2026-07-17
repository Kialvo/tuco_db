@component('mail::message')
# New user on Linkinablink

There's a new user on Linkinablink. Here are their details:

**Name:** {{ $newUser->name }}

**Email:** {{ $newUser->email }}

@component('mail::button', ['url' => route('admin.users.index')])
Manage Users
@endcomponent

Thanks,
{{ config('app.name') }}
@endcomponent
