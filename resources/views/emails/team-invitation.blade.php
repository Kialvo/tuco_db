@component('mail::message')
# You've been invited to join {{ $team->name ?? 'a team' }}

@if($inviter)
**{{ $inviter->name }}** has invited you to join **{{ $team->name }}** on Link in a Blink.
@else
You've been invited to join **{{ $team->name }}** on Link in a Blink.
@endif

Joining means you'll share the team's token balance — you'll be able to see it and use it to order guest post placements, alongside the rest of the team.

@component('mail::button', ['url' => $url])
Join the team
@endcomponent

You'll need to sign in with **{{ $invitation->email }}** to accept. If you don't have an account yet, create one with that address first and then open this link again.

This invitation expires on **{{ $expiresAt->format('j F Y') }}**.

If you weren't expecting this, you can ignore it — nothing happens until you accept.

Thanks,<br>
The Link in a Blink team
@endcomponent
