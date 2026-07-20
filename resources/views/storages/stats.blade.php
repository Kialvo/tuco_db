@extends('layouts.dashboard')

@section('subnav')
    @include('layouts.partials.stats-sidebar')
@endsection

@section('content')
    <div class="mx-auto max-w-7xl space-y-6 py-2">
        {{-- Universal-filter note + the date-range picker, top-right. Every widget
             on this page reflects only storages with status Article Published. --}}
        <div class="flex flex-wrap items-center justify-end gap-3">
            <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-600">
                <x-icon name="info" size="sm" class="shrink-0 text-slate-400" />
                All stats below show only storages with status
                <span class="font-semibold text-slate-800">Article Published</span>
            </span>

            <form id="statsFiltersForm" method="GET" action="{{ route('stats.production') }}"
                  x-data="statsRangePicker({
                      dateFrom: @js($dateFrom ?? ''),
                      dateTo: @js($dateTo ?? ''),
                  })"
                  class="flex items-end gap-3">

                {{-- Preserve the publisher widgets' website filters across a date change. --}}
                @foreach($pubArticleSelected as $s)
                    <input type="hidden" name="article_sites[]" value="{{ $s }}">
                @endforeach
                @foreach($pubSpendSelected as $s)
                    <input type="hidden" name="spend_sites[]" value="{{ $s }}">
                @endforeach

                @include('stats.partials.date-range-picker', ['showDateLabel' => false])
            </form>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Total Published Articles</p>
            <p class="mt-2 text-4xl font-bold text-slate-900">{{ number_format($totalPublished) }}</p>
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

        {{-- Publisher time-series widgets: published articles + € spent by website.
             Each has its own server-side website filter; submitting one preserves
             the sibling filter + the page's date/window/granularity via $preserve. --}}
        @php
            // Only the date range is still user-controllable on this page, so it's
            // the only page-level param the publisher filters need to preserve.
            $pubPageFilters = array_filter([
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
            ], fn ($v) => $v !== null && $v !== '');
        @endphp

        <div class="grid grid-cols-1 gap-6">
            @include('stats.partials.publisher-bar-widget', [
                'title'          => 'PUBLISHED ARTICLES PER WEBSITE',
                'subtitle'       => 'Number of published articles, stacked by publisher website (Domain). Dated by Publication Date.',
                'chartId'        => 'pubArticlesChart',
                'toggleKey'      => 'pubArticles',
                'filterButtonId' => 'pubArticlesFilterToggle',
                'filterPanelId'  => 'pubArticlesFilterPanel',
                'selectId'       => 'pubArticlesSiteFilter',
                'formRoute'      => 'stats.production',
                'paramName'      => 'article_sites',
                'selected'       => $pubArticleSelected,
                'preserve'       => $pubPageFilters + ['spend_sites' => $pubSpendSelected],
                'siteOptions'    => $pubSiteOptions,
                'series'         => $pubArticleWidget['series'],
                'itemHeader'     => 'Website',
                'valueHeader'    => 'Articles',
                'isMoney'        => false,
            ])

            @include('stats.partials.publisher-bar-widget', [
                'title'          => '€ SPENT PER WEBSITE',
                'subtitle'       => 'Publisher payment (EUR) stacked by publisher website (Domain). Dated by Publication Date.',
                'chartId'        => 'pubSpendChart',
                'toggleKey'      => 'pubSpend',
                'filterButtonId' => 'pubSpendFilterToggle',
                'filterPanelId'  => 'pubSpendFilterPanel',
                'selectId'       => 'pubSpendSiteFilter',
                'formRoute'      => 'stats.production',
                'paramName'      => 'spend_sites',
                'selected'       => $pubSpendSelected,
                'preserve'       => $pubPageFilters + ['article_sites' => $pubArticleSelected],
                'siteOptions'    => $pubSiteOptions,
                'series'         => $pubSpendWidget['series'],
                'itemHeader'     => 'Website',
                'valueHeader'    => 'Spent',
                'isMoney'        => true,
            ])
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const labels = @json($labels);
            const publishedSeries = @json($publishedSeries);
            const copyMedianSeries = @json($copyMedianSeries);
            const publisherMedianSeries = @json($publisherMedianSeries);
            const granularity = @json($granularity);
            const totalPoints = labels.length;
            const maxVisibleTicks = granularity === 'quarterly' ? 10 : 14;
            const labelStep = Math.max(1, Math.ceil(totalPoints / maxVisibleTicks));
            const labelRotation = totalPoints > 24 ? -40 : (totalPoints > 14 ? -25 : 0);

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

    </script>
@endpush

@push('scripts')
    {{-- Publisher stacked-bar widgets (published articles + € spent by website).
         Separate closure so its own consts don't collide with the charts above.
         Series are pre-filtered server-side; the Monthly/Quarterly/Yearly toggle
         re-buckets client-side. --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof ApexCharts === 'undefined') return;

            const MONTHS_ABBR = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            const euro = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'EUR', minimumFractionDigits: 0, maximumFractionDigits: 0 });
            const compactCurrency = function (v) {
                const a = Math.abs(v);
                if (a >= 1000000) return 'EUR ' + (v / 1000000).toFixed(1) + 'M';
                if (a >= 1000) return 'EUR ' + (v / 1000).toFixed(1) + 'k';
                return 'EUR ' + v.toFixed(0);
            };

            const pubCommonOptions = {
                chart: { foreColor: '#334155', toolbar: { show: false }, animations: { enabled: true, easing: 'easeout', speed: 400 } },
                noData: { text: 'No article_published data available', align: 'center', verticalAlign: 'middle', style: { color: '#64748b' } },
                grid: { borderColor: '#e2e8f0', strokeDashArray: 4, padding: { left: 8, right: 8, top: 6, bottom: 6 } },
                xaxis: {
                    tickPlacement: 'on',
                    axisBorder: { color: '#cbd5e1' }, axisTicks: { color: '#cbd5e1' },
                    labels: { hideOverlappingLabels: true, trim: true, style: { colors: '#64748b', fontSize: '11px' } }
                }
            };

            const PUB_PALETTE = ['#059669', '#2563eb', '#f59e0b', '#db2777', '#7c3aed',
                                 '#0891b2', '#65a30d', '#dc2626', '#0d9488', '#c026d3'];

            const renderPublisherStacked = function (cfg) {
                const node = document.querySelector(cfg.nodeSelector);
                if (! node) return;
                const toggle = document.querySelector('[data-granularity-toggle="' + cfg.toggleKey + '"]');

                const parseLabel = function (label) {
                    const parts = String(label).split(' ');
                    return { y: Number(parts[1]), m: MONTHS_ABBR.indexOf(parts[0]) };
                };

                const bucketize = function (months, series, g) {
                    if (g === 'monthly') {
                        return { labels: months.slice(), series: series.map((s) => ({ name: s.name, data: s.data.slice() })) };
                    }
                    const outLabels = [];
                    const idxByKey = new Map();
                    const monthOutIdx = months.map((label) => {
                        const parsed = parseLabel(label);
                        const key = g === 'yearly' ? String(parsed.y) : 'Q' + (Math.floor(parsed.m / 3) + 1) + ' ' + parsed.y;
                        let idx = idxByKey.get(key);
                        if (idx === undefined) { idx = outLabels.length; idxByKey.set(key, idx); outLabels.push(key); }
                        return idx;
                    });
                    const outSeries = series.map((s) => {
                        const data = new Array(outLabels.length).fill(0);
                        s.data.forEach((v, i) => { data[monthOutIdx[i]] += v; });
                        return { name: s.name, data: cfg.isMoney ? data.map((x) => Math.round(x * 100) / 100) : data };
                    });
                    return { labels: outLabels, series: outSeries };
                };

                const colorsFor = function (series) {
                    let ci = 0;
                    return series.map((s) => {
                        if (s.name === 'Others') return '#cbd5e1';
                        if (s.name === '(No domain)') return '#94a3b8';
                        return PUB_PALETTE[(ci++) % PUB_PALETTE.length];
                    });
                };

                let step = 1, rot = 0;
                const tuneAxis = function (n) {
                    step = Math.max(1, Math.ceil(n / 14));
                    rot = n > 24 ? -40 : (n > 14 ? -25 : 0);
                };
                const xaxisFor = function (labels) {
                    return {
                        ...pubCommonOptions.xaxis,
                        categories: labels,
                        tickAmount: Math.min(labels.length, 14),
                        labels: {
                            ...pubCommonOptions.xaxis.labels,
                            rotate: rot,
                            formatter: function (value, _t, opts) {
                                const index = opts && typeof opts.dataPointIndex === 'number' ? opts.dataPointIndex : 0;
                                return index % step === 0 ? value : '';
                            }
                        }
                    };
                };

                let current = 'monthly';
                let chart = null;

                const applyState = function () {
                    const bucket = bucketize(cfg.months, cfg.series, current);
                    tuneAxis(bucket.labels.length);
                    const colors = colorsFor(bucket.series);
                    if (! chart) {
                        chart = new ApexCharts(node, {
                            ...pubCommonOptions,
                            chart: { ...pubCommonOptions.chart, type: 'bar', height: 460, stacked: true },
                            series: bucket.series,
                            colors: colors,
                            plotOptions: { bar: { columnWidth: '68%', borderRadius: 2 } },
                            dataLabels: { enabled: false },
                            legend: { show: true, position: 'bottom', horizontalAlign: 'left', fontSize: '12px', markers: { radius: 3 }, itemMargin: { horizontal: 8, vertical: 3 } },
                            xaxis: xaxisFor(bucket.labels),
                            yaxis: {
                                min: 0, forceNiceScale: true,
                                title: { text: cfg.yTitle },
                                labels: { style: { colors: '#64748b', fontSize: '11px' }, formatter: cfg.yFormatter }
                            },
                            tooltip: { theme: 'light', shared: true, intersect: false, y: { formatter: cfg.tooltipFormatter } }
                        });
                        chart.render();
                        return;
                    }
                    chart.updateOptions({ series: bucket.series, colors: colors, xaxis: xaxisFor(bucket.labels) });
                };

                applyState();

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
            };

            // Filter panel: icon toggles the panel; select2 initialises lazily on
            // first open so it measures a visible width. The Apply button submits.
            const wireFilter = function (buttonId, panelId, selectId) {
                const btn = document.getElementById(buttonId);
                const panel = document.getElementById(panelId);
                if (! btn || ! panel) return;
                let inited = false;
                const initSelect2 = function () {
                    if (inited || ! window.jQuery) return;
                    const $s = window.jQuery('#' + selectId);
                    if (! $s.length) return;
                    $s.select2({ placeholder: 'Search websites…', allowClear: true, width: '100%', closeOnSelect: false });
                    inited = true;
                };
                if (! panel.classList.contains('hidden')) initSelect2();
                btn.addEventListener('click', function () {
                    const willShow = panel.classList.contains('hidden');
                    panel.classList.toggle('hidden', ! willShow);
                    btn.setAttribute('aria-expanded', willShow ? 'true' : 'false');
                    if (willShow) initSelect2();
                });
            };

            renderPublisherStacked({
                nodeSelector: '#pubArticlesChart',
                toggleKey: 'pubArticles',
                months: @json($pubArticleWidget['months']),
                series: @json($pubArticleWidget['series']),
                isMoney: false,
                yTitle: 'Published Articles',
                yFormatter: function (v) { return Math.round(v).toLocaleString(); },
                tooltipFormatter: function (v) { return Math.round(v).toLocaleString() + ' articles'; }
            });
            wireFilter('pubArticlesFilterToggle', 'pubArticlesFilterPanel', 'pubArticlesSiteFilter');

            renderPublisherStacked({
                nodeSelector: '#pubSpendChart',
                toggleKey: 'pubSpend',
                months: @json($pubSpendWidget['months']),
                series: @json($pubSpendWidget['series']),
                isMoney: true,
                yTitle: 'Spent (EUR)',
                yFormatter: function (v) { return compactCurrency(v); },
                tooltipFormatter: function (v) { return euro.format(v); }
            });
            wireFilter('pubSpendFilterToggle', 'pubSpendFilterPanel', 'pubSpendSiteFilter');
        });
    </script>
@endpush
