{{--  resources/views/websites/partials/bulk-modal.blade.php  --}}
@php
    /*────────────────────────────── Lists for <select>s ──────────────────────────────*/
    $countries   = \App\Models\Country ::orderBy('country_name')->pluck('country_name','id');
    $languages   = \App\Models\Language::orderBy('name')        ->pluck('name','id');
    $categories  = \App\Models\Category::orderBy('name')        ->pluck('name','id');

    /*────────────────────────────── 1. Labels (what the user sees) ───────────────────*/
    $bulkLabels = [
        // GENERAL
        'status'          => 'Status',
        'country_id'      => 'Country',
        'language_id'     => 'Language',
        'linkbuilder'     => 'Link-builder',
        'type_of_website' => 'Type',

        // SEO
        'DR'=>'DR','UR'=>'UR','DA'=>'DA','PA'=>'PA','TF'=>'TF','CF'=>'CF',
        'ZA'=>'ZA','as_metric'=>'AS','seozoom'=>'SEOZoom',
        'semrush_traffic'=>'Semrush Traffic',
        'ahrefs_keyword'=>'Ahrefs Keyword','ahrefs_traffic'=>'Ahrefs Traffic',
        'keyword_vs_traffic'=>'KW / Traffic','TF_vs_CF'=>'TF vs CF',

        // PRICE
        'publisher_price'=>'Publisher €','link_insertion_price'=>'Link Insertion €',
        'no_follow_price'=>'No-follow €','special_topic_price'=>'Special-topic €',
        'banner_price'=>'Banner €','sitewide_link_price'=>'Site-wide €',

        // KPI
        'kialvo_evaluation'=>'Kialvo Evaluation €','profit'=>'Profit €',

        // DATES (stored as yyyy-mm-dd so a plain <input type=date> is OK)
        'date_publisher_price'=>'Date – Publisher',
        'seo_metrics_date'    =>'Date – SEO metrics',
        'date_kialvo_evaluation' => 'Date – Kialvo',   // ← add this



        // MANY-TO-MANY
        'category_ids'=>'Categories',

        // BOOLEAN FLAGS
        'betting'              => 'Betting',
        'trading'              => 'Trading',
        'permanent_link'       => 'LINK LIFETIME',
        'more_than_one_link'   => 'More than one link',
        'copywriting'          => 'Copywriting',
        'no_sponsored_tag'     => 'No Sponsored Tag',
        'social_media_sharing' => 'Social Media Sharing',
        'post_in_homepage'     => 'Post in Homepage',

    ];

    /*────────────────────────────── 2. Field-meta (widget hints) ─────────────────────*/
    $bulkMeta = [
        /* plain <select>s */
        'status' => ['type'=>'select','options'=>[
            ''=>'-- Clear --','ACTIVE'=>'Active','INACTIVE'=>'Inactive','REJECTED'=>'Rejected']],
        'country_id'      => ['type'=>'select','options'=>$countries],
        'language_id'     => ['type'=>'select','options'=>$languages],
        'type_of_website' => ['type'=>'select','options'=>[
            ''=>'-- Clear --','FORUM'=>'Forum','GENERALIST'=>'Generalist',
            'VERTICAL'=>'Vertical','LOCAL'=>'Local']],

        /* multiselect (Select2) */
        'category_ids' => ['type'=>'multiselect','options'=>$categories],

        /* booleans as select yes/no with clear */
        'betting'              => ['type'=>'select','options'=>[''=>'-- Clear --',1=>'Yes',0=>'No']],
        'trading'              => ['type'=>'select','options'=>[''=>'-- Clear --',1=>'Yes',0=>'No']],
        'permanent_link'       => ['type'=>'select','options'=>[''=>'-- Clear --',1=>'Yes',0=>'No']],
        'more_than_one_link'   => ['type'=>'select','options'=>[''=>'-- Clear --',1=>'Yes',0=>'No']],
        'copywriting'          => ['type'=>'select','options'=>[''=>'-- Clear --',1=>'Yes',0=>'No']],
        'no_sponsored_tag'     => ['type'=>'select','options'=>[''=>'-- Clear --',1=>'Yes',0=>'No']],
        'social_media_sharing' => ['type'=>'select','options'=>[''=>'-- Clear --',1=>'Yes',0=>'No']],
        'post_in_homepage'     => ['type'=>'select','options'=>[''=>'-- Clear --',1=>'Yes',0=>'No']],


        /* everything else defaults to a plain <input type=text> */
    ];

    /* list that feeds the drop-down – real db columns only */
    $bulkEditable = array_keys($bulkLabels);
@endphp


{{--──────────────────────────── Modal overlay ───────────────────────────--}}
<div id="bulkEditModal" class="fixed inset-0 z-50 bg-black/50 hidden">
    <div class="bg-white rounded-lg shadow-xl p-4 w-[26rem] mx-auto mt-24 text-sm">
        <h2 class="font-semibold text-lg mb-3">Bulk edit websites</h2>

        <input type="hidden" id="bulkIds">

        <div class="space-y-3">
            <label class="block font-medium">Field to update</label>

            {{-- ── master <select> ─ first = quick action, last = recalc ───────── --}}
            <select id="bulkField" class="border border-gray-300 rounded w-full p-2">
                {{-- 1️⃣ Apply auto-calculations (pre-selected) --}}
                <option value="{{ \App\Http\Controllers\WebsiteController::FIELD_RECALC }}" selected>
                    Apply auto-calculations
                </option>

                {{-- 2️⃣ Every real column --}}
                @foreach($bulkEditable as $f)
                    <option value="{{ $f }}">{{ $bulkLabels[$f] }}</option>
                @endforeach

                {{-- 3️⃣ Same pseudo-field, shown again at the end --}}
                <option value="{{ \App\Http\Controllers\WebsiteController::FIELD_RECALC }}">
                    Re-calculate totals
                </option>
            </select>

            {{-- rebuilt by JS every time the field changes --}}
            <div id="bulkInputWrapper"></div>
        </div>

        <div class="flex justify-end gap-2 pt-4">
            <button id="bulkCancel" class="bg-gray-400 text-white px-3 py-1 rounded">Cancel</button>
            <button id="bulkSave"   class="bg-amber-600 text-white px-3 py-1 rounded">Save</button>
        </div>
    </div>
</div>

{{-- expose meta to the page-wide JS --}}
<script>
    // ⬇️  this *adds* the partial’s hints to the global table
    (function(add){ window.bulkMeta = Object.assign({}, window.bulkMeta || {}, add); })
    (@json($bulkMeta));
</script>
