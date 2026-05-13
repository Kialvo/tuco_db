<x-marketplace-layout>
    <x-slot name="title">My Orders</x-slot>

    <x-slot name="pageHeader">
        <x-ds.page-header title="My Orders">
            <x-slot name="actions">
                <x-ds.button :href="route('websites.index')" variant="secondary" size="md">
                    <x-icon name="search" size="sm" /> Browse domains
                </x-ds.button>
            </x-slot>
        </x-ds.page-header>
    </x-slot>

    @if($orders->total() === 0)
        <x-ds.empty-state
            icon="orders"
            title="No orders yet"
            hint="Your submitted order requests will appear here.">
            <x-slot name="action">
                <x-ds.button variant="primary" size="md" :href="route('websites.index')">
                    <x-icon name="plus" size="sm" /> Start a new order
                </x-ds.button>
            </x-slot>
        </x-ds.empty-state>
    @else
        <x-ds.table-shell>
            <x-slot name="head">
                <x-ds.th>#</x-ds.th>
                <x-ds.th>Date</x-ds.th>
                <x-ds.th>Sites</x-ds.th>
                <x-ds.th>Est. Total</x-ds.th>
                <x-ds.th>Status</x-ds.th>
                <x-ds.th>Notes</x-ds.th>
                <x-ds.th align="right">&nbsp;</x-ds.th>
            </x-slot>

            @foreach($orders as $order)
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-3 font-mono text-xs text-gray-500">{{ $order->reference }}</td>
                    <td class="px-3 py-3 text-sm text-gray-700">{{ $order->submitted_at?->format('M j, Y') ?? '—' }}</td>
                    <td class="px-3 py-3 text-sm text-gray-700">{{ $order->items->count() }} {{ Str::plural('site', $order->items->count()) }}</td>
                    <td class="px-3 py-3 text-sm font-semibold text-gray-800">€ {{ number_format($order->total_amount, 0, '.', ',') }}</td>
                    <td class="px-3 py-3">
                        <x-ds.pill :tone="$order->status_tone">{{ $order->status_label }}</x-ds.pill>
                    </td>
                    <td class="px-3 py-3 text-xs text-gray-400 max-w-[200px]">
                        <span class="block truncate" title="{{ $order->notes }}">{{ $order->notes ?: '—' }}</span>
                    </td>
                    <td class="px-3 py-3 text-right">
                        <a href="{{ route('orders.show', $order->id) }}"
                           class="text-sm font-medium text-green-600 hover:text-green-700 inline-flex items-center gap-1">
                            View <x-icon name="arrow-right" size="sm" />
                        </a>
                    </td>
                </tr>
            @endforeach
        </x-ds.table-shell>

        <x-ds.pagination :paginator="$orders" />
    @endif
</x-marketplace-layout>
