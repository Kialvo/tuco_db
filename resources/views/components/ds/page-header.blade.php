@props([
    'title'    => '',
    'subtitle' => null,
])

<div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between flex-shrink-0">
    <div>
        <h1 class="text-base font-bold text-gray-800">{{ $title }}</h1>
        @if($subtitle)
            <p class="text-xs text-gray-500 mt-0.5">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
