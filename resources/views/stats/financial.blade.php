@extends('layouts.dashboard')

@section('subnav')
    @include('layouts.partials.stats-sidebar')
@endsection

@section('content')
    <div class="mx-auto max-w-7xl space-y-6 py-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Total Net Profit</p>
            <p class="mt-2 text-4xl font-bold text-slate-900">
                EUR {{ number_format((float) $totalNetProfit, 2, '.', ',') }}
            </p>
        </div>

        {{-- Per-widget granularity toggle (Monthly / Quarterly / Yearly), styled after
             menford-analytics' GranularityToggle. The buttons drive a client-side
             re-aggregation of a monthly source, independent of the page granularity. --}}
        @php
            $toggleBtn = 'rounded-md border px-3 py-1 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-green-200';
            $toggleOn  = 'border-slate-200 bg-white font-semibold text-slate-900 shadow-sm';
            $toggleOff = 'border-transparent text-slate-500 hover:text-slate-700';
        @endphp

        <div class="grid grid-cols-1 gap-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold uppercase tracking-wide text-slate-900">Net Profit</h2>
                    </div>
                    <div data-granularity-toggle="netProfit" role="group" aria-label="Data granularity"
                         class="inline-flex shrink-0 rounded-lg border border-slate-200 bg-slate-100 p-1 text-sm">
                        <button type="button" data-granularity="monthly"   aria-pressed="true"  class="{{ $toggleBtn }} {{ $toggleOn }}">Monthly</button>
                        <button type="button" data-granularity="quarterly" aria-pressed="false" class="{{ $toggleBtn }} {{ $toggleOff }}">Quarterly</button>
                        <button type="button" data-granularity="yearly"    aria-pressed="false" class="{{ $toggleBtn }} {{ $toggleOff }}">Yearly</button>
                    </div>
                </div>
                <div id="netProfitChart" class="mt-4 h-[390px]"></div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold uppercase tracking-wide text-slate-900">Revenues per Client</h2>
                        <p class="mt-1 text-sm text-slate-600">
                            Total revenues (EUR) by company, stacked per period. Dated by <strong>Live Date</strong>.
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        {{-- Filter toggle — reveals the company filter panel below. --}}
                        <button type="button" id="companyFilterToggle"
                                aria-label="Filter by company" aria-expanded="false"
                                aria-controls="companyRevenueFilterPanel"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm transition-colors hover:border-slate-300 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-green-200">
                            <x-icon name="filter" size="md" />
                        </button>

                        <div data-granularity-toggle="revenuesPerClient" role="group" aria-label="Data granularity"
                             class="inline-flex rounded-lg border border-slate-200 bg-slate-100 p-1 text-sm">
                            <button type="button" data-granularity="monthly"   aria-pressed="true"  class="{{ $toggleBtn }} {{ $toggleOn }}">Monthly</button>
                            <button type="button" data-granularity="quarterly" aria-pressed="false" class="{{ $toggleBtn }} {{ $toggleOff }}">Quarterly</button>
                            <button type="button" data-granularity="yearly"    aria-pressed="false" class="{{ $toggleBtn }} {{ $toggleOff }}">Yearly</button>
                        </div>
                    </div>
                </div>

                {{-- Company filter: pick one or more companies to isolate their revenue
                     evolution over time. Empty = the default top-companies view.
                     Collapsed by default; revealed by the filter button above. --}}
                <div id="companyRevenueFilterPanel" class="mt-4 hidden max-w-xl">
                    <label for="companyRevenueFilter" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                        Filter by company
                    </label>
                    <select id="companyRevenueFilter" multiple class="w-full">
                        @foreach($revenuePerCompanyList as $co)
                            <option value="{{ $co['name'] }}">{{ $co['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="revenuesPerClientChart" class="mt-4 h-[460px]"></div>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const labels = @json($labels);
            const netProfitMonthly = @json($netProfitMonthly);
            const granularity = @json($granularity);

            const euro = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'EUR',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            const compactCurrency = function (value) {
                const abs = Math.abs(value);
                if (abs >= 1000000) return 'EUR ' + (value / 1000000).toFixed(1) + 'M';
                if (abs >= 1000) return 'EUR ' + (value / 1000).toFixed(1) + 'k';
                return 'EUR ' + value.toFixed(0);
            };

            const commonOptions = {
                chart: {
                    foreColor: '#334155',
                    toolbar: { show: false },
                    animations: { enabled: true, easing: 'easeout', speed: 500 }
                },
                noData: {
                    text: 'No article_published data available',
                    align: 'center',
                    verticalAlign: 'middle',
                    style: { color: '#64748b' }
                },
                stroke: { lineCap: 'round' },
                grid: {
                    borderColor: '#e2e8f0',
                    strokeDashArray: 4,
                    padding: { left: 8, right: 8, top: 6, bottom: 6 }
                },
                legend: { show: false },
                xaxis: {
                    categories: labels,
                    tickPlacement: 'on',
                    axisBorder: { color: '#cbd5e1' },
                    axisTicks: { color: '#cbd5e1' },
                    labels: {
                        hideOverlappingLabels: true,
                        trim: true,
                        style: { colors: '#64748b', fontSize: '11px' }
                    }
                }
            };

            // ── Granularity-toggle widget ──────────────────────────────────
            // A line/area chart driven by its own Monthly / Quarterly / Yearly
            // segmented toggle. The source is always monthly; quarters/years are
            // summed client-side (ported from menford-analytics' bucketize()).
            const MONTHS_ABBR = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

            const renderGranularityChart = function (cfg) {
                const node = document.querySelector(cfg.nodeSelector);
                const toggle = document.querySelector('[data-granularity-toggle="' + cfg.toggleKey + '"]');
                if (! node) return;

                const parseLabel = function (label) {
                    const parts = String(label).split(' ');
                    return { y: Number(parts[1]), m: MONTHS_ABBR.indexOf(parts[0]) };
                };

                // Roll the monthly points up into the chosen granularity (sum).
                const bucketize = function (points, g) {
                    if (g === 'monthly') {
                        return { labels: points.map((p) => p.label), data: points.map((p) => p.value) };
                    }
                    const outLabels = [];
                    const outData = [];
                    const idxByKey = new Map();
                    points.forEach((p) => {
                        const parsed = parseLabel(p.label);
                        const key = g === 'yearly'
                            ? String(parsed.y)
                            : 'Q' + (Math.floor(parsed.m / 3) + 1) + ' ' + parsed.y;
                        let idx = idxByKey.get(key);
                        if (idx === undefined) {
                            idx = outLabels.length;
                            idxByKey.set(key, idx);
                            outLabels.push(key);
                            outData.push(0);
                        }
                        outData[idx] += p.value;
                    });
                    return { labels: outLabels, data: outData };
                };

                // Tick sparsity + rotation, tuned to the current bucket count.
                let step = 1;
                let rot = 0;
                const tuneAxis = function (n, g) {
                    const maxTicks = g === 'monthly' ? 14 : 12;
                    step = Math.max(1, Math.ceil(n / maxTicks));
                    rot = n > 24 ? -40 : (n > 14 ? -25 : 0);
                };

                const xaxisFor = function (bucket) {
                    return {
                        ...commonOptions.xaxis,
                        categories: bucket.labels,
                        tickAmount: Math.min(bucket.labels.length, 12),
                        labels: {
                            ...commonOptions.xaxis.labels,
                            rotate: rot,
                            formatter: function (value, _timestamp, opts) {
                                const index = opts && typeof opts.dataPointIndex === 'number' ? opts.dataPointIndex : 0;
                                return index % step === 0 ? value : '';
                            }
                        }
                    };
                };

                let current = 'monthly';
                let bucket = bucketize(cfg.monthly, current);
                tuneAxis(bucket.labels.length, current);

                const options = {
                    ...commonOptions,
                    chart: { ...commonOptions.chart, type: cfg.type, height: 390 },
                    series: [{ name: cfg.seriesName, data: bucket.data }],
                    colors: [cfg.color],
                    stroke: { curve: 'smooth', width: 3, lineCap: 'round' },
                    markers: { size: cfg.type === 'area' ? 0 : 4, hover: { sizeOffset: 2 } },
                    dataLabels: { enabled: false },
                    xaxis: xaxisFor(bucket),
                    yaxis: {
                        min: cfg.yMin,
                        forceNiceScale: true,
                        title: { text: cfg.yTitle },
                        labels: {
                            style: { colors: '#64748b', fontSize: '11px' },
                            formatter: cfg.yFormatter
                        }
                    },
                    tooltip: { theme: 'light', y: { formatter: cfg.tooltipFormatter } }
                };
                if (cfg.type === 'area') {
                    options.fill = {
                        type: 'gradient',
                        gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 90, 100] }
                    };
                }

                const chart = new ApexCharts(node, options);
                chart.render();

                if (! toggle) return;
                const buttons = toggle.querySelectorAll('[data-granularity]');
                buttons.forEach((btn) => {
                    btn.addEventListener('click', function () {
                        const g = btn.getAttribute('data-granularity');
                        if (g === current) return;
                        current = g;

                        bucket = bucketize(cfg.monthly, g);
                        tuneAxis(bucket.labels.length, g);
                        chart.updateOptions({
                            series: [{ name: cfg.seriesName, data: bucket.data }],
                            xaxis: xaxisFor(bucket)
                        });

                        buttons.forEach((b) => {
                            const on = b.getAttribute('data-granularity') === g;
                            b.setAttribute('aria-pressed', on ? 'true' : 'false');
                            // active chip ↔ muted text (mirrors the Blade $toggleOn/$toggleOff classes)
                            b.classList.toggle('border-slate-200', on);
                            b.classList.toggle('bg-white', on);
                            b.classList.toggle('font-semibold', on);
                            b.classList.toggle('text-slate-900', on);
                            b.classList.toggle('shadow-sm', on);
                            b.classList.toggle('border-transparent', ! on);
                            b.classList.toggle('text-slate-500', ! on);
                            b.classList.toggle('hover:text-slate-700', ! on);
                        });
                    });
                });
            };

            renderGranularityChart({
                nodeSelector: '#netProfitChart',
                toggleKey: 'netProfit',
                monthly: netProfitMonthly,
                seriesName: 'Net Profit',
                color: '#059669',
                type: 'area',
                yMin: undefined,
                yTitle: 'Net Profit (EUR)',
                yFormatter: function (value) { return compactCurrency(value); },
                tooltipFormatter: function (value) { return euro.format(value); }
            });

            // ── Stacked bars: Revenues per Client ─────────────────────────
            // Companies are stacked series over a monthly axis. The Monthly /
            // Quarterly / Yearly toggle sums each series across buckets (revenue is
            // additive), and the company filter isolates the evolution of chosen
            // companies over time. Dated by Live Date.
            const revenuePerCompanyMonths = @json($revenuePerCompanyMonths);
            const revenuePerCompanySeries = @json($revenuePerCompanySeries);   // default view
            const revenuePerCompanyList   = @json($revenuePerCompanyList);     // every company

            const renderStackedClientChart = function (cfg) {
                const node = document.querySelector(cfg.nodeSelector);
                const toggle = document.querySelector('[data-granularity-toggle="' + cfg.toggleKey + '"]');
                if (! node) return;

                const parseLabel = function (label) {
                    const parts = String(label).split(' ');
                    return { y: Number(parts[1]), m: MONTHS_ABBR.indexOf(parts[0]) };
                };

                // Roll the monthly matrix up into the chosen granularity (sum per series).
                const bucketize = function (months, series, g) {
                    if (g === 'monthly') {
                        return { labels: months.slice(), series: series.map((s) => ({ name: s.name, data: s.data.slice() })) };
                    }
                    const outLabels = [];
                    const idxByKey = new Map();
                    const monthOutIdx = months.map((label) => {
                        const parsed = parseLabel(label);
                        const key = g === 'yearly'
                            ? String(parsed.y)
                            : 'Q' + (Math.floor(parsed.m / 3) + 1) + ' ' + parsed.y;
                        let idx = idxByKey.get(key);
                        if (idx === undefined) {
                            idx = outLabels.length;
                            idxByKey.set(key, idx);
                            outLabels.push(key);
                        }
                        return idx;
                    });
                    const outSeries = series.map((s) => {
                        const data = new Array(outLabels.length).fill(0);
                        s.data.forEach((v, i) => { data[monthOutIdx[i]] += v; });
                        return { name: s.name, data: data.map((x) => Math.round(x * 100) / 100) };
                    });
                    return { labels: outLabels, series: outSeries };
                };

                // Drop leading/trailing months where every visible series is zero,
                // so a filtered selection starts at its own first month with data.
                const trimEmpty = function (months, series) {
                    let first = null, last = null;
                    for (let i = 0; i < months.length; i++) {
                        const has = series.some((s) => (s.data[i] || 0) !== 0);
                        if (has) { if (first === null) first = i; last = i; }
                    }
                    if (first === null) return { months: [], series: series.map((s) => ({ name: s.name, data: [] })) };
                    return {
                        months: months.slice(first, last + 1),
                        series: series.map((s) => ({ name: s.name, data: s.data.slice(first, last + 1) }))
                    };
                };

                // Base data for the current selection: default view when nothing is
                // picked, otherwise just the chosen companies re-trimmed to their range.
                const computeBase = function (selected) {
                    if (! selected || selected.length === 0) {
                        return { months: cfg.months, series: cfg.series };
                    }
                    const pick = new Set(selected);
                    const chosen = cfg.companies.filter((c) => pick.has(c.name));
                    return trimEmpty(cfg.months, chosen);
                };

                // Distinct hues for companies; "Others"/"Unassigned" pinned to greys.
                const PALETTE = ['#059669', '#2563eb', '#f59e0b', '#db2777', '#7c3aed',
                                 '#0891b2', '#65a30d', '#dc2626', '#0d9488', '#c026d3'];
                const colorsFor = function (series) {
                    let ci = 0;
                    return series.map((s) => {
                        if (s.name === 'Unassigned') return '#94a3b8';
                        if (s.name === 'Others') return '#cbd5e1';
                        return PALETTE[(ci++) % PALETTE.length];
                    });
                };

                let step = 1;
                let rot = 0;
                const tuneAxis = function (n) {
                    step = Math.max(1, Math.ceil(n / 14));
                    rot = n > 24 ? -40 : (n > 14 ? -25 : 0);
                };

                const xaxisFor = function (labels) {
                    return {
                        ...commonOptions.xaxis,
                        categories: labels,
                        tickAmount: Math.min(labels.length, 14),
                        labels: {
                            ...commonOptions.xaxis.labels,
                            rotate: rot,
                            formatter: function (value, _timestamp, opts) {
                                const index = opts && typeof opts.dataPointIndex === 'number' ? opts.dataPointIndex : 0;
                                return index % step === 0 ? value : '';
                            }
                        }
                    };
                };

                let current = 'monthly';
                let selected = [];
                let chart = null;

                const applyState = function () {
                    const base = computeBase(selected);
                    const bucket = bucketize(base.months, base.series, current);
                    tuneAxis(bucket.labels.length);
                    const colors = colorsFor(bucket.series);

                    if (! chart) {
                        chart = new ApexCharts(node, {
                            ...commonOptions,
                            chart: { ...commonOptions.chart, type: 'bar', height: 460, stacked: true },
                            series: bucket.series,
                            colors: colors,
                            plotOptions: { bar: { columnWidth: '68%', borderRadius: 2 } },
                            dataLabels: { enabled: false },
                            stroke: { show: false, width: 0 },
                            legend: {
                                show: true, position: 'bottom', horizontalAlign: 'left',
                                fontSize: '12px', markers: { radius: 3 }, itemMargin: { horizontal: 8, vertical: 3 }
                            },
                            xaxis: xaxisFor(bucket.labels),
                            yaxis: {
                                min: 0,
                                forceNiceScale: true,
                                title: { text: 'Revenues (EUR)' },
                                labels: {
                                    style: { colors: '#64748b', fontSize: '11px' },
                                    formatter: function (value) { return compactCurrency(value); }
                                }
                            },
                            tooltip: { theme: 'light', shared: true, intersect: false, y: { formatter: function (value) { return euro.format(value); } } }
                        });
                        chart.render();
                        return;
                    }

                    chart.updateOptions({
                        series: bucket.series,
                        colors: colors,
                        xaxis: xaxisFor(bucket.labels)
                    });
                };

                applyState();

                // Granularity toggle.
                if (toggle) {
                    const buttons = toggle.querySelectorAll('[data-granularity]');
                    buttons.forEach((btn) => {
                        btn.addEventListener('click', function () {
                            const g = btn.getAttribute('data-granularity');
                            if (g === current) return;
                            current = g;
                            applyState();

                            buttons.forEach((b) => {
                                const on = b.getAttribute('data-granularity') === g;
                                b.setAttribute('aria-pressed', on ? 'true' : 'false');
                                b.classList.toggle('border-slate-200', on);
                                b.classList.toggle('bg-white', on);
                                b.classList.toggle('font-semibold', on);
                                b.classList.toggle('text-slate-900', on);
                                b.classList.toggle('shadow-sm', on);
                                b.classList.toggle('border-transparent', ! on);
                                b.classList.toggle('text-slate-500', ! on);
                                b.classList.toggle('hover:text-slate-700', ! on);
                            });
                        });
                    });
                }

                // Company filter (select2), revealed by the header filter button.
                // select2 is initialised lazily on first open so it measures a
                // visible (non-zero) width.
                const filterBtn = cfg.filterButtonSelector ? document.querySelector(cfg.filterButtonSelector) : null;
                const filterPanel = cfg.filterPanelSelector ? document.querySelector(cfg.filterPanelSelector) : null;
                let select2Inited = false;

                const refreshFilterBtn = function () {
                    if (! filterBtn) return;
                    const open = filterPanel && ! filterPanel.classList.contains('hidden');
                    const active = open || selected.length > 0;
                    filterBtn.classList.toggle('border-green-300', active);
                    filterBtn.classList.toggle('bg-green-50', active);
                    filterBtn.classList.toggle('text-green-700', active);
                    filterBtn.classList.toggle('border-slate-200', ! active);
                    filterBtn.classList.toggle('text-slate-500', ! active);
                };

                const initSelect2 = function () {
                    if (select2Inited || ! cfg.filterSelector || ! window.jQuery) return;
                    const $filter = window.jQuery(cfg.filterSelector);
                    if (! $filter.length) return;
                    $filter.select2({
                        placeholder: 'All companies',
                        allowClear: true,
                        width: '100%',
                        closeOnSelect: false
                    });
                    $filter.on('change', function () {
                        selected = window.jQuery(this).val() || [];
                        applyState();
                        refreshFilterBtn();
                    });
                    select2Inited = true;
                };

                if (filterBtn && filterPanel) {
                    filterBtn.addEventListener('click', function () {
                        const willShow = filterPanel.classList.contains('hidden');
                        filterPanel.classList.toggle('hidden', ! willShow);
                        filterBtn.setAttribute('aria-expanded', willShow ? 'true' : 'false');
                        if (willShow) initSelect2();
                        refreshFilterBtn();
                    });
                }
            };

            renderStackedClientChart({
                nodeSelector: '#revenuesPerClientChart',
                toggleKey: 'revenuesPerClient',
                filterSelector: '#companyRevenueFilter',
                filterButtonSelector: '#companyFilterToggle',
                filterPanelSelector: '#companyRevenueFilterPanel',
                months: revenuePerCompanyMonths,
                series: revenuePerCompanySeries,
                companies: revenuePerCompanyList
            });
        });

    </script>
@endpush
