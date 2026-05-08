@props([
    'variant' => 'light',     // 'light' (white text on dark bg) | 'dark' (dark text on light bg)
    'size'    => 'md',        // 'sm' | 'md' | 'lg'
])

@php
    $iconBox  = ['sm' => 'w-7 h-7',  'md' => 'w-9 h-9',  'lg' => 'w-12 h-12'][$size];
    $iconSize = ['sm' => 'w-4 h-4',  'md' => 'w-5 h-5',  'lg' => 'w-7 h-7'][$size];
    $title    = ['sm' => 'text-xs',  'md' => 'text-sm',  'lg' => 'text-xl'][$size];
    $subtitle = ['sm' => 'text-[10px]','md' => 'text-xs','lg' => 'text-sm'][$size];

    $titleColor    = $variant === 'light' ? 'text-white' : 'text-[#1a2332]';
    $subtitleColor = 'text-green-500';
    $iconBg        = $variant === 'light' ? 'bg-green-500/20' : 'bg-[#1a2332]';
    $iconColor     = 'text-green-400';
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <div class="{{ $iconBox }} rounded-lg {{ $iconBg }} flex items-center justify-center flex-shrink-0">
        <x-application-logo class="{{ $iconSize }} {{ $iconColor }}" />
    </div>
    <div class="leading-tight">
        <div class="{{ $title }} font-bold {{ $titleColor }}">Linkinablink</div>
        <div class="{{ $subtitle }} font-medium {{ $subtitleColor }}">Marketplace</div>
    </div>
</div>
