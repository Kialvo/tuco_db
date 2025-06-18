@extends('layouts.dashboard')

@section('content')
    <h1 class="text-lg font-bold text-gray-700 py-6">Websites</h1>
    <div class="px-6 py-4 bg-gray-50 min-h-screen text-xs">
        <!-- Header: Title + Buttons -->

        <div class="flex flex-col gap-3 mb-4">


            <div class="space-x-2">
                <!-- "Show/Hide Filters" button -->
                <button  id="toggleFiltersBtn"
                         class="bg-gray-300 text-gray-700 px-4 py-2 rounded shadow text-xs hover:bg-gray-400
                   focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-300">
                    Hide Filters
                </button>

                <a href="{{ route('websites.create') }}"
                   class="bg-cyan-600 text-white px-4 py-2 rounded shadow hover:bg-cyan-700
                      focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 text-xs">
                    Create Website
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

        <!-- FILTERS WRAPPER -->

        <div id="filterForm"
             class="bg-white border border-gray-200 rounded shadow p-2 mb-8
            inline-block max-w-[2000px]">
            <!-- ROW 1 -->
            <div class="flex flex-wrap gap-2 mb-2">
                <!-- Domain -->
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Domain</label>
                    <input id="filterDomainName" type="text"
                           class="border border-gray-300 rounded px-2 py-2 w-30
                              focus:ring-cyan-500 focus:border-cyan-500" placeholder="Type Domain">
                </div>

                <!-- Type -->
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Type</label>
                    <select id="filterWebsiteType"
                            class="border border-gray-300 rounded px-2 py-2 w-28
                               focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="">-- Any --</option>
                        <option value="FORUM">Forum</option>
                        <option value="GENERALIST">Generalist</option>
                        <option value="VERTICAL">Vertical</option>
                        <option value="LOCAL">Local</option>
                    </select>
                </div>

                <!-- Language -->
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Language</label>
                    <select id="filterLanguage"
                            class="border border-gray-300 rounded px-2 py-2 w-28
                               focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="">-- Any --</option>
                        @foreach($languages as $lang)
                            <option value="{{ $lang->id }}">{{ $lang->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status -->
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Status</label>
                    <select id="filterStatus"
                            class="border border-gray-300 rounded px-2 py-2 w-28
                               focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="">-- Any --</option>
                        <option value="active">Active</option>
                        <option value="past">Past</option>
                    </select>
                </div>

                <!-- Country -->
                <!-- Include Countries -->
                <div class="flex flex-col">
                    <label for="filterCountriesInclude" class="text-gray-700 font-medium">Include Countries</label>
                    <select id="filterCountriesInclude" multiple
                            class="border border-gray-300 rounded px-2 py-2 w-48
                   focus:ring-cyan-500 focus:border-cyan-500">
                        @foreach($countries as $c)
                            <option value="{{ $c->id }}">{{ $c->country_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Exclude Countries -->
                <div class="flex flex-col">
                    <label for="filterCountriesExclude" class="text-gray-700 font-medium">Exclude Countries</label>
                    <select id="filterCountriesExclude" multiple
                            class="border border-gray-300 rounded px-2 py-2 w-48
                   focus:ring-cyan-500 focus:border-cyan-500">
                        @foreach($countries as $c)
                            <option value="{{ $c->id }}">{{ $c->country_name }}</option>
                        @endforeach
                    </select>
                </div>

            </div>

            <!-- ROW 2: Publisher, Kialvo, Profit -->
            <div class="flex flex-wrap gap-2 mb-2">
                <!-- Publisher Price Min/Max -->
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Publisher Min/Max</label>
                    <div class="flex gap-1">
                        <input type="number" id="filterPublisher_priceMin"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Min">
                        <input type="number" id="filterPublisher_priceMax"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Max">
                    </div>
                </div>

                <!-- Kialvo Min/Max -->
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Kialvo Min/Max</label>
                    <div class="flex gap-1">
                        <input type="number" id="filterKialvo_evaluationMin"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Min">
                        <input type="number" id="filterKialvo_evaluationMax"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Max">
                    </div>
                </div>

                <!-- Profit Min/Max -->
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Profit Min/Max</label>
                    <div class="flex gap-1">
                        <input type="number" id="filterProfitMin"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Min">
                        <input type="number" id="filterProfitMax"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Max">
                    </div>
                </div>
            </div>

            <!-- ROW 3: DA/PA, TF/CF, DR/UR, etc. -->
            <div class="flex flex-wrap gap-2 mb-2">
                <!-- DA Min/Max -->
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">DA Min/Max</label>
                    <div class="flex gap-1">
                        <input type="number" id="filterDAMin"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Min">
                        <input type="number" id="filterDAMax"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Max">
                    </div>
                </div>
                <!-- PA Min/Max -->
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">PA Min/Max</label>
                    <div class="flex gap-1">
                        <input type="number" id="filterPAMin"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Min">
                        <input type="number" id="filterPAMax"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Max">
                    </div>
                </div>
                <!-- TF Min/Max -->
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">TF Min/Max</label>
                    <div class="flex gap-1">
                        <input type="number" id="filterTFMin"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Min">
                        <input type="number" id="filterTFMax"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Max">
                    </div>
                </div>
                <!-- CF Min/Max -->
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">CF Min/Max</label>
                    <div class="flex gap-1">
                        <input type="number" id="filterCFMin"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Min">
                        <input type="number" id="filterCFMax"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Max">
                    </div>
                </div>
                <!-- DR Min/Max -->
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">DR Min/Max</label>
                    <div class="flex gap-1">
                        <input type="number" id="filterDRMin"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Min">
                        <input type="number" id="filterDRMax"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Max">
                    </div>
                </div>
                <!-- UR Min/Max -->
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">UR Min/Max</label>
                    <div class="flex gap-1">
                        <input type="number" id="filterURMin"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Min">
                        <input type="number" id="filterURMax"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Max">
                    </div>
                </div>
                <!-- ZA Min/Max -->
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">ZA Min/Max</label>
                    <div class="flex gap-1">
                        <input type="number" id="filterZAMin"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Min">
                        <input type="number" id="filterZAMax"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Max">
                    </div>
                </div>
                <!-- AS Min/Max -->
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">AS Min/Max</label>
                    <div class="flex gap-1">
                        <input type="number" id="filterASMin"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Min">
                        <input type="number" id="filterASMax"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Max">
                    </div>
                </div>
                <!-- TF vs CF Min/Max -->
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">TF vs CF</label>
                    <div class="flex gap-1">
                        <input type="number" id="filterTF_vS_cfMin"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Min">
                        <input type="number" id="filterTF_vS_cfMax"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Max">
                    </div>
                </div>
                <!-- Semrush Traffic Min/Max -->
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Semrush Traffic</label>
                    <div class="flex gap-1">
                        <input type="number" id="filterSemrush_trafficMin"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Min">
                        <input type="number" id="filterSemrush_trafficMax"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Max">
                    </div>
                </div>
                <!-- Ahrefs KW Min/Max -->
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Ahrefs KW</label>
                    <div class="flex gap-1">
                        <input type="number" id="filterAhrefs_keywordMin"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Min">
                        <input type="number" id="filterAhrefs_keywordMax"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Max">
                    </div>
                </div>
                <!-- Ahrefs Traffic Min/Max -->
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">Ahrefs Traffic</label>
                    <div class="flex gap-1">
                        <input type="number" id="filterAhrefs_trafficMin"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Min">
                        <input type="number" id="filterAhrefs_trafficMax"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Max">
                    </div>
                </div>
                <!-- KW vs Traffic Min/Max -->
                <div class="flex flex-col">
                    <label class="text-gray-700 font-medium">KW vs Traffic</label>
                    <div class="flex gap-1">
                        <input type="number" id="filterKeyword_vs_trafficMin"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Min">
                        <input type="number" id="filterKeyword_vs_trafficMax"
                               class="border border-gray-300 rounded w-16 px-2 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500" placeholder="Max">
                    </div>
                </div>
            </div><!-- END ROW 3 -->

            <!-- ROW 4: Categories -->
            <div class="mb-2 flex items-center">
                <label class="text-gray-700 font-medium mr-2">Categories</label>
                <select id="filterCategories" multiple
                        class="border border-gray-300 rounded px-2 py-2 text-xs w-48
               max-h-16 overflow-y-auto focus:ring-cyan-500 focus:border-cyan-500">

                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- ROW 5: Toggles in one row -->
            <div class="flex flex-wrap items-center gap-3 mb-2">
                @foreach(['betting','trading','permanent_link','more_than_one_link','copywriting','no_sponsored_tag','social_media_sharing','post_in_homepage'] as $chk)
                    <div class="flex items-center space-x-1">
                        <span class="text-gray-700">{{ str_replace('_',' ', $chk) }}</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="filter{{ ucfirst($chk) }}" class="sr-only peer">
                            <div class="w-10 h-6 bg-gray-200 rounded-full peer-checked:bg-cyan-600
                                after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                after:bg-white after:border-gray-300 after:border
                                after:rounded-full after:h-4 after:w-4 after:transition-all
                                peer-checked:after:translate-x-full peer-focus:outline-none
                                peer-focus:ring-1 peer-focus:ring-cyan-500 peer-checked:after:border-white">
                            </div>
                        </label>
                    </div>
                @endforeach

                    {{-- Orphan domains --}}
                    <div class="flex items-center space-x-1">
                        <span class="text-gray-700">no contact</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="filterNoContact" class="sr-only peer">
                            <div class="w-10 h-6 bg-gray-200 rounded-full peer-checked:bg-cyan-600
            after:content-[''] after:absolute after:top-[2px] after:left-[2px]
            after:bg-white after:border-gray-300 after:border after:rounded-full
            after:h-4 after:w-4 after:transition-all
            peer-checked:after:translate-x-full peer-focus:outline-none
            peer-focus:ring-1 peer-focus:ring-cyan-500 peer-checked:after:border-white">
                            </div>
                        </label>
                    </div>
            </div>



            <!-- ROW 6: Buttons -->
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
        </div><!-- END FILTER FORM -->

        <!-- Show Deleted Toggle -->
        <div class="flex items-center space-x-2 mb-4">
            <!-- Enlarge text to "text-lg", keep "font-medium", etc. -->
            <label for="filterShowDeleted" class="text-lg font-medium text-gray-700">Show Deleted</label>

            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" id="filterShowDeleted" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 rounded-full
                    peer dark:bg-gray-700 peer-checked:bg-cyan-600
                    peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-cyan-500
                    after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                    after:bg-white after:border-gray-300 after:border
                    after:rounded-full after:h-5 after:w-5
                    after:transition-all peer-checked:after:translate-x-full
                    peer-checked:after:border-white">
                </div>
            </label>
        </div>

        <!-- TABLE WRAPPER for horizontal scrolling if needed -->
        <div class="bg-white border border-gray-200 rounded shadow p-2
            overflow-x-auto
            max-w-[1400px]">

            <table id="websitesTable" class="text-xs text-gray-700 w-full min-w-[1500px]">
                <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-[12px] uppercase text-gray-500 tracking-wider">
                    <th class="whitespace-nowrap px-4 py-2">ID</th>
                    <th class="whitespace-nowrap px-4 py-2">Domain</th>
                    <th class="whitespace-nowrap px-4 py-2">Extra Notes</th>
                    <th class="whitespace-nowrap px-4 py-2">Status</th>
                    <th class="whitespace-nowrap px-4 py-2">Country</th>
                    <th class="whitespace-nowrap px-4 py-2">Language</th>
                    <th class="whitespace-nowrap px-4 py-2">Contact</th>
                    <th class="whitespace-nowrap px-4 py-2">Currency</th>
                    <th class="whitespace-nowrap px-4 py-2">Publisher Price</th>
                    <th class="whitespace-nowrap px-4 py-2">No Follow Price</th>
                    <th class="whitespace-nowrap px-4 py-2">Special Topic Price</th>
                    <th class="whitespace-nowrap px-4 py-2">Link Insertion Price</th>
                    <th class="whitespace-nowrap px-4 py-2">Kialvo</th>
                    <strong><th class="whitespace-nowrap px-4 py-2">Profit</th></strong>
                    <th class="whitespace-nowrap px-4 py-2">Date Publisher Price</th>
                    <th class="whitespace-nowrap px-4 py-2">Linkbuilder</th>
                    <th class="whitespace-nowrap px-4 py-2">Type of Website</th>
                    <th class="whitespace-nowrap px-4 py-2">Categories</th>
                    <th class="whitespace-nowrap px-4 py-2">DA</th>
                    <th class="whitespace-nowrap px-4 py-2">PA</th>
                    <th class="whitespace-nowrap px-4 py-2">TF</th>
                    <th class="whitespace-nowrap px-4 py-2">CF</th>
                    <th class="whitespace-nowrap px-4 py-2">DR</th>
                    <th class="whitespace-nowrap px-4 py-2">UR</th>
                    <th class="whitespace-nowrap px-4 py-2">ZA</th>
                    <th class="whitespace-nowrap px-4 py-2">AS</th>
                    <th class="whitespace-nowrap px-4 py-2">SEO Zoom</th>
                    <th class="whitespace-nowrap px-4 py-2">TF vs CF</th>
                    <th class="whitespace-nowrap px-4 py-2">Semrush Traffic</th>
                    <th class="whitespace-nowrap px-4 py-2">Ahrefs Keyword</th>
                    <th class="whitespace-nowrap px-4 py-2">Ahrefs Traffic</th>
                    <th class="whitespace-nowrap px-4 py-2">Keyword vs Traffic</th>
                    <th class="whitespace-nowrap px-4 py-2">SEO Metrics Date</th>
                    <th class="whitespace-nowrap px-4 py-2">Betting</th>
                    <th class="whitespace-nowrap px-4 py-2">Trading</th>
                    <th class="whitespace-nowrap px-4 py-2">Permanent Link</th>
                    <th class="whitespace-nowrap px-4 py-2">More than 1 link</th>
                    <th class="whitespace-nowrap px-4 py-2">Copywriting</th>
                    <th class="whitespace-nowrap px-4 py-2">Sponsored Tag</th>
                    <th class="whitespace-nowrap px-4 py-2">Social Media Sharing</th>
                    <th class="whitespace-nowrap px-4 py-2">Post in Homepage</th>
                    <th class="whitespace-nowrap px-4 py-2">Date Added</th>

                    <th class="whitespace-nowrap px-4 py-2">Action</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div><!-- END TABLE WRAPPER -->
    </div>

    @include('websites.partials.contact-modal')
    @include('websites.partials.note-modal')
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize select2 with smaller text
            $('#filterCategories').select2({
                placeholder: 'Select Categories',
                allowClear: true,
                closeOnSelect: false,
                width: 'resolve',
                dropdownAutoWidth: true,
                containerCssClass: 'text-xs',
                dropdownCssClass: 'text-xs'
            });




            $('#filterCountriesInclude').select2({
                placeholder: 'Select Countries to Include',
                allowClear: true,
                width: '10em', // Or '100%' if you prefer
            });

            $('#filterCountriesExclude').select2({
                placeholder: 'Select Countries to Exclude',
                allowClear: true,
                width: '10em',
            });
            // Initialize the DataTable
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
                        // Gather filter values
                        d.domain_name = $('#filterDomainName').val();
                        d.type_of_website = $('#filterWebsiteType').val();
                        d.language_id = $('#filterLanguage').val();
                        d.status = $('#filterStatus').val();
                        d.no_contact = $('#filterNoContact').is(':checked');

                        d.country_ids_include = $('#filterCountriesInclude').val(); // Array
                        d.country_ids_exclude = $('#filterCountriesExclude').val(); // Array

                        d.publisher_price_min = $('#filterPublisher_priceMin').val();
                        d.publisher_price_max = $('#filterPublisher_priceMax').val();
                        d.kialvo_min = $('#filterKialvo_evaluationMin').val();
                        d.kialvo_max = $('#filterKialvo_evaluationMax').val();
                        d.profit_min = $('#filterProfitMin').val();
                        d.profit_max = $('#filterProfitMax').val();
                        d.category_ids = $('#filterCategories').val();

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
                        d.SR_min = $('#filterASMin').val();
                        d.SR_max = $('#filterASMax').val();
                        d.TF_VS_CF_min = $('#filterTF_vS_cfMin').val();
                        d.TF_VS_CF_max = $('#filterTF_vS_cfMax').val();
                        d.semrush_traffic_min = $('#filterSemrush_trafficMin').val();
                        d.semrush_traffic_max = $('#filterSemrush_trafficMax').val();
                        d.ahrefs_keyword_min = $('#filterAhrefs_keywordMin').val();
                        d.ahrefs_keyword_max = $('#filterAhrefs_keywordMax').val();
                        d.ahrefs_traffic_min = $('#filterAhrefs_trafficMin').val();
                        d.ahrefs_traffic_max = $('#filterAhrefs_trafficMax').val();
                        d.keyword_vs_traffic_min = $('#filterKeyword_vs_trafficMin').val();
                        d.keyword_vs_traffic_max = $('#filterKeyword_vs_trafficMax').val();

                        d.betting = $('#filterBetting').is(':checked');
                        d.trading = $('#filterTrading').is(':checked');
                        d.permanent_link = $('#filterPermanent_link').is(':checked');
                        d.more_than_one_link = $('#filterMore_than_one_link').is(':checked');
                        d.copywriting = $('#filterCopywriting').is(':checked');
                        d.no_sponsored_tag = $('#filterNo_sponsored_tag').is(':checked');
                        d.social_media_sharing = $('#filterSocial_media_sharing').is(':checked');
                        d.post_in_homepage = $('#filterPost_in_homepage').is(':checked');
                        d.show_deleted = $('#filterShowDeleted').is(':checked');

                    }
                },
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'domain_name', name: 'domain_name' },
                    {
                        data: 'extra_notes',
                        name: 'extra_notes',
                        className: 'text-center',
                        render: function (data) {
                            if (!data) return '';
                            // escape any "<" & ">" so the note can safely live in a data-attribute
                            const safe = $('<div>').text(data).html();
                            return `
            <a href="#" class="note-link text-cyan-700" data-note="${safe}">
                <i class="fas fa-comment-dots"></i>
            </a>`;
                        }
                    },
                    { data: 'status', name: 'status', className: 'text-center', },
                    { data: 'country_name', name: 'country.country_name', className: 'text-center', },
                    { data: 'language_name', name: 'language.name',  className: 'text-center', },
                    {
                        data: 'contact_name',
                        name: 'contact.name',
                        render: function(data, type, row) {
                            if (!row.contact_id) return "No Contact";
                            return `
                        <a href="#"
                           class="contact-link text-blue-600 underline"
                           data-contact-id="${row.contact_id}">
                            ${data}
                        </a>`;
                        }
                    },
                    { data: 'currency_code', name: 'currency_code', className: 'text-center', },
                    {
                        data: 'publisher_price',
                        name: 'publisher_price',
                        className: 'text-center',
                        render: function (data, type, row) {
                            if (data === null || data === undefined) {
                                return '';
                            }
                            // Wrap the numeric value in <strong>
                            return '<strong> € ' + data + '</strong>';
                        }
                    },
                    {
                        data: 'no_follow_price',
                        name: 'no_follow_price',
                        className: 'text-center',
                        render: function (data, type, row) {
                            if (data === null || data === undefined) {
                                return '';
                            }
                            return '<strong> € ' + data + '</strong>';
                        }
                    },
                    {
                        data: 'special_topic_price',
                        name: 'special_topic_price',
                        className: 'text-center',
                        render: function (data, type, row) {
                            if (data === null || data === undefined) {
                                return '';
                            }
                            return '<strong> € ' + data + '</strong>';
                        }
                    },
                    {
                        data: 'link_insertion_price',
                        name: 'link_insertion_price',
                        className: 'text-center',
                        render: function (data, type, row) {
                            if (data === null || data === undefined) {
                                return '';
                            }
                            return '<strong> € ' + data + '</strong>';
                        }
                    },
                    {
                        data: 'kialvo_evaluation',
                        name: 'kialvo_evaluation',
                        className: 'text-center',
                        render: function (data, type, row) {
                            if (data === null || data === undefined) {
                                return '';
                            }
                            return '<strong> € ' + data + '</strong>';
                        }
                    },
                    {
                        data: 'profit',
                        name: 'profit',
                        className: 'text-center',
                        render: function (data, type, row) {
                            if (data === null || data === undefined) {
                                return '';
                            }
                            return '<strong> € ' + data + '</strong>';
                        }
                    },
                    { data:'date_publisher_price', name:'date_publisher_price',
                       className:'text-center', render:dt },

                    { data: 'linkbuilder', name: 'linkbuilder', className: 'text-center', },
                    { data: 'type_of_website', name: 'type_of_website', className: 'text-center', },
                    { data: 'categories_list', name: 'categories_list', className: 'text-center', },
                    { data: 'DA', name: 'DA', className: 'text-center', },
                    { data: 'PA', name: 'PA', className: 'text-center', },
                    { data: 'TF', name: 'TF', className: 'text-center', },
                    { data: 'CF', name: 'CF', className: 'text-center', },
                    { data: 'DR', name: 'DR', className: 'text-center', },
                    { data: 'UR', name: 'UR', className: 'text-center', },
                    { data: 'ZA', name: 'ZA', className: 'text-center', },
                    { data: 'as_metric', name: 'as_metric', className: 'text-center', },
                    { data: 'seozoom', name: 'seozoom', className: 'text-center', },
                    { data: 'TF_vs_CF', name: 'TF_vs_CF', className: 'text-center', },
                    { data: 'semrush_traffic', name: 'semrush_traffic', className: 'text-center', },
                    { data: 'ahrefs_keyword', name: 'ahrefs_keyword', className: 'text-center', },
                    { data: 'ahrefs_traffic', name: 'ahrefs_traffic', className: 'text-center', },
                    { data: 'keyword_vs_traffic', name: 'keyword_vs_traffic', className: 'text-center', },
                    { data:'seo_metrics_date', name:'seo_metrics_date',
                      className:'text-center', render:dt },
                    { data: 'betting', name: 'betting', className: 'text-center',
                        render: function (data, type, row) {
                            if (data === 1 )  {
                                return 'YES';
                            }else if(data === 0)

                            return 'NO';
                        }
                    },
                    { data: 'trading', name: 'trading', className: 'text-center',
                        render: function (data, type, row) {
                            if (data === 1 )  {
                                return 'YES';
                            }else if(data === 0)

                                return 'NO';
                        }
                    },
                    { data: 'permanent_link', name: 'permanent_link', className: 'text-center',
                        render: function (data, type, row) {
                            if (data === 1 )  {
                                return 'YES';
                            }else if(data === 0)

                                return 'NO';
                        }
                    },
                    { data: 'more_than_one_link', name: 'more_than_one_link', className: 'text-center',
                        render: function (data, type, row) {
                            if (data === 1 )  {
                                return 'YES';
                            }else if(data === 0)

                                return 'NO';
                        }
                    },
                    { data: 'copywriting', name: 'copywriting', className: 'text-center',  render: function (data, type, row) {
                            if (data === 1 )  {
                                return 'PROVIDED';
                            }else if(data === 0)

                                return 'NOT PROVIDED';
                        }
                    },
                    { data: 'no_sponsored_tag', name: 'no_sponsored_tag', className: 'text-center',  render: function (data, type, row) {
                            if (data === 1 )  {
                                return 'YES';
                            }else if(data === 0)

                                return 'NO';
                        }
                    },
                    { data: 'social_media_sharing', name: 'social_media_sharing', className: 'text-center',  render: function (data, type, row) {
                            if (data === 1 )  {
                                return 'YES';
                            }else if(data === 0)

                                return 'NO';
                        }
                    },
                    { data: 'post_in_homepage', name: 'post_in_homepage', className: 'text-center',  render: function (data, type, row) {
                            if (data === 1 )  {
                                return 'YES';
                            }else if(data === 0)

                                return 'NO';
                        }
                    },
                    { data:'created_at', name:'date_added',
                     className:'text-center', render:dt },


                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                order: [[0, 'desc']],
                responsive: false,
                autoWidth: false
            });
            function dt(v){ return v ? new Date(v).toLocaleDateString('en-GB') : ''; }
            // Toggle-based filter
            $('#filterShowDeleted').on('change', function() {
                table.ajax.reload();
            });

            // Search
            $('#btnSearch').on('click', function(e) {
                e.preventDefault();
                table.ajax.reload();
            });

            // Clear
            $('#btnClear').on('click', function(e) {
                e.preventDefault();
                $('#filterForm input[type="text"], #filterForm input[type="number"]').val('');
                $('#filterForm select').val('');
                $('#filterForm input[type="checkbox"]').prop('checked', false);
                // Specifically clear the multi-selects:
                $('#filterCountriesInclude').val(null).trigger('change');
                $('#filterCountriesExclude').val(null).trigger('change');
                $('#filterNoContact').prop('checked', false);

                $('#filterCategories').val(null).trigger('change');
                table.ajax.reload();
            });

            function csvList(selector){
                const v = $(selector).val();
                return Array.isArray(v) ? v.join(',') : '';
            }
            // =====================
            // CSV Export
            // =====================
            $('#btnExportCsv').click(function(e) {
                e.preventDefault();
                let params = $.param({
                    domain_name: $('#filterDomainName').val(),
                    type_of_website: $('#filterWebsiteType').val(),
                    language_id: $('#filterLanguage').val(),
                    status: $('#filterStatus').val(),
                    country_id: $('#filterCountry').val(),
                    publisher_price_min: $('#filterPublisher_priceMin').val(),
                    publisher_price_max: $('#filterPublisher_priceMax').val(),
                    kialvo_min: $('#filterKialvo_evaluationMin').val(),
                    kialvo_max: $('#filterKialvo_evaluationMax').val(),
                    profit_min: $('#filterProfitMin').val(),
                    profit_max: $('#filterProfitMax').val(),
                    category_ids        : csvList('#filterCategories'),        // ← NEW
                    country_ids_include : csvList('#filterCountriesInclude'),  // ← NEW
                    country_ids_exclude : csvList('#filterCountriesExclude'),  // ← NEW
                    DA_min: $('#filterDAMin').val(),
                    DA_max: $('#filterDAMax').val(),
                    PA_min: $('#filterPAMin').val(),
                    PA_max: $('#filterPAMax').val(),
                    TF_min: $('#filterTFMin').val(),
                    TF_max: $('#filterTFMax').val(),
                    CF_min: $('#filterCFMin').val(),
                    CF_max: $('#filterCFMax').val(),
                    DR_min: $('#filterDRMin').val(),
                    DR_max: $('#filterDRMax').val(),
                    UR_min: $('#filterURMin').val(),
                    UR_max: $('#filterURMax').val(),
                    ZA_min: $('#filterZAMin').val(),
                    ZA_max: $('#filterZAMax').val(),
                    SR_min: $('#filterASMin').val(),
                    SR_max: $('#filterASMax').val(),
                    TF_VS_CF_min: $('#filterTF_vS_cfMin').val(),
                    TF_VS_CF_max: $('#filterTF_vS_cfMax').val(),
                    semrush_traffic_min: $('#filterSemrush_trafficMin').val(),
                    semrush_traffic_max: $('#filterSemrush_trafficMax').val(),
                    ahrefs_keyword_min: $('#filterAhrefs_keywordMin').val(),
                    ahrefs_keyword_max: $('#filterAhrefs_keywordMax').val(),
                    ahrefs_traffic_min: $('#filterAhrefs_trafficMin').val(),
                    ahrefs_traffic_max: $('#filterAhrefs_trafficMax').val(),
                    keyword_vs_traffic_min: $('#filterKeyword_vs_trafficMin').val(),
                    keyword_vs_traffic_max: $('#filterKeyword_vs_trafficMax').val(),

                    betting: $('#filterBetting').is(':checked') ? 1 : 0,
                    trading: $('#filterTrading').is(':checked') ? 1 : 0,
                    permanent_link: $('#filterPermanent_link').is(':checked') ? 1 : 0,
                    more_than_one_link: $('#filterMore_than_one_link').is(':checked') ? 1 : 0,
                    copywriting: $('#filterCopywriting').is(':checked') ? 1 : 0,
                    no_sponsored_tag: $('#filterNo_sponsored_tag').is(':checked') ? 1 : 0,
                    social_media_sharing: $('#filterSocial_media_sharing').is(':checked') ? 1 : 0,
                    post_in_homepage: $('#filterPost_in_homepage').is(':checked') ? 1 : 0,
                    show_deleted: $('#filterShowDeleted').is(':checked') ? 1 : 0
                });

                // Change this route to match your CSV export route
                window.location = "{{ route('websites.export.csv') }}?" + params;
            });

            // =====================
            // PDF Export
            // =====================
            $('#btnExportPdf').click(function(e) {
                e.preventDefault();
                let params = $.param({
                    domain_name: $('#filterDomainName').val(),
                    type_of_website: $('#filterWebsiteType').val(),
                    language_id: $('#filterLanguage').val(),
                    status: $('#filterStatus').val(),
                    country_id: $('#filterCountry').val(),
                    publisher_price_min: $('#filterPublisher_priceMin').val(),
                    publisher_price_max: $('#filterPublisher_priceMax').val(),
                    kialvo_min: $('#filterKialvo_evaluationMin').val(),
                    kialvo_max: $('#filterKialvo_evaluationMax').val(),
                    profit_min: $('#filterProfitMin').val(),
                    profit_max: $('#filterProfitMax').val(),
                    category_ids        : csvList('#filterCategories'),        // ← NEW
                    country_ids_include : csvList('#filterCountriesInclude'),  // ← NEW
                    country_ids_exclude : csvList('#filterCountriesExclude'),  // ← NEW

                    DA_min: $('#filterDAMin').val(),
                    DA_max: $('#filterDAMax').val(),
                    PA_min: $('#filterPAMin').val(),
                    PA_max: $('#filterPAMax').val(),
                    TF_min: $('#filterTFMin').val(),
                    TF_max: $('#filterTFMax').val(),
                    CF_min: $('#filterCFMin').val(),
                    CF_max: $('#filterCFMax').val(),
                    DR_min: $('#filterDRMin').val(),
                    DR_max: $('#filterDRMax').val(),
                    UR_min: $('#filterURMin').val(),
                    UR_max: $('#filterURMax').val(),
                    ZA_min: $('#filterZAMin').val(),
                    ZA_max: $('#filterZAMax').val(),
                    SR_min: $('#filterASMin').val(),
                    SR_max: $('#filterASMax').val(),
                    TF_VS_CF_min: $('#filterTF_vS_cfMin').val(),
                    TF_VS_CF_max: $('#filterTF_vS_cfMax').val(),
                    semrush_traffic_min: $('#filterSemrush_trafficMin').val(),
                    semrush_traffic_max: $('#filterSemrush_trafficMax').val(),
                    ahrefs_keyword_min: $('#filterAhrefs_keywordMin').val(),
                    ahrefs_keyword_max: $('#filterAhrefs_keywordMax').val(),
                    ahrefs_traffic_min: $('#filterAhrefs_trafficMin').val(),
                    ahrefs_traffic_max: $('#filterAhrefs_trafficMax').val(),
                    keyword_vs_traffic_min: $('#filterKeyword_vs_trafficMin').val(),
                    keyword_vs_traffic_max: $('#filterKeyword_vs_trafficMax').val(),

                    betting: $('#filterBetting').is(':checked') ? 1 : 0,
                    trading: $('#filterTrading').is(':checked') ? 1 : 0,
                    permanent_link: $('#filterPermanent_link').is(':checked') ? 1 : 0,
                    more_than_one_link: $('#filterMore_than_one_link').is(':checked') ? 1 : 0,
                    copywriting: $('#filterCopywriting').is(':checked') ? 1 : 0,
                    no_sponsored_tag: $('#filterNo_sponsored_tag').is(':checked') ? 1 : 0,
                    social_media_sharing: $('#filterSocial_media_sharing').is(':checked') ? 1 : 0,
                    post_in_homepage: $('#filterPost_in_homepage').is(':checked') ? 1 : 0,
                    show_deleted: $('#filterShowDeleted').is(':checked') ? 1 : 0
                });

                // Change this route to match your PDF export route
                window.location = "{{ route('websites.export.pdf') }}?" + params;
            });


            // ───────────────────────────────────────────────
            //  NOTE  MODAL
            // ───────────────────────────────────────────────
            $(document).on('click', '.note-link', function (e) {
                e.preventDefault();
                const note = $(this).data('note');
                $('#modalNoteBody').text(note);
                $('#noteModal').removeClass('hidden');
            });
            $('#closeNoteModal, #closeNoteModalBottom').on('click', function () {
                $('#noteModal').addClass('hidden');
            });

            // =====================
            // Contact Modal
            // =====================
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

                            // Build websites list
                            let websitesHtml = '';
                            if (c.websites && c.websites.length > 0) {
                                websitesHtml = '<ul>';
                                c.websites.forEach(function (w) {
                                    // If you want to view the website's show blade:
                                    let url = "/websites/" + w.id;

                                    websitesHtml += `
                                <li>
                                    <a href="${url}" class="underline text-blue-600">
                                        ${w.domain_name}
                                    </a>
                                </li>
                            `;
                                });
                                websitesHtml += '</ul>';
                            } else {
                                websitesHtml = '<p>No websites found for this contact.</p>';
                            }

                            $('#modalContactWebsites').html(websitesHtml);
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

            // Close buttons
            $('#closeContactModal, #closeContactModalBottom').on('click', function() {
                $('#contactModal').addClass('hidden');
            });

        }); // <-- END of $(document).ready()


        document.addEventListener('DOMContentLoaded', function() {
            let toggleBtn = document.getElementById('toggleFiltersBtn');
            let filtersDiv = document.getElementById('filterForm');
            let filtersVisible = true; // assume filters start out visible

            toggleBtn.addEventListener('click', function() {
                // Toggle the "hidden" class on the filters container
                filtersDiv.classList.toggle('hidden');
                filtersVisible = !filtersVisible;

                // Update the button text based on whether filters are now visible or not
                if (filtersVisible) {
                    toggleBtn.textContent = 'Hide Filters';
                } else {
                    toggleBtn.textContent = 'Show Filters';
                }
            });

            @if (session('status'))
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: '{{ session('status') }}',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false
            });
            @endif
        });

    </script>
@endpush
