{{--
    Right-slide cart drawer + order-confirmed modal + global Alpine store.
    Auto-included by the marketplace layout for guest users.
--}}

<div x-data x-cloak>
    {{-- ─── DRAWER ─── --}}
    <aside id="cart-drawer"
           class="cart-panel fixed right-0 top-0 h-full w-[380px] max-w-full bg-white shadow-cart border-l border-gray-200 flex flex-col z-40"
           :class="$store.cart.open ? 'open' : ''">
        <header class="px-5 py-4 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
            <div>
                <h2 class="font-bold text-gray-800 text-base">Current Order</h2>
                <p class="text-xs text-gray-400 mt-0.5">
                    <span x-text="$store.cart.count"></span>
                    <span x-text="$store.cart.count === 1 ? 'site' : 'sites'"></span>
                    selected
                </p>
            </div>
            <button type="button" @click="$store.cart.close()"
                    class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-700 transition-colors">
                <x-icon name="x" size="lg" />
            </button>
        </header>

        <div class="flex-1 overflow-y-auto slim-scroll p-4">
            {{-- Empty state --}}
            <div x-show="$store.cart.count === 0" class="text-center py-14">
                <x-icon name="cart" size="w-10 h-10" class="text-gray-200 mx-auto mb-3" :stroke="1.5" />
                <p class="text-gray-400 text-sm">No sites added yet.</p>
                <p class="text-gray-400 text-xs mt-1">Click <strong>+</strong> on any domain to add it here.</p>
            </div>

            {{-- Items --}}
            <div x-show="$store.cart.count > 0" class="space-y-2">
                <template x-for="item in $store.cart.items" :key="item.id">
                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                        <div class="flex items-start gap-2">
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-800 truncate" x-text="item.domain"></div>
                                <div class="text-xs text-gray-400 mt-0.5">
                                    <span x-text="item.country ?? '—'"></span> ·
                                    DA <span x-text="item.da ?? '—'"></span> ·
                                    MS <span x-text="item.ms ?? '—'"></span> ·
                                    <strong class="text-gray-700">€ <span x-text="(item.unit_price ?? 0).toLocaleString()"></span></strong>
                                </div>
                            </div>
                            <button type="button" @click="$store.cart.removeItem(item.id)"
                                    class="w-6 h-6 flex items-center justify-center rounded text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors flex-shrink-0 mt-0.5">
                                <x-icon name="x" size="sm" />
                            </button>
                        </div>

                        {{-- Standard / Sensitive toggle --}}
                        <div class="flex gap-1.5 mt-2.5">
                            <button type="button" @click="$store.cart.setType(item.id, 'standard')"
                                    class="flex-1 text-xs py-1.5 px-2 rounded-md font-semibold border transition-all text-center leading-tight"
                                    :class="item.article_type === 'standard'
                                        ? 'bg-green-600 text-white border-green-600 shadow-sm'
                                        : 'bg-white text-gray-500 border-gray-200 hover:border-gray-300 hover:bg-gray-50'">
                                Standard<br>
                                <span class="font-normal"
                                      :class="item.article_type === 'standard' ? 'opacity-80' : 'opacity-60'">
                                    € <span x-text="(item.price ?? 0).toLocaleString()"></span>
                                </span>
                            </button>
                            <button type="button"
                                    :disabled="!item.has_sensitive"
                                    :title="!item.has_sensitive ? 'This site does not accept sensitive content' : ''"
                                    @click="item.has_sensitive && $store.cart.setType(item.id, 'sensitive')"
                                    class="flex-1 text-xs py-1.5 px-2 rounded-md font-semibold border transition-all text-center leading-tight"
                                    :class="!item.has_sensitive
                                        ? 'bg-gray-50 text-gray-300 border-gray-100 cursor-not-allowed'
                                        : item.article_type === 'sensitive'
                                            ? 'bg-sensitive text-white border-sensitive shadow-sm'
                                            : 'bg-white text-gray-500 border-gray-200 hover:border-sensitive-border hover:text-sensitive-text'">
                                Sensitive<br>
                                <span class="font-normal"
                                      :class="item.article_type === 'sensitive' ? 'opacity-80' : 'opacity-60'">
                                    <span x-show="item.has_sensitive">€ <span x-text="(item.sensitive_price ?? 0).toLocaleString()"></span></span>
                                    <span x-show="!item.has_sensitive">N/A</span>
                                </span>
                            </button>
                        </div>

                        {{-- Sensitive warning --}}
                        <p x-show="item.article_type === 'sensitive'"
                           class="text-xs text-sensitive-text mt-1.5 leading-relaxed">
                            ⚠ Sensitive topics: gambling &amp; betting · trading, forex &amp; crypto · adult content · pharmaceuticals · weapons
                        </p>
                    </div>
                </template>

                {{-- Total --}}
                <div class="flex items-center justify-between px-3 py-2 bg-green-50 rounded-lg border border-green-100 mt-1">
                    <span class="text-xs font-semibold text-green-700">Estimated total</span>
                    <span class="text-sm font-bold text-green-700">€ <span x-text="$store.cart.total.toLocaleString()"></span></span>
                </div>
            </div>
        </div>

        {{-- Footer: notes + submit --}}
        <div class="px-4 pb-5 pt-3 border-t border-gray-100 flex-shrink-0 space-y-3">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                    Order Notes <span class="font-normal text-gray-400 normal-case">(optional)</span>
                </label>
                <textarea x-model="$store.cart.notes" rows="2"
                          placeholder="e.g. Article provided by us · Focus on IT and ES markets…"
                          class="fi resize-none text-xs py-2 leading-relaxed"></textarea>
            </div>
            <button type="button" @click="$store.cart.submit()"
                    :disabled="$store.cart.count === 0 || $store.cart.submitting"
                    class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-green-600 hover:bg-green-700 active:bg-green-800 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed">
                <span x-show="!$store.cart.submitting">Submit Order Request</span>
                <span x-show="$store.cart.submitting">Submitting…</span>
            </button>
            <p class="text-xs text-gray-400 text-center">We'll confirm prices within 24 hours</p>
        </div>
    </aside>

    {{-- Backdrop (Alpine path — vanilla backdrop is also injected by LIBCart.openDrawer) --}}
    <div x-show="$store.cart.open" @click="$store.cart.close()"
         x-cloak
         class="fixed inset-0 bg-black/20 z-30"
         x-transition.opacity.duration.200ms></div>

    {{-- ─── ORDER CONFIRMED MODAL ─── --}}
    <div x-show="$store.cart.confirmShown"
         x-cloak
         class="fixed inset-0 bg-black/50 items-center justify-center z-50 p-4 flex"
         x-transition.opacity.duration.200ms>
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm text-center p-8"
             @click.outside="$store.cart.confirmShown = false">
            <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
                <x-icon name="check" size="xl" class="text-green-600" :stroke="2.5" />
            </div>
            <h2 class="text-xl font-bold text-gray-800 mb-2">Order submitted!</h2>
            <p class="text-gray-500 text-sm leading-relaxed mb-6">
                Your request has been received. We'll verify current prices with publishers and get back to you within 24 hours.
            </p>
            <button type="button" @click="$store.cart.viewOrder()"
                    class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg shadow-sm">
                View My Orders
            </button>
            <button type="button" @click="$store.cart.confirmShown = false"
                    class="mt-2 w-full text-sm text-gray-400 hover:text-gray-600 py-1 transition-colors">
                Continue browsing
            </button>
        </div>
    </div>
