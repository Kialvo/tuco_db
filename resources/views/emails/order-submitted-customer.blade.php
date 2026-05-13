@component('mail::message')
# Hi {{ $order->user->name }},

Thanks for your order {{ $order->reference }}. We've received it and will verify the current prices with publishers within **24 hours**.

## Your selection ({{ $order->items->count() }} {{ \Illuminate\Support\Str::plural('site', $order->items->count()) }})

@component('mail::table')
| Domain | Article type | Price |
|:-------|:-------------|------:|
@foreach($order->items as $item)
| {{ $item->website->domain_name }} | {{ ucfirst($item->article_type) }} | € {{ number_format($item->unit_price, 0, '.', ',') }} |
@endforeach
| | **Estimated total** | **€ {{ number_format($order->total_amount, 0, '.', ',') }}** |
@endcomponent

@if($order->notes)
**Your notes:**

> {{ $order->notes }}
@endif

@component('mail::button', ['url' => route('orders.show', $order->id)])
View order
@endcomponent

We'll be in touch shortly with confirmed pricing.

Thanks,
{{ config('app.name') }}
@endcomponent
