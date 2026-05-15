<x-marketplace-layout>
    <x-slot name="title">Order {{ $order->reference }}</x-slot>

    <x-slot name="pageHeader">
        <x-ds.page-header :title="'Order ' . $order->reference">
            <x-slot name="actions">
                <x-ds.button :href="route('admin.orders.index')" variant="ghost" size="md">
                    <x-icon name="arrow-left" size="sm" /> Back to orders
                </x-ds.button>
            </x-slot>
        </x-ds.page-header>
    </x-slot>

    <div class="max-w-5xl mx-auto space-y-6">

        {{-- Customer + summary --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-gray-200 shadow-card p-5">
                <div class="text-xs text-gray-400 uppercase tracking-wider mb-2">Customer</div>
                <div class="font-semibold text-gray-800">{{ $order->user->name }}</div>
                <div class="text-sm text-gray-500">{{ $order->user->email }}</div>
                <div class="text-xs text-gray-400 mt-2">User ID: {{ $order->user->id }}</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-card p-5">
                <div class="text-xs text-gray-400 uppercase tracking-wider mb-2">Submitted</div>
                <div class="font-semibold text-gray-800">
                    {{ $order->submitted_at?->format('M j, Y') ?? '—' }}
                </div>
                <div class="text-sm text-gray-500">
                    {{ $order->submitted_at?->format('H:i') ?? '' }}
                </div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-card p-5">
                <div class="text-xs text-gray-400 uppercase tracking-wider mb-2">Last updated</div>
                <div class="font-semibold text-gray-800">
                    {{ $order->status_changed_at?->format('M j, Y') ?? '—' }}
                </div>
                <div class="text-sm text-gray-500">
                    {{ $order->status_changed_at?->format('H:i') ?? '' }}
                </div>
            </div>
            <div class="bg-green-50 rounded-xl border border-green-100 p-5">
                <div class="text-xs text-green-700 uppercase tracking-wider mb-2 font-semibold">Estimated total</div>
                <div class="text-2xl font-bold text-green-700">€ {{ number_format($order->total_amount, 0, '.', ',') }}</div>
                <div class="text-xs text-green-700 mt-1">{{ $order->items->count() }} {{ Str::plural('site', $order->items->count()) }}</div>
            </div>
        </div>

        {{-- Status workflow --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-card p-5">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Current status</div>
                    <x-ds.pill :tone="$order->status_tone" size="md">{{ $order->status_label }}</x-ds.pill>
                </div>
                <form method="POST" action="{{ route('admin.orders.update-status', $order->id) }}" class="inline-flex items-center gap-2">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="fi py-1.5 text-sm">
                        @foreach(\App\Models\Order::STATUSES as $val)
                            @if($val !== 'draft')
                                <option value="{{ $val }}" @selected($order->status === $val)>
                                    {{ \App\Models\Order::STATUS_LABELS[$val] }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                    <x-ds.button type="submit" variant="primary" size="md">
                        <x-icon name="check" size="sm" /> Update
                    </x-ds.button>
                </form>
            </div>
        </div>

        @if($order->notes)
            <div class="bg-amber-50 border border-amber-100 rounded-xl p-5">
                <div class="text-xs text-amber-700 font-semibold uppercase tracking-wider mb-2">Customer notes</div>
                <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $order->notes }}</p>
            </div>
        @endif

        {{-- Items --}}
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
                        <a href="{{ route('websites.show', $item->website->id) }}"
                           class="font-medium text-gray-800 hover:text-green-600 text-sm">
                            {{ $item->website->domain_name }}
                        </a>
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
</x-marketplace-layout>
