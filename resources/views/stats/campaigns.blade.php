@extends('layouts.dashboard')

@section('title', 'Campaigns Statistics')

@section('subnav')
    @include('layouts.partials.stats-sidebar')
@endsection

@section('content')
    <div class="mx-auto max-w-7xl space-y-6 py-2">
        {{-- Same sticky date-range picker as Production and Financial Stats.
             Unlike those pages, the two widget families here are dated by
             DIFFERENT columns (no single date fits both), so each says which. --}}
        @include('stats.partials.filters-bar', [
            'route' => 'stats.campaigns',
            'note' => 'Campaigns dated by completion date · approvals by proposal date',
            'noteStrong' => '',
        ])

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
                        Dated by <strong>completion date</strong> (latest live date).
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

        @php
            // A client can have proposals but no decision yet. Those are kept in
            // the table (with "—") but excluded from the 100%-stacked chart,
            // whose category total would be zero and render as NaN.
            $decidedClients = array_values(array_filter($decisionByClient, fn ($c) => $c['decided'] > 0));
        @endphp

        {{-- Site-approval funnel KPIs. Rates use a decided-only denominator, so
             they sum to 100% and pending proposals never silently drag them down. --}}
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Approval Rate</p>
                <p class="mt-2 text-4xl font-bold text-emerald-600">
                    {{ $decisionTotals['approvalRate'] === null ? '—' : number_format($decisionTotals['approvalRate'], 1) . '%' }}
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    {{ number_format($decisionTotals['approved']) }} of {{ number_format($decisionTotals['decided']) }} decided proposals
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Rejection Rate</p>
                <p class="mt-2 text-4xl font-bold text-red-600">
                    {{ $decisionTotals['rejectionRate'] === null ? '—' : number_format($decisionTotals['rejectionRate'], 1) . '%' }}
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    {{ number_format($decisionTotals['rejected']) }} of {{ number_format($decisionTotals['decided']) }} decided proposals
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Proposals</p>
                <p class="mt-2 text-4xl font-bold text-slate-900">{{ number_format($decisionTotals['proposed']) }}</p>
                <p class="mt-1 text-xs text-slate-500">
                    {{ number_format($decisionTotals['decided']) }} decided ·
                    {{ number_format($decisionTotals['pending']) }} awaiting a decision
                </p>
            </div>
        </div>

        <p class="-mt-2 text-xs text-slate-500">
            Sites proposed on Link Building CRM campaigns, dated by <strong>proposal date</strong> (when the site
            was added){{ $dateFrom || $dateTo ? '' : ' — all time' }}. Proposals still awaiting a decision are excluded from both rates; a recent range
            will therefore show more pending, since some decisions have not landed yet. A site that was approved
            and later fell through on the publisher’s side still counts as approved.
        </p>

        {{-- Approval vs rejection per client --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div>
                <h2 class="text-lg font-semibold uppercase tracking-wide text-slate-900">Approval vs Rejection by Client</h2>
                <p class="mt-1 text-sm text-slate-600">
                    Share of each client’s decided proposals that were approved or refused — normalised to 100%
                    so clients are comparable regardless of volume.
                </p>
            </div>

            @if(count($decidedClients) > 0)
                <div id="approvalByClientChart" class="mt-4"></div>
            @elseif(count($decisionByClient) === 0)
                <div class="mt-4">
                    <x-ds.empty-state
                        icon="briefcase"
                        title="No campaign publications yet"
                        hint="Sites proposed on a Link Building CRM campaign appear here as soon as they are added." />
                </div>
            @else
                <div class="mt-4">
                    <x-ds.empty-state
                        icon="briefcase"
                        title="No decisions yet"
                        hint="Every proposal so far is still awaiting the client’s decision." />
                </div>
            @endif

            @if(count($decisionByClient) > 0)
                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full text-sm" data-sortable>
                        <thead>
                            <tr class="border-b border-slate-200 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <th class="py-2 pr-4 text-left"  data-sort-key data-sort-type="text">Client <span data-sort-indicator></span></th>
                                <th class="py-2 pr-4 text-right" data-sort-key data-sort-type="number">Proposed <span data-sort-indicator></span></th>
                                <th class="py-2 pr-4 text-right" data-sort-key data-sort-type="number" data-sort-default>Decided <span data-sort-indicator></span></th>
                                <th class="py-2 pr-4 text-right" data-sort-key data-sort-type="number">Approved <span data-sort-indicator></span></th>
                                <th class="py-2 pr-4 text-right" data-sort-key data-sort-type="number">Rejected <span data-sort-indicator></span></th>
                                <th class="py-2 pr-4 text-right" data-sort-key data-sort-type="number">Approval % <span data-sort-indicator></span></th>
                                <th class="py-2 pr-4 text-right" data-sort-key data-sort-type="number">Rejection % <span data-sort-indicator></span></th>
                                <th class="py-2 text-right"      data-sort-key data-sort-type="number">Pending <span data-sort-indicator></span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($decisionByClient as $row)
                                <tr class="border-b border-slate-100">
                                    <td class="py-2 pr-4 text-left text-slate-700" data-sort-value="{{ $row['name'] }}">{{ $row['name'] }}</td>
                                    <td class="py-2 pr-4 text-right tabular-nums text-slate-600" data-sort-value="{{ $row['proposed'] }}">{{ number_format($row['proposed']) }}</td>
                                    <td class="py-2 pr-4 text-right tabular-nums text-slate-900" data-sort-value="{{ $row['decided'] }}">{{ number_format($row['decided']) }}</td>
                                    <td class="py-2 pr-4 text-right tabular-nums text-emerald-700" data-sort-value="{{ $row['approved'] }}">{{ number_format($row['approved']) }}</td>
                                    <td class="py-2 pr-4 text-right tabular-nums text-red-700" data-sort-value="{{ $row['rejected'] }}">{{ number_format($row['rejected']) }}</td>
                                    <td class="py-2 pr-4 text-right tabular-nums text-slate-600" data-sort-value="{{ $row['approvalRate'] ?? '' }}">
                                        {{ $row['approvalRate'] === null ? '—' : number_format($row['approvalRate'], 1) . '%' }}
                                    </td>
                                    <td class="py-2 pr-4 text-right tabular-nums text-slate-600" data-sort-value="{{ $row['rejectionRate'] ?? '' }}">
                                        {{ $row['rejectionRate'] === null ? '—' : number_format($row['rejectionRate'], 1) . '%' }}
                                    </td>
                                    <td class="py-2 text-right tabular-nums text-slate-500" data-sort-value="{{ $row['pending'] }}">{{ number_format($row['pending']) }}</td>
                                </tr>
                            @endforeach
                            <tr class="font-semibold text-slate-900" data-sort-pinned>
                                <td class="py-2 pr-4 text-left">Total</td>
                                <td class="py-2 pr-4 text-right tabular-nums">{{ number_format($decisionTotals['proposed']) }}</td>
                                <td class="py-2 pr-4 text-right tabular-nums">{{ number_format($decisionTotals['decided']) }}</td>
                                <td class="py-2 pr-4 text-right tabular-nums">{{ number_format($decisionTotals['approved']) }}</td>
                                <td class="py-2 pr-4 text-right tabular-nums">{{ number_format($decisionTotals['rejected']) }}</td>
                                <td class="py-2 pr-4 text-right tabular-nums">{{ $decisionTotals['approvalRate'] === null ? '—' : number_format($decisionTotals['approvalRate'], 1) . '%' }}</td>
                                <td class="py-2 pr-4 text-right tabular-nums">{{ $decisionTotals['rejectionRate'] === null ? '—' : number_format($decisionTotals['rejectionRate'], 1) . '%' }}</td>
                                <td class="py-2 text-right tabular-nums">{{ number_format($decisionTotals['pending']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Rejection reasons — overall donut + per-client split --}}
        @if(count($rejectionReasons) > 0)
            @include('stats.partials.pie-table', [
                'title'       => 'REJECTION REASONS',
                'subtitle'    => 'Why clients refused a proposed site, across all campaigns.',
                'chartId'     => 'rejectionReasonsChart',
                'itemHeader'  => 'Reason',
                'countHeader' => 'Publications',
                'labels'      => $rejectionReasonChart['labels'],
                'series'      => $rejectionReasonChart['series'],
            ])

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div>
                    <h2 class="text-lg font-semibold uppercase tracking-wide text-slate-900">Rejection Reasons by Client</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        What drives each client’s refusals — price sensitivity reads differently from a metrics
                        threshold, and each points at a different fix when picking sites to pitch.
                    </p>
                </div>
                <div id="rejectionReasonsByClientChart" class="mt-4"></div>
            </div>
        @else
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold uppercase tracking-wide text-slate-900">Rejection Reasons</h2>
                <x-ds.empty-state
                    icon="briefcase"
                    title="No rejections recorded"
                    hint="A proposal refused by the client is counted here with the reason chosen on its status." />
            </div>
        @endif
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

        // ── Site-approval funnel charts ────────────────────────────────────
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof ApexCharts === 'undefined') return;

            const pubs = (v) => v + (v === 1 ? ' publication' : ' publications');

            // Approval vs rejection per client — 100%-stacked so a client with 4
            // decisions is comparable to one with 140. Clients with zero decisions
            // are filtered out server-side; a zero category total renders as NaN.
            const approvalClients = @json(array_column($decidedClients, 'name'));
            const approvalNode = document.querySelector('#approvalByClientChart');

            if (approvalNode && approvalClients.length) {
                new ApexCharts(approvalNode, {
                    chart: {
                        type: 'bar', stacked: true, stackType: '100%',
                        height: Math.max(220, approvalClients.length * 56),
                        foreColor: '#334155', toolbar: { show: false },
                    },
                    series: [
                        { name: 'Approved', data: @json(array_column($decidedClients, 'approved')) },
                        { name: 'Rejected', data: @json(array_column($decidedClients, 'rejected')) },
                    ],
                    colors: ['#10b981', '#ef4444'],
                    plotOptions: { bar: { horizontal: true, barHeight: '58%', borderRadius: 2 } },
                    dataLabels: {
                        enabled: true,
                        formatter: (pct) => (pct >= 8 ? Math.round(pct) + '%' : ''),
                        style: { fontSize: '11px', colors: ['#fff'] },
                    },
                    stroke: { show: false, width: 0 },
                    xaxis: {
                        categories: approvalClients,
                        labels: { formatter: (v) => Math.round(v) + '%', style: { colors: '#64748b', fontSize: '11px' } },
                    },
                    yaxis: { labels: { style: { colors: '#64748b', fontSize: '11px' }, maxWidth: 220 } },
                    legend: { position: 'bottom', horizontalAlign: 'left' },
                    grid: { borderColor: '#e2e8f0', strokeDashArray: 4 },
                    // Absolute counts in the tooltip — the bars are normalised,
                    // so the percentage alone would hide how thin the sample is.
                    tooltip: { theme: 'light', y: { formatter: (v) => pubs(v) } },
                }).render();
            }

            // Rejection reasons — overall donut (pie-table partial's slot).
            const reasonNode = document.querySelector('#rejectionReasonsChart');
            if (reasonNode) {
                new ApexCharts(reasonNode, {
                    chart: { type: 'donut', height: 320, foreColor: '#334155', toolbar: { show: false } },
                    series: @json($rejectionReasonChart['series']),
                    labels: @json($rejectionReasonChart['labels']),
                    colors: ['#ef4444', '#f59e0b', '#ec4899', '#7c3aed', '#0891b2', '#64748b'],
                    dataLabels: { enabled: true, formatter: (pct) => Math.round(pct) + '%' },
                    legend: { position: 'bottom', horizontalAlign: 'center', fontSize: '12px' },
                    stroke: { width: 0 },
                    tooltip: { theme: 'light', y: { formatter: (v) => pubs(v) } },
                }).render();
            }

            // Rejection reasons per client — stacked absolute counts (not 100%),
            // so volume stays visible alongside the mix.
            const reasonClients = @json($rejectionReasonsByClient['clients']);
            const reasonSeries  = @json($rejectionReasonsByClient['series']);
            const byClientNode  = document.querySelector('#rejectionReasonsByClientChart');

            if (byClientNode && reasonClients.length && reasonSeries.length) {
                new ApexCharts(byClientNode, {
                    chart: {
                        type: 'bar', stacked: true,
                        height: Math.max(220, reasonClients.length * 56),
                        foreColor: '#334155', toolbar: { show: false },
                    },
                    series: reasonSeries,
                    colors: ['#ef4444', '#f59e0b', '#ec4899', '#7c3aed', '#0891b2', '#64748b'],
                    plotOptions: { bar: { horizontal: true, barHeight: '58%', borderRadius: 2 } },
                    dataLabels: { enabled: false },
                    stroke: { show: false, width: 0 },
                    xaxis: {
                        categories: reasonClients,
                        title: { text: 'Rejected publications' },
                        labels: { formatter: (v) => Math.round(v), style: { colors: '#64748b', fontSize: '11px' } },
                    },
                    yaxis: { labels: { style: { colors: '#64748b', fontSize: '11px' }, maxWidth: 220 } },
                    legend: { position: 'bottom', horizontalAlign: 'left', fontSize: '12px' },
                    grid: { borderColor: '#e2e8f0', strokeDashArray: 4 },
                    tooltip: { theme: 'light', shared: true, intersect: false, y: { formatter: (v) => pubs(v) } },
                }).render();
            }
        });
    </script>

    @include('stats.partials.sortable-table-script')
@endpush
