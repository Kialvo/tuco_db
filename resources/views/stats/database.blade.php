@extends('layouts.dashboard')

@section('title', 'Database Statistics')

@section('subnav')
    @include('layouts.partials.stats-sidebar')
@endsection

@section('content')
    <div class="mx-auto max-w-7xl space-y-6 py-2">
        {{-- Header --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-semibold text-slate-900">Database Statistics</h1>
            <p class="mt-2 text-sm text-slate-600">
                Overview and health metrics for the domain database.
            </p>
        </div>

        {{-- Total domains KPI tile --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Total domains</p>
            <p class="mt-2 text-4xl font-bold text-slate-900">{{ number_format($totalDomains) }}</p>
            <p class="mt-2 text-sm text-slate-600">Non-deleted domains in the database.</p>
        </div>

        {{-- Website status donut --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Website status</h2>
            <p class="mt-1 text-sm text-slate-500">Active vs inactive vs blacklisted domains.</p>
            <div id="websiteStatusChart" class="mt-4 mx-auto max-w-md"></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const el = document.querySelector('#websiteStatusChart');
            if (! el) return;

            new ApexCharts(el, {
                chart:   { type: 'donut', height: 340 },
                labels:  @json($statusChart['labels']),
                series:  @json($statusChart['series']),
                colors:  ['#10b981', '#f59e0b', '#ef4444'], // emerald / amber / red
                legend:  { position: 'bottom' },
                dataLabels: {
                    formatter: (percent, opts) => opts.w.config.series[opts.seriesIndex],
                },
                tooltip: {
                    y: { formatter: (v) => v.toLocaleString() + ' domains' },
                },
            }).render();
        });
    </script>
@endpush
