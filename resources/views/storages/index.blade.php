{{-- resources/views/storages/index.blade.php --}}
@extends('layouts.dashboard')

@section('content')
    <h1 class="text-lg font-bold text-gray-700 py-6">Storages</h1>

    <div class="px-6 py-4 bg-gray-50 min-h-screen text-xs">
        {{-- ───────────── HEADER BUTTONS ───────────── --}}
        <div class="flex flex-col gap-3 mb-4">
            <div class="space-x-2">
                <button id="toggleFiltersBtn"
                        class="bg-gray-300 text-gray-700 px-4 py-2 rounded shadow text-xs hover:bg-gray-400
                               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-300">
                    Hide Filters
                </button>

                <a href="{{ route('storages.create') }}"
                   class="bg-cyan-600 text-white px-4 py-2 rounded shadow hover:bg-cyan-700
                          focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 text-xs">
                    Create Storage
                </a>

                <a href="#" id="btnExportCsv"
                   class="bg-green-600 text-white px-4 py-2 rounded shadow hover:bg-green-700
                          focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 text-xs">
                    Export CSV
                </a>

                <a href="#" id="btnExportPdf"
                   class="bg-red-600 text-white px-4 py-2 rounded shadow hover:bg-red-700
                          focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 text-xs">
                    Export PDF
                </a>
            </div>
        </div>

        {{-- ───────────── FILTERS ───────────── --}}
        <div id="filterForm"
             class="bg-white border border-gray-200 rounded shadow p-2 mb-8 inline-block max-w-[2200px]">

            {{-- ROW 1 --}}
            <div class="flex flex-wrap gap-2 mb-2">
                {{-- Publication From / To --}}
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Publication From</label>
                    <input type="date" id="filterPublicationFrom"
                           class="border border-gray-300 rounded px-2 py-2 w-40 focus:ring-cyan-500 focus:border-cyan-500">
                </div>
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Publication To</label>
                    <input type="date" id="filterPublicationTo"
                           class="border border-gray-300 rounded px-2 py-2 w-40 focus:ring-cyan-500 focus:border-cyan-500">
                </div>

                {{-- Copywriter --}}
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Copywriter</label>
                    <select id="filterCopy"
                            class="border border-gray-300 rounded px-2 py-2 w-48 focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="">-- Any --</option>
                        @foreach($copies as $cp)
                            <option value="{{ $cp->id }}">{{ $cp->copy_val }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Language --}}
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Language</label>
                    <select id="filterLanguage"
                            class="border border-gray-300 rounded px-2 py-2 w-32 focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="">-- Any --</option>
                        @foreach($languages as $lang)
                            <option value="{{ $lang->id }}">{{ $lang->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Country --}}
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Country</label>
                    <select id="filterCountry"
                            class="border border-gray-300 rounded px-2 py-2 w-32 focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="">-- Any --</option>
                        @foreach($countries as $c)
                            <option value="{{ $c->id }}">{{ $c->country_name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Client --}}
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Client</label>
                    <select id="filterClient"
                            class="border border-gray-300 rounded px-2 py-2 w-44 focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="">-- Any --</option>
                        @foreach($clients as $cl)
                            <option value="{{ $cl->id }}">{{ $cl->first_name }} {{ $cl->last_name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Campaign --}}
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Campaign</label>
                    <input type="text" id="filterCampaign"
                           class="border border-gray-300 rounded px-2 py-2 w-40 focus:ring-cyan-500 focus:border-cyan-500"
                           placeholder="Campaign name">
                </div>

                {{-- Status --}}
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Status</label>
                    <select id="filterStatus"
                            class="border border-gray-300 rounded px-2 py-2 w-48 focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="">-- Any --</option>
                        <option value="article_published">Article Published</option>
                        <option value="requirements_not_met">Requirements not met</option>
                        <option value="already_used_by_client">Already used by client</option>
                        <option value="out_of_topic">Out of topic</option>
                        <option value="high_price">High Price</option>
                    </select>
                </div>
            </div>

            {{-- ROW 2 – Categories --}}
            <div class="mb-2 flex items-center">
                <label class="text-gray-700 font-medium mr-2">Categories</label>
                <select id="filterCategories" multiple
                        class="border border-gray-300 rounded px-2 py-2 text-xs w-48 max-h-20 overflow-y-auto
                               focus:ring-cyan-500 focus:border-cyan-500">
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- ROW 3 – Buttons --}}
            <div class="flex space-x-2">
                <button id="btnSearch"
                        class="bg-cyan-600 text-white px-4 py-2 rounded shadow text-xs hover:bg-cyan-700
                               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500">
                    Search
                </button>
                <button id="btnClear"
                        class="bg-gray-400 text-white px-4 py-2 rounded shadow text-xs hover:bg-gray-500
                               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-300">
                    Clear
                </button>
            </div>
        </div><!-- /filterForm -->

        {{-- SHOW‑DELETED toggle --}}
        <div class="flex items-center space-x-2 mb-4">
            <label for="filterShowDeleted" class="text-lg font-medium text-gray-700">Show Deleted</label>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" id="filterShowDeleted" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 rounded-full peer-checked:bg-cyan-600
                            after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                            after:bg-white after:border-gray-300 after:border
                            after:rounded-full after:h-5 after:w-5 after:transition-all
                            peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
            </label>
        </div>

        {{-- ───────────── DATA TABLE ───────────── --}}
        <div class="bg-white border border-gray-200 rounded shadow p-2 overflow-x-auto max-w-full">
            <table id="storagesTable" class="text-xs text-gray-700 w-full min-w-[2400px]">
                <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-[11px] uppercase text-gray-500 tracking-wider">
                    <th class="px-4 py-2">ID</th>
                    <th class="px-4 py-2">Website</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">LB</th>
                    <th class="px-4 py-2">Client</th>
                    <th class="px-4 py-2">Copywriter</th>
                    <th class="px-4 py-2">Copywriter Amount EUR €</th>
                    <th class="px-4 py-2">Copy Comm.&nbsp;Date</th>
                    <th class="px-4 py-2">Copy Subm.&nbsp;Date</th>
                    <th class="px-4 py-2">Copy Period</th>
                    <th class="px-4 py-2">Language</th>
                    <th class="px-4 py-2">Country</th>
                    <th class="px-4 py-2">Publisher Agreed Amount</th>
                    <th class="px-4 py-2">Total Costc€</th>
                    <th class="px-4 py-2">Menford €</th>
                    <th class="px-4 py-2">Client Copy €</th>
                    <th class="px-4 py-2">Total Revenues €</th>
                    <th class="px-4 py-2">Profit €</th>
                    <th class="px-4 py-2">Target Domain</th>
                    <th class="px-4 py-2">Anchor Text</th>
                    <th class="px-4 py-2">Target URL</th>
                    <th class="px-4 py-2">Campaign Code</th>
                    <th class="px-4 py-2">Sent to Publisher</th>
                    <th class="px-4 py-2">Publication Date</th>
                    <th class="px-4 py-2">Expiration Date</th>
                    <th class="px-4 py-2">Publisher Period</th>
                    <th class="px-4 py-2">Article URL</th>
                    <th class="px-4 py-2">Pay to Us Method</th>
                    <th class="px-4 py-2">Invoice Menford Date</th>
                    <th class="px-4 py-2">Invoice Menford Nr</th>
                    <th class="px-4 py-2">Invoice Company</th>
                    <th class="px-4 py-2">Pay to Us Date</th>
                    <th class="px-4 py-2">Bill Publisher Name</th>
                    <th class="px-4 py-2">Bill Publisher Nr</th>
                    <th class="px-4 py-2">Pay to Publisher Date</th>
                    <th class="px-4 py-2">Pay to Publisher Method</th>
                    <th class="px-4 py-2 whitespace-nowrap">Categories</th>
                    <th class="px-4 py-2">Files</th>
                    <th class="px-4 py-2">Action</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
