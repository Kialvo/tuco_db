@extends('layouts.dashboard')
@section('title', 'Keyword Research')

@section('content')
    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between flex-shrink-0">
        <div>
            <h1 class="text-base font-bold text-gray-800">Keyword Research</h1>
            <p class="text-xs text-gray-500 mt-0.5">Expand seed keywords and get volume, difficulty, CPC and search intent.</p>
        </div>
    </div>

    <div class="px-6 py-6 pb-10 bg-gray-50 min-h-screen">

        {{-- ── Input card ── --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-card p-6 mb-6 max-w-3xl">
            <p class="text-sm text-gray-500 mb-4">
                Enter up to 5 seed keywords (one per line or comma-separated). The tool will expand them into keyword ideas and enrich each with search volume, keyword difficulty, CPC and search intent.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                <div class="sm:col-span-3">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Seed Keywords</label>
                    <textarea id="keywordsInput" rows="3"
                              placeholder="e.g. link building&#10;guest posting&#10;seo services"
                              class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-green-500 focus:border-green-500 resize-none"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Country</label>
                    <select id="locationSelect" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-green-500 focus:border-green-500">
                        <option value="United States">United States</option>
                        <option value="United Kingdom">United Kingdom</option>
                        <option value="Italy">Italy</option>
                        <option value="Spain">Spain</option>
                        <option value="France">France</option>
                        <option value="Germany">Germany</option>
                        <option value="Australia">Australia</option>
                        <option value="Canada">Canada</option>
                        <option value="Netherlands">Netherlands</option>
                        <option value="Denmark">Denmark</option>
                        <option value="Sweden">Sweden</option>
                        <option value="Norway">Norway</option>
                        <option value="Brazil">Brazil</option>
                        <option value="Mexico">Mexico</option>
                        <option value="Argentina">Argentina</option>
                        <option value="India">India</option>
                        <option value="Russia">Russia</option>
                        <option value="Poland">Poland</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Language</label>
                    <select id="languageSelect" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-green-500 focus:border-green-500">
                        <option value="English">English</option>
                        <option value="Italian">Italian</option>
                        <option value="Spanish">Spanish</option>
                        <option value="French">French</option>
                        <option value="German">German</option>
                        <option value="Portuguese">Portuguese</option>
                        <option value="Dutch">Dutch</option>
                        <option value="Swedish">Swedish</option>
                        <option value="Danish">Danish</option>
                        <option value="Norwegian">Norwegian</option>
                        <option value="Polish">Polish</option>
                        <option value="Russian">Russian</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Results limit</label>
                    <select id="limitSelect" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-green-500 focus:border-green-500">
                        <option value="50">50 keywords</option>
                        <option value="100" selected>100 keywords</option>
                        <option value="200">200 keywords</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button id="btnSearch"
                        class="bg-green-600 text-white px-6 py-2 rounded shadow-sm text-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition flex items-center gap-2">
                    <x-icon name="key" size="sm" class="inline" />
                    <span id="btnLabel">Research</span>
                </button>
                <p id="errorMsg" class="text-red-600 text-sm hidden"></p>
            </div>
        </div>

        {{-- ── Results ── --}}
        <div id="resultsWrapper" class="hidden">

            {{-- Filter bar --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-card p-4 mb-4 flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Volume</label>
                    <div class="flex items-center gap-1.5">
                        <input type="number" id="filterVolMin" placeholder="Min" class="w-20 border border-gray-300 rounded px-2 py-1 text-xs focus:ring-green-500 focus:border-green-500">
                        <span class="text-gray-400 text-xs">–</span>
                        <input type="number" id="filterVolMax" placeholder="Max" class="w-20 border border-gray-300 rounded px-2 py-1 text-xs focus:ring-green-500 focus:border-green-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">KD</label>
                    <div class="flex items-center gap-1.5">
                        <input type="number" id="filterKdMin" placeholder="Min" min="0" max="100" class="w-16 border border-gray-300 rounded px-2 py-1 text-xs focus:ring-green-500 focus:border-green-500">
                        <span class="text-gray-400 text-xs">–</span>
                        <input type="number" id="filterKdMax" placeholder="Max" min="0" max="100" class="w-16 border border-gray-300 rounded px-2 py-1 text-xs focus:ring-green-500 focus:border-green-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Intent</label>
                    <select id="filterIntent" class="border border-gray-300 rounded px-2 py-1 text-xs focus:ring-green-500 focus:border-green-500">
                        <option value="">All</option>
                        <option value="informational">Informational</option>
                        <option value="commercial">Commercial</option>
                        <option value="transactional">Transactional</option>
                        <option value="navigational">Navigational</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Questions</label>
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" id="filterQuestions" class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                        <span class="text-xs text-gray-600">Only questions</span>
                    </label>
                </div>
                <div class="flex items-end gap-2 ms-auto">
                    <p class="text-xs text-gray-400"><span id="filteredCount">0</span> / <span id="totalCount">0</span> keywords</p>
                    <button id="btnExportCsv"
                            class="bg-green-600 text-white px-4 py-1.5 rounded shadow-sm text-sm hover:bg-green-700 focus:outline-none transition flex items-center gap-2">
                        <x-icon name="document-csv" size="sm" class="inline" /> Export CSV
                    </button>
                </div>
            </div>

            {{-- Table --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-card overflow-x-auto">
                <table class="w-full text-sm text-gray-700">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 tracking-wider">
                            <th class="py-3 px-4 font-semibold text-left">#</th>
                            <th class="py-3 px-4 font-semibold text-left">Keyword</th>
                            <th id="thVolume" class="py-3 px-4 font-semibold text-center cursor-pointer select-none hover:text-gray-700">
                                Volume <span id="volArrow">↓</span>
                            </th>
                            <th id="thKd" class="py-3 px-4 font-semibold text-center cursor-pointer select-none hover:text-gray-700">
                                KD <span id="kdArrow">–</span>
                            </th>
                            <th class="py-3 px-4 font-semibold text-center">CPC</th>
                            <th class="py-3 px-4 font-semibold text-center">Intent</th>
                            <th class="py-3 px-4 font-semibold text-center">Competition</th>
                        </tr>
                    </thead>
                    <tbody id="resultsBody" class="divide-y divide-gray-100"></tbody>
                </table>
            </div>
        </div>

        {{-- ── Empty state ── --}}
        <div id="emptyState" class="hidden text-center py-16 text-gray-400">
            <x-icon name="key" size="xl" class="inline mb-3" />
            <p class="text-lg">No keywords found for these seeds.</p>
        </div>

    </div>
@endsection

@push('scripts')
<script>
$(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    let allRows    = [];
    let sortField  = 'volume';
    let sortAsc    = false;

    const intentColors = {
        informational: 'bg-blue-100 text-blue-700',
        commercial:    'bg-amber-100 text-amber-700',
        transactional: 'bg-green-100 text-green-700',
        navigational:  'bg-gray-100 text-gray-600',
    };
    const compColors = {
        LOW:    'bg-green-100 text-green-700',
        MEDIUM: 'bg-amber-100 text-amber-700',
        HIGH:   'bg-red-100 text-red-700',
    };

    function kdColor(kd) {
        if (kd === null) return 'text-gray-400';
        if (kd <= 30)    return 'text-green-600 font-bold';
        if (kd <= 60)    return 'text-amber-600 font-bold';
        return 'text-red-600 font-bold';
    }

    // ── Filtering ──
    function applyFilters() {
        const volMin  = parseInt($('#filterVolMin').val()) || 0;
        const volMax  = parseInt($('#filterVolMax').val()) || Infinity;
        const kdMin   = parseInt($('#filterKdMin').val())  || 0;
        const kdMax   = parseInt($('#filterKdMax').val())  || 100;
        const intent  = $('#filterIntent').val();
        const questOnly = $('#filterQuestions').is(':checked');

        return allRows.filter(function (r) {
            const vol = r.volume ?? 0;
            const kd  = r.kd    ?? 0;
            if (vol < volMin || vol > volMax) return false;
            if (kd  < kdMin  || kd  > kdMax)  return false;
            if (intent && r.intent !== intent) return false;
            if (questOnly && !/^(who|what|when|where|why|how|is|are|can|does|do|should|will)\b/i.test(r.keyword)) return false;
            return true;
        });
    }

    function sortRows(rows) {
        return rows.slice().sort(function (a, b) {
            let va = a[sortField] ?? -1;
            let vb = b[sortField] ?? -1;
            return sortAsc ? va - vb : vb - va;
        });
    }

    function renderRows() {
        const filtered = sortRows(applyFilters());
        $('#filteredCount').text(filtered.length);
        const body = $('#resultsBody').empty();

        if (filtered.length === 0) {
            body.append('<tr><td colspan="7" class="py-8 text-center text-gray-400 text-sm">No keywords match the current filters.</td></tr>');
            return;
        }

        filtered.forEach(function (row, i) {
            const vol    = row.volume      !== null ? row.volume.toLocaleString()             : '—';
            const kd     = row.kd          !== null ? `<span class="${kdColor(row.kd)}">${row.kd}</span>` : '<span class="text-gray-300">—</span>';
            const cpc    = row.cpc         !== null ? '€ ' + row.cpc.toFixed(2)               : '—';
            const intent = row.intent
                ? `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold ${intentColors[row.intent] ?? 'bg-gray-100 text-gray-600'}">${row.intent.charAt(0).toUpperCase() + row.intent.slice(1)}</span>`
                : '<span class="text-gray-300">—</span>';
            const comp   = row.competition
                ? `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold ${compColors[row.competition] ?? 'bg-gray-100 text-gray-600'}">${row.competition}</span>`
                : '<span class="text-gray-300">—</span>';

            body.append(`
                <tr class="hover:bg-gray-50">
                    <td class="py-2 px-4 text-gray-400">${i + 1}</td>
                    <td class="py-2 px-4 font-medium text-gray-800">${row.keyword}</td>
                    <td class="py-2 px-4 text-center">${vol}</td>
                    <td class="py-2 px-4 text-center">${kd}</td>
                    <td class="py-2 px-4 text-center text-gray-600">${cpc}</td>
                    <td class="py-2 px-4 text-center">${intent}</td>
                    <td class="py-2 px-4 text-center">${comp}</td>
                </tr>
            `);
        });
    }

    // ── Sort headers ──
    function setSortArrow(field) {
        $('#volArrow').text('–');
        $('#kdArrow').text('–');
        if (field === 'volume') $('#volArrow').text(sortAsc ? '↑' : '↓');
        if (field === 'kd')     $('#kdArrow').text(sortAsc ? '↑' : '↓');
    }

    $('#thVolume').on('click', function () {
        if (sortField === 'volume') sortAsc = !sortAsc; else { sortField = 'volume'; sortAsc = false; }
        setSortArrow('volume');
        renderRows();
    });
    $('#thKd').on('click', function () {
        if (sortField === 'kd') sortAsc = !sortAsc; else { sortField = 'kd'; sortAsc = false; }
        setSortArrow('kd');
        renderRows();
    });

    // ── Filters live ──
    $('#filterVolMin, #filterVolMax, #filterKdMin, #filterKdMax, #filterIntent, #filterQuestions').on('change input', renderRows);

    // ── CSV export ──
    $('#btnExportCsv').on('click', function () {
        const rows = sortRows(applyFilters());
        if (!rows.length) return;

        const headers = ['Keyword', 'Volume', 'KD', 'CPC (€)', 'Intent', 'Competition'];
        const lines   = [headers.join(',')];

        rows.forEach(function (r) {
            lines.push([
                '"' + r.keyword.replace(/"/g, '""') + '"',
                r.volume      !== null ? r.volume      : '',
                r.kd          !== null ? r.kd          : '',
                r.cpc         !== null ? r.cpc.toFixed(2) : '',
                r.intent      || '',
                r.competition || '',
            ].join(','));
        });

        const blob = new Blob([lines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
        const a    = document.createElement('a');
        a.href     = URL.createObjectURL(blob);
        a.download = 'keyword-research.csv';
        a.click();
        URL.revokeObjectURL(a.href);
    });

    // ── Loading state ──
    function setLoading(on) {
        if (on) {
            $('#btnSearch').prop('disabled', true);
            $('#btnLabel').text('Researching…');
            $('#btnSearch').prepend('<svg id="spinner" class="w-3.5 h-3.5 me-1 inline animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>');
        } else {
            $('#btnSearch').prop('disabled', false);
            $('#btnLabel').text('Research');
            $('#spinner').remove();
        }
    }

    // ── Search ──
    function doSearch() {
        const seeds = $('#keywordsInput').val().trim();
        if (!seeds) {
            $('#errorMsg').text('Please enter at least one seed keyword.').removeClass('hidden');
            return;
        }

        $('#errorMsg').addClass('hidden');
        $('#resultsWrapper').addClass('hidden');
        $('#emptyState').addClass('hidden');
        setLoading(true);

        $.ajax({
            url: "{{ route('tools.keyword_research.search') }}",
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: {
                keywords:      seeds,
                location_name: $('#locationSelect').val(),
                language_name: $('#languageSelect').val(),
                limit:         $('#limitSelect').val(),
            },
            success: function (res) {
                setLoading(false);
                allRows = res.keywords || [];

                if (allRows.length === 0) {
                    $('#emptyState').removeClass('hidden');
                    return;
                }

                sortField = 'volume';
                sortAsc   = false;
                setSortArrow('volume');

                $('#totalCount').text(res.total);
                renderRows();
                $('#resultsWrapper').removeClass('hidden');
            },
            error: function (xhr) {
                setLoading(false);
                const msg = xhr.responseJSON?.error ?? 'Something went wrong. Please try again.';
                $('#errorMsg').text(msg).removeClass('hidden');
            }
        });
    }

    $('#btnSearch').on('click', doSearch);
    $('#keywordsInput').on('keydown', function (e) {
        if (e.key === 'Enter' && e.ctrlKey) doSearch();
    });
});
</script>
@endpush
