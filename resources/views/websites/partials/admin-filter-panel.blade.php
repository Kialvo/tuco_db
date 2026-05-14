{{--
    Vertical filter panel for /websites admin view.
    All inputs preserve their original IDs so the existing JS in
    websites/index.blade.php (table.ajax.data callbacks, btnSearch/Clear handlers)
    keeps working unchanged.
--}}

<div class="px-4 py-3 border-b border-gray-100 flex-shrink-0">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="text-sm font-semibold text-gray-700">Filters</span>
            <span id="filterActiveBadge" class="hidden bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full"></span>
        </div>
        <button id="btnClear" type="button"
                class="text-xs text-red-500 hover:text-red-600 font-medium transition-colors">
            Clear all
        </button>
    </div>
    <div id="filterChipsBar" class="hidden mt-2 flex flex-wrap gap-1.5"></div>
</div>

<div id="filterForm" class="flex-1 overflow-y-auto slim-scroll p-4 space-y-4">

    {{-- ── Basic ── --}}
    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Domain</label>
        <input id="filterDomainName" type="text" placeholder="Type Domain" class="fi">
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Type</label>
        <select id="filterWebsiteType" class="fi">
            <option value="">— Any —</option>
            <option value="FORUM">Forum</option>
            <option value="GENERALIST">Generalist</option>
            <option value="VERTICAL">Vertical</option>
            <option value="LOCAL">Local</option>
        </select>
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Language</label>
        <select id="filterLanguage" class="fi">
            <option value="">— Any —</option>
            @foreach($languages as $lang)
                <option value="{{ $lang->id }}">{{ $lang->name }}</option>
            @endforeach
        </select>
    </div>

    @unless($isGuestUser)
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Status</label>
            <select id="filterStatus" class="fi">
                <option value="">— Any —</option>
                <option value="active">Active</option>
                <option value="past">Past</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Publisher</label>
            <select id="filterContact" class="fi">
                <option value="">— Any —</option>
                @foreach($contacts as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
    @endunless

    {{-- ── Geography ── --}}
    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Include Countries</label>
        <select id="filterCountriesInclude" multiple class="fi" size="4">
            @foreach($countries as $c)
                <option value="{{ $c->id }}">{{ $c->country_name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Exclude Countries</label>
        <select id="filterCountriesExclude" multiple class="fi" size="4">
            @foreach($countries as $c)
                <option value="{{ $c->id }}">{{ $c->country_name }}</option>
            @endforeach
        </select>
    </div>

    {{-- ── Pricing ── --}}
    <div class="pt-3 border-t border-gray-100">
        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-2">Pricing</p>
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Price (€)</label>
        <div class="mpair">
            <input type="number" step="0.01" id="filterPriceMin" placeholder="Min" class="fi">
            <input type="number" step="0.01" id="filterPriceMax" placeholder="Max" class="fi">
        </div>
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Sensitive Topic Price (€)</label>
        <div class="mpair">
            <input type="number" step="0.01" id="filterSensitiveTopicPriceMin" placeholder="Min" class="fi">
            <input type="number" step="0.01" id="filterSensitiveTopicPriceMax" placeholder="Max" class="fi">
        </div>
    </div>

    @unless($isGuestUser)
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Publisher (€)</label>
            <div class="mpair">
                <input type="number" id="filterPublisher_priceMin" placeholder="Min" class="fi">
                <input type="number" id="filterPublisher_priceMax" placeholder="Max" class="fi">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Kialvo Evaluation</label>
            <div class="mpair">
                <input type="number" id="filterKialvo_evaluationMin" placeholder="Min" class="fi">
                <input type="number" id="filterKialvo_evaluationMax" placeholder="Max" class="fi">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Profit (€)</label>
            <div class="mpair">
                <input type="number" id="filterProfitMin" placeholder="Min" class="fi">
                <input type="number" id="filterProfitMax" placeholder="Max" class="fi">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Banner (€)</label>
            <div class="mpair">
                <input type="number" step="0.01" id="filterBannerMin" placeholder="Min" class="fi">
                <input type="number" step="0.01" id="filterBannerMax" placeholder="Max" class="fi">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Site-wide (€)</label>
            <div class="mpair">
                <input type="number" step="0.01" id="filterSWMin" placeholder="Min" class="fi">
                <input type="number" step="0.01" id="filterSWMax" placeholder="Max" class="fi">
            </div>
        </div>
    @endunless

    {{-- ── Authority ── --}}
    <div class="pt-3 border-t border-gray-100">
        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-2">Authority Metrics</p>
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">DA</label>
        <div class="mpair">
            <input type="number" id="filterDAMin" placeholder="Min" class="fi">
            <input type="number" id="filterDAMax" placeholder="Max" class="fi">
        </div>
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">PA</label>
        <div class="mpair">
            <input type="number" id="filterPAMin" placeholder="Min" class="fi">
            <input type="number" id="filterPAMax" placeholder="Max" class="fi">
        </div>
    </div>

    @unless($isGuestUser)
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">TF</label>
            <div class="mpair">
                <input type="number" id="filterTFMin" placeholder="Min" class="fi">
                <input type="number" id="filterTFMax" placeholder="Max" class="fi">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">CF</label>
            <div class="mpair">
                <input type="number" id="filterCFMin" placeholder="Min" class="fi">
                <input type="number" id="filterCFMax" placeholder="Max" class="fi">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">DR</label>
            <div class="mpair">
                <input type="number" id="filterDRMin" placeholder="Min" class="fi">
                <input type="number" id="filterDRMax" placeholder="Max" class="fi">
            </div>
        </div>
    @endunless

    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">UR</label>
        <div class="mpair">
            <input type="number" id="filterURMin" placeholder="Min" class="fi">
            <input type="number" id="filterURMax" placeholder="Max" class="fi">
        </div>
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">ZA</label>
        <div class="mpair">
            <input type="number" id="filterZAMin" placeholder="Min" class="fi">
            <input type="number" id="filterZAMax" placeholder="Max" class="fi">
        </div>
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">AS</label>
        <div class="mpair">
            <input type="number" id="filterASMin" placeholder="Min" class="fi">
            <input type="number" id="filterASMax" placeholder="Max" class="fi">
        </div>
    </div>

    @unless($isGuestUser)
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">TF vs CF</label>
            <div class="mpair">
                <input type="number" id="filterTF_vS_cfMin" placeholder="Min" class="fi">
                <input type="number" id="filterTF_vS_cfMax" placeholder="Max" class="fi">
            </div>
        </div>
    @endunless

    {{-- ── Traffic ── --}}
    <div class="pt-3 border-t border-gray-100">
        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-2">Traffic Metrics</p>
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Semrush Traffic</label>
        <div class="mpair">
            <input type="number" id="filterSemrush_trafficMin" placeholder="Min" class="fi">
            <input type="number" id="filterSemrush_trafficMax" placeholder="Max" class="fi">
        </div>
    </div>

    @unless($isGuestUser)
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Ahrefs KW</label>
            <div class="mpair">
                <input type="number" id="filterAhrefs_keywordMin" placeholder="Min" class="fi">
                <input type="number" id="filterAhrefs_keywordMax" placeholder="Max" class="fi">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Ahrefs Traffic</label>
            <div class="mpair">
                <input type="number" id="filterAhrefs_trafficMin" placeholder="Min" class="fi">
                <input type="number" id="filterAhrefs_trafficMax" placeholder="Max" class="fi">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">KW vs Traffic</label>
            <div class="mpair">
                <input type="number" id="filterKeyword_vs_trafficMin" placeholder="Min" class="fi">
                <input type="number" id="filterKeyword_vs_trafficMax" placeholder="Max" class="fi">
            </div>
        </div>
    @endunless

    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">MS</label>
        <div class="mpair">
            <input type="number" id="filterMSMin" placeholder="Min" class="fi">
            <input type="number" id="filterMSMax" placeholder="Max" class="fi">
        </div>
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Organic Keywords</label>
        <div class="mpair">
            <input type="number" id="filterOrganicKWMin" placeholder="Min" class="fi">
            <input type="number" id="filterOrganicKWMax" placeholder="Max" class="fi">
        </div>
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Organic Traffic</label>
        <div class="mpair">
            <input type="number" id="filterOrganicTRMin" placeholder="Min" class="fi">
            <input type="number" id="filterOrganicTRMax" placeholder="Max" class="fi">
        </div>
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">KW/TR Ratio</label>
        <div class="mpair">
            <input type="number" step="0.01" id="filterKWTRRatioMin" placeholder="Min" class="fi">
            <input type="number" step="0.01" id="filterKWTRRatioMax" placeholder="Max" class="fi">
        </div>
    </div>

    {{-- ── Categories ── --}}
    <div class="pt-3 border-t border-gray-100">
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Categories</label>
        <select id="filterCategories" multiple class="fi" size="4">
            @foreach($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- ── Toggles ── --}}
    <div class="pt-3 border-t border-gray-100 space-y-3">
        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Content flags</p>

        @php
            $toggleFilters = $isGuestUser
                ? ['trading']
                : ['betting','trading','permanent_link','more_than_one_link','copywriting','no_sponsored_tag','social_media_sharing','post_in_homepage'];
        @endphp
        @foreach($toggleFilters as $chk)
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm text-gray-700 capitalize">{{ str_replace('_',' ', $chk) }}</span>
                <label class="toggle-switch">
                    <input type="checkbox" id="filter{{ ucfirst($chk) }}">
                    <span class="toggle-track"></span>
                </label>
            </div>
        @endforeach

        @unless($isGuestUser)
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm text-gray-700">No publisher</span>
                <label class="toggle-switch">
                    <input type="checkbox" id="filterNoContact">
                    <span class="toggle-track"></span>
                </label>
            </div>

            <div class="flex items-center justify-between gap-3">
                <span class="text-sm text-gray-700">Show deleted</span>
                <label class="toggle-switch">
                    <input type="checkbox" id="filterShowDeleted">
                    <span class="toggle-track"></span>
                </label>
            </div>
        @endunless
    </div>
</div>

{{-- Sticky search button at bottom --}}
<div class="px-4 py-3 border-t border-gray-100 flex-shrink-0 bg-white">
    <button id="btnSearch" type="button"
            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
        <x-icon name="search" size="sm" /> Search
    </button>
</div>
