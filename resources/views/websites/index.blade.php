@extends('layouts.dashboard')

@section('content')
    <div class="mb-4">
        <h1 class="text-2xl font-bold">Websites (Full CRUD)</h1>
        @if(session('status'))
            <div class="text-green-600 mb-2">{{ session('status') }}</div>
        @endif
        <a href="{{ route('websites.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded">
            Create Website
        </a>

        <a href="#" id="btnExportCsv" class="bg-green-600 text-white px-4 py-2 rounded">
            Export CSV
        </a>
        <a href="#" id="btnExportPdf" class="bg-red-600 text-white px-4 py-2 rounded">
            Export PDF
        </a>
    </div>

    <!-- FILTER FORM -->
    <div class="bg-white p-4 rounded shadow mb-4" id="filterForm">
        <!-- FIRST ROW FILTERS -->
        <div class="grid grid-cols-7 gap-4 mb-4">
            <div>
                <label class="block mb-1">Domain Name</label>
                <input type="text" id="filterDomainName" class="w-full border-gray-300 rounded">
            </div>

            @foreach(['Publisher Price' => 'publisher_price', 'Kialvo' => 'kialvo_evaluation', 'Profit' => 'profit'] as $label => $field)
                <div>
                    <label class="block mb-1">{{ $label }} Min</label>
                    <input type="number" id="filter{{ ucfirst($field) }}Min" class="w-full border-gray-300 rounded text-sm">
                    <label class="block mb-1 mt-1">{{ $label }} Max</label>
                    <input type="number" id="filter{{ ucfirst($field) }}Max" class="w-full border-gray-300 rounded text-sm">
                </div>
            @endforeach

            <div>
                <label class="block mb-1">Language</label>
                <select id="filterLanguage" class="w-full border-gray-300 rounded">
                    <option value="">-- Any --</option>
                    @foreach($languages as $lang)
                        <option value="{{ $lang->id }}">{{ $lang->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block mb-1">Status</label>
                <select id="filterStatus" class="w-full border-gray-300 rounded">
                    <option value="">-- Any --</option>
                    <option value="active">Active</option>
                    <option value="past">Past</option>
                </select>
            </div>
            <div>
                <label class="block mb-1">Type</label>
                <select id="filterWebsiteType" class="w-full border-gray-300 rounded">
                    <option value="">-- Any --</option>
                    <option value="FORUM">Forum</option>
                    <option value="GENERALIST">Generalist</option>
                    <option value="VERTICAL">Vertical</option>
                    <option value="LOCAL">Local</option>
                </select>
            </div>
            <div>
                <label class="block mb-1">Country</label>
                <select id="filterCountry" class="w-full border-gray-300 rounded">
                    <option value="">-- Any --</option>
                    @foreach($countries as $c)
                        <option value="{{ $c->id }}">{{ $c->country_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- SECOND ROW FILTERS -->
        <div class="grid grid-cols-6 gap-4 mb-4">
            @foreach(['DA', 'PA', 'TF', 'CF', 'DR', 'UR', 'ZA', 'AS','TF_VS_CF', 'semrush_traffic', 'ahrefs_keyword', 'ahrefs_traffic', 'keyword_vs_traffic'] as $field)
                <div>
                    <label class="block mb-1">{{ strtoupper(str_replace('_', ' ', $field)) }} Min</label>
                    <input type="number" id="filter{{ ucfirst($field) }}Min" class="w-full border-gray-300 rounded text-sm">
                    <label class="block mb-1 mt-1">{{ strtoupper(str_replace('_', ' ', $field)) }} Max</label>
                    <input type="number" id="filter{{ ucfirst($field) }}Max" class="w-full border-gray-300 rounded text-sm">
                </div>
            @endforeach
        </div>

        <!-- CATEGORIES FILTER ROW -->
        <div class="grid grid-cols-1 gap-4 mb-4">
            <div>
                <label class="block mb-1">Categories</label>
                <select id="filterCategories" class="w-full border-gray-300 rounded" multiple>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- THIRD ROW FILTERS (Checkboxes) -->
        <div class="grid grid-cols-6 gap-4">
            @foreach(['betting','trading','more_than_one_link', 'copywriting', 'no_sponsored_tag', 'social_media_sharing', 'post_in_homepage'] as $checkbox)
                <div>
                    <label class="block mb-1">{{ ucwords(str_replace('_', ' ', $checkbox)) }}</label>
                    <input type="checkbox" id="filter{{ ucfirst($checkbox) }}">
                </div>
            @endforeach

            <!-- Show Deleted CheckBox -->
            <div>
                <label class="block mb-1">Show Deleted</label>
                <input type="checkbox" id="filterShowDeleted">
            </div>
        </div>

        <div class="mt-4">
            <button id="btnSearch" class="px-4 py-2 bg-blue-600 text-white rounded">Search</button>
            <button id="btnClear" class="px-4 py-2  bg-blue-600 text-white rounded">Clear Filters</button>
        </div>
    </div>

    <!-- DataTable -->
    <table id="websitesTable" class="min-w-full bg-white">
        <thead>
        <tr>
            <th>ID</th>
            <th>Domain</th>
            <th>Publisher Price</th>
            <th>Kialvo</th>
            <th>Profit</th>
            <th>Country</th>
            <th>Language</th>
            <th>Contact</th> <!-- We do NOT have a contact filter, but we display contact data here. -->
            <th>Categories</th>
            <th>Status</th>
            <th>Currency</th>
            <th>Date Publisher Price</th>
            <th>Link Insertion Price</th>
            <th>No Follow Price</th>
            <th>Special Topic Price</th>
            <th>Linkbuilder</th>
            <th>Automatic Evaluation</th>
            <th>Date Kialvo Evaluation</th>
            <th>Type of Website</th>
            <th>DA</th>
            <th>PA</th>
            <th>TF</th>
            <th>CF</th>
            <th>DR</th>
            <th>UR</th>
            <th>ZA</th>
            <th>AS</th>
            <th>SEO Zoom</th>
            <th>TF vs CF</th>
            <th>Semrush Traffic</th>
            <th>Ahrefs Keyword</th>
            <th>Ahrefs Traffic</th>
            <th>Keyword vs Traffic</th>
            <th>SEO Metrics Date</th>
            <th>Betting</th>
            <th>Trading</th>
            <th>More than 1 link</th>
            <th>Copywriting</th>
            <th>No Sponsored Tag</th>
            <th>Social Media Sharing</th>
            <th>Post in Homepage</th>
            <th>Date Added</th>
            <th>Extra Notes</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody></tbody>
    </table>

    @include('partials.contact-modal')
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {

            // Initialize select2 for categories
            $('#filterCategories').select2({
                placeholder: 'Select Categories',
                allowClear: true
            });

            let table = $('#websitesTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('websites.data') }}",
                    type: "POST",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: function(d) {
                        d.domain_name = $('#filterDomainName').val();
                        d.type_of_website = $('#filterWebsiteType').val();
                        d.publisher_price_min = $('#filterPublisher_priceMin').val();
                        d.publisher_price_max = $('#filterPublisher_priceMax').val();
                        d.kialvo_min = $('#filterKialvo_evaluationMin').val();
                        d.kialvo_max = $('#filterKialvo_evaluationMax').val();
                        d.profit_min = $('#filterProfitMin').val();
                        d.profit_max = $('#filterProfitMax').val();
                        d.status = $('#filterStatus').val();
                        d.category_ids = $('#filterCategories').val();

                        // DA, PA, TF, CF, DR, UR, ZA
                        d.DA_min = $('#filterDAMin').val();
                        d.DA_max = $('#filterDAMax').val();
                        d.PA_min = $('#filterPAMin').val();
                        d.PA_max = $('#filterPAMax').val();
                        d.TF_min = $('#filterTFMin').val();
                        d.TF_max = $('#filterTFMax').val();
                        d.CF_min = $('#filterCFMin').val();
                        d.CF_max = $('#filterCFMax').val();
                        d.DR_min = $('#filterDRMin').val();
                        d.DR_max = $('#filterDRMax').val();
                        d.UR_min = $('#filterURMin').val();
                        d.UR_max = $('#filterURMax').val();
                        d.ZA_min = $('#filterZAMin').val();
                        d.ZA_max = $('#filterZAMax').val();

                        // AS_metric (we're calling it SR in the controller, but using #filterASMin)
                        d.SR_min = $('#filterASMin').val();
                        d.SR_max = $('#filterASMax').val();

                        // TF_VS_CF
                        d.TF_VS_CF_min = $('#filterTF_vS_cfMin').val();
                        d.TF_VS_CF_max = $('#filterTF_vS_cfMax').val();

                        // semrush_traffic
                        d.semrush_traffic_min = $('#filterSemrush_trafficMin').val();
                        d.semrush_traffic_max = $('#filterSemrush_trafficMax').val();

                        // ahrefs_keyword
                        d.ahrefs_keyword_min = $('#filterAhrefs_keywordMin').val();
                        d.ahrefs_keyword_max = $('#filterAhrefs_keywordMax').val();

                        // ahrefs_traffic
                        d.ahrefs_traffic_min = $('#filterAhrefs_trafficMin').val();
                        d.ahrefs_traffic_max = $('#filterAhrefs_trafficMax').val();

                        // keyword_vs_traffic
                        d.keyword_vs_traffic_min = $('#filterKeyword_vs_trafficMin').val();
                        d.keyword_vs_traffic_max = $('#filterKeyword_vs_trafficMax').val();

                        d.country_id = $('#filterCountry').val();
                        d.language_id = $('#filterLanguage').val();

                        // Checkbox filters
                        d.betting = $('#filterBetting').is(':checked');
                        d.trading = $('#filterTrading').is(':checked');
                        d.more_than_one_link = $('#filterMore_than_one_link').is(':checked');
                        d.copywriting = $('#filterCopywriting').is(':checked');
                        d.no_sponsored_tag = $('#filterNo_sponsored_tag').is(':checked');
                        d.social_media_sharing = $('#filterSocial_media_sharing').is(':checked');
                        d.post_in_homepage = $('#filterPost_in_homepage').is(':checked');

                        // "Show Deleted" filter
                        d.show_deleted = $('#filterShowDeleted').is(':checked');
                    }
                },
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'domain_name', name: 'domain_name' },
                    { data: 'publisher_price', name: 'publisher_price' },
                    { data: 'kialvo_evaluation', name: 'kialvo_evaluation' },
                    { data: 'profit', name: 'profit' },
                    { data: 'country_name', name: 'country.country_name' },
                    { data: 'language_name', name: 'language.name' },
                    {
                        data: 'contact_name',
                        name: 'contact.name',
                        render: function (data, type, row) {
                            // If no contact_id, just return blank
                            if (!row.contact_id) {
                                return "No Contact";
                            }
                            // Otherwise, clickable
                            return `
                                <a href="#"
                                   class="contact-link text-blue-600 underline"
                                   data-contact-id="${row.contact_id}">
                                   ${data}
                                </a>`;
                        }
                    },
                    { data: 'categories_list', name: 'categories_list' },
                    { data: 'status', name: 'status' },
                    { data: 'currency_code', name: 'currency_code' },
                    { data: 'date_publisher_price', name: 'date_publisher_price' },
                    { data: 'link_insertion_price', name: 'link_insertion_price' },
                    { data: 'no_follow_price', name: 'no_follow_price' },
                    { data: 'special_topic_price', name: 'special_topic_price' },
                    { data: 'linkbuilder', name: 'linkbuilder' },
                    { data: 'automatic_evaluation', name: 'automatic_evaluation' },
                    { data: 'date_kialvo_evaluation', name: 'date_kialvo_evaluation' },
                    { data: 'type_of_website', name: 'type_of_website' },
                    { data: 'DA', name: 'DA' },
                    { data: 'PA', name: 'PA' },
                    { data: 'TF', name: 'TF' },
                    { data: 'CF', name: 'CF' },
                    { data: 'DR', name: 'DR' },
                    { data: 'UR', name: 'UR' },
                    { data: 'ZA', name: 'ZA' },
                    { data: 'as_metric', name: 'as_metric', title: 'AS' },
                    { data: 'seozoom', name: 'seozoom' },
                    { data: 'TF_vs_CF', name: 'TF_vs_CF' },
                    { data: 'semrush_traffic', name: 'semrush_traffic' },
                    { data: 'ahrefs_keyword', name: 'ahrefs_keyword' },
                    { data: 'ahrefs_traffic', name: 'ahrefs_traffic' },
                    { data: 'keyword_vs_traffic', name: 'keyword_vs_traffic' },
                    { data: 'seo_metrics_date', name: 'seo_metrics_date' },
                    { data: 'betting', name: 'betting' },
                    { data: 'trading', name: 'trading' },
                    { data: 'more_than_one_link', name: 'more_than_one_link' },
                    { data: 'copywriting', name: 'copywriting' },
                    { data: 'no_sponsored_tag', name: 'no_sponsored_tag' },
                    { data: 'social_media_sharing', name: 'social_media_sharing' },
                    { data: 'post_in_homepage', name: 'post_in_homepage' },
                    { data: 'created_at', name: 'date_added' },
                    { data: 'extra_notes', name: 'extra_notes' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ],
                order: [[0, 'desc']],
            });

            // Search button triggers reload
            $('#btnSearch').click(function(){
                table.ajax.reload();
            });

            // Clear all filters and reload
            $('#btnClear').click(function() {
                $('#filterForm input[type="text"]').val('');
                $('#filterForm input[type="number"]').val('');
                $('#filterForm select').val('');
                $('#filterForm input[type="checkbox"]').prop('checked', false);
                $('#filterCategories').val(null).trigger('change');
                table.ajax.reload();
            });

            // Export CSV
            $('#btnExportCsv').click(function(e){
                e.preventDefault();
                // Build query params from the same filters
                let params = $.param({
                    domain_name: $('#filterDomainName').val(),
                    type_of_website: $('#filterWebsiteType').val(),
                    publisher_price_min: $('#filterPublisher_priceMin').val(),
                    publisher_price_max: $('#filterPublisher_priceMax').val(),
                    kialvo_min: $('#filterKialvo_evaluationMin').val(),
                    kialvo_max: $('#filterKialvo_evaluationMax').val(),
                    profit_min: $('#filterProfitMin').val(),
                    profit_max: $('#filterProfitMax').val(),
                    status: $('#filterStatus').val(),
                    category_ids: $('#filterCategories').val(),
                    DA_min: $('#filterDAMin').val(),
                    DA_max: $('#filterDAMax').val(),
                    ZA_min: $('#filterZAMin').val(),
                    ZA_max: $('#filterZAMax').val(),
                    PA_min: $('#filterPAMin').val(),
                    PA_max: $('#filterPAMax').val(),
                    SR_min: $('#filterASMin').val(),
                    SR_max: $('#filterASMax').val(),
                    TF_min: $('#filterTFMin').val(),
                    TF_max: $('#filterTFMax').val(),
                    TF_VS_CF_min: $('#filterTF_vS_cfMin').val(),
                    TF_VS_CF_max: $('#filterTF_vS_cfMax').val(),
                    semrush_traffic_min: $('#filterSemrush_trafficMin').val(),
                    semrush_traffic_max: $('#filterSemrush_trafficMax').val(),
                    CF_min: $('#filterCFMin').val(),
                    CF_max: $('#filterCFMax').val(),
                    ahrefs_keyword_min: $('#filterAhrefs_keywordMin').val(),
                    ahrefs_keyword_max: $('#filterAhrefs_keywordMax').val(),
                    DR_min: $('#filterDRMin').val(),
                    DR_max: $('#filterDRMax').val(),
                    ahrefs_traffic_min: $('#filterAhrefs_trafficMin').val(),
                    ahrefs_traffic_max: $('#filterAhrefs_trafficMax').val(),
                    UR_min: $('#filterURMin').val(),
                    UR_max: $('#filterURMax').val(),
                    keyword_vs_traffic_min: $('#filterKeyword_vs_trafficMin').val(),
                    keyword_vs_traffic_max: $('#filterKeyword_vs_trafficMax').val(),
                    country_id: $('#filterCountry').val(),
                    language_id: $('#filterLanguage').val(),
                    betting: $('#filterBetting').is(':checked') ? 1 : 0,
                    trading: $('#filterTrading').is(':checked') ? 1 : 0,
                    more_than_one_link: $('#filterMore_than_one_link').is(':checked') ? 1 : 0,
                    copywriting: $('#filterCopywriting').is(':checked') ? 1 : 0,
                    no_sponsored_tag: $('#filterNo_sponsored_tag').is(':checked') ? 1 : 0,
                    social_media_sharing: $('#filterSocial_media_sharing').is(':checked') ? 1 : 0,
                    post_in_homepage: $('#filterPost_in_homepage').is(':checked') ? 1 : 0,
                    show_deleted: $('#filterShowDeleted').is(':checked') ? 1 : 0
                });

                window.location = "{{ route('websites.export.csv') }}?" + params;
            });

            // Export PDF
            $('#btnExportPdf').click(function(e){
                e.preventDefault();
                let params = $.param({
                    // same as CSV
                    domain_name: $('#filterDomainName').val(),
                    type_of_website: $('#filterWebsiteType').val(),
                    publisher_price_min: $('#filterPublisher_priceMin').val(),
                    publisher_price_max: $('#filterPublisher_priceMax').val(),
                    kialvo_min: $('#filterKialvo_evaluationMin').val(),
                    kialvo_max: $('#filterKialvo_evaluationMax').val(),
                    profit_min: $('#filterProfitMin').val(),
                    profit_max: $('#filterProfitMax').val(),
                    status: $('#filterStatus').val(),
                    category_ids: $('#filterCategories').val(),
                    DA_min: $('#filterDAMin').val(),
                    DA_max: $('#filterDAMax').val(),
                    ZA_min: $('#filterZAMin').val(),
                    ZA_max: $('#filterZAMax').val(),
                    PA_min: $('#filterPAMin').val(),
                    PA_max: $('#filterPAMax').val(),
                    SR_min: $('#filterASMin').val(),
                    SR_max: $('#filterASMax').val(),
                    TF_min: $('#filterTFMin').val(),
                    TF_max: $('#filterTFMax').val(),
                    TF_VS_CF_min: $('#filterTF_vS_cfMin').val(),
                    TF_VS_CF_max: $('#filterTF_vS_cfMax').val(),
                    semrush_traffic_min: $('#filterSemrush_trafficMin').val(),
                    semrush_traffic_max: $('#filterSemrush_trafficMax').val(),
                    CF_min: $('#filterCFMin').val(),
                    CF_max: $('#filterCFMax').val(),
                    ahrefs_keyword_min: $('#filterAhrefs_keywordMin').val(),
                    ahrefs_keyword_max: $('#filterAhrefs_keywordMax').val(),
                    DR_min: $('#filterDRMin').val(),
                    DR_max: $('#filterDRMax').val(),
                    ahrefs_traffic_min: $('#filterAhrefs_trafficMin').val(),
                    ahrefs_traffic_max: $('#filterAhrefs_trafficMax').val(),
                    UR_min: $('#filterURMin').val(),
                    UR_max: $('#filterURMax').val(),
                    keyword_vs_traffic_min: $('#filterKeyword_vs_trafficMin').val(),
                    keyword_vs_traffic_max: $('#filterKeyword_vs_trafficMax').val(),
                    country_id: $('#filterCountry').val(),
                    language_id: $('#filterLanguage').val(),
                    betting: $('#filterBetting').is(':checked') ? 1 : 0,
                    trading: $('#filterTrading').is(':checked') ? 1 : 0,
                    more_than_one_link: $('#filterMore_than_one_link').is(':checked') ? 1 : 0,
                    copywriting: $('#filterCopywriting').is(':checked') ? 1 : 0,
                    no_sponsored_tag: $('#filterNo_sponsored_tag').is(':checked') ? 1 : 0,
                    social_media_sharing: $('#filterSocial_media_sharing').is(':checked') ? 1 : 0,
                    post_in_homepage: $('#filterPost_in_homepage').is(':checked') ? 1 : 0,
                    show_deleted: $('#filterShowDeleted').is(':checked') ? 1 : 0
                });
                window.location = "{{ route('websites.export.pdf') }}?" + params;
            });

            // Contact modal stuff
            $(document).on('click', '.contact-link', function(e) {
                e.preventDefault();
                let contactId = $(this).data('contact-id');
                $.ajax({
                    url: "{{ route('contacts.showAjax', '') }}/" + contactId,
                    type: 'GET',
                    success: function(response) {
                        if (response.status === 'success') {
                            let contact = response.data;
                            // Fill modal fields
                            $('#modalContactName').text(contact.name ?? '');
                            $('#modalContactEmail').text(contact.email ?? '');
                            $('#modalContactPhone').text(contact.phone ?? '');
                            $('#modalContactFacebook').text(contact.facebook ?? '');
                            $('#modalContactInstagram').text(contact.instagram ?? '');
                            // Show the modal
                            $('#contactModal').removeClass('hidden');
                        } else {
                            alert('Could not load contact info.');
                        }
                    },
                    error: function() {
                        alert('Error fetching contact info.');
                    }
                });
            });

            $('#closeContactModal, #closeContactModalBottom').on('click', function() {
                $('#contactModal').addClass('hidden');
            });
        });
    </script>
@endpush
