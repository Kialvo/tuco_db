@php
    $user        = Auth::user();
    $isGuest     = $user && $user->isGuest();
    $isAdmin     = $user && $user->isAdmin();
    $initials    = $user ? strtoupper(collect(explode(' ', $user->name))->map(fn($p) => mb_substr($p, 0, 1))->take(2)->implode('')) : '?';

    $navActive = function (string ...$patterns) {
        foreach ($patterns as $p) {
            if (request()->routeIs($p)) return true;
        }
        return false;
    };
@endphp

<aside class="w-52 bg-sidebar flex flex-col flex-shrink-0 z-10 h-screen sticky top-0">

    {{-- Logo --}}
    <div class="px-4 py-4 border-b border-sidebar-border">
        <x-app-wordmark variant="light" size="md" />
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto slim-scroll p-3 space-y-0.5 text-sm">

        @if($isGuest)
            {{-- ─── GUEST: flat 3-item nav ─── --}}
            <a href="{{ route('websites.index') }}"
               class="nav-btn flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium hover:bg-white/10 hover:text-white transition-all
                      {{ $navActive('websites.*') ? 'nav-active' : 'text-gray-300' }}">
                <x-icon name="globe" />
                Domains
            </a>
            <a href="{{ route('favorites.index') }}"
               class="nav-btn flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium hover:bg-white/10 hover:text-white transition-all
                      {{ $navActive('favorites.*') ? 'nav-active' : 'text-gray-300' }}">
                <x-icon name="star" />
                My Favorites
            </a>
            <a href="{{ route('orders.index') }}"
               class="nav-btn flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium hover:bg-white/10 hover:text-white transition-all
                      {{ $navActive('orders.*') ? 'nav-active' : 'text-gray-300' }}">
                <x-icon name="orders" />
                My Orders
            </a>

        @else
            {{-- ─── ADMIN/EDITOR: full nested nav ─── --}}
            <a href="{{ route('dashboard') }}"
               class="nav-btn flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium hover:bg-white/10 hover:text-white transition-all
                      {{ $navActive('dashboard') ? 'nav-active' : 'text-gray-300' }}">
                <x-icon name="dashboard" />
                Dashboard
            </a>

            {{-- Domains group --}}
            <div x-data="{ open: {{ $navActive('websites.*', 'contacts.*') ? 'true' : 'false' }} }">
                <div class="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-white/10 transition-all
                            {{ $navActive('websites.*') ? 'nav-active' : 'text-gray-300' }}">
                    <a href="{{ route('websites.index') }}" class="flex-1 inline-flex items-center gap-3 font-medium">
                        <x-icon name="globe" /> Domains
                    </a>
                    <button @click="open = !open" class="focus:outline-none p-1">
                        <x-icon ::name="open ? 'chevron-up' : 'chevron-down'" size="sm" />
                    </button>
                </div>
                <div x-show="open" x-cloak class="space-y-0.5 mt-1 ps-6">
                    <a href="{{ route('contacts.index') }}"
                       class="block px-3 py-2 rounded-lg hover:bg-white/10 transition-all
                              {{ $navActive('contacts.*') ? 'nav-active' : 'text-gray-300' }}">
                        <x-icon name="address-book" size="sm" class="me-2 inline" /> Publishers
                    </a>
                </div>
            </div>

            {{-- New Entries group --}}
            <div x-data="{ open: {{ $navActive('new_entries.*', 'historical_view.*') ? 'true' : 'false' }} }">
                <div class="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-white/10 transition-all
                            {{ $navActive('new_entries.*') ? 'nav-active' : 'text-gray-300' }}">
                    <a href="{{ route('new_entries.index') }}" class="flex-1 inline-flex items-center gap-3 font-medium">
                        <x-icon name="folder-plus" /> New Entries
                    </a>
                    <button @click="open = !open" class="focus:outline-none p-1">
                        <x-icon ::name="open ? 'chevron-up' : 'chevron-down'" size="sm" />
                    </button>
                </div>
                <div x-show="open" x-cloak class="space-y-0.5 mt-1 ps-6">
                    <a href="{{ route('historical_view.index') }}"
                       class="block px-3 py-2 rounded-lg hover:bg-white/10 transition-all
                              {{ $navActive('historical_view.*') ? 'nav-active' : 'text-gray-300' }}">
                        <x-icon name="history" size="sm" class="me-2 inline" /> Historical View
                    </a>
                </div>
            </div>

            {{-- Storages --}}
            <a href="{{ route('storages.index') }}"
               class="nav-btn flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium hover:bg-white/10 hover:text-white transition-all
                      {{ $navActive('storages.*') ? 'nav-active' : 'text-gray-300' }}">
                <x-icon name="warehouse" />
                Storages
            </a>

            {{-- Contacts --}}
            <a href="{{ route('clients.index') }}"
               class="nav-btn flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium hover:bg-white/10 hover:text-white transition-all
                      {{ $navActive('clients.*') ? 'nav-active' : 'text-gray-300' }}">
                <x-icon name="address-book" />
                Contacts
            </a>

            {{-- Companies --}}
            <a href="{{ route('companies.index') }}"
               class="nav-btn flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium hover:bg-white/10 hover:text-white transition-all
                      {{ $navActive('companies.*') ? 'nav-active' : 'text-gray-300' }}">
                <x-icon name="briefcase" />
                Companies
            </a>

            @if($isAdmin)
                {{-- Campaigns (Link Building CRM) --}}
                <a href="{{ route('crm.campaigns.index') }}"
                   class="nav-btn flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium hover:bg-white/10 hover:text-white transition-all
                          {{ $navActive('crm.campaigns.*', 'crm.companies.*', 'crm.clients.*', 'crm.publications.*') ? 'nav-active' : 'text-gray-300' }}">
                    <x-icon name="newspaper" />
                    Campaigns
                </a>
            @endif

            {{-- Copywriters --}}
            <a href="{{ route('copy.index') }}"
               class="nav-btn flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium hover:bg-white/10 hover:text-white transition-all
                      {{ $navActive('copy.*') ? 'nav-active' : 'text-gray-300' }}">
                <x-icon name="document" />
                Copywriters
            </a>

            <a href="{{ route('stats.publishers') }}"
               class="nav-btn flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium hover:bg-white/10 hover:text-white transition-all
                      {{ $navActive('stats.*') ? 'nav-active' : 'text-gray-300' }}">
                <x-icon name="chart-bar" />
                Stats
            </a>

            <a href="{{ route('admin.orders.index') }}"
               class="nav-btn flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium hover:bg-white/10 hover:text-white transition-all
                      {{ $navActive('admin.orders.*') ? 'nav-active' : 'text-gray-300' }}">
                <x-icon name="orders" />
                Orders
            </a>

            @if($isAdmin)
                <a href="{{ route('admin.users.index') }}"
                   class="nav-btn flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium hover:bg-white/10 hover:text-white transition-all
                          {{ $navActive('admin.users.*') ? 'nav-active' : 'text-gray-300' }}">
                    <x-icon name="users-cog" />
                    Manage Users
                </a>
            @endif

            {{-- Tools group --}}
            <div x-data="{ open: {{ $navActive('tools.*') ? 'true' : 'false' }} }">
                <div class="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-white/10 transition-all
                            {{ $navActive('tools.*') ? 'nav-active' : 'text-gray-300' }}">
                    <button @click="open = !open" class="flex-1 inline-flex items-center gap-3 font-medium select-none focus:outline-none">
                        <x-icon name="wrench" /> Tools
                    </button>
                    <button @click="open = !open" class="focus:outline-none p-1">
                        <x-icon ::name="open ? 'chevron-up' : 'chevron-down'" size="sm" />
                    </button>
                </div>
                <div x-show="open" x-cloak class="space-y-0.5 mt-1 ps-6">
                    <a href="{{ route('tools.discover') }}"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white/10 transition-all
                              {{ $navActive('tools.discover') ? 'nav-active' : 'text-gray-300' }}">
                        <x-icon name="search" size="sm" class="flex-shrink-0" /><span class="truncate">Discover Domains</span>
                    </a>
                    <a href="{{ route('tools.ahrefs.index') }}"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white/10 transition-all
                              {{ $navActive('tools.ahrefs.*') ? 'nav-active' : 'text-gray-300' }}">
                        <x-icon name="broom" size="sm" class="flex-shrink-0" /><span class="truncate">Clean Ahrefs CSV</span>
                    </a>
                    <a href="{{ route('tools.referring_domains.index') }}"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white/10 transition-all
                              {{ $navActive('tools.referring_domains.*') ? 'nav-active' : 'text-gray-300' }}">
                        <x-icon name="link" size="sm" class="flex-shrink-0" /><span class="truncate">Referring Domains</span>
                    </a>
                    <a href="{{ route('tools.traffic_distribution.index') }}"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white/10 transition-all
                              {{ $navActive('tools.traffic_distribution.*') ? 'nav-active' : 'text-gray-300' }}">
                        <x-icon name="globe-europe" size="sm" class="flex-shrink-0" /><span class="truncate">Batch Analysis</span>
                    </a>
                    <a href="{{ route('tools.keyword_research.index') }}"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white/10 transition-all
                              {{ $navActive('tools.keyword_research.*') ? 'nav-active' : 'text-gray-300' }}">
                        <x-icon name="key" size="sm" class="flex-shrink-0" /><span class="truncate">Keyword Research</span>
                    </a>
                </div>
            </div>
        @endif
    </nav>

    {{-- User + Logout --}}
    <div class="p-3 border-t border-sidebar-border">
        @if($user)
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-2 mb-2 rounded-lg py-1 hover:bg-white/10" title="My Profile">
                @if($user->avatar)
                    <img src="{{ $user->avatar }}" alt="" class="w-7 h-7 rounded-full object-cover flex-shrink-0 border border-white/20">
                @else
                    <div class="w-7 h-7 rounded-full bg-green-500/25 flex items-center justify-center text-green-400 text-xs font-bold flex-shrink-0">
                        {{ $initials }}
                    </div>
                @endif
                <div class="min-w-0">
                    <div class="text-white text-xs font-medium truncate">{{ $user->name }}</div>
                    <div class="text-gray-400 text-xs truncate">{{ $user->email }}</div>
                </div>
            </a>
        @endif
        {{-- Notification bell moved to the global topbar (layouts/partials/topbar.blade.php) --}}
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-gray-400 hover:bg-white/10 hover:text-white transition-all">
                <x-icon name="logout" />
                Logout
            </button>
        </form>
    </div>
</aside>
