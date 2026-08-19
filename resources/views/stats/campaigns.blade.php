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
                        <x-ds.info-tip
                            label="How is campaigns completed by month calculated?"
                            text="A campaign counts in the month of its completion date, which is derived as the LATEST live date among its publications — campaigns with no live publication cannot be placed on the axis and are skipped. Bars are stacked by the campaign's Completed status, so on-time work and each kind of delay stay separate. Deleted campaigns are excluded." />
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
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
                    Approval Rate
                    <x-ds.info-tip
                        label="How is the approval rate calculated?"
                        text="Approved ÷ decided proposals, where decided = approved + rejected. Proposals still awaiting the client's answer are excluded from the denominator, so approval and rejection sum to 100% and a pile of pending sites never drags the rate down. Scope is sites proposed on a Link Building CRM campaign, dated by proposal date. A site approved by the client but later dropped on the publisher's side still counts as approved." />
                </p>
                <p class="mt-2 text-4xl font-bold text-emerald-600">
                    {{ $decisionTotals['approvalRate'] === null ? '—' : number_format($decisionTotals['approvalRate'], 1) . '%' }}
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    {{ number_format($decisionTotals['approved']) }} of {{ number_format($decisionTotals['decided']) }} decided proposals
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
                    Rejection Rate
                    <x-ds.info-tip
                        label="How is the rejection rate calculated?"
                        text="Rejected ÷ decided proposals, where decided = approved + rejected. Pending proposals are excluded from the denominator, so this is exactly 100% minus the approval rate. Scope is sites proposed on a Link Building CRM campaign, dated by proposal date." />
                </p>
                <p class="mt-2 text-4xl font-bold text-red-600">
                    {{ $decisionTotals['rejectionRate'] === null ? '—' : number_format($decisionTotals['rejectionRate'], 1) . '%' }}
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    {{ number_format($decisionTotals['rejected']) }} of {{ number_format($decisionTotals['decided']) }} decided proposals
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
                    Proposals
                    <x-ds.info-tip
                        label="How is the proposals count calculated?"
                        text="Every site proposed on a Link Building CRM campaign inside the selected range, counted by proposal date (when the site was added). Split into decided (approved or rejected) and awaiting a decision. Legacy publications with no campaign are deliberately out of scope: before the CRM a row was usually only created after the client had already accepted, which would report a survivorship-biased approval rate." />
                </p>
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

        {{-- Approval / rejection rate over time — the trend the KPI cards above
             flatten away. Rates are recomputed from summed counts per bucket
             (never averaged across months), and a bucket with no decision draws
             a gap rather than a 0% nobody measured. --}}
        @php
            // Blade's @json() cannot parse a nested-array default inline, so the
            // fallback shape is normalised here instead.
            $decisionTrend = ($decisionTrend ?? []) + [
                'months'  => [],
                'overall' => ['approved' => [], 'rejected' => []],
                'clients' => [],
            ];
            $trendClients = $decisionTrend['clients'];

            // Same segmented-toggle chrome the Financial/Production widgets use.
            $tToggleBtn = 'rounded-md border px-3 py-1 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-green-200';
            $tToggleOn  = 'border-slate-200 bg-white font-semibold text-slate-900 shadow-sm';
            // slate-600, not slate-500: an inactive chip sits on bg-slate-100, where
            // slate-500 measures 4.34:1 — just under the 4.5:1 text minimum.
            // publisher-bar-widget already uses slate-600 (6.92:1) for this reason.
            $tToggleOff = 'border-transparent text-slate-600 hover:text-slate-800';
        @endphp

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold uppercase tracking-wide text-slate-900">
                        Approval / Rejection Rate over time
                        <x-ds.info-tip
                            label="How is the approval/rejection rate over time calculated?"
                            text="For each period: approved ÷ (approved + rejected) of that client's proposals, as a percentage — the rejection rate is the same sum with rejected on top. Proposals are placed in the month they were PROPOSED (the site's creation date on a Link Building CRM campaign), so a period is a proposal cohort. Quarterly and Yearly re-sum the underlying counts before dividing, so a month with 2 decisions never weighs as much as one with 200. A period where the client had no decision at all is a gap in the line, not 0%. Proposals still awaiting a decision are excluded." />
                    </h2>
                    <p class="mt-1 text-sm text-slate-600">
                        The all-clients rate across periods. Use the filter to break it out per client.
                        Dated by <strong>proposal date</strong>.
                    </p>
                </div>

                @if(count($trendClients) > 0)
                    <div class="flex shrink-0 items-center gap-2">
                        <button type="button" id="trendClientFilterToggle"
                                aria-label="Filter by client" aria-expanded="false"
                                aria-controls="trendClientFilterPanel"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm transition-colors hover:border-slate-300 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-green-200">
                            <x-icon name="filter" size="md" />
                        </button>

                        <div data-metric-toggle="decisionTrend" role="group" aria-label="Rate shown"
                             class="inline-flex rounded-lg border border-slate-200 bg-slate-100 p-1 text-sm">
                            <button type="button" data-metric="approved" aria-pressed="true"  class="{{ $tToggleBtn }} {{ $tToggleOn }}">Approval</button>
                            <button type="button" data-metric="rejected" aria-pressed="false" class="{{ $tToggleBtn }} {{ $tToggleOff }}">Rejection</button>
                        </div>

                        <div data-granularity-toggle="decisionTrend" role="group" aria-label="Data granularity"
                             class="inline-flex rounded-lg border border-slate-200 bg-slate-100 p-1 text-sm">
                            <button type="button" data-granularity="monthly"   aria-pressed="true"  class="{{ $tToggleBtn }} {{ $tToggleOn }}">Monthly</button>
                            <button type="button" data-granularity="quarterly" aria-pressed="false" class="{{ $tToggleBtn }} {{ $tToggleOff }}">Quarterly</button>
                            <button type="button" data-granularity="yearly"    aria-pressed="false" class="{{ $tToggleBtn }} {{ $tToggleOff }}">Yearly</button>
                        </div>
                    </div>
                @endif
            </div>

            @if(count($trendClients) > 0)
                {{-- Breakdown chooser. The widget opens on the aggregate alone —
                     one line reads instantly, where every client at once does not.
                     "All clients only" is listed explicitly so the default state is
                     visible and reversible, not a mode you can leave but not re-enter. --}}
                <div id="trendClientFilterPanel" class="mt-4 hidden max-w-xl rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <fieldset>
                        <legend class="mb-2 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                            Break down by client
                        </legend>

                        <div class="space-y-2">
                            <label class="flex cursor-pointer items-start gap-2 text-sm text-slate-700">
                                <input type="radio" name="trendMode" value="overall" checked
                                       class="mt-0.5 h-4 w-4 shrink-0 border-slate-300 text-green-600 focus:ring-green-200">
                                <span>
                                    <span class="font-medium text-slate-900">All clients only</span>
                                    <span class="block text-sm text-slate-500">A single aggregate line — the default.</span>
                                </span>
                            </label>

                            <label class="flex cursor-pointer items-start gap-2 text-sm text-slate-700">
                                <input type="radio" name="trendMode" value="active"
                                       class="mt-0.5 h-4 w-4 shrink-0 border-slate-300 text-green-600 focus:ring-green-200">
                                <span>
                                    <span class="font-medium text-slate-900">All active clients in this period</span>
                                    <span class="block text-sm text-slate-500">
                                        One line per client with at least one decision in the selected range
                                        ({{ count($trendClients) }} {{ count($trendClients) === 1 ? 'client' : 'clients' }}).
                                    </span>
                                </span>
                            </label>

                            <label class="flex cursor-pointer items-start gap-2 text-sm text-slate-700">
                                <input type="radio" name="trendMode" value="specific"
                                       class="mt-0.5 h-4 w-4 shrink-0 border-slate-300 text-green-600 focus:ring-green-200">
                                <span>
                                    <span class="font-medium text-slate-900">Select specific clients</span>
                                    <span class="block text-sm text-slate-500">Pick the clients to compare.</span>
                                </span>
                            </label>
                        </div>
                    </fieldset>

                    {{-- Revealed only by the "specific" mode. --}}
                    <div id="trendClientSelectWrap" class="mt-3 hidden border-t border-slate-200 pt-3">
                        <label for="trendClientFilter" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                            Filter by client
                        </label>
                        <select id="trendClientFilter" multiple class="w-full">
                            @foreach($trendClients as $c)
                                <option value="{{ $c['name'] }}">{{ $c['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div id="decisionTrendChart" class="mt-4 h-[420px]"></div>
            @else
                <div class="mt-4">
                    <x-ds.empty-state
                        icon="briefcase"
                        title="No decided proposals to trend yet"
                        hint="A proposal appears here once the client has approved or refused it." />
                </div>
            @endif
        </div>

        {{-- Approval vs rejection per client --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div>
                <h2 class="text-lg font-semibold uppercase tracking-wide text-slate-900">
                    Approval vs Rejection by Client
                    <x-ds.info-tip
                        label="How is approval vs rejection by client calculated?"
                        text="Each client's decided proposals split into approved and refused, normalised to 100% so a client with 4 decisions is comparable to one with 140. The tooltip shows the absolute counts behind the bar. Clients with no decision yet are excluded — their bar would have a zero total." />
                </h2>
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
                'info'        => 'Refused proposals grouped by the reason recorded on the publication\'s status. The share column is each reason as a percentage of all rejections in the selected range, dated by proposal date.',
                'chartId'     => 'rejectionReasonsChart',
                'itemHeader'  => 'Reason',
                'countHeader' => 'Publications',
                'labels'      => $rejectionReasonChart['labels'],
                'series'      => $rejectionReasonChart['series'],
            ])

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div>
                    <h2 class="text-lg font-semibold uppercase tracking-wide text-slate-900">
                        Rejection Reasons by Client
                        <x-ds.info-tip
                            label="How are rejection reasons by client calculated?"
                            text="Each client's refused proposals split by the reason recorded on the publication's status, as absolute counts rather than percentages so volume stays visible alongside the mix. Only clients with at least one rejection appear." />
                    </h2>
                    <p class="mt-1 text-sm text-slate-600">
                        What drives each client’s refusals — price sensitivity reads differently from a metrics
                        threshold, and each points at a different fix when picking sites to pitch.
                    </p>
                </div>
                <div id="rejectionReasonsByClientChart" class="mt-4"></div>
            </div>
        @else
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold uppercase tracking-wide text-slate-900">
                    Rejection Reasons
                    <x-ds.info-tip
                        label="How are rejection reasons calculated?"
                        text="Refused proposals grouped by the reason recorded on the publication's status, across all Link Building CRM campaigns in the selected range." />
                </h2>
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

    {{-- statsSortedTooltip(): shared tooltips listed highest → lowest. --}}
    @include('stats.partials.chart-tooltip-script')

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
                    theme: 'light', shared: true, intersect: false,
                    custom: statsSortedTooltip((v) => v + (v === 1 ? ' campaign' : ' campaigns')),
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
                    tooltip: { theme: 'light', shared: true, intersect: false, custom: statsSortedTooltip(pubs) },
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
                    tooltip: { theme: 'light', shared: true, intersect: false, custom: statsSortedTooltip(pubs) },
                }).render();
            }
        });
    </script>


    {{-- ── Approval / Rejection Rate over time ──────────────────────────────
         One line per client plus the all-clients aggregate. COUNTS arrive from
         the controller and the rate is computed here, because the granularity
         toggle re-sums the counts first: a quarter's rate is
         sum(approved) / sum(decided), never the mean of three monthly rates.
         A bucket with zero decisions stays null so the line breaks. --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof ApexCharts === 'undefined') return;

            const node = document.querySelector('#decisionTrendChart');
            if (! node) return;

            const trend = @json($decisionTrend);
            if (! trend.months.length) return;

            const MONTHS_ABBR = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            const OVERALL_NAME = 'All clients';
            const PALETTE = ['#059669', '#2563eb', '#f59e0b', '#db2777', '#7c3aed',
                             '#0891b2', '#65a30d', '#dc2626', '#0d9488', '#c026d3'];

            const parseLabel = function (label) {
                const parts = String(label).split(' ');
                return { y: Number(parts[1]), m: MONTHS_ABBR.indexOf(parts[0]) };
            };

            // Month → quarter/year buckets, summing the raw counts. Returns the
            // bucket labels plus, per entity, the summed approved/rejected arrays.
            const bucketize = function (entities, g) {
                if (g === 'monthly') {
                    return {
                        labels: trend.months.slice(),
                        entities: entities.map(function (e) {
                            return { name: e.name, approved: e.approved.slice(), rejected: e.rejected.slice() };
                        }),
                    };
                }
                const outLabels = [];
                const idxByKey = new Map();
                const monthOutIdx = trend.months.map(function (label) {
                    const parsed = parseLabel(label);
                    const key = g === 'yearly'
                        ? String(parsed.y)
                        : 'Q' + (Math.floor(parsed.m / 3) + 1) + ' ' + parsed.y;
                    let idx = idxByKey.get(key);
                    if (idx === undefined) { idx = outLabels.length; idxByKey.set(key, idx); outLabels.push(key); }
                    return idx;
                });
                const out = entities.map(function (e) {
                    const a = new Array(outLabels.length).fill(0);
                    const r = new Array(outLabels.length).fill(0);
                    e.approved.forEach(function (v, i) { a[monthOutIdx[i]] += v; });
                    e.rejected.forEach(function (v, i) { r[monthOutIdx[i]] += v; });
                    return { name: e.name, approved: a, rejected: r };
                });
                return { labels: outLabels, entities: out };
            };

            // Rate per bucket, and the counts behind it for the tooltip.
            const rateOf = function (entity, metric) {
                const data = [];
                const meta = [];
                for (let i = 0; i < entity.approved.length; i++) {
                    const a = entity.approved[i];
                    const r = entity.rejected[i];
                    const decided = a + r;
                    data.push(decided > 0 ? Math.round((metric === 'approved' ? a : r) / decided * 1000) / 10 : null);
                    meta.push({ part: metric === 'approved' ? a : r, decided: decided });
                }
                return { data: data, meta: meta };
            };

            let metric = 'approved';
            let granularity = 'monthly';
            // 'overall' (default, aggregate only) | 'active' (every client with a
            // decision in the range) | 'specific' (the picked clients).
            let mode = 'overall';
            let selected = [];
            let chart = null;
            let metaBySeries = [];       // parallel to the rendered series, for the tooltip

            const overallEntity = {
                name: OVERALL_NAME,
                approved: trend.overall.approved,
                rejected: trend.overall.rejected,
            };

            // trend.clients arrives ranked by decided volume and ALREADY scoped to the
            // selected date range — a client with no decision in the range is dropped
            // controller-side, so "active in this period" is exactly this list.
            const chosenClients = function () {
                if (mode === 'active') return trend.clients;
                if (mode === 'specific') {
                    const pick = new Set(selected);
                    return trend.clients.filter(function (c) { return pick.has(c.name); });
                }
                return [];
            };

            // Sorted tooltip, highest rate first, carrying the counts the rate
            // hides — 100% off two decisions is not the same claim as 100% off 80.
            const esc = function (v) {
                return String(v == null ? '' : v).replace(/[&<>"']/g, function (ch) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
                });
            };
            const tooltip = function (ctx) {
                const w = ctx.w;
                const i = ctx.dataPointIndex;
                const collapsed = w.globals.collapsedSeriesIndices || [];
                const rows = [];
                for (let s = 0; s < ctx.series.length; s++) {
                    if (collapsed.indexOf(s) !== -1) continue;
                    const value = ctx.series[s] ? ctx.series[s][i] : undefined;
                    if (value === undefined || value === null) continue;
                    const m = (metaBySeries[s] && metaBySeries[s][i]) || { part: 0, decided: 0 };
                    rows.push({ name: w.globals.seriesNames[s], value: Number(value), color: w.globals.colors[s], meta: m });
                }
                rows.sort(function (a, b) { return b.value - a.value; });

                // Same as statsSortedTooltip: the category TEXT lives in
                // categoryLabels; globals.labels is a numeric index array here.
                const cat = w.globals.categoryLabels && w.globals.categoryLabels[i];
                const fb = w.globals.labels && w.globals.labels[i];
                const title = (cat !== undefined && cat !== null && cat !== '')
                    ? cat
                    : (typeof fb === 'string' ? fb : '');
                let html = '<div class="apexcharts-tooltip-title">' + esc(title) + '</div>';
                if (! rows.length) {
                    return html + '<div style="padding:4px 10px;color:#64748b;">No decisions in this period</div>';
                }
                rows.forEach(function (row) {
                    html += '<div class="apexcharts-tooltip-series-group apexcharts-active" style="display:flex;">'
                        + '<span class="apexcharts-tooltip-marker" style="background-color:' + esc(row.color) + '"></span>'
                        + '<div class="apexcharts-tooltip-text"><div class="apexcharts-tooltip-y-group">'
                        + '<span class="apexcharts-tooltip-text-y-label">' + esc(row.name) + ': </span>'
                        + '<span class="apexcharts-tooltip-text-y-value">' + row.value.toFixed(1) + '%</span>'
                        + '<span style="color:#64748b;"> (' + row.meta.part + ' of ' + row.meta.decided + ' decided)</span>'
                        + '</div></div></div>';
                });
                return html;
            };

            // The aggregate is drawn as a dashed reference line ONLY when there are
            // client lines to reference. On its own — the default view — a lone dashed
            // line reads as a projection or an estimate, so it is drawn solid.
            const strokeWidths = function (series) {
                return series.map(function (s) { return s.name === OVERALL_NAME && series.length > 1 ? 4 : 3; });
            };
            const dashPattern = function (series) {
                return series.map(function (s) { return s.name === OVERALL_NAME && series.length > 1 ? 5 : 0; });
            };

            const applyState = function () {
                const bucket = bucketize([overallEntity].concat(chosenClients()), granularity);
                const series = [];
                const colors = [];
                metaBySeries = [];
                let ci = 0;

                bucket.entities.forEach(function (entity) {
                    const computed = rateOf(entity, metric);
                    series.push({ name: entity.name, data: computed.data });
                    metaBySeries.push(computed.meta);
                    // The aggregate is the reference line, so it keeps a fixed
                    // neutral colour instead of competing for a palette hue.
                    colors.push(entity.name === OVERALL_NAME ? '#0f172a' : PALETTE[(ci++) % PALETTE.length]);
                });

                const n = bucket.labels.length;
                const step = Math.max(1, Math.ceil(n / 14));
                const rot = n > 24 ? -40 : (n > 14 ? -25 : 0);

                const xaxis = {
                    categories: bucket.labels,
                    tickPlacement: 'on',
                    tickAmount: Math.min(n, 14),
                    axisBorder: { color: '#cbd5e1' },
                    axisTicks: { color: '#cbd5e1' },
                    labels: {
                        rotate: rot, hideOverlappingLabels: true, trim: true,
                        style: { colors: '#64748b', fontSize: '11px' },
                        formatter: function (value, _t, opts) {
                            const idx = opts && typeof opts.dataPointIndex === 'number' ? opts.dataPointIndex : 0;
                            return idx % step === 0 ? value : '';
                        },
                    },
                };

                if (! chart) {
                    chart = new ApexCharts(node, {
                        chart: {
                            type: 'line', height: 420, foreColor: '#334155',
                            toolbar: { show: false },
                            animations: { enabled: true, easing: 'easeout', speed: 400 },
                        },
                        series: series,
                        colors: colors,
                        // The aggregate is drawn thicker; a null bucket is a real
                        // gap, so connectNulls stays off.
                        stroke: { curve: 'straight', width: strokeWidths(series), dashArray: dashPattern(series), lineCap: 'round' },
                        markers: { size: 3, hover: { sizeOffset: 2 } },
                        dataLabels: { enabled: false },
                        grid: { borderColor: '#e2e8f0', strokeDashArray: 4, padding: { left: 8, right: 8, top: 6, bottom: 6 } },
                        legend: { show: true, position: 'bottom', horizontalAlign: 'left', fontSize: '12px', markers: { radius: 3 }, itemMargin: { horizontal: 8, vertical: 3 } },
                        noData: { text: 'No decided proposals in this range', align: 'center', verticalAlign: 'middle', style: { color: '#64748b' } },
                        xaxis: xaxis,
                        yaxis: {
                            min: 0, max: 100, tickAmount: 5,
                            title: { text: 'Rate (%)' },
                            labels: { style: { colors: '#64748b', fontSize: '11px' }, formatter: function (v) { return Math.round(v) + '%'; } },
                        },
                        tooltip: { theme: 'light', shared: true, intersect: false, custom: tooltip },
                    });
                    chart.render();
                    return;
                }

                chart.updateOptions({
                    series: series,
                    colors: colors,
                    stroke: { curve: 'straight', width: strokeWidths(series), dashArray: dashPattern(series), lineCap: 'round' },
                    xaxis: xaxis,
                });
            };

            applyState();

            // Segmented toggles — same active/muted chrome as the other widgets.
            const paintToggle = function (buttons, attr, active) {
                buttons.forEach(function (b) {
                    const on = b.getAttribute(attr) === active;
                    b.setAttribute('aria-pressed', on ? 'true' : 'false');
                    b.classList.toggle('border-slate-200', on);
                    b.classList.toggle('bg-white', on);
                    b.classList.toggle('font-semibold', on);
                    b.classList.toggle('text-slate-900', on);
                    b.classList.toggle('shadow-sm', on);
                    b.classList.toggle('border-transparent', ! on);
                    b.classList.toggle('text-slate-600', ! on);
                    b.classList.toggle('hover:text-slate-800', ! on);
                });
            };

            const metricToggle = document.querySelector('[data-metric-toggle="decisionTrend"]');
            if (metricToggle) {
                const buttons = Array.from(metricToggle.querySelectorAll('[data-metric]'));
                buttons.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const m = btn.getAttribute('data-metric');
                        if (m === metric) return;
                        metric = m;
                        applyState();
                        paintToggle(buttons, 'data-metric', m);
                    });
                });
            }

            const granToggle = document.querySelector('[data-granularity-toggle="decisionTrend"]');
            if (granToggle) {
                const buttons = Array.from(granToggle.querySelectorAll('[data-granularity]'));
                buttons.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const g = btn.getAttribute('data-granularity');
                        if (g === granularity) return;
                        granularity = g;
                        applyState();
                        paintToggle(buttons, 'data-granularity', g);
                    });
                });
            }

            // ── Breakdown chooser ─────────────────────────────────────────
            // The filter icon opens the panel; the panel's radios pick the mode.
            // select2 is built lazily the first time "specific" is chosen, so it
            // measures a visible (non-zero) width.
            const filterBtn = document.querySelector('#trendClientFilterToggle');
            const filterPanel = document.querySelector('#trendClientFilterPanel');
            const selectWrap = document.querySelector('#trendClientSelectWrap');
            const modeInputs = Array.from(document.querySelectorAll('input[name="trendMode"]'));
            let select2Inited = false;

            // The icon reads "active" whenever a breakdown is applied, so the state
            // is visible with the panel closed.
            const refreshFilterBtn = function () {
                if (! filterBtn) return;
                const open = filterPanel && ! filterPanel.classList.contains('hidden');
                const active = open || mode !== 'overall';
                filterBtn.classList.toggle('border-green-300', active);
                filterBtn.classList.toggle('bg-green-50', active);
                filterBtn.classList.toggle('text-green-700', active);
                filterBtn.classList.toggle('border-slate-200', ! active);
                filterBtn.classList.toggle('text-slate-500', ! active);
            };

            const initSelect2 = function () {
                if (select2Inited || ! window.jQuery) return;
                const $filter = window.jQuery('#trendClientFilter');
                if (! $filter.length) return;
                $filter.select2({
                    placeholder: 'Choose one or more clients',
                    allowClear: true,
                    width: '100%',
                    closeOnSelect: false,
                });
                $filter.on('change', function () {
                    selected = window.jQuery(this).val() || [];
                    if (mode === 'specific') applyState();
                });
                select2Inited = true;
            };

            modeInputs.forEach(function (input) {
                input.addEventListener('change', function () {
                    if (! input.checked) return;
                    mode = input.value;
                    // The picker only belongs to "specific"; leaving that mode hides
                    // it but KEEPS the selection, so coming back restores the view.
                    if (selectWrap) selectWrap.classList.toggle('hidden', mode !== 'specific');
                    if (mode === 'specific') initSelect2();
                    applyState();
                    refreshFilterBtn();
                });
            });

            if (filterBtn && filterPanel) {
                filterBtn.addEventListener('click', function () {
                    const willShow = filterPanel.classList.contains('hidden');
                    filterPanel.classList.toggle('hidden', ! willShow);
                    filterBtn.setAttribute('aria-expanded', willShow ? 'true' : 'false');
                    if (willShow && mode === 'specific') initSelect2();
                    refreshFilterBtn();
                });
            }
        });
    </script>

    @include('stats.partials.sortable-table-script')
@endpush
