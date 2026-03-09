@extends('layouts.dashboard')

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
                      class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:w-auto xl:grid-cols-[220px_180px_170px_170px_auto_auto]">
                    <label class="flex flex-col gap-1">
                        <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Time Window</span>
                        <select name="window"
                                class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-200">
                            @foreach($windowOptions as $value => $label)
                                <option value="{{ $value }}" @selected($window === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="flex flex-col gap-1">
                        <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Granularity</span>
                        <select name="granularity"
                                class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-200">
                            @foreach($granularityOptions as $value => $label)
                                <option value="{{ $value }}" @selected($granularity === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="flex flex-col gap-1">
                        <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">From</span>
                        <input type="text"
                               name="date_from"
                               value="{{ $dateFrom }}"
                               placeholder="YYYY-MM-DD"
                               autocomplete="off"
                               class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-200">
                    </label>

                    <label class="flex flex-col gap-1">
                        <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">To</span>
                        <input type="text"
                               name="date_to"
                               value="{{ $dateTo }}"
                               placeholder="YYYY-MM-DD"
                               autocomplete="off"
                               class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-200">
                    </label>

                    <button type="submit"
                            class="inline-flex h-[42px] items-center justify-center self-end rounded-xl bg-cyan-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-cyan-700 focus:outline-none focus:ring-2 focus:ring-cyan-300">
                        Apply
                    </button>

                    <a href="{{ route('storages.stats') }}"
                       class="inline-flex h-[42px] items-center justify-center gap-2 self-end rounded-xl border border-pink-200 bg-pink-50 px-4 text-sm font-semibold text-pink-700 transition hover:bg-pink-100 focus:outline-none focus:ring-2 focus:ring-pink-200">
                        <i class="fas fa-rotate-left text-xs"></i>
                        Reset
                    </a>

                    <p class="text-xs text-slate-500 sm:col-span-2 lg:col-span-3 xl:col-span-6">
                        @if($hasCustomRange)
                            Custom dates are active. The preset window is ignored until you switch the window or reset the filters.
                        @else
                            Pick a custom date range for the charts or keep using the preset window.
                        @endif
                    </p>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <section class="relative overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-sky-50 to-white p-6 shadow-sm">
                <div class="mb-4 inline-flex h-10 w-10 items-center justify-center rounded-lg bg-sky-100 text-sky-700">
                    <i class="fas fa-newspaper text-sm"></i>
                </div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Total Published Articles</p>
                <p class="mt-3 text-4xl font-bold leading-none text-slate-900">{{ number_format($totalPublished) }}</p>
                <p class="mt-3 text-sm text-slate-600">{{ number_format($pointsCount) }} periods in current view</p>
            </section>

            <section class="relative overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-emerald-50 to-white p-6 shadow-sm">
                <div class="mb-4 inline-flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                    <i class="fas fa-euro-sign text-sm"></i>
                </div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Total Net Profit</p>
                <p class="mt-3 text-4xl font-bold leading-none text-emerald-700">
                    EUR {{ number_format((float) $totalNetProfit, 2, '.', ',') }}
                </p>
                <p class="mt-3 text-sm text-slate-600">Net value across visible periods</p>
            </section>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Articles Published Per {{ $granularity === 'quarterly' ? 'Quarter' : 'Month' }}</h2>
                <p class="mt-1 text-sm text-slate-500">Publication count trend for the selected scope.</p>
                <div id="publishedPerMonthChart" class="mt-4 h-[390px]"></div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Net Profit Per {{ $granularity === 'quarterly' ? 'Quarter' : 'Month' }}</h2>
                <p class="mt-1 text-sm text-slate-500">Profit progression across the same selected periods.</p>
                <div id="netProfitPerMonthChart" class="mt-4 h-[390px]"></div>
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
            const profitSeries = @json($profitSeries);
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

            new ApexCharts(document.querySelector('#publishedPerMonthChart'), {
                ...commonOptions,
                chart: {
                    ...commonOptions.chart,
                    type: 'bar',
                    height: 390
                },
                series: [{
                    name: 'Articles Published',
                    data: publishedSeries
                }],
                colors: ['#2563eb'],
                plotOptions: {
                    bar: {
                        borderRadius: 5,
                        columnWidth: '62%'
                    }
                },
                dataLabels: { enabled: false },
                xaxis: {
                    ...commonOptions.xaxis,
                    tickAmount: Math.min(totalPoints, 12),
                },
                yaxis: {
                    title: { text: 'Articles' },
                    labels: {
                        style: { colors: '#64748b', fontSize: '11px' },
                        formatter: function (value) {
                            return Math.round(value).toString();
                        }
                    }
                },
                tooltip: {
                    theme: 'light',
                    y: {
                        formatter: function (value) {
                            const n = Math.round(value);
                            return n + ' article' + (n === 1 ? '' : 's');
                        }
                    }
                }
            }).render();

            new ApexCharts(document.querySelector('#netProfitPerMonthChart'), {
                ...commonOptions,
                chart: {
                    ...commonOptions.chart,
                    type: 'area',
                    height: 390
                },
                series: [{
                    name: 'Net Profit',
                    data: profitSeries
                }],
                colors: ['#059669'],
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.35,
                        opacityTo: 0.05,
                        stops: [0, 90, 100]
                    }
                },
                dataLabels: { enabled: false },
                xaxis: {
                    ...commonOptions.xaxis,
                    tickAmount: Math.min(totalPoints, 10),
                },
                yaxis: {
                    title: { text: 'Net Profit (EUR)' },
                    labels: {
                        style: { colors: '#64748b', fontSize: '11px' },
                        formatter: function (value) {
                            return compactCurrency(value);
                        }
                    }
                },
                tooltip: {
                    theme: 'light',
                    y: {
                        formatter: function (value) {
                            return euro.format(value);
                        }
                    }
                }
            }).render();

            const form = document.getElementById('statsFiltersForm');
            if (form) {
                const windowSelect = form.querySelector('select[name="window"]');
                const granularitySelect = form.querySelector('select[name="granularity"]');
                const dateInputs = form.querySelectorAll('input[name="date_from"], input[name="date_to"]');

                dateInputs.forEach(function (input) {
                    if (typeof flatpickr === 'function') {
                        flatpickr(input, {
                            dateFormat: 'Y-m-d',
                            allowInput: true
                        });
                    }
                });

                if (windowSelect) {
                    windowSelect.addEventListener('change', function () {
                        dateInputs.forEach(function (input) {
                            if (input._flatpickr) {
                                input._flatpickr.clear();
                            } else {
                                input.value = '';
                            }
                        });

                        form.submit();
                    });
                }

                if (granularitySelect) {
                    granularitySelect.addEventListener('change', function () {
                        form.submit();
                    });
                }

                const dateFromInput = form.querySelector('input[name="date_from"]');
                const dateToInput = form.querySelector('input[name="date_to"]');

                if (dateFromInput && dateToInput) {
                    [dateFromInput, dateToInput].forEach(function (input) {
                        input.addEventListener('keydown', function (event) {
                            if (event.key === 'Enter') {
                                form.submit();
                            }
                        });
                    });
                }
            }
        });
    </script>
@endpush