@endsection

@include('storages.partials.client-modal')
@include('storages.partials.copy-modal')
@include('storages.partials.url-modal')   {{-- NEW --}}

@push('scripts')
    <script>
        $(function () {
            /* ---------- Select2 ---------- */
            $('#filterLanguage, #filterCountry, #filterClient, #filterCopy, #filterCategories')
                .select2({
                    width:'resolve',
                    dropdownAutoWidth:true,
                    placeholder:'Select',
                    allowClear:true,
                    containerCssClass:'text-xs',
                    dropdownCssClass:'text-xs'
                });

            /* ---------- DataTable ---------- */
            const table = $('#storagesTable').DataTable({
                processing:true,
                serverSide:true,
                ajax:{
                    url:"{{ route('storages.data') }}",
                    type:"POST",
                    headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')},
                    data:d=>{
                        d.publication_from   = $('#filterPublicationFrom').val();
                        d.publication_to     = $('#filterPublicationTo').val();
                        d.copy_id            = $('#filterCopy').val();
                        d.language_id        = $('#filterLanguage').val();
                        d.country_id         = $('#filterCountry').val();
                        d.client_id          = $('#filterClient').val();
                        d.campaign           = $('#filterCampaign').val();
                        d.status             = $('#filterStatus').val();
                        d.category_ids       = $('#filterCategories').val();
                        d.show_deleted       = $('#filterShowDeleted').is(':checked');
                    }
                },
                columns:[
                    {data:'id',name:'id'},
                    {data:'website_domain',name:'domain_name'},
                    {data:'status',name:'status'},
                    {data:'LB',name:'LB'},
                    {
                        data:'client_name',
                        name:'client.first_name',
                        render:function(data,type,row){
                            if(!row.client_id) return '';
                            return `
                                <a href="#" class="client-link underline text-blue-600"
                                   data-client-id="${row.client_id}">
                                   ${data}
                                </a>`;
                        }
                    },
                    {
                        data:'copywriter_name',
                        name:'copy.copy_val',
                        render:function(data,type,row){
                            if(!row.copy_id) return '';
                            return `
                                <a href="#" class="copy-link underline text-blue-600"
                                   data-copy-id="${row.copy_id}">
                                   ${data}
                                </a>`;
                        }
                    },
                    {data:'copy_nr',name:'copy_nr'},
                    {data:'copywriter_commision_date',name:'copywriter_commision_date',render:fmtDateEU},
                    {data:'copywriter_submission_date',name:'copywriter_submission_date',render:fmtDateEU},
                    {data:'copywriter_period',name:'copywriter_period'},
                    {data:'language_name',name:'language.name'},
                    {data:'country_name',name:'country.country_name'},
                    {data:'publisher',name:'publisher',render:fmtEuro},
                    {data:'total_cost',name:'total_cost',render:fmtEuro},
                    {data:'menford',name:'menford',render:fmtEuro},
                    {data:'client_copy',name:'client_copy',render:fmtEuro},
                    {data:'total_revenues',name:'total_revenues',render:fmtEuro},
                    {data:'profit',name:'profit',render:fmtEuro},
                    {data:'campaign',name:'campaign'},
                    {data:'anchor_text',name:'anchor_text'},
                    {
                        data:'target_url',
                        name:'target_url',
                        orderable:false,
                        searchable:false,
                        render:d=>d ? `<a href="#" class="url-link underline text-blue-600" data-url="${d}">link</a>` : ''
                    },
                    {data:'campaign_code',name:'campaign_code'},
                    {data:'article_sent_to_publisher',name:'article_sent_to_publisher',render:fmtDateEU},
                    {data:'publication_date',name:'publication_date',render:fmtDateEU},
                    {data:'expiration_date',name:'expiration_date',render:fmtDateEU},
                    {data:'publisher_period',name:'publisher_period'},
                    {
                        data:'article_url',
                        name:'article_url',
                        orderable:false,
                        searchable:false,
                        render:d=>d ? `<a href="#" class="url-link underline text-blue-600" data-url="${d}">article</a>` : ''
                    },
                    {data:'method_payment_to_us',name:'method_payment_to_us'},
                    {data:'invoice_menford',name:'invoice_menford',render:fmtDateEU},
                    {data:'invoice_menford_nr',name:'invoice_menford_nr'},
                    {data:'invoice_company',name:'invoice_company'},
                    {data:'payment_to_us_date',name:'payment_to_us_date',render:fmtDateEU},
                    {data:'bill_publisher_name',name:'bill_publisher_name'},
                    {data:'bill_publisher_nr',name:'bill_publisher_nr'},
                    {data:'payment_to_publisher_date',name:'payment_to_publisher_date',render:fmtDateEU},
                    {data:'method_payment_to_publisher',name:'method_payment_to_publisher'},
                    {data:'categories_list',name:'categories_list',className:'text-center'},
                    {
                        data: 'files',
                        name: 'files',
                        orderable: false,
                        searchable: false,
                        render: d => d
                            ? `<a href="${d}" target="_blank">
               <i class="fas fa-paperclip text-lg text-blue-600"></i>
           </a>`
                            : ''
                    },

                    {data:'action',name:'action',orderable:false,searchable:false}
                ],
                order:[[0,'desc']],
                autoWidth:false,
                scrollX:true
            });

            /* ---------- Helper renderers ---------- */
            function fmtDateEU(iso){
                if(!iso) return '';
                return new Date(iso).toLocaleDateString('en-GB');
            }
            function fmtEuro(d){return d!==null?'<strong>€ '+d+'</strong>':'';}

            /* ---------- Buttons & Toggles ---------- */
            $('#btnSearch').on('click',e=>{e.preventDefault();table.ajax.reload();});

            $('#btnClear').on('click',e=>{
                e.preventDefault();
                $('#filterForm').find('input[type="text"],input[type="date"]').val('');
                $('#filterForm select').val('').trigger('change');
                $('#filterShowDeleted').prop('checked',false);
                table.ajax.reload();
            });

            $('#filterShowDeleted').on('change',()=>table.ajax.reload());

            /* ---------- Export helpers ---------- */
            const buildParams=()=>$.param({
                publication_from:$('#filterPublicationFrom').val(),
                publication_to:$('#filterPublicationTo').val(),
                copy_id:$('#filterCopy').val(),
                language_id:$('#filterLanguage').val(),
                country_id:$('#filterCountry').val(),
                client_id:$('#filterClient').val(),
                campaign:$('#filterCampaign').val(),
                status:$('#filterStatus').val(),
                category_ids:$('#filterCategories').val(),
                show_deleted:$('#filterShowDeleted').is(':checked')?1:0
            });

            $('#btnExportCsv').on('click',e=>{e.preventDefault();window.location="{{ route('storages.export.csv') }}?"+buildParams();});
            $('#btnExportPdf').on('click',e=>{e.preventDefault();window.location="{{ route('storages.export.pdf') }}?"+buildParams();});

            /* ---------- Hide / Show filters ---------- */
            let filtersVisible=true;
            $('#toggleFiltersBtn').on('click',function(){
                $('#filterForm').toggleClass('hidden');
                filtersVisible=!filtersVisible;
                this.textContent=filtersVisible?'Hide Filters':'Show Filters';
            });

            /* ---------- Client & Copy modals ---------- */
            $(document).on('click','.client-link',function(e){
                e.preventDefault();
                const id=$(this).data('client-id');
                $.get("{{ route('clients.showAjax', '') }}/" + id, function (res) {
                    if(res.status==='success'){
                        const c=res.data;
                        $('#modalClientName').text((c.first_name??'')+' '+(c.last_name??''));
                        $('#modalClientEmail').text(c.email??'');
                        $('#modalClientCompany').text(c.company??'');
                        $('#clientModal').removeClass('hidden').addClass('flex');
                    }else{alert('Could not load client.');}
                }).fail(()=>alert('Error fetching client.'));
            });
            $(document).on('click', '#closeClientModal, #closeClientModalBottom', function () {
                $('#clientModal').addClass('hidden').removeClass('flex');
            });

            $(document).on('click','.copy-link',function(e){
                e.preventDefault();
                const id=$(this).data('copy-id');
                $.get("{{ route('copy.showAjax', '') }}/" + id, function (res) {
                    if(res.status==='success'){
                        $('#modalCopyVal').text(res.data.copy_val);
                        $('#copyModal').removeClass('hidden').addClass('flex');
                    }else{alert('Could not load copy.');}
                }).fail(()=>alert('Error fetching copy.'));
            });
            $(document).on('click', '#closeCopyModal, #closeCopyModalBottom', function () {
                $('#copyModal').addClass('hidden').removeClass('flex');
            });

            /* ---------- URL modal ---------- */
            $(document).on('click','.url-link',function(e){
                e.preventDefault();
                const url=$(this).data('url');
                $('#urlModalInput').val(url);
                $('#urlModalOpen').attr('href',url);
                $('#urlModal').removeClass('hidden').addClass('flex');
            });
            /* ---------- URL‑modal copy button ---------- */
            $('#urlModalCopy').on('click', function () {
                const text = $('#urlModalInput').val();

                // tiny helpers
                const hasSwal   = typeof Swal !== 'undefined';
                const successUI = () => hasSwal
                    ? Swal.fire({ icon:'success', title:'Copied to clipboard!', timer:1500, showConfirmButton:false })
                    : alert('Copied to clipboard!');
                const errorUI   = () => hasSwal
                    ? Swal.fire({ icon:'error',   title:'Copy failed', text:'Your browser blocked the operation.' })
                    : alert('Copy failed');

                // modern API when available and secure
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(successUI).catch(errorUI);
                    return;
                }

                // fallback for HTTP / old browsers
                const tmp = document.createElement('textarea');
                tmp.style.position = 'fixed';
                tmp.style.opacity  = '0';
                tmp.value = text;
                document.body.appendChild(tmp);
                tmp.select();

                try {
                    document.execCommand('copy');
                    successUI();
                } catch (e) {
                    errorUI();
                }
                document.body.removeChild(tmp);
            });



            $('#urlModalClose').on('click',()=>$('#urlModal').addClass('hidden').removeClass('flex'));

            /* ---------- Flash message ---------- */
            @if(session('status'))
            Swal.fire({icon:'success',title:'Success',text:'{{ session('status') }}',timer:3000,showConfirmButton:false});
            @endif
        });
    </script>
@endpush
