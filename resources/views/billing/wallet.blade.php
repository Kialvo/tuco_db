{{--
    Token wallet.

    Built from the app's design-system components (x-ds.*) rather than
    hand-rolled markup, so spacing, borders, type scale and tones match every
    other page. The euro figure is authoritative; a converted figure is
    indicative and is hidden entirely when no usable rate exists.
--}}
<x-marketplace-layout>
    <x-slot name="title">Tokens</x-slot>

    <x-slot name="pageHeader">
        <x-ds.page-header title="Tokens" subtitle="Prepaid credit for placements — 1 token = €1">
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

    <div class="px-6 py-5 space-y-6">

        @if(session('status'))
            <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif

        {{-- ─────────── Balance ─────────── --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-card p-7">
            <div class="flex items-start justify-between flex-wrap gap-x-8 gap-y-5">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Your balance</p>

                    <div class="flex items-baseline gap-2.5 mt-3">
                        <span class="text-4xl font-bold text-gray-800 leading-none tabular-nums">{{ number_format($balance) }}</span>
                        <span class="text-sm font-semibold text-gray-500">tokens</span>
                    </div>

                    <p class="text-lg font-semibold text-gray-700 mt-2.5 tabular-nums">€{{ number_format($balance, 2) }}</p>

                    @if($converted)
                        <p class="text-xs text-gray-500 mt-1.5">
                            ≈ {{ $display }} {{ number_format($balance * $converted['rate'], 2) }}
                            <span class="text-gray-400 mx-1" aria-hidden="true">·</span>
                            rate {{ number_format($converted['rate'], 4) }}
                            <span class="text-gray-400 mx-1" aria-hidden="true">·</span>
                            indicative only
                        </p>
                    @endif
                </div>

                {{-- flex-shrink-0 so the select keeps its own width instead of
                     being squeezed against the card edge on narrow viewports. --}}
                <form method="GET" class="flex items-center gap-2.5 flex-shrink-0">
                    <label for="display" class="text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Show in</label>
                    <select name="display" id="display" onchange="this.form.submit()" class="fi !w-24">
                        @foreach($displayCurrencies as $c)
                            <option value="{{ $c }}" @selected($display === $c)>{{ $c }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <p class="text-xs text-gray-500 leading-relaxed mt-7 pt-5 border-t border-gray-100">
                Placements are charged in tokens at their euro price, so the conversion above never
                affects what you pay.
            </p>
        </div>

        {{-- ─────────── Packages ─────────── --}}
        <div>
            <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Buy tokens</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-stretch">

                @foreach($packages as $key => $package)
                    <form method="POST" action="{{ route('billing.tokens.checkout') }}"
                          class="bg-white rounded-xl border border-gray-200 shadow-card p-5 flex flex-col
                                 hover:border-green-300 transition-colors">
                        @csrf
                        <input type="hidden" name="package" value="{{ $key }}">

                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ ucfirst($key) }}</p>

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

                        <div class="mt-auto pt-5 space-y-2">
                            <select name="currency" class="fi" aria-label="Currency for {{ $key }}">
                                @foreach($paymentCurrencies as $c)
                                    @if(isset($package['prices'][$c]))
                                        <option value="{{ $c }}">{{ $c }} {{ number_format($package['prices'][$c] / 100, 2) }}</option>
                                    @endif
                                @endforeach
                            </select>

                            <x-ds.button type="submit" variant="primary" size="md" block>Buy</x-ds.button>
                        </div>
                    </form>
                @endforeach

                {{-- Custom amount. Replaced the fixed 2,500 tier: a placement can
                     cost €130, and a €250 floor pushed people into buying more
                     credit than the job needed. --}}
                <form method="POST" action="{{ route('billing.tokens.checkout') }}"
                      class="bg-white rounded-xl border-2 border-dashed border-gray-300 shadow-card p-5 flex flex-col
                             hover:border-green-400 transition-colors">
                    @csrf
                    <input type="hidden" name="package" value="custom">

                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Custom</p>

                    {{-- The € is a fixed prefix rather than part of the value, so it
                         needs real clearance from the digits — pl-11 against a
                         left-4 glyph — otherwise "€130" reads as one crowded token. --}}
                    <div class="relative mt-3 h-11">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-2xl font-semibold text-gray-500 leading-none pointer-events-none">€</span>
                        <input type="number" name="tokens" id="customTokens"
                               min="{{ $customMin }}" max="{{ $customMax }}" step="1"
                               value="{{ old('tokens') }}" placeholder="130" required
                               class="fi w-full h-full !pl-11 !pr-3 !py-0 !text-3xl !font-bold !text-gray-800 !leading-none">
                    </div>

                    <div class="mt-2.5 h-6">
                        <span id="customHint" class="text-xs text-gray-500">
                            €{{ number_format($customMin) }}–€{{ number_format($customMax) }}
                        </span>
                    </div>

                    <div class="mt-auto pt-5 space-y-2">
                        <select name="currency" class="fi" aria-label="Currency for custom amount">
                            @foreach($paymentCurrencies as $c)
                                @if(isset($customMultipliers[$c]))
                                    <option value="{{ $c }}">{{ $c }}</option>
                                @endif
                            @endforeach
                        </select>

                        <x-ds.button type="submit" variant="secondary" size="md" block>Buy</x-ds.button>
                    </div>
                </form>
            </div>

            @error('tokens')
                <p class="mt-3 text-xs font-medium text-red-600">{{ $message }}</p>
            @enderror

            {{-- A custom amount earns no bonus, so someone typing 500 would
                 silently get 15 fewer tokens than the Standard package for the
                 same money. Say so rather than letting them find out later. --}}
            <p id="packageNudge"
               class="hidden mt-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-medium text-amber-700"></p>
        </div>

        {{-- ─────────── Statement ─────────── --}}
        <div>
            <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">History</h2>

            @if($transactions->isEmpty())
                <div class="bg-white rounded-xl border border-gray-200 shadow-card">
                    <x-ds.empty-state
                        icon="cart"
                        title="No token activity yet"
                        hint="Purchases, bonuses and placements will appear here." />
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
        </div>

    </div>

    @push('scripts')
    <script>
    (function () {
        var input = document.getElementById('customTokens');
        if (!input) { return; }

        var hint = document.getElementById('customHint');
        var nudge = document.getElementById('packageNudge');
        var packages = @json($nudgePackages);
        var range = hint.textContent.trim();

        function refresh() {
            var value = parseInt(input.value, 10);

            if (!value || value < 0) {
                hint.textContent = range;
                nudge.classList.add('hidden');
                return;
            }

            // 1 token = €1, so the amount typed IS the token count — say it
            // plainly rather than making the customer infer it.
            hint.textContent = value.toLocaleString() + ' tokens · ' + range;

            // A fixed package that costs no more AND gives more tokens means
            // the customer is about to lose out by typing a number.
            var better = packages.filter(function (p) {
                return p.euros <= value && p.total > value;
            }).sort(function (a, b) { return b.total - a.total; })[0];

            if (better) {
                nudge.textContent = 'Tip: the ' + better.label + ' package costs €'
                    + better.euros.toLocaleString() + ' and gives you '
                    + better.total.toLocaleString() + ' tokens — '
                    + (better.total - value).toLocaleString() + ' more than a €'
                    + value.toLocaleString() + ' custom top-up.';
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
