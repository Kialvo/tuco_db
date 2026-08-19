@extends('layouts.dashboard')

@section('title', 'Publisher Statistics')

@section('subnav')
    @include('layouts.partials.stats-sidebar')
@endsection

@section('content')
    <div class="mx-auto max-w-7xl space-y-6 py-2">
        {{-- Active domains KPI tile --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
                ACTIVE DOMAINS
                <x-ds.info-tip
                    label="How is active domains calculated?"
                    text="Count of websites whose status is Active. Deleted domains are excluded. This is the whole database — the Stats date picker does not apply to this page, because a domain's status is a current fact, not a dated event." />
            </p>
            <p class="mt-2 text-4xl font-bold text-slate-900">{{ number_format($activeDomains) }}</p>
        </div>

        {{-- Two pie widgets per row --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            @include('stats.partials.pie-table', [
                'title'      => 'WEBSITE STATUS',
                'subtitle'   => 'Active vs inactive vs blacklisted domains.',
                'info'       => 'Every website in the database counted by its current status — active, inactive or blacklisted. Deleted domains are excluded.',
                'chartId'    => 'websiteStatusChart',
                'itemHeader' => 'Status',
                'labels'     => $statusChart['labels'],
                'series'     => $statusChart['series'],
            ])

            @include('stats.partials.pie-table', [
                'title'      => 'ACTIVE WEBSITES BY COUNTRY',
                'subtitle'   => 'Top 10 countries by active domains; the rest grouped as “Other”.',
                'info'       => 'Active websites grouped by their assigned country. The 10 largest countries get their own slice; every remaining country is folded into “Other”, together with active domains that have no country set — so the total still equals the active-domains count above.',
                'chartId'    => 'countryChart',
                'itemHeader' => 'Country',
                'labels'     => $countryChart['labels'],
                'series'     => $countryChart['series'],
            ])

            @include('stats.partials.pie-table', [
                'title'      => 'ACTIVE WEBSITES BY TYPE',
                'subtitle'   => 'Active domains split by type of website.',
                'info'       => 'Active websites grouped by their type-of-website field. Domains with no type recorded are shown as “(None)” rather than dropped, so the total matches the active-domains count above.',
                'chartId'    => 'typeChart',
                'itemHeader' => 'Type',
                'labels'     => $typeChart['labels'],
                'series'     => $typeChart['series'],
            ])

            @include('stats.partials.pie-table', [
                'title'      => 'ACTIVE WEBSITES BY LANGUAGE',
                'subtitle'   => 'Top 10 languages by active domains; the rest grouped as “Other”.',
                'info'       => 'Active websites grouped by their assigned language. The 10 largest languages get their own slice; the rest are folded into “Other”, together with active domains that have no language set.',
                'chartId'    => 'languageChart',
                'itemHeader' => 'Language',
                'labels'     => $languageChart['labels'],
                'series'     => $languageChart['series'],
            ])
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof ApexCharts === 'undefined') return;

            // Pie + legend only; the per-slice number and % live in the table
            // beside each chart, so on-slice data labels are turned off.
            const pie = (selector, labels, series, colors) => {
                const node = document.querySelector(selector);
                if (! node) return;
                const opts = {
                    chart:   { type: 'pie', height: 340 },
                    labels:  labels,
                    series:  series,
                    legend:  { position: 'bottom' },
                    dataLabels: { enabled: false },
                    tooltip: {
                        y: { formatter: (v) => v.toLocaleString() + ' domains' },
                    },
                };
                if (colors) opts.colors = colors;
                new ApexCharts(node, opts).render();
            };

            pie('#websiteStatusChart', @json($statusChart['labels']), @json($statusChart['series']),
                ['#10b981', '#f59e0b', '#ef4444']); // emerald / amber / red
            pie('#countryChart',  @json($countryChart['labels']),  @json($countryChart['series']));
            pie('#typeChart',     @json($typeChart['labels']),     @json($typeChart['series']));
            pie('#languageChart', @json($languageChart['labels']), @json($languageChart['series']));
        });
    </script>
@endpush
