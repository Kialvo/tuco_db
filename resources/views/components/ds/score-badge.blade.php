@props([
    'score'      => 0,
    'thresholds' => [80, 65],   // [great, good]; below 'good' is muted
])

@php
    $score = (int) $score;
    [$great, $good] = $thresholds;
    if ($score >= $great)      $tone = 'bg-green-100 text-green-700';
    else if ($score >= $good)  $tone = 'bg-amber-100 text-amber-700';
    else                       $tone = 'bg-gray-100 text-gray-600';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center justify-center w-8 h-8 rounded-lg text-xs font-bold {$tone}"]) }}>
    {{ $score }}
</span>
