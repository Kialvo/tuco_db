@props([
    'variant' => 'primary',     // primary | secondary | ghost | danger | sensitive
    'size'    => 'md',          // sm | md | lg
    'href'    => null,
    'block'   => false,         // full width
    'type'    => 'button',
])

@php
    $base = 'inline-flex items-center justify-center gap-2 font-semibold rounded-lg border transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';

    $variants = [
        'primary'   => 'bg-green-600 hover:bg-green-700 active:bg-green-800 text-white border-transparent shadow-sm focus:ring-green-500',
        'secondary' => 'bg-white hover:bg-gray-50 text-gray-700 border-gray-200 hover:border-gray-300 focus:ring-gray-300',
        'ghost'     => 'bg-transparent hover:bg-gray-100 text-gray-600 border-transparent focus:ring-gray-300',
        'danger'    => 'bg-red-600 hover:bg-red-700 text-white border-transparent shadow-sm focus:ring-red-500',
        'sensitive' => 'bg-sensitive hover:bg-sensitive-hover text-white border-transparent shadow-sm focus:ring-orange-400',
    ];

    $sizes = [
        'sm' => 'text-xs px-3 py-1.5',
        'md' => 'text-sm px-3.5 py-2',
        'lg' => 'text-sm px-4 py-2.5',
    ];

    $klass = trim("{$base} " . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']) . ($block ? ' w-full' : ''));
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $klass]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $klass]) }}>
        {{ $slot }}
    </button>
@endif
