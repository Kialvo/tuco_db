{{--
    Pie-with-table stats widget.
    Vars: $title, $subtitle, $chartId, $itemHeader, $labels (array), $series (array),
          $countHeader (optional, defaults to "Domains" — the count column's header).
    Pie + legend on the left; breakdown table (item | count | %) on the right.

    This is an @include partial, not a Blade component, so defaults are ?? here
    rather than in an @props block.
--}}
@php
    $total = array_sum($series);
    $countHeader = $countHeader ?? 'Domains';
@endphp
<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="text-lg font-semibold uppercase tracking-wide text-slate-900">{{ $title }}</h2>
    <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>

    <div class="mt-4 grid grid-cols-1 gap-6 2xl:grid-cols-2 2xl:items-center">
        {{-- Pie + legend (left) --}}
        <div id="{{ $chartId }}" class="mx-auto w-full max-w-md"></div>

        {{-- Breakdown table (right) --}}
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="py-2 pr-4 text-left">{{ $itemHeader }}</th>
                        <th class="py-2 pr-4 text-right">{{ $countHeader }}</th>
                        <th class="py-2 text-right">%</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($labels as $i => $label)
                        <tr class="border-b border-slate-100">
                            <td class="py-2 pr-4 text-left text-slate-700">{{ $label }}</td>
                            <td class="py-2 pr-4 text-right tabular-nums text-slate-900">{{ number_format($series[$i]) }}</td>
                            <td class="py-2 text-right tabular-nums text-slate-600">{{ $total ? number_format($series[$i] / $total * 100, 1) : '0.0' }}%</td>
                        </tr>
                    @endforeach
                    <tr class="font-semibold text-slate-900">
                        <td class="py-2 pr-4 text-left">Total</td>
                        <td class="py-2 pr-4 text-right tabular-nums">{{ number_format($total) }}</td>
                        <td class="py-2 text-right tabular-nums">100%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
