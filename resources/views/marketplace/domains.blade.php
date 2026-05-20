<x-marketplace-layout>
    <x-slot name="title">Domains</x-slot>

    {{-- ─── LEFT FILTER PANEL ─── --}}
    <x-slot name="filters">
        @include('marketplace.partials.domain-filters')
    </x-slot>

    {{-- ─── PAGE HEADER (top bar) ─── --}}
    <x-slot name="pageHeader">
        <x-ds.page-header title="Domains">
            <x-slot name="actions">
                <button type="button"
                        id="btnOpenCart"
                        onclick="window.LIBCart && window.LIBCart.openDrawer()"
                        class="relative inline-flex items-center justify-center gap-2 px-3.5 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <x-icon name="cart" size="sm" /> Order
                    <span id="cartCountBadge"
                          class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-white text-green-700 text-[10px] font-bold hidden items-center justify-center border-2 border-green-600">0</span>
                </button>
                <x-ds.button :href="route('websites.export.csv', request()->query())" variant="secondary" size="md">
                    <x-icon name="download" size="sm" /> Export CSV
                </x-ds.button>
                <x-ds.button :href="route('websites.export.pdf', request()->query())" variant="secondary" size="md">
                    <x-icon name="document-pdf" size="sm" /> Export PDF
                </x-ds.button>
            </x-slot>
        </x-ds.page-header>
    </x-slot>

    {{-- ─── MAIN CONTENT ─── --}}
    @php
        $totalShown = $websites->count();
        $from       = $websites->firstItem() ?? 0;
        $to         = $websites->lastItem() ?? 0;
        $total      = $websites->total();
    @endphp

    <div class="space-y-4" x-data="domainListUI()" @fav-bulk-update.window="favs = new Set($event.detail || [])">

        {{-- Top row: page-size + search --}}
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-2.5 text-sm">
                <span class="text-gray-500">Show</span>
                <form method="GET" action="{{ route('websites.index') }}" class="inline-flex">
                    @foreach($filters as $k => $v)
                        @if(! is_null($v) && $v !== '' && $k !== 'per_page' && $k !== 'page' && ! is_array($v))
                            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                        @endif
                    @endforeach
                    <select name="per_page" onchange="this.form.submit()"
                            class="fi w-20 py-1.5 text-sm">
                        @foreach([10, 25, 50, 100] as $opt)
                            <option value="{{ $opt }}" @selected($perPage === $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                </form>
                <span class="text-gray-500">websites</span>
                @if($total > 0)
                    <span class="text-gray-400">— Showing {{ $from }}–{{ $to }} of <strong class="text-gray-700">{{ number_format($total) }} websites</strong></span>
                @else
                    <span class="text-gray-400">— no matches</span>
                @endif
            </div>

            <div class="relative">
                <x-icon name="search" size="sm" class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400" />
                <form method="GET" action="{{ route('websites.index') }}" class="contents">
                    @foreach($filters as $k => $v)
                        @if(! is_null($v) && $v !== '' && $k !== 'domain_name' && ! is_array($v))
                            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                        @endif
                    @endforeach
                    <input type="text" name="domain_name" value="{{ $filters['domain_name'] ?? '' }}"
                           placeholder="Search domains…" class="fi pl-8 py-1.5 w-52 text-sm">
                </form>
            </div>
        </div>

        {{-- Table --}}
        @if($websites->count() === 0)
            <x-ds.empty-state
                icon="search"
                title="No domains match these filters"
                hint="Try clearing some filters, or widen your price/score ranges.">
                <x-slot name="action">
                    <x-ds.button variant="secondary" size="md" :href="route('websites.index')">
                        Clear all filters
                    </x-ds.button>
                </x-slot>
            </x-ds.empty-state>
        @else
            <x-ds.table-shell>
                <x-slot name="head">
                    <x-ds.th width="10" align="center">
                        <button type="button" id="favHeaderToggle"
                                title="Click to select / unselect all visible domains as favorites"
                                class="inline-flex items-center justify-center w-7 h-7 rounded-md text-gray-400 hover:bg-gray-100 hover:text-amber-500 transition">
                            <span id="favHeaderStar" class="text-lg leading-none">☆</span>
                        </button>
                    </x-ds.th>
                    <x-ds.th width="10" align="center" tip="Add to order">+</x-ds.th>
                    <x-ds.th>{{ __('Domain') }}</x-ds.th>
                    <x-ds.th>Notes</x-ds.th>
                    <x-ds.th>Country</x-ds.th>
                    <x-ds.th>Lang</x-ds.th>
                    <x-ds.th tip="This is the final amount you pay for placement on this website, including our service fee.">Price</x-ds.th>
                    <x-ds.th tip="This is the final amount you pay for publishing content in sensitive niches (e.g. gambling, crypto, adult, dating, CBD, etc.), including our service fee.">Sens. Price</x-ds.th>
                    <x-ds.th>Type</x-ds.th>
                    <x-ds.th>Categories</x-ds.th>
                    <x-ds.th align="center" tip="Domain Authority (Moz): ranking score 1-100. Higher DA usually passes more link value; 30+ good, 50+ excellent, 70+ premium.">DA</x-ds.th>
                    <x-ds.th align="center" tip="Page Authority (Moz): predicts ranking strength of a specific page on a 1-100 scale. Higher is better.">PA</x-ds.th>
                    <x-ds.th align="center" tip="Authority Score (Semrush): overall domain quality score (0-100) based on backlinks, traffic, and trust signals.">AS</x-ds.th>
                    <x-ds.th align="center" tip="Estimated monthly organic visitors from Semrush. Higher traffic means more visibility; 5k+ good, 50k+ excellent.">Semrush Traffic</x-ds.th>
                    <x-ds.th align="center" tip="Menford Score: proprietary authority score (0–1,000) based on a weighted average of backlink profile strength across multiple competitive intelligence sources. Higher is better; 100–200 entry level, 200–400 good, 400+ strong.">MS</x-ds.th>
                    <x-ds.th align="center" tip="Organic Keywords: estimated number of keywords a domain ranks for in organic search results globally. Higher values indicate broader topical relevance; 1,000–5,000 entry level, 5,000–30,000 good, 30,000+ strong.">Organic KW</x-ds.th>
                    <x-ds.th align="center" tip="Organic Traffic: estimated monthly organic search visits. Values are best used for comparative analysis across domains; 5,000–20,000 entry level, 20,000–200,000 good, 200,000+ strong.">Organic Traffic</x-ds.th>
                    <x-ds.th align="center" tip="KW/Traffic Ratio: traffic efficiency per keyword. Higher means each keyword brings more visits; low ratios may suggest weak rankings.">KW/Traffic</x-ds.th>
                    <x-ds.th align="center" tip="Accepts betting and gambling content.">Betting</x-ds.th>
                    <x-ds.th align="center" tip="Accepts trading, forex and crypto content.">Trading</x-ds.th>
                </x-slot>

                @foreach($websites as $w)
                    @php
                        $isFav = isset($favoriteIds[$w->id]);
                        $isNew = $w->created_at && $w->created_at->gt(now()->subDays(7));
                        $type  = strtoupper((string) $w->type_of_website);
                        $typeTone = match($type) {
                            'NEWS'                => 'blue',
                            'NICHE', 'VERTICAL'   => 'purple',
                            'GENERALIST', 'FORUM' => 'gray',
                            'LOCAL'               => 'amber',
                            default               => 'gray',
                        };
                        $cats = $w->categories->pluck('name')->join(', ');
                        $daClass = $w->DA >= 60 ? 'text-green-600' : ($w->DA >= 40 ? 'text-amber-600' : 'text-gray-500');
                    @endphp
                    <tr class="cart-row" data-website-id="{{ $w->id }}">
                        <td class="px-3 py-3 text-center">
                            <button type="button" @click="toggleFav({{ $w->id }})"
                                    class="text-lg leading-none transition-transform hover:scale-110"
                                    title="Favourite">
                                <span x-show="favs.has({{ $w->id }})" {{ $isFav ? '' : 'style=display:none' }}>⭐</span>
                                <span x-show="!favs.has({{ $w->id }})" {{ $isFav ? 'style=display:none' : '' }}>☆</span>
                            </button>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <button type="button"
                                    class="cart-toggle w-7 h-7 rounded-lg flex items-center justify-center mx-auto transition-all bg-gray-100 hover:bg-green-100 text-gray-500 hover:text-green-700"
                                    data-website-id="{{ $w->id }}"
                                    title="Add to order">
                                <svg class="w-4 h-4 cart-toggle-plus" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                <svg class="w-4 h-4 cart-toggle-check hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </td>
                        <td class="px-3 py-3">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-800 text-sm">{{ $w->domain_name }}</span>
                                @if($isNew)
                                    <x-ds.pill tone="green" size="sm" class="!text-[10px] !px-1.5 !py-0.5">NEW</x-ds.pill>
                                @endif
                            </div>
                        </td>
                        <td class="px-3 py-3 text-xs text-gray-500 max-w-[140px]">
                            <span class="block truncate" title="{{ $w->notes }}">{{ $w->notes ?: '—' }}</span>
                        </td>
                        <td class="px-3 py-3 text-sm text-gray-600 whitespace-nowrap">
                            <x-flag :country="optional($w->country)->country_name" /> {{ optional($w->country)->country_name ?? '—' }}
                        </td>
                        <td class="px-3 py-3 text-sm text-gray-600">{{ optional($w->language)->name ?? '—' }}</td>
                        <td class="px-3 py-3 text-sm font-semibold text-gray-800 whitespace-nowrap">
                            @if($w->price)€ {{ number_format($w->price, 0, '.', ',') }}@else<span class="text-gray-300">—</span>@endif
                        </td>
                        <td class="px-3 py-3 text-sm text-gray-600 whitespace-nowrap">
                            @if($w->sensitive_topic_price)€ {{ number_format($w->sensitive_topic_price, 0, '.', ',') }}@else<span class="text-gray-300">—</span>@endif
                        </td>
                        <td class="px-3 py-3">
                            @if($type)
                                <x-ds.pill :tone="$typeTone" shape="square" size="sm">{{ $type }}</x-ds.pill>
                            @else
                                <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-xs text-gray-500 max-w-[150px]">
                            <span class="block truncate" title="{{ $cats }}">{{ $cats ?: '—' }}</span>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <span class="text-sm font-bold {{ $daClass }}">{{ $w->DA ?? '—' }}</span>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <span class="text-sm font-semibold text-gray-600">{{ $w->PA ?? '—' }}</span>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <span class="text-sm font-semibold text-gray-600">{{ $w->as_metric ?? '—' }}</span>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <span class="text-sm text-gray-600">{{ $w->semrush_traffic ? number_format($w->semrush_traffic) : '—' }}</span>
                        </td>
                        <td class="px-3 py-3 text-center">
                            @if(! is_null($w->ms))
                                <x-ds.score-badge :score="$w->ms" :thresholds="[80, 65]" />
                            @else
                                <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-center">
                            <span class="text-sm text-gray-600">{{ $w->organic_keywords ? number_format($w->organic_keywords) : '—' }}</span>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <span class="text-sm text-gray-600">{{ $w->organic_traffic ? number_format($w->organic_traffic) : '—' }}</span>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <span class="text-sm text-gray-600">{{ $w->kw_traffic_ratio ?? '—' }}</span>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <x-ds.pill :tone="$w->betting ? 'green' : 'red'" size="sm">{{ $w->betting ? 'YES' : 'NO' }}</x-ds.pill>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <x-ds.pill :tone="$w->trading ? 'green' : 'red'" size="sm">{{ $w->trading ? 'YES' : 'NO' }}</x-ds.pill>
                        </td>
                    </tr>
                @endforeach
            </x-ds.table-shell>

            {{-- Pagination --}}
            <x-ds.pagination :paginator="$websites" />
        @endif
    </div>

    {{-- ─── ALPINE STATE for favorites ─── --}}
    <script>
        function domainListUI() {
            const initialFavs = @json(array_keys($favoriteIds));
            window.__visibleDomainIds = @json($websites->pluck('id')->all());
            window.__favSet = new Set(initialFavs);
            return {
                favs: window.__favSet,
                async toggleFav(id) {
                    if (this.favs.has(id)) this.favs.delete(id); else this.favs.add(id);
                    this.favs = new Set(this.favs); // trigger reactivity
                    window.__favSet = this.favs;
                    window.__updateFavHeaderStar && window.__updateFavHeaderStar();
                    try {
                        await fetch(`/websites/${id}/favorite`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                        });
                    } catch (e) {
                        // revert on error
                        if (this.favs.has(id)) this.favs.delete(id); else this.favs.add(id);
                        this.favs = new Set(this.favs);
                        window.__favSet = this.favs;
                        window.__updateFavHeaderStar && window.__updateFavHeaderStar();
                    }
                },
            };
        }
    </script>

    {{-- ─── FAV header → toggle-all visible ─── --}}
    <script>
        (function () {
            const star = document.getElementById('favHeaderStar');
            const btn  = document.getElementById('favHeaderToggle');
            if (!star || !btn) return;

            function visibleIds() {
                return (window.__visibleDomainIds || []).map(Number);
            }
            function favSet() {
                return window.__favSet || new Set();
            }

            function paintStar() {
                const ids = visibleIds();
                if (ids.length === 0) {
                    star.textContent = '☆';
                    star.className = 'text-lg leading-none text-gray-300';
                    btn.title = 'No domains on this page';
                    return;
                }
                const favs = favSet();
                const favCount = ids.filter(id => favs.has(id)).length;
                if (favCount === 0) {
                    star.textContent = '☆';
                    star.className = 'text-lg leading-none text-gray-400';
                    btn.title = 'Favorite all ' + ids.length + ' visible domains';
                } else if (favCount === ids.length) {
                    star.textContent = '⭐';
                    star.className = 'text-lg leading-none';
                    btn.title = 'Unfavorite all ' + ids.length + ' visible domains';
                } else {
                    star.textContent = '⭐';
                    star.className = 'text-lg leading-none opacity-50';
                    btn.title = favCount + ' of ' + ids.length + ' favorited — click to favorite the rest';
                }
            }
            window.__updateFavHeaderStar = paintStar;

            // Mirror the change on Alpine's per-row star clicks too
            paintStar();

            btn.addEventListener('click', async function (e) {
                e.preventDefault();
                const ids = visibleIds();
                if (ids.length === 0) return;
                const favs = favSet();
                const allFavorited = ids.every(id => favs.has(id));
                const action = allFavorited ? 'remove' : 'add';

                btn.disabled = true;
                try {
                    const r = await fetch('{{ route('websites.favorites.bulk') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ ids, action }),
                    });
                    if (!r.ok) throw new Error('bulk fav failed: ' + r.status);
                    const d = await r.json();
                    const favoritedSet = new Set((d.favorited || []).map(Number));

                    // Reconcile: for each visible id, if it should be a favorite, add; else remove.
                    const next = new Set(window.__favSet || []);
                    ids.forEach(id => {
                        if (favoritedSet.has(id)) next.add(id); else next.delete(id);
                    });
                    window.__favSet = next;

                    // Tell Alpine to re-evaluate per-row stars
                    window.dispatchEvent(new CustomEvent('fav-bulk-update', { detail: Array.from(next) }));

                    paintStar();
                } catch (err) {
                    console.error(err);
                    alert('Could not update favorites. Please try again.');
                } finally {
                    btn.disabled = false;
                }
            });
        })();
    </script>

    {{-- ─── VANILLA CART WIRING (no Alpine dependency for clicks) ─── --}}
    <script>
        (function () {
            console.log('[domains] cart wiring init');

            function paintRow(websiteId, inCart) {
                const btn = document.querySelector(`.cart-toggle[data-website-id="${websiteId}"]`);
                if (!btn) return;
                const plus = btn.querySelector('.cart-toggle-plus');
                const check = btn.querySelector('.cart-toggle-check');
                if (inCart) {
                    btn.classList.remove('bg-gray-100', 'hover:bg-green-100', 'text-gray-500', 'hover:text-green-700');
                    btn.classList.add('bg-green-600', 'text-white');
                    btn.title = 'Click to remove from order';
                    if (plus) plus.classList.add('hidden');
                    if (check) check.classList.remove('hidden');
                } else {
                    btn.classList.remove('bg-green-600', 'text-white');
                    btn.classList.add('bg-gray-100', 'hover:bg-green-100', 'text-gray-500', 'hover:text-green-700');
                    btn.title = 'Add to order';
                    if (plus) plus.classList.remove('hidden');
                    if (check) check.classList.add('hidden');
                }
                const row = btn.closest('.cart-row');
                if (row) row.classList.toggle('bg-green-50', inCart);
            }

            function paintBadge(count) {
                const b = document.getElementById('cartCountBadge');
                if (!b) return;
                if (count > 0) {
                    b.textContent = count;
                    b.classList.remove('hidden');
                    b.classList.add('flex');
                } else {
                    b.classList.add('hidden');
                    b.classList.remove('flex');
                }
            }

            function paintAll(snap) {
                const ids = new Set((snap.items || []).map(i => Number(i.website_id)));
                document.querySelectorAll('.cart-toggle').forEach(btn => {
                    const id = Number(btn.dataset.websiteId);
                    paintRow(id, ids.has(id));
                });
                paintBadge(snap.count || 0);
            }

            // Click handler — event-delegated so DataTables / pagination don't break it
            document.addEventListener('click', function (e) {
                const btn = e.target.closest('.cart-toggle');
                if (!btn) return;
                e.preventDefault();
                if (!window.LIBCart) {
                    console.warn('[cart-toggle] LIBCart not ready yet');
                    return;
                }
                const id = Number(btn.dataset.websiteId);
                const inCart = window.LIBCart.isInCart(id);
                console.log('[cart-toggle] click', id, 'inCart=', inCart);
                btn.disabled = true;
                const p = inCart
                    ? window.LIBCart.removeItemByWebsiteId(id)
                    : window.LIBCart.addItem(id);
                p.then(() => {
                    btn.disabled = false;
                    if (!inCart) window.LIBCart.openDrawer();
                });
            });

            // Wait for the bridge, then wire up subscriptions + initial paint
            function ready() {
                if (!window.LIBCart) return setTimeout(ready, 50);
                console.log('[domains] LIBCart found — subscribing');
                window.LIBCart.onChange(paintAll);
                window.LIBCart.refresh().then(d => d && paintAll(d));
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', ready, { once: true });
            } else {
                ready();
            }
        })();
    </script>
</x-marketplace-layout>
