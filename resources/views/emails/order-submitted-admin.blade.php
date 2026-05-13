@component('mail::message')
**{{ $order->user->name }}** has submitted a new order:

@foreach($order->items as $index => $item)
{{ $index + 1 }}) {{ $order->user->name }} wants to publish on **{{ $item->website->domain_name }}** a *{{ ucfirst($item->article_type) }}* article at a price of **€ {{ number_format($item->unit_price, 2, '.', ',') }}**

@endforeach
@if($order->notes)
**Additional notes:** {{ $order->notes }}
@else
**Additional notes:** None
@endif

**Client contact:** {{ $order->user->email }}

Please contact the site(s) to verify final prices before confirming the order to the client.

---

Submitted on {{ $order->submitted_at?->format('j F Y') ?? now()->format('j F Y') }} · Link in a Blink Marketplace
@endcomponent
