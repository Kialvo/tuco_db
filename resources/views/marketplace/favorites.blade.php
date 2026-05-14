<x-marketplace-layout>
    <x-slot name="title">My Favorites</x-slot>

    <x-slot name="pageHeader">
        <x-ds.page-header title="My Favorites">
            <x-slot name="actions">
                <button type="button"
                        onclick="window.LIBCart && window.LIBCart.openDrawer()"
                        class="relative inline-flex items-center justify-center gap-2 px-3.5 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <x-icon name="cart" size="sm" /> Order
                    <span id="cartCountBadge"
                          class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-white text-green-700 text-[10px] font-bold hidden items-center justify-center border-2 border-green-600">0</span>
                </button>
            </x-slot>
        </x-ds.page-header>
    </x-slot>

    <div id="favoritesWrap">

        {{-- Empty state: shown by Blade when 0, or revealed by JS when last row is removed --}}
        <div id="favEmptyState" @if($websites->total() > 0) style="display:none" @endif>
            <x-ds.empty-state
                icon="star"
                title="No favorites yet"
                hint="Click the ☆ on any domain to save it here.">
                <x-slot name="action">
                    <x-ds.button variant="primary" size="md" :href="route('websites.index')">
                        <x-icon name="search" size="sm" /> Browse domains
                    </x-ds.button>
                </x-slot>
            </x-ds.empty-state>
        </div>

        @if($websites->total() > 0)
            <div id="favTableSection">
                <p id="favCountLabel" class="text-sm text-gray-500 mb-4">
                    {{ $websites->total() }} {{ Str::plural('domain', $websites->total()) }} saved
                </p>

                <x-ds.table-shell>
                    <x-slot name="head">
                        <x-ds.th width="10" align="center">Fav</x-ds.th>
                        <x-ds.th width="10" align="center" tip="Add to order">+</x-ds.th>
                        <x-ds.th>Domain</x-ds.th>
                        <x-ds.th>Country</x-ds.th>
                        <x-ds.th>Price</x-ds.th>
                        <x-ds.th tip="Price for betting, trading or adult content">Sens. Price</x-ds.th>
                        <x-ds.th align="center" tip="Domain Authority (Moz) 0–100">DA</x-ds.th>
                        <x-ds.th align="center" tip="Menford Score 0–100">MS</x-ds.th>
                    </x-slot>

                    @foreach($websites as $w)
                        @php
                            $daClass = $w->DA >= 60 ? 'text-green-600' : ($w->DA >= 40 ? 'text-amber-600' : 'text-gray-500');
                        @endphp
                        <tr id="fav-row-{{ $w->id }}" class="cart-row" data-website-id="{{ $w->id }}">
                            <td class="px-3 py-3 text-center">
                                <button type="button"
                                        onclick="removeFav({{ $w->id }})"
                                        class="text-lg leading-none transition-transform hover:scale-110"
                                        title="Remove from favorites">⭐</button>
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
                                <span class="font-medium text-gray-800 text-sm">{{ $w->domain_name }}</span>
                            </td>
                            <td class="px-3 py-3 text-sm text-gray-600 whitespace-nowrap">
                                <x-flag :country="optional($w->country)->country_name" /> {{ optional($w->country)->country_name ?? '—' }}
                            </td>
                            <td class="px-3 py-3 text-sm font-semibold text-gray-800 whitespace-nowrap">
                                @if($w->price)€ {{ number_format($w->price, 0, '.', ',') }}@else<span class="text-gray-300">—</span>@endif
                            </td>
                            <td class="px-3 py-3 text-sm text-gray-600 whitespace-nowrap">
                                @if($w->sensitive_topic_price)€ {{ number_format($w->sensitive_topic_price, 0, '.', ',') }}@else<span class="text-gray-300">—</span>@endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="text-sm font-bold {{ $daClass }}">{{ $w->DA ?? '—' }}</span>
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if(! is_null($w->ms))
                                    <x-ds.score-badge :score="$w->ms" :thresholds="[80, 65]" />
                                @else
                                    <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-ds.table-shell>

                <x-ds.pagination :paginator="$websites" />
            </div>
        @endif
    </div>

    <script>
        // ─── STAR REMOVE (instant row removal, no page reload) ───
        var __favCount = {{ $websites->total() }};

        function removeFav(id) {
            var row = document.getElementById('fav-row-' + id);
            if (!row) return;

            fetch('/websites/' + id + '/favorite', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            }).then(function (r) {
                if (!r.ok) return;
                row.style.transition = 'opacity 200ms, transform 200ms';
                row.style.opacity = '0';
                row.style.transform = 'translateX(10px)';
                setTimeout(function () {
                    row.remove();
                    __favCount--;
                    var label = document.getElementById('favCountLabel');
                    if (__favCount <= 0) {
                        var section = document.getElementById('favTableSection');
                        var empty   = document.getElementById('favEmptyState');
                        if (section) section.style.display = 'none';
                        if (empty)   empty.style.display = '';
                    } else if (label) {
                        label.textContent = __favCount + ' ' + (__favCount === 1 ? 'domain' : 'domains') + ' saved';
                    }
                }, 220);
            }).catch(function (e) {
                console.error('[removeFav]', e);
            });
        }

        // ─── CART WIRING (identical to domains.blade.php) ───
        (function () {
            function paintRow(websiteId, inCart) {
                var btn = document.querySelector('.cart-toggle[data-website-id="' + websiteId + '"]');
                if (!btn) return;
                var plus  = btn.querySelector('.cart-toggle-plus');
                var check = btn.querySelector('.cart-toggle-check');
                if (inCart) {
                    btn.classList.remove('bg-gray-100', 'hover:bg-green-100', 'text-gray-500', 'hover:text-green-700');
                    btn.classList.add('bg-green-600', 'text-white');
                    btn.title = 'Click to remove from order';
                    if (plus)  plus.classList.add('hidden');
                    if (check) check.classList.remove('hidden');
                } else {
                    btn.classList.remove('bg-green-600', 'text-white');
                    btn.classList.add('bg-gray-100', 'hover:bg-green-100', 'text-gray-500', 'hover:text-green-700');
                    btn.title = 'Add to order';
                    if (plus)  plus.classList.remove('hidden');
                    if (check) check.classList.add('hidden');
                }
                var row = btn.closest('.cart-row');
                if (row) row.classList.toggle('bg-green-50', inCart);
            }

            function paintBadge(count) {
                var b = document.getElementById('cartCountBadge');
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
                var ids = new Set((snap.items || []).map(function (i) { return Number(i.website_id); }));
                document.querySelectorAll('.cart-toggle').forEach(function (btn) {
                    paintRow(Number(btn.dataset.websiteId), ids.has(Number(btn.dataset.websiteId)));
                });
                paintBadge(snap.count || 0);
            }

            document.addEventListener('click', function (e) {
                var btn = e.target.closest('.cart-toggle');
                if (!btn) return;
                e.preventDefault();
                if (!window.LIBCart) return;
                var id     = Number(btn.dataset.websiteId);
                var inCart = window.LIBCart.isInCart(id);
                btn.disabled = true;
                var p = inCart
                    ? window.LIBCart.removeItemByWebsiteId(id)
                    : window.LIBCart.addItem(id);
                p.then(function () {
                    btn.disabled = false;
                    if (!inCart) window.LIBCart.openDrawer();
                });
            });

            function ready() {
                if (!window.LIBCart) return setTimeout(ready, 50);
                window.LIBCart.onChange(paintAll);
                window.LIBCart.refresh().then(function (d) { if (d) paintAll(d); });
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', ready, { once: true });
            } else {
                ready();
            }
        })();
    </script>
</x-marketplace-layout>
