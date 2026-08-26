@extends('layouts.dashboard')

@section('title', 'Marketplace Revenue & Tokens Statistics')

@section('subnav')
    @include('layouts.partials.stats-sidebar')
@endsection

@section('content')
    <div class="mx-auto max-w-7xl space-y-6 py-2">

        <header>
            <h1 class="text-2xl font-bold text-slate-900">Marketplace · Revenue &amp; Tokens</h1>
            <p class="mt-1 max-w-3xl text-sm text-slate-600">
                What the marketplace earns, what it owes, and how orders move once placed —
                <strong>guest accounts only</strong>.
                One token is one euro, permanently, so token figures are euro-denominated by construction.
                Cash is per-currency and is never summed across currencies.
            </p>
        </header>

        {{-- ── Headline tiles ─────────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
            @include('stats.partials.kpi-tile', [
                'label' => 'Tokens Sold',
                'value' => number_format($totalTokensSold),
                'info'  => 'Base tokens on every PAID top-up, all time. Bonus tokens are excluded here and reported on their own — they are the cost of the discount policy, not revenue. Because one token is one euro, this figure is directly comparable across every currency we charge in.',
                'sub'   => 'Base tokens, all currencies',
            ])

            @include('stats.partials.kpi-tile', [
                'label' => 'Outstanding Liability',
                'value' => number_format($liabilityDerived) . ' tk',
                'info'  => 'Prepaid credit guests have paid for and not yet spent, derived from the ledger — the authoritative figure. At one token per euro this is also the euro amount owed.',
                'sub'   => 'EUR ' . number_format($liabilityDerived, 2, '.', ',') . ' owed to buyers',
            ])

            @include('stats.partials.kpi-tile', [
                'label' => 'Payment Success Rate',
                'value' => $paymentSuccessRate === null ? '—' : number_format($paymentSuccessRate, 1) . '%',
                'info'  => 'Paid ÷ (paid + failed + expired) top-up attempts. Pending attempts are still undecided and refunded ones SUCCEEDED before being reversed, so neither sits on either side of this rate — both appear in the attempts table instead.',
                'sub'   => 'Decided attempts only',
            ])

            @include('stats.partials.kpi-tile', [
                'label' => 'Average Order Value',
                'value' => $averageOrderValue === null ? '—' : 'EUR ' . number_format($averageOrderValue, 2, '.', ','),
                'info'  => 'Mean value of a submitted guest order. Orders carry no total column, so an order is worth the sum of its item prices; each item is one website, so quantity is always one.',
                'sub'   => number_format($submittedOrderCount) . ' submitted orders',
            ])
        </div>

        {{-- Ledger drift — only shown when the cache and the ledger disagree. --}}
        @if($liabilityDrift !== 0)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <p class="font-semibold">Cached balances have drifted from the ledger.</p>
                <p class="mt-1">
                    Cached total <strong class="tabular-nums">{{ number_format($liabilityCached) }} tk</strong>,
                    ledger total <strong class="tabular-nums">{{ number_format($liabilityDerived) }} tk</strong>,
                    difference <strong class="tabular-nums">{{ number_format($liabilityDrift) }} tk</strong>.
                    The ledger is the truth; <code>balance_cached</code> is only a cache. Every figure on this page uses the ledger.
                </p>
            </div>
        @endif

        {{-- ── 1 · Token revenue ──────────────────────────────────────────── --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold uppercase tracking-wide text-slate-900">
                Token Revenue
                <x-ds.info-tip
                    label="How is token revenue calculated?"
                    text="Cash actually received on PAID top-ups, dated by paid_at, with one series per currency charged. The currencies are never added together — a euro and a dollar are different units, and a combined total would be a number with no meaning. The tokens-sold line beside them IS comparable across all of them, because the peg fixes one token at one euro." />
            </h2>
            <p class="mt-1 text-sm text-slate-600">
                Paid top-ups per month, split by the currency charged. Dated by <strong>payment date</strong>.
            </p>

            @if($currencies)
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach($currencies as $currency)
                        <span class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-700">
                            {{ $currency }} total
                            <strong class="tabular-nums text-slate-900">{{ number_format($revenueTotals[$currency] ?? 0, 2, '.', ',') }}</strong>
                        </span>
                    @endforeach
                </div>
            @endif

            <div id="revenueChart" class="mt-4 h-[380px]"></div>
        </section>

        {{-- ── 2 + 3 · Ledger flow and liability ──────────────────────────── --}}
        <div class="grid grid-cols-1 gap-6 2xl:grid-cols-2">
            @include('stats.partials.chart-card', [
                'title'    => 'Tokens Purchased vs Spent',
                'subtitle' => 'Credits arriving (purchase + bonus) against tokens spent, per month.',
                'info'     => 'Signed sums straight from the ledger: credits are purchase and bonus rows, spend is reported as a positive bar although it is stored negative. Credits persistently outrunning spend means credit is being parked rather than used — which is exactly what the liability line beside it turns into money owed.',
                'chartId'  => 'ledgerChart',
            ])

            @include('stats.partials.chart-card', [
                'title'    => 'Outstanding Token Liability',
                'subtitle' => 'Prepaid credit owed to guests at the end of each month.',
                'info'     => 'The ledger records movements, not balances, so each point is the opening balance plus every movement since. The opening balance carries the history from before this chart starts — without it the line would begin at zero and understate what is owed for the whole period.',
                'chartId'  => 'liabilityChart',
            ])
        </div>

        {{-- ── 4 · Package mix ────────────────────────────────────────────── --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold uppercase tracking-wide text-slate-900">
                Package Mix
                <x-ds.info-tip
                    label="How is package mix calculated?"
                    text="Paid top-ups grouped by the package bought. Share is by TOKENS, not cash, because tokens are one unit across every currency while cash is not. Cash is still shown, split per currency, in its own columns." />
            </h2>
            <p class="mt-1 text-sm text-slate-600">
                Which packages guests actually buy. Ranked by tokens sold.
            </p>

            <div class="mt-4 grid grid-cols-1 gap-6 2xl:grid-cols-2 2xl:items-center">
                <div id="packageChart" class="mx-auto w-full max-w-md"></div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm" data-sortable>
                        <thead>
                            <tr class="border-b border-slate-200 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <th class="py-2 pr-4 text-left"  data-sort-key data-sort-type="text">Package <span data-sort-indicator></span></th>
                                <th class="py-2 pr-4 text-right" data-sort-key data-sort-type="number">Purchases <span data-sort-indicator></span></th>
                                <th class="py-2 pr-4 text-right" data-sort-key data-sort-type="number" data-sort-default>Tokens <span data-sort-indicator></span></th>
                                <th class="py-2 pr-4 text-right" data-sort-key data-sort-type="number">Bonus <span data-sort-indicator></span></th>
                                <th class="py-2 pr-4 text-right" data-sort-key data-sort-type="number">Share <span data-sort-indicator></span></th>
                                @foreach($currencies as $currency)
                                    <th class="py-2 pr-4 text-right" data-sort-key data-sort-type="number">{{ $currency }} <span data-sort-indicator></span></th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($packageRows as $row)
                                <tr class="border-b border-slate-100">
                                    <td class="py-2 pr-4 text-left text-slate-700" data-sort-value="{{ $row['label'] }}">{{ $row['label'] }}</td>
                                    <td class="py-2 pr-4 text-right tabular-nums text-slate-900" data-sort-value="{{ $row['count'] }}">{{ number_format($row['count']) }}</td>
                                    <td class="py-2 pr-4 text-right tabular-nums text-slate-900" data-sort-value="{{ $row['tokens'] }}">{{ number_format($row['tokens']) }}</td>
                                    <td class="py-2 pr-4 text-right tabular-nums text-slate-700" data-sort-value="{{ $row['bonus'] }}">{{ number_format($row['bonus']) }}</td>
                                    <td class="py-2 pr-4 text-right tabular-nums text-slate-700" data-sort-value="{{ $row['share'] ?? '' }}">
                                        {{ $row['share'] === null ? '—' : number_format($row['share'], 1) . '%' }}
                                    </td>
                                    @foreach($currencies as $currency)
                                        <td class="py-2 pr-4 text-right tabular-nums text-slate-700" data-sort-value="{{ $row['revenue'][$currency] ?? '' }}">
                                            {{ isset($row['revenue'][$currency]) ? number_format($row['revenue'][$currency], 2, '.', ',') : '—' }}
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 5 + count($currencies) }}" class="py-6 text-center text-sm text-slate-600">
                                        No paid top-ups yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        {{-- ── 5 + 6 · Order value and revenue per buyer ──────────────────── --}}
        <div class="grid grid-cols-1 gap-6 2xl:grid-cols-2">
            @include('stats.partials.chart-card', [
                'title'    => 'Average Order Value',
                'subtitle' => 'Mean value of a submitted guest order, by submission month.',
                'info'     => 'An order is worth the sum of its item prices — orders carry no total column, and each item is one website, so quantity is always one. A month with no submitted order breaks the line rather than dropping to zero euro.',
                'chartId'  => 'aovChart',
            ])

            @include('stats.partials.chart-card', [
                'title'    => 'Revenue per Paying Buyer',
                'subtitle' => 'Paid top-up value divided by the distinct guests who paid that month, per currency.',
                'info'     => 'One line per currency, because the numerator is per-currency cash. A guest who paid in two currencies counts in BOTH denominators — the alternative, one shared headcount, would divide euro revenue by people who never paid a euro.',
                'chartId'  => 'arppuChart',
            ])
        </div>

        {{-- ── 5b · Article-type price split ──────────────────────────────── --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold uppercase tracking-wide text-slate-900">
                Order Items by Article Type
                <x-ds.info-tip
                    label="How is the article-type split calculated?"
                    text="Items on submitted guest orders, grouped by article type. The split is PER ITEM and not per order, deliberately: one order can hold both standard and sensitive placements, so there is no such thing as a sensitive order to average." />
            </h2>
            <p class="mt-1 text-sm text-slate-600">
                Standard against sensitive placements on submitted orders. Amounts in EUR.
            </p>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm" data-sortable>
                    <thead>
                        <tr class="border-b border-slate-200 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="py-2 pr-4 text-left"  data-sort-key data-sort-type="text">Article Type <span data-sort-indicator></span></th>
                            <th class="py-2 pr-4 text-right" data-sort-key data-sort-type="number" data-sort-default>Items <span data-sort-indicator></span></th>
                            <th class="py-2 pr-4 text-right" data-sort-key data-sort-type="number">Share <span data-sort-indicator></span></th>
                            <th class="py-2 pr-4 text-right" data-sort-key data-sort-type="number">Average Price <span data-sort-indicator></span></th>
                            <th class="py-2 text-right"      data-sort-key data-sort-type="number">Total <span data-sort-indicator></span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($articleTypeRows as $row)
                            <tr class="border-b border-slate-100">
                                <td class="py-2 pr-4 text-left text-slate-700" data-sort-value="{{ $row['label'] }}">{{ $row['label'] }}</td>
                                <td class="py-2 pr-4 text-right tabular-nums text-slate-900" data-sort-value="{{ $row['items'] }}">{{ number_format($row['items']) }}</td>
                                <td class="py-2 pr-4 text-right tabular-nums text-slate-700" data-sort-value="{{ $row['share'] ?? '' }}">
                                    {{ $row['share'] === null ? '—' : number_format($row['share'], 1) . '%' }}
                                </td>
                                <td class="py-2 pr-4 text-right tabular-nums text-slate-700" data-sort-value="{{ $row['average'] ?? '' }}">
                                    {{ $row['average'] === null ? '—' : 'EUR ' . number_format($row['average'], 2, '.', ',') }}
                                </td>
                                <td class="py-2 text-right tabular-nums text-slate-900" data-sort-value="{{ $row['total'] }}">
                                    EUR {{ number_format($row['total'], 2, '.', ',') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-sm text-slate-600">No submitted order items yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- ── 7 · Payment success ────────────────────────────────────────── --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold uppercase tracking-wide text-slate-900">
                Payment Success Rate
                <x-ds.info-tip
                    label="How is payment success rate calculated?"
                    text="Top-up ATTEMPTS are dated by creation, not by paid_at: a failed or expired attempt never gets a paid_at, so dating by it would silently drop the very rows this rate exists to measure. Each month therefore reports on the attempts started in it." />
            </h2>
            <p class="mt-1 text-sm text-slate-600">
                Share of decided top-up attempts that were paid, by the month the attempt started.
            </p>

            <div class="mt-4 grid grid-cols-1 gap-6 2xl:grid-cols-2 2xl:items-center">
                <div id="paymentChart" class="h-[320px]"></div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm" data-sortable>
                        <thead>
                            <tr class="border-b border-slate-200 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <th class="py-2 pr-4 text-left"  data-sort-key data-sort-type="text">Attempt Status <span data-sort-indicator></span></th>
                                <th class="py-2 pr-4 text-right" data-sort-key data-sort-type="number" data-sort-default>Attempts <span data-sort-indicator></span></th>
                                <th class="py-2 text-right"      data-sort-key data-sort-type="number">Share <span data-sort-indicator></span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($paymentAttemptRows as $row)
                                <tr class="border-b border-slate-100">
                                    <td class="py-2 pr-4 text-left text-slate-700" data-sort-value="{{ $row['label'] }}">{{ $row['label'] }}</td>
                                    <td class="py-2 pr-4 text-right tabular-nums text-slate-900" data-sort-value="{{ $row['count'] }}">{{ number_format($row['count']) }}</td>
                                    <td class="py-2 text-right tabular-nums text-slate-700" data-sort-value="{{ $row['share'] ?? '' }}">
                                        {{ $row['share'] === null ? '—' : number_format($row['share'], 1) . '%' }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="py-6 text-center text-sm text-slate-600">No top-up attempts yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        {{-- ── 8 + 9 · Refunds, adjustments, bonus ────────────────────────── --}}
        <div class="grid grid-cols-1 gap-6 2xl:grid-cols-2">
            @include('stats.partials.chart-card', [
                'title'    => 'Refunds and Adjustments',
                'subtitle' => 'Ledger refund and adjustment volume per month, in tokens.',
                'info'     => 'Dated by the LEDGER row, not by the purchase: a refund writes its own immutable row with its own timestamp, whereas updated_at on the purchase moves on any later write. Refunds are shown as a positive magnitude; adjustments keep their sign, since a correction can go either way.',
                'chartId'  => 'refundChart',
            ])

            @include('stats.partials.chart-card', [
                'title'    => 'Bonus Tokens Granted',
                'subtitle' => 'Free tokens handed out with paid packages, per month.',
                'info'     => 'Bonus tokens on paid top-ups, dated by payment. This is what the discount policy costs: every one of them is a euro of liability created without a euro of cash arriving.',
                'chartId'  => 'bonusChart',
            ])
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
            @include('stats.partials.kpi-tile', [
                'label' => 'Refunded Purchases',
                'value' => number_format($refundedPurchaseCount),
                'info'  => 'Top-ups whose status is refunded, all time. These succeeded before being reversed, which is why they sit outside the payment-success rate rather than counting against it.',
                'sub'   => number_format($totalRefundTokens) . ' tokens refunded on the ledger',
            ])

            @include('stats.partials.kpi-tile', [
                'label' => 'Manual Adjustments',
                'value' => number_format($totalAdjustmentTokens) . ' tk',
                'info'  => 'Net of every adjustment row on the ledger. The ledger is append-only, so a correction is a new compensating row rather than an edit — this is the running total of those corrections, and it can be negative.',
                'sub'   => 'Net, all time',
            ])

            @include('stats.partials.kpi-tile', [
                'label' => 'Bonus Tokens Granted',
                'value' => number_format($totalBonusTokens) . ' tk',
                'info'  => 'All bonus tokens on paid top-ups. At one token per euro this is the euro value given away as discount.',
                'sub'   => 'EUR ' . number_format($totalBonusTokens, 2, '.', ',') . ' of discount',
            ])

            @include('stats.partials.kpi-tile', [
                'label' => 'Median Fulfilment Time',
                'value' => $medianFulfilmentDays === null ? '—' : number_format($medianFulfilmentDays, 1) . ' d',
                'info'  => 'Median days from submission to completion, over orders that are currently completed. Orders store only their LAST status change, so no per-stage history exists — but for an order sitting in completed, that last change IS the completion.',
                'sub'   => number_format($measuredCompletions) . ' completed orders measured',
            ])
        </div>

        {{-- ── 10 · Pipeline ──────────────────────────────────────────────── --}}
        @include('stats.partials.pie-table', [
            'title'       => 'Order Pipeline',
            'subtitle'    => 'Where every guest order stands right now.',
            'info'        => 'All guest orders by their current status — a snapshot of today, not a dated series. Statuses the orders column can hold but that the app does not currently write are included when present, so this always adds up to the total number of guest orders.',
            'chartId'     => 'pipelineChart',
            'itemHeader'  => 'Status',
            'countHeader' => 'Orders',
            'labels'      => $pipelineChart['labels'],
            'series'      => $pipelineChart['series'],
        ])
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    @include('stats.partials.sortable-table-script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof ApexCharts === 'undefined') return;

            const months = @json($months);
            const int   = (v) => (v === null ? '—' : Number(v).toLocaleString());
            const tk    = (v) => (v === null ? '—' : Number(v).toLocaleString() + ' tk');
            const money = (v) => (v === null ? '—' : Number(v).toLocaleString(undefined, {
                minimumFractionDigits: 2, maximumFractionDigits: 2,
            }));
            const pct   = (v) => (v === null ? '—' : Number(v).toFixed(1) + '%');

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

            // Distinct hue per currency, assigned in the same order the server
            // ranked them, so a currency keeps its colour across every widget.
            const currencyColors = ['#0ea5e9', '#f59e0b', '#8b5cf6', '#10b981'];

            // 1 — token revenue: one column series per currency, never combined.
            mount('#revenueChart', base({
                chart:  { type: 'bar', height: '100%', toolbar: { show: false }, fontFamily: 'inherit' },
                colors: currencyColors,
                series: @json($revenueSeries),
                plotOptions: { bar: { borderRadius: 4, columnWidth: '60%' } },
                yaxis:  { title: { text: 'Cash received' }, labels: { formatter: money } },
                tooltip: { shared: true, intersect: false, y: { formatter: money } },
            }));

            // 2 — credits vs debits
            mount('#ledgerChart', base({
                chart:  { type: 'bar', height: '100%', toolbar: { show: false }, fontFamily: 'inherit' },
                colors: ['#10b981', '#ef4444'],
                series: [
                    { name: 'Tokens purchased', data: @json($creditsSeries) },
                    { name: 'Tokens spent',     data: @json($debitsSeries) },
                ],
                plotOptions: { bar: { borderRadius: 4, columnWidth: '65%' } },
                yaxis:  { title: { text: 'Tokens' }, labels: { formatter: int } },
                tooltip: { shared: true, intersect: false, y: { formatter: tk } },
            }));

            // 3 — liability
            mount('#liabilityChart', base({
                chart:  { type: 'area', height: '100%', toolbar: { show: false }, fontFamily: 'inherit' },
                colors: ['#6366f1'],
                fill:   { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
                series: [{ name: 'Outstanding liability', data: @json($liabilitySeries) }],
                legend: { show: false },
                yaxis:  { title: { text: 'Tokens owed' }, labels: { formatter: int } },
                tooltip: { y: { formatter: tk } },
            }));

            // 5 — average order value
            mount('#aovChart', base({
                chart:  { type: 'line', height: '100%', toolbar: { show: false }, fontFamily: 'inherit' },
                colors: ['#6366f1'],
                series: [{ name: 'Average order value', data: @json($aovSeries) }],
                markers: { size: 5 },
                legend: { show: false },
                yaxis:  { title: { text: 'EUR' }, labels: { formatter: money } },
                tooltip: { y: { formatter: money } },
            }));

            // 6 — revenue per paying buyer, one line per currency
            mount('#arppuChart', base({
                chart:  { type: 'line', height: '100%', toolbar: { show: false }, fontFamily: 'inherit' },
                colors: currencyColors,
                series: @json($arppuSeries),
                markers: { size: 5 },
                yaxis:  { title: { text: 'Per paying buyer' }, labels: { formatter: money } },
                tooltip: { shared: true, intersect: false, y: { formatter: money } },
            }));

            // 7 — payment success rate over time
            mount('#paymentChart', base({
                chart:  { type: 'line', height: '100%', toolbar: { show: false }, fontFamily: 'inherit' },
                colors: ['#10b981'],
                series: [{ name: 'Payment success rate', data: @json($paymentSuccessSeries) }],
                markers: { size: 5 },
                legend: { show: false },
                yaxis:  { min: 0, max: 100, title: { text: 'Success rate' }, labels: { formatter: pct } },
                tooltip: { y: { formatter: pct } },
            }));

            // 8 — refunds and adjustments
            mount('#refundChart', base({
                chart:  { type: 'bar', height: '100%', toolbar: { show: false }, fontFamily: 'inherit' },
                colors: ['#ef4444', '#64748b'],
                series: [
                    { name: 'Refunds',     data: @json($refundSeries) },
                    { name: 'Adjustments', data: @json($adjustmentSeries) },
                ],
                plotOptions: { bar: { borderRadius: 4, columnWidth: '65%' } },
                yaxis:  { title: { text: 'Tokens' }, labels: { formatter: int } },
                tooltip: { shared: true, intersect: false, y: { formatter: tk } },
            }));

            // 9 — bonus tokens
            mount('#bonusChart', base({
                chart:  { type: 'bar', height: '100%', toolbar: { show: false }, fontFamily: 'inherit' },
                colors: ['#f59e0b'],
                series: [{ name: 'Bonus tokens granted', data: @json($bonusSeries) }],
                plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
                legend: { show: false },
                yaxis:  { title: { text: 'Tokens' }, labels: { formatter: int } },
                tooltip: { y: { formatter: tk } },
            }));

            // 4 + 10 — pies. Slice numbers live in the table beside each chart,
            // matching the Publisher Stats convention.
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

            pie('#packageChart', @json($packageChart['labels']), @json($packageChart['series']));
            pie('#pipelineChart', @json($pipelineChart['labels']), @json($pipelineChart['series']));
        });
    </script>
@endpush
