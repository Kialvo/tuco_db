@props([
    'icon'  => 'search',
    'title' => '',
    'hint'  => null,
])

<div {{ $attributes->merge(['class' => 'text-center py-16']) }}>
    <x-icon :name="$icon" size="w-12 h-12" :stroke="1.5" class="mx-auto mb-3 text-gray-200" />
    <p class="text-gray-400 text-sm">{{ $title }}</p>
    @if($hint)
        <p class="text-gray-400 text-xs mt-1">{{ $hint }}</p>
    @endif
    @isset($action)
        <div class="mt-4">{{ $action }}</div>
    @endisset
</div>
