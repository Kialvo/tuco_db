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

@section('content')
    <h1 class="text-lg font-bold text-gray-700 py-6">New Entries</h1>

    <div class="px-6 py-4 bg-gray-50 min-h-screen text-xs">
        {{-- HEADER --}}
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

        {{-- FILTERS (keep your simpler set) --}}
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

                {{-- Language --}}
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

                {{-- 1st Contact date range --}}
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

            {{-- Filter buttons --}}
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

        {{-- ACTION BAR (identical pattern to Websites) --}}
        <div id="actionBar"
             class="flex items-center gap-3 mb-2 sticky top-0 z-10 bg-gray-50 border-b border-gray-200 py-2">

            {{-- Bulk Edit --}}
            <button id="btnBulkEdit"
                    class="flex items-center gap-1 px-3 py-1.5 rounded text-xs
                           bg-amber-600 hover:bg-amber-700 text-white shadow disabled:opacity-50
                           disabled:cursor-not-allowed">
                <i class="fas fa-pen"></i> Bulk&nbsp;Edit
            </button>

            {{-- Restore / Rollback --}}
            <button id="btnBulkRestore"
                    class="flex items-center gap-1 px-3 py-1.5 rounded text-xs
                           bg-purple-600 hover:bg-purple-700 text-white shadow disabled:opacity-50
                           disabled:cursor-not-allowed">
                <i class="fas fa-history"></i> Restore
            </button>

            {{-- live counter --}}
            <span class="ml-2 text-sm text-gray-600">
                Selected:&nbsp;<span id="selCount">0</span>
            </span>
        </div>

        {{-- TABLE --}}
        <div class="bg-white border border-gray-200 rounded shadow p-2 overflow-x-auto">
            <table id="newEntriesTable" class="text-xs text-gray-700 w-full min-w-[1550px]">
                <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-[12px] uppercase text-gray-500 tracking-wider">
                    <th class="px-4 py-2">
                        <input type="checkbox" id="chkAll" class="form-checkbox h-4 w-4 text-cyan-600">
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
                    <th class="px-4 py-2">Link Insertion Price</th>
                    <th class="px-4 py-2">Banner €</th>
                    <th class="px-4 py-2">Site-wide €</th>

                    <th class="px-4 py-2">Kialvo</th>
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
@endsection

