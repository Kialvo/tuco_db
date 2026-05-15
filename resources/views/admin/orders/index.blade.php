<x-marketplace-layout>
    <x-slot name="title">Orders</x-slot>

    <x-slot name="pageHeader">
        <x-ds.page-header title="Orders">
            <x-slot name="actions">
                <form method="GET" action="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-2">
                    <select name="status" onchange="this.form.submit()" class="fi w-44 py-1.5 text-sm">
                        <option value="">All statuses</option>
                        @foreach(\App\Models\Order::STATUS_LABELS as $val => $lbl)
                            @if($val !== 'draft')
                                <option value="{{ $val }}" @selected($status === $val)>{{ $lbl }}</option>
                            @endif
                        @endforeach
                    </select>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search by ID or email…"
                           class="fi w-56 py-1.5 text-sm">
                </form>
            </x-slot>
        </x-ds.page-header>
    </x-slot>

    @if($orders->total() === 0)
        <x-ds.empty-state
            icon="orders"
            title="No orders match"
            hint="Try adjusting the filter or clearing the search." />
    @else
        <x-ds.table-shell>
            <x-slot name="head">
                <x-ds.th>#</x-ds.th>
                <x-ds.th>Customer</x-ds.th>
                <x-ds.th>Submitted</x-ds.th>
                <x-ds.th>Sites</x-ds.th>
                <x-ds.th align="right">Est. Total</x-ds.th>
                <x-ds.th>Status</x-ds.th>
                <x-ds.th>Status changed</x-ds.th>
                <x-ds.th align="right">&nbsp;</x-ds.th>
            </x-slot>

            @foreach($orders as $order)
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-3 font-mono text-xs text-gray-500">{{ $order->reference }}</td>
                    <td class="px-3 py-3 text-sm">
                        <div class="font-medium text-gray-800">{{ $order->user->name }}</div>
                        <div class="text-xs text-gray-500">{{ $order->user->email }}</div>
                    </td>
                    <td class="px-3 py-3 text-sm text-gray-700">
                        {{ $order->submitted_at?->format('M j, Y · H:i') ?? '—' }}
                    </td>
                    <td class="px-3 py-3 text-sm text-gray-700">{{ $order->items->count() }}</td>
                    <td class="px-3 py-3 text-sm font-semibold text-gray-800 text-right">
                        € {{ number_format($order->total_amount, 0, '.', ',') }}
                    </td>
                    <td class="px-3 py-3">
                        <x-ds.pill :tone="$order->status_tone">{{ $order->status_label }}</x-ds.pill>
                    </td>
                    <td class="px-3 py-3 text-sm text-gray-500">
                        {{ $order->status_changed_at?->format('M j, Y · H:i') ?? '—' }}
                    </td>
                    <td class="px-3 py-3 text-right">
                        <a href="{{ route('admin.orders.show', $order->id) }}"
                           class="text-sm font-medium text-green-600 hover:text-green-700 inline-flex items-center gap-1">
                            Manage <x-icon name="arrow-right" size="sm" />
                        </a>
                    </td>
                </tr>
            @endforeach
        </x-ds.table-shell>

        <x-ds.pagination :paginator="$orders" />
    @endif
</x-marketplace-layout>
