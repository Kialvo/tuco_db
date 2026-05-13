<x-marketplace-layout>
    <x-slot name="title">My Favorites</x-slot>

    <x-slot name="pageHeader">
        <x-ds.page-header title="My Favorites">
            <x-slot name="actions">
                <button type="button" @click="$store.cart.toggle()"
                        class="relative inline-flex items-center justify-center gap-2 px-3.5 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <x-icon name="cart" size="sm" /> Order
                    <span x-show="$store.cart.count > 0"
                          class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-white text-green-700 text-[10px] font-bold flex items-center justify-center border-2 border-green-600"
                          x-text="$store.cart.count"></span>
                </button>
            </x-slot>
        </x-ds.page-header>
    </x-slot>

    <div x-data="favoritesUI()">
        @if($websites->total() === 0)
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
        @else
            <div class="text-sm text-gray-500 mb-4">
                {{ $websites->total() }} {{ Str::plural('domain', $websites->total()) }} saved
            </div>

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
                    <tr :class="$store.cart.hasItem({{ $w->id }}) ? 'bg-green-50' : ''">
                        <td class="px-3 py-3 text-center">
                            <button type="button" @click="toggleFav({{ $w->id }})"
                                    class="text-lg leading-none transition-transform hover:scale-110"
                                    title="Remove from favorites">
                                <span x-show="favs.has({{ $w->id }})">⭐</span>
                                <span x-show="!favs.has({{ $w->id }})" class="text-gray-300">☆</span>
                            </button>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <button type="button"
                                    @click="$store.cart.hasItem({{ $w->id }}) ? null : $store.cart.addItem({{ $w->id }})"
                                    class="w-7 h-7 rounded-lg flex items-center justify-center mx-auto transition-all"
                                    :class="$store.cart.hasItem({{ $w->id }})
                                        ? 'bg-green-600 text-white'
                                        : 'bg-gray-100 hover:bg-green-100 text-gray-500 hover:text-green-700'"
                                    :title="$store.cart.hasItem({{ $w->id }}) ? 'Already in order' : 'Add to order'">
                                <span x-show="!$store.cart.hasItem({{ $w->id }})"><x-icon name="plus" size="sm" :stroke="2.5" /></span>
                                <span x-show="$store.cart.hasItem({{ $w->id }})" style="display:none"><x-icon name="check" size="sm" :stroke="2.5" /></span>
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
        @endif
    </div>

    <script>
        function favoritesUI() {
            const initialFavs = @json(array_keys($favoriteIds));
            return {
                favs: new Set(initialFavs),
                async toggleFav(id) {
                    const wasFav = this.favs.has(id);
                    if (wasFav) this.favs.delete(id); else this.favs.add(id);
                    this.favs = new Set(this.favs);
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
                        if (wasFav) this.favs.add(id); else this.favs.delete(id);
                        this.favs = new Set(this.favs);
                    }
                },
            };
        }
    </script>
</x-marketplace-layout>
