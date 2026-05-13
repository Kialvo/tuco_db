<x-marketplace-layout>
    <x-slot name="title">Order {{ $order->reference }}</x-slot>

    <x-slot name="pageHeader">
        <x-ds.page-header :title="'Order ' . $order->reference">
            <x-slot name="actions">
                <x-ds.button :href="route('orders.index')" variant="ghost" size="md">
                    <x-icon name="arrow-left" size="sm" /> Back to orders
                </x-ds.button>
            </x-slot>
        </x-ds.page-header>
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6">

        {{-- Summary card --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-card p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Status</div>
                    <x-ds.pill :tone="$order->status_tone" size="md">{{ $order->status_label }}</x-ds.pill>
                </div>
                <div class="text-right">
                    <div class="text-xs text-gray-400 uppercase tracking-wider">Submitted</div>
                    <div class="text-sm font-medium text-gray-800">
                        {{ $order->submitted_at?->format('M j, Y · H:i') ?? '—' }}
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-xs text-gray-400 uppercase tracking-wider">Estimated total</div>
                    <div class="text-2xl font-bold text-green-700">€ {{ number_format($order->total_amount, 0, '.', ',') }}</div>
                </div>
            </div>

            @if($order->notes)
                <div class="mt-6 pt-6 border-t border-gray-100">
                    <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Your notes</div>
                    <p class="text-sm text-gray-700 leading-relaxed">{{ $order->notes }}</p>
                </div>
            @endif
        </div>

        {{-- Items --}}
        <div>
            <h2 class="text-base font-bold text-gray-800 mb-3">
                {{ $order->items->count() }} {{ Str::plural('site', $order->items->count()) }}
            </h2>

            <x-ds.table-shell>
                <x-slot name="head">
                    <x-ds.th>Domain</x-ds.th>
                    <x-ds.th>Country</x-ds.th>
                    <x-ds.th>Article type</x-ds.th>
                    <x-ds.th align="right">Unit price</x-ds.th>
                </x-slot>

                @foreach($order->items as $item)
                    <tr>
                        <td class="px-3 py-3">
                            <span class="font-medium text-gray-800 text-sm">{{ $item->website->domain_name }}</span>
                        </td>
                        <td class="px-3 py-3 text-sm text-gray-600 whitespace-nowrap">
                            <x-flag :country="optional($item->website->country)->country_name" />
                            {{ optional($item->website->country)->country_name ?? '—' }}
                        </td>
                        <td class="px-3 py-3">
                            @if($item->article_type === 'sensitive')
                                <x-ds.pill tone="sensitive" shape="square" size="sm">Sensitive</x-ds.pill>
                            @else
                                <x-ds.pill tone="green" shape="square" size="sm">Standard</x-ds.pill>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-sm font-semibold text-gray-800 text-right">
                            € {{ number_format($item->unit_price, 0, '.', ',') }}
                        </td>
                    </tr>
                @endforeach
            </x-ds.table-shell>
        </div>

        @if($order->status === \App\Models\Order::STATUS_SUBMITTED)
            <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 text-sm text-blue-700">
                <strong>What happens next?</strong>
                We're verifying current prices with the publishers. We'll get back to you within 24 hours with a confirmed quote.
            </div>
        @endif
    </div>
</x-marketplace-layout>
