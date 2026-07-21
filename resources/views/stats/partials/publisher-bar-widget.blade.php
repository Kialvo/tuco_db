{{--
    Publisher stacked-bar widget (published articles / € spent by website).
    Params:
      title, subtitle, chartId, toggleKey
      filterButtonId, filterPanelId, selectId
      formRoute   — route name the filter form submits to (e.g. 'stats.production')
      paramName   — this widget's site filter query key (e.g. 'article_sites')
      selected    — array of currently-selected site names for this widget
      preserve    — assoc array of OTHER query params to keep across a submit
                    (sibling widget's sites + the page's date/window/granularity);
                    array values render as `key[]=` hidden inputs
      siteOptions — every distinct publisher website (most active first)
      series      — the widget's stacked series [{name, data:[]}]; drives the
                    breakdown table on the right (per-website totals over the range)
      itemHeader  — left column header for the table (e.g. 'Website')
      valueHeader — value column header for the table (e.g. 'Articles')
      isMoney     — format the table values as EUR when true
--}}
@php
    $wToggleBtn = 'rounded-md border px-3 py-1 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-green-200';
    $wToggleOn  = 'border-slate-200 bg-white font-semibold text-slate-900 shadow-sm';
    $wToggleOff = 'border-transparent text-slate-500 hover:text-slate-700';
    $hasFilter  = ! empty($selected);
    $preserve   = $preserve ?? [];

    // Per-website totals over the visible range (granularity-independent, so this
    // stays correct regardless of the Monthly/Quarterly/Yearly toggle).
    $tableRows = collect($series ?? [])
        ->map(fn ($s) => ['name' => $s['name'], 'total' => array_sum($s['data'])])
        ->all();
    $grandTotal = array_sum(array_column($tableRows, 'total'));
    $fmtValue = fn ($v) => ($isMoney ?? false) ? '€' . number_format((float) $v, 0) : number_format((float) $v);
@endphp

<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold uppercase tracking-wide text-slate-900">{{ $title }}</h2>
            <p class="mt-1 text-sm text-slate-600">{{ $subtitle }}</p>
        </div>
        <div class="flex shrink-0 items-center gap-2">
            <button type="button" id="{{ $filterButtonId }}"
                    aria-label="Filter by website" aria-expanded="{{ $hasFilter ? 'true' : 'false' }}"
                    aria-controls="{{ $filterPanelId }}"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border shadow-sm transition-colors hover:border-slate-300 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-green-200 {{ $hasFilter ? 'border-green-300 bg-green-50 text-green-700' : 'border-slate-200 bg-white text-slate-500' }}">
                <x-icon name="filter" size="md" />
            </button>

            <div data-granularity-toggle="{{ $toggleKey }}" role="group" aria-label="Data granularity"
                 class="inline-flex rounded-lg border border-slate-200 bg-slate-100 p-1 text-sm">
                <button type="button" data-granularity="monthly"   aria-pressed="true"  class="{{ $wToggleBtn }} {{ $wToggleOn }}">Monthly</button>
                <button type="button" data-granularity="quarterly" aria-pressed="false" class="{{ $wToggleBtn }} {{ $wToggleOff }}">Quarterly</button>
                <button type="button" data-granularity="yearly"    aria-pressed="false" class="{{ $wToggleBtn }} {{ $wToggleOff }}">Yearly</button>
            </div>
        </div>
    </div>

    {{-- Website filter panel — collapsed unless a filter is active. Server-side:
         picking websites + Apply reloads with this widget scoped to them. --}}
    <div id="{{ $filterPanelId }}" class="mt-4 {{ $hasFilter ? '' : 'hidden' }}">
        <form method="GET" action="{{ route($formRoute) }}"
              class="flex flex-col gap-2 sm:flex-row sm:items-end">
            {{-- Preserve the sibling widget's filter + the page's own filters. --}}
            @foreach($preserve as $pKey => $pVal)
                @if(is_array($pVal))
                    @foreach($pVal as $pv)
                        <input type="hidden" name="{{ $pKey }}[]" value="{{ $pv }}">
                    @endforeach
                @elseif($pVal !== null && $pVal !== '')
                    <input type="hidden" name="{{ $pKey }}" value="{{ $pVal }}">
                @endif
            @endforeach

            <div class="min-w-0 flex-1">
                <label for="{{ $selectId }}" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                    Filter by website
                </label>
                <select id="{{ $selectId }}" name="{{ $paramName }}[]" multiple class="w-full">
                    @foreach($siteOptions as $site)
                        <option value="{{ $site }}" @selected(in_array($site, $selected, true))>{{ $site }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit"
                        class="inline-flex h-[38px] items-center rounded-lg bg-green-600 px-4 text-sm font-semibold text-white transition hover:bg-green-700">
                    Apply
                </button>
                @if($hasFilter)
                    <a href="{{ route($formRoute, $preserve) }}"
                       class="inline-flex h-[38px] items-center rounded-lg border border-slate-200 px-3 text-sm text-slate-600 transition hover:bg-slate-50">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-6 xl:grid-cols-3 xl:items-start">
        {{-- Stacked bars (left) --}}
        <div id="{{ $chartId }}" class="h-[460px] xl:col-span-2"></div>

        {{-- Breakdown table (right): website | value | % of range total --}}
        <div class="max-h-[460px] overflow-auto xl:col-span-1">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="sticky top-0 bg-white py-2 pr-4 text-left">{{ $itemHeader ?? 'Website' }}</th>
                        <th class="sticky top-0 bg-white py-2 pr-4 text-right">{{ $valueHeader ?? 'Value' }}</th>
                        <th class="sticky top-0 bg-white py-2 text-right">%</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tableRows as $row)
                        <tr class="border-b border-slate-100">
                            <td class="py-2 pr-4 text-left text-slate-700 break-all">{{ $row['name'] }}</td>
                            <td class="py-2 pr-4 text-right tabular-nums text-slate-900">{{ $fmtValue($row['total']) }}</td>
                            <td class="py-2 text-right tabular-nums text-slate-600">{{ $grandTotal ? number_format($row['total'] / $grandTotal * 100, 1) : '0.0' }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-6 text-center text-slate-400">No data in range</td></tr>
                    @endforelse
                    @if(! empty($tableRows))
                        <tr class="font-semibold text-slate-900">
                            <td class="py-2 pr-4 text-left">Total</td>
                            <td class="py-2 pr-4 text-right tabular-nums">{{ $fmtValue($grandTotal) }}</td>
                            <td class="py-2 text-right tabular-nums">100%</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</section>
