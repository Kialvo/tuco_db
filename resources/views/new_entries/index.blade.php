@extends('layouts.dashboard')

@section('content')
    <h1 class="text-lg font-bold text-gray-700 py-6">New Entries</h1>

    <div class="px-6 py-4 bg-gray-50 min-h-screen text-xs">
        <!-- ───── HEADER ───── -->
        <div class="flex flex-col gap-3 mb-4">
            <div class="space-x-2">
                <button id="toggleFiltersBtn"
                        class="bg-gray-300 text-gray-700 px-4 py-2 rounded shadow text-xs hover:bg-gray-400">
                    Hide Filters
                </button>

                <a href="{{ route('new_entries.create') }}"
                   class="bg-cyan-600 text-white px-4 py-2 rounded shadow hover:bg-cyan-700">
                    Create Entry
                </a>

                <a href="{{ route('new_entries.import.index') }}"
                   class="bg-cyan-600 text-white px-3 py-2 rounded shadow hover:bg-cyan-700">
                    Import CSV
                </a>

            </div>
        </div>

        <!-- ───── FILTERS (only 4) ───── -->
        <div id="filterForm"
             class="bg-white border border-gray-200 rounded shadow p-2 mb-8 inline-block">
            <div class="flex flex-wrap gap-2">
                <!-- Domain -->
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Domain</label>
                    <input id="filterDomainName" type="text"
                           class="border border-gray-300 rounded px-2 py-2 w-32"
                           placeholder="example.com">
                </div>

                <!-- Status -->
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Status</label>
                    <!-- Status filter -->
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

                <!-- Language (single) -->
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

                <!-- 1st Contact date range -->
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

            <!-- Buttons -->
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
        </div><!-- /filters -->

        <!-- ───── DATA TABLE ───── -->
        <div class="bg-white border border-gray-200 rounded shadow p-2 overflow-x-auto">
            <table id="newEntriesTable"
                   class="text-xs text-gray-700 w-full min-w-[1550px]">
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
                    <th class="px-4 py-2">Link Insertion Price</th>
                    <th class="px-4 py-2">Banner €</th>
                    <th class="px-4 py-2">Site-wide €</th>

                    <th class="px-4 py-2">Kialvo</th>
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
                    <th class="px-4 py-2">TF vs CF</th>
                    <th class="px-4 py-2">Semrush Traffic</th>
                    <th class="px-4 py-2">Ahrefs Keyword</th>
                    <th class="px-4 py-2">Ahrefs Traffic</th>
                    <th class="px-4 py-2">Keyword vs Traffic</th>
                    <th class="px-4 py-2">SEO Metrics Date</th>

                    <th class="px-4 py-2">Betting</th>
                    <th class="px-4 py-2">Trading</th>
                    <th class="px-4 py-2">Permanent Link</th>
                    <th class="px-4 py-2">More than 1 link</th>
                    <th class="px-4 py-2">Copywriting</th>
                    <th class="px-4 py-2">Sponsored Tag</th>
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
        </div><!-- /table -->
    </div>
@endsection
@include('new_entries.partials.note-modal')

