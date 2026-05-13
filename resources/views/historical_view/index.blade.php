@extends('layouts.dashboard')
@section('title', 'Historical View')

@section('pageHeader')
    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between flex-shrink-0">
        <div>
            <h1 class="text-base font-bold text-gray-800">Historical View</h1>
            <p class="text-xs text-gray-500 mt-0.5">Archived and active new-entry records.</p>
        </div>
    </div>
@endsection

@section('filters')
    @include('new_entries.partials.admin-filter-panel')
@endsection

@section('content')
    <div class="px-6 py-4 bg-gray-50 min-h-screen text-xs">
        {{-- Hidden no-op placeholder so legacy JS that targets #toggleFiltersBtn doesn't error --}}
        <button id="toggleFiltersBtn" class="hidden" aria-hidden="true"></button>


        {{-- â”€â”€â”€â”€â”€ TABLE â”€â”€â”€â”€â”€ --}}
        <div id="historicalTableSearchWrap" class="table-search-wrap">
            <div class="flex items-center w-72 border border-gray-300 rounded-md bg-white shadow-sm
                        focus-within:ring-1 focus-within:ring-green-500 focus-within:border-green-500">
                <span class="px-3 text-gray-400 text-base leading-none">
                    <x-icon name="search" size="sm" class="inline" />
                </span>
                <input id="historicalTableSearch" type="text"
                       class="w-full bg-transparent border-0 focus:ring-0 focus:outline-none py-2 pr-3 text-sm leading-5"
                       placeholder="Search historical view...">
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-card">
            <table id="newEntriesTable" class="text-xs text-gray-700 w-full min-w-[1550px]">
                <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-[12px] uppercase text-gray-500 tracking-wider">
                    {{-- === same columns as Websites === --}}
                    <th class="px-4 py-2">ID</th>
                    <th class="px-4 py-2">Domain</th>
                    <th class="px-4 py-2">Extra Notes</th>

                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Country</th>
                    <th class="px-4 py-2">Language</th>
                    <th class="px-4 py-2">Contact</th>
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

                    <th class="px-4 py-2">DA</th><th class="px-4 py-2">PA</th>
                    <th class="px-4 py-2">TF</th><th class="px-4 py-2">CF</th>
                    <th class="px-4 py-2">DR</th><th class="px-4 py-2">UR</th>
                    <th class="px-4 py-2">ZA</th><th class="px-4 py-2">AS</th>

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

                    {{-- === extra columns === --}}
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
@endsection

@include('new_entries.partials.note-modal')

@push('scripts')
    <script>
        $(function () {

            /* â•â•â• helpers â•â•â• */
            const statusMap = [
                {value:'never_opened',            label:'Never Opened',           tone:'bg-gray-100 text-gray-500 ring-gray-200'},
                {value:'read_but_never_answered', label:'Read but never answered',tone:'bg-amber-100 text-amber-700 ring-amber-200'},
                {value:'waiting_for_first_answer',label:'Waiting for 1st answer', tone:'bg-blue-100 text-blue-700 ring-blue-200'},
                {value:'refused_by_us',           label:'Refused by us',          tone:'bg-red-100 text-red-700 ring-red-200'},
                {value:'publisher_refused',       label:'Publisher refused',      tone:'bg-red-100 text-red-700 ring-red-200'},
                {value:'negotiation',             label:'Negotiation',            tone:'bg-blue-100 text-blue-700 ring-blue-200'},
                {value:'active',                  label:'Active',                 tone:'bg-green-100 text-green-700 ring-green-200'},
                {value:'past',                    label:'Past',                   tone:'bg-gray-100 text-gray-600 ring-gray-200'},
            ];
            const statusLabel = v => (statusMap.find(x => x.value === String(v))||{}).label || v;
            const statusPill = v => {
                if (!v) return '<span class="text-gray-300">—</span>';
                const info = statusMap.find(x => x.value === String(v));
                const tone = info ? info.tone : 'bg-gray-100 text-gray-700 ring-gray-200';
                const label = info ? info.label : String(v).replace(/_/g,' ');
                return `<span class="inline-flex items-center whitespace-nowrap px-2.5 py-0.5 rounded-full text-[11px] font-medium ring-1 ring-inset ${tone}">${label}</span>`;
            };

            const emDash = '<span class="text-gray-300">—</span>';
            const money   = v=> (v==null || v==='') ? emDash : `<span class="font-semibold text-gray-800">€ ${v}</span>`;
            const profitFmt = v=> {
                if (v==null || v==='') return emDash;
                const neg = Number(v) < 0;
                return `<span class="font-semibold ${neg ? 'text-red-600' : 'text-gray-800'}">€ ${v}</span>`;
            };
            const renderMetric = v=> (v==null || v==='') ? emDash : v;
            const renderCurrencyPill = v=> v
                ? `<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider bg-gray-100 text-gray-700 ring-1 ring-inset ring-gray-200">${String(v).toUpperCase()}</span>`
                : emDash;
            const yesNo   = v=> v
                ? '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-green-100 text-green-700 ring-1 ring-inset ring-green-200">YES</span>'
                : '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-500 ring-1 ring-inset ring-gray-200">NO</span>';
            const dateFmt = v=> v ? new Date(v).toLocaleDateString('en-GB') : emDash;
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
            const historicalThead = document.querySelector('#newEntriesTable thead');
            if (historicalThead) {
                const blockSortFromInfoButton = (event) => {
                    if (!event.target.closest('.metric-info-btn')) return;
                    event.preventDefault();
                    event.stopPropagation();
                    event.stopImmediatePropagation();
                };
                ['mousedown', 'mouseup', 'pointerdown', 'pointerup', 'touchstart', 'touchend'].forEach((eventName) => {
                    historicalThead.addEventListener(eventName, blockSortFromInfoButton, true);
                });
                historicalThead.addEventListener('keydown', (event) => {
                    const button = event.target.closest('.metric-info-btn');
                    if (!button) return;
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        event.stopPropagation();
                        event.stopImmediatePropagation();
                        showInfoPopup(button.getAttribute('data-info'));
                    }
                }, true);
                historicalThead.addEventListener('click', (event) => {
                    const button = event.target.closest('.metric-info-btn');
                    if (!button) return;
                    event.preventDefault();
                    event.stopPropagation();
                    event.stopImmediatePropagation();
                    showInfoPopup(button.getAttribute('data-info'));
                }, true);
            }

            /* widgets */

            flatpickr('#filterFirstFrom',{dateFormat:'Y-m-d',allowInput:true});
            flatpickr('#filterFirstTo'  ,{dateFormat:'Y-m-d',allowInput:true});

            /* DataTable */
            let tbl = $('#newEntriesTable').DataTable({
                processing:true, serverSide:true,
                dom: "<'dt-toolbar-top'<'flex items-center gap-3'l<'dt-search'>>>" +
                     "<'dt-scroll'rt>" +
                     "<'dt-toolbar-bottom'ip>",
                ajax:{
                    url:"{{ route('historical_view.data') }}",       // â† route changed
                    type:"POST",
                    headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')},
                    data:d=>{
                        d.domain_name        = $('#filterDomainName').val();
                        d.status             = $('#filterStatus').val();
                        d.country_ids        = $('#filterCountries').val();   // multi
                        d.language_id        = $('#filterLanguage').val();    // single
                        d.first_contact_from = $('#filterFirstFrom').val();
                        d.first_contact_to   = $('#filterFirstTo').val();
                    }
                },
                columns:[
                    {data:'id'}, {data:'domain_name'},
                    {data:'extra_notes', render:d=>{
                            if(!d) return '';
                            const safe = $('<div>').text(d).html();
                            return `<a href="#" class="note-link text-green-700" data-note="${safe}">
                            <x-icon name="comment" size="sm" class="inline" /></a>`;
                        }},
                    {data:'status', render:(d,t)=> t==='display' ? statusPill(d) : (statusLabel(d) || '')},        // â† plain text
                    {data:'country_name',
                        render: function (data, type, row) {
                            if (! data) return '<span class="text-gray-300">—</span>';
                            const flag = row.country_iso
                                ? `<img src="https://flagcdn.com/48x36/${row.country_iso}.png" srcset="https://flagcdn.com/96x72/${row.country_iso}.png 2x" width="20" height="15" alt="" class="rounded-sm border border-gray-200" loading="lazy">`
                                : '';
                            return `<span class="inline-flex items-center gap-1.5">${flag}<span>${data}</span></span>`;
                        }
                    },
                    {data:'language_name'}, {data:'contact_name'},
                    {data:'currency_code', render: renderCurrencyPill, className:'text-center'},
                    {data:'publisher_price',      render:money,     className:'text-right'},
                    {data:'no_follow_price',      render:money,     className:'text-right'},
                    {data:'special_topic_price',  render:money,     className:'text-right'},
                    {data:'price',                render:money,     className:'text-right'},
                    {data:'sensitive_topic_price',render:money,     className:'text-right'},
                    {data:'link_insertion_price', render:money,     className:'text-right'},
                    {data:'banner_price',         render:money,     className:'text-right'},
                    {data:'sitewide_link_price',  render:money,     className:'text-right'},
                    {data:'kialvo_evaluation',    render:money,     className:'text-right'},
                    {data:'profit',               render:profitFmt, className:'text-right'},
                    {data:'date_publisher_price', render:dateFmt,   className:'text-center'},
                    {data:'linkbuilder'},
                    {data:'type_of_website'},
                    {data:'categories_list', className:'max-w-[160px]',
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
                    {data:'DA', render:renderMetric, className:'text-right'}, {data:'PA', render:renderMetric, className:'text-right'},
                    {data:'TF', render:renderMetric, className:'text-right'}, {data:'CF', render:renderMetric, className:'text-right'},
                    {data:'DR', render:renderMetric, className:'text-right'}, {data:'UR', render:renderMetric, className:'text-right'},
                    {data:'ZA', render:renderMetric, className:'text-right'}, {data:'as_metric', render:renderMetric, className:'text-right'},
                    {data:'seozoom', render:renderMetric, className:'text-right'}, {data:'TF_vs_CF', render:renderMetric, className:'text-right'},
                    {data:'semrush_traffic', render:renderMetric, className:'text-right'}, {data:'ahrefs_keyword', render:renderMetric, className:'text-right'},
                    {data:'ahrefs_traffic', render:renderMetric, className:'text-right'}, {data:'keyword_vs_traffic', render:renderMetric, className:'text-right'},
                    {data:'seo_metrics_date',     render:dateFmt},
                    {data:'betting',              render:yesNo},
                    {data:'trading',              render:yesNo},
                    {data:'permanent_link',       render:yesNo},
                    {data:'more_than_one_link',   render:yesNo},
                    {data:'copywriting',          render:d=>d?'PROVIDED':'NOT PROVIDED'},
                    {data:'no_sponsored_tag',     render:yesNo},
                    {data:'social_media_sharing', render:yesNo},
                    {data:'post_in_homepage',     render:yesNo},
                    {data:'first_contact_date',   render:dateFmt},
                    {data:'copied_to_overview',   render:d=> (d==0||d==='0')?'NO':'YES'},
                    {data:'date_added',           render:dateFmt},
                    {data:'action', orderable:false, searchable:false}
                ],
                order:[[0,'desc']],
                autoWidth:false,
                language: {
                    lengthMenu:   'Show _MENU_ websites',
                    info:         'Showing _START_ to _END_ of _TOTAL_ websites',
                    infoFiltered: '(filtered from _MAX_ total websites)',
                    infoEmpty:    'Showing 0 to 0 of 0 websites',
                }
            });

            // Move search box into the DataTable header (next to "Show entries")
            $(tbl.table().container()).find('.dt-search').append($('#historicalTableSearchWrap'));

            // Table search (debounced to avoid slow typing)
            let historicalSearchTimer;
            $('#historicalTableSearch').on('input', function() {
                const value = this.value;
                clearTimeout(historicalSearchTimer);
                historicalSearchTimer = setTimeout(() => {
                    tbl.search(value).draw();
                }, 300);
            });
            $('#historicalTableSearch').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    clearTimeout(historicalSearchTimer);
                    tbl.search(this.value).draw();
                }
            });

            /* search / clear */
            $('#btnSearch').click(()=>tbl.ajax.reload());
            $('#btnClear').click(function(){
                $('#filterForm input').val('');
                $('#filterStatus').val('');
                $('#filterLanguage').val('');            // <â€” add
                $('#filterCountries').val('');
                $('#historicalTableSearch').val('');
                tbl.search('');
                tbl.ajax.reload();
            });

            /* note modal */
            $(document).on('click','.note-link',function(e){
                e.preventDefault();
                $('#modalNoteBody').text(decodeHtml($(this).data('note')));
                $('#noteModal').removeClass('hidden');
            });
            $(document).on('click','#closeNoteModal,#closeNoteModalBottom',()=>$('#noteModal').addClass('hidden'));
        });

        /* toggle filters visibility */
        document.addEventListener('DOMContentLoaded',()=>{
            const btn   = document.getElementById('toggleFiltersBtn');
            const panel = document.getElementById('filterForm');
            let visible = true;
            btn.addEventListener('click',()=>{
                panel.classList.toggle('hidden');
                visible = !visible;
                btn.textContent = visible ? 'Hide Filters' : 'Show Filters';
            });
        });
    </script>
@endpush

