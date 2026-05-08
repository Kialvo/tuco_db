@props([
    'id'        => null,
    'title'     => null,
    'maxWidth'  => 'md',     // sm | md | lg | xl
    'show'      => false,
])

@php
    $maxW = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
    ][$maxWidth] ?? 'max-w-md';
@endphp

<div @if($id) id="{{ $id }}" @endif
     class="{{ $show ? 'flex' : 'hidden' }} fixed inset-0 bg-black/50 items-center justify-center z-50 p-4"
     {{ $attributes }}>
    <div class="bg-white rounded-2xl shadow-2xl w-full {{ $maxW }} max-h-[90vh] flex flex-col">
        @if($title || isset($header))
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between flex-shrink-0 sticky top-0 bg-white rounded-t-2xl">
                <h2 class="text-base font-bold text-gray-800">
                    @isset($header){{ $header }}@else{{ $title }}@endisset
                </h2>
                <button type="button" data-close-modal
                        class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-700 transition-colors">
                    <x-icon name="x" size="lg" />
                </button>
            </div>
        @endif

        <div class="overflow-y-auto slim-scroll p-6 flex-1">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="px-6 py-4 border-t border-gray-100 flex-shrink-0 rounded-b-2xl bg-white">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
