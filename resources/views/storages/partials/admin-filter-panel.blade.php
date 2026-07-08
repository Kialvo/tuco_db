{{-- Vertical filter panel for /storages. All input IDs preserved. --}}
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
    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Publication From</label>
        <input type="date" id="filterPublicationFrom" class="fi">
    </div>
    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Publication To</label>
        <input type="date" id="filterPublicationTo" class="fi">
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Status</label>
        <select id="filterStatus" class="fi">
            <option value="">— Any —</option>
            @foreach(\App\Support\PublicationStatus::grouped() as $group => $statuses)
                <optgroup label="{{ $group }}">
                    @foreach($statuses as $slug => $label)
                        <option value="{{ $slug }}">{{ $label }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Language</label>
        <select id="filterLanguage" class="fi">
            <option value="">— Any —</option>
            @foreach($languages as $l)
                <option value="{{ $l->id }}">{{ $l->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Country</label>
        <select id="filterCountry" class="fi">
            <option value="">— Any —</option>
            @foreach($countries as $c)
                <option value="{{ $c->id }}">{{ $c->country_name }}</option>
            @endforeach
        </select>
    </div>

    <div class="pt-3 border-t border-gray-100">
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Copywriter</label>
        <select id="filterCopy" class="fi">
            <option value="">— Any —</option>
            @foreach($copies as $cp)
                <option value="{{ $cp->id }}">{{ $cp->copy_val }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Contact</label>
        <select id="filterClient" class="fi">
            <option value="">— Any —</option>
            @foreach($clients as $cl)
                <option value="{{ $cl->id }}">{{ $cl->first_name }} {{ $cl->last_name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Company</label>
        <input type="text" id="filterCompany" placeholder="Search company..." class="fi">
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Publisher</label>
        <select id="filterContact" class="fi">
            <option value="">— Any —</option>
            @foreach($contacts as $contact)
                <option value="{{ $contact->id }}">
                    {{ $contact->name }}@if($contact->email) ({{ $contact->email }})@endif
                </option>
            @endforeach
        </select>
    </div>

    <div class="pt-3 border-t border-gray-100">
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Domain (publisher site)</label>
        <input type="text" id="filterWebsiteDomain" placeholder="publisher-site.com" class="fi">
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Target Domain</label>
        <input type="text" id="filterCampaign" placeholder="domain.com" class="fi">
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Campaign Code</label>
        <input type="text" id="filterCampaignCode" placeholder="code" class="fi">
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Invoice Menford NR</label>
        <input type="text" id="filterInvoiceMenfordNr" placeholder="number" class="fi">
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Bill Publisher Name</label>
        <input type="text" id="filterBillPublisherName" placeholder="publisher" class="fi">
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Link URL</label>
        <input type="text" id="filterTargetUrl" placeholder="full url" class="fi">
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Article URL</label>
        <input type="text" id="filterArticleUrl" placeholder="full url" class="fi">
    </div>

    <div class="pt-3 border-t border-gray-100">
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Categories</label>
        <select id="filterCategories" multiple class="fi" size="4">
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="px-4 py-3 border-t border-gray-100 flex-shrink-0 bg-white">
    <button id="btnSearch" type="button"
            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
        <x-icon name="search" size="sm" /> Search
    </button>
</div>
