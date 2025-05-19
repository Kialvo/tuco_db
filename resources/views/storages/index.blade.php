{{-- resources/views/storages/index.blade.php --}}
@extends('layouts.dashboard')

@section('content')
    <h1 class="text-lg font-bold text-gray-700 py-6">Storages</h1>

    {{-- Define exportable columns --}}
    @php
        $exportColumns = [
            'id'                             => 'ID',
            'website_domain'                => 'Website',
            'status'                         => 'Status',
            'LB'                             => 'LB',
            'client_name'                   => 'Client',
            'copywriter_name'               => 'Copywriter',
            'copy_nr'                        => 'Copywriter Amount €',
            'copywriter_commision_date'     => 'Copy Comm. Date',
            'copywriter_submission_date'    => 'Copy Subm. Date',
            'copywriter_period'             => 'Copy Period',
            'language_name'                 => 'Language',
            'country_name'                  => 'Country',
            'publisher_currency'            => 'Publisher Currency',
            'publisher_amount'              => 'Publisher Amount €',
            'publisher'                     => 'Publisher Agreed €',
            'total_cost'                    => 'Total Cost €',
            'menford'                       => 'Menford €',
            'client_copy'                   => 'Client Copy €',
            'total_revenues'                => 'Total Revenues €',
            'profit'                        => 'Profit €',
            'campaign'                      => 'Target Domain',
            'anchor_text'                   => 'Anchor Text',
            'target_url'                    => 'Target URL',
            'campaign_code'                 => 'Campaign Code',
            'article_sent_to_publisher'     => 'Sent to Publisher',
            'publication_date'              => 'Publication Date',
            'expiration_date'               => 'Expiration Date',
            'publisher_period'              => 'Publisher Period',
            'article_url'                   => 'Article URL',
            'method_payment_to_us'          => 'Pay to Us Method',
            'invoice_menford'               => 'Invoice Menford Date',
            'invoice_menford_nr'            => 'Invoice Menford Nr',
            'invoice_company'               => 'Invoice Company',
            'payment_to_us_date'            => 'Pay to Us Date',
            'bill_publisher_name'           => 'Bill Publisher Name',
            'bill_publisher_nr'             => 'Bill Publisher Nr',
            'bill_publisher_date'           => 'Bill Publisher Date',
            'payment_to_publisher_date'     => 'Pay to Publisher Date',
            'method_payment_to_publisher'   => 'Pay to Publisher Method',
            'categories_list'               => 'Categories',
            'files'                         => 'Files',
        ];
    @endphp

    <div class="px-6 py-4 bg-gray-50 min-h-screen text-xs">

        {{-- ───────────── HEADER BUTTONS ───────────── --}}
        <div class="flex flex-col gap-3 mb-4">
            <div class="space-x-2 flex flex-wrap items-center">
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

                {{-- Export Buttons --}}
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

            {{-- ───────────── SELECT FIELDS TO EXPORT ───────────── --}}
            <div class="mt-2 flex items-center gap-2">
                <label class="text-gray-700 font-medium text-xs">Choose Columns:</label>
                <select id="exportFields" multiple
                        class="border border-gray-300 rounded px-2 py-1 text-xs w-64
                               focus:ring-cyan-500 focus:border-cyan-500">
                    @foreach($exportColumns as $fieldKey => $fieldLabel)
                        <option value="{{ $fieldKey }}">{{ $fieldLabel }}</option>
                    @endforeach
                </select>
                <span class="text-gray-500 text-xs">(<em>leave blank for all</em>)</span>
            </div>
        </div>

        {{-- ───────────── FILTERS ───────────── --}}
        <div id="filterForm"
             class="bg-white border border-gray-200 rounded shadow p-2 mb-8 inline-block max-w-[2000px]">
            {{-- ROW 1 --}}
            <div class="flex flex-wrap gap-2 mb-2">
                {{-- Publication From --}}
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Publication From</label>
                    <input type="date" id="filterPublicationFrom"
                           class="border border-gray-300 rounded px-2 py-2 w-40
                                  focus:ring-cyan-500 focus:border-cyan-500">
                </div>
                {{-- Publication To --}}
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Publication To</label>
                    <input type="date" id="filterPublicationTo"
                           class="border border-gray-300 rounded px-2 py-2 w-40
                                  focus:ring-cyan-500 focus:border-cyan-500">
                </div>
                {{-- Language --}}
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Language</label>
                    <select id="filterLanguage"
                            class="border border-gray-300 rounded px-2 py-2 w-28
                                   focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="">-- Any --</option>
                        @foreach($languages as $l)
                            <option value="{{ $l->id }}">{{ $l->name }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- Country --}}
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Country</label>
                    <select id="filterCountry"
                            class="border border-gray-300 rounded px-2 py-2 w-28
                                   focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="">-- Any --</option>
                        @foreach($countries as $c)
                            <option value="{{ $c->id }}">{{ $c->country_name }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- Copywriter --}}
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Copywriter</label>
                    <select id="filterCopy"
                            class="border border-gray-300 rounded px-2 py-2 w-40
                                   focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="">-- Any --</option>
                        @foreach($copies as $cp)
                            <option value="{{ $cp->id }}">{{ $cp->copy_val }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- Client --}}
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Client</label>
                    <select id="filterClient"
                            class="border border-gray-300 rounded px-2 py-2 w-44
                                   focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="">-- Any --</option>
                        @foreach($clients as $cl)
                            <option value="{{ $cl->id }}">{{ $cl->first_name }} {{ $cl->last_name }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- Status --}}
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Status</label>
                    <select id="filterStatus"
                            class="border border-gray-300 rounded px-2 py-2 w-40
                                   focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="">-- Any --</option>
                        <option value="article_published">Article Published</option>
                        <option value="requirements_not_met">Requirements not met</option>
                        <option value="already_used_by_client">Already used by client</option>
                        <option value="out_of_topic">Out of topic</option>
                        <option value="high_price">High Price</option>
                    </select>
                </div>
            </div>

            {{-- ROW 2 --}}
            <div class="flex flex-wrap gap-2 mb-2">
                {{-- Target Domain --}}
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Target Domain</label>
                    <input type="text" id="filterCampaign"
                           class="border border-gray-300 rounded px-2 py-2 w-40
                                  focus:ring-cyan-500 focus:border-cyan-500"
                           placeholder="domain.com">
                </div>
                {{-- Campaign Code --}}
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Campaign Code</label>
                    <input type="text" id="filterCampaignCode"
                           class="border border-gray-300 rounded px-2 py-2 w-28
                                  focus:ring-cyan-500 focus:border-cyan-500"
                           placeholder="code">
                </div>
                {{-- Invoice Menford NR --}}
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Invoice Menford NR</label>
                    <input type="text" id="filterInvoiceMenfordNr"
                           class="border border-gray-300 rounded px-2 py-2 w-28
                                  focus:ring-cyan-500 focus:border-cyan-500"
                           placeholder="number">
                </div>
                {{-- Bill Publisher Name --}}
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Bill Publisher Name</label>
                    <input type="text" id="filterBillPublisherName"
                           class="border border-gray-300 rounded px-2 py-2 w-40
                                  focus:ring-cyan-500 focus:border-cyan-500"
                           placeholder="publisher">
                </div>
                {{-- Link URL --}}
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Link URL</label>
                    <input type="text" id="filterTargetUrl"
                           class="border border-gray-300 rounded px-2 py-2 w-48
                                  focus:ring-cyan-500 focus:border-cyan-500"
                           placeholder="full url">
                </div>
                {{-- Article URL --}}
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Article URL</label>
                    <input type="text" id="filterArticleUrl"
                           class="border border-gray-300 rounded px-2 py-2 w-48
                                  focus:ring-cyan-500 focus:border-cyan-500"
                           placeholder="full url">
                </div>
            </div>

            {{-- ROW 3 – Categories --}}
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

            {{-- ROW 4 – Buttons --}}
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

        {{-- SHOW-DELETED toggle --}}
        <div class="flex items-center space-x-2 mb-4">
            <label for="filterShowDeleted" class="text-lg font-medium text-gray-700">Show Deleted</label>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" id="filterShowDeleted" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 rounded-full
                            peer-checked:bg-cyan-600 after:content-[''] after:absolute
                            after:top-[2px] after:left-[2px] after:bg-white
                            after:border-gray-300 after:border after:rounded-full after:h-5
                            after:w-5 after:transition-all peer-checked:after:translate-x-full
                            peer-checked:after:border-white">
                </div>
            </label>
        </div>

        {{-- ───────────── DATA TABLE ───────────── --}}
        <div class="bg-white border border-gray-200 rounded shadow p-2 overflow-x-auto max-w-[2400px]">
            <table id="storagesTable" class="text-xs text-gray-700 w-full min-w-[2400px]">
                <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-[11px] uppercase text-gray-500 tracking-wider">
                    <th class="px-4 py-2">ID</th>
                    <th class="px-4 py-2">Website</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">LB</th>
                    <th class="px-4 py-2">Client</th>
                    <th class="px-4 py-2">Copywriter</th>
                    <th class="px-4 py-2">Copywriter Amount €</th>
                    <th class="px-4 py-2">Copy Comm.<br>Date</th>
                    <th class="px-4 py-2">Copy Subm.<br>Date</th>
                    <th class="px-4 py-2">Copy Period</th>
                    <th class="px-4 py-2">Language</th>
                    <th class="px-4 py-2">Country</th>
                    <th class="px-4 py-2">Publisher Currency</th>
                    <th class="px-4 py-2">Publisher Amount €</th>
                    <th class="px-4 py-2">Publisher Agreed €</th>
                    <th class="px-4 py-2">Total Cost €</th>
                    <th class="px-4 py-2">Menford €</th>
                    <th class="px-4 py-2">Client Copy €</th>
                    <th class="px-4 py-2">Total Revenues €</th>
                    <th class="px-4 py-2">Profit €</th>
                    <th class="px-4 py-2">Target Domain</th>
                    <th class="px-4 py-2">Anchor Text</th>
                    <th class="px-4 py-2">Target URL</th>
                    <th class="px-4 py-2">Campaign Code</th>
                    <th class="px-4 py-2">Sent to Publisher</th>
                    <th class="px-4 py-2">Publication Date</th>
                    <th class="px-4 py-2">Expiration Date</th>
                    <th class="px-4 py-2">Publisher Period</th>
                    <th class="px-4 py-2">Article URL</th>
                    <th class="px-4 py-2">Pay to Us Method</th>
                    <th class="px-4 py-2">Invoice Menford Date</th>
                    <th class="px-4 py-2">Invoice Menford Nr</th>
                    <th class="px-4 py-2">Invoice Company</th>
                    <th class="px-4 py-2">Pay to Us Date</th>
                    <th class="px-4 py-2">Bill Publisher Name</th>
                    <th class="px-4 py-2">Bill Publisher Nr</th>
                    <th class="px-4 py-2">Bill Publisher Date</th>
                    <th class="px-4 py-2">Pay to Publisher Date</th>
                    <th class="px-4 py-2">Pay to Publisher Method</th>
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
@include('storages.partials.url-modal')

