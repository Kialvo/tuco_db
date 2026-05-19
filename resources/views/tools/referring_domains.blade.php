@extends('layouts.dashboard')
@section('title', 'Organic Competitors')

@section('content')
    {{-- Page header --}}
    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between flex-shrink-0">
        <div>
            <h1 class="text-base font-bold text-gray-800">Organic Competitors</h1>
            <p class="text-xs text-gray-500 mt-0.5">Find domains competing for the same organic keywords.</p>
        </div>
    </div>

    <div class="px-6 py-6 pb-10 bg-gray-50 min-h-screen">
        {{-- ── Search bar ── --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-card p-6 mb-6 max-w-2xl">
            <p class="text-sm text-gray-500 mb-4">
                Enter a domain to find its top 100 organic competitors — sites ranking for the same keywords.
            </p>
            <div class="flex gap-3 mb-3">
                <input type="text"
                       id="domainInput"
                       placeholder="e.g. corriere.it"
                       class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm
                              focus:ring-green-500 focus:border-green-500"/>
                <select id="locationSelect"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-green-500 focus:border-green-500 w-44">
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
                <button id="btnSearch"
                        class="bg-green-600 text-white px-5 py-2 rounded shadow-sm text-sm
                               hover:bg-green-700 focus:outline-none focus:ring-2
                               focus:ring-offset-2 focus:ring-green-500 transition flex items-center gap-2">
                    <x-icon name="search" size="sm" class="inline" />
                    <span id="btnLabel">Search</span>
                </button>
            </div>
            <p id="errorMsg" class="text-red-600 text-sm mt-3 hidden"></p>
        </div>

        {{-- ── Results ── --}}
        <div id="resultsWrapper" class="hidden">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm text-gray-600">
                    Competitors for <strong id="resultDomain"></strong> —
                    <span id="resultCount"></span> domain(s) found.
                </p>
                <button id="btnExportCsv"
                        class="bg-green-600 text-white px-4 py-1.5 rounded shadow-sm text-sm
                               hover:bg-green-700 focus:outline-none focus:ring-2
                               focus:ring-offset-2 focus:ring-green-500 transition flex items-center gap-2">
                    <x-icon name="document-csv" size="sm" class="inline" /> Export CSV
                </button>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl shadow-card overflow-x-auto">
                <table class="w-full text-sm text-gray-700">
                    <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 tracking-wider">
                        <th class="py-3 px-4 font-semibold text-left">#</th>
                        <th class="py-3 px-4 font-semibold text-left">Competitor Domain</th>
                        <th id="thIntersections" class="py-3 px-4 font-semibold text-center cursor-pointer select-none hover:text-gray-700">
                            Shared KW <span id="kwArrow">↓</span>
                        </th>
                        <th class="py-3 px-4 font-semibold text-center">Relevance</th>
                        <th class="py-3 px-4 font-semibold text-center">MS</th>
                        <th class="py-3 px-4 font-semibold text-center">Organic Traffic</th>
                    </tr>
                    </thead>
                    <tbody id="resultsBody" class="divide-y divide-gray-100">
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── Empty state ── --}}
        <div id="emptyState" class="hidden text-center py-16 text-gray-400">
            <x-icon name="search" size="xl" class="inline mb-3" />
            <p class="text-lg">No competitors found for this domain.</p>
        </div>

    </div>
@endsection

