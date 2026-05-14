@php
    // â‘  Just list EVERY real db column once.
    //    If tomorrow you add a new column, drop its name here and
    //    (optionally) extend bulkMeta below with a prettier <select>.
    $bulkEditable = [
        // id is NOT editable, everything else is:
        'status','country_id','language_id','linkbuilder','type_of_website',
        'contact_id','currency_code','publisher_price','no_follow_price',
        'special_topic_price','link_insertion_price','banner_price','sitewide_link_price',
        'kialvo_evaluation','profit','date_publisher_price',
        'DA','PA','TF','CF','DR','UR','ZA','as_metric','seozoom',
        'TF_vs_CF','semrush_traffic','ahrefs_keyword','ahrefs_traffic',
        'keyword_vs_traffic','seo_metrics_date',
        'betting','trading','permanent_link','more_than_one_link',
        'copywriting','no_sponsored_tag','social_media_sharing','post_in_homepage',
        'category_ids',              // m-m
        'recalculate_totals',        // pseudo
    ];
    $isGuestUser = auth()->check() && auth()->user()->isGuest();
    $adminExportColumns = [
        'id' => 'ID',
        'domain_name' => 'Domain',
        'notes' => 'Notes',
        'extra_notes' => 'Internal Notes',
        'status' => 'Status',
        'country_name' => 'Country',
        'language_name' => 'Language',
        'contact_name' => 'Publisher',
        'currency_code' => 'Currency',
        'publisher_price' => 'Publisher Price',
        'no_follow_price' => 'No Follow Price',
        'special_topic_price' => 'Special Topic Price',
        'price' => 'Price',
        'sensitive_topic_price' => 'Sensitive Topic Price',
        'link_insertion_price' => 'Link Insertion Price',
        'banner_price' => 'Banner EUR',
        'sitewide_link_price' => 'Site-wide EUR',
        'kialvo_evaluation' => 'Kialvo Evaluation',
        'profit' => 'Profit',
        'date_publisher_price' => 'Date Publisher Price',
        'linkbuilder' => 'Linkbuilder',
        'type_of_website' => 'Type of Website',
        'categories_list' => 'Categories',
        'DA' => 'DA',
        'PA' => 'PA',
        'TF' => 'TF',
        'CF' => 'CF',
        'DR' => 'DR',
        'UR' => 'UR',
        'ZA' => 'ZA',
        'as_metric' => 'AS',
        'seozoom' => 'SEO Zoom',
        'TF_vs_CF' => 'TF vs CF',
        'semrush_traffic' => 'Semrush Traffic',
        'ahrefs_keyword' => 'Ahrefs Keyword',
        'ahrefs_traffic' => 'Ahrefs Traffic',
        'keyword_vs_traffic' => 'Keywords vs Traffic',
        'ms'               => 'MS',
        'organic_keywords' => 'Organic Keywords',
        'organic_traffic'  => 'Organic Traffic',
        'kw_traffic_ratio' => 'KW/Traffic Ratio',
        'seo_metrics_date' => 'SEO Metrics Date',
        'betting' => 'Betting',
        'trading' => 'Trading',
        'permanent_link' => 'LINK LIFETIME',
        'more_than_one_link' => 'More than 1 link',
        'copywriting' => 'Copywriting',
        'no_sponsored_tag' => 'Sponsored Tag',
        'social_media_sharing' => 'Social Media Sharing',
        'post_in_homepage' => 'Post in Homepage',
        'created_at' => 'Date Added',
    ];
@endphp

@extends('layouts.dashboard')
@section('title', 'Domains')

{{-- ─── Page header (top bar) ─── --}}
@section('pageHeader')
    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between flex-shrink-0">
        <div>
            <h1 class="text-base font-bold text-gray-800">Domains</h1>
            <p class="text-xs text-gray-500 mt-0.5">Manage your domain inventory.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            @if($isGuestUser)
                <button id="btnFavoritesToggle"
                        class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded-lg hover:bg-amber-100">
                    <x-icon name="star" size="sm" class="inline me-0.5" /> My Favorites
                </button>
                <button type="button"
                        id="btnOpenCart"
                        onclick="window.LIBCart && window.LIBCart.openDrawer()"
                        class="relative inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-sm">
                    <x-icon name="cart" size="sm" />
                    <span>Current Order</span>
                    <span id="cartCountBadge"
                          class="ml-1 hidden items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full bg-white text-green-700 text-[11px] font-bold leading-none">0</span>
                </button>
            @endif

            <a href="#" id="btnExportCsv"
               class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300">
                <x-icon name="download" size="sm" /> Export CSV
            </a>
            <a href="#" id="btnExportPdf"
               class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300">
                <x-icon name="document-pdf" size="sm" /> Export PDF
            </a>
            @unless($isGuestUser)
                <a href="{{ route('websites.import.index') }}" id="btnImportCsv"
                   class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300">
                    <x-icon name="upload" size="sm" /> Import CSV
                </a>
                <a href="{{ route('websites.create') }}"
                   class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-sm">
                    <x-icon name="plus" size="sm" /> Create Domain
                </a>
            @endunless
        </div>
    </div>
@endsection

{{-- ─── Sticky left filter panel ─── --}}
@section('filters')
    @include('websites.partials.admin-filter-panel')
@endsection