@push('scripts')
    {{-- SweetAlert2 (only if not globally loaded) --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        /* ── tiny toast helper ── */
        const toast = msg =>
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: msg,
                showConfirmButton: false,
                timer: 1500,
                timerProgressBar: true
            });

        /* ── copy helper ── */
        const copyToClipboard = txt => new Promise((ok,ko)=>{
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(txt).then(ok).catch(ko);
            } else {
                const t = document.createElement('textarea');
                t.value = txt;
                t.style.position = 'fixed';
                t.style.opacity  = '0';
                document.body.appendChild(t);
                t.select();
                try {
                    document.execCommand('copy');
                    ok();
                } catch(e) { ko(e) }
                document.body.removeChild(t);
            }
        });

        $(function () {
            /* ── Select2 on filters & exportFields ── */
            $('#filterLanguage, #filterCountry, #filterClient, #filterCopy, #filterCategories, #exportFields')
                .select2({
                    width: 'resolve',
                    dropdownAutoWidth: true,
                    placeholder: 'Select',
                    allowClear: true,
                    containerCssClass: 'text-xs',
                    dropdownCssClass: 'text-xs'
                });

            /* ── DataTable ── */
            const table = $('#storagesTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('storages.data') }}",
                    type: "POST",
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: d => {
                        d.publication_from      = $('#filterPublicationFrom').val();
                        d.publication_to        = $('#filterPublicationTo').val();
                        d.copy_id               = $('#filterCopy').val();
                        d.language_id           = $('#filterLanguage').val();
                        d.country_id            = $('#filterCountry').val();
                        d.client_id             = $('#filterClient').val();
                        d.campaign              = $('#filterCampaign').val();
                        d.campaign_code         = $('#filterCampaignCode').val();
                        d.invoice_menford_nr    = $('#filterInvoiceMenfordNr').val();
                        d.bill_publisher_name   = $('#filterBillPublisherName').val();
                        d.target_url            = $('#filterTargetUrl').val();
                        d.article_url           = $('#filterArticleUrl').val();
                        d.status                = $('#filterStatus').val();
                        d.category_ids          = $('#filterCategories').val();
                        d.show_deleted          = $('#filterShowDeleted').is(':checked');
                    }
                },
                columns: [
                    { data:'id', name:'id' },
                    { data:'website_domain', name:'site.domain_name' },
                    { data:'status', name:'status' },
                    { data:'LB', name:'LB' },
                    { data:'client_name', name:'client.first_name', render:(d,t,r)=>(r.client_id?`<a href="#" class="client-link underline text-blue-600" data-client-id="${r.client_id}">${d}</a>`:'') },
                    { data:'copywriter_name', name:'copy.copy_val', render:(d,t,r)=>(r.copy_id?`<a href="#" class="copy-link underline text-blue-600" data-copy-id="${r.copy_id}">${d}</a>`:'') },
                    { data:'copy_nr', name:'copy_nr' },
                    { data:'copywriter_commision_date', name:'copywriter_commision_date', render:fmtDateEU },
                    { data:'copywriter_submission_date', name:'copywriter_submission_date', render:fmtDateEU },
                    { data:'copywriter_period', name:'copywriter_period' },
                    { data:'language_name', name:'language.name' },
                    { data:'country_name', name:'country.country_name' },
                    { data:'publisher_currency', name:'publisher_currency' },
                    { data:'publisher_amount', name:'publisher_amount', render:fmtEuro },
                    { data:'publisher', name:'publisher', render:fmtEuro },
                    { data:'total_cost', name:'total_cost', render:fmtEuro },
                    { data:'menford', name:'menford', render:fmtEuro },
                    { data:'client_copy', name:'client_copy', render:fmtEuro },
                    { data:'total_revenues', name:'total_revenues', render:fmtEuro },
                    { data:'profit', name:'profit', render:fmtEuro },
                    { data:'campaign', name:'campaign' },
                    { data:'anchor_text', name:'anchor_text' },
                    { data:'target_url', name:'target_url', orderable:false, searchable:false, render:d=>d?`<a href="#" class="url-link underline text-blue-600" data-url="${d}">link</a>`:'' },
                    { data:'campaign_code', name:'campaign_code' },
                    { data:'article_sent_to_publisher', name:'article_sent_to_publisher', render:fmtDateEU },
                    { data:'publication_date', name:'publication_date', render:fmtDateEU },
                    { data:'expiration_date', name:'expiration_date', render:fmtDateEU },
                    { data:'publisher_period', name:'publisher_period' },
                    { data:'article_url', name:'article_url', orderable:false, searchable:false, render:d=>d?`<a href="#" class="url-link underline text-blue-600" data-url="${d}">article</a>`:'' },
                    { data:'method_payment_to_us', name:'method_payment_to_us' },
                    { data:'invoice_menford', name:'invoice_menford', render:fmtDateEU },
                    { data:'invoice_menford_nr', name:'invoice_menford_nr' },
                    { data:'invoice_company', name:'invoice_company' },
                    { data:'payment_to_us_date', name:'payment_to_us_date', render:fmtDateEU },
                    { data:'bill_publisher_name', name:'bill_publisher_name' },
                    { data:'bill_publisher_nr', name:'bill_publisher_nr' },
                    { data:'bill_publisher_date', name:'bill_publisher_date', render:fmtDateEU },
                    { data:'payment_to_publisher_date', name:'payment_to_publisher_date', render:fmtDateEU },
                    { data:'method_payment_to_publisher', name:'method_payment_to_publisher' },
                    { data:'categories_list', name:'categories_list', className:'text-center' },
                    { data:'files', name:'files', orderable:false, searchable:false, render:d=>d?`<a href="${d}" target="_blank"><i class="fas fa-paperclip text-lg text-blue-600"></i></a>`:'' },
                    { data:'action', name:'action', orderable:false, searchable:false }
                ],
                order:[[0,'desc']],
                autoWidth:false,
                scrollX:true
            });

            /* ── helper renderers ── */
            function fmtDateEU(iso){ return iso ? new Date(iso).toLocaleDateString('en-GB') : ''; }
            function fmtEuro(v){ return v !== null ? '<strong>€ '+v+'</strong>' : ''; }

            /* ── Search / Clear ── */
            $('#btnSearch').on('click', e => { e.preventDefault(); table.ajax.reload(); });
            $('#btnClear').on('click', e => {
                e.preventDefault();
                $('#filterForm').find('input[type="text"],input[type="date"]').val('');
                $('#filterForm').find('select').val('').trigger('change');
                $('#filterShowDeleted').prop('checked', false);
                table.ajax.reload();
            });
            $('#filterShowDeleted').on('change', () => table.ajax.reload());

            /* ── Build params for export ── */
            const buildParams = () => {
                let p = {
                    publication_from: $('#filterPublicationFrom').val(),
                    publication_to:   $('#filterPublicationTo').val(),
                    copy_id:          $('#filterCopy').val(),
                    language_id:      $('#filterLanguage').val(),
                    country_id:       $('#filterCountry').val(),
                    client_id:        $('#filterClient').val(),
                    campaign:         $('#filterCampaign').val(),
                    campaign_code:    $('#filterCampaignCode').val(),
                    invoice_menford_nr: $('#filterInvoiceMenfordNr').val(),
                    bill_publisher_name: $('#filterBillPublisherName').val(),
                    target_url:       $('#filterTargetUrl').val(),
                    article_url:      $('#filterArticleUrl').val(),
                    status:           $('#filterStatus').val(),
                    category_ids:     $('#filterCategories').val(),
                    show_deleted:     $('#filterShowDeleted').is(':checked') ? 1 : 0
                };
                // include selected fields if any
                const sel = $('#exportFields').val();
                if (sel && sel.length) p.fields = sel;
                return $.param(p);
            };

            /* ── Export CSV / PDF ── */
            $('#btnExportCsv').on('click', e => {
                e.preventDefault();
                window.location = "{{ route('storages.export.csv') }}?" + buildParams();
            });
            $('#btnExportPdf').on('click', e => {
                e.preventDefault();
                window.location = "{{ route('storages.export.pdf') }}?" + buildParams();
            });

            /* ── Toggle filters ── */
            let filtersVisible = true;
            $('#toggleFiltersBtn').on('click', function(){
                $('#filterForm').toggleClass('hidden');
                filtersVisible = !filtersVisible;
                this.textContent = filtersVisible ? 'Hide Filters' : 'Show Filters';
            });

            /* ── Modals ── */
            // client, copy, url code unchanged...

            /* ── Flash ── */
            @if(session('status'))
            toast('{{ session('status') }}');
            @endif
        });
    </script>
@endpush
