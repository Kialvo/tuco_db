{{-- resources/views/storages/index.blade.php --}}
@extends('layouts.dashboard')
@section('title', 'Storages')

@section('pageHeader')
    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between flex-shrink-0">
        <div>
            <h1 class="text-base font-bold text-gray-800">Storages</h1>
            <p class="text-xs text-gray-500 mt-0.5">Publication tracking and revenue records.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="#" id="btnExportCsv"
               class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300">
                <x-icon name="download" size="sm" /> Export CSV
            </a>
            <a href="#" id="btnExportPdf"
               class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300">
                <x-icon name="document-pdf" size="sm" /> Export PDF
            </a>
            <a href="{{ route('storages.create') }}"
               class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-sm">
                <x-icon name="plus" size="sm" /> Add new article
            </a>
        </div>
    </div>
@endsection

@section('filters')
@include('storages.partials.admin-filter-panel')
@endsection

@section('content')

    {{-- map “database_field” → human label (used by export & bulk-edit) --}}
    @php
        $exportColumns = [
        'id'                             => 'ID',
        'website_domain'                 => 'Domain',
        'status'                         => 'Status',
        'LB'                             => 'LB',
        'client_name'                    => 'Contact',
        'contact_name'                   => 'Publisher',
        'copywriter_name'                => 'Copywriter',
        'copy_nr'                        => 'Copywriter Amount €',
        'copywriter_commision_date'      => 'Copywriter Comm. Date',
        'copywriter_submission_date'     => 'Copywriter Subm. Date',
        'copywriter_period'              => 'Copywriter Period',
        'language_name'                  => 'Language',
        'country_name'                   => 'Country',
        'publisher_currency'             => 'Publisher Currency',
        'publisher_amount'               => 'Publisher Amount €',
        'publisher'                      => 'Publisher Agreed €',
        'total_cost'                     => 'Total Cost €',
        'menford'                        => 'Menford €',
        'client_copy'                    => 'Contact Copy €',
        'total_revenues'                 => 'Total Revenues €',
        'profit'                         => 'Profit €',
        'campaign'                       => 'Target Domain',
        'anchor_text'                    => 'Anchor Text',
        'target_url'                     => 'Target URL',
        'campaign_code'                  => 'Campaign Code',
        'article_sent_to_publisher'      => 'Sent to Publisher',
        'publication_date'               => 'Publication Date',
        'expiration_date'                => 'Expiration Date',
        'publisher_period'               => 'Publisher Period',
        'article_url'                    => 'Article URL',
        'method_payment_to_us'           => 'Pay to Us Method',
        'invoice_menford'                => 'Invoice Menford Date',
        'invoice_menford_nr'             => 'Invoice Menford Nr',
        'invoice_company'                => 'Invoice Company',
        'payment_to_us_date'             => 'Pay to Us Date',
        'bill_publisher_name'            => 'Bill Publisher Name',
        'bill_publisher_nr'              => 'Bill Publisher Nr',
        'bill_publisher_date'            => 'Bill Publisher Date',
        'payment_to_publisher_date'      => 'Pay to Publisher Date',
        'method_payment_to_publisher'    => 'Pay to Publisher Method',
        'categories_list'                => 'Categories',
        'created_at'                     => 'Date Created',
        'files'                          => 'Files',
        ];

        /* fields allowed for bulk-edit (keep in sync with StorageController::BULK_EDITABLE) */
        $bulkEditable = [
        'status','LB','client_id','contact_id','copy_id','copy_nr','copywriter_commision_date',
        'copywriter_submission_date','language_id','country_id',
        'publisher_currency','publisher_amount','publisher','menford','client_copy',
        'campaign','anchor_text','target_url','campaign_code','article_sent_to_publisher',
        'publication_date','expiration_date','article_url',
        'method_payment_to_us','invoice_menford','invoice_menford_nr','invoice_company',
        'payment_to_us_date','bill_publisher_name','bill_publisher_nr','bill_publisher_date',
        'payment_to_publisher_date','method_payment_to_publisher','category_ids','recalculate_totals'
        ];
    @endphp

    <div class="px-6 py-4 bg-gray-50 min-h-full text-xs">
        {{-- Hidden no-op placeholder so legacy JS that targets #toggleFiltersBtn doesn't error --}}
        <button id="toggleFiltersBtn" class="hidden" aria-hidden="true"></button>
        <div class="relative flex flex-col gap-3 mb-4">

            <div id="storageExportPicker"
                 class="hidden absolute left-0 top-full z-40 mt-2 w-full max-w-3xl">
                <div class="w-full rounded border border-gray-200 bg-white shadow-xl">
                    <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                        <p id="storageExportPickerTitle" class="text-sm font-semibold text-gray-700">
                            Choose columns to export
                        </p>
                        <button type="button" id="storageExportClose"
                                class="rounded px-2 py-1 text-xs text-gray-500 hover:bg-gray-100 hover:text-gray-700">
                            Close
                        </button>
                    </div>
                    <div class="border-b border-gray-200 px-4 py-2">
                        <label class="inline-flex items-center gap-2 text-xs text-gray-700">
                            <input type="checkbox" id="storageExportSelectAll" checked
                                   class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                            Select all columns
                        </label>
                    </div>
                    <div class="grid max-h-[55vh] grid-cols-1 gap-2 overflow-y-auto p-4 sm:grid-cols-2 md:grid-cols-3">
                        @foreach($exportColumns as $key=>$label)
                            <label class="inline-flex items-center gap-2 text-xs text-gray-700">
                                <input type="checkbox" class="storage-export-field rounded border-gray-300 text-green-600 focus:ring-green-500"
                                       value="{{ $key }}" checked>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="flex items-center justify-end gap-2 border-t border-gray-200 px-4 py-3">
                        <button type="button" id="storageExportCancel"
                                class="rounded border border-gray-300 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-100">
                            Cancel
                        </button>
                        <button type="button" id="storageExportConfirm"
                                class="rounded bg-green-600 px-3 py-1.5 text-xs text-white hover:bg-green-700">
                            Continue Export
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ───────────────────── FILTERS (unchanged) ───────────────────── --}}

        {{-- ───────────── TABLE ACTION BAR ───────────── --}}
        {{-- ───────────── TABLE ACTION BAR ───────────── --}}
        <div id="actionBar"
             class="flex items-center flex-wrap gap-2 mb-3 px-4 py-2.5 bg-white border border-gray-200 rounded-xl shadow-card">
            <button id="btnBulkEdit"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium
                           bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200
                           disabled:opacity-50 disabled:cursor-not-allowed">
                <x-icon name="pencil" size="sm" /> Bulk Edit
            </button>
            <button id="btnRollback"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium
                           bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200
                           disabled:opacity-50 disabled:cursor-not-allowed">
                <x-icon name="history" size="sm" /> Rollback
            </button>
            <span class="ml-auto text-xs text-gray-500">
                Selected: <span id="selCount" class="font-semibold text-gray-800">0</span>
            </span>
        </div>


        {{-- ───────────────────── DATA TABLE ───────────────────── --}}
        <div id="storagesTableSearchWrap" class="table-search-wrap">
            <div class="flex items-center w-72 border border-gray-300 rounded-md bg-white shadow-sm
                        focus-within:ring-1 focus-within:ring-green-500 focus-within:border-green-500">
                <span class="px-3 text-gray-400 text-base leading-none">
                    <x-icon name="search" size="sm" class="inline" />
                </span>
                <input id="storagesTableSearch" type="text"
                       class="w-full bg-transparent border-0 focus:ring-0 focus:outline-none py-2 pr-3 text-sm leading-5"
                       placeholder="Search storages...">
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-card max-w-[2400px]">

            <table id="storagesTable" class="text-xs text-gray-700 w-full min-w-[2400px]">
                <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-[11px] uppercase text-gray-500 tracking-wider">
                    {{-- master checkbox --}}
                    <th class="px-4 py-2">
                        <input id="chkAll" type="checkbox" class="form-checkbox h-4 w-4 text-green-600">
                    </th>

                    <th class="px-4 py-2">ID</th>
                    <th class="px-4 py-2">Domain</th>
                    <th class="px-4 py-2">Campaign Code</th>
                    <th class="px-4 py-2 min-w-[160px]">Status</th>
                    <th class="px-4 py-2">LB</th>
                    <th class="px-4 py-2">Contact</th>
                    <th class="px-4 py-2">Company</th>
                    <th class="px-4 py-2">Publisher</th>
                    <th class="px-4 py-2">Copywriter</th>
                    <th class="px-4 py-2">Copywriter Amount €</th>
                    <th class="px-4 py-2">Copywriter Comm.<br>Date</th>
                    <th class="px-4 py-2">Copywriter Subm.<br>Date</th>
                    <th class="px-4 py-2">Copywriter Period</th>
                    <th class="px-4 py-2">Language</th>
                    <th class="px-4 py-2">Country</th>
                    <th class="px-4 py-2">Publisher Currency</th>
                    <th class="px-4 py-2">Publisher Amount €</th>
                    <th class="px-4 py-2">Publisher Agreed €</th>
                    <th class="px-4 py-2">Total Cost €</th>
                    <th class="px-4 py-2">Menford €</th>
                    <th class="px-4 py-2">Contact Copy €</th>
                    <th class="px-4 py-2">Total Revenues €</th>
                    <th class="px-4 py-2">Profit €</th>
                    <th class="px-4 py-2">Target Domain</th>
                    <th class="px-4 py-2">Anchor Text</th>
                    <th class="px-4 py-2">Target URL</th>
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
                    <th class="px-4 py-2">Date Added</th>
                    <th class="px-4 py-2">Files</th>
                    <th class="px-4 py-2">Action</th>
                </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                {{-- Sticky summary row — one cell per thead column (46 cells). Numeric columns
                     carry a data-col attribute so refreshSummary() can paint them. --}}
                <tr id="summaryRow">
                    <td></td>                                            {{--  0 checkbox --}}
                    <td class="text-right text-[10px] uppercase tracking-wider font-semibold text-gray-400">Total</td>  {{--  1 ID column repurposed as label --}}
                    <td></td>                                            {{--  2 Domain --}}
                    <td></td>                                            {{--  3 Campaign Code --}}
                    <td></td>                                            {{--  4 Status --}}
                    <td></td>                                            {{--  5 LB --}}
                    <td></td>                                            {{--  6 Client --}}
                    <td></td>                                            {{--  7 Company --}}
                    <td></td>                                            {{--  8 Publisher --}}
                    <td></td>                                            {{--  9 Copywriter --}}
                    <td data-col="copy_nr"            data-index="10"></td>  {{-- 10 Copywriter Amount € --}}
                    <td></td>                                            {{-- 11 Copywriter Comm. Date --}}
                    <td></td>                                            {{-- 12 Copywriter Subm. Date --}}
                    <td data-col="copywriter_period"  data-index="13"></td> {{-- 13 Copywriter Period --}}
                    <td></td>                                            {{-- 14 Language --}}
                    <td></td>                                            {{-- 15 Country --}}
                    <td></td>                                            {{-- 16 Publisher Currency --}}
                    <td data-col="publisher_amount"   data-index="17"></td> {{-- 17 Publisher Amount € --}}
                    <td data-col="publisher"          data-index="18"></td> {{-- 18 Publisher Agreed € --}}
                    <td data-col="total_cost"         data-index="19"></td> {{-- 19 Total Cost € --}}
                    <td data-col="menford"            data-index="20"></td> {{-- 20 Menford € --}}
                    <td data-col="client_copy"        data-index="21"></td> {{-- 21 Contact Copy € --}}
                    <td data-col="total_revenues"     data-index="22"></td> {{-- 22 Total Revenues € --}}
                    <td data-col="profit"             data-index="23"></td> {{-- 23 Profit € --}}
                    <td></td>                                            {{-- 24 Target Domain --}}
                    <td></td>                                            {{-- 25 Anchor Text --}}
                    <td></td>                                            {{-- 26 Target URL --}}
                    <td></td>                                            {{-- 27 Sent to Publisher --}}
                    <td></td>                                            {{-- 28 Publication Date --}}
                    <td></td>                                            {{-- 29 Expiration Date --}}
                    <td data-col="publisher_period"   data-index="30"></td> {{-- 30 Publisher Period --}}
                    <td></td>                                            {{-- 31 Article URL --}}
                    <td></td>                                            {{-- 32 Pay to Us Method --}}
                    <td></td>                                            {{-- 33 Invoice Menford Date --}}
                    <td></td>                                            {{-- 34 Invoice Menford Nr --}}
                    <td></td>                                            {{-- 35 Invoice Company --}}
                    <td></td>                                            {{-- 36 Pay to Us Date --}}
                    <td></td>                                            {{-- 37 Bill Publisher Name --}}
                    <td></td>                                            {{-- 38 Bill Publisher Nr --}}
                    <td></td>                                            {{-- 39 Bill Publisher Date --}}
                    <td></td>                                            {{-- 40 Pay to Publisher Date --}}
                    <td></td>                                            {{-- 41 Pay to Publisher Method --}}
                    <td></td>                                            {{-- 42 Categories --}}
                    <td></td>                                            {{-- 43 Date Added --}}
                    <td></td>                                            {{-- 44 Files --}}
                    <td></td>                                            {{-- 45 Action --}}
                </tr>
                </tfoot>


            </table>
            <div id="calcPortal"
                 class="fixed z-[999999] hidden"
                 style="min-width:110px;background:#1f2937;color:#f3f4f6;
            border:1px solid #4b5563;border-radius:4px;
            padding:.25rem 0;white-space:nowrap"></div>

        </div>
    </div>
