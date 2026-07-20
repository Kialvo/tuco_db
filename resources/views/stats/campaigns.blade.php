@extends('layouts.dashboard')

@section('title', 'Campaigns Statistics')

@section('subnav')
    @include('layouts.partials.stats-sidebar')
@endsection

@section('content')
    <div class="mx-auto max-w-7xl space-y-6 py-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-semibold text-slate-900">Campaigns Statistics</h1>
            <p class="mt-2 text-sm text-slate-600">
                Completed campaigns over time, broken down by how the deadline was respected.
            </p>
        </div>

        {{-- Campaigns completed by month, stacked by completion status --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
                        CAMPAIGNS COMPLETED BY MONTH
                    </p>
                    <p class="mt-1 text-sm text-slate-600">
                        Campaigns completed each month, stacked by completion status.
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-3xl font-bold leading-none text-slate-900">{{ number_format($campaignsChart['totalCompleted']) }}</p>
                    <p class="mt-1 text-xs text-slate-500">completed (in view)</p>
                </div>
            </div>

            @if(count($campaignsChart['series']) > 0)
                <div id="campaignsCompletedChart" class="mt-4"></div>
            @else
                <div class="mt-4">
                    <x-ds.empty-state
                        icon="briefcase"
                        title="No completed campaigns yet"
                        hint="Campaigns move into this chart once they reach a “Completed” status with a completion date." />
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof ApexCharts === 'undefined') return;

            const node = document.querySelector('#campaignsCompletedChart');
            if (! node) return;

            new ApexCharts(node, {
                chart:   { type: 'bar', height: 420, stacked: true, toolbar: { show: false } },
                series:  @json($campaignsChart['series']),
                colors:  @json($campaignsChart['colors']),
                xaxis:   { categories: @json($campaignsChart['categories']) },
                yaxis:   {
                    title:  { text: 'Campaigns completed' },
                    labels: { formatter: (v) => Math.round(v) },
                    // whole campaigns only — no fractional gridlines
                    forceNiceScale: true,
                },
                plotOptions: { bar: { columnWidth: '55%' } },
                dataLabels:  { enabled: false },
                legend:      { position: 'bottom' },
                tooltip:     {
                    y: { formatter: (v) => v + (v === 1 ? ' campaign' : ' campaigns') },
                },
            }).render();
        });
    </script>
@endpush
