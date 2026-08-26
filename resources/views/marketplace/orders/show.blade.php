@php
    /*
     |  PLACEHOLDER — per-item fulfilment timeline.
     |  Static steps + static dates so the UI can be reviewed. The real steps and
     |  their per-item state come later; nothing here reads the database.
     */
    $progressSteps = [
        ['label' => 'Order Placed',      'at' => '2024-03-20 14:23', 'done' => true],
        ['label' => 'Order Confirmed',   'at' => '2024-03-20 14:30', 'done' => true],
        ['label' => 'Content Delivered', 'at' => '2024-03-21 09:45', 'done' => true],
        ['label' => 'Published',         'at' => '2024-03-22 08:15', 'done' => false],
        ['label' => 'Live URL Sent',     'at' => 'Pending',          'done' => false],
    ];
@endphp

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

            <x-ds.table-shell x-data="{ open: null }">
                <x-slot name="head">
                    <x-ds.th pad="ps-3 pe-0">&nbsp;</x-ds.th>
                    <x-ds.th>Domain</x-ds.th>
                    <x-ds.th>Country</x-ds.th>
                    <x-ds.th>Article type</x-ds.th>
                    <x-ds.th align="right">Unit price</x-ds.th>
                </x-slot>

                @foreach($order->items as $item)
                    <tr>
                        {{-- Progress toggle — expands the timeline row below --}}
                        <td class="ps-3 pe-0 py-3 w-px align-middle">
                            <button type="button"
                                    @click="open = open === {{ $item->id }} ? null : {{ $item->id }}"
                                    :aria-expanded="open === {{ $item->id }} ? 'true' : 'false'"
                                    aria-controls="progress-{{ $item->id }}"
                                    class="inline-flex items-center gap-1 whitespace-nowrap rounded-md border border-green-600 bg-green-50 px-2 py-1 text-xs font-semibold text-green-700 transition-colors hover:bg-green-100 hover:text-green-800">
                                Progress
                                <span class="inline-flex transition-transform duration-200"
                                      :class="open === {{ $item->id }} ? 'rotate-180' : ''">
                                    <x-icon name="chevron-down" size="sm" />
                                </span>
                            </button>
                        </td>
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

                    {{-- Expanded fulfilment timeline (PLACEHOLDER content) --}}
                    <tr id="progress-{{ $item->id }}" x-show="open === {{ $item->id }}" x-cloak class="bg-gray-50/70">
                        <td colspan="5" class="px-6 py-5">
                            {{-- The table scrolls horizontally on narrow screens (.ds-table is the
                                 scrollport). Pinning the panel to its start edge keeps the vertical
                                 stepper readable on mobile without sideways scrolling; from md up it
                                 goes back to flowing full-width for the horizontal stepper. --}}
                            <div class="sticky start-0 w-fit md:static md:w-auto">
                            <div class="mb-4 text-xs font-semibold uppercase tracking-wider text-gray-400">
                                Order progress
                                <span class="ms-1 font-normal normal-case tracking-normal text-gray-500">· placeholder</span>
                            </div>

                            {{-- Vertical rail on mobile, horizontal stepper from md up.
                                 One markup tree, two layouts — no duplicated step list. --}}
                            <ol class="relative flex flex-col md:flex-row md:items-start">
                                @foreach($progressSteps as $step)
                                    <li class="relative flex gap-3 {{ $loop->last ? '' : 'pb-6' }} md:flex-1 md:flex-col md:items-center md:gap-2 md:px-2 md:pb-0 md:text-center">
                                        @unless($loop->last)
                                            {{-- connector: down to the next step on mobile, across to it on desktop --}}
                                            <span class="absolute {{ $step['done'] ? 'bg-green-600' : 'bg-gray-300' }} start-[11px] top-6 bottom-0 w-0.5
                                                         md:start-1/2 md:top-3 md:bottom-auto md:h-0.5 md:w-full"
                                                  aria-hidden="true"></span>
                                        @endunless

                                        <span class="relative z-10 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full border-2 {{ $step['done'] ? 'border-green-600 bg-green-600 text-white' : 'border-gray-500 bg-white' }}">
                                            @if($step['done'])
                                                <x-icon name="check" size="sm" :stroke="3" />
                                            @endif
                                        </span>

                                        <div class="-mt-0.5 md:mt-0 md:min-w-0">
                                            <div class="text-sm font-semibold {{ $step['done'] ? 'text-gray-800' : 'text-gray-500' }}">
                                                {{ $step['label'] }}
                                            </div>
                                            <div class="mt-0.5 text-xs {{ $step['done'] ? 'text-gray-500' : 'text-gray-500' }}">
                                                {{ $step['at'] }}
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-ds.table-shell>
        </div>

        @if($order->status === \App\Models\Order::STATUS_SUBMITTED)
            <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 text-sm text-blue-700">
                <strong>What happens next?</strong>
                We're verifying current prices with the publishers and we'll come back within 24 hours
                with a confirmed quote. The order only goes ahead if you approve it.
            </div>
        @endif
    </div>
</x-marketplace-layout>
