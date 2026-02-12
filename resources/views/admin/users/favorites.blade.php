@extends('layouts.dashboard')

@section('content')
    <div class="px-6 py-6 bg-gray-50 min-h-screen text-sm">
        <div class="max-w-[1400px]">
            <div class="mb-4">
                <h1 class="text-2xl font-bold text-gray-700">Favorites for {{ $user->name }}</h1>
            </div>
        </div>

        <div id="favoritesTableSearchWrap" class="table-search-wrap mb-2">
            <div class="flex items-center w-72 border border-gray-300 rounded-md bg-white shadow-sm
                        focus-within:ring-1 focus-within:ring-cyan-500 focus-within:border-cyan-500">
                <span class="px-3 text-gray-400 text-base leading-none">
                    <i class="fas fa-search"></i>
                </span>
                <input id="favoritesTableSearch" type="text"
                       class="w-full bg-transparent border-0 focus:ring-0 focus:outline-none py-2 pr-3 text-sm leading-5"
                       placeholder="Search favorites...">
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 mb-2 max-w-[1400px]">
            <a href="{{ route('admin.users.favorites.export.csv', $user) }}"
               class="bg-green-600 text-white px-4 py-2 rounded shadow hover:bg-green-700">
                Export CSV
            </a>
            <a href="{{ route('admin.users.favorites.export.pdf', $user) }}"
               class="bg-red-600 text-white px-4 py-2 rounded shadow hover:bg-red-700">
                Export PDF
            </a>
        </div>

        <div class="bg-white border border-gray-200 rounded shadow p-2 overflow-x-auto max-w-[1400px]">
            <table id="favoritesTable" class="text-xs text-gray-700 w-full min-w-[1500px]">
                <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-[12px] uppercase text-gray-500 tracking-wider"><th class="whitespace-nowrap px-4 py-2">ID</th>
                    <th class="whitespace-nowrap px-4 py-2">Domain</th>
                    <th class="whitespace-nowrap px-4 py-2">Notes</th>
                    <th class="whitespace-nowrap px-4 py-2">Internal Notes</th>
                    <th class="whitespace-nowrap px-4 py-2">Status</th>
                    <th class="whitespace-nowrap px-4 py-2">Country</th>
                    <th class="whitespace-nowrap px-4 py-2">Language</th>
                    <th class="whitespace-nowrap px-4 py-2">Publisher</th>
                    <th class="whitespace-nowrap px-4 py-2">Currency</th>
                    <th class="whitespace-nowrap px-4 py-2">Publisher Price</th>
                    <th class="whitespace-nowrap px-4 py-2">No Follow Price</th>
                    <th class="whitespace-nowrap px-4 py-2">Special Topic Price</th>
                    <th class="whitespace-nowrap px-4 py-2">Link Insertion Price</th>
                    <th class="whitespace-nowrap px-4 py-2">Banner &euro;</th>
                    <th class="whitespace-nowrap px-4 py-2">Site-wide &euro;</th>
                    <th class="whitespace-nowrap px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            Price
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-cyan-600 text-[11px]"
                                        data-info="The price you pay for a guest post placement on this website. This is your final cost including our service fee."
                                        aria-label="What is Price?">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    The price you pay for a guest post placement on this website. This is your final cost including our service fee.
                                </span>
                            </span>
                        </span>
                    </th>
                    <th class="whitespace-nowrap px-4 py-2">Profit</th>
                    <th class="whitespace-nowrap px-4 py-2">Date Publisher Price</th>
                    <th class="whitespace-nowrap px-4 py-2">Linkbuilder</th>
                    <th class="whitespace-nowrap px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            Type of Website
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-cyan-600 text-[11px]"
                                        data-info="Vertical: focused on one topic. Local: focused on a city/area. Generalist: covers many topics."
                                        aria-label="What is Type of Website?">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
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
                                        class="metric-info-btn text-cyan-600 text-[11px]"
                                        data-info="Domain Authority (Moz): ranking score 1-100. Higher DA usually passes more link value; 30+ good, 50+ excellent, 70+ premium."
                                        aria-label="What is DA?">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
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
                                        class="metric-info-btn text-cyan-600 text-[11px]"
                                        data-info="Page Authority (Moz): predicts ranking strength of a specific page on a 1-100 scale. Higher is better."
                                        aria-label="What is PA?">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
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
                                        class="metric-info-btn text-cyan-600 text-[11px]"
                                        data-info="Trust Flow (Majestic): backlink quality score on 0-100. Higher is better; TF 20+ is typically reliable."
                                        aria-label="What is TF?">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
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
                                        class="metric-info-btn text-cyan-600 text-[11px]"
                                        data-info="Citation Flow (Majestic): backlink quantity influence score on 0-100. Higher is better, especially when TF is close to or above CF."
                                        aria-label="What is CF?">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
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
                                        class="metric-info-btn text-cyan-600 text-[11px]"
                                        data-info="Domain Rating (Ahrefs): backlink profile strength from 0 to 100. Higher DR means stronger authority; 40+ is solid."
                                        aria-label="What is DR?">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
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
                                        class="metric-info-btn text-cyan-600 text-[11px]"
                                        data-info="URL Rating (Ahrefs): strength of the target page backlink profile on a 0-100 scale. Higher is better."
                                        aria-label="What is UR?">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
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
                                        class="metric-info-btn text-cyan-600 text-[11px]"
                                        data-info="Zoom Authority (SEOZoom): domain authority metric focused on Italian SERPs, on a 0-100 scale."
                                        aria-label="What is ZA?">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
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
                                        class="metric-info-btn text-cyan-600 text-[11px]"
                                        data-info="Authority Score (Semrush): overall domain quality score (0-100) based on backlinks, traffic, and trust signals."
                                        aria-label="What is AS?">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
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
                                        class="metric-info-btn text-cyan-600 text-[11px]"
                                        data-info="SEOZoom Traffic: estimated organic traffic from SEOZoom, especially useful for Italian-market visibility."
                                        aria-label="What is SEO Zoom?">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
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
                                        class="metric-info-btn text-cyan-600 text-[11px]"
                                        data-info="Ratio between Trust Flow and Citation Flow. Close to 1 is ideal; TF > CF suggests stronger quality, CF > TF may indicate spammy links."
                                        aria-label="What is TF vs CF?">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
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
                                        class="metric-info-btn text-cyan-600 text-[11px]"
                                        data-info="Estimated monthly organic visitors from Semrush. Higher traffic means more visibility; 5k+ good, 50k+ excellent."
                                        aria-label="What is Semrush Traffic?">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
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
                                        class="metric-info-btn text-cyan-600 text-[11px]"
                                        data-info="Number of keywords the domain ranks for. More keywords usually mean stronger organic visibility; 1k+ is strong."
                                        aria-label="What is Ahrefs Keyword?">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
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
                                        class="metric-info-btn text-cyan-600 text-[11px]"
                                        data-info="Estimated monthly organic visitors from Ahrefs. Higher traffic means more exposure; 5k+ good, 50k+ excellent."
                                        aria-label="What is Ahrefs Traffic?">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
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
                                        class="metric-info-btn text-cyan-600 text-[11px]"
                                        data-info="Traffic efficiency per keyword. Higher means each keyword brings more visits; low ratios may suggest weak rankings."
                                        aria-label="What is Keywords vs Traffic?">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    Traffic efficiency per keyword. Higher means each keyword brings more visits; low ratios may suggest weak rankings.
                                </span>
                            </span>
                        </span>
                    </th>
                    <th class="whitespace-nowrap px-4 py-2">SEO Metrics Date</th>
                    <th class="whitespace-nowrap px-4 py-2">Betting</th>
                    <th class="whitespace-nowrap px-4 py-2">Trading</th>
                    <th class="whitespace-nowrap px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            LINK LIFETIME
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-cyan-600 text-[11px]"
                                        data-info="Link duration. Permanent means it should stay online indefinitely; yearly options indicate minimum guaranteed duration."
                                        aria-label="What is Link Lifetime?">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
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
                                        class="metric-info-btn text-cyan-600 text-[11px]"
                                        data-info="Yes means the publisher accepts multiple backlinks in one guest post."
                                        aria-label="What does More than 1 link mean?">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
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
                                        class="metric-info-btn text-cyan-600 text-[11px]"
                                        data-info="Indicates whether links are tagged sponsored/nofollow. No means full SEO value, yes means rel='sponsored' or rel='nofollow'."
                                        aria-label="What is Sponsored Tag?">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    Indicates whether links are tagged sponsored/nofollow. No means full SEO value, yes means rel='sponsored' or rel='nofollow'.
                                </span>
                            </span>
                        </span>
                    </th>
                    <th class="whitespace-nowrap px-4 py-2">Social Media Sharing</th>
                    <th class="whitespace-nowrap px-4 py-2">Post in Homepage</th>
                    <th class="whitespace-nowrap px-4 py-2">Date Added</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    @include('websites.partials.note-modal')
    @include('websites.partials.contact-modal')
