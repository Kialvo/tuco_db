@props([
    'tip'    => null,
    'align'  => 'left',      // left | center | right
    'width'  => null,
])

@php
    $align = ['left' => 'text-left', 'center' => 'text-center', 'right' => 'text-right'][$align];
    $w = $width ? "w-{$width}" : '';
@endphp

<th class="px-3 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider {{ $align }} {{ $w }}">
    @if($tip)
        <div class="tip {{ $align === 'center' ? 'justify-center' : '' }}">
            {{ $slot }}
            <x-icon name="info" size="sm" class="ms-1 text-gray-400 font-normal cursor-help" />
            <span class="tip-box">{{ $tip }}</span>
        </div>
    @else
        {{ $slot }}
    @endif
</th>
