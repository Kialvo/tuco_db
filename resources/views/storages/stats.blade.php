@extends('layouts.dashboard')

@section('subnav')
    @include('layouts.partials.stats-sidebar')
@endsection

@section('content')
    <div class="mx-auto max-w-7xl space-y-6 py-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900">Publication Statistics</h1>
                    <p class="mt-2 text-sm text-slate-600">
                        Trend and profitability for storages with status <strong>article_published</strong>.
                    </p>
                    @if($rangeLabel)
                        <p class="mt-2 text-xs font-medium uppercase tracking-wide text-slate-500">
                            Visible period: {{ $rangeLabel }}
                        </p>
                    @endif
                </div>

                <form id="statsFiltersForm" method="GET" action="{{ route('storages.stats') }}"
                      x-data="statsRangePicker({
                          window: @js($window),
                          dateFrom: @js($dateFrom ?? ''),
                          dateTo: @js($dateTo ?? ''),
                          hasCustomRange: @js($hasCustomRange),
                          windowOptions: @js($windowOptions),
                      })"
                      class="flex w-full flex-wrap items-end gap-3 xl:w-auto xl:flex-nowrap">

                    {{-- Params submitted with the form; kept in sync by the picker. --}}
                    <input type="hidden" name="window" :value="window">
                    <input type="hidden" name="date_from" :value="dateFrom">
                    <input type="hidden" name="date_to" :value="dateTo">

                    {{-- Date-range dropdown (ported from menford-analytics DateRangePicker). --}}
                    <div class="relative flex flex-col gap-1"
                         @keydown.escape.window="open = false"
                         @click.outside="open = false">
                        <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Date Range</span>
                        <button type="button"
                                @click="open = !open"
                                :aria-expanded="open.toString()"
                                class="inline-flex h-[42px] min-w-[240px] items-center gap-2 rounded-xl border border-green-200 bg-green-50 px-3 text-sm font-medium text-green-700 shadow-sm transition hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-200">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0" aria-hidden="true">
                                <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="flex-1 text-left" x-text="displayLabel"></span>
                            <x-icon name="chevron-down" size="sm" class="shrink-0 transition-transform"
                                    ::class="open ? 'rotate-180' : ''" />
                        </button>

                        <div x-show="open" x-cloak x-transition
                             role="dialog" aria-label="Select date range"
                             class="absolute left-0 top-full z-30 mt-2 w-72 origin-top-left rounded-xl border border-slate-200 bg-white p-3 shadow-xl">
                            {{-- Presets (reuse the tested `window` slicing logic). --}}
                            <div class="space-y-1">
                                <template x-for="preset in presets" :key="preset.key">
                                    <button type="button"
                                            @click="applyPreset(preset)"
                                            class="w-full rounded-lg px-3 py-2 text-left text-sm transition"
                                            :class="isActivePreset(preset) ? 'bg-green-50 font-medium text-green-700' : 'text-slate-700 hover:bg-slate-50'"
                                            x-text="preset.label"></button>
                                </template>
                            </div>

                            {{-- Custom range. --}}
                            <div class="mt-2 border-t border-slate-200 pt-2">
                                <button type="button"
                                        @click="showCustom = !showCustom"
                                        class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm text-slate-500 transition hover:bg-slate-50 hover:text-slate-700">
                                    Custom range
                                    <x-icon name="chevron-down" size="sm" class="transition-transform"
                                            ::class="showCustom ? 'rotate-180' : ''" />
                                </button>

                                <div x-show="showCustom" x-cloak class="mt-2 space-y-2 px-1">
                                    <div>
                                        <label class="mb-1 block text-xs text-slate-500">Start date</label>
                                        <input type="date" x-model="customStart" :max="customEnd || null"
                                               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-200">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs text-slate-500">End date</label>
                                        <input type="date" x-model="customEnd" :min="customStart || null"
                                               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-200">
                                    </div>
                                    <button type="button"
                                            :disabled="!customStart || !customEnd || customStart > customEnd"
                                            @click="applyCustom()"
                                            class="w-full rounded-lg bg-green-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-40">
                                        Apply
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <label class="flex flex-col gap-1">
                        <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Granularity</span>
                        <select name="granularity"
                                @change="$el.form.submit()"
                                class="h-[42px] rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-700 shadow-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-200">
                            @foreach($granularityOptions as $value => $label)
                                <option value="{{ $value }}" @selected($granularity === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <a href="{{ route('storages.stats') }}"
                       class="inline-flex h-[42px] items-center justify-center gap-2 self-end rounded-xl border border-pink-200 bg-pink-50 px-4 text-sm font-semibold text-pink-700 transition hover:bg-pink-100 focus:outline-none focus:ring-2 focus:ring-pink-200">
                        <x-icon name="rotate" size="sm" class="inline" />
                        Reset
                    </a>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <section class="relative overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-sky-50 to-white p-6 shadow-sm">
                <div class="mb-4 inline-flex h-10 w-10 items-center justify-center rounded-lg bg-sky-100 text-sky-700">
                    <x-icon name="newspaper" size="sm" class="inline" />
                </div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Total Published Articles</p>
                <p class="mt-3 text-4xl font-bold leading-none text-slate-900">{{ number_format($totalPublished) }}</p>
                <p class="mt-3 text-sm text-slate-600">{{ number_format($pointsCount) }} periods in current view</p>
            </section>

            <section class="relative overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-emerald-50 to-white p-6 shadow-sm">
                <div class="mb-4 inline-flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                    <x-icon name="euro" size="sm" class="inline" />
                </div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Total Net Profit</p>
                <p class="mt-3 text-4xl font-bold leading-none text-emerald-700">
                    EUR {{ number_format((float) $totalNetProfit, 2, '.', ',') }}
                </p>
                <p class="mt-3 text-sm text-slate-600">Net value across visible periods</p>
            </section>
        </div>

        {{-- Per-widget granularity toggle (Monthly / Quarterly / Yearly), styled after
             menford-analytics' GranularityToggle. The buttons drive a client-side
             re-aggregation of a monthly source, independent of the page granularity. --}}
        @php
            $toggleBtn = 'rounded-md border px-3 py-1 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-green-200';
            $toggleOn  = 'border-slate-200 bg-white font-semibold text-slate-900 shadow-sm';
            $toggleOff = 'border-transparent text-slate-500 hover:text-slate-700';
        @endphp

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold uppercase tracking-wide text-slate-900">Guest Posts Published</h2>
                    </div>
                    <div data-granularity-toggle="guestPosts" role="group" aria-label="Data granularity"
                         class="inline-flex shrink-0 rounded-lg border border-slate-200 bg-slate-100 p-1 text-sm">
                        <button type="button" data-granularity="monthly"   aria-pressed="true"  class="{{ $toggleBtn }} {{ $toggleOn }}">Monthly</button>
                        <button type="button" data-granularity="quarterly" aria-pressed="false" class="{{ $toggleBtn }} {{ $toggleOff }}">Quarterly</button>
                        <button type="button" data-granularity="yearly"    aria-pressed="false" class="{{ $toggleBtn }} {{ $toggleOff }}">Yearly</button>
                    </div>
                </div>
                <div id="guestPostsPublishedChart" class="mt-4 h-[390px]"></div>
            </section>

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
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold uppercase tracking-wide text-slate-900">Copy Delivery Time</h2>
                <div id="copyDeliveryTimeChart" class="mt-4 h-[390px]"></div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold uppercase tracking-wide text-slate-900">Publisher Publication Time</h2>
                <div id="publisherPublicationTimeChart" class="mt-4 h-[390px]"></div>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const labels = @json($labels);
            const publishedSeries = @json($publishedSeries);
            const guestPostsMonthly = @json($guestPostsMonthly);
            const netProfitMonthly = @json($netProfitMonthly);
            const profitSeries = @json($profitSeries);
            const copyMedianSeries = @json($copyMedianSeries);
            const publisherMedianSeries = @json($publisherMedianSeries);
            const granularity = @json($granularity);
            const totalPoints = labels.length;
            const maxVisibleTicks = granularity === 'quarterly' ? 10 : 14;
            const labelStep = Math.max(1, Math.ceil(totalPoints / maxVisibleTicks));
            const labelRotation = totalPoints > 24 ? -40 : (totalPoints > 14 ? -25 : 0);

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

            const sparseLabelFormatter = function (value, _timestamp, opts) {
                const index = opts && typeof opts.dataPointIndex === 'number' ? opts.dataPointIndex : 0;
                return index % labelStep === 0 ? value : '';
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
                        rotate: labelRotation,
                        hideOverlappingLabels: true,
                        trim: true,
                        style: { colors: '#64748b', fontSize: '11px' },
                        formatter: sparseLabelFormatter
                    }
                }
            };

            // ── Granularity-toggle widgets ─────────────────────────────────
            // A line/area chart driven by its own Monthly / Quarterly / Yearly
            // segmented toggle. The source is always monthly; quarters/years are
            // summed client-side (ported from menford-analytics' bucketize()), so
            // each widget is independent of the page-level granularity select.
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
                nodeSelector: '#guestPostsPublishedChart',
                toggleKey: 'guestPosts',
                monthly: guestPostsMonthly,
                seriesName: 'Guest Posts Published',
                color: '#2563eb',
                type: 'line',
                yMin: 0,
                yTitle: 'Guest posts',
                yFormatter: function (value) { return Math.round(value).toString(); },
                tooltipFormatter: function (value) {
                    const n = Math.round(value);
                    return n + ' guest post' + (n === 1 ? '' : 's');
                }
            });

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

            const renderMedianDaysChart = function (selector, seriesName, data, color) {
                // Start the line at the first period that actually has data — drop
                // only the LEADING empty periods so the axis doesn't begin on months
                // with no median. Interior gaps (null between two data points) and
                // trailing nulls are left untouched.
                let firstIdx = data.findIndex(function (value) {
                    return value !== null && value !== undefined;
                });
                if (firstIdx < 0) firstIdx = 0;
                const chartData = data.slice(firstIdx);
                const chartLabels = labels.slice(firstIdx);
                const chartPoints = chartLabels.length;
                const chartLabelStep = Math.max(1, Math.ceil(chartPoints / (granularity === 'quarterly' ? 6 : 8)));

                new ApexCharts(document.querySelector(selector), {
                    ...commonOptions,
                    chart: {
                        ...commonOptions.chart,
                        type: 'line',
                        height: 390
                    },
                    series: [{
                        name: seriesName,
                        data: chartData
                    }],
                    colors: [color],
                    stroke: {
                        curve: 'straight',
                        width: 3,
                        lineCap: 'round'
                    },
                    markers: { size: 3, hover: { sizeOffset: 2 } },
                    dataLabels: { enabled: false },
                    // Median can be null for periods with no data — show a gap, not a fake 0.
                    fill: { opacity: 1 },
                    xaxis: {
                        ...commonOptions.xaxis,
                        categories: chartLabels,
                        tickAmount: Math.min(chartPoints, 8),
                        labels: {
                            ...commonOptions.xaxis.labels,
                            formatter: function (value, _timestamp, opts) {
                                const index = opts && typeof opts.dataPointIndex === 'number' ? opts.dataPointIndex : 0;
                                return index % chartLabelStep === 0 ? value : '';
                            },
                        },
                    },
                    yaxis: {
                        min: 0,
                        forceNiceScale: true,
                        title: { text: 'Median days' },
                        labels: {
                            style: { colors: '#64748b', fontSize: '11px' },
                            formatter: function (value) {
                                return Math.round(value).toString();
                            }
                        }
                    },
                    annotations: {
                        yaxis: [{
                            y: 2,
                            borderColor: '#ef4444',
                            strokeDashArray: 4,
                            label: {
                                text: 'Target 2d',
                                borderColor: '#ef4444',
                                style: { color: '#fff', background: '#ef4444', fontSize: '10px' }
                            }
                        }]
                    },
                    tooltip: {
                        theme: 'light',
                        y: {
                            formatter: function (value) {
                                if (value === null) return 'No data';
                                const rounded = Math.round(value * 10) / 10;
                                return rounded + ' day' + (rounded === 1 ? '' : 's');
                            }
                        }
                    }
                }).render();
            };

            renderMedianDaysChart('#copyDeliveryTimeChart', 'Copy Delivery Time', copyMedianSeries, '#6366f1');
            renderMedianDaysChart('#publisherPublicationTimeChart', 'Publisher Publication Time', publisherMedianSeries, '#0ea5e9');
        });

        // Date-range picker, ported from menford-analytics' DateRangePicker.tsx.
        // Presets drive the existing `window` param (reusing the controller's tested
        // month-window slicing); the custom range drives `date_from`/`date_to`.
        function statsRangePicker(config) {
            return {
                open: false,
                showCustom: config.hasCustomRange,
                window: config.window,
                dateFrom: config.dateFrom || '',
                dateTo: config.dateTo || '',
                customStart: config.dateFrom || '',
                customEnd: config.dateTo || '',
                windowOptions: config.windowOptions,
                hasCustomRange: config.hasCustomRange,

                get presets() {
                    // Friendly order; only keep windows the controller actually offers.
                    return ['12', '24', '36', '60', 'all']
                        .filter((key) => this.windowOptions[key])
                        .map((key) => ({ key: key, label: this.windowOptions[key] }));
                },

                get displayLabel() {
                    if (this.hasCustomRange && this.dateFrom && this.dateTo) {
                        return this.formatLabel(this.dateFrom) + ' – ' + this.formatLabel(this.dateTo);
                    }
                    if (this.hasCustomRange && this.dateFrom) return 'From ' + this.formatLabel(this.dateFrom);
                    if (this.hasCustomRange && this.dateTo) return 'Up to ' + this.formatLabel(this.dateTo);
                    return this.windowOptions[this.window] || 'Select range';
                },

                formatLabel(dateStr) {
                    if (!dateStr) return '';
                    const d = new Date(dateStr + 'T00:00:00');
                    if (isNaN(d.getTime())) return dateStr;
                    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                },

                isActivePreset(preset) {
                    return !this.hasCustomRange && this.window === preset.key;
                },

                submit() {
                    this.$nextTick(() => document.getElementById('statsFiltersForm').submit());
                },

                applyPreset(preset) {
                    this.window = preset.key;
                    this.dateFrom = '';
                    this.dateTo = '';
                    this.open = false;
                    this.submit();
                },

                applyCustom() {
                    if (!this.customStart || !this.customEnd || this.customStart > this.customEnd) return;
                    this.dateFrom = this.customStart;
                    this.dateTo = this.customEnd;
                    this.window = 'all'; // ignored by the controller while a custom range is set
                    this.open = false;
                    this.showCustom = false;
                    this.submit();
                },
            };
        }
    </script>
@endpush
