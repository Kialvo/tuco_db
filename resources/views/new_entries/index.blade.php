@php
    // Fields available for bulk edit (mirrors Websites; keep New-Entry statuses)
    $bulkEditable = [
        'status','country_id','language_id','linkbuilder','type_of_website',
        'contact_id','currency_code','publisher_price','no_follow_price',
        'special_topic_price','link_insertion_price','banner_price','sitewide_link_price',
        'kialvo_evaluation','profit','date_publisher_price',
        'DA','PA','TF','CF','DR','UR','ZA','as_metric','seozoom',
        'TF_vs_CF','semrush_traffic','ahrefs_keyword','ahrefs_traffic',
        'keyword_vs_traffic','seo_metrics_date',
        'betting','trading','permanent_link','more_than_one_link',
        'copywriting','no_sponsored_tag','social_media_sharing','post_in_homepage',
        'category_ids',              // many-to-many
        'recalculate_totals',        // pseudo
    ];
@endphp

@extends('layouts.dashboard')

{{-- Page header --}}
@section('pageHeader')
    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between flex-shrink-0">
        <div>
            <h1 class="text-base font-bold text-gray-800">New Entries</h1>
            <p class="text-xs text-gray-500 mt-0.5">Pre-publish queue and outreach pipeline.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('new_entries.import.index') }}"
               class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300">
                <x-icon name="upload" size="sm" /> Import CSV
            </a>
            <a href="{{ route('new_entries.create') }}"
               class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-sm">
                <x-icon name="plus" size="sm" /> Create Entry
            </a>
        </div>
    </div>
@endsection

@section('filters')
    @include('new_entries.partials.admin-filter-panel')
@endsection

