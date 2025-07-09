{{-- resources/views/storages/partials/bulk-modal.blade.php --}}
@php
    /** --------------------------------------------------------
     * 1.  Human-readable labels  (copy-pasted from edit view)
     * ----------------------------------------------------- */
    $bulkLabels = [
        // GENERAL / FK
        'status'                    => 'Status',
        'LB'                        => 'LB',
        'client_id'                 => 'Client',
        'copy_id'                   => 'Copywriter',
        'country_id'                => 'Country',
        'language_id'               => 'Language',

        // COPY DETAILS
        'copy_nr'                   => 'Copywriter Amount €',
        'copywriter_commision_date' => 'Copy Comm. Date',
        'copywriter_submission_date'=> 'Copy Subm. Date',
        'copywriter_period'         => 'Copy Period (days)',

        // PUBLISHER
        'publisher_currency'        => 'Publisher Currency',
        'publisher_amount'          => 'Publisher Amount',

        // PRICES & COSTS
        'publisher'                 => 'Publisher Agreed €',
        'total_cost'                => 'Total Cost €',
        'menford'                   => 'Menford €',
        'client_copy'               => 'Client Copy €',
        'total_revenues'            => 'Total Revenues €',
        'profit'                    => 'Profit €',

        // CAMPAIGN & LINKS
        'campaign'                  => 'Target Domain',
        'anchor_text'               => 'Anchor Text',
        'target_url'                => 'Target URL',
        'campaign_code'             => 'Campaign Code',

        // PUBLICATION
        'article_sent_to_publisher' => 'Sent to Publisher',
        'publication_date'          => 'Publication Date',
        'expiration_date'           => 'Expiration Date',
        'publisher_period'          => 'Publisher Period (days)',
        'article_url'               => 'Article URL',

        // INVOICING / PAYMENTS
        'method_payment_to_us'      => 'Pay to Us Method',
        'invoice_menford'           => 'Invoice Menford Date',
        'invoice_menford_nr'        => 'Invoice Menford Nr',
        'invoice_company'           => 'Invoice Company',
        'payment_to_us_date'        => 'Pay to Us Date',
        'bill_publisher_name'       => 'Bill Publisher Name',
        'bill_publisher_nr'         => 'Bill Publisher Nr',
        'bill_publisher_date'       => 'Bill Publisher Date',
        'payment_to_publisher_date' => 'Pay to Publisher Date',
        'method_payment_to_publisher'=> 'Pay to Publisher Method',
        'category_ids' => 'Categories',
        // FILES & NOTES
        'files'                     => 'Files',
        'extra_notes'               => 'Extra Notes',
    ];

    /** --------------------------------------------------------
     * 2.  Helper that turns a collection into id=>label pairs
     *     (label can be a callable instead of simple column)
     * ----------------------------------------------------- */
    $toOptions = function ($collection, $label) {
        return $collection->mapWithKeys(function ($row) use ($label) {
            $text = is_callable($label) ? $label($row) : $row->$label;
            return [$row->id => $text];
        });
    };

    /** --------------------------------------------------------
     * 3.  Meta describing the proper input for each field
     * ----------------------------------------------------- */
    $bulkMeta = [
        /* drop-downs ---------------------------------------*/
        'status' => [
            'type'    => 'select',
            'options' => [
                ''                       => '-- None --',
                'article_published'      => 'Article Published',
                'requirements_not_met'   => 'Requirements not met',
                'already_used_by_client' => 'Already used by client',
                'out_of_topic'           => 'Out of topic',
                'high_price'             => 'High Price',
            ],
        ],
        'publisher_currency' => [
            'type'    => 'select',
            'options' => ['EUR'=>'EUR','USD'=>'USD'],
        ],
        'client_id'   => ['type'=>'select','options'=>$toOptions($clients,
                                fn($c)=>$c->first_name.' '.$c->last_name)],
        'copy_id'     => ['type'=>'select','options'=>$toOptions($copies ,'copy_val')],
        'language_id' => ['type'=>'select','options'=>$toOptions($languages,'name')],
        'country_id'  => ['type'=>'select','options'=>$toOptions($countries,'country_name')],

        /* date-pickers ------------------------------------*/
        'copywriter_commision_date'   => ['type'=>'date'],
        'copywriter_submission_date'  => ['type'=>'date'],
        'article_sent_to_publisher'   => ['type'=>'date'],
        'publication_date'            => ['type'=>'date'],
        'expiration_date'             => ['type'=>'date'],
        'invoice_menford'             => ['type'=>'date'],
        'payment_to_us_date'          => ['type'=>'date'],
        'bill_publisher_date'         => ['type'=>'date'],
        'payment_to_publisher_date'   => ['type'=>'date'],

        'category_ids' => ['type'=>'multiselect','options'=> $toOptions($categories,'name')],

        /* long text ---------------------------------------*/
        'extra_notes'                 => ['type'=>'textarea'],

        /* everything else → plain input -------------------*/
    ];
@endphp


{{-- Bulk-edit modal --}}
<div id="bulkEditModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
    <div class="bg-white w-96 rounded shadow p-4 space-y-4">
        <h2 class="font-semibold text-lg text-gray-700">Bulk Edit Storages</h2>

        <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">Field</label>
            <select id="bulkField"
                    class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-cyan-500">
                <option value="recalculate_totals">Apply Auto Calculation</option>

            @foreach($bulkEditable as $f)
                    <option value="{{ $f }}">{{ $bulkLabels[$f] ?? Str::headline($f) }}</option>
                @endforeach

            </select>

            <div id="bulkInputWrapper"></div> {{-- rebuilt by JS --}}
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <button id="bulkCancel"
                    class="bg-gray-400 text-white px-3 py-1 rounded text-xs">Cancel</button>
            <button id="bulkSave"
                    class="bg-amber-600 text-white px-3 py-1 rounded text-xs">Save</button>
        </div>
    </div>
</div>

{{-- expose the meta table to JS --}}
<script>
    window.bulkMeta = @json($bulkMeta);
</script>
