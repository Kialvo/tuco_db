@component('mail::message')
# Hi {{ $item->order->user->name }},

Your article for **{{ $item->website->domain_name ?? 'your placement' }}** is ready and waiting for your approval.

We haven't heard back yet, so this is a reminder that we'll go ahead and publish it on **{{ $deadline->format('l j F') }}** if we don't hear from you before then.

You don't need to do anything if you're happy with it — it will simply go live.

@component('mail::button', ['url' => $item->order ? route('orders.show', $item->order->id) : url('/orders')])
Review your article
@endcomponent

**Need more time?** Just reply to this email and tell us. If you're away or waiting on someone internally, we'll move the date — we'd much rather wait than publish something you haven't read.

If you'd like changes instead, reply with what you'd like adjusted and we'll send a revised version. The clock starts again from the new draft.

Thanks,<br>
The Link in a Blink team
@endcomponent
