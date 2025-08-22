{{-- resources/views/new_entries/partials/bulk-modal.blade.php --}}
@php
    /* Lookups */
    $countries   = \App\Models\Country ::orderBy('country_name')->pluck('country_name','id');
    $languages   = \App\Models\Language::orderBy('name')        ->pluck('name','id');
    $categories  = \App\Models\Category::orderBy('name')        ->pluck('name','id');

    /* Labels shown to user */
    $bulkLabels = [
        'status'          => 'Status',
        'country_id'      => 'Country',
        'language_id'     => 'Language',
        'linkbuilder'     => 'Link-builder',
        'type_of_website' => 'Type',

        'DR'=>'DR','UR'=>'UR','DA'=>'DA','PA'=>'PA','TF'=>'TF','CF'=>'CF',
        'ZA'=>'ZA','as_metric'=>'AS','seozoom'=>'SEOZoom',
        'semrush_traffic'=>'Semrush Traffic',
        'ahrefs_keyword'=>'Ahrefs Keyword','ahrefs_traffic'=>'Ahrefs Traffic',
        'keyword_vs_traffic'=>'KW / Traffic','TF_vs_CF'=>'TF vs CF',

        'publisher_price'=>'Publisher €','link_insertion_price'=>'Link Insertion €',
        'no_follow_price'=>'No-follow €','special_topic_price'=>'Special-topic €',
        'banner_price'=>'Banner €','sitewide_link_price'=>'Site-wide €',

        'kialvo_evaluation'=>'Kialvo Evaluation €','profit'=>'Profit €',

        'date_publisher_price'=>'Date – Publisher',
        'seo_metrics_date'    =>'Date – SEO metrics',
        'date_kialvo_evaluation' => 'Date – Kialvo',

        'category_ids'=>'Categories',

        'betting'              => 'Betting',
        'trading'              => 'Trading',
        'permanent_link'       => 'Permanent Link',
        'more_than_one_link'   => 'More than one link',
        'copywriting'          => 'Copywriting',
        'no_sponsored_tag'     => 'No Sponsored Tag',
        'social_media_sharing' => 'Social Media Sharing',
        'post_in_homepage'     => 'Post in Homepage',
    ];

    /* Widget hints */
    $bulkMeta = [
        'status' => ['type'=>'select','options'=>[
            ''=>'-- Clear --',
            'never_opened'=>'Never Opened',
            'read_but_never_answered'=>'Read but never answered',
            'waiting_for_first_answer'=>'Waiting for 1st answer',
            'refused_by_us'=>'Refused by us',
            'publisher_refused'=>'Publisher refused',
            'negotiation'=>'Negotiation',
            'active'=>'Active',
        ]],
        'country_id'      => ['type'=>'select','options'=>$countries],
        'language_id'     => ['type'=>'select','options'=>$languages],
        'type_of_website' => ['type'=>'select','options'=>[
            ''=>'-- Clear --','FORUM'=>'Forum','GENERALIST'=>'Generalist',
            'VERTICAL'=>'Vertical','LOCAL'=>'Local']],

        'category_ids' => ['type'=>'multiselect','options'=>$categories],

        'betting'              => ['type'=>'select','options'=>[''=>'-- Clear --',1=>'Yes',0=>'No']],
        'trading'              => ['type'=>'select','options'=>[''=>'-- Clear --',1=>'Yes',0=>'No']],
        'permanent_link'       => ['type'=>'select','options'=>[''=>'-- Clear --',1=>'Yes',0=>'No']],
        'more_than_one_link'   => ['type'=>'select','options'=>[''=>'-- Clear --',1=>'Yes',0=>'No']],
        'copywriting'          => ['type'=>'select','options'=>[''=>'-- Clear --',1=>'Yes',0=>'No']],
        'no_sponsored_tag'     => ['type'=>'select','options'=>[''=>'-- Clear --',1=>'Yes',0=>'No']],
        'social_media_sharing' => ['type'=>'select','options'=>[''=>'-- Clear --',1=>'Yes',0=>'No']],
        'post_in_homepage'     => ['type'=>'select','options'=>[''=>'-- Clear --',1=>'Yes',0=>'No']],
    ];

    $bulkEditable = array_keys($bulkLabels);
@endphp

{{-- Overlay (flex centers the card; compact sizing) --}}
<div id="bulkEditModal" class="fixed inset-0 z-50 bg-black/50 hidden flex items-start justify-center pt-16">
    <div class="bg-white rounded-lg shadow-xl p-3 w-[22rem] max-w-[95vw] text-xs">
        <h2 class="font-semibold text-base mb-2">Bulk edit entries</h2>

        <input type="hidden" id="bulkIds">

        <div class="space-y-2">
            <label class="block font-medium">Field to update</label>

            {{-- master select: auto-calc first + last --}}
            <select id="bulkField" class="border border-gray-300 rounded w-full px-2 py-1">
                <option value="{{ \App\Http\Controllers\NewEntryController::FIELD_RECALC }}" selected>
                    Apply auto-calculations
                </option>

                @foreach($bulkEditable as $f)
                    <option value="{{ $f }}">{{ $bulkLabels[$f] }}</option>
                @endforeach

                <option value="{{ \App\Http\Controllers\NewEntryController::FIELD_RECALC }}">
                    Re-calculate totals
                </option>
            </select>

            {{-- rebuilt by JS when field changes --}}
            <div id="bulkInputWrapper"></div>
        </div>

        <div class="flex justify-end gap-2 pt-3">
            <button id="bulkCancel" class="bg-gray-400 text-white px-2 py-1 rounded">Cancel</button>
            <button id="bulkSave"   class="bg-amber-600 text-white px-2 py-1 rounded">Save</button>
        </div>
    </div>
</div>

{{-- Merge field meta into global bulkMeta used by the page JS --}}
<script>
    (function(add){
        window.bulkMeta = Object.assign({}, window.bulkMeta || {}, add);
    })(@json($bulkMeta));
</script>
