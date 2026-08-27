{{-- Secondary "Stats" menu — rendered in the dashboard layout's @section('subnav') slot,
     next to the main sidebar, on every page in the Stats section.

     TWO GROUPS, because the pages measure two different businesses: the
     Link-Building group reports on the managed SERVICE (campaigns we run for
     clients), the Marketplace group on the self-serve product guests buy from
     themselves. The old "Sales Stats (soon)" placeholder is gone — its
     sales/leads intent is what the Marketplace group now actually reports. --}}
@php
    $statsGroups = [
        [
            'heading' => 'Link Building',
            'items' => [
                ['route' => 'stats.financial',  'label' => 'Financial Stats',  'icon' => 'chart-line'],
                ['route' => 'stats.campaigns',  'label' => 'Campaigns Stats',  'icon' => 'briefcase'],
                ['route' => 'stats.production', 'label' => 'Production Stats', 'icon' => 'chart-bar'],
                ['route' => 'stats.publishers', 'label' => 'Publisher Stats',  'icon' => 'database'],
            ],
        ],
        [
            'heading' => 'Marketplace',
            'items' => [
                ['route' => 'stats.marketplace.growth',  'label' => 'Growth',           'icon' => 'users-cog'],
                ['route' => 'stats.marketplace.revenue', 'label' => 'Revenue & Tokens', 'icon' => 'euro'],
            ],
        ],
    ];
@endphp

<div class="p-3 text-sm">
    <div class="px-3 pt-2 pb-3 text-[11px] font-semibold uppercase tracking-widest text-gray-500">
        Stats
    </div>

    @foreach($statsGroups as $group)
        <div class="@if(! $loop->first) mt-5 @endif">
            <div class="px-3 pb-1.5 text-[11px] font-bold uppercase tracking-widest text-gray-700">
                {{ $group['heading'] }}
            </div>

            <nav class="space-y-0.5" aria-label="{{ $group['heading'] }} statistics">
                @foreach($group['items'] as $item)
                    @if($item['soon'] ?? false)
                        <span class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium text-gray-500 cursor-not-allowed select-none">
                            <x-icon name="{{ $item['icon'] }}" size="sm" class="flex-shrink-0 text-gray-400" />
                            <span class="truncate">{{ $item['label'] }}</span>
                            <span class="ml-auto text-[10px] font-semibold uppercase tracking-wide rounded bg-gray-100 px-1.5 py-0.5 text-gray-600">soon</span>
                        </span>
                    @else
                        @php $active = request()->routeIs($item['route']); @endphp
                        <a href="{{ route($item['route']) }}"
                           @if($active) aria-current="page" @endif
                           class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition-colors
                                  {{ $active
                                      ? 'bg-green-50 text-green-700'
                                      : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                            <x-icon name="{{ $item['icon'] }}" size="sm" class="flex-shrink-0" />
                            <span class="truncate">{{ $item['label'] }}</span>
                        </a>
                    @endif
                @endforeach
            </nav>
        </div>
    @endforeach
</div>
