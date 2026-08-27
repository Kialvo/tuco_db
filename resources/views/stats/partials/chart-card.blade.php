{{--
    Chart card — heading + explanation + an empty div for ApexCharts to mount into.
    Vars: $title, $chartId
          $subtitle (optional), $info (optional plain text → "i" icon)
          $height   (optional Tailwind height class, default h-[360px])
--}}
@php
    $subtitle = $subtitle ?? null;
    $info = $info ?? null;
    $height = $height ?? 'h-[360px]';
@endphp
<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="text-lg font-semibold uppercase tracking-wide text-slate-900">
        {{ $title }}
        @if($info)
            <x-ds.info-tip :text="$info" :label="'How is ' . \Illuminate\Support\Str::lower($title) . ' calculated?'" />
        @endif
    </h2>
    @if($subtitle)
        <p class="mt-1 text-sm text-slate-600">{{ $subtitle }}</p>
    @endif
    <div id="{{ $chartId }}" class="mt-4 {{ $height }}"></div>
</section>
