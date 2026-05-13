@props([
    'tone'  => 'gray',      // gray | green | amber | blue | indigo | purple | red | sensitive
    'shape' => 'rounded',   // rounded (`rounded-full`) | square (`rounded-md`)
    'size'  => 'sm',        // sm | md
])

@php
    $tones = [
        'gray'      => 'bg-gray-100 text-gray-600',
        'green'     => 'bg-green-100 text-green-700',
        'amber'     => 'bg-amber-100 text-amber-700',
        'blue'      => 'bg-blue-50 text-blue-700',
        'indigo'    => 'bg-indigo-100 text-indigo-700',
        'purple'    => 'bg-purple-50 text-purple-700',
        'red'       => 'bg-red-100 text-red-700',
        'sensitive' => 'bg-sensitive-soft text-sensitive-text',
    ];
    $shapes = [
        'rounded' => 'rounded-full px-2.5 py-1',
        'square'  => 'rounded-md px-2 py-1',
    ];
    $sizes = [
        'sm' => 'text-xs',
        'md' => 'text-sm',
    ];
    $klass = ($tones[$tone] ?? $tones['gray']) . ' ' . ($shapes[$shape] ?? $shapes['rounded']) . ' ' . ($sizes[$size] ?? $sizes['sm']);
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center font-semibold ' . $klass]) }}>
    {{ $slot }}
</span>
