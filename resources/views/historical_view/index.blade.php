@extends('layouts.dashboard')

@section('content')
    <h1 class="text-lg font-bold text-gray-700 py-6">Historical View</h1>

    <div class="px-6 py-4 bg-gray-50 min-h-screen text-xs">

        {{-- ───── HEADER ───── --}}
        <div class="flex items-center gap-2 mb-4">
            <button id="toggleFiltersBtn"
                    class="inline-flex items-center gap-1
                   bg-gray-300 text-gray-700 px-3 py-1 rounded shadow text-xs
                   hover:bg-gray-400 focus:outline-none">
                <i class="fas fa-sliders-h text-[11px]"></i>
                <span>Hide Filters</span>
            </button>
            {{--  no Create button here  --}}
        </div>


        {{-- ───── FILTERS (4) ───── --}}
        <div id="filterForm"
             class="bg-white border border-gray-200 rounded shadow p-2 mb-8 inline-block">
            <div class="flex flex-wrap gap-2">
                {{-- Domain --}}
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Domain</label>
                    <input id="filterDomainName" type="text"
                           class="border border-gray-300 rounded px-2 py-2 w-32"
                           placeholder="example.com">
                </div>

                {{-- Status --}}
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Status</label>
                    <select id="filterStatus"
                            class="border border-gray-300 rounded px-2 py-2 w-40">
                        <option value="">-- Any --</option>
                        <option value="never_opened">Never Opened</option>
                        <option value="read_but_never_answered">Read but never answered</option>
                        <option value="waiting_for_first_answer">Waiting for 1st answer</option>
                        <option value="refused_by_us">Refused by us</option>
                        <option value="publisher_refused">Publisher refused</option>
                        <option value="negotiation">Negotiation</option>
                        <option value="active">Active</option>
                    </select>
                </div>

                {{-- Country --}}
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Country</label>
                    <select id="filterCountries"
                            class="border border-gray-300 rounded px-2 py-2 w-40">
                        <option value="">-- Any --</option>
                        @foreach($countries as $c)
                            <option value="{{ $c->id }}">{{ $c->country_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Language (single) -->
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Language</label>
                    <select id="filterLanguage"
                            class="border border-gray-300 rounded px-2 py-2 w-40">
                        <option value="">-- Any --</option>
                        @foreach($languages as $lang)
                            <option value="{{ $lang->id }}">{{ $lang->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- 1st-contact range --}}
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">1st Contact From</label>
                    <input id="filterFirstFrom" type="text"
                           class="border border-gray-300 rounded px-2 py-2 w-36"
                           placeholder="YYYY-MM-DD">
                </div>
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">To</label>
                    <input id="filterFirstTo" type="text"
                           class="border border-gray-300 rounded px-2 py-2 w-36"
                           placeholder="YYYY-MM-DD">
                </div>
            </div>

            {{-- search / clear --}}
            <div class="flex space-x-2 mt-3">
                <button id="btnSearch"
                        class="bg-cyan-600 text-white px-4 py-2 rounded shadow text-xs hover:bg-cyan-700">
                    Search
                </button>
                <button id="btnClear"
                        class="bg-gray-400 text-white px-4 py-2 rounded shadow text-xs hover:bg-gray-500">
                    Clear
                </button>
            </div>
        </div>

        {{-- ───── TABLE ───── --}}
        <div id="historicalTableSearchWrap" class="table-search-wrap">
            <div class="flex items-center w-72 border border-gray-300 rounded-md bg-white shadow-sm
                        focus-within:ring-1 focus-within:ring-cyan-500 focus-within:border-cyan-500">
                <span class="px-3 text-gray-400 text-base leading-none">
                    <i class="fas fa-search"></i>
                </span>
                <input id="historicalTableSearch" type="text"
                       class="w-full bg-transparent border-0 focus:ring-0 focus:outline-none py-2 pr-3 text-sm leading-5"
                       placeholder="Search historical view...">
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded shadow p-2 overflow-x-auto">
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
                    <th class="px-4 py-2">Sensitive Topic Price</th>
                    <th class="px-4 py-2">Link Insertion Price</th>
                    <th class="px-4 py-2">Banner €</th>
                    <th class="px-4 py-2">Site-wide €</th>
                    <th class="px-4 py-2">Price</th>

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
                                        class="metric-info-btn text-cyan-600 text-[11px]"
                                        data-info="Majestic Trust Flow divided by Citation Flow. It compares link quality vs quantity; usually, higher is better."
                                        aria-label="What is TF vs CF?">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
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
                                        class="metric-info-btn text-cyan-600 text-[11px]"
                                        data-info="Compares ranking keywords with estimated visits. Higher generally means keyword visibility turns into stronger traffic."
                                        aria-label="What is Keywords vs Traffic?">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
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
                                        class="metric-info-btn text-cyan-600 text-[11px]"
                                        data-info="YES means the publisher can place multiple links in one article/page, not only one link."
                                        aria-label="What does More than 1 link mean?">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
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
                                        class="metric-info-btn text-cyan-600 text-[11px]"
                                        data-info="Shows whether links are marked rel=&quot;sponsored&quot;. YES means sponsored-tagged links, often with lower SEO impact."
                                        aria-label="What is Sponsored Tag?">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden w-56 -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case font-normal leading-4 text-white shadow-lg group-hover:block group-focus-within:block">
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

            /* ═══ helpers ═══ */
            const statusMap = [
                {value:'never_opened',            label:'Never Opened'},
                {value:'read_but_never_answered', label:'Read but never answered'},
                {value:'waiting_for_first_answer',label:'Waiting for 1st answer'},
                {value:'refused_by_us',           label:'Refused by us'},
                {value:'publisher_refused',       label:'Publisher refused'},
                {value:'negotiation',             label:'Negotiation'},
                {value:'active',                  label:'Active'},
            ];
            const statusLabel = v => (statusMap.find(x => x.value === String(v))||{}).label || v;

            const money   = v=> v==null ? '' : `<strong>€ ${v}</strong>`;
            const yesNo   = v=> v ? 'YES' : 'NO';
            const dateFmt = v=> v ? new Date(v).toLocaleDateString('en-GB') : '';
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
                dom: "<'dt-top flex items-center justify-between mb-2'<'dt-left flex items-center gap-3'l<'dt-search'>>>" +
                    "tr" +
                    "<'flex items-center justify-between mt-2'<'dt-info'i><'dt-pagination'p>>",
                ajax:{
                    url:"{{ route('historical_view.data') }}",       // ← route changed
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
                            return `<a href="#" class="note-link text-cyan-700" data-note="${safe}">
                            <i class="fas fa-comment-dots"></i></a>`;
                        }},
                    {data:'status', render:statusLabel},        // ← plain text
                    {data:'country_name'}, {data:'language_name'}, {data:'contact_name'},
                    {data:'currency_code'},
                    {data:'publisher_price',      render:money},
                    {data:'no_follow_price',      render:money},
                    {data:'special_topic_price',  render:money},
                    {data:'sensitive_topic_price',render:money},
                    {data:'link_insertion_price', render:money},
                    {data:'banner_price',         render:money},
                    {data:'sitewide_link_price',  render:money},
                    {data:'price',                render:money},
                    {data:'kialvo_evaluation',    render:money},
                    {data:'profit',               render:money},
                    {data:'date_publisher_price', render:dateFmt},
                    {data:'linkbuilder'},
                    {data:'type_of_website'},
                    {data:'categories_list'},
                    {data:'DA'}, {data:'PA'}, {data:'TF'}, {data:'CF'},
                    {data:'DR'}, {data:'UR'}, {data:'ZA'}, {data:'as_metric'},
                    {data:'seozoom'}, {data:'TF_vs_CF'},
                    {data:'semrush_traffic'}, {data:'ahrefs_keyword'},
                    {data:'ahrefs_traffic'}, {data:'keyword_vs_traffic'},
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
                autoWidth:false
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
                $('#filterLanguage').val('');            // <— add
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