@push('scripts')
    <script>
        $(function () {
            /* ========== helpers (same as Websites) ========== */
            const statusMap = [
                {value:'never_opened',            label:'Never Opened'},
                {value:'read_but_never_answered', label:'Read but never answered'},
                {value:'waiting_for_first_answer',label:'Waiting for 1st answer'},
                {value:'refused_by_us',           label:'Refused by us'},
                {value:'publisher_refused',       label:'Publisher refused'},
                {value:'negotiation',             label:'Negotiation'},
                {value:'active',                  label:'Active'},
            ];
            function selectHTML(cur, id){
                let html = `<select class="status-dd border border-gray-300 rounded px-1 py-[2px] text-xs" data-id="${id}">`;
                statusMap.forEach(({value,label})=>{
                    const sel = (String(cur)===value)?'selected':'';
                    html += `<option value="${value}" ${sel}>${label}</option>`;
                });
                return html + '</select>';
            }
            const money = v => (v==null? '' : `<strong>€ ${v}</strong>`);
            const yesNo = v => (v ? 'YES' : 'NO');
            const dateFmt = v => (v ? new Date(v).toLocaleDateString('en-GB') : '');

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
                        render: id => `<input type="checkbox" class="rowChk form-checkbox h-4 w-4 text-cyan-600" value="${id}">`
                    },
                    { data:'id' },
                    { data:'domain_name' },
                    {
                        data:'extra_notes',
                        render:d=>{
                            if(!d) return '';
                            const safe=$('<div>').text(d).html();
                            return `<a href="#" class="note-link text-cyan-700" data-note="${safe}">
                              <i class="fas fa-comment-dots"></i></a>`;
                        }
                    },

                    { data:'status', render:(d,t,r)=> t==='display' ? selectHTML(d,r.id) : d },
                    { data:'country_name' },
                    { data:'language_name' },
                    {
                        data: 'contact_name',
                        render: function(data, type, row) {
                            if (!row.contact_id) return "No Contact";
                            return `
                        <a href="#" class="contact-link text-blue-600 underline"
                           data-contact-id="${row.contact_id}">
                           ${data ?? 'Contact'}
                        </a>`;
                        }
                    },
                    { data:'currency_code' },

                    { data:'publisher_price',      render:money, className:'text-center' },
                    { data:'no_follow_price',      render:money, className:'text-center' },
                    { data:'special_topic_price',  render:money, className:'text-center' },
                    { data:'link_insertion_price', render:money, className:'text-center' },
                    { data:'banner_price',         render:money, className:'text-center' },
                    { data:'sitewide_link_price',  render:money, className:'text-center' },

                    { data:'kialvo_evaluation',    render:money, className:'text-center' },
                    { data:'profit',               render:money, className:'text-center' },

                    { data:'date_publisher_price', render:dateFmt, className:'text-center' },
                    { data:'linkbuilder',          className:'text-center' },
                    { data:'type_of_website',      className:'text-center' },
                    { data:'categories_list',      className:'text-center' },

                    { data:'DA', className:'text-center' }, { data:'PA', className:'text-center' },
                    { data:'TF', className:'text-center' }, { data:'CF', className:'text-center' },
                    { data:'DR', className:'text-center' }, { data:'UR', className:'text-center' },
                    { data:'ZA', className:'text-center' }, { data:'as_metric', className:'text-center' },

                    { data:'seozoom', className:'text-center' }, { data:'TF_vs_CF', className:'text-center' },
                    { data:'semrush_traffic', className:'text-center' }, { data:'ahrefs_keyword', className:'text-center' },
                    { data:'ahrefs_traffic', className:'text-center' }, { data:'keyword_vs_traffic', className:'text-center' },
                    { data:'seo_metrics_date', render:dateFmt, className:'text-center' },

                    { data:'betting',            render:yesNo, className:'text-center' },
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
                // IMPORTANT: order by the ID column (index 1) — first col is the checkbox
                order: [[1, 'desc']],
                responsive:false,
                autoWidth:false
            });

            // status inline change (same pattern as you had)
            $(document).on('change', '.status-dd', function () {
                const $sel = $(this), newVal = $sel.val();
                $.ajax({
                    url: `{{ url('/new-entries') }}/${$sel.data('id')}/status`,
                    type: 'PUT',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: { status: newVal },
                    success: () => {
                        Swal.fire({ toast:true, position:'top-end', timer:2500, showConfirmButton:false,
                            icon:'success', title:`Status changed to “${(statusMap.find(s=>s.value===newVal)||{}).label || newVal}”`});
                        tbl.ajax.reload(null, false);
                    },
                    error: () => {
                        Swal.fire({ toast:true, position:'top-end', timer:3000, showConfirmButton:false,
                            icon:'error', title:'Status update failed' });
                        tbl.ajax.reload(null, false);
                    }
                });
            });

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
            $('#btnSearch').on('click', ()=> tbl.ajax.reload());
            $('#btnClear').on('click', ()=>{
                $('#filterForm input').val('');
                $('#filterStatus').val('');
                $('#filterLanguage').val('');
                $('#filterCountries').val('');
                tbl.ajax.reload();
            });

            // NOTE modal
            $(document).on('click', '.note-link', function (e) {
                e.preventDefault();
                $('#modalNoteBody').text($(this).data('note'));
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
                    wrap.append('<p class="text-gray-500 text-xs">Nothing to fill in – just click “Save”.</p>');
                    return;
                }
                if(meta.type==='date'){
                    wrap.append(`<input id="bulkValue" type="date" class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-cyan-500">`);
                    return;
                }
                if(meta.type==='select'){
                    const none=`<option value="">-- Clear --</option>`;
                    const opts = Object.entries(meta.options || {}).map(([v,l])=>`<option value="${v}">${l}</option>`).join('');
                    wrap.append(`<select id="bulkValue" class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-cyan-500">${none}${opts}</select>`);
                    if($('#bulkValue option').length>15){ $('#bulkValue').select2({width:'resolve', dropdownAutoWidth:true}); }
                    return;
                }
                if(meta.type==='multiselect'){
                    const opts = Object.entries(meta.options || {}).map(([v,l])=>`<option value="${v}">${l}</option>`).join('');
                    wrap.append(`<select id="bulkValue" multiple class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-cyan-500">${opts}</select>`);
                    $('#bulkValue').select2({width:'resolve', dropdownAutoWidth:true});
                    return;
                }
                if(meta.type==='textarea'){
                    wrap.append(`<textarea id="bulkValue" rows="3" class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-cyan-500"></textarea>`);
                    return;
                }
                wrap.append(`<input id="bulkValue" type="text" class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-cyan-500">`);
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
        });
    </script>
@endpush
