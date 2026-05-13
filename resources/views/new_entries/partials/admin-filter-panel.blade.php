{{-- Vertical filter panel for /new-entries (admin/editor view). All input IDs preserved. --}}
<div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
    <span class="text-sm font-semibold text-gray-700">Filters</span>
    <button id="btnClear" type="button"
            class="text-xs text-red-500 hover:text-red-600 font-medium transition-colors">
        Clear all
    </button>
</div>

<div id="filterForm" class="flex-1 overflow-y-auto slim-scroll p-4 space-y-4">
    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Domain</label>
        <input id="filterDomainName" type="text" placeholder="example.com" class="fi">
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Status</label>
        <select id="filterStatus" class="fi">
            <option value="">— Any —</option>
            <option value="never_opened">Never Opened</option>
            <option value="read_but_never_answered">Read but never answered</option>
            <option value="waiting_for_first_answer">Waiting for 1st answer</option>
            <option value="refused_by_us">Refused by us</option>
            <option value="publisher_refused">Publisher refused</option>
            <option value="negotiation">Negotiation</option>
            <option value="active">Active</option>
        </select>
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Country</label>
        <select id="filterCountries" class="fi">
            <option value="">— Any —</option>
            @foreach($countries as $c)
                <option value="{{ $c->id }}">{{ $c->country_name }}</option>
            @endforeach
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

    <div class="pt-3 border-t border-gray-100">
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">1st Contact (from)</label>
        <input id="filterFirstFrom" type="text" placeholder="YYYY-MM-DD" class="fi">
    </div>
    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">1st Contact (to)</label>
        <input id="filterFirstTo" type="text" placeholder="YYYY-MM-DD" class="fi">
    </div>
</div>

<div class="px-4 py-3 border-t border-gray-100 flex-shrink-0 bg-white">
    <button id="btnSearch" type="button"
            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
        <x-icon name="search" size="sm" /> Search
    </button>
</div>
