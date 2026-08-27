{{--
    Token wallet.

    Built from the app's design-system components (x-ds.*) rather than
    hand-rolled markup, so spacing, borders, type scale and tones match every
    other page.

    ONE currency control governs the page (top right). It picks the currency you
    PAY in, so it drives every card price and rides along to checkout in a hidden
    field. Cards therefore carry no per-card currency select — two independent
    currency controls on one screen is exactly the confusion this replaced.

    The BALANCE deliberately does not convert. A token is EUR 1 by definition, so
    the euro figure IS the balance; an FX-converted figure beside an exact,
    published card price reads as a contradiction (it is not one — the published
    price carries a cross-currency margin — but nobody should have to know that).
--}}
<x-marketplace-layout>
    <x-slot name="title">Tokens</x-slot>

    <x-slot name="pageHeader">
        <x-ds.page-header title="Tokens" subtitle="Prepaid tokens for guest post orders. 1 token = €1.">
            <x-slot name="actions">
                @if($isFakeGateway)
                    <x-ds.pill tone="amber">Test mode — no real payment</x-ds.pill>
                @endif
                <x-ds.button :href="route('websites.index')" variant="secondary" size="md">
                    <x-icon name="search" size="sm" /> Browse domains
                </x-ds.button>
            </x-slot>
        </x-ds.page-header>
    </x-slot>

    @php
        // The chosen currency must be one we can actually CHARGE in, because it
        // is what the Buy forms submit. ?display= is validated upstream against
        // display_currencies, which is a wider set (it carries GBP, which has no
        // price and no rate) — so re-narrow it to payment_currencies here rather
        // than letting a bookmarked URL reach checkout with an unchargeable code.
        $currency = in_array($display, $paymentCurrencies, true) ? $display : 'EUR';
        $otherCurrencies = array_values(array_diff($paymentCurrencies, [$currency]));

        $symbols = ['EUR' => '€', 'USD' => '$', 'GBP' => '£'];
        $sym = fn (string $c) => $symbols[$c] ?? ($c.' ');
        $money = fn (string $c, int $minor) => $sym($c).number_format($minor / 100, 2);
    @endphp

    <div class="px-6 py-5 space-y-6">

        @if(session('status'))
            <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif

        {{-- ─────────── Balance ─────────── --}}
        <section aria-labelledby="balanceHeading"
                 data-card="static"
                 class="bg-white rounded-xl border border-gray-200 shadow-card p-7">
            <div class="flex items-start justify-between flex-wrap gap-x-8 gap-y-5">
                <div>
                    <h2 id="balanceHeading" class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Your balance</h2>

                    <div class="flex items-baseline gap-2.5 mt-3">
                        <span class="text-4xl font-bold text-gray-800 leading-none tabular-nums">{{ number_format($balance) }}</span>
                        <span class="text-sm font-semibold text-gray-500">tokens</span>
                    </div>

                    {{-- The peg makes this the same number again, so it reads as an
                         EQUIVALENCE rather than a second balance competing with the
                         figure above. --}}
                    <p class="text-sm text-gray-600 mt-2.5 tabular-nums">= €{{ number_format($balance, 2) }}</p>
                </div>

                {{-- The page's single currency control. A segmented toggle rather
                     than a select: with two options, hiding one behind a click buys
                     nothing, and this has to read as a page-level switch. Neutral
                     gray on purpose — it is a state control, not a CTA, and must
                     not compete with the one solid Buy button below. --}}
                <div class="flex items-center gap-3 flex-shrink-0">
                    <span id="currencyLabel" class="text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Currency</span>
                    <div role="group" aria-labelledby="currencyLabel" class="inline-flex items-center gap-1 rounded-lg bg-gray-100 p-1">
                        @foreach($paymentCurrencies as $c)
                            <a href="{{ request()->fullUrlWithQuery(['display' => $c]) }}"
                               @if($c === $currency) aria-current="true" @endif
                               class="px-3.5 py-1.5 rounded-md text-sm font-semibold transition-colors
                                      {{ $c === $currency
                                          ? 'bg-white text-gray-900 shadow-sm'
                                          : 'text-gray-600 hover:text-gray-900' }}">
                                {{ $c }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <p class="text-sm text-gray-600 leading-relaxed mt-7 pt-5 border-t border-gray-100">
                Orders are charged in tokens at €1 each, whatever currency you pay in.
            </p>
        </section>

        {{-- ─────────── Packages ─────────── --}}
        @php
            // The badge always sits on the arithmetically best tier, derived from the
            // prices on screen rather than hardcoded — so it cannot go stale or become
            // a false claim if the packages are re-priced. Guarded: with no bonus
            // anywhere there is no "best value" to claim, so nothing is badged.
            $bonusRates = collect($packages)->map(fn ($p) => ($p['bonus'] ?? 0) / max(1, $p['tokens'] ?? 1));
            $recommended = $bonusRates->max() > 0 ? $bonusRates->sortDesc()->keys()->first() : null;

            // Rebuilt here rather than using the controller's $nudgePackages, which
            // is euro-only: the nudge quotes a price, so it has to quote the one the
            // customer would actually be charged in the currency they picked.
            $nudge = collect($packages)
                ->map(fn ($p, $k) => [
                    'label' => ucfirst($k),
                    'total' => ($p['tokens'] ?? 0) + ($p['bonus'] ?? 0),
                    'minor' => $p['prices'][$currency] ?? null,
                ])
                ->filter(fn ($p) => $p['minor'] !== null)
                ->values()
                ->all();
        @endphp

        <section aria-labelledby="packagesHeading">
            <h2 id="packagesHeading" class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Buy tokens</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-stretch">

                @foreach($packages as $key => $package)
                    @php
                        $isRecommended = $key === $recommended;
                        // Every package prices both currencies today. The fallback
                        // exists so a package missing one could never submit an
                        // unchargeable currency and 500 on the way to the gateway.
                        $cardCurrency = isset($package['prices'][$currency]) ? $currency : 'EUR';
                        $cardOthers = array_values(array_diff($paymentCurrencies, [$cardCurrency]));
                    @endphp
                    <form method="POST" action="{{ route('billing.tokens.checkout') }}"
                          data-card
                          class="bg-white rounded-xl shadow-card p-5 flex flex-col transition-colors
                                 {{ $isRecommended
                                     ? 'border-2 border-green-500 hover:border-green-600'
                                     : 'border border-gray-200 hover:border-green-300' }}">
                        @csrf
                        <input type="hidden" name="package" value="{{ $key }}">
                        <input type="hidden" name="currency" value="{{ $cardCurrency }}">

                        {{-- Fixed-height header row on EVERY card, badged or not: the
                             pill is ~24px against a bare label's 16px, so badging one
                             card alone would push its h-11 block down and break the
                             Buy-button alignment the slots below exist to hold. --}}
                        <div class="flex items-center justify-between gap-2 h-6">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ ucfirst($key) }}</p>
                            @if($isRecommended)
                                <x-ds.pill tone="green">Best value</x-ds.pill>
                            @endif
                        </div>

                        {{-- h-11 matches the custom card's input height so all four
                             cards share one vertical rhythm and the Buy buttons line up. --}}
                        <div class="flex items-baseline gap-2 mt-3 h-11">
                            <span class="text-3xl font-bold text-gray-800 leading-none tabular-nums">{{ number_format($package["tokens"]) }}</span>
                            <span class="text-xs font-semibold text-gray-500">tokens</span>
                        </div>

                        <div class="mt-2.5 h-6">
                            @if(($package['bonus'] ?? 0) > 0)
                                <x-ds.pill tone="green">+{{ $package['bonus'] }} bonus</x-ds.pill>
                            @endif
                        </div>

                        {{-- h-12 so the price block occupies the same room on all four
                             cards whether or not a second currency renders — the custom
                             card fills this from JS and would otherwise be empty. --}}
                        <div class="mt-auto pt-5 space-y-3">
                            <div class="h-12 flex flex-col justify-end">
                                <p class="text-xl font-bold text-gray-800 leading-none tabular-nums">{{ $money($cardCurrency, $package['prices'][$cardCurrency]) }}</p>
                                @foreach($cardOthers as $oc)
                                    @if(isset($package['prices'][$oc]))
                                        <p class="text-sm text-gray-500 mt-1.5 tabular-nums">{{ $money($oc, $package['prices'][$oc]) }}</p>
                                    @endif
                                @endforeach
                            </div>

                            {{-- Exactly one solid CTA on the page: the recommended tier.
                                 Every other Buy drops to the outline tier so the page
                                 does not ask "which of these four do I click?". --}}
                            <x-ds.button type="submit"
                                         :variant="$isRecommended ? 'primary' : 'secondary-brand'"
                                         :data-cta="$isRecommended ? 'primary' : 'secondary'"
                                         size="md" block>Buy in {{ $cardCurrency }}</x-ds.button>
                        </div>
                    </form>
                @endforeach

                {{-- Custom amount. Replaced the fixed 2,500 tier: a placement can
                     cost €130, and a €250 floor pushed people into buying more
                     credit than the job needed. --}}
                @php
                    $customCurrency = isset($customMultipliers[$currency]) ? $currency : 'EUR';
                    $customOthers = array_values(array_diff(array_keys($customMultipliers), [$customCurrency]));
                @endphp
                <form method="POST" action="{{ route('billing.tokens.checkout') }}"
                      data-card
                      class="bg-white rounded-xl border-2 border-dashed border-gray-500 shadow-card p-5 flex flex-col
                             hover:border-green-400 transition-colors">
                    @csrf
                    <input type="hidden" name="package" value="custom">
                    <input type="hidden" name="currency" value="{{ $customCurrency }}">

                    <div class="flex items-center justify-between gap-2 h-6">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Custom</p>
                    </div>

                    {{-- The input is a TOKEN count, so its prefix stays € whatever
                         currency is selected: 1 token = €1 is the peg, not a display
                         preference. What you pay in the chosen currency is derived
                         below, from the same published multiplier the server uses. --}}
                    <div class="relative mt-3 h-11">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-2xl font-semibold text-gray-500 leading-none pointer-events-none">€</span>
                        <input type="number" name="tokens" id="customTokens"
                               min="{{ $customMin }}" max="{{ $customMax }}" step="1"
                               value="{{ old('tokens') }}" placeholder="130" required
                               class="fi w-full h-full !pl-11 !pr-3 !py-0 !text-3xl !font-bold !text-gray-800 !leading-none">
                    </div>

                    <div class="mt-2.5 h-6">
                        <span id="customHint" class="text-sm text-gray-600">
                            €{{ number_format($customMin) }}–€{{ number_format($customMax) }}
                        </span>
                    </div>

                    <div class="mt-auto pt-5 space-y-3">
                        <div class="h-12 flex flex-col justify-end">
                            <p id="customPricePrimary" class="text-xl font-bold text-gray-800 leading-none tabular-nums"></p>
                            <p id="customPriceSecondary" class="text-sm text-gray-500 mt-1.5 tabular-nums"></p>
                        </div>

                        <x-ds.button type="submit" variant="secondary-brand" data-cta="secondary" size="md" block>Buy in {{ $customCurrency }}</x-ds.button>
                    </div>
                </form>
            </div>

            @error('tokens')
                <p class="mt-3 text-sm font-medium text-red-600">{{ $message }}</p>
            @enderror

            {{-- A custom amount earns no bonus, so someone typing 500 would
                 silently get 15 fewer tokens than the Standard package for the
                 same money. Say so rather than letting them find out later. --}}
            <p id="packageNudge"
               class="hidden mt-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-700"></p>
        </section>

        {{-- ─────────── Statement ─────────── --}}
        <section aria-labelledby="historyHeading">
            <h2 id="historyHeading" class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">History</h2>

            @if($transactions->isEmpty())
                <div class="bg-white rounded-xl border border-gray-200 shadow-card">
                    <x-ds.empty-state
                        icon="cart"
                        title="No token activity yet"
                        hint="Purchases, bonuses and orders will appear here." />
                </div>
            @else
                {{--
                    data-table="static" — deliberately NOT sortable. The Balance
                    column is a running total, so it is only meaningful in
                    chronological order: sorting by Tokens would leave each row
                    showing a balance that never existed in that sequence. A
                    statement is read as a ledger, not ranked.
                --}}
                <x-ds.table-shell data-table="static">
                    <x-slot name="head">
                        <x-ds.th>Date</x-ds.th>
                        <x-ds.th>Description</x-ds.th>
                        <x-ds.th align="right">Tokens</x-ds.th>
                        <x-ds.th align="right">Balance</x-ds.th>
                    </x-slot>

                    @foreach($transactions as $tx)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-3 text-sm text-gray-500 whitespace-nowrap">
                                {{ optional($tx->created_at)->format('M j, Y') ?? '—' }}
                            </td>
                            <td class="px-3 py-3 text-sm text-gray-700">
                                {{ \App\Http\Controllers\Billing\TokenWalletController::describe($tx) }}
                            </td>
                            <td class="px-3 py-3 text-sm text-right font-semibold whitespace-nowrap tabular-nums
                                       {{ $tx->isCredit() ? 'text-green-700' : 'text-gray-800' }}">
                                {{ $tx->isCredit() ? '+' : '' }}{{ number_format($tx->amount) }}
                            </td>
                            <td class="px-3 py-3 text-sm text-right text-gray-500 whitespace-nowrap tabular-nums">
                                {{ number_format($tx->balance_after) }}
                            </td>
                        </tr>
                    @endforeach
                </x-ds.table-shell>
            @endif
        </section>

    </div>

    @push('scripts')
    <script>
    (function () {
        var input = document.getElementById('customTokens');
        if (!input) { return; }

        var hint = document.getElementById('customHint');
        var nudge = document.getElementById('packageNudge');
        var pricePrimary = document.getElementById('customPricePrimary');
        var priceSecondary = document.getElementById('customPriceSecondary');

        var multipliers = @json($customMultipliers);
        var packages = @json($nudge);
        var symbols = @json($symbols);
        var currency = @json($customCurrency);
        var other = @json($customOthers[0] ?? null);
        var range = hint.textContent.trim();

        function sym(c) { return symbols[c] || (c + ' '); }

        function fmtMinor(c, minor) {
            return sym(c) + (minor / 100).toLocaleString('en-US', {
                minimumFractionDigits: 2, maximumFractionDigits: 2
            });
        }

        // Same formula the server uses in TokenPurchaseService::startCustomPurchase,
        // so the figure shown here is the figure charged — never an approximation.
        function minorFor(c, tokens) {
            var m = multipliers[c];
            return m == null ? null : Math.round(tokens * 100 * m);
        }

        function refresh() {
            var value = parseInt(input.value, 10);

            if (!value || value < 0) {
                hint.textContent = range;
                pricePrimary.textContent = '';
                priceSecondary.textContent = '';
                nudge.classList.add('hidden');
                return;
            }

            // 1 token = €1, so the amount typed IS the token count — say it
            // plainly rather than making the customer infer it.
            hint.textContent = value.toLocaleString() + ' tokens · ' + range;

            var customMinor = minorFor(currency, value);
            pricePrimary.textContent = customMinor == null ? '' : fmtMinor(currency, customMinor);

            var otherMinor = other ? minorFor(other, value) : null;
            priceSecondary.textContent = otherMinor == null ? '' : fmtMinor(other, otherMinor);

            // A fixed package that costs no more AND gives more tokens means
            // the customer is about to lose out by typing a number. Compared in
            // the SELECTED currency, because that is what they would pay.
            var better = packages.filter(function (p) {
                return customMinor != null && p.minor <= customMinor && p.total > value;
            }).sort(function (a, b) { return b.total - a.total; })[0];

            if (better) {
                nudge.textContent = 'The ' + better.label + ' package gives you '
                    + better.total.toLocaleString() + ' tokens for '
                    + fmtMinor(currency, better.minor) + ', which is '
                    + (better.total - value).toLocaleString()
                    + ' more tokens and costs no more than this custom amount.';
                nudge.classList.remove('hidden');
            } else {
                nudge.classList.add('hidden');
            }
        }

        input.addEventListener('input', refresh);
        refresh();
    })();
    </script>
    @endpush

</x-marketplace-layout>