@endsection

{{-- existing small modals --}}
@include('storages.partials.client-modal')
@include('storages.partials.copy-modal')
@include('storages.partials.url-modal')

{{-- NEW bulk-edit modal --}}
@include('storages.partials.bulk-modal')
@include('websites.partials.contact-modal')

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        /* ───────── helpers ───────── */
        const toast = m=>Swal.fire({toast:true,position:'top-end',icon:'success',title:m,
            showConfirmButton:false,timer:1500});
        const oops  = m=>Swal.fire({toast:true,position:'top-end',icon:'error',title:m,
            showConfirmButton:false,timer:2000});

        function toastUndo(msg, token) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                background: '#2563eb',        // blue-600
                color: '#fff',
                html: `<span class="font-semibold">${msg}</span>
               <button id="undoBtn"
                       style="background:#f59e0b"
                       class="ml-3 px-2 py-[2px] rounded text-xs font-bold">
                       UNDO
               </button>`,
                showConfirmButton: false,
                timer: 4000,                  // 4 000 ms
                timerProgressBar: true,
                didOpen: () => {
                    document.getElementById('undoBtn').onclick = () => {
                        fetch("{{ route('storages.rollback') }}", {
                            method : 'POST',
                            headers: {
                                'Content-Type':'application/json',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            body: JSON.stringify({ token })
                        })
                            .then(r => r.json())
                            .then(r => { toast(r.message); table.ajax.reload(null,false); })
                            .catch(() => oops('Failed to undo'));
                    };
                }
            });
        }function toastUndo(msg, token) {

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                html: `${msg}
               <button id="undoBtn"
                       class="bg-yellow-500 hover:bg-yellow-600
                              text-white px-2 py-1 rounded text-xs ml-2">
                       Undo
               </button>`,
                showConfirmButton: false,
                timer   : 4000,         // 4 s
                timerProgressBar: true,
                didOpen: () => {

                    document.getElementById('undoBtn').onclick = () => {

                        /* --- send as classical form data -------------------------- */
                        $.post(
                            "{{ route('storages.rollback') }}",
                            { token, _token: $('meta[name="csrf-token"]').attr('content')},
                            r => {
                                toast(r.message);           // green toast
                                if (window.stTable) {       // refresh without losing page/filters
                                    window.stTable.ajax.reload(null, false);
                                }
                            }
                        ).fail(()=>oops('Failed to undo'));
                    };
                }
            });
        }



        /* ───────── document ready ───────── */
        $(function(){

            /* Select2 */
            $('#filterLanguage,#filterCountry,#filterClient,#filterContact,#filterCopy,#filterCategories,#filterCampaignId')
                .select2({width:'resolve',dropdownAutoWidth:true,placeholder:'Select',allowClear:true,
                    containerCssClass:'text-xs',dropdownCssClass:'text-xs'});

            /* helpers */
            // Unified 12-status list (shared with Campaigns): slug => label
            const STATUS_LABELS = @json(\App\Support\PublicationStatus::labels());
            const TONE_RING = {
                green:  'bg-green-100 text-green-700 ring-green-200',
                amber:  'bg-amber-100 text-amber-700 ring-amber-200',
                red:    'bg-red-100 text-red-700 ring-red-200',
                blue:   'bg-blue-100 text-blue-700 ring-blue-200',
                purple: 'bg-purple-100 text-purple-700 ring-purple-200',
                gray:   'bg-gray-100 text-gray-600 ring-gray-200',
            };
            const STATUS_TONES = @json(collect(\App\Support\PublicationStatus::all())->map(fn($d) => $d['tone']));
            const renderStatusPill = function (data) {
                if (!data || data === '0') return '<span class="text-gray-300">—</span>';
                const key = String(data).toLowerCase().replace(/\s+/g, '_');
                const tone = TONE_RING[STATUS_TONES[key]] || 'bg-gray-100 text-gray-700 ring-gray-200';
                const label = STATUS_LABELS[key] || String(data).replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
                return `<span class="inline-flex items-center whitespace-nowrap px-2.5 py-0.5 rounded-full text-[11px] font-medium ring-1 ring-inset ${tone}">${label}</span>`;
            };

            /* DataTable */
            const table = $('#storagesTable').DataTable({
                processing:true, serverSide:true,
                dom: "<'dt-toolbar-top'<'flex items-center gap-3'l<'dt-search'>>>" +
                     "<'dt-scroll'rt>" +
                     "<'dt-toolbar-bottom'ip>",
                ajax:{
                    url:"{{ route('storages.data') }}",
                    type:"POST",
                    headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')},
                    data:d=>{
                        /* filters => request */
                        d.publication_from =$('#filterPublicationFrom').val();
                        d.publication_to   =$('#filterPublicationTo').val();
                        d.copy_id          =$('#filterCopy').val();
                        d.language_id      =$('#filterLanguage').val();
                        d.country_id       =$('#filterCountry').val();
                        d.client_id        =$('#filterClient').val();
                        d.company          =$('#filterCompany').val();
                        d.contact_id = $('#filterContact').val();

                        // NEW
                        d.website_domain   =$('#filterWebsiteDomain').val();


                        d.campaign         =$('#filterCampaign').val();
                        d.lb_campaign_id   =$('#filterCampaignId').val();
                        d.invoice_menford_nr=$('#filterInvoiceMenfordNr').val();
                        d.bill_publisher_name=$('#filterBillPublisherName').val();
                        d.target_url       =$('#filterTargetUrl').val();
                        d.article_url      =$('#filterArticleUrl').val();
                        d.status           =$('#filterStatus').val();
                        d.category_ids     =$('#filterCategories').val();
                        d.show_deleted     =$('#filterShowDeleted').is(':checked');
                    }
                },
                columns:[
                    { /* row checkbox */
                        data:'id',orderable:false,searchable:false,className:'text-center',
                        render:id=>`<input type="checkbox" class="rowChk form-checkbox h-4 w-4 text-green-600" value="${id}">`
                    },
                    {data:'id',name:'id'},
                    {data:'website_domain',name:'site.domain_name'},
                    {data:'campaign_code',name:'campaign_code',
                        render:(d,t,r)=>{
                            if (r.lb_campaign_id) {
                                const code = (r.lb_campaign && r.lb_campaign.code) ? r.lb_campaign.code : d;
                                return `<a href="{{ url('campaigns') }}/${r.lb_campaign_id}" class="text-green-600 font-medium underline">${code ?? ''}</a>`;
                            }
                            return d ? `<span class="text-gray-400" title="Legacy code (not linked to a campaign)">${d}</span>` : '';
                        }},
                    {data:'status',name:'status', render: renderStatusPill},
                    {data:'LB',name:'LB'},
                    {data:'client_name',name:'client.first_name',
                        render:(d,t,r)=>r.client_id?`<a href="#" class="client-link underline text-blue-600"
                                           data-client-id="${r.client_id}">${d}</a>`:''},
                    {data:'client_company',name:'client_company',searchable:false,orderable:false,className:'text-center'},
                    {
                        data: 'contact_name',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            if (!row.contact_id || !data) {
                                return 'No Publisher';
                            }
                            return `
            <a href="#"
               class="contact-link text-blue-600 underline"
               data-contact-id="${row.contact_id}">
                ${data}
            </a>
        `;
                        }
                    },

                    {data:'copywriter_name',name:'copy.copy_val',
                        render:(d,t,r)=>r.copy_id?`<a href="#" class="copy-link underline text-blue-600"
                                           data-copy-id="${r.copy_id}">${d}</a>`:''},
                    {data:'copy_nr',name:'copy_nr',render:eu, className:'text-right'},
                    {data:'copywriter_commision_date',name:'copywriter_commision_date',render:dt},
                    {data:'copywriter_submission_date',name:'copywriter_submission_date',render:dt},
                    {data:'copywriter_period',name:'copywriter_period'},
                    {data:'language_name',name:'language.name'},
                    {data:'country_name',name:'country.country_name',
                        render: function (data, type, row) {
                            if (! data) return '<span class="text-gray-300">—</span>';
                            const flag = row.country_iso
                                ? `<img src="https://flagcdn.com/48x36/${row.country_iso}.png" srcset="https://flagcdn.com/96x72/${row.country_iso}.png 2x" width="20" height="15" alt="" class="rounded-sm border border-gray-200" loading="lazy">`
                                : '';
                            return `<span class="inline-flex items-center gap-1.5">${flag}<span>${data}</span></span>`;
                        }
                    },
                    {data:'publisher_currency',name:'publisher_currency', render: renderCurrencyPill, className:'text-center'},
                    {data:'publisher_amount',name:'publisher_amount',render:eu,       className:'text-right'},
                    {data:'publisher',name:'publisher',render:eu,                     className:'text-right'},
                    {data:'total_cost',name:'total_cost',render:eu,                   className:'text-right'},
                    {data:'menford',name:'menford',render:eu,                         className:'text-right'},
                    {data:'client_copy',name:'client_copy',render:eu,                 className:'text-right'},
                    {data:'total_revenues',name:'total_revenues',render:eu,           className:'text-right'},
                    {data:'profit',name:'profit',render:euProfit,                     className:'text-right'},
                    {data:'campaign',name:'campaign'},
                    {data:'anchor_text',name:'anchor_text'},
                    {data:'target_url',name:'target_url',orderable:false,searchable:false,
                        render:d=>d?`<a href="#" class="url-link underline text-blue-600" data-url="${d}">link</a>`:''},
                    {data:'article_sent_to_publisher',name:'article_sent_to_publisher',render:dt},
                    {data:'publication_date',name:'publication_date',render:dt},
                    {data:'expiration_date',name:'expiration_date',render:dt},
                    {data:'publisher_period',name:'publisher_period'},
                    {data:'article_url',name:'article_url',orderable:false,searchable:false,
                        render:d=>d?`<a href="#" class="url-link underline text-blue-600" data-url="${d}">article</a>`:''},
                    {data:'method_payment_to_us',name:'method_payment_to_us'},
                    {data:'invoice_menford',name:'invoice_menford',render:dt},
                    {data:'invoice_menford_nr',name:'invoice_menford_nr'},
                    {data:'invoice_company',name:'invoice_company'},
                    {data:'payment_to_us_date',name:'payment_to_us_date',render:dt},
                    {data:'bill_publisher_name',name:'bill_publisher_name'},
                    {data:'bill_publisher_nr',name:'bill_publisher_nr'},
                    {data:'bill_publisher_date',name:'bill_publisher_date',render:dt},
                    {data:'payment_to_publisher_date',name:'payment_to_publisher_date',render:dt},
                    {data:'method_payment_to_publisher',name:'method_payment_to_publisher'},
                    {data:'categories_list',name:'categories_list',className:'text-center max-w-[160px]',
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
                    { data:'created_at', name:'created_at', render:dt },
                    {data:'files',name:'files',orderable:false,searchable:false,
                        render:d=>d?`<a href="${d}" target="_blank" class="text-blue-600 hover:text-blue-700"><x-icon name="paperclip" size="lg" class="inline" /></a>`:''},
                    {data:'action',name:'action',orderable:false,searchable:false}
                ],
                order:[[1,'desc']], /* skip checkbox column */
                autoWidth:false,
                scrollX:true,
                lengthMenu: [[10, 25, 50, 100, 200, 500, -1],
                    [10, 25, 50, 100, 200, 500, "All"]],
            });

            // Sticky header
            if (window.initDtStickyHeader) window.initDtStickyHeader(table);

            // Move search box into the DataTable header (next to "Show entries")
            $(table.table().container()).find('.dt-search').append($('#storagesTableSearchWrap'));

            // Table search (debounced to avoid slow typing)
            let storagesSearchTimer;
            $('#storagesTableSearch').on('input', function() {
                const value = this.value;
                clearTimeout(storagesSearchTimer);
                storagesSearchTimer = setTimeout(() => {
                    table.search(value).draw();
                }, 300);
            });
            $('#storagesTableSearch').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    clearTimeout(storagesSearchTimer);
                    table.search(this.value).draw();
                }
            });

            let filtersApplied = false;
            setTimeout(syncFooterWidths, 0);
            /* ───────────────────────── SUMMARY ROW ───────────────────────── */
            // ── helper keeps footer cells the same width as their header  ──
            // The tfoot lives inside the actual table — cells inherit column widths
            // automatically. No JS width-sync needed.
            function syncFooterWidths () { /* no-op (kept for callsite compatibility) */ }


            const numericCols = [
                'copy_nr','copywriter_period','publisher_amount','publisher',
                'total_cost','menford','client_copy','total_revenues',
                'profit','publisher_period'
            ];
            const calcLabels = {none:'None',sum:'Sum',average:'Average',
                median:'Median',min:'Min',max:'Max',count:'Count'};
            const prefsKey   = 'stSummaryPrefs';
            let   prefs      = JSON.parse(localStorage.getItem(prefsKey)||'{}');

            /* A.--- build mini-widgets */          /* default is “none” (= no calc) */
            /* A.–– build mini-widgets — default = “sum” */
            $('#summaryRow td[data-col]').each(function () {
                const col  = $(this).data('col');
                let mode = prefs[col];
                if (!mode || mode === 'none') mode = 'sum';
                prefs[col] = mode;               // save back         // default → Sum
                // write back so it’s stored

                $(this).html(`
        <div class="flex flex-col items-end gap-0.5 w-full">
            <span class="sum-val"></span>
            <button class="calc-toggle ${mode!=='none'?'active':'inactive'}"
                    data-col="${col}">${calcLabels[mode]}</button>
        </div>
    `);
            });



            /* B.--- right-align the summary numeric cells (header alignment stays as authored) */
            table.one('init', () => {
                $('#summaryRow td[data-col]').css('text-align', 'right');
            });



            /* C. current filter helper (same keys as controller expects) */
            /* C. helper – send EVERY filter the backend knows -------------------- */
            function currentFilters () {
                return {
                    publication_from  : $('#filterPublicationFrom').val(),
                    publication_to    : $('#filterPublicationTo').val(),
                    copy_id           : $('#filterCopy').val(),
                    language_id       : $('#filterLanguage').val(),
                    country_id        : $('#filterCountry').val(),
                    client_id         : $('#filterClient').val(),
                    company           : $('#filterCompany').val(),
                    status            : $('#filterStatus').val(),
// NEW
                    website_domain    : $('#filterWebsiteDomain').val(),
                    campaign          : $('#filterCampaign').val(),
                    lb_campaign_id    : $('#filterCampaignId').val(),
                    invoice_menford_nr: $('#filterInvoiceMenfordNr').val(),
                    bill_publisher_name:$('#filterBillPublisherName').val(),
                    target_url        : $('#filterTargetUrl').val(),
                    article_url       : $('#filterArticleUrl').val(),

                    category_ids      : $('#filterCategories').val(),   // array or null
                    show_deleted      : $('#filterShowDeleted').is(':checked')
                };
            }


            /* D.--- fetch + paint                                   */
            /* D.--- fetch + paint  — now works on first load too — */
            /* D.--- fetch + paint — only when needed — */
            function refreshSummary() {

                const selectedIds = $('.rowChk:checked').map((_, c) => c.value).get();

                /* run ONLY if
                   a) filters were applied (Search pressed)  OR
                   b) at least one row is selected                           */
                if (!filtersApplied && selectedIds.length === 0) {
                    $('#summaryRow .sum-val').text('—');
                    return;
                }

                /* build payload */
                const payload = { ...currentFilters() };
                if (selectedIds.length) payload.ids = selectedIds;

                $.ajax({
                    url : "{{ route('storages.summary') }}",
                    type: "POST",
                    data: payload,
                    dataType:'json',
                    headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')},
                    success: json => {
                        $('#summaryRow td[data-col]').each(function () {
                            const col  = $(this).data('col'),
                                mode = prefs[col] || 'none',
                                val  = json[col] ? json[col][mode] : null;

                            $(this).find('.sum-val').text(
                                mode==='none'||val===null ? '—'
                                    : Number(val).toLocaleString('en-US')
                            );

                            $(this).find('.calc-toggle')
                                .text(calcLabels[mode])
                                .toggleClass('active',   mode!=='none')
                                .toggleClass('inactive', mode==='none');
                        });
                    }
                });
            }




            /* E. refresh on every redraw AND first load */
            table.on('draw', refreshSummary);
            table.on('draw init', syncFooterWidths);
            $(window).on('resize', syncFooterWidths);
            refreshSummary();

            /* F.  interactions */
            $(document).on('click', '.calc-toggle', function (e) {
                e.stopPropagation();

                const $btn   = $(this);
                const col    = $btn.data('col');
                const rect   = $btn[0].getBoundingClientRect();        // button position
                const portal = $('#calcPortal');

                // build menu HTML
                portal.html(Object.entries(calcLabels).map(
                    ([k,l]) => `<div class="calc-opt px-2 py-1 text-[11px] hover:bg-blue-600 cursor-pointer"
                         data-col="${col}" data-opt="${k}">
                         ${l}</div>`
                ).join(''));

                // position it above the button (or below if not enough room)
                const menuH =  portal.outerHeight();
                let   top   = rect.top - menuH - 6;                    // try above
                if (top < 0) top = rect.bottom + 6;                    // fallback below

                portal.css({left: rect.left, top}).removeClass('hidden');
            });

            $(document).on('click', '.calc-opt', function (e) {
                const col  = $(this).data('col');
                const opt  = $(this).data('opt');
                prefs[col] = opt;
                localStorage.setItem(prefsKey, JSON.stringify(prefs));
                $('#calcPortal').addClass('hidden');
                refreshSummary();
            });

            $(document).on('click', () => $('#calcPortal').addClass('hidden'));

            /* ─────────────────────────────────────────────────────────────── */


            /* ----------------------------------------------------------
             * live “Selected: N” badge
             * ----------------------------------------------------------*/
            function updateSelCount () {
                $('#selCount').text($('.rowChk:checked').length);
            }

            /* every time a row checkbox (or the master one) toggles */
            $(document).on('change', '.rowChk, #chkAll', updateSelCount);
            $(document).on('change', '.rowChk, #chkAll', refreshSummary);


            /* when the table redraws (pagination, search, etc.) */
            table.on('draw', updateSelCount);


            window.stTable = table;
            /* cell formatters */
            const emDash = '<span class="text-gray-300">—</span>';
            function dt(v){return v?new Date(v).toLocaleDateString('en-GB'):emDash;}
            function eu(v){return (v===null || v===undefined || v==='') ? emDash : '<span class="font-semibold text-gray-800">€ '+v+'</span>';}
            function euProfit(v){
                if (v===null || v===undefined || v==='') return emDash;
                const neg = Number(v) < 0;
                return '<span class="font-semibold '+(neg?'text-red-600':'text-gray-800')+'">€ '+v+'</span>';
            }
            function renderMetric(v){return (v===null || v===undefined || v==='') ? emDash : v;}
            function renderCurrencyPill(v){return v
                ? '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider bg-gray-100 text-gray-700 ring-1 ring-inset ring-gray-200">'+String(v).toUpperCase()+'</span>'
                : emDash;}

            /* master checkbox */
            $('#chkAll').on('change',function(){ $('.rowChk').prop('checked',this.checked); });

            /* filters: Search / Clear / toggle-deleted */
            $('#btnSearch').on('click',e=>{
                e.preventDefault();
                table.ajax.reload();
                filtersApplied = true;                     // mark that filters are active
                table.ajax.reload(refreshSummary);
                window.buildFilterChips(() => table.ajax.reload(refreshSummary));
            });
            $('#btnClear').on('click',e=>{
                e.preventDefault();
                $('#filterForm').find('input[type=”text”],input[type=”date”]').val('');
                $('#filterForm').find('select').val('').trigger('change');
                $('#filterShowDeleted').prop('checked',false);
                $('#storagesTableSearch').val('');
                table.search('');
                filtersApplied = false;
                table.ajax.reload();
                $('#summaryRow .sum-val').text('—');
                window.buildFilterChips(() => table.ajax.reload(refreshSummary));
            });
            $('#filterShowDeleted').on('change',()=>table.ajax.reload());

            /* build export params */
            const buildParams=(selectedFields = null)=>{
                let p={
                    publication_from:$('#filterPublicationFrom').val(),
                    publication_to  :$('#filterPublicationTo').val(),
                    copy_id         :$('#filterCopy').val(),
                    language_id     :$('#filterLanguage').val(),
                    country_id      :$('#filterCountry').val(),
                    client_id       :$('#filterClient').val(),
                    company         :$('#filterCompany').val(),
                    website_domain  :$('#filterWebsiteDomain').val(),
                    campaign        :$('#filterCampaign').val(),
                    lb_campaign_id  :$('#filterCampaignId').val(),
                    invoice_menford_nr:$('#filterInvoiceMenfordNr').val(),
                    bill_publisher_name:$('#filterBillPublisherName').val(),
                    target_url      :$('#filterTargetUrl').val(),
                    article_url     :$('#filterArticleUrl').val(),
                    status          :$('#filterStatus').val(),
                    category_ids    :$('#filterCategories').val(),
                    show_deleted    :$('#filterShowDeleted').is(':checked')?1:0
                };
                if (Array.isArray(selectedFields) && selectedFields.length) p.fields=selectedFields;
                return $.param(p);
            };

            let storagePendingExportType = null;
            const getStorageSelectedFields = () =>
                $('.storage-export-field:checked').map((_, el) => el.value).get();

            const syncStorageSelectAll = function () {
                const total = $('.storage-export-field').length;
                const checked = $('.storage-export-field:checked').length;
                $('#storageExportSelectAll').prop('checked', total > 0 && checked === total);
            };

            const openStorageExportPicker = function(type) {
                storagePendingExportType = type;
                $('#storageExportPickerTitle').text(
                    type === 'pdf' ? 'Choose columns for PDF export' : 'Choose columns for CSV export'
                );
                $('#storageExportPicker').removeClass('hidden');
                syncStorageSelectAll();
            };

            const closeStorageExportPicker = function() {
                $('#storageExportPicker').addClass('hidden');
                storagePendingExportType = null;
            };

            $('#storageExportSelectAll').on('change', function() {
                $('.storage-export-field').prop('checked', this.checked);
            });
            $(document).on('change', '.storage-export-field', syncStorageSelectAll);
            $('#storageExportClose, #storageExportCancel').on('click', closeStorageExportPicker);
            $(document).on('mousedown', function(e) {
                if ($('#storageExportPicker').hasClass('hidden')) {
                    return;
                }
                if ($(e.target).closest('#storageExportPicker, #btnExportCsv, #btnExportPdf').length) {
                    return;
                }
                closeStorageExportPicker();
            });

            $('#storageExportConfirm').on('click', function() {
                const selected = getStorageSelectedFields();
                if (!selected.length) {
                    Swal.fire({ icon: 'warning', title: 'Select at least one column' });
                    return;
                }

                const route = storagePendingExportType === 'pdf'
                    ? "{{ route('storages.export.pdf') }}"
                    : "{{ route('storages.export.csv') }}";

                window.location = route + "?" + buildParams(selected);
                closeStorageExportPicker();
            });

            $('#btnExportCsv').on('click',e=>{
                e.preventDefault();
                openStorageExportPicker('csv');
            });
            $('#btnExportPdf').on('click',e=>{
                e.preventDefault();
                openStorageExportPicker('pdf');
            });

            /* toggle filter visibility */
            let filtersVisible=true;
            $('#toggleFiltersBtn').on('click',function(){
                $('#filterForm').toggleClass('hidden');
                filtersVisible=!filtersVisible;
                this.textContent=filtersVisible?'Hide Filters':'Show Filters';
            });

            /* ── Modals ── */
            /* ---------- Client & Copy modals ---------- */
            $(document).on('click','.client-link',function(e){
                e.preventDefault();
                $.get("{{ route('clients.showAjax','') }}/"+$(this).data('client-id'),res=>{
                    if(res.status==='success'){
                        const c=res.data;
                        $('#modalClientName').text((c.first_name??'')+' '+(c.last_name??''));
                        $('#modalClientEmail').text(c.email??'');
                        $('#modalClientCompany').text(c.company??'');
                        $('#clientModal').removeClass('hidden').addClass('flex');
                    }else{alert('Could not load client.');}
                }).fail(()=>alert('Error fetching client.'));
            });
            $(document).on('click','#closeClientModal,#closeClientModalBottom',()=>$('#clientModal').addClass('hidden').removeClass('flex'));

            $(document).on('click','.copy-link',function(e){
                e.preventDefault();
                $.get("{{ route('copy.showAjax','') }}/"+$(this).data('copy-id'),res=>{
                    if(res.status==='success'){
                        $('#modalCopyVal').text(res.data.copy_val);
                        $('#copyModal').removeClass('hidden').addClass('flex');
                    }else{alert('Could not load copy.');}
                }).fail(()=>alert('Error fetching copy.'));
            });
            $(document).on('click','#closeCopyModal,#closeCopyModalBottom',()=>$('#copyModal').addClass('hidden').removeClass('flex'));

            /* ---------- URL modal ---------- */
            async function copyUrlToClipboard(value) {
                const text = String(value ?? '').trim();
                if (!text) {
                    throw new Error('No URL to copy.');
                }

                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(text);
                    return;
                }

                const input = document.getElementById('urlModalInput');
                if (!input) {
                    throw new Error('Copy input not found.');
                }

                input.focus();
                input.select();
                input.setSelectionRange(0, input.value.length);

                if (!document.execCommand('copy')) {
                    throw new Error('Clipboard copy failed.');
                }

                if (window.getSelection) {
                    window.getSelection().removeAllRanges();
                }
            }

            $(document).on('click','.url-link',function(e){
                e.preventDefault();
                const url=$(this).data('url');
                $('#urlModalInput').val(url);
                $('#urlModalOpen').attr('href',url);
                $('#urlModal').removeClass('hidden').addClass('flex');
            });
            $('#urlModalClose').on('click',()=>$('#urlModal').addClass('hidden').removeClass('flex'));

            $('#urlModalCopy').on('click',function(){
                copyUrlToClipboard($('#urlModalInput').val())
                    .then(()=>toast('Copied to clipboard!'))
                    .catch(()=>Swal.fire({icon:'error',title:'Copy failed'}));
            });

            // ---------- Contact modal (same as Websites) ----------
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

                            // Build websites list (same as Websites index)
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
                            </li>`;
                                });
                                websitesHtml += '</ul>';
                            } else {
                                websitesHtml = '<p>No domains found for this publisher.</p>';
                            }
                            $('#modalContactWebsites').html(websitesHtml);

                            $('#contactModal').removeClass('hidden');
                        } else {
                            alert('Could not load publisher info.');
                        }
                    },
                    error: function() {
                        alert('Error fetching publisher info.');
                    }
                });
            });

            $('#closeContactModal, #closeContactModalBottom').on('click', function() {
                $('#contactModal').addClass('hidden');
            });

            /* ──────────────── BULK-EDIT logic ──────────────── */
            /* ─── Bulk-Edit ─── */
            function buildBulkInput () {
                const field = $('#bulkField').val();
                const meta  = window.bulkMeta[field] || { type: 'text' };
                const wrap  = $('#bulkInputWrapper');

                wrap.empty();
                /* NEW – Apply Auto Calculation needs no additional value */
                if (field === 'recalculate_totals') {
                    wrap.append('<p class="text-gray-500 text-xs">Nothing to fill in – just click “Save”.</p>');
                    return;                                 // stop here
                }
                /* ――― date picker ――― */
                if (meta.type === 'date') {
                    wrap.append(
                        `<input id="bulkValue" type="date"
                    class="w-full border border-gray-300 rounded px-2 py-1 text-xs
                           focus:ring-green-500">`
                    );
                    return;
                }

                /* ――― select / drop-down ――― */
                if (meta.type === 'select') {
                    const none = `<option value="">-- Clear --</option>`;   // let user blank the field
                    const opts = Object.entries(meta.options || {})
                        .map(([v, l]) => `<option value="${v}">${l}</option>`).join('');

                    wrap.append(
                        `<select id="bulkValue"
                     class="w-full border border-gray-300 rounded px-2 py-1 text-xs
                            focus:ring-green-500">${none}${opts}</select>`
                    );

                    /* large lists → enhance with Select2 */
                    if ($('#bulkValue option').length > 15) {
                        $('#bulkValue').select2({ width: 'resolve', dropdownAutoWidth: true });
                    }
                    return;
                }

                /* ――― textarea (long text) ――― */
                if (meta.type === 'textarea') {
                    wrap.append(
                        `<textarea id="bulkValue" rows="3"
                       class="w-full border border-gray-300 rounded px-2 py-1 text-xs
                              focus:ring-green-500"></textarea>`
                    );
                    return;
                }

                /* multiselect (many categories) -----------------------------------------*/
                if (meta.type === 'multiselect') {
                    const opts = Object.entries(meta.options || {})
                        .map(([v,l]) => `<option value="${v}">${l}</option>`).join('');

                    wrap.append(`
        <select id="bulkValue" multiple
                class="w-full border border-gray-300 rounded px-2 py-1 text-xs
                       focus:ring-green-500">${opts}</select>`);

                    $('#bulkValue').select2({
                        width:'resolve', dropdownAutoWidth:true, placeholder:'Select'
                    });
                    return;
                }

                /* ――― fallback = plain text/number input ――― */
                wrap.append(
                    `<input id="bulkValue" type="text"
                class="w-full border border-gray-300 rounded px-2 py-1 text-xs
                       focus:ring-green-500">`
                );
            }

            $('#bulkField').on('change', buildBulkInput);
            buildBulkInput(); // first time

            $('#btnBulkEdit').on('click', function () {
                const cnt = $('.rowChk:checked').length;
                if (!cnt) { oops('Select at least one row'); return; }
                $('#bulkEditModal').removeClass('hidden').addClass('flex');
            });
            $('#bulkCancel').on('click', () => $('#bulkEditModal').addClass('hidden').removeClass('flex'));

            $('#bulkSave').on('click', function () {
                const ids   = $('.rowChk:checked').map((_, c) => c.value).get();
                const field = $('#bulkField').val();
                let value = $('#bulkValue').val();            // may be null / [] / ''
                if (value === undefined || value === null) {
                    value = '';                               // make sure key exists
                }

                if (!ids.length) {
                    oops('Select at least one row');
                    return;
                }

                $.ajax({
                    url: "{{ route('storages.bulkUpdate') }}",
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: { ids, field, value },
                    success : r => {
                        toast(r.message);             // normal green toast
                        if (r.undo_token) {
                            toastUndo('Update saved.', r.undo_token);
                        }
                        $('#bulkEditModal').addClass('hidden').removeClass('flex');
                        $('#chkAll').prop('checked', false);
                        table.ajax.reload(null, false);
                    },

                    error: x => oops(x.responseJSON?.message || 'Error')
                });
            });

            $('#btnRollback').on('click',function(){
                const ids = $('.rowChk:checked').map((_,c)=>c.value).get();
                if(!ids.length){ oops('Select at least one row'); return; }

                Swal.fire({
                    title:'Restore previous snapshot?',
                    icon :'warning',
                    showCancelButton:true,
                    confirmButtonText:'Yes, rollback!'
                }).then(res=>{
                    if(!res.isConfirmed) return;

                    $.post("{{ route('storages.rollback') }}",
                        { ids, _token:'{{ csrf_token() }}' },
                        r=>{
                            toast(r.message);
                            $('#chkAll').prop('checked',false);
                            $('#storagesTable').DataTable().ajax.reload(null,false);
                        }).fail(()=>oops('Rollback failed'));
                });
            });



            /* flash from server */
            @if(session('status')) toast('{{ session('status') }}'); @endif
        });

        @if(session('undo_token'))
        toastUndo('{{ session('status') }}','{{ session('undo_token') }}');
        @endif
    </script>


@endpush