@section('content')
    <div class="px-6 py-4 bg-gray-50 min-h-screen text-xs">
        {{-- Hidden no-op placeholder so existing JS that targets #toggleFiltersBtn doesn't error --}}
        <button id="toggleFiltersBtn" class="hidden" aria-hidden="true"></button>

        {{-- ACTION BAR --}}
        <div id="actionBar"
             class="flex items-center flex-wrap gap-2 mb-3 px-4 py-2.5 bg-white border border-gray-200 rounded-xl shadow-card">
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

        <div id="newEntriesTableSearchWrap" class="table-search-wrap">
            <div class="flex items-center w-72 border border-gray-300 rounded-md bg-white shadow-sm
                        focus-within:ring-1 focus-within:ring-green-500 focus-within:border-green-500">
                <span class="px-3 text-gray-400 text-base leading-none">
                    <x-icon name="search" size="sm" class="inline" />
                </span>
                <input id="newEntriesTableSearch" type="text"
                       class="w-full bg-transparent border-0 focus:ring-0 focus:outline-none py-2 pr-3 text-sm leading-5"
                       placeholder="Search new entries...">
            </div>
        </div>

        {{-- TABLE --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-card">
            <table id="newEntriesTable" class="text-xs text-gray-700 w-full min-w-[1550px]">
                <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-[12px] uppercase text-gray-500 tracking-wider">
                    <th class="px-4 py-2">
                        <input type="checkbox" id="chkAll" class="form-checkbox h-4 w-4 text-green-600">
                    </th>
                    <th class="px-4 py-2">ID</th>
                    <th class="px-4 py-2">Domain</th>
                    <th class="px-4 py-2">Extra Notes</th>

                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Country</th>
                    <th class="px-4 py-2">Language</th>
                    <th class="px-4 py-2">Publisher</th>
                    <th class="px-4 py-2">Currency</th>

                    <th class="px-4 py-2">Publisher Price</th>
                    <th class="px-4 py-2">No Follow Price</th>
                    <th class="px-4 py-2">Special Topic Price</th>
                    <th class="px-4 py-2">Price</th>
                    <th class="px-4 py-2">Sensitive Topic Price</th>
                    <th class="px-4 py-2">Link Insertion Price</th>
                    <th class="px-4 py-2">Banner &euro;</th>
                    <th class="px-4 py-2">Site-wide &euro;</th>
                    {{-- Kialvo Evaluation (data key stays kialvo_evaluation) --}}
                    <th class="px-4 py-2">Kialvo Evaluation</th>
                    <th class="px-4 py-2">Profit</th>

                    <th class="px-4 py-2">Date Publisher Price</th>
                    <th class="px-4 py-2">Linkbuilder</th>
                    <th class="px-4 py-2">Type of Website</th>
                    <th class="px-4 py-2">Categories</th>

                    <th class="px-4 py-2">DA</th>
                    <th class="px-4 py-2">PA</th>
                    <th class="px-4 py-2">TF</th>
                    <th class="px-4 py-2">CF</th>
                    <th class="px-4 py-2">DR</th>
                    <th class="px-4 py-2">UR</th>
                    <th class="px-4 py-2">ZA</th>
                    <th class="px-4 py-2">AS</th>

                    <th class="px-4 py-2">SEO Zoom</th>
                    <th class="px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            TF vs CF
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-green-600 text-[11px]"
                                        data-info="Majestic Trust Flow divided by Citation Flow. It compares link quality vs quantity; usually, higher is better."
                                        aria-label="What is TF vs CF?">
                                    <x-icon name="info" size="sm" class="inline" />
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case whitespace-normal break-words font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    Majestic Trust Flow divided by Citation Flow. It compares link quality vs quantity; usually, higher is better.
                                </span>
                            </span>
                        </span>
                    </th>
                    <th class="px-4 py-2">Semrush Traffic</th>
                    <th class="px-4 py-2">Ahrefs Keyword</th>
                    <th class="px-4 py-2">Ahrefs Traffic</th>
                    <th class="px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            Keywords vs Traffic
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-green-600 text-[11px]"
                                        data-info="Compares ranking keywords with estimated visits. Higher generally means keyword visibility turns into stronger traffic."
                                        aria-label="What is Keywords vs Traffic?">
                                    <x-icon name="info" size="sm" class="inline" />
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case whitespace-normal break-words font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    Compares ranking keywords with estimated visits. Higher generally means keyword visibility turns into stronger traffic.
                                </span>
                            </span>
                        </span>
                    </th>
                    <th class="px-4 py-2">
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
                    <th class="px-4 py-2">
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
                    <th class="px-4 py-2">
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
                    <th class="px-4 py-2">KW/Traffic Ratio</th>
                    <th class="px-4 py-2">SEO Metrics Date</th>

                    <th class="px-4 py-2">Betting</th>
                    <th class="px-4 py-2">Trading</th>
                    <th class="px-4 py-2">Permanent Link</th>
                    <th class="px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            More than 1 link
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-green-600 text-[11px]"
                                        data-info="YES means the publisher can place multiple links in one article/page, not only one link."
                                        aria-label="What does More than 1 link mean?">
                                    <x-icon name="info" size="sm" class="inline" />
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case whitespace-normal break-words font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    YES means the publisher can place multiple links in one article/page, not only one link.
                                </span>
                            </span>
                        </span>
                    </th>
                    <th class="px-4 py-2">Copywriting</th>
                    <th class="px-4 py-2">
                        <span class="inline-flex items-center gap-1">
                            Sponsored Tag
                            <span class="relative inline-flex group cursor-help">
                                <button type="button"
                                        class="metric-info-btn text-green-600 text-[11px]"
                                        data-info="Shows whether links are marked rel=&quot;sponsored&quot;. YES means sponsored-tagged links, often with lower SEO impact."
                                        aria-label="What is Sponsored Tag?">
                                    <x-icon name="info" size="sm" class="inline" />
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case whitespace-normal break-words font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
                                    Shows whether links are marked rel="sponsored". YES means sponsored-tagged links, often with lower SEO impact.
                                </span>
                            </span>
                        </span>
                    </th>
                    <th class="px-4 py-2">Social Sharing</th>
                    <th class="px-4 py-2">Post in Homepage</th>

                    {{-- extra --}}
                    <th class="px-4 py-2">1st Contact</th>
                    <th class="px-4 py-2">Copied</th>

                    <th class="px-4 py-2">Date Added</th>
                    <th class="px-4 py-2">Action</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    {{-- Modals (same pattern as Websites) --}}
    @include('new_entries.partials.contact-modal')
    @include('new_entries.partials.note-modal')
    @include('new_entries.partials.bulk-modal')
    @include('partials.domain_storage_drawer')
@endsection

@push('scripts')
    <script>
        $(function () {
            /* ========== helpers (same as Websites) ========== */
            const statusMap = [
                {value:'never_opened',            label:'Never Opened',           tone:'bg-gray-100 text-gray-500 ring-gray-200'},
                {value:'read_but_never_answered', label:'Read but never answered',tone:'bg-amber-100 text-amber-700 ring-amber-200'},
                {value:'waiting_for_first_answer',label:'Waiting for 1st answer', tone:'bg-blue-100 text-blue-700 ring-blue-200'},
                {value:'refused_by_us',           label:'Refused by us',          tone:'bg-red-100 text-red-700 ring-red-200'},
                {value:'publisher_refused',       label:'Publisher refused',      tone:'bg-red-100 text-red-700 ring-red-200'},
                {value:'negotiation',             label:'Negotiation',            tone:'bg-blue-100 text-blue-700 ring-blue-200'},
                {value:'active',                  label:'Active',                 tone:'bg-green-100 text-green-700 ring-green-200'},
            ];
            const statusByValue = Object.fromEntries(statusMap.map(s => [s.value, s]));
            function pillHTML(cur, id){
                const info = statusByValue[String(cur)] || { label: (cur ? String(cur).replace(/_/g,' ') : '—'), tone:'bg-gray-100 text-gray-700 ring-gray-200' };
                const empty = !cur ? 'text-gray-300' : '';
                return `<button type="button" class="status-pill inline-flex items-center gap-1 whitespace-nowrap px-2.5 py-0.5 rounded-full text-[11px] font-medium ring-1 ring-inset ${info.tone} ${empty} hover:opacity-80 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-green-500" data-id="${id}" data-status="${cur||''}">
                            ${info.label}
                            <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>`;
            }
            const emDash = '<span class="text-gray-300">—</span>';
            const money = v => (v==null || v==='' ? emDash : `<span class="font-semibold text-gray-800">€ ${v}</span>`);
            const profitFmt = v => {
                if (v==null || v==='') return emDash;
                const neg = Number(v) < 0;
                return `<span class="font-semibold ${neg ? 'text-red-600' : 'text-gray-800'}">€ ${v}</span>`;
            };
            const renderMetric = v => (v==null || v==='' ? emDash : v);
            const renderCurrencyPill = v => v
                ? `<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider bg-gray-100 text-gray-700 ring-1 ring-inset ring-gray-200">${String(v).toUpperCase()}</span>`
                : emDash;
            const yesNoPill = v => v
                ? '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-green-100 text-green-700 ring-1 ring-inset ring-green-200">YES</span>'
                : '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-500 ring-1 ring-inset ring-gray-200">NO</span>';
            const yesNo = yesNoPill;
            const dateFmt = v => (v ? new Date(v).toLocaleDateString('en-GB') : emDash);
            const decodeHtml = (value) => $('<textarea/>').html(value ?? '').text();
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
            const newEntriesThead = document.querySelector('#newEntriesTable thead');
            if (newEntriesThead) {
                const blockSortFromInfoButton = (event) => {
                    if (!event.target.closest('.metric-info-btn')) return;
                    event.preventDefault();
                    event.stopPropagation();
                    event.stopImmediatePropagation();
                };
                ['mousedown', 'mouseup', 'pointerdown', 'pointerup', 'touchstart', 'touchend'].forEach((eventName) => {
                    newEntriesThead.addEventListener(eventName, blockSortFromInfoButton, true);
                });
                newEntriesThead.addEventListener('keydown', (event) => {
                    const button = event.target.closest('.metric-info-btn');
                    if (!button) return;
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        event.stopPropagation();
                        event.stopImmediatePropagation();
                        showInfoPopup(button.getAttribute('data-info'));
                    }
                }, true);
                newEntriesThead.addEventListener('click', (event) => {
                    const button = event.target.closest('.metric-info-btn');
                    if (!button) return;
                    event.preventDefault();
                    event.stopPropagation();
                    event.stopImmediatePropagation();
                    showInfoPopup(button.getAttribute('data-info'));
                }, true);
            }

            // Toasters + UNDO (identical to Websites)
            const toast = m => Swal.fire({ toast:true, position:'top-end', icon:'success', title:m, showConfirmButton:false, timer:1500 });
            const oops  = m => Swal.fire({ toast:true, position:'top-end', icon:'error', title:m, showConfirmButton:false, timer:2000 });
            function toastUndo (msg, token) {
                Swal.fire({
                    toast:true, position:'top-end', icon:'info',
                    html: `<span class="font-semibold">${msg}</span>
                   <button id="undoBtn" style="background:#f59e0b" class="ml-3 px-2 py-[2px] rounded text-xs font-bold">UNDO</button>`,
                    background:'#2563eb', color:'#fff',
                    showConfirmButton:false, timer:4000, timerProgressBar:true,
                    didOpen: () => {
                        document.getElementById('undoBtn').onclick = () => {
                            Swal.close();
                            fetch("{{ route('new_entries.rollback') }}", {
                                method:'POST',
                                headers:{'Content-Type':'application/json','X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')},
                                body: JSON.stringify({ token })
                            })
                                .then(r=>r.json())
                                .then(r => { toast(r.message); tbl.ajax.reload(null,false); })
                                .catch(()=> oops('Failed to undo'));
                        };
                    }
                });
            }

            // widgets
            flatpickr('#filterFirstFrom',{dateFormat:'Y-m-d',allowInput:true});
            flatpickr('#filterFirstTo'  ,{dateFormat:'Y-m-d',allowInput:true});

            /* ========== DataTable (same renderers/order as Websites) ========== */
            window.tbl = $('#newEntriesTable').DataTable({
                processing:true, serverSide:true,
                dom: "<'dt-toolbar-top'<'flex items-center gap-3'l<'dt-search'>>>" +
                     "<'dt-scroll'rt>" +
                     "<'dt-toolbar-bottom'ip>",
                ajax:{
                    url:"{{ route('new_entries.data') }}",
                    type:"POST",
                    headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')},
                    data:d=>{
                        d.domain_name        = $('#filterDomainName').val();
                        d.status             = $('#filterStatus').val();
                        d.country_ids        = $('#filterCountries').val();
                        d.language_id        = $('#filterLanguage').val();
                        d.first_contact_from = $('#filterFirstFrom').val();
                        d.first_contact_to   = $('#filterFirstTo').val();
                    }
                },
                columns:[
                    {
                        data: 'id', orderable:false, searchable:false, className:'text-center',
                        render: id => `<input type="checkbox" class="rowChk form-checkbox h-4 w-4 text-green-600" value="${id}">`
                    },
                    { data:'id' },
                    {
                        data: 'domain_name',
                        render: function(data) {
                            if (!data) return '—';
                            return `<a href="#" class="domain-storage-link text-green-700 hover:underline font-medium" data-domain="${data}" title="View storage entries for ${data}">${data}</a>`;
                        }
                    },
                    {
                        data:'extra_notes',
                        render:d=>{
                            if(!d) return '';
                            const safe=$('<div>').text(d).html();
                            return `<a href="#" class="note-link text-green-700" data-note="${safe}">
                              <x-icon name="comment" size="sm" class="inline" /></a>`;
                        }
                    },

                    { data:'status', render:(d,t,r)=> t==='display' ? pillHTML(d,r.id) : d },
                    { data:'country_name',
                        render: function (data, type, row) {
                            if (! data) return '<span class="text-gray-300">—</span>';
                            const flag = row.country_iso
                                ? `<img src="https://flagcdn.com/48x36/${row.country_iso}.png" srcset="https://flagcdn.com/96x72/${row.country_iso}.png 2x" width="20" height="15" alt="" class="rounded-sm border border-gray-200" loading="lazy">`
                                : '';
                            return `<span class="inline-flex items-center gap-1.5">${flag}<span>${data}</span></span>`;
                        }
                    },
                    { data:'language_name' },
                    {
                        data: 'contact_name',
                        render: function(data, type, row) {
                            if (!row.contact_id) return "No Publisher";
                            return `
                        <a href="#" class="contact-link text-blue-600 underline"
                           data-contact-id="${row.contact_id}">
                           ${data ?? 'Contact'}
                        </a>`;
                        }
                    },
                    { data:'currency_code', render: renderCurrencyPill, className: 'text-center' },

                    { data:'publisher_price',      render:money,     className:'text-right' },
                    { data:'no_follow_price',      render:money,     className:'text-right' },
                    { data:'special_topic_price',  render:money,     className:'text-right' },
                    { data:'price',                render:money,     className:'text-right' },
                    { data:'sensitive_topic_price',render:money,     className:'text-right' },
                    { data:'link_insertion_price', render:money,     className:'text-right' },
                    { data:'banner_price',         render:money,     className:'text-right' },
                    { data:'sitewide_link_price',  render:money,     className:'text-right' },

                    { data:'kialvo_evaluation',    render:money,     className:'text-right' },
                    { data:'profit',               render:profitFmt, className:'text-right' },

                    { data:'date_publisher_price', render:dateFmt, className:'text-center' },
                    { data:'linkbuilder',          className:'text-center' },
                    { data:'type_of_website',      className:'text-center' },
                    { data:'categories_list', className:'text-center max-w-[160px]',
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

                    { data:'DA', render:renderMetric, className:'text-right' }, { data:'PA', render:renderMetric, className:'text-right' },
                    { data:'TF', render:renderMetric, className:'text-right' }, { data:'CF', render:renderMetric, className:'text-right' },
                    { data:'DR', render:renderMetric, className:'text-right' }, { data:'UR', render:renderMetric, className:'text-right' },
                    { data:'ZA', render:renderMetric, className:'text-right' }, { data:'as_metric', render:renderMetric, className:'text-right' },

                    { data:'seozoom', render:renderMetric, className:'text-right' }, { data:'TF_vs_CF', render:renderMetric, className:'text-right' },
                    { data:'semrush_traffic', render:renderMetric, className:'text-right' }, { data:'ahrefs_keyword', render:renderMetric, className:'text-right' },
                    { data:'ahrefs_traffic', render:renderMetric, className:'text-right' }, { data:'keyword_vs_traffic', render:renderMetric, className:'text-right' },
                    { data:'ms',               type:'number', render:renderMetric, className:'text-right' },
                    { data:'organic_keywords', type:'number', render:renderMetric, className:'text-right' },
                    { data:'organic_traffic',  type:'number', render:renderMetric, className:'text-right' },
                    { data:'kw_traffic_ratio', type:'number', render:renderMetric, className:'text-right' },
                    { data:'seo_metrics_date', render:dateFmt, className:'text-center' },

                    { data:'betting', render:yesNo, className:'text-center' },
                    { data:'trading',            render:yesNo, className:'text-center' },
                    { data:'permanent_link',     render:yesNo, className:'text-center' },
                    { data:'more_than_one_link', render:yesNo, className:'text-center' },
                    { data:'copywriting',        render:d=>d?'PROVIDED':'NOT PROVIDED', className:'text-center' },
                    { data:'no_sponsored_tag',   render:yesNo, className:'text-center' },
                    { data:'social_media_sharing', render:yesNo, className:'text-center' },
                    { data:'post_in_homepage',   render:yesNo, className:'text-center' },

                    { data:'first_contact_date', render:dateFmt, className:'text-center' },
                    { data:'copied_to_overview', render:d=> (d==0||d==='0')?'NO':'YES', className:'text-center' },

                    { data:'date_added',         render:dateFmt, className:'text-center' },

                    { data:'action', orderable:false, searchable:false }
                ],
                // IMPORTANT: order by the ID column (index 1) â€” first col is the checkbox
                order: [[1, 'desc']],
                responsive:false,
                autoWidth:false,
                scrollX:true,
                language: {
                    lengthMenu:   'Show _MENU_ websites',
                    info:         'Showing _START_ to _END_ of _TOTAL_ websites',
                    infoFiltered: '(filtered from _MAX_ total websites)',
                    infoEmpty:    'Showing 0 to 0 of 0 websites',
                }
            });

            // Sticky header
            if (window.initDtStickyHeader) window.initDtStickyHeader(tbl);

            // Move search box into the DataTable header (next to "Show entries")
            $(tbl.table().container()).find('.dt-search').append($('#newEntriesTableSearchWrap'));

            // Table search (debounced to avoid slow typing)
            let newEntriesSearchTimer;
            $('#newEntriesTableSearch').on('input', function() {
                const value = this.value;
                clearTimeout(newEntriesSearchTimer);
                newEntriesSearchTimer = setTimeout(() => {
                    tbl.search(value).draw();
                }, 300);
            });
            $('#newEntriesTableSearch').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    clearTimeout(newEntriesSearchTimer);
                    tbl.search(this.value).draw();
                }
            });

            // status inline change (replaced by pill popover below; legacy <select> path kept inert)
            $(document).on('change', '.status-dd-disabled', function () {
                const $sel = $(this), newVal = $sel.val();
                $.ajax({
                    url: `{{ url('/new-entries') }}/${$sel.data('id')}/status`,
                    type: 'PUT',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: { status: newVal },
                    success: () => {
                        Swal.fire({ toast:true, position:'top-end', timer:2500, showConfirmButton:false,
                            icon:'success', title:`Status changed to â€œ${(statusMap.find(s=>s.value===newVal)||{}).label || newVal}â€`});
                        tbl.ajax.reload(null, false);
                    },
                    error: () => {
                        Swal.fire({ toast:true, position:'top-end', timer:3000, showConfirmButton:false,
                            icon:'error', title:'Status update failed' });
                        tbl.ajax.reload(null, false);
                    }
                });
            });

            /* ===== Status pill popover (replaces inline <select>) ===== */
            const $statusPopover = $(`
                <div id="statusPopover" class="hidden absolute z-50 w-56 bg-white rounded-lg shadow-xl border border-gray-200 py-1">
                    ${statusMap.map(s => `
                        <button type="button" data-value="${s.value}"
                                class="status-pop-item w-full text-left px-3 py-1.5 text-sm hover:bg-gray-50 flex items-center gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold ring-1 ring-inset ${s.tone}">${s.label}</span>
                        </button>
                    `).join('')}
                </div>
            `).appendTo('body');
            let popoverPillId = null;
            function closeStatusPopover() {
                $statusPopover.addClass('hidden');
                popoverPillId = null;
            }
            $(document).on('click', '.status-pill', function (e) {
                e.preventDefault(); e.stopPropagation();
                const $btn = $(this);
                const id = $btn.data('id');
                const cur = String($btn.data('status') || '');
                if (popoverPillId === id && !$statusPopover.hasClass('hidden')) { closeStatusPopover(); return; }
                popoverPillId = id;
                $statusPopover.find('.status-pop-item').each(function () {
                    $(this).toggleClass('bg-gray-50 font-semibold', $(this).data('value') === cur);
                });
                const rect = this.getBoundingClientRect();
                $statusPopover.css({
                    top: (window.scrollY + rect.bottom + 4) + 'px',
                    left: (window.scrollX + rect.left) + 'px',
                }).removeClass('hidden');
            });
            $(document).on('click', '.status-pop-item', function (e) {
                e.preventDefault(); e.stopPropagation();
                const newVal = $(this).data('value');
                const id = popoverPillId;
                closeStatusPopover();
                if (!id) return;
                $.ajax({
                    url: `{{ url('/new-entries') }}/${id}/status`,
                    type: 'PUT',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: { status: newVal },
                    success: () => {
                        Swal.fire({ toast:true, position:'top-end', timer:2500, showConfirmButton:false,
                            icon:'success', title:'Status changed to ' + ((statusByValue[newVal]||{}).label || newVal) });
                        tbl.ajax.reload(null, false);
                    },
                    error: () => {
                        Swal.fire({ toast:true, position:'top-end', timer:3000, showConfirmButton:false,
                            icon:'error', title:'Status update failed' });
                        tbl.ajax.reload(null, false);
                    }
                });
            });
            $(document).on('click', () => closeStatusPopover());
            $(document).on('keydown', (e) => { if (e.key === 'Escape') closeStatusPopover(); });
            $(window).on('scroll resize', () => closeStatusPopover());

            // live selected count + enable/disable buttons (exactly like Websites)
            function updateSelCount(){ $('#selCount').text($('.rowChk:checked').length); }
            function toggleActionButtons(){
                const any=$('.rowChk:checked').length>0;
                $('#btnBulkEdit,#btnBulkRestore').prop('disabled', !any);
            }
            $(document).on('change','.rowChk,#chkAll', e=>{
                if(e.target.id==='chkAll'){
                    $('.rowChk').prop('checked',$('#chkAll').is(':checked'));
                }
                updateSelCount(); toggleActionButtons();
            });
            tbl.on('draw',()=>{ updateSelCount(); toggleActionButtons(); });

            // Search / Clear
            $('#btnSearch').on('click', () => {
                tbl.ajax.reload();
                window.buildFilterChips(() => tbl.ajax.reload());
            });
            $('#btnClear').on('click', () => {
                $('#filterForm input').val('');
                $('#filterStatus').val('');
                $('#filterLanguage').val('');
                $('#filterCountries').val('');
                $('#newEntriesTableSearch').val('');
                tbl.search('');
                tbl.ajax.reload();
                window.buildFilterChips(() => tbl.ajax.reload());
            });

            // NOTE modal
            $(document).on('click', '.note-link', function (e) {
                e.preventDefault();
                $('#modalNoteBody').text(decodeHtml($(this).data('note')));
                $('#noteModal').removeClass('hidden');
            });
            $(document).on('click', '#closeNoteModal, #closeNoteModalBottom', function () {
                $('#noteModal').addClass('hidden');
            });

            // CONTACT modal (same as Websites)
            $(document).on('click', '.contact-link', function(e) {
                e.preventDefault();
                const contactId = $(this).data('contact-id');
                $.ajax({
                    url: "{{ route('contacts.showAjax', '') }}/" + contactId,
                    type: 'GET',
                    success: function(response) {
                        if (response.status === 'success') {
                            const c = response.data;
                            $('#modalContactName').text(c.name ?? '');
                            $('#modalContactEmail').text(c.email ?? '');
                            $('#modalContactPhone').text(c.phone ?? '');
                            $('#modalContactFacebook').text(c.facebook ?? '');
                            $('#modalContactInstagram').text(c.instagram ?? '');

                            let websitesHtml = '';
                            if (c.websites && c.websites.length > 0) {
                                websitesHtml = '<ul>';
                                c.websites.forEach(function (w) {
                                    const url = "/websites/" + w.id;
                                    websitesHtml += `<li><a href="${url}" class="underline text-blue-600">${w.domain_name}</a></li>`;
                                });
                                websitesHtml += '</ul>';
                            } else {
                                websitesHtml = '<p>No websites found for this publisher.</p>';
                            }
                            $('#modalContactWebsites').html(websitesHtml);
                            $('#contactModal').removeClass('hidden');
                        } else {
                            alert('Could not load publisher info.');
                        }
                    },
                    error: function() { alert('Error fetching publisher info.'); }
                });
            });
            $('#closeContactModal, #closeContactModalBottom').on('click', function() {
                $('#contactModal').addClass('hidden');
            });

            // BULK-EDIT wiring (identical to Websites; routes point to new_entries.*)
            function buildBulkInput(){
                const field=$('#bulkField').val();
                const meta = window.bulkMeta[field] || {type:'text'};
                const wrap = $('#bulkInputWrapper');
                wrap.empty();

                if(field==='recalculate_totals'){
                    wrap.append('<p class="text-gray-500 text-xs">Nothing to fill in â€“ just click â€œSaveâ€.</p>');
                    return;
                }
                if(meta.type==='date'){
                    wrap.append(`<input id="bulkValue" type="date" class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-green-500">`);
                    return;
                }
                if(meta.type==='select'){
                    const none=`<option value="">-- Clear --</option>`;
                    const opts = Object.entries(meta.options || {}).map(([v,l])=>`<option value="${v}">${l}</option>`).join('');
                    wrap.append(`<select id="bulkValue" class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-green-500">${none}${opts}</select>`);
                    if($('#bulkValue option').length>15){ $('#bulkValue').select2({width:'resolve', dropdownAutoWidth:true}); }
                    return;
                }
                if(meta.type==='multiselect'){
                    const opts = Object.entries(meta.options || {}).map(([v,l])=>`<option value="${v}">${l}</option>`).join('');
                    wrap.append(`<select id="bulkValue" multiple class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-green-500">${opts}</select>`);
                    $('#bulkValue').select2({width:'resolve', dropdownAutoWidth:true});
                    return;
                }
                if(meta.type==='textarea'){
                    wrap.append(`<textarea id="bulkValue" rows="3" class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-green-500"></textarea>`);
                    return;
                }
                wrap.append(`<input id="bulkValue" type="text" class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-green-500">`);
            }
            $('#bulkField').on('change', buildBulkInput);

            $('#btnBulkEdit').on('click', function(){
                if ($('.rowChk:checked').length === 0) {
                    Swal.fire('Select at least one row first');
                    return;
                }
                $('#bulkIds').val(JSON.stringify($('.rowChk:checked').map((_, c) => +c.value).get()));
                $('#bulkField').val('recalculate_totals').trigger('change');
                buildBulkInput();
                $('#bulkEditModal').removeClass('hidden').addClass('flex');
            });
            $('#bulkCancel').on('click', () => $('#bulkEditModal').addClass('hidden').removeClass('flex'));

            $('#bulkSave').on('click', function(){
                const ids = $('.rowChk:checked').map((_, c) => parseInt(c.value, 10)).get();
                const field = $('#bulkField').val();
                let value   = $('#bulkValue').length ? $('#bulkValue').val() : '';
                if (Array.isArray(value)) value = value.join(',');
                if (!ids.length) { Swal.fire('No rows selected'); return; }

                $.ajax({
                    url : "{{ route('new_entries.bulkUpdate') }}",
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: { 'ids[]': ids, field, value },
                    traditional: true,
                    success : res => {
                        toast(res.message);
                        if (res.undo_token) { toastUndo('Update saved.', res.undo_token); }
                        $('#bulkEditModal').addClass('hidden').removeClass('flex');
                        $('#chkAll').prop('checked', false);
                        tbl.ajax.reload(null, false);
                    },
                    error  : xhr => Swal.fire('Error', xhr.responseJSON?.message ?? 'Server error','error')
                });
            });

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
                    $.post("{{ route('new_entries.rollback') }}",
                        { ids, _token: $('meta[name="csrf-token"]').attr('content') },
                        r => {
                            toast(r.message);
                            $('#chkAll').prop('checked', false);
                            tbl.ajax.reload(null, false);
                        }
                    ).fail(() => oops('Rollback failed'));
                });
            });

            // flash
            @if (session('status'))
            Swal.fire({ icon:'success', title:'Success', text:'{{ session('status') }}',
                timer:3000, timerProgressBar:true, showConfirmButton:false });
            @endif
        });

        // filters show/hide button
        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('toggleFiltersBtn');
            const panel = document.getElementById('filterForm');
            let visible = true;
            btn.addEventListener('click', () => {
                panel.classList.toggle('hidden');
                visible = !visible;
                btn.textContent = visible ? 'Hide Filters' : 'Show Filters';
            });

            // ─────────────────────────────────────────────────
            //  Sync DataforSEO button
            // ─────────────────────────────────────────────────
            $('#btnSyncDataForSeo').on('click', function () {
                const csrfToken = $('meta[name="csrf-token"]').attr('content');

                let ids     = $('.rowChk:checked').map((_, c) => parseInt(c.value, 10)).get();
                let syncAll = ids.length === 0;
                let syncLabel = syncAll ? 'all domains' : ids.length + ' selected domain(s)';

                const steps = [
                    { icon: '🛰️', text: 'Connecting to DataforSEO API...' },
                    { icon: '📡', text: 'Fetching Domain Rank (MS)...' },
                    { icon: '📊', text: 'Fetching Organic Keywords &amp; Traffic...' },
                    { icon: '💾', text: 'Writing data to database...' },
                ];
                let stepIndex = 0, elapsed = 0;
                const updateStep = () => {
                    const s = steps[Math.min(stepIndex, steps.length - 1)];
                    const mins = String(Math.floor(elapsed / 60)).padStart(2, '0');
                    const secs = String(elapsed % 60).padStart(2, '0');
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
                    didOpen: () => {
                        window._syncStepInterval = setInterval(updateStep, 1000);
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

                fetch("{{ route('new_entries.dataforseo.sync-selected') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(syncAll ? { sync_all: true } : { ids: ids })
                })
                .then(r => r.json())
                .then(data => {
                    const bar = document.getElementById('swal-sync-bar');
                    if (bar) bar.style.width = '100%';
                    setTimeout(() => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sync Complete!',
                            html: `<p style="color:#374151;">${data.message || 'All domains synced.'}</p>`,
                            confirmButtonText: 'Great!',
                            confirmButtonColor: '#4f46e5',
                        });
                    }, 600);
                })
                .catch(() => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Sync Failed',
                        text: 'Something went wrong. Please try again.',
                        confirmButtonColor: '#dc2626',
                    });
                });
            });

        });
    </script>
@endpush

