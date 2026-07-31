{{--
    Bulk Add to Campaign — creates one publication (storage row) per selected
    domain. Rendered ONLY for users passing the bulk-add-to-campaign Gate; the
    parent view wraps the @include, the button and the script in @can, so for
    everyone else this markup never reaches the browser.

    The Amount column is READ-ONLY by design: the server reads the price from
    the domain record based on the chosen price type, so no amount is ever sent
    from the browser.

    The campaign list is loaded here rather than in WebsiteController so the
    query runs ONLY for gated users and the shared Domains controller stays
    untouched (soft-deleted campaigns excluded by the model's SoftDeletes).
--}}
@php
    $bacCampaigns = \App\Models\Campaign::orderBy('code')->get(['id', 'code']);
@endphp
<div id="bulkAddCampaignModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl flex flex-col max-h-[90vh]">

        {{-- Header --}}
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
            <h3 class="text-sm font-semibold text-gray-800">Bulk Add to Campaign</h3>
            <button id="bacCloseTop" type="button" class="text-gray-500 hover:text-gray-700" aria-label="Close">
                <x-icon name="x" size="sm" class="inline" />
            </button>
        </div>

        {{-- Campaign + apply-to-all --}}
        <div class="px-4 py-3 border-b border-gray-200 space-y-3 text-xs flex-shrink-0">
            <div>
                <label class="block text-gray-700 font-medium mb-1">Campaign</label>
                <select id="bacCampaign" class="w-full border border-gray-300 rounded px-2 py-2">
                    <option value="">— Select a campaign —</option>
                    @foreach($bacCampaigns as $camp)
                        <option value="{{ $camp->id }}">{{ $camp->code }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-1">
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Set all statuses</label>
                    <select id="bacStatusAll" class="w-full border border-gray-300 rounded px-2 py-2">
                        <option value="">— Leave as is —</option>
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
                    <label class="block text-gray-700 font-medium mb-1">Set price type for all</label>
                    <div class="flex items-center gap-4 pt-2">
                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="bacPriceTypeAll" value="price" class="text-sky-600">
                            <span>Price</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="bacPriceTypeAll" value="sensitive_topic_price" class="text-sky-600">
                            <span>Sensitive Topic Price</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Rows --}}
        <div class="flex-1 overflow-y-auto px-4 py-3">
            <table class="w-full text-xs">
                <thead class="text-gray-500 uppercase tracking-wider text-[10px]">
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-2 font-semibold">Domain</th>
                        <th class="text-left py-2 font-semibold w-56">Status</th>
                        <th class="text-left py-2 font-semibold w-56">Price Type</th>
                        <th class="text-right py-2 font-semibold w-28">Amount (€)</th>
                    </tr>
                </thead>
                <tbody id="bacRows"></tbody>
            </table>
            <div id="bacLoading" class="hidden py-6 text-center text-gray-500">Loading domains…</div>
        </div>

        {{-- Footer --}}
        <div class="px-4 py-3 border-t border-gray-200 flex items-center justify-between flex-shrink-0 bg-gray-50 rounded-b-2xl">
            <div class="text-xs text-gray-600">
                <span id="bacReadyCount" class="font-semibold text-gray-800">0</span> ready
                · <span id="bacBlockedCount" class="font-semibold text-gray-800">0</span> blocked
            </div>
            <div class="flex items-center gap-2">
                <button id="bacCancel" type="button"
                        class="px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button id="bacConfirm" type="button" disabled
                        class="px-3 py-2 text-xs font-semibold text-white bg-sky-600 rounded-lg hover:bg-sky-700
                               disabled:opacity-50 disabled:cursor-not-allowed">
                    Create publications
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Status options reused when building each row (kept out of the JS string) --}}
<template id="bacStatusTemplate">
    <select class="bac-status w-full border border-gray-300 rounded px-2 py-1.5 text-xs">
        @foreach(\App\Support\PublicationStatus::grouped() as $group => $statuses)
            <optgroup label="{{ $group }}">
                @foreach($statuses as $slug => $label)
                    <option value="{{ $slug }}">{{ $label }}</option>
                @endforeach
            </optgroup>
        @endforeach
    </select>
</template>