@push('scripts')
    <script>
        $(function(){

            /* ─── helpers ─── */
            /* ─── helpers ─── */
            const statusMap = [
                {value:'never_opened',             label:'Never Opened'},
                {value:'read_but_never_answered',  label:'Read but never answered'},
                {value:'waiting_for_first_answer',   label:'Waiting for 1st answer'},
                {value:'refused_by_us',            label:'Refused by us'},
                {value:'publisher_refused',        label:'Publisher refused'},
                {value:'negotiation',              label:'Negotiation'},
                {value:'active',                   label:'Active'},
            ];

            function selectHTML(currentValue, rowId) {
                let html = `<select class="status-dd border border-gray-300 rounded
                          px-1 py-[2px] text-xs"
                       data-id="${rowId}">`;
                statusMap.forEach(({value,label}) => {
                    const sel = (value === String(currentValue)) ? 'selected' : '';
                    html += `<option value="${value}" ${sel}>${label}</option>`;
                });
                return html + '</select>';
            }

            function statusLabel(val){
                const found = statusMap.find(x => x.value === String(val));
                return found ? found.label : val;
            }


            function money(v){ return v==null?'':`<strong>€ ${v}</strong>`; }
            function yesNo(v){ return v ? 'YES':'NO'; }
            function dateFmt(v){ return v?new Date(v).toLocaleDateString('en-GB'):''; }

            /* ─── widgets ─── */
            flatpickr('#filterFirstFrom',{dateFormat:'Y-m-d',allowInput:true});
            flatpickr('#filterFirstTo'  ,{dateFormat:'Y-m-d',allowInput:true});

            /* ─── DataTable ─── */
            let tbl = $('#newEntriesTable').DataTable({
                processing:true, serverSide:true,
                ajax:{
                    url:"{{ route('new_entries.data') }}",
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
                            const safe=$('<div>').text(d).html();
                            return `<a href="#" class="note-link text-cyan-700" data-note="${safe}">
                            <i class="fas fa-comment-dots"></i></a>`;
                        }},
                    {data:'status', render:(d,t,r)=>t==='display'?selectHTML(d,r.id):d},
                    {data:'country_name'}, {data:'language_name'}, {data:'contact_name'},
                    {data:'currency_code'},

                    {data:'publisher_price',        render:money},
                    {data:'no_follow_price',        render:money},
                    {data:'special_topic_price',    render:money},
                    {data:'link_insertion_price',   render:money},
                    {data:'banner_price',           render:money},
                    {data:'sitewide_link_price',    render:money},

                    {data:'kialvo_evaluation',      render:money},
                    {data:'profit',                 render:money},

                    {data:'date_publisher_price',   render:dateFmt},
                    {data:'linkbuilder'},
                    {data:'type_of_website'},
                    {data:'categories_list'},

                    {data:'DA'}, {data:'PA'}, {data:'TF'}, {data:'CF'},
                    {data:'DR'}, {data:'UR'}, {data:'ZA'}, {data:'as_metric'},

                    {data:'seozoom'}, {data:'TF_vs_CF'},
                    {data:'semrush_traffic'}, {data:'ahrefs_keyword'},
                    {data:'ahrefs_traffic'}, {data:'keyword_vs_traffic'},
                    {data:'seo_metrics_date',       render:dateFmt},

                    {data:'betting',                render:yesNo},
                    {data:'trading',                render:yesNo},
                    {data:'permanent_link',         render:yesNo},
                    {data:'more_than_one_link',     render:yesNo},
                    {data:'copywriting',            render:d=>d?'PROVIDED':'NOT PROVIDED'},
                    {data:'no_sponsored_tag',       render:yesNo},
                    {data:'social_media_sharing',   render:yesNo},
                    {data:'post_in_homepage',       render:yesNo},

                    {data:'first_contact_date',     render:dateFmt},
                    {
                        data: 'copied_to_overview',
                        render: function (d) {
                            // treat 0 or "0" as NO, anything else as YES
                            return (d == 0 || d === '0') ? 'NO' : 'YES';
                        }
                    },


                    {data:'date_added',             render:dateFmt},
                    {data:'action', orderable:false, searchable:false}
                ],
                order:[[0,'desc']],
                autoWidth:false
            });

            @if(session('status'))
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text : @json(session('status')),
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false
                // no “toast”, no “position”  → default is centred modal
            });
            @endif

            /* ─── buttons ─── */
            $('#btnSearch').click(()=>tbl.ajax.reload());
            $('#btnClear').click(function(){
                $('#filterForm input').val('');
                $('#filterStatus').val('');
                $('#filterLanguage').val('');            // <— add
                $('#filterCountries').val('');
                tbl.ajax.reload();
            });

            /* ─── inline-status AJAX ─── */
            $(document).on('change', '.status-dd', function () {
                const $sel   = $(this);
                const newVal = $sel.val();                 // machine value (e.g. negotiation)
                $.ajax({
                    url: `{{ url('/new-entries') }}/${$sel.data('id')}/status`,
                    type: 'PUT',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: { status: newVal },

                    success: () => {
                        // ✨ nice toast
                        Swal.fire({
                            toast: true, position: 'top-end', timer: 2500, showConfirmButton: false,
                            icon: 'success',
                            title: `Status changed to “${statusLabel(newVal)}”`
                        });
                        tbl.ajax.reload(null, false);      // keep pagination / filters
                    },

                    error: () => {
                        Swal.fire({
                            toast: true, position: 'top-end', timer: 3000, showConfirmButton: false,
                            icon: 'error',
                            title: 'Status update failed'
                        });
                        tbl.ajax.reload(null, false);
                    }
                });
            });


            /* ───────────────────────────────────────────
           NOTE MODAL – OPEN
           (this is already in your file)
        ─────────────────────────────────────────── */
            $(document).on('click', '.note-link', function (e) {
                e.preventDefault();
                $('#modalNoteBody').text($(this).data('note'));
                $('#noteModal').removeClass('hidden');
            });

            /* ───────────────────────────────────────────
               NOTE MODAL – CLOSE               ←----- add
            ─────────────────────────────────────────── */
            $(document).on('click', '#closeNoteModal, #closeNoteModalBottom', function () {
                $('#noteModal').addClass('hidden');
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            const btn     = document.getElementById('toggleFiltersBtn');
            const panel   = document.getElementById('filterForm');
            let  visible  = true;                       // filters start visible

            btn.addEventListener('click', () => {
                panel.classList.toggle('hidden');
                visible = !visible;
                btn.textContent = visible ? 'Hide Filters' : 'Show Filters';
            });
        });
    </script>
@endpush
