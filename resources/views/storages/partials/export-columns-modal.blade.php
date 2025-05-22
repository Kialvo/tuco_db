{{-- resources/views/storages/partials/export-columns-modal.blade.php --}}
<div id="exportColumnsModal"
     class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50">
    <div class="bg-white w-full max-w-xl rounded shadow-lg p-4 space-y-4">

        {{-- Header --}}
        <div class="flex justify-between items-center border-b pb-2">
            <h2 class="text-lg font-semibold text-gray-700">Choose columns to export</h2>
            <button id="exportModalClose" class="text-gray-500 hover:text-gray-700 text-xl leading-none">&times;</button>
        </div>

        {{-- Column list --}}
        <div class="grid grid-cols-2 gap-2 max-h-64 overflow-y-auto text-xs" id="exportColumnList">
            @php
                $cols = [
                    'id'                       => 'ID',
                    'website_domain'           => 'Website',
                    'status'                   => 'Status',
                    'LB'                       => 'LB',
                    'client_name'              => 'Client',
                    'copywriter_name'          => 'Copywriter',
                    'copy_nr'                  => 'Copywriter Amount €',
                    'copywriter_commision_date'=> 'Copy Comm. Date',
                    'copywriter_submission_date'=> 'Copy Subm. Date',
                    'copywriter_period'        => 'Copy Period',
                    'language_name'            => 'Language',
                    'country_name'             => 'Country',
                    'publisher_currency'       => 'Publisher Curr.',
                    'publisher_amount'         => 'Publisher Amount €',
                    'publisher'                => 'Publisher Agreed €',
                    'total_cost'               => 'Total Cost €',
                    'menford'                  => 'Menford €',
                    'client_copy'              => 'Client Copy €',
                    'total_revenues'           => 'Total Revenues €',
                    'profit'                   => 'Profit €',
                    'campaign'                 => 'Target Domain',
                    'anchor_text'              => 'Anchor Text',
                    'target_url'               => 'Target URL',
                    'campaign_code'            => 'Campaign Code',
                    'article_sent_to_publisher'=> 'Sent to Publisher',
                    'publication_date'         => 'Publication Date',
                    'expiration_date'          => 'Expiration Date',
                    'publisher_period'         => 'Publisher Period',
                    'article_url'              => 'Article URL',
                    'method_payment_to_us'     => 'Pay to Us Method',
                    'invoice_menford'          => 'Invoice Menford Date',
                    'invoice_menford_nr'       => 'Invoice Menford Nr',
                    'invoice_company'          => 'Invoice Company',
                    'payment_to_us_date'       => 'Pay to Us Date',
                    'bill_publisher_name'      => 'Bill Publisher Name',
                    'bill_publisher_nr'        => 'Bill Publisher Nr',
                    'bill_publisher_date'      => 'Bill Publisher Date',
                    'payment_to_publisher_date'=> 'Pay to Publisher Date',
                    'method_payment_to_publisher'=> 'Pay to Publisher Method',
                    'categories_list'          => 'Categories',
                    'files'                    => 'Files'
                ];
            @endphp
            @foreach($cols as $key=>$label)
                <label class="inline-flex items-center space-x-1">
                    <input type="checkbox" class="export-field h-4 w-4 text-cyan-600 rounded border-gray-300"
                           value="{{ $key }}" checked>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>

        {{-- Footer --}}
        <div class="flex justify-between items-center pt-2 border-t">
            <div class="space-x-2">
                <button id="exportSelectAll"  class="px-3 py-1 bg-gray-200 rounded text-xs hover:bg-gray-300">Select All</button>
                <button id="exportClearAll"   class="px-3 py-1 bg-gray-200 rounded text-xs hover:bg-gray-300">Clear All</button>
            </div>

            <div class="space-x-2">
                <button id="exportDownloadCsv"
                        class="hidden px-4 py-1 bg-green-600 text-white rounded text-xs hover:bg-green-700">
                    Download CSV
                </button>
                <button id="exportDownloadPdf"
                        class="hidden px-4 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700">
                    Download PDF
                </button>
            </div>
        </div>
    </div>
</div>