</div>

{{-- Alpine global store + window.LIBCart bridge --}}
<script>
    /* ─── Bridge: jQuery / non-Alpine code calls these. ───
       Each method lazily resolves the Alpine store at call time, so it
       works regardless of Alpine boot timing. Listeners get notified
       whenever the store state changes (used for header count badge). */
    (function () {
        const listeners = new Set();
        const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
        const getStore = () => (window.Alpine && Alpine.store ? Alpine.store('cart') : null);
        const cartRoutes = {
            state:  '{{ route('orders.cart') }}',
            add:    (id)     => `/orders/cart/${id}`,
            remove: (itemId) => `/orders/cart/items/${itemId}`,
            type:   (itemId) => `/orders/cart/items/${itemId}`,
            submit: '{{ route('orders.submit') }}',
        };

        function notify() { listeners.forEach(cb => { try { cb(window.LIBCart.snapshot()); } catch(e){} }); }

        async function applyState(d) {
            const s = getStore();
            if (s) {
                s.count = d.count || 0;
                s.total = d.total || 0;
                s.items = d.items || [];
            }
            notify();
            return d;
        }

        async function refresh() {
            try {
                const r = await fetch(cartRoutes.state, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!r.ok) return null;
                return applyState(await r.json());
            } catch (e) { return null; }
        }

        window.LIBCart = {
            async addItem(websiteId) {
                console.log('[LIBCart] addItem', websiteId);
                try {
                    const r = await fetch(cartRoutes.add(websiteId), {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() },
                    });
                    if (!r.ok) { console.warn('[LIBCart] addItem failed', r.status); return false; }
                    await applyState(await r.json());
                    return true;
                } catch (e) { console.error('[LIBCart] addItem error', e); return false; }
            },
            async removeItem(itemId) {
                console.log('[LIBCart] removeItem', itemId);
                try {
                    const r = await fetch(cartRoutes.remove(itemId), {
                        method: 'DELETE',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() },
                    });
                    if (!r.ok) { console.warn('[LIBCart] removeItem failed', r.status); return false; }
                    await applyState(await r.json());
                    return true;
                } catch (e) { console.error('[LIBCart] removeItem error', e); return false; }
            },
            async removeItemByWebsiteId(websiteId) {
                const s = getStore();
                const item = s && s.items.find(i => i.website_id === Number(websiteId));
                if (item) return this.removeItem(item.id);
                // Fall back: fetch state, then look again
                const d = await refresh();
                const it = (d?.items || []).find(i => i.website_id === Number(websiteId));
                return it ? this.removeItem(it.id) : false;
            },
            openDrawer() {
                console.log('[LIBCart] openDrawer');
                // 1) Slide the panel in via plain CSS class (no Alpine needed)
                const aside = document.getElementById('cart-drawer');
                if (aside) aside.classList.add('open');
                // 2) Render backdrop element directly
                let bd = document.getElementById('cart-backdrop');
                if (!bd) {
                    bd = document.createElement('div');
                    bd.id = 'cart-backdrop';
                    bd.className = 'fixed inset-0 bg-black/30 z-30';
                    bd.addEventListener('click', () => window.LIBCart.closeDrawer());
                    document.body.appendChild(bd);
                }
                bd.style.display = '';
                // 3) Best-effort: also flip Alpine store so x-show bindings agree
                const s = getStore();
                if (s) s.open = true;
            },
            closeDrawer() {
                console.log('[LIBCart] closeDrawer');
                const aside = document.getElementById('cart-drawer');
                if (aside) aside.classList.remove('open');
                const bd = document.getElementById('cart-backdrop');
                if (bd) bd.remove();
                const s = getStore();
                if (s) s.open = false;
            },
            snapshot() {
                const s = getStore();
                return s
                    ? { count: s.count, total: s.total, items: s.items.slice() }
                    : { count: 0, total: 0, items: [] };
            },
            onChange(cb)  { listeners.add(cb);    return () => listeners.delete(cb); },
            isInCart(websiteId) {
                const s = getStore();
                if (!s) return false;
                return s.items.some(i => i.website_id === Number(websiteId));
            },
            refresh,
            notify,
        };

        console.log('[LIBCart] bridge ready');

        // Paint badge / sync state as soon as the DOM is ready (don't wait for Alpine)
        if (document.readyState !== 'loading') {
            window.LIBCart.refresh();
        } else {
            document.addEventListener('DOMContentLoaded', () => window.LIBCart.refresh(), { once: true });
        }
    })();

    document.addEventListener('alpine:init', () => {
        Alpine.store('cart', {
            open: false,
            count: 0,
            total: 0,
            items: [],
            notes: '',
            submitting: false,
            confirmShown: false,
            lastOrderId: null,

            csrf() {
                return document.querySelector('meta[name="csrf-token"]')?.content || '';
            },

            async load() {
                try {
                    const r = await fetch('{{ route('orders.cart') }}', {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!r.ok) return;
                    const d = await r.json();
                    this.applyState(d);
                } catch (e) { /* silent */ }
            },

            applyState(d) {
                this.count = d.count || 0;
                this.total = d.total || 0;
                this.items = d.items || [];
                if (window.LIBCart && typeof window.LIBCart.notify === 'function') {
                    window.LIBCart.notify();
                }
            },

            openDrawer() { this.open = true;  if (window.LIBCart) window.LIBCart.openDrawer(); },
            close()      { this.open = false; if (window.LIBCart) { /* mirror vanilla cleanup */
                                  const aside = document.getElementById('cart-drawer');
                                  if (aside) aside.classList.remove('open');
                                  const bd = document.getElementById('cart-backdrop');
                                  if (bd) bd.remove();
                              } },
            toggle()     { this.open = !this.open; },

            async addItem(websiteId) {
                try {
                    const r = await fetch(`/orders/cart/${websiteId}`, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': this.csrf() },
                    });
                    if (!r.ok) return false;
                    this.applyState(await r.json());
                    return true;
                } catch (e) { return false; }
            },

            async removeItem(itemId) {
                try {
                    const r = await fetch(`/orders/cart/items/${itemId}`, {
                        method: 'DELETE',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': this.csrf() },
                    });
                    if (!r.ok) return false;
                    this.applyState(await r.json());
                    return true;
                } catch (e) { return false; }
            },

            async setType(itemId, type) {
                try {
                    const r = await fetch(`/orders/cart/items/${itemId}`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': this.csrf(),
                        },
                        body: JSON.stringify({ article_type: type }),
                    });
                    if (!r.ok) return;
                    this.applyState(await r.json());
                } catch (e) {}
            },

            hasItem(websiteId) {
                return this.items.some(i => i.website_id === websiteId);
            },

            async submit() {
                if (this.count === 0 || this.submitting) return;
                this.submitting = true;
                try {
                    const r = await fetch('{{ route('orders.submit') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': this.csrf(),
                        },
                        body: JSON.stringify({ notes: this.notes }),
                    });
                    if (r.ok) {
                        const d = await r.json();
                        this.lastOrderId = d.order_id;
                        this.count = 0;
                        this.total = 0;
                        this.items = [];
                        this.notes = '';
                        this.close();
                        this.confirmShown = true;
                        // Repaint table + buttons so they go back to gray
                        if (window.LIBCart && typeof window.LIBCart.notify === 'function') {
                            window.LIBCart.notify();
                        }
                        if (window.jQuery && jQuery.fn.dataTable && jQuery.fn.dataTable.isDataTable('#websitesTable')) {
                            jQuery('#websitesTable').DataTable().ajax.reload(null, false);
                        }
                    } else {
                        const d = await r.json().catch(() => ({}));
                        const msg = d.error || 'Something went wrong. Please try again.';
                        if (window.Swal) {
                            await Swal.fire({ icon: 'error', title: 'Order failed', text: msg });
                        } else {
                            alert(msg);
                        }
                        // Sync cart state from server to fix any client/server mismatch
                        await this.load();
                    }
                } catch (e) {
                    if (window.Swal) {
                        Swal.fire({ icon: 'error', title: 'Network error', text: 'Please check your connection and try again.' });
                    }
                }
                this.submitting = false;
            },

            viewOrder() {
                if (this.lastOrderId) {
                    window.location.href = `/orders/${this.lastOrderId}`;
                } else {
                    window.location.href = '{{ route('orders.index') }}';
                }
            },
        });

        // Load cart state on page load
        Alpine.store('cart').load();
    });
</script>