@endsection

@push('scripts')
    <script>
        $(function () {
            const renderNote = function (data) {
                if (!data) return '';
                const safe = $('<div>').text(data).html();
                return `
                    <a href="#" class="note-link text-cyan-700" data-note="${safe}">
                        <i class="fas fa-comment-dots"></i>
                    </a>`;
            };
            const decodeHtml = (value) => $('<textarea/>').html(value ?? '').text();
            const dt = (v) => v ? new Date(v).toLocaleDateString('en-GB') : '';

            const table = $('#favoritesTable').DataTable({
                processing: true,
                serverSide: true,
                dom: "<'dt-top flex items-center justify-between mb-2'<'dt-left flex items-center gap-3'l<'dt-search'>>>" +
                    "tr" +
                    "<'flex items-center justify-between mt-2'<'dt-info'i><'dt-pagination'p>>",
                ajax: {
                    url: "{{ route('admin.users.favorites.data', $user) }}",
                    type: "POST",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                },
                columns: [{ data: 'id', name: 'id'},
                    { data: 'domain_name', name: 'domain_name' },
                    {
                        data: 'notes',
                        name: 'notes',
                        className: 'text-center',
                        render: renderNote
                    },
                                        {
                        data: 'extra_notes',
                        name: 'extra_notes',
                        className: 'text-center',
                        render: renderNote
                    },
                                        { data: 'status', name: 'status', className: 'text-center'},
                    { data: 'country_name', name: 'country.country_name', className: 'text-center', },
                    { data: 'language_name', name: 'language.name',  className: 'text-center', },
                    {
                        data: 'contact_name',
                        name: 'contact.name',
                        render: function(data, type, row) {
                                                        if (!row.contact_id) return "No Publisher";
                            return `
                        <a href="#"
                           class="contact-link text-blue-600 underline"
                           data-contact-id="${row.contact_id}">
                            ${data}
                        </a>`;
                        }
                    },
                    { data: 'currency_code', name: 'currency_code', className: 'text-center'},
                    {
                        data: 'publisher_price',
                        name: 'publisher_price',
                        className: 'text-center',
                        render: function (data, type, row) {
                            if (data === null || data === undefined) {
                                return '';
                            }
                            // Wrap the numeric value in <strong>
                            return '<strong> € ' + data + '</strong>';
                        }
                    },
                    {
                        data: 'no_follow_price',
                        name: 'no_follow_price',
                        className: 'text-center',
                        render: function (data, type, row) {
                            if (data === null || data === undefined) {
                                return '';
                            }
                            return '<strong> € ' + data + '</strong>';
                        }
                    },
                    {
                        data: 'special_topic_price',
                        name: 'special_topic_price',
                        className: 'text-center',
                        render: function (data, type, row) {
                            if (data === null || data === undefined) {
                                return '';
                            }
                            return '<strong> € ' + data + '</strong>';
                        }
                    },
                    {
                        data: 'link_insertion_price',
                        name: 'link_insertion_price',
                        className: 'text-center',
                        render: function (data, type, row) {
                            if (data === null || data === undefined) {
                                return '';
                            }
                            return '<strong> € ' + data + '</strong>';
                        }
                    },
                    {
                        data:'banner_price',
                        name:'banner_price',
                        className: 'text-center',
                        render: function (data, type, row) {
                            if (data === null || data === undefined) {
                                return '';
                            }
                            return '<strong> € ' + data + '</strong>';
                        }},
                    {
                        data:'sitewide_link_price',
                        name:'sitewide_link_price',
                        className: 'text-center',
                        render: function (data, type, row) {
                            if (data === null || data === undefined) {
                                return '';
                            }
                            return '<strong> € ' + data + '</strong>';
                        }},

                    {
                        data: 'kialvo_evaluation',
                        name: 'kialvo_evaluation',
                        className: 'text-center',
                        render: function (data, type, row) {
                            if (data === null || data === undefined) {
                                return '';
                            }
                            return '<strong> € ' + data + '</strong>';
                        }
                    },
                    {
                        data: 'profit',
                        name: 'profit',
                        className: 'text-center',
                        render: function (data, type, row) {
                            if (data === null || data === undefined) {
                                return '';
                            }
                            return '<strong> € ' + data + '</strong>';
                        }
                    },
                    { data:'date_publisher_price', name:'date_publisher_price',
                       className:'text-center', render:dt},

                    { data: 'linkbuilder', name: 'linkbuilder', className: 'text-center'},
                    { data: 'type_of_website', name: 'type_of_website', className: 'text-center', },
                    { data: 'categories_list', name: 'categories_list', className: 'text-center', },
                    { data: 'DA', name: 'DA', className: 'text-center', },
                    { data: 'PA', name: 'PA', className: 'text-center', },
                    { data: 'TF', name: 'TF', className: 'text-center', },
                    { data: 'CF', name: 'CF', className: 'text-center', },
                    { data: 'DR', name: 'DR', className: 'text-center', },
                    { data: 'UR', name: 'UR', className: 'text-center', },
                    { data: 'ZA', name: 'ZA', className: 'text-center', },
                    { data: 'as_metric', name: 'as_metric', className: 'text-center', },
                    { data: 'seozoom', name: 'seozoom', className: 'text-center', },
                    { data: 'TF_vs_CF', name: 'TF_vs_CF', className: 'text-center', },
                    { data: 'semrush_traffic', name: 'semrush_traffic', className: 'text-center', },
                    { data: 'ahrefs_keyword', name: 'ahrefs_keyword', className: 'text-center', },
                    { data: 'ahrefs_traffic', name: 'ahrefs_traffic', className: 'text-center', },
                    { data: 'keyword_vs_traffic', name: 'keyword_vs_traffic', className: 'text-center', },
                    { data:'seo_metrics_date', name:'seo_metrics_date',
                      className:'text-center', render:dt},
                    { data: 'betting', name: 'betting', className: 'text-center',
                        render: function (data, type, row) {
                            if (data === 1 )  {
                                return 'YES';
                            }else if(data === 0)

                            return 'NO';
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
                    { data: 'permanent_link', name: 'permanent_link', className: 'text-center',
                        render: function (data, type, row) {
                            if (data === 1 )  {
                                return 'YES';
                            }else if(data === 0)

                                return 'NO';
                        }
                    },
                    { data: 'more_than_one_link', name: 'more_than_one_link', className: 'text-center',
                        render: function (data, type, row) {
                            if (data === 1 )  {
                                return 'YES';
                            }else if(data === 0)

                                return 'NO';
                        }
                    },
                    { data: 'copywriting', name: 'copywriting', className: 'text-center', defaultContent: '',  render: function (data, type, row) {
                            if (Number(data) === 1)  {
                                return 'PROVIDED';
                            }
                            if (Number(data) === 0) {
                                return 'NOT PROVIDED';
                            }
                            return '';
                        }
                    },
                    { data: 'no_sponsored_tag', name: 'no_sponsored_tag', className: 'text-center',  render: function (data, type, row) {
                            if (Number(data) === 1)  {
                                return 'NO';
                            }
                            if (Number(data) === 0) {
                                return 'YES';
                            }
                            return '';
                        }
                    },
                    { data: 'social_media_sharing', name: 'social_media_sharing', className: 'text-center',  render: function (data, type, row) {
                            if (data === 1 )  {
                                return 'YES';
                            }else if(data === 0)

                                return 'NO';
                        }
                    },
                    { data: 'post_in_homepage', name: 'post_in_homepage', className: 'text-center',  render: function (data, type, row) {
                            if (data === 1 )  {
                                return 'YES';
                            }else if(data === 0)

                                return 'NO';
                        }
                    },
                    { data:'created_at', name:'date_added',
                     className:'text-center', render:dt}
                ],
                order: [[1, 'desc']],
                responsive: false,
                autoWidth: false
            });

            $(table.table().container()).find('.dt-search').append($('#favoritesTableSearchWrap'));

            let searchTimer;
            $('#favoritesTableSearch').on('input', function() {
                const value = this.value;
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => table.search(value).draw(), 300);
            });
            $('#favoritesTableSearch').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    clearTimeout(searchTimer);
                    table.search(this.value).draw();
                }
            });

            $(document).on('click', '.note-link', function (e) {
                e.preventDefault();
                $('#modalNoteBody').text(decodeHtml($(this).data('note')));
                $('#noteModal').removeClass('hidden');
            });
            $(document).on('click', '#closeNoteModal, #closeNoteModalBottom', function () {
                $('#noteModal').addClass('hidden');
            });

            // Contact modal
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

                            let websitesHtml = '';
                            if (c.websites && c.websites.length > 0) {
                                websitesHtml = '<ul>';
                                c.websites.forEach(function (w) {
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

            $('#closeContactModal, #closeContactModalBottom').on('click', function() {
                $('#contactModal').addClass('hidden');
            });
        });
    </script>
@endpush
