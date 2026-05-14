@props([
    'activeCount' => 0,
    'clearUrl'    => null,
    'searchAction' => null,    // optional: a form action so the search button submits
])

<aside class="w-[268px] bg-white border-r border-gray-200 flex flex-col flex-shrink-0 overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 flex-shrink-0">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-sm font-semibold text-gray-700">Filters</span>
                @if($activeCount > 0)
                    <span class="bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full">
                        {{ $activeCount }} active
                    </span>
                @endif
            </div>
            @if($clearUrl)
                <a href="{{ $clearUrl }}" class="text-xs text-red-500 hover:text-red-600 font-medium transition-colors">Clear all</a>
            @else
                <button type="button" data-clear-filters class="text-xs text-red-500 hover:text-red-600 font-medium transition-colors">Clear all</button>
            @endif
        </div>
        @if(isset($chips) && trim((string) $chips))
            <div class="mt-2 flex flex-wrap gap-1.5">
                {{ $chips }}
            </div>
        @endif
    </div>

    <div class="flex-1 overflow-y-auto slim-scroll p-4 space-y-4">
        {{ $slot }}

        @isset($search)
            <div class="pt-1 pb-2">
                {{ $search }}
            </div>
        @endisset
    </div>
</aside>
