@extends('layouts.dashboard')

@section('title', 'Marketplace Growth Statistics')

@section('subnav')
    @include('layouts.partials.stats-sidebar')
@endsection

@section('content')
    <div class="mx-auto max-w-7xl space-y-6 py-2">

        <header>
            <h1 class="text-2xl font-bold text-slate-900">Marketplace · Growth</h1>
            <p class="mt-1 max-w-3xl text-sm text-slate-600">
                Acquisition, activation and engagement for the self-serve marketplace.
                Every figure counts <strong>guest accounts only</strong> — admin, editor and publisher
                activity is excluded, because the marketplace question is whether outside buyers use it.
            </p>
        </header>

        {{-- ── Headline tiles ─────────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
            @include('stats.partials.kpi-tile', [
                'label' => 'Guest Signups',
                'value' => number_format($totalSignups),
                'info'  => 'Every account with the guest role, counted by its registration date. Unverified bot signups that the users:purge-unverified-guests command has already deleted are not here — the count is of accounts that still exist.',
                'sub'   => 'All time',
            ])

            @include('stats.partials.kpi-tile', [
                'label' => 'Activated Buyers',
                'value' => number_format($totalActivated),
                'info'  => 'Guests who have done at least one thing that costs money: submitted an order, or paid for a token top-up. A guest is counted once, in the month of whichever came first — funding a wallet counts as activation even before the first order.',
                'sub'   => $totalSignups > 0
                    ? number_format($totalActivated / $totalSignups * 100, 1) . '% of signups'
                    : '—',
            ])

            @include('stats.partials.kpi-tile', [
                'label' => 'Repeat-Buyer Rate',
                'value' => $repeatRate === null ? '—' : number_format($repeatRate, 1) . '%',
                'info'  => 'Share of buyers who have submitted two or more orders in their lifetime. A buyer is a guest with at least one submitted order; cancellation afterwards does not remove them, because the order was still placed.',
                'sub'   => number_format($repeatBuyers) . ' of ' . number_format($buyers) . ' buyers',
            ])

            @include('stats.partials.kpi-tile', [
                'label' => 'Time to First Purchase',
                'value' => $medianDaysToFirstOrder === null ? '—' : number_format($medianDaysToFirstOrder, 1) . ' d',
                'info'  => 'Median days between a guest signing up and submitting their first order. Median, not mean, so one account that ordered a year late does not move the number. Guests who have never ordered are not in it at all — they have no elapsed time yet, only an open question.',
                'sub'   => 'Median, buyers only',
            ])
        </div>

        {{-- ── 1 + 2 · Signups and activation ─────────────────────────────── --}}
        @include('stats.partials.chart-card', [
            'title'    => 'Signups and Activation',
            'subtitle' => 'New guest accounts per month, and how many guests activated (first order or first paid top-up) in that month.',
            'info'     => 'Two separate populations on one axis: the signup line counts accounts created that month, the activation line counts guests whose FIRST paid action fell that month — whenever they signed up. A gap that widens is registration outrunning activation.',
            'chartId'  => 'signupChart',
        ])

        {{-- ── 3 · Cohort conversion ──────────────────────────────────────── --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold uppercase tracking-wide text-slate-900">
                Signup → First-Order Conversion
                <x-ds.info-tip
                    label="How is signup to first-order conversion calculated?"
                    text="Guests are grouped by the month they signed up. A cohort member converts if their first submitted order lands within 30 days of signing up. The rate is converted ÷ cohort size — so it measures the cohort, not the month's traffic." />
            </h2>
            <p class="mt-1 text-sm text-slate-600">
                Share of each signup cohort that submitted a first order within
                <strong>30 days</strong>. Funding a wallet does not count here — this one is about reaching checkout.
            </p>

            @if($maturedThroughLabel)
                <p class="mt-3 inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-800">
                    <x-icon name="info" size="sm" class="shrink-0" />
                    Cohorts after <strong>{{ $maturedThroughLabel }}</strong> have not had the full 30 days yet — their rate can still rise.
                </p>
            @endif

            <div id="cohortChart" class="mt-4 h-[340px]"></div>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm" data-sortable>
                    <thead>
                        <tr class="border-b border-slate-200 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="py-2 pr-4 text-left"  data-sort-key data-sort-type="text" data-sort-default>Signup Month <span data-sort-indicator></span></th>
                            <th class="py-2 pr-4 text-right" data-sort-key data-sort-type="number">Signups <span data-sort-indicator></span></th>
                            <th class="py-2 pr-4 text-right" data-sort-key data-sort-type="number">Ordered in 30d <span data-sort-indicator></span></th>
                            <th class="py-2 text-right"      data-sort-key data-sort-type="number">Conversion <span data-sort-indicator></span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cohortRows as $row)
                            <tr class="border-b border-slate-100">
                                <td class="py-2 pr-4 text-left text-slate-700" data-sort-value="{{ $row['key'] }}">
                                    {{ $row['month'] }}
                                    @unless($row['mature'])
                                        <span class="ml-1 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-800">maturing</span>
                                    @endunless
                                </td>
                                <td class="py-2 pr-4 text-right tabular-nums text-slate-900" data-sort-value="{{ $row['size'] }}">{{ number_format($row['size']) }}</td>
                                <td class="py-2 pr-4 text-right tabular-nums text-slate-700" data-sort-value="{{ $row['converted'] }}">{{ number_format($row['converted']) }}</td>
                                <td class="py-2 text-right tabular-nums text-slate-900" data-sort-value="{{ $row['rate'] ?? '' }}">
                                    {{ $row['rate'] === null ? '—' : number_format($row['rate'], 1) . '%' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- ── 4 · Orders submitted ───────────────────────────────────────── --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold uppercase tracking-wide text-slate-900">
                Orders Submitted
                <x-ds.info-tip
                    label="How are orders submitted calculated?"
                    text="Guest orders counted by their submitted_at month. An order that was cancelled later STAYS in the submitted total and is reported on its own line instead — netting it out would silently rewrite a past month every time an old order is cancelled." />
            </h2>
            <p class="mt-1 text-sm text-slate-600">
                {{ number_format($totalOrders) }} orders submitted all time, of which
                <strong>{{ number_format($totalCancelled) }}</strong> were cancelled afterwards.
            </p>
            <div id="ordersChart" class="mt-4 h-[360px]"></div>
        </section>

        {{-- ── 5 · Active buyers ──────────────────────────────────────────── --}}
        @include('stats.partials.chart-card', [
            'title'    => 'Active Buyers per Month',
            'subtitle' => 'Distinct guests who submitted an order or moved tokens in the month.',
            'info'     => 'Order submissions and token-ledger movements are UNIONED per month, not added: spending tokens on an order writes both, so adding the two sources would count the same buyer twice.',
            'chartId'  => 'activeBuyersChart',
        ])

        {{-- ── 6 + 7 · Repeat buyers and open carts ───────────────────────── --}}
        <div class="grid grid-cols-1 gap-6 2xl:grid-cols-2">
            @include('stats.partials.pie-table', [
                'title'       => 'Orders per Buyer',
                'subtitle'    => 'How many submitted orders each buyer has placed, lifetime.',
                'info'        => 'Every guest with at least one submitted order, bucketed by their lifetime order count. Buckets with nobody in them are kept at zero so the shape stays comparable between visits.',
                'chartId'     => 'orderCountChart',
                'itemHeader'  => 'Orders placed',
                'countHeader' => 'Buyers',
                'labels'      => $orderCountChart['labels'],
                'series'      => $orderCountChart['series'],
            ])

            @include('stats.partials.pie-table', [
                'title'       => 'Open Carts by Age',
                'subtitle'    => 'Draft orders holding at least one item, aged from when they were last touched.',
                'info'        => 'A draft order IS the cart — there is no cart table, and a user has one at a time. Empty drafts are excluded: an empty cart is not an abandoned one. Age is measured from the last update, not from creation, so a cart edited this morning reads as fresh.',
                'chartId'     => 'cartAgeChart',
                'itemHeader'  => 'Age',
                'countHeader' => 'Carts',
                'labels'      => $openCartAgeChart['labels'],
                'series'      => $openCartAgeChart['series'],
            ])
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
            @include('stats.partials.kpi-tile', [
                'label' => 'Open Carts',
                'value' => number_format($openCartCount),
                'info'  => 'Draft orders holding at least one item, right now. This is a snapshot of today — it is not dated, because a cart has no event date until it is submitted.',
                'sub'   => number_format($openCartItems) . ' items waiting',
            ])

            @include('stats.partials.kpi-tile', [
                'label' => 'Median Cart Age',
                'value' => $openCartMedianAge === null ? '—' : number_format($openCartMedianAge, 1) . ' d',
                'info'  => 'Median days since an open cart was last touched. Carts with no recorded update timestamp stay in the cart COUNT but are left out of the age statistics, rather than being dropped from both.',
                'sub'   => 'Since last change',
            ])

            @include('stats.partials.kpi-tile', [
                'label' => 'Abandoned Carts',
                'value' => $openCartIdleRate === null ? '—' : number_format($openCartIdleRate, 1) . '%',
                'info'  => 'Share of open carts untouched for more than 7 days. The denominator is the carts that can be aged, not every open cart.',
                'sub'   => number_format($openCartIdleCount) . ' idle > 7 days',
            ])

            @include('stats.partials.kpi-tile', [
                'label' => 'Favorites → Orders',
                'value' => $favoriteConversionRate === null ? '—' : number_format($favoriteConversionRate, 1) . '%',
                'info'  => 'Share of favorited domains the same guest LATER ordered — the order item must have been created after the favorite, and its order must have been submitted. Two known undercounts, both honest: un-favoriting after ordering deletes the row, and a favorite added this week has had no time to convert.',
                'sub'   => number_format($favoriteConverted) . ' of ' . number_format($favoriteTotal) . ' favorites',
            ])
        </div>

        {{-- ── 8 + 9 · Time to first purchase, favorites ──────────────────── --}}
        <div class="grid grid-cols-1 gap-6 2xl:grid-cols-2">
            @include('stats.partials.chart-card', [
                'title'    => 'Time to First Purchase',
                'subtitle' => 'Median days from signup to first order, by the month that first order landed.',
                'info'     => 'Each point is the median over the guests whose FIRST order fell in that month. A month where nobody bought for the first time breaks the line rather than dropping to zero days, which would read as instant conversion.',
                'chartId'  => 'timeToFirstChart',
            ])

            @include('stats.partials.chart-card', [
                'title'    => 'Favorites Added',
                'subtitle' => 'Domains favorited by guests per month.',
                'info'     => 'Rows in user_favorite_domains counted by their created_at. Un-favoriting DELETES the row, so this is favorites that still stand, not everything ever added.',
                'chartId'  => 'favoritesChart',
            ])
        </div>

        <p class="text-sm text-slate-600">
            Top-of-funnel engagement (domain views, searches, view→cart rate) is not on this page:
            no page-view or search event is recorded anywhere in the app. It stays parked until these
            pages show the instrumentation is worth building.
        </p>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    @include('stats.partials.sortable-table-script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof ApexCharts === 'undefined') return;

            const months = @json($months);
            const int = (v) => (v === null ? '—' : Number(v).toLocaleString());
            const days = (v) => (v === null ? '—' : Number(v).toFixed(1) + ' d');
            const pct  = (v) => (v === null ? '—' : Number(v).toFixed(1) + '%');

            const mount = (id, options) => {
                const node = document.querySelector(id);
                if (! node) return;
                new ApexCharts(node, options).render();
            };

            const base = (extra) => Object.assign({
                chart:   { height: '100%', toolbar: { show: false }, fontFamily: 'inherit' },
                xaxis:   { categories: months },
                grid:    { borderColor: '#e2e8f0' },
                dataLabels: { enabled: false },
                legend:  { position: 'top', horizontalAlign: 'left' },
                stroke:  { curve: 'smooth', width: 3 },
            }, extra);

            // 1 + 2 — signups vs activation
            mount('#signupChart', base({
                chart:  { type: 'line', height: '100%', toolbar: { show: false }, fontFamily: 'inherit' },
                colors: ['#0ea5e9', '#10b981'],
                series: [
                    { name: 'New guest signups', data: @json($signupSeries) },
                    { name: 'Activated buyers',  data: @json($activationSeries) },
                ],
                markers: { size: 4 },
                yaxis: [
                    { seriesName: 'New guest signups', title: { text: 'Signups' },
                      labels: { formatter: int } },
                    { seriesName: 'Activated buyers', opposite: true,
                      title: { text: 'Activated buyers' }, labels: { formatter: int } },
                ],
                tooltip: { shared: true, intersect: false, y: { formatter: int } },
            }));

            // 3 — cohort conversion. Immature cohorts are drawn slate, not green:
            // their rate can still rise, so they must not read as a drop.
            const cohortMature = @json(array_column($cohortRows, 'mature'));
            mount('#cohortChart', base({
                chart:  { type: 'bar', height: '100%', toolbar: { show: false }, fontFamily: 'inherit' },
                colors: [function ({ dataPointIndex }) {
                    return cohortMature[dataPointIndex] ? '#10b981' : '#cbd5e1';
                }],
                series: [{ name: 'Converted within 30 days', data: @json($cohortRateSeries) }],
                plotOptions: { bar: { borderRadius: 4, columnWidth: '60%' } },
                legend: { show: false },
                yaxis:  { title: { text: 'Conversion' }, labels: { formatter: pct } },
                tooltip: { y: { formatter: pct } },
            }));

            // 4 — orders submitted, with later-cancelled as its own line
            mount('#ordersChart', base({
                chart:  { type: 'line', height: '100%', toolbar: { show: false }, fontFamily: 'inherit' },
                colors: ['#6366f1', '#ef4444'],
                series: [
                    { name: 'Submitted',       type: 'column', data: @json($ordersSeries) },
                    { name: 'Later cancelled', type: 'line',   data: @json($cancelledSeries) },
                ],
                plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
                stroke: { curve: 'smooth', width: [0, 3] },
                yaxis:  { title: { text: 'Orders' }, labels: { formatter: int } },
                tooltip: { shared: true, intersect: false, y: { formatter: int } },
            }));

            // 5 — active buyers
            mount('#activeBuyersChart', base({
                chart:  { type: 'bar', height: '100%', toolbar: { show: false }, fontFamily: 'inherit' },
                colors: ['#8b5cf6'],
                series: [{ name: 'Active buyers', data: @json($activeBuyerSeries) }],
                plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
                legend: { show: false },
                yaxis:  { title: { text: 'Guests' }, labels: { formatter: int } },
                tooltip: { y: { formatter: int } },
            }));

            // 8 — median days to first purchase
            mount('#timeToFirstChart', base({
                chart:  { type: 'line', height: '100%', toolbar: { show: false }, fontFamily: 'inherit' },
                colors: ['#f59e0b'],
                series: [{ name: 'Median days to first order', data: @json($medianDaysSeries) }],
                markers: { size: 5 },
                legend: { show: false },
                yaxis:  { title: { text: 'Days' }, labels: { formatter: days } },
                tooltip: { y: { formatter: days } },
            }));

            // 9 — favorites added
            mount('#favoritesChart', base({
                chart:  { type: 'bar', height: '100%', toolbar: { show: false }, fontFamily: 'inherit' },
                colors: ['#0ea5e9'],
                series: [{ name: 'Favorites added', data: @json($favoriteSeries) }],
                plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
                legend: { show: false },
                yaxis:  { title: { text: 'Favorites' }, labels: { formatter: int } },
                tooltip: { y: { formatter: int } },
            }));

            // 6 + 7 — the two pie widgets, matching the Publisher Stats convention:
            // slice numbers live in the table beside each chart, not on the slice.
            const pie = (selector, labels, series, colors) => {
                const node = document.querySelector(selector);
                if (! node) return;
                const opts = {
                    chart:      { type: 'pie', height: 340, fontFamily: 'inherit' },
                    labels:     labels,
                    series:     series,
                    legend:     { position: 'bottom' },
                    dataLabels: { enabled: false },
                    tooltip:    { y: { formatter: int } },
                };
                if (colors) opts.colors = colors;
                new ApexCharts(node, opts).render();
            };

            pie('#orderCountChart', @json($orderCountChart['labels']), @json($orderCountChart['series']),
                ['#cbd5e1', '#a5b4fc', '#818cf8', '#6366f1', '#4338ca']);
            pie('#cartAgeChart', @json($openCartAgeChart['labels']), @json($openCartAgeChart['series']),
                ['#10b981', '#84cc16', '#f59e0b', '#ef4444']);
        });
    </script>
@endpush