@section('content')
    <div class="px-6 py-4 bg-gray-50 min-h-screen text-xs">
        @unless($isGuestUser)
            <div id="websiteExportPicker"
                 class="hidden fixed top-20 right-6 z-40 w-full max-w-3xl">
                <div class="w-full rounded-xl border border-gray-200 bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                        <p id="websiteExportPickerTitle" class="text-sm font-semibold text-gray-700">
                            Choose columns to export
                        </p>
                        <button type="button" id="websiteExportClose"
                                class="rounded px-2 py-1 text-xs text-gray-500 hover:bg-gray-100 hover:text-gray-700">
                            Close
                        </button>
                    </div>
                    <div class="border-b border-gray-200 px-4 py-2">
                        <label class="inline-flex items-center gap-2 text-xs text-gray-700">
                            <input type="checkbox" id="websiteExportSelectAll" checked
                                   class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                            Select all columns
                        </label>
                    </div>
                    <div class="grid max-h-[55vh] grid-cols-1 gap-2 overflow-y-auto p-4 sm:grid-cols-2 md:grid-cols-3">
                        @foreach($adminExportColumns as $key => $label)
                            <label class="inline-flex items-center gap-2 text-xs text-gray-700">
                                <input type="checkbox" class="website-export-field rounded border-gray-300 text-green-600 focus:ring-green-500"
                                       value="{{ $key }}" checked>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="flex items-center justify-end gap-2 border-t border-gray-200 px-4 py-3">
                        <button type="button" id="websiteExportCancel"
                                class="rounded border border-gray-300 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-100">
                            Cancel
                        </button>
                        <button type="button" id="websiteExportConfirm"
                                class="rounded bg-green-600 px-3 py-1.5 text-xs text-white hover:bg-green-700">
                            Continue Export
                        </button>
                    </div>
                </div>
            </div>
        @endunless

        {{-- Hidden no-op placeholder so existing JS that targets #toggleFiltersBtn doesn't error --}}
        <button id="toggleFiltersBtn" class="hidden" aria-hidden="true"></button>


        {{-- â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ TABLE ACTION BAR â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
        @unless($isGuestUser)
        <div id="actionBar"
             class="flex items-center flex-wrap gap-2 mb-3 px-4 py-2.5 bg-white border border-gray-200 rounded-xl shadow-card">
            {{-- Bulk Edit --}}
            <button id="btnBulkEdit"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium
                           bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200
                           disabled:opacity-50 disabled:cursor-not-allowed">
                <x-icon name="pencil" size="sm" /> Bulk Edit
            </button>

            <button id="btnBulkRestore"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium
                           bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200
                           disabled:opacity-50 disabled:cursor-not-allowed">
                <x-icon name="history" size="sm" /> Restore
            </button>

            <button id="btnBulkOutreach"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium
                           bg-green-50 text-green-700 hover:bg-green-100 border border-green-200
                           disabled:opacity-50 disabled:cursor-not-allowed">
                <x-icon name="paper-plane" size="sm" /> Bulk Outreach
            </button>

            <span class="h-5 w-px bg-gray-200 mx-1"></span>

            <button id="btnSyncDataForSeo"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium
                           bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200">
                <x-icon name="satellite" size="sm" /> Sync DataforSEO
            </button>

            <span class="ml-auto text-xs text-gray-500">
                Selected: <span id="selCount" class="font-semibold text-gray-800">0</span>
            </span>
        </div>
        @endunless

        <div id="websitesTableSearchWrap" class="table-search-wrap">
            <div class="flex items-center w-72 border border-gray-300 rounded-md bg-white shadow-sm
                        focus-within:ring-1 focus-within:ring-green-500 focus-within:border-green-500">
                <span class="px-3 text-gray-400 text-base leading-none">
                    <x-icon name="search" size="sm" class="inline" />
                </span>
                <input id="websitesTableSearch" type="text"
                       class="w-full bg-transparent border-0 focus:ring-0 focus:outline-none py-2 pr-3 text-sm leading-5"
                       placeholder="Search domains...">
            </div>
        </div>

        <!-- TABLE WRAPPER -->
        <div class="bg-white border border-gray-200 rounded-xl shadow-card">

            <table id="websitesTable" class="text-xs text-gray-700 w-full min-w-[1500px]">
                <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-[12px] uppercase text-gray-500 tracking-wider">
                    <th class="px-4 py-2">
                        <input type="checkbox" id="chkAll" class="form-checkbox h-4 w-4 text-green-600">
                    </th>

                    <th class="whitespace-nowrap px-4 py-2">ID</th>
                    @if($isGuestUser)
                        <th class="whitespace-nowrap px-4 py-2 text-center">Fav</th>
                        <th class="whitespace-nowrap px-4 py-2 text-center">Order</th>
                    @endif
                    <th class="whitespace-nowrap px-4 py-2">Domain</th>
                    <th class="whitespace-nowrap px-4 py-2">Notes</th>
                    @unless($isGuestUser)
                        <th class="whitespace-nowrap px-4 py-2">Internal Notes</th>
                    @endunless
                    <th class="whitespace-nowrap px-4 py-2">Status</th>
                    <th class="whitespace-nowrap px-4 py-2">Country</th>
                    <th class="whitespace-nowrap px-4 py-2">Language</th>
                    <th class="whitespace-nowrap px-4 py-2">Publisher</th>
                    <th class="whitespace-nowrap px-4 py-2">Currency</th>
                    <th class="whitespace-nowrap px-4 py-2">Publisher Price</th>
                    <th class="whitespace-nowrap px-4 py-2">No Follow Price</th>
                    <th class="whitespace-nowrap px-4 py-2">Special Topic Price</th>
                    <th class="whitespace-nowrap px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            Price
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-green-600 text-[11px]"
                                        data-info="This is the final amount you pay for placement on this website, including our service fee."
                                        aria-label="What is Price?">
                                    <x-icon name="info" size="sm" class="inline" />
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case whitespace-normal break-words font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    This is the final amount you pay for placement on this website, including our service fee.
                                </span>
                            </span>
                        </span>
                    </th>
                    <th class="whitespace-nowrap px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            Sensitive Topic Price
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-green-600 text-[11px]"
                                        data-info="This is the final amount you pay for publishing content in sensitive niches (e.g. gambling, crypto, adult, dating, CBD, etc.), including our service fee."
                                        aria-label="What is Sensitive Topic Price?">
                                    <x-icon name="info" size="sm" class="inline" />
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case whitespace-normal break-words font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    This is the final amount you pay for publishing content in sensitive niches (e.g. gambling, crypto, adult, dating, CBD, etc.), including our service fee.
                                </span>
                            </span>
                        </span>
                    </th>
                    <th class="whitespace-nowrap px-4 py-2">Link Insertion Price</th>
                    <th class="whitespace-nowrap px-4 py-2">Banner &euro;</th>
                    <th class="whitespace-nowrap px-4 py-2">Site-wide &euro;</th>
                    @unless($isGuestUser)
                    {{-- Kialvo Evaluation (data key stays kialvo_evaluation) --}}
                    <th class="whitespace-nowrap px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            Kialvo Evaluation
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-green-600 text-[11px]"
                                        data-info="Kialvo Evaluation is the final amount you pay for placement on this website, including our service fee."
                                        aria-label="What is Kialvo Evaluation?">
                                    <x-icon name="info" size="sm" class="inline" />
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case whitespace-normal break-words font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    Kialvo Evaluation is the final amount you pay for placement on this website, including our service fee.
                                </span>
                            </span>
                        </span>
                    </th>
                    @endunless
                    <th class="whitespace-nowrap px-4 py-2">Profit</th>
                    <th class="whitespace-nowrap px-4 py-2">Date Publisher Price</th>
                    <th class="whitespace-nowrap px-4 py-2">Linkbuilder</th>
                    <th class="whitespace-nowrap px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            Type of Website
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-green-600 text-[11px]"
                                        data-info="Vertical: focused on one topic. Local: focused on a city/area. Generalist: covers many topics."
                                        aria-label="What is Type of Website?">
                                    <x-icon name="info" size="sm" class="inline" />
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case whitespace-normal break-words font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    Vertical: focused on one topic. Local: focused on a city/area. Generalist: covers many topics.
                                </span>
                            </span>
                        </span>
                    </th>
                    <th class="whitespace-nowrap px-4 py-2">Categories</th>
                    <th class="whitespace-nowrap px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            DA
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-green-600 text-[11px]"
                                        data-info="Domain Authority (Moz): ranking score 1-100. Higher DA usually passes more link value; 30+ good, 50+ excellent, 70+ premium."
                                        aria-label="What is DA?">
                                    <x-icon name="info" size="sm" class="inline" />
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case whitespace-normal break-words font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    Domain Authority (Moz): ranking score 1-100. Higher DA usually passes more link value; 30+ good, 50+ excellent, 70+ premium.
                                </span>
                            </span>
                        </span>
                    </th>
                    <th class="whitespace-nowrap px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            PA
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-green-600 text-[11px]"
                                        data-info="Page Authority (Moz): predicts ranking strength of a specific page on a 1-100 scale. Higher is better."
                                        aria-label="What is PA?">
                                    <x-icon name="info" size="sm" class="inline" />
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case whitespace-normal break-words font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    Page Authority (Moz): predicts ranking strength of a specific page on a 1-100 scale. Higher is better.
                                </span>
                            </span>
                        </span>
                    </th>
                    <th class="whitespace-nowrap px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            TF
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-green-600 text-[11px]"
                                        data-info="Trust Flow (Majestic): backlink quality score on 0-100. Higher is better; TF 20+ is typically reliable."
                                        aria-label="What is TF?">
                                    <x-icon name="info" size="sm" class="inline" />
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case whitespace-normal break-words font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    Trust Flow (Majestic): backlink quality score on 0-100. Higher is better; TF 20+ is typically reliable.
                                </span>
                            </span>
                        </span>
                    </th>
                    <th class="whitespace-nowrap px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            CF
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-green-600 text-[11px]"
                                        data-info="Citation Flow (Majestic): backlink quantity influence score on 0-100. Higher is better, especially when TF is close to or above CF."
                                        aria-label="What is CF?">
                                    <x-icon name="info" size="sm" class="inline" />
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case whitespace-normal break-words font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    Citation Flow (Majestic): backlink quantity influence score on 0-100. Higher is better, especially when TF is close to or above CF.
                                </span>
                            </span>
                        </span>
                    </th>
                    <th class="whitespace-nowrap px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            DR
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-green-600 text-[11px]"
                                        data-info="Domain Rating (Ahrefs): backlink profile strength from 0 to 100. Higher DR means stronger authority; 40+ is solid."
                                        aria-label="What is DR?">
                                    <x-icon name="info" size="sm" class="inline" />
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case whitespace-normal break-words font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    Domain Rating (Ahrefs): backlink profile strength from 0 to 100. Higher DR means stronger authority; 40+ is solid.
                                </span>
                            </span>
                        </span>
                    </th>
                    <th class="whitespace-nowrap px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            UR
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-green-600 text-[11px]"
                                        data-info="URL Rating (Ahrefs): strength of the target page backlink profile on a 0-100 scale. Higher is better."
                                        aria-label="What is UR?">
                                    <x-icon name="info" size="sm" class="inline" />
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case whitespace-normal break-words font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    URL Rating (Ahrefs): strength of the target page backlink profile on a 0-100 scale. Higher is better.
                                </span>
                            </span>
                        </span>
                    </th>
                    <th class="whitespace-nowrap px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            ZA
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-green-600 text-[11px]"
                                        data-info="Zoom Authority (SEOZoom): domain authority metric focused on Italian SERPs, on a 0-100 scale."
                                        aria-label="What is ZA?">
                                    <x-icon name="info" size="sm" class="inline" />
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case whitespace-normal break-words font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    Zoom Authority (SEOZoom): domain authority metric focused on Italian SERPs, on a 0-100 scale.
                                </span>
                            </span>
                        </span>
                    </th>
                    <th class="whitespace-nowrap px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            AS
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-green-600 text-[11px]"
                                        data-info="Authority Score (Semrush): overall domain quality score (0-100) based on backlinks, traffic, and trust signals."
                                        aria-label="What is AS?">
                                    <x-icon name="info" size="sm" class="inline" />
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case whitespace-normal break-words font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    Authority Score (Semrush): overall domain quality score (0-100) based on backlinks, traffic, and trust signals.
                                </span>
                            </span>
                        </span>
                    </th>
                    <th class="whitespace-nowrap px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            SEO Zoom
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-green-600 text-[11px]"
                                        data-info="SEOZoom Traffic: estimated organic traffic from SEOZoom, especially useful for Italian-market visibility."
                                        aria-label="What is SEO Zoom?">
                                    <x-icon name="info" size="sm" class="inline" />
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case whitespace-normal break-words font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    SEOZoom Traffic: estimated organic traffic from SEOZoom, especially useful for Italian-market visibility.
                                </span>
                            </span>
                        </span>
                    </th>
                    <th class="whitespace-nowrap px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            TF vs CF
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-green-600 text-[11px]"
                                        data-info="Ratio between Trust Flow and Citation Flow. Close to 1 is ideal; TF > CF suggests stronger quality, CF > TF may indicate spammy links."
                                        aria-label="What is TF vs CF?">
                                    <x-icon name="info" size="sm" class="inline" />
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case whitespace-normal break-words font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    Ratio between Trust Flow and Citation Flow. Close to 1 is ideal; TF > CF suggests stronger quality, CF > TF may indicate spammy links.
                                </span>
                            </span>
                        </span>
                    </th>
                    <th class="whitespace-nowrap px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            Semrush Traffic
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-green-600 text-[11px]"
                                        data-info="Estimated monthly organic visitors from Semrush. Higher traffic means more visibility; 5k+ good, 50k+ excellent."
                                        aria-label="What is Semrush Traffic?">
                                    <x-icon name="info" size="sm" class="inline" />
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case whitespace-normal break-words font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    Estimated monthly organic visitors from Semrush. Higher traffic means more visibility; 5k+ good, 50k+ excellent.
                                </span>
                            </span>
                        </span>
                    </th>
                    <th class="whitespace-nowrap px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            Ahrefs Keyword
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-green-600 text-[11px]"
                                        data-info="Number of keywords the domain ranks for. More keywords usually mean stronger organic visibility; 1k+ is strong."
                                        aria-label="What is Ahrefs Keyword?">
                                    <x-icon name="info" size="sm" class="inline" />
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case whitespace-normal break-words font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    Number of keywords the domain ranks for. More keywords usually mean stronger organic visibility; 1k+ is strong.
                                </span>
                            </span>
                        </span>
                    </th>
                    <th class="whitespace-nowrap px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            Ahrefs Traffic
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-green-600 text-[11px]"
                                        data-info="Estimated monthly organic visitors from Ahrefs. Higher traffic means more exposure; 5k+ good, 50k+ excellent."
                                        aria-label="What is Ahrefs Traffic?">
                                    <x-icon name="info" size="sm" class="inline" />
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case whitespace-normal break-words font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    Estimated monthly organic visitors from Ahrefs. Higher traffic means more exposure; 5k+ good, 50k+ excellent.
                                </span>
                            </span>
                        </span>
                    </th>
                    <th class="whitespace-nowrap px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            Keywords vs Traffic
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-green-600 text-[11px]"
                                        data-info="Traffic efficiency per keyword. Higher means each keyword brings more visits; low ratios may suggest weak rankings."
                                        aria-label="What is Keywords vs Traffic?">
                                    <x-icon name="info" size="sm" class="inline" />
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case whitespace-normal break-words font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    Traffic efficiency per keyword. Higher means each keyword brings more visits; low ratios may suggest weak rankings.
                                </span>
                            </span>
                        </span>
                    </th>
                    <th class="whitespace-nowrap px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            MS
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-green-600 text-[11px]"
                                        data-info="Menford Score: proprietary authority score (0–1,000) based on a weighted average of backlink profile strength across multiple competitive intelligence sources. Higher is better; 100–200 entry level, 200–400 good, 400+ strong."
                                        aria-label="What is MS?">
                                    <x-icon name="info" size="sm" class="inline" />
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case whitespace-normal break-words font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    Menford Score: proprietary authority score (0–1,000) based on a weighted average of backlink profile strength across multiple competitive intelligence sources. Higher is better; 100–200 entry level, 200–400 good, 400+ strong.
                                </span>
                            </span>
                        </span>
                    </th>
                    <th class="whitespace-nowrap px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            Organic Keywords
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-green-600 text-[11px]"
                                        data-info="Organic Keywords: estimated number of keywords a domain ranks for in organic search results globally, aggregated across multiple competitive intelligence sources. Higher values indicate broader topical relevance and search visibility; 1,000–5,000 entry level, 5,000–30,000 good, 30,000+ strong."
                                        aria-label="What is Organic Keywords?">
                                    <x-icon name="info" size="sm" class="inline" />
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case whitespace-normal break-words font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    Organic Keywords: estimated number of keywords a domain ranks for in organic search results globally, aggregated across multiple competitive intelligence sources. Higher values indicate broader topical relevance and search visibility; 1,000–5,000 entry level, 5,000–30,000 good, 30,000+ strong.
                                </span>
                            </span>
                        </span>
                    </th>
                    <th class="whitespace-nowrap px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            Organic Traffic
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-green-600 text-[11px]"
                                        data-info="Organic Traffic: estimated monthly organic search visits, aggregated across multiple competitive intelligence sources. Values are best used for comparative analysis across domains rather than as standalone figures; 5,000–20,000 entry level, 20,000–200,000 good, 200,000+ strong."
                                        aria-label="What is Organic Traffic?">
                                    <x-icon name="info" size="sm" class="inline" />
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case whitespace-normal break-words font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    Organic Traffic: estimated monthly organic search visits, aggregated across multiple competitive intelligence sources. Values are best used for comparative analysis across domains rather than as standalone figures; 5,000–20,000 entry level, 20,000–200,000 good, 200,000+ strong.
                                </span>
                            </span>
                        </span>
                    </th>
                    <th class="whitespace-nowrap px-4 py-2">KW/Traffic Ratio</th>
                    <th class="whitespace-nowrap px-4 py-2">SEO Metrics Date</th>
                    <th class="whitespace-nowrap px-4 py-2">Betting</th>
                    <th class="whitespace-nowrap px-4 py-2">Trading</th>
                    <th class="whitespace-nowrap px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            LINK LIFETIME
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-green-600 text-[11px]"
                                        data-info="Link duration. Permanent means it should stay online indefinitely; yearly options indicate minimum guaranteed duration."
                                        aria-label="What is Link Lifetime?">
                                    <x-icon name="info" size="sm" class="inline" />
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case whitespace-normal break-words font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    Link duration. Permanent means it should stay online indefinitely; yearly options indicate minimum guaranteed duration.
                                </span>
                            </span>
                        </span>
                    </th>
                    <th class="whitespace-nowrap px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            More than 1 link
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-green-600 text-[11px]"
                                        data-info="Yes means the publisher accepts multiple backlinks in one guest post."
                                        aria-label="What does More than 1 link mean?">
                                    <x-icon name="info" size="sm" class="inline" />
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case whitespace-normal break-words font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    Yes means the publisher accepts multiple backlinks in one guest post.
                                </span>
                            </span>
                        </span>
                    </th>
                    <th class="whitespace-nowrap px-4 py-2">Copywriting</th>
                    <th class="whitespace-nowrap px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            Sponsored Tag
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-green-600 text-[11px]"
                                        data-info="Indicates whether links are tagged sponsored/nofollow. No means full SEO value, yes means rel='sponsored' or rel='nofollow'."
                                        aria-label="What is Sponsored Tag?">
                                    <x-icon name="info" size="sm" class="inline" />
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case whitespace-normal break-words font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    Indicates whether links are tagged sponsored/nofollow. No means full SEO value, yes means rel='sponsored' or rel='nofollow'.
                                </span>
                            </span>
                        </span>
                    </th>
                    <th class="whitespace-nowrap px-4 py-2">Social Media Sharing</th>
                    <th class="whitespace-nowrap px-4 py-2">Post in Homepage</th>
                    <th class="whitespace-nowrap px-4 py-2">Date Added</th>

                    <th class="whitespace-nowrap px-4 py-2">Action</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div><!-- END TABLE WRAPPER -->
    </div>

    @include('websites.partials.contact-modal')
    @include('websites.partials.note-modal')
    @include('websites.partials.bulk-modal')
    @include('websites.partials.outreach-modal')

@endsection

@push('scripts')
    {{-- ###############################################
     Bulk-edit metadata â€“ MUST load before buildBulkInput()
############################################### --}}
    <script>
        window.bulkMeta = {
            /* ========= SELECTS ========= */
            status : {type:'select',options:{active:'Active',past:'Past'}},
            currency_code : {type:'select',options:{EUR:'EUR',USD:'USD'}},
            type_of_website : {type:'select',options:{
                    FORUM:'Forum',GENERALIST:'Generalist',VERTICAL:'Vertical',LOCAL:'Local'
                }},

            /* ---------- look-ups straight from PHP --------- */
            country_id  : {type:'select',options:@json($countries ->pluck('country_name','id'))},
            language_id : {type:'select',options:@json($languages ->pluck('name','id'))},
            contact_id  : {type:'select',options:@json($contacts  ->pluck('name','id'))},

            /* ========= NUMBERS / TEXT ========= */
            publisher_price      : {type:'number'},
            no_follow_price      : {type:'number'},
            special_topic_price  : {type:'number'},
            link_insertion_price : {type:'number'},
            banner_price         : {type:'number'},
            sitewide_link_price  : {type:'number'},
            kialvo_evaluation    : {type:'number'},
            profit               : {type:'number'},
            DA:{type:'number'}, PA:{type:'number'}, TF:{type:'number'}, CF:{type:'number'},
            DR:{type:'number'}, UR:{type:'number'}, ZA:{type:'number'}, as_metric:{type:'number'},
            seozoom:{type:'text'}, linkbuilder:{type:'text'},
            semrush_traffic:{type:'number'}, ahrefs_keyword:{type:'number'},
            ahrefs_traffic:{type:'number'}, keyword_vs_traffic:{type:'number'}, TF_vs_CF:{type:'number'},

            /* ========= DATES ========= */
            date_publisher_price : {type:'date'},
            date_kialvo_evaluation : {type:'date'},
            seo_metrics_date     : {type:'date'},

            /* ========= BOOLEAN FLAGS ========= */
            betting:{type:'select',options:{1:'Yes',0:'No'}},
            trading:{type:'select',options:{1:'Yes',0:'No'}},
            permanent_link:{type:'select',options:{1:'Yes',0:'No'}},
            more_than_one_link:{type:'select',options:{1:'Yes',0:'No'}},
            copywriting:{type:'select',options:{1:'Yes',0:'No'}},
            no_sponsored_tag:{type:'select',options:{1:'Yes',0:'No'}},
            social_media_sharing:{type:'select',options:{1:'Yes',0:'No'}},
            post_in_homepage:{type:'select',options:{1:'Yes',0:'No'}},

            /* ========= MANY-TO-MANY ========= */
            category_ids:{type:'multiselect',options:@json($categories->pluck('name','id'))},

            /* ========= PSEUDO FIELD ========= */
            recalculate_totals:{type:'noop'}   // shows the grey â€œNothing to fill inâ€ text
        };
    </script>

    <script>
        /* ========= generic toasts identical to Storages ========= */
        const toast = m => Swal.fire({
            toast: true, position: 'top-end', icon: 'success', title: m,
            showConfirmButton: false, timer: 1500
        });

        const oops  = m => Swal.fire({
            toast: true, position: 'top-end', icon: 'error', title: m,
            showConfirmButton: false, timer: 2000
        });

        function toastUndo (msg, token) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                html: `<span class="font-semibold">${msg}</span>
                   <button id="undoBtn"
                           style="background:#f59e0b"
                           class="ml-3 px-2 py-[2px] rounded text-xs font-bold">
                       UNDO
                   </button>`,
                background: '#2563eb',          // blue-600
                color: '#fff',
                showConfirmButton: false,
                timer: 4000,                    // 4 s
                timerProgressBar: true,
                didOpen: () => {
                    document.getElementById('undoBtn').onclick = () => {
                        Swal.close();
                        fetch("{{ route('websites.rollback') }}", {
                            method : 'POST',
                            headers: {
                                'Content-Type' : 'application/json',
                                'X-CSRF-TOKEN' : $('meta[name="csrf-token"]').attr('content')
                            },
                            body: JSON.stringify({ token })
                        })
                            .then(r => r.json())
                            .then(r => { toast(r.message); table.ajax.reload(null,false); })
                            .catch(() => oops('Failed to undo'));
                    };
                }
            });
        }

        $(document).ready(function() {
            const isGuestUser = @json($isGuestUser);
            let favoritesOnly = false;
            const showInfoPopup = (message) => {
                if (!message) return;
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'info',
                        title: 'Column Info',
                        text: message,
                        confirmButtonText: 'OK'
                    });
                    return;
                }
                window.alert(message);
            };

            // Intercept in capture phase so DataTables sort handlers never receive the event.
            const websitesThead = document.querySelector('#websitesTable thead');
            if (websitesThead) {
                const blockSortFromInfoButton = (event) => {
                    if (!event.target.closest('.metric-info-btn')) return;
                    event.preventDefault();
                    event.stopPropagation();
                    event.stopImmediatePropagation();
                };
                ['mousedown', 'mouseup', 'pointerdown', 'pointerup', 'touchstart', 'touchend'].forEach((eventName) => {
                    websitesThead.addEventListener(eventName, blockSortFromInfoButton, true);
                });
                websitesThead.addEventListener('keydown', (event) => {
                    const button = event.target.closest('.metric-info-btn');
                    if (!button) return;
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        event.stopPropagation();
                        event.stopImmediatePropagation();
                        showInfoPopup(button.getAttribute('data-info'));
                    }
                }, true);
                websitesThead.addEventListener('click', (event) => {
                    const button = event.target.closest('.metric-info-btn');
                    if (!button) return;
                    event.preventDefault();
                    event.stopPropagation();
                    event.stopImmediatePropagation();
                    showInfoPopup(button.getAttribute('data-info'));
                }, true);
            }

            // Initialize select2 with smaller text
            $('#filterCategories').select2({
                placeholder: 'Select Categories',
                allowClear: true,
                closeOnSelect: false,
                width: 'resolve',
                dropdownAutoWidth: true,
                containerCssClass: 'text-xs',
                dropdownCssClass: 'text-xs'
            });

            $('#filterContact').select2({
                placeholder: 'Select Publisher',
                allowClear: true,
                width: '12em' // tweak as you like
            });


            $('#filterCountriesInclude').select2({
                placeholder: 'Select Countries to Include',
                allowClear: true,
                width: '10em', // Or '100%' if you prefer
            });

            $('#filterCountriesExclude').select2({
                placeholder: 'Select Countries to Exclude',
                allowClear: true,
                width: '10em',
            });
            const renderNote = function (data) {
                if (!data) return '';
                // escape any "<" & ">" so the note can safely live in a data-attribute
                const safe = $('<div>').text(data).html();
                return `
            <a href="#" class="note-link text-green-700" data-note="${safe}">
                <x-icon name="comment" size="sm" class="inline" />
            </a>`;
            };
            const STATUS_TONES = {
                'active': 'bg-green-100 text-green-700 ring-green-200',
                'past': 'bg-gray-100 text-gray-600 ring-gray-200',
                'negotiation': 'bg-blue-100 text-blue-700 ring-blue-200',
                'waiting_for_first_answer': 'bg-blue-100 text-blue-700 ring-blue-200',
                'read_but_never_answered': 'bg-amber-100 text-amber-700 ring-amber-200',
                'refused_by_us': 'bg-red-100 text-red-700 ring-red-200',
                'publisher_refused': 'bg-red-100 text-red-700 ring-red-200',
                'never_opened': 'bg-gray-100 text-gray-500 ring-gray-200',
            };
            const renderStatusPill = function (data) {
                if (!data) return '<span class="text-gray-300">—</span>';
                const key = String(data).toLowerCase().replace(/\s+/g, '_');
                const tone = STATUS_TONES[key] || 'bg-gray-100 text-gray-700 ring-gray-200';
                const label = String(data).replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
                return `<span class="inline-flex items-center whitespace-nowrap px-2.5 py-0.5 rounded-full text-[11px] font-medium ring-1 ring-inset ${tone}">${label}</span>`;
            };
            const emDash = '<span class="text-gray-300">—</span>';
            const renderPrice = function (data) {
                if (data === null || data === undefined || data === '') return emDash;
                return '<span class="font-semibold text-gray-800">€ ' + data + '</span>';
            };
            const renderProfit = function (data) {
                if (data === null || data === undefined || data === '') return emDash;
                const neg = Number(data) < 0;
                return '<span class="font-semibold ' + (neg ? 'text-red-600' : 'text-gray-800') + '">€ ' + data + '</span>';
            };
            const renderCurrencyPill = function (data) {
                if (!data) return emDash;
                return '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider bg-gray-100 text-gray-700 ring-1 ring-inset ring-gray-200">' + String(data).toUpperCase() + '</span>';
            };
            const renderMetric = function (data) {
                if (data === null || data === undefined || data === '') return emDash;
                return data;
            };
            const renderEmailLink = function (data) {
                if (!data) return emDash;
                const safe = $('<div>').text(data).html();
                return '<a href="mailto:' + safe + '" class="text-green-600 hover:text-green-700 hover:underline">' + safe + '</a>';
            };
            const renderUrlLink = function (data) {
                if (!data) return emDash;
                const safe = $('<div>').text(data).html();
                return '<a href="' + safe + '" target="_blank" rel="noopener" class="text-green-600 hover:text-green-700 hover:underline break-all">' + safe + '</a>';
            };
            const decodeHtml = (value) => $('<textarea/>').html(value ?? '').text();
            // Initialize the DataTable
            window.table =$('#websitesTable').DataTable({

                processing: true,
                serverSide: true,
                dom: "<'dt-toolbar-top'<'flex items-center gap-3'l<'dt-search'>>>" +
                     "<'dt-scroll'rt>" +
                     "<'dt-toolbar-bottom'ip>",
                ajax: {
                    url: "{{ route('websites.data') }}",
                    type: "POST",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: function(d) {
                        // Gather filter values
                        d.domain_name = $('#filterDomainName').val();
                        d.type_of_website = $('#filterWebsiteType').val();
                        d.language_id = $('#filterLanguage').val();
                        d.status = isGuestUser ? null : $('#filterStatus').val();
                        d.contact_id = isGuestUser ? null : $('#filterContact').val();
                        d.no_contact = isGuestUser ? false : $('#filterNoContact').is(':checked');

                        d.country_ids_include = $('#filterCountriesInclude').val(); // Array
                        d.country_ids_exclude = $('#filterCountriesExclude').val(); // Array

                        d.publisher_price_min = isGuestUser ? null : $('#filterPublisher_priceMin').val();
                        d.publisher_price_max = isGuestUser ? null : $('#filterPublisher_priceMax').val();
                        d.price_min = $('#filterPriceMin').val();
                        d.price_max = $('#filterPriceMax').val();
                        d.sensitive_topic_price_min = $('#filterSensitiveTopicPriceMin').val();
                        d.sensitive_topic_price_max = $('#filterSensitiveTopicPriceMax').val();
                        d.kialvo_min = isGuestUser ? null : $('#filterKialvo_evaluationMin').val();
                        d.kialvo_max = isGuestUser ? null : $('#filterKialvo_evaluationMax').val();
                        d.profit_min = isGuestUser ? null : $('#filterProfitMin').val();
                        d.profit_max = isGuestUser ? null : $('#filterProfitMax').val();
                        d.banner_price_min   = isGuestUser ? null : $('#filterBannerMin').val();
                        d.banner_price_max   = isGuestUser ? null : $('#filterBannerMax').val();
                        d.sitewide_price_min = isGuestUser ? null : $('#filterSWMin').val();
                        d.sitewide_price_max = isGuestUser ? null : $('#filterSWMax').val();
                        d.category_ids = $('#filterCategories').val();

                        d.DA_min = $('#filterDAMin').val();
                        d.DA_max = $('#filterDAMax').val();
                        d.PA_min = $('#filterPAMin').val();
                        d.PA_max = $('#filterPAMax').val();
                        d.TF_min = $('#filterTFMin').val();
                        d.TF_max = $('#filterTFMax').val();
                        d.CF_min = $('#filterCFMin').val();
                        d.CF_max = $('#filterCFMax').val();
                        d.DR_min = $('#filterDRMin').val();
                        d.DR_max = $('#filterDRMax').val();
                        d.UR_min = $('#filterURMin').val();
                        d.UR_max = $('#filterURMax').val();
                        d.ZA_min = $('#filterZAMin').val();
                        d.ZA_max = $('#filterZAMax').val();
                        d.SR_min = $('#filterASMin').val();
                        d.SR_max = $('#filterASMax').val();
                        d.TF_VS_CF_min = $('#filterTF_vS_cfMin').val();
                        d.TF_VS_CF_max = $('#filterTF_vS_cfMax').val();
                        d.semrush_traffic_min = $('#filterSemrush_trafficMin').val();
                        d.semrush_traffic_max = $('#filterSemrush_trafficMax').val();
                        d.ahrefs_keyword_min = $('#filterAhrefs_keywordMin').val();
                        d.ahrefs_keyword_max = $('#filterAhrefs_keywordMax').val();
                        d.ahrefs_traffic_min = $('#filterAhrefs_trafficMin').val();
                        d.ahrefs_traffic_max = $('#filterAhrefs_trafficMax').val();
                        d.keyword_vs_traffic_min = $('#filterKeyword_vs_trafficMin').val();
                        d.keyword_vs_traffic_max = $('#filterKeyword_vs_trafficMax').val();
                        d.ms_min               = $('#filterMSMin').val();
                        d.ms_max               = $('#filterMSMax').val();
                        d.organic_keywords_min = $('#filterOrganicKWMin').val();
                        d.organic_keywords_max = $('#filterOrganicKWMax').val();
                        d.organic_traffic_min  = $('#filterOrganicTRMin').val();
                        d.organic_traffic_max  = $('#filterOrganicTRMax').val();
                        d.kw_traffic_ratio_min = $('#filterKWTRRatioMin').val();
                        d.kw_traffic_ratio_max = $('#filterKWTRRatioMax').val();

                        d.betting = $('#filterBetting').is(':checked');
                        d.trading = $('#filterTrading').is(':checked');
                        d.permanent_link = $('#filterPermanent_link').is(':checked');
                        d.more_than_one_link = $('#filterMore_than_one_link').is(':checked');
                        d.copywriting = $('#filterCopywriting').is(':checked');
                        d.no_sponsored_tag = $('#filterNo_sponsored_tag').is(':checked');
                        d.social_media_sharing = $('#filterSocial_media_sharing').is(':checked');
                        d.post_in_homepage = $('#filterPost_in_homepage').is(':checked');
                        d.show_deleted = isGuestUser ? false : $('#filterShowDeleted').is(':checked');
                        d.favorites_only = isGuestUser && favoritesOnly ? 1 : 0;

                    }
                },
                columns: [
                    {
                        data      : 'id',                     // re-use the rowâ€™s id
                        orderable : false,
                        searchable: false,
                        className : 'text-center',
                        visible   : !isGuestUser,
                        render    : id =>
                            `<input type="checkbox"
                class="rowChk form-checkbox h-4 w-4 text-green-600"
                value="${id}">`
                    },
                    { data: 'id', name: 'id', visible: !isGuestUser },
                    @if($isGuestUser)
                    {
                        data: 'is_favorite',
                        name: 'is_favorite',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function (data, type, row) {
                            const active = data ? 'text-yellow-400' : 'text-gray-400 opacity-60';
                            const title = data ? 'Remove from favorites' : 'Add to favorites';
                            return `
            <button class="fav-toggle ${active}" data-id="${row.id}" data-fav="${data ? 1 : 0}" title="${title}">
                <x-icon name="star" size="sm" class="inline" />
            </button>`;
                        }
                    },
                    {
                        data: 'is_in_cart',
                        name: 'is_in_cart',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function (data, type, row) {
                            const inCart = !!data;
                            if (inCart) {
                                return `
            <button class="order-toggle inline-flex items-center justify-center w-7 h-7 rounded-md bg-green-600 text-white hover:bg-green-700 transition" data-id="${row.id}" data-in-cart="1" title="Remove from order">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </button>`;
                            }
                            return `
            <button class="order-toggle inline-flex items-center justify-center w-7 h-7 rounded-md bg-gray-100 text-gray-600 hover:bg-green-100 hover:text-green-700 transition" data-id="${row.id}" data-in-cart="0" title="Add to order">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            </button>`;
                        }
                    },
                    @endif
                    { data: 'domain_name', name: 'domain_name' },
                    {
                        data: 'notes',
                        name: 'notes',
                        className: 'text-center',
                        render: renderNote
                    },
                    @unless($isGuestUser)
                    {
                        data: 'extra_notes',
                        name: 'extra_notes',
                        className: 'text-center',
                        render: renderNote
                    },
                    @endunless
                    { data: 'status', name: 'status', className: 'text-center', visible: !isGuestUser, render: renderStatusPill },
                    { data: 'country_name', name: 'country.country_name', className: 'text-center',
                        render: function (data, type, row) {
                            if (! data) return '<span class="text-gray-300">—</span>';
                            const flag = row.country_iso
                                ? `<img src="https://flagcdn.com/48x36/${row.country_iso}.png" srcset="https://flagcdn.com/96x72/${row.country_iso}.png 2x" width="20" height="15" alt="" class="rounded-sm border border-gray-200" loading="lazy">`
                                : '';
                            return `<span class="inline-flex items-center gap-1.5">${flag}<span>${data}</span></span>`;
                        }
                    },
                    { data: 'language_name', name: 'language.name',  className: 'text-center', },
                    {
                        data: 'contact_name',
                        name: 'contact.name',
                        visible: !isGuestUser,
                        render: function(data, type, row) {
                            if (isGuestUser) return '';
                            if (!row.contact_id) return "No Publisher";
                            return `
                        <a href="#"
                           class="contact-link text-blue-600 underline"
                           data-contact-id="${row.contact_id}">
                            ${data}
                        </a>`;
                        }
                    },
                    { data: 'currency_code', name: 'currency_code', className: 'text-center', visible: !isGuestUser, render: renderCurrencyPill },
                    { data: 'publisher_price',     name: 'publisher_price',     className: 'text-right', visible: !isGuestUser, render: renderPrice },
                    { data: 'no_follow_price',     name: 'no_follow_price',     className: 'text-right', visible: !isGuestUser, render: renderPrice },
                    { data: 'special_topic_price', name: 'special_topic_price', className: 'text-right', visible: !isGuestUser, render: renderPrice },
                    { data: 'price',               name: 'price',               className: 'text-right', render: renderPrice },
                    { data: 'sensitive_topic_price', name: 'sensitive_topic_price', className: 'text-right', visible: true, render: renderPrice },
                    { data: 'link_insertion_price', name: 'link_insertion_price', className: 'text-right', visible: !isGuestUser, render: renderPrice },
                    { data: 'banner_price',        name: 'banner_price',        className: 'text-right', visible: !isGuestUser, render: renderPrice },
                    { data: 'sitewide_link_price', name: 'sitewide_link_price', className: 'text-right', visible: !isGuestUser, render: renderPrice },

                    @unless($isGuestUser)
                    { data: 'kialvo_evaluation', name: 'kialvo_evaluation', className: 'text-right', render: renderPrice },
                    @endunless
                    { data: 'profit', name: 'profit', className: 'text-right', visible: !isGuestUser, render: renderProfit },
                    { data:'date_publisher_price', name:'date_publisher_price',
                       className:'text-center', render:dt, visible: !isGuestUser },

                    { data: 'linkbuilder', name: 'linkbuilder', className: 'text-center', visible: !isGuestUser },
                    { data: 'type_of_website', name: 'type_of_website', className: 'text-center', },
                    { data: 'categories_list', name: 'categories_list', className: 'text-center max-w-[160px]',
                        render: function (data, type, row) {
                            if (! data) return '<span class="text-gray-300">—</span>';
                            if (type !== 'display') return data;
                            const parts = data.split(',').map(s => s.trim()).filter(Boolean);
                            if (parts.length <= 2) return `<span class="text-xs text-gray-600">${data}</span>`;
                            const shown = parts.slice(0, 2).join(', ');
                            const safe = data.replace(/"/g, '&quot;');
                            return `<span class="text-xs text-gray-600" title="${safe}">${shown} <span class="text-gray-400">+${parts.length - 2} more</span></span>`;
                        }
                    },
                    { data: 'DA', name: 'DA', className: 'text-right', render: renderMetric },
                    { data: 'PA', name: 'PA', className: 'text-right', render: renderMetric },
                    { data: 'TF', name: 'TF', className: 'text-right', visible: !isGuestUser, render: renderMetric },
                    { data: 'CF', name: 'CF', className: 'text-right', visible: !isGuestUser, render: renderMetric },
                    { data: 'DR', name: 'DR', className: 'text-right', visible: !isGuestUser, render: renderMetric },
                    { data: 'UR', name: 'UR', className: 'text-right', render: renderMetric },
                    { data: 'ZA', name: 'ZA', className: 'text-right', render: renderMetric },
                    { data: 'as_metric', name: 'as_metric', className: 'text-right', render: renderMetric },
                    { data: 'seozoom', name: 'seozoom', className: 'text-right', render: renderMetric },
                    { data: 'TF_vs_CF', name: 'TF_vs_CF', className: 'text-right', visible: !isGuestUser, render: renderMetric },
                    { data: 'semrush_traffic', name: 'semrush_traffic', className: 'text-right', render: renderMetric },
                    { data: 'ahrefs_keyword', name: 'ahrefs_keyword', className: 'text-right', visible: !isGuestUser, render: renderMetric },
                    { data: 'ahrefs_traffic', name: 'ahrefs_traffic', className: 'text-right', visible: !isGuestUser, render: renderMetric },
                    { data: 'keyword_vs_traffic', name: 'keyword_vs_traffic', className: 'text-right', visible: !isGuestUser, render: renderMetric },
                    { data: 'ms',               name: 'ms',               type: 'number', className: 'text-center' },
                    { data: 'organic_keywords', name: 'organic_keywords', type: 'number', className: 'text-center' },
                    { data: 'organic_traffic',  name: 'organic_traffic',  type: 'number', className: 'text-center' },
                    { data: 'kw_traffic_ratio', name: 'kw_traffic_ratio', type: 'number', className: 'text-center' },
                    { data:'seo_metrics_date', name:'seo_metrics_date',
                      className:'text-center', render:dt, visible: !isGuestUser },
                    { data: 'betting', name: 'betting', className: 'text-center',
                        render: function (data, type, row) {
                            if (data === 1 )  { return 'YES'; }
                            else if(data === 0) return 'NO';
                        }
                    },
                    { data: 'trading', name: 'trading', className: 'text-center',
                        render: function (data, type, row) {
                            if (data === 1 )  {
                                return 'YES';
                            }else if(data === 0)

                                return 'NO';
                        }
                    },
                    { data: 'permanent_link', name: 'permanent_link', className: 'text-center', visible: !isGuestUser,
                        render: function (data, type, row) {
                            if (data === 1 )  {
                                return 'YES';
                            }else if(data === 0)

                                return 'NO';
                        }
                    },
                    { data: 'more_than_one_link', name: 'more_than_one_link', className: 'text-center', visible: !isGuestUser,
                        render: function (data, type, row) {
                            if (data === 1 )  {
                                return 'YES';
                            }else if(data === 0)

                                return 'NO';
                        }
                    },
                    { data: 'copywriting', name: 'copywriting', className: 'text-center', visible: !isGuestUser, defaultContent: '',  render: function (data, type, row) {
                            if (Number(data) === 1)  {
                                return 'PROVIDED';
                            }
                            if (Number(data) === 0) {
                                return 'NOT PROVIDED';
                            }
                            return '';
                        }
                    },
                    { data: 'no_sponsored_tag', name: 'no_sponsored_tag', className: 'text-center', visible: !isGuestUser,  render: function (data, type, row) {
                            if (Number(data) === 1)  {
                                return 'NO';
                            }
                            if (Number(data) === 0) {
                                return 'YES';
                            }
                            return '';
                        }
                    },
                    { data: 'social_media_sharing', name: 'social_media_sharing', className: 'text-center', visible: !isGuestUser,  render: function (data, type, row) {
                            if (data === 1 )  {
                                return 'YES';
                            }else if(data === 0)

                                return 'NO';
                        }
                    },
                    { data: 'post_in_homepage', name: 'post_in_homepage', className: 'text-center', visible: !isGuestUser,  render: function (data, type, row) {
                            if (data === 1 )  {
                                return 'YES';
                            }else if(data === 0)

                                return 'NO';
                        }
                    },
                    { data:'created_at', name:'date_added',
                     className:'text-center', render:dt, visible: !isGuestUser },


                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                order: [[1, 'desc']],
                responsive: false,
                autoWidth: false,
                scrollX: true,
                language: {
                    lengthMenu:   'Show _MENU_ websites',
                    info:         'Showing _START_ to _END_ of _TOTAL_ websites',
                    infoFiltered: '(filtered from _MAX_ total websites)',
                    infoEmpty:    'Showing 0 to 0 of 0 websites',
                }
            });

            // Sticky header (JS clone — CSS sticky blocked by DataTables' own overflow:hidden)
            if (window.initDtStickyHeader) window.initDtStickyHeader(table);

            // Move search box into the DataTable header (next to "Show entries")
            $(table.table().container()).find('.dt-search').append($('#websitesTableSearchWrap'));
            function dt(v){ return v ? new Date(v).toLocaleDateString('en-GB') : ''; }

            /* ---------- live â€œSelected: Nâ€ badge ---------- */
            function updateSelCount () {
                $('#selCount').text($('.rowChk:checked').length);
            }
            function toggleActionButtons () {
                const any = $('.rowChk:checked').length > 0;
                $('#btnBulkEdit, #btnBulkRestore').prop('disabled', !any);
            }

            /* row & master checkboxes */
            $(document).on('change', '.rowChk, #chkAll', (e) => {
                  if (e.target.id === 'chkAll') {
                    $('.rowChk').prop('checked', $('#chkAll').is(':checked'));
                }
                updateSelCount();
                toggleActionButtons();
            });

            /* keep count after pagination / search redraws */
            table.on('draw', () => {
                updateSelCount();
                toggleActionButtons();
            });

            /*â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€*/
            /* BULK-EDIT  (identical to Storages, just hits the Websites route) */
            /*â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€*/
            function buildBulkInput () {
                const field = $('#bulkField').val();
                const meta  = window.bulkMeta[field] || { type: 'text' };
                const wrap  = $('#bulkInputWrapper');

                wrap.empty();

                if (field === 'recalculate_totals') {
                    wrap.append('<p class="text-gray-500 text-xs">Nothing to fill in â€“ just click â€œSaveâ€.</p>');
                    return;
                }

                if (meta.type === 'date') {
                    wrap.append(`<input id="bulkValue" type="date"
                            class="w-full border border-gray-300 rounded px-2 py-1 text-xs
                                   focus:ring-green-500">`);
                    return;
                }

                if (meta.type === 'select') {
                    const none = `<option value="">-- Clear --</option>`;
                    const opts = Object.entries(meta.options || {})
                        .map(([v,l]) => `<option value="${v}">${l}</option>`).join('');
                    wrap.append(
                        `<select id="bulkValue"
                     class="w-full border border-gray-300 rounded px-2 py-1 text-xs
                            focus:ring-green-500">${none}${opts}</select>`);
                    if ($('#bulkValue option').length > 15) {
                        $('#bulkValue').select2({ width: 'resolve', dropdownAutoWidth: true });
                    }
                    return;
                }

                if (meta.type === 'multiselect') {
                    const opts = Object.entries(meta.options || {})
                        .map(([v,l]) => `<option value="${v}">${l}</option>`).join('');
                    wrap.append(
                        `<select id="bulkValue" multiple
                     class="w-full border border-gray-300 rounded px-2 py-1 text-xs
                            focus:ring-green-500">${opts}</select>`);
                    $('#bulkValue').select2({ width:'resolve', dropdownAutoWidth:true });
                    return;
                }

                if (meta.type === 'textarea') {
                    wrap.append(`<textarea id="bulkValue" rows="3"
                               class="w-full border border-gray-300 rounded px-2 py-1 text-xs
                                      focus:ring-green-500"></textarea>`);
                    return;
                }

                /* default = plain text/number */
                wrap.append(`<input id="bulkValue" type="text"
                       class="w-full border border-gray-300 rounded px-2 py-1 text-xs
                              focus:ring-green-500">`);
            }

            $('#bulkField').on('change', buildBulkInput);

            /* open modal -------------------------------------------------------*/
            $('#btnBulkEdit').on('click', function () {
                if ($('.rowChk:checked').length === 0) {
                    Swal.fire('Select at least one row first');   // quick feedback
                    return;
                }
                $('#bulkIds').val(
                    JSON.stringify($('.rowChk:checked').map((_, c) => +c.value).get())
                );

                $('#bulkField').val('recalculate_totals').trigger('change');   // default choice
                buildBulkInput();
                $('#bulkEditModal').removeClass('hidden');
            });

            /* cancel -----------------------------------------------------------*/
            $('#bulkCancel').on('click', () => $('#bulkEditModal').addClass('hidden'));

            /* save -------------------------------------------------------------*/
            $('#bulkSave').on('click', function () {
                const ids = $('.rowChk:checked').map((_, c) => parseInt(c.value, 10)).get();
                const field = $('#bulkField').val();
                let   value = $('#bulkValue').length ? $('#bulkValue').val() : '';

                // If multiselect produced an array, encode as comma-separated string
                if (Array.isArray(value)) {
                    value = value.join(',');
                }

                if (!ids.length) { Swal.fire('No rows selected'); return; }


                $.ajax({
                    url : "{{ route('websites.bulkUpdate') }}",
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: { 'ids[]': ids, field, value },
                    traditional: true,
                    success : res => {
                        toast(res.message);                     // green toast

                        if (res.undo_token) {                   // show 4-sec UNDO
                            toastUndo('Update saved.', res.undo_token);
                        }

                        $('#bulkEditModal').addClass('hidden').removeClass('flex');
                        $('#chkAll').prop('checked', false);
                        table.ajax.reload(null, false);
                    },

                    error  : xhr => Swal.fire('Error', xhr.responseJSON?.message ?? 'Server error','error')
                });
            });

            /* ---------- Rollback selected rows ---------- */
            $('#btnBulkRestore').on('click', function () {

                const ids = $('.rowChk:checked').map((_, c) => c.value).get();
                if (!ids.length) { oops('Select at least one row'); return; }

                Swal.fire({
                    title: 'Restore previous snapshot?',
                    icon : 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, rollback!'
                }).then(result => {
                    if (!result.isConfirmed) return;

                    $.post(
                        "{{ route('websites.rollback') }}",
                        { ids, _token: $('meta[name="csrf-token"]').attr('content') },
                        r => {
                            toast(r.message);
                            $('#chkAll').prop('checked', false);
                            table.ajax.reload(null, false);
                        }
                    ).fail(() => oops('Rollback failed'));
                });
            });

            // Toggle-based filter
            if (!isGuestUser) {
                $('#filterShowDeleted').on('change', function() {
                    table.ajax.reload();
                });
            }

            // Table search (debounced to avoid slow typing)
            let websiteSearchTimer;
            $('#websitesTableSearch').on('input', function() {
                const value = this.value;
                clearTimeout(websiteSearchTimer);
                websiteSearchTimer = setTimeout(() => {
                    table.search(value).draw();
                }, 300);
            });
            $('#websitesTableSearch').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    clearTimeout(websiteSearchTimer);
                    table.search(this.value).draw();
                }
            });

            // Favorites toggle (guest only)
            const favBaseUrl = "{{ url('/websites') }}";
            $(document).on('click', '.fav-toggle', function(e) {
                e.preventDefault();
                if (!isGuestUser) return;
                const $btn = $(this);
                const id = $btn.data('id');
                $btn.prop('disabled', true);
                $.ajax({
                    url: `${favBaseUrl}/${id}/favorite`,
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(res) {
                        const isFav = !!res.favorite;
                        $btn.data('fav', isFav ? 1 : 0);
                        $btn.toggleClass('text-yellow-400', isFav);
                        $btn.toggleClass('text-gray-400 opacity-60', !isFav);
                        if (favoritesOnly && !isFav) {
                            table.ajax.reload(null, false);
                        }
                    },
                    error: function() {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Could not update favorite.' });
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });

            // Order cart toggle (guest only) — uses LIBCart bridge (no Alpine timing dependency)
            $(document).on('click', '.order-toggle', function (e) {
                e.preventDefault();
                if (!isGuestUser) return;
                if (!window.LIBCart) { console.warn('LIBCart bridge not present'); return; }
                const $btn = $(this);
                const id = $btn.data('id');
                const inCart = String($btn.data('in-cart')) === '1';
                $btn.prop('disabled', true);
                const done = () => {
                    $btn.prop('disabled', false);
                    table.ajax.reload(null, false);
                };
                if (inCart) {
                    LIBCart.removeItemByWebsiteId(id).then(done, done);
                } else {
                    LIBCart.addItem(id).then((ok) => {
                        if (ok) LIBCart.openDrawer();
                        done();
                    }, done);
                }
            });

            // Header "Current Order" button — event-delegated, checked at click time (no gate)
            $(document).on('click', '#btnOpenCart', function (e) {
                e.preventDefault();
                console.log('[btnOpenCart] click, LIBCart available?', !!window.LIBCart);
                if (window.LIBCart) {
                    LIBCart.openDrawer();
                } else {
                    console.warn('[btnOpenCart] LIBCart not loaded — falling back to DOM toggle');
                    const aside = document.getElementById('cart-drawer');
                    if (aside) aside.classList.add('open');
                }
            });

            // Live header count badge — wire if bridge is available, retry shortly otherwise
            function wireCartBadge() {
                if (!isGuestUser) return;
                if (!window.LIBCart) return setTimeout(wireCartBadge, 100);
                const $badge = $('#cartCountBadge');
                const renderBadge = (snap) => {
                    const n = (snap && snap.count) || 0;
                    if (n > 0) {
                        $badge.text(n).removeClass('hidden').addClass('inline-flex');
                    } else {
                        $badge.addClass('hidden').removeClass('inline-flex');
                    }
                };
                LIBCart.onChange(renderBadge);
                LIBCart.refresh().then(renderBadge);
            }
            wireCartBadge();

            // Favorites filter toggle (guest only)
            $('#btnFavoritesToggle').on('click', function() {
                if (!isGuestUser) return;
                favoritesOnly = !favoritesOnly;
                $(this)
                    .toggleClass('bg-amber-600', favoritesOnly)
                    .toggleClass('bg-amber-500', !favoritesOnly)
                    .text(favoritesOnly ? 'Show All' : 'My Favorites')
                    .prepend('<svg class="w-3.5 h-3.5 inline me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg> ');
                table.ajax.reload();
            });

            // Search
            $('#btnSearch').on('click', function(e) {
                e.preventDefault();
                table.ajax.reload();
                window.buildFilterChips(() => table.ajax.reload());
            });

            // Clear
            $('#btnClear').on('click', function(e) {
                e.preventDefault();
                $('#filterForm input[type="text"], #filterForm input[type="number"]').val('');
                $('#filterForm select').val('');
                $('#filterForm input[type="checkbox"]').prop('checked', false);
                // Specifically clear the multi-selects:
                $('#filterCountriesInclude').val(null).trigger('change');
                $('#filterCountriesExclude').val(null).trigger('change');
                $('#filterNoContact').prop('checked', false);
                // Clear the Publisher filter (select2)
                $('#filterContact').val(null).trigger('change');             // â† NEW
                $('#filterBannerMin,#filterBannerMax,#filterSWMin,#filterSWMax').val('');

                $('#filterCategories').val(null).trigger('change');
                $('#websitesTableSearch').val('');
                table.search('');
                if (isGuestUser) {
                    favoritesOnly = false;
                    $('#btnFavoritesToggle')
                        .removeClass('bg-amber-600')
                        .addClass('bg-amber-500')
                        .text('My Favorites')
                        .prepend('<svg class="w-3.5 h-3.5 inline me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg> ');
                }
                table.ajax.reload();
                window.buildFilterChips(() => table.ajax.reload());
            });

            function csvList(selector){
                const v = $(selector).val();
                return Array.isArray(v) ? v.join(',') : '';
            }

            const buildWebsiteExportParams = function(selectedFields = null) {
                const params = {
                    domain_name: $('#filterDomainName').val(),
                    type_of_website: $('#filterWebsiteType').val(),
                    language_id: $('#filterLanguage').val(),
                    status: $('#filterStatus').val(),
                    country_id: $('#filterCountry').val(),
                    publisher_price_min: $('#filterPublisher_priceMin').val(),
                    publisher_price_max: $('#filterPublisher_priceMax').val(),
                    price_min: $('#filterPriceMin').val(),
                    price_max: $('#filterPriceMax').val(),
                    sensitive_topic_price_min: $('#filterSensitiveTopicPriceMin').val(),
                    sensitive_topic_price_max: $('#filterSensitiveTopicPriceMax').val(),
                    kialvo_min: isGuestUser ? null : $('#filterKialvo_evaluationMin').val(),
                    kialvo_max: isGuestUser ? null : $('#filterKialvo_evaluationMax').val(),
                    profit_min: $('#filterProfitMin').val(),
                    profit_max: $('#filterProfitMax').val(),
                    category_ids: csvList('#filterCategories'),
                    country_ids_include: csvList('#filterCountriesInclude'),
                    country_ids_exclude: csvList('#filterCountriesExclude'),
                    DA_min: $('#filterDAMin').val(),
                    DA_max: $('#filterDAMax').val(),
                    PA_min: $('#filterPAMin').val(),
                    PA_max: $('#filterPAMax').val(),
                    TF_min: $('#filterTFMin').val(),
                    TF_max: $('#filterTFMax').val(),
                    CF_min: $('#filterCFMin').val(),
                    CF_max: $('#filterCFMax').val(),
                    DR_min: $('#filterDRMin').val(),
                    DR_max: $('#filterDRMax').val(),
                    UR_min: $('#filterURMin').val(),
                    UR_max: $('#filterURMax').val(),
                    ZA_min: $('#filterZAMin').val(),
                    ZA_max: $('#filterZAMax').val(),
                    SR_min: $('#filterASMin').val(),
                    SR_max: $('#filterASMax').val(),
                    TF_VS_CF_min: $('#filterTF_vS_cfMin').val(),
                    TF_VS_CF_max: $('#filterTF_vS_cfMax').val(),
                    semrush_traffic_min: $('#filterSemrush_trafficMin').val(),
                    semrush_traffic_max: $('#filterSemrush_trafficMax').val(),
                    ahrefs_keyword_min: $('#filterAhrefs_keywordMin').val(),
                    ahrefs_keyword_max: $('#filterAhrefs_keywordMax').val(),
                    ahrefs_traffic_min: $('#filterAhrefs_trafficMin').val(),
                    ahrefs_traffic_max: $('#filterAhrefs_trafficMax').val(),
                    keyword_vs_traffic_min: $('#filterKeyword_vs_trafficMin').val(),
                    keyword_vs_traffic_max: $('#filterKeyword_vs_trafficMax').val(),
                    ms_min:               $('#filterMSMin').val(),
                    ms_max:               $('#filterMSMax').val(),
                    organic_keywords_min: $('#filterOrganicKWMin').val(),
                    organic_keywords_max: $('#filterOrganicKWMax').val(),
                    organic_traffic_min:  $('#filterOrganicTRMin').val(),
                    organic_traffic_max:  $('#filterOrganicTRMax').val(),
                    kw_traffic_ratio_min: $('#filterKWTRRatioMin').val(),
                    kw_traffic_ratio_max: $('#filterKWTRRatioMax').val(),
                    betting: $('#filterBetting').is(':checked') ? 1 : 0,
                    trading: $('#filterTrading').is(':checked') ? 1 : 0,
                    permanent_link: $('#filterPermanent_link').is(':checked') ? 1 : 0,
                    more_than_one_link: $('#filterMore_than_one_link').is(':checked') ? 1 : 0,
                    copywriting: $('#filterCopywriting').is(':checked') ? 1 : 0,
                    no_sponsored_tag: $('#filterNo_sponsored_tag').is(':checked') ? 1 : 0,
                    social_media_sharing: $('#filterSocial_media_sharing').is(':checked') ? 1 : 0,
                    post_in_homepage: $('#filterPost_in_homepage').is(':checked') ? 1 : 0,
                    show_deleted: isGuestUser ? 0 : ($('#filterShowDeleted').is(':checked') ? 1 : 0),
                    favorites_only: isGuestUser && favoritesOnly ? 1 : 0
                };

                if (!isGuestUser && Array.isArray(selectedFields) && selectedFields.length) {
                    params.fields = selectedFields;
                }

                return $.param(params);
            };

            const runWebsiteExport = function(type, selectedFields = null) {
                const route = type === 'pdf'
                    ? "{{ route('websites.export.pdf') }}"
                    : "{{ route('websites.export.csv') }}";
                window.location = route + "?" + buildWebsiteExportParams(selectedFields);
            };

            let websitePendingExportType = null;
            const getWebsiteSelectedFields = () =>
                $('.website-export-field:checked').map((_, el) => el.value).get();

            const syncWebsiteSelectAll = function() {
                const total = $('.website-export-field').length;
                const checked = $('.website-export-field:checked').length;
                $('#websiteExportSelectAll').prop('checked', total > 0 && checked === total);
            };

            const openWebsiteExportPicker = function(type) {
                websitePendingExportType = type;
                $('#websiteExportPickerTitle').text(
                    type === 'pdf' ? 'Choose columns for PDF export' : 'Choose columns for CSV export'
                );
                $('#websiteExportPicker').removeClass('hidden');
                syncWebsiteSelectAll();
            };

            const closeWebsiteExportPicker = function() {
                $('#websiteExportPicker').addClass('hidden');
                websitePendingExportType = null;
            };

            $('#websiteExportSelectAll').on('change', function() {
                $('.website-export-field').prop('checked', this.checked);
            });
            $(document).on('change', '.website-export-field', syncWebsiteSelectAll);
            $('#websiteExportClose, #websiteExportCancel').on('click', closeWebsiteExportPicker);
            $(document).on('mousedown', function(e) {
                if ($('#websiteExportPicker').hasClass('hidden')) {
                    return;
                }
                if ($(e.target).closest('#websiteExportPicker, #btnExportCsv, #btnExportPdf').length) {
                    return;
                }
                closeWebsiteExportPicker();
            });

            $('#websiteExportConfirm').on('click', function() {
                const selected = getWebsiteSelectedFields();
                if (!selected.length) {
                    Swal.fire({ icon: 'warning', title: 'Select at least one column' });
                    return;
                }
                runWebsiteExport(websitePendingExportType || 'csv', selected);
                closeWebsiteExportPicker();
            });

            $('#btnExportCsv').click(function(e) {
                e.preventDefault();
                if (isGuestUser) {
                    runWebsiteExport('csv');
                    return;
                }
                openWebsiteExportPicker('csv');
            });

            $('#btnExportPdf').click(function(e) {
                e.preventDefault();
                if (isGuestUser) {
                    runWebsiteExport('pdf');
                    return;
                }
                openWebsiteExportPicker('pdf');
            });


            // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            //  NOTE  MODAL
            // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            $(document).on('click', '.note-link', function (e) {
                e.preventDefault();
                const note = $(this).data('note');
                $('#modalNoteBody').text(decodeHtml(note));
                $('#noteModal').removeClass('hidden');
            });
            $('#closeNoteModal, #closeNoteModalBottom').on('click', function () {
                $('#noteModal').addClass('hidden');
            });

            // =====================
            // Contact Modal
            // =====================
            $(document).on('click', '.contact-link', function(e) {
                e.preventDefault();
                let contactId = $(this).data('contact-id');

                $.ajax({
                    url: "{{ route('contacts.showAjax', '') }}/" + contactId,
                    type: 'GET',
                    success: function(response) {
                        if (response.status === 'success') {
                            let c = response.data;
                            $('#modalContactName').text(c.name ?? '');
                            $('#modalContactEmail').text(c.email ?? '');
                            $('#modalContactPhone').text(c.phone ?? '');
                            $('#modalContactFacebook').text(c.facebook ?? '');
                            $('#modalContactInstagram').text(c.instagram ?? '');

                            // Build websites list
                            let websitesHtml = '';
                            if (c.websites && c.websites.length > 0) {
                                websitesHtml = '<ul>';
                                c.websites.forEach(function (w) {
                                    // If you want to view the website's show blade:
                                    let url = "/websites/" + w.id;

                                    websitesHtml += `
                                <li>
                                    <a href="${url}" class="underline text-blue-600">
                                        ${w.domain_name}
                                    </a>
                                </li>
                            `;
                                });
                                websitesHtml += '</ul>';
                            } else {
                                websitesHtml = '<p>No websites found for this contact.</p>';
                            }

                            $('#modalContactWebsites').html(websitesHtml);
                            // Show the modal
                            $('#contactModal').removeClass('hidden');
                        } else {
                            alert('Could not load contact info.');
                        }
                    },
                    error: function() {
                        alert('Error fetching contact info.');
                    }
                });
            });

            // Close buttons
            $('#closeContactModal, #closeContactModalBottom').on('click', function() {
                $('#contactModal').addClass('hidden');
            });

            // ─────────────────────────────────────────────────
            //  Sync DataforSEO button (admin only)
            // ─────────────────────────────────────────────────
            if (!isGuestUser) {
                $('#btnSyncDataForSeo').on('click', function () {
                    const csrfToken = $('meta[name="csrf-token"]').attr('content');

                    let ids      = $('.rowChk:checked').map((_, c) => parseInt(c.value, 10)).get();
                    let syncAll  = ids.length === 0;
                    let syncLabel = syncAll ? 'all domains' : ids.length + ' selected domain(s)';

                    // Steps shown in sequence while waiting for the response
                    const steps = [
                        { icon: '🛰️', text: 'Connecting to DataforSEO API...' },
                        { icon: '📡', text: 'Fetching Domain Rank (MS)...' },
                        { icon: '📊', text: 'Fetching Organic Keywords &amp; Traffic...' },
                        { icon: '💾', text: 'Writing data to database...' },
                        { icon: '⏳', text: 'Almost done, finishing up...' },
                    ];

                    let stepIndex = 0;
                    let elapsed   = 0;

                    const updateStep = () => {
                        const s = steps[Math.min(stepIndex, steps.length - 1)];
                        const mins  = String(Math.floor(elapsed / 60)).padStart(2, '0');
                        const secs  = String(elapsed % 60).padStart(2, '0');
                        $('#swal-sync-step').html(s.icon + ' ' + s.text);
                        $('#swal-sync-timer').text(mins + ':' + secs);
                        stepIndex++;
                        elapsed++;
                    };

                    Swal.fire({
                        title: '<span style="font-size:1.1rem;font-weight:700;">Syncing DataforSEO</span>',
                        html: `
                            <div style="font-size:0.85rem;color:#6b7280;margin-bottom:6px;">
                                Target: <strong>${syncLabel}</strong>
                            </div>
                            <div id="swal-sync-step" style="font-size:0.9rem;color:#4f46e5;margin:10px 0;min-height:22px;">
                                🛰️ Connecting to DataforSEO API...
                            </div>
                            <div style="background:#e5e7eb;border-radius:9999px;height:6px;overflow:hidden;margin:10px 0;">
                                <div id="swal-sync-bar"
                                     style="height:6px;border-radius:9999px;background:linear-gradient(90deg,#4f46e5,#818cf8);
                                            width:0%;transition:width 1s linear;">
                                </div>
                            </div>
                            <div style="font-size:0.75rem;color:#9ca3af;margin-top:4px;">
                                Elapsed: <span id="swal-sync-timer">00:00</span>
                            </div>`,
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                            updateStep();

                            // Advance step text every 3 seconds
                            window._syncStepInterval = setInterval(updateStep, 1000);

                            // Animate progress bar — grows slowly, stops at 90% until done
                            let pct = 0;
                            window._syncBarInterval = setInterval(() => {
                                pct = Math.min(pct + (pct < 60 ? 2 : 0.3), 90);
                                const bar = document.getElementById('swal-sync-bar');
                                if (bar) bar.style.width = pct + '%';
                            }, 500);
                        },
                        willClose: () => {
                            clearInterval(window._syncStepInterval);
                            clearInterval(window._syncBarInterval);
                        }
                    });

                    fetch("{{ route('websites.dataforseo.sync-selected') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify(syncAll ? { sync_all: true } : { ids: ids })
                    })
                        .then(r => r.json())
                        .then(data => {
                            // Fill bar to 100% before closing
                            const bar = document.getElementById('swal-sync-bar');
                            if (bar) bar.style.width = '100%';

                            setTimeout(() => {
                                Swal.close();
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Sync Complete!',
                                    html: `<p style="color:#374151;">${data.message || 'All domains synced.'}</p>`,
                                    confirmButtonText: 'Great!',
                                    confirmButtonColor: '#4f46e5',
                                    timer: 6000,
                                    timerProgressBar: true,
                                });
                                table.ajax.reload(null, false);
                            }, 400);
                        })
                        .catch(() => {
                            Swal.close();
                            Swal.fire({
                                icon: 'error',
                                title: 'Sync Failed',
                                text: 'Something went wrong. Please try again.',
                                confirmButtonColor: '#dc2626',
                            });
                        });
                });
            }

        }); // <-- END of $(document).ready()

        // ----- Outreach helpers (multilang) -----
        function boGetTemplate(lang, kind) {
            try {
                if (window.BO_TEMPLATES && window.BO_TEMPLATES[lang] && window.BO_TEMPLATES[lang][kind]) {
                    return window.BO_TEMPLATES[lang][kind];
                }
            } catch(e) {}
            return { subject:'', body:'' };
        }
        function boLoadSubjectBodyFromConfig() {
            const lang = $('#boLanguage').val() || window.BO_DEFAULT_LANG || 'en';
            const kind = $('#boTemplate').val()  || window.BO_DEFAULT_KIND || 'first';
            const t = boGetTemplate(lang, kind);
            $('#boSubject').val(t.subject || '');
            $('#boBody').val(t.body || '');
        }

        function boOpenModal() {
            // reset preview box
            $('#boPreviewBox').addClass('hidden');
            // force-refresh from current language/template selections:

            // defaults for selectors
            if (!$('#boLanguage').val()) $('#boLanguage').val(window.BO_DEFAULT_LANG || 'en');
            if (!$('#boTemplate').val()) $('#boTemplate').val(window.BO_DEFAULT_KIND || 'first');

            // if user fields are empty, load from config;
            // if they already typed something, don't overwrite.
            if (!$('#boSubject').val() || !$('#boBody').val()) {
                boLoadSubjectBodyFromConfig();
            }

            if (window.BO_loadTemplate) window.BO_loadTemplate();
            $('#bulkOutreachModal').removeClass('hidden');

        }
        function boCloseModal() { $('#bulkOutreachModal').addClass('hidden'); }


        /* ========= Wire up the button ========= */
        $(document).on('click', '#btnBulkOutreach', function () {
            if ($('.rowChk:checked').length === 0) { Swal.fire('Select at least one row first'); return; }
            boOpenModal();
        });
        $(document).on('click', '#boCloseTop,#boCloseBottom', boCloseModal);

        /* ========= Real-time template switch ========= */
        /* ========= Real-time template/lang switch ========= */
        $(document).on('change', '#boTemplate, #boLanguage', function () {
            boLoadSubjectBodyFromConfig();
        });


        /* ========= Preview recipients ========= */
        $(document).on('click', '#boCheck', function () {
            var ids = $('.rowChk:checked').map(function(_, c){ return parseInt(c.value,10); }).get();
            var onlyPast = $('#boOnlyPast').is(':checked');
            var tplKey   = $('#boTemplate').val() || 'first';

            $('#boSelTotal').text(ids.length);
            $('#boEligible').text('0');
            $('#boSkipped').text('0');
            $('#boNoSpecialCount').text('0');
            $('#boSkippedList').empty();

            fetch("{{ route('websites.outreach.preview') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                body: JSON.stringify({
                    ids: ids,
                    only_past: !!onlyPast,
                    template_key: tplKey,
                    language: $('#boLanguage').val() || window.BO_DEFAULT_LANG || 'en'
                })

            })
                .then(function(r){ return r.json(); })
                .then(function(r){
                    if (!r || r.status !== 'ok') throw new Error(r && r.message ? r.message : 'Preview failed');
                    $('#boPreviewBox').removeClass('hidden');
                    $('#boEligible').text(r.data.eligible.length);
                    $('#boSkipped').text(r.data.skipped.length);
                    $('#boNoSpecialCount').text(r.data.no_special_count || 0);

                    if (r.data.skipped.length) {
                        var lines = r.data.skipped.map(function(s){ return '<div>â€¢ #'+s.id+' '+s.domain+' â€” '+s.reason+'</div>'; });
                        $('#boSkippedList').html(lines.join(''));
                    } else {
                        $('#boSkippedList').html('<div class="text-green-700">All selected are eligible.</div>');
                    }
                })
                .catch(function(err){ oops(err.message || 'Preview error'); });
        });

        /* ========= Send ========= */
        $(document).on('click', '#boSend', function () {
            var ids        = $('.rowChk:checked').map(function(_, c){ return parseInt(c.value,10); }).get();
            var target_url = $('#boTargetUrl').val().trim();
            var brand      = $('#boBrand').val().trim();
            var subject    = $('#boSubject').val().trim();
            var body       = $('#boBody').val().trim();
            var only_past  = $('#boOnlyPast').is(':checked');
            var tplKey     = $('#boTemplate').val() || 'first';
            var lang       = $('#boLanguage').val() || window.BO_DEFAULT_LANG || 'en';


            if (!subject) { oops('Subject is required'); return; }
            if (!body)    { oops('Email body is required'); return; }

            $('#boSend').prop('disabled', true).text('Sending...');

            fetch("{{ route('websites.outreach.send') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN' : $('meta[name="csrf-token"]').attr('content')
                },
                body: JSON.stringify({
                    ids: ids,
                    template_key: tplKey,
                    language: lang,          // <â€” send language
                    target_url: target_url,
                    brand: brand,
                    subject: subject,
                    body: body,
                    only_past: only_past,

                })
            })
                .then(r => r.json())
                .then(r => {
                    if (r.status !== 'ok') throw new Error(r.message || 'Send failed');

                    var msg = 'Sent ' + r.data.sent + ' email(s)';
                    if (r.data.failed) msg += ', ' + r.data.failed + ' failed';
                    toast(msg);                          // âœ… same toast style
                    if (r.data.failed_details?.length) {
                        var lines = r.data.failed_details.map(s => '#'+s.id+' '+s.domain+': '+s.error).join('\n');
                        Swal.fire('Some emails failed', lines.substring(0, 1800), 'warning');
                    }

                    $('#chkAll').prop('checked', false);
                    if (window.table) window.table.ajax.reload(null, false);
                    boCloseModal();                      // âœ… close modal
                })
                .catch(err => oops(err.message || 'Send error'))
                .finally(() => $('#boSend').prop('disabled', false).text('Send'));
        });


        document.addEventListener('DOMContentLoaded', function() {
            let toggleBtn = document.getElementById('toggleFiltersBtn');
            let filtersDiv = document.getElementById('filterForm');
            let filtersVisible = true; // assume filters start out visible

            toggleBtn.addEventListener('click', function() {
                // Toggle the "hidden" class on the filters container
                filtersDiv.classList.toggle('hidden');
                filtersVisible = !filtersVisible;

                // Update the button text based on whether filters are now visible or not
                if (filtersVisible) {
                    toggleBtn.textContent = 'Hide Filters';
                } else {
                    toggleBtn.textContent = 'Show Filters';
                }
            });

            @if (session('status'))
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: '{{ session('status') }}',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false
            });
            @endif
        });

        @if (session('undo_token'))
        toastUndo('{{ session('status') }}', '{{ session('undo_token') }}');
        @elseif (session('status'))
        toast('{{ session('status') }}');
        @endif

    </script>

@endpush



