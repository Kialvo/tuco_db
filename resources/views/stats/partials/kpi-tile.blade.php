{{--
    Single-number KPI tile.
    Vars: $label, $value (already formatted string), $info (plain text, → "i" icon)
          $sub (optional, small line under the number)
          $tone (optional: 'default' | 'warning' — warning is for a number that
                 means something is wrong, e.g. ledger drift)

    slate-500 on white is the floor the readability audit accepts for the label;
    do not lighten it to slate-400.
--}}
@php
    $sub = $sub ?? null;
    $tone = $tone ?? 'default';
    $valueClass = $tone === 'warning' ? 'text-amber-700' : 'text-slate-900';
@endphp
<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition-colors duration-150 hover:border-slate-300">
    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
        {{ $label }}
        @if(!empty($info))
            <x-ds.info-tip :text="$info" :label="'How is ' . \Illuminate\Support\Str::lower($label) . ' calculated?'" />
        @endif
    </p>
    <p class="mt-2 text-3xl font-bold tabular-nums {{ $valueClass }}">{{ $value }}</p>
    @if($sub)
        <p class="mt-1 text-sm text-slate-600">{!! $sub !!}</p>
    @endif
</div>