@push('scripts')
<script>
$(function () {
    const btnSearch      = $('#btnSearch');
    const btnLabel       = $('#btnLabel');
    const domainInput    = $('#domainInput');
    const locationSelect = $('#locationSelect');
    const errorMsg       = $('#errorMsg');
    const resultsWrapper = $('#resultsWrapper');
    const resultsBody    = $('#resultsBody');
    const emptyState     = $('#emptyState');
    const resultDomain   = $('#resultDomain');
    const resultCount    = $('#resultCount');

    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    let lastRows   = [];
    let lastDomain = '';
    let sortAsc    = false;

    // ── CSV export ──
    $('#btnExportCsv').on('click', function () {
        if (!lastRows.length) return;

        const headers = ['Competitor Domain', 'Shared Keywords', 'Relevance (%)', 'MS', 'Organic Traffic'];
        const lines   = [headers.join(',')];

        lastRows.forEach(function (row) {
            const cols = [
                '"' + (row.domain || '').replace(/"/g, '""') + '"',
                row.intersections   !== null ? row.intersections   : '',
                row.relevance       !== null ? row.relevance       : '',
                row.ms              !== null ? row.ms              : '',
                row.organic_traffic !== null ? row.organic_traffic : '',
            ];
            lines.push(cols.join(','));
        });

        const csv      = lines.join('\r\n');
        const blob     = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url      = URL.createObjectURL(blob);
        const filename = 'competitors-' + lastDomain + '.csv';

        const a = document.createElement('a');
        a.href     = url;
        a.download = filename;
        a.click();
        URL.revokeObjectURL(url);
    });

    function setLoading(on) {
        if (on) {
            btnSearch.prop('disabled', true);
            btnLabel.text('Searching…');
            btnSearch.prepend('<svg id="spinner" class="w-3.5 h-3.5 me-1 inline animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>');
        } else {
            btnSearch.prop('disabled', false);
            btnLabel.text('Search');
            $('#spinner').remove();
        }
    }

    function doSearch() {
        const domain = domainInput.val().trim();
        if (!domain) {
            errorMsg.text('Please enter a domain.').removeClass('hidden');
            return;
        }

        errorMsg.addClass('hidden');
        resultsWrapper.addClass('hidden');
        emptyState.addClass('hidden');
        resultsBody.empty();
        setLoading(true);

        $.ajax({
            url: "{{ route('tools.referring_domains.search') }}",
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: { domain: domain, location_name: locationSelect.val() },
            success: function (res) {
                setLoading(false);
                lastRows   = res.rows || [];
                lastDomain = res.domain || '';
                resultDomain.text(res.domain);
                resultCount.text(res.total);

                if (!res.rows || res.rows.length === 0) {
                    emptyState.removeClass('hidden');
                    return;
                }

                sortAsc = false;
                $('#kwArrow').text('↓');
                renderRows();
                resultsWrapper.removeClass('hidden');
            },
            error: function (xhr) {
                setLoading(false);
                const msg = xhr.responseJSON?.error ?? 'Something went wrong. Please try again.';
                errorMsg.text(msg).removeClass('hidden');
            }
        });
    }

    function renderRows() {
        const sorted = lastRows.slice().sort(function (a, b) {
            const ka = a.intersections !== null ? a.intersections : -1;
            const kb = b.intersections !== null ? b.intersections : -1;
            return sortAsc ? ka - kb : kb - ka;
        });

        resultsBody.empty();
        sorted.forEach(function (row, i) {
            const kw      = row.intersections    !== null ? row.intersections.toLocaleString()   : '—';
            const rel     = row.relevance        !== null ? row.relevance + '%'                   : '—';
            const ms      = row.ms               !== null ? row.ms                                : '—';
            const traffic = row.organic_traffic  !== null ? row.organic_traffic.toLocaleString() : '—';
            resultsBody.append(`
                <tr class="hover:bg-gray-50">
                    <td class="py-2 px-4 text-gray-400">${i + 1}</td>
                    <td class="py-2 px-4 font-medium">
                        <a href="https://${row.domain}" target="_blank"
                           class="text-green-700 hover:underline">${row.domain}</a>
                    </td>
                    <td class="py-2 px-4 text-center font-semibold">${kw}</td>
                    <td class="py-2 px-4 text-center">${rel}</td>
                    <td class="py-2 px-4 text-center font-semibold">${ms}</td>
                    <td class="py-2 px-4 text-center">${traffic}</td>
                </tr>
            `);
        });
    }

    $('#thIntersections').on('click', function () {
        if (!lastRows.length) return;
        sortAsc = !sortAsc;
        $('#kwArrow').text(sortAsc ? '↑' : '↓');
        renderRows();
    });

    btnSearch.on('click', doSearch);

    domainInput.on('keydown', function (e) {
        if (e.key === 'Enter') doSearch();
    });
});
</script>
@endpush
