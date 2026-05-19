@extends('layouts.dashboard')
@section('title', 'Traffic by Country')

@section('content')
    {{-- Page header --}}
    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between flex-shrink-0">
        <div>
            <h1 class="text-base font-bold text-gray-800">Traffic by Country</h1>
            <p class="text-xs text-gray-500 mt-0.5">Top 3 traffic countries, MS, organic keywords and traffic for any list of domains.</p>
        </div>
    </div>

    <div class="px-6 py-6 pb-10 bg-gray-50 min-h-screen">
        {{-- ── Input card ── --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-card p-6 mb-6 max-w-2xl">
            <p class="text-sm text-gray-500 mb-4">
                Enter up to 200 domains (one per line) or upload a CSV file.
            </p>

            {{-- Textarea --}}
            <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">
                    Domains (one per line)
                </label>
                <textarea id="domainsInput" rows="6"
                          placeholder="corriere.it&#10;repubblica.it&#10;gazzetta.it"
                          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm
                                 focus:ring-green-500 focus:border-green-500 font-mono"></textarea>
            </div>

            {{-- Divider --}}
            <div class="flex items-center gap-3 mb-4">
                <div class="flex-1 border-t border-gray-200"></div>
                <span class="text-xs text-gray-400 font-medium">OR</span>
                <div class="flex-1 border-t border-gray-200"></div>
            </div>

            {{-- CSV upload --}}
            <div class="mb-5">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">
                    Upload CSV (first column = domain)
                </label>
                <input type="file" id="csvFile" accept=".csv,.txt"
                       class="block w-full text-sm text-gray-500
                              file:mr-3 file:py-1.5 file:px-3 file:rounded
                              file:border-0 file:text-sm file:font-medium
                              file:bg-green-50 file:text-green-700
                              hover:file:bg-green-100 cursor-pointer"/>
            </div>

            {{-- Limit + Analyse --}}
            <div class="flex items-center gap-3">
                <select id="limitSelect"
                        class="border border-gray-300 rounded-md pl-3 pr-8 py-2 text-sm focus:ring-green-500 focus:border-green-500">
                    <option value="50">Up to 50 domains</option>
                    <option value="100">Up to 100 domains</option>
                    <option value="200">Up to 200 domains</option>
                </select>
                <button id="btnSearch"
                        class="bg-green-600 text-white px-5 py-2 rounded shadow-sm text-sm
                               hover:bg-green-700 focus:outline-none focus:ring-2
                               focus:ring-offset-2 focus:ring-green-500 transition flex items-center gap-2">
                    <x-icon name="globe-europe" size="sm" class="inline" />
                    <span id="btnLabel">Analyse</span>
                </button>
                <span id="progressText" class="text-sm text-gray-500 hidden"></span>
            </div>

            <p id="errorMsg" class="text-red-600 text-sm mt-3 hidden"></p>
        </div>

        {{-- ── Results ── --}}
        <div id="resultsWrapper" class="hidden">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm text-gray-600">
                    <span id="resultCount"></span> domain(s) analysed.
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
                        <th class="py-3 px-4 font-semibold text-left">Domain</th>
                        <th class="py-3 px-4 font-semibold text-center">MS</th>
                        <th class="py-3 px-4 font-semibold text-center">Organic KW</th>
                        <th class="py-3 px-4 font-semibold text-center">Organic Traffic</th>
                        <th class="py-3 px-4 font-semibold text-left">1st Country</th>
                        <th class="py-3 px-4 font-semibold text-left">2nd Country</th>
                        <th class="py-3 px-4 font-semibold text-left">3rd Country</th>
                    </tr>
                    </thead>
                    <tbody id="resultsBody" class="divide-y divide-gray-100">
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── Empty state ── --}}
        <div id="emptyState" class="hidden text-center py-16 text-gray-400">
            <x-icon name="globe" size="xl" class="inline mb-3" />
            <p class="text-lg">No traffic data found for the given domains.</p>
        </div>

    </div>
@endsection

@push('scripts')
<script>
$(function () {
    const btnSearch      = $('#btnSearch');
    const btnLabel       = $('#btnLabel');
    const domainsInput   = $('#domainsInput');
    const csvFile        = $('#csvFile');
    const limitSelect    = $('#limitSelect');
    const errorMsg       = $('#errorMsg');
    const progressText   = $('#progressText');
    const resultsWrapper = $('#resultsWrapper');
    const resultsBody    = $('#resultsBody');
    const emptyState     = $('#emptyState');
    const resultCount    = $('#resultCount');

    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    let lastResults = [];

    // ── CSV export ──
    $('#btnExportCsv').on('click', function () {
        if (!lastResults.length) return;

        const headers = [
            'Domain', 'MS', 'Organic KW', 'Organic Traffic',
            '1st Country', '1st Country %',
            '2nd Country', '2nd Country %',
            '3rd Country', '3rd Country %',
        ];
        const lines = [headers.join(',')];

        lastResults.forEach(function (row) {
            const c = row.countries || [];
            const cols = [
                '"' + (row.domain || '').replace(/"/g, '""') + '"',
                row.ms               !== null ? row.ms               : '',
                row.organic_kw       !== null ? row.organic_kw       : '',
                row.organic_traffic  !== null ? row.organic_traffic  : '',
                '"' + (c[0] ? c[0].name : '').replace(/"/g, '""') + '"',
                c[0] ? c[0].pct : '',
                '"' + (c[1] ? c[1].name : '').replace(/"/g, '""') + '"',
                c[1] ? c[1].pct : '',
                '"' + (c[2] ? c[2].name : '').replace(/"/g, '""') + '"',
                c[2] ? c[2].pct : '',
            ];
            lines.push(cols.join(','));
        });

        const csv  = lines.join('\r\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = 'traffic-distribution.csv';
        a.click();
        URL.revokeObjectURL(url);
    });

    // ── Loading state ──
    function setLoading(on) {
        if (on) {
            btnSearch.prop('disabled', true);
            btnLabel.text('Analysing…');
            btnSearch.prepend('<svg id="spinner" class="w-3.5 h-3.5 me-1 inline animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>');
            progressText.text('Sending request…').removeClass('hidden');
        } else {
            btnSearch.prop('disabled', false);
            btnLabel.text('Analyse');
            $('#spinner').remove();
            progressText.addClass('hidden');
        }
    }

    // ── Render a single result row ──
    function renderRow(row) {
        const c = row.countries || [];

        function countryCell(entry) {
            if (!entry) return '<td class="py-2 px-4 text-gray-400">—</td>';
            const pct = entry.pct;
            let badgeClass = 'bg-gray-100 text-gray-600';
            if (pct >= 50) badgeClass = 'bg-green-100 text-green-800';
            else if (pct >= 20) badgeClass = 'bg-blue-100 text-blue-800';
            return `<td class="py-2 px-4">
                        <span class="inline-flex items-center gap-1">
                            ${entry.name}
                            <span class="text-xs font-semibold px-1.5 py-0.5 rounded ${badgeClass}">${pct}%</span>
                        </span>
                    </td>`;
        }

        if (row.error) {
            return `<tr class="hover:bg-gray-50">
                        <td class="py-2 px-4 font-medium text-red-600">${row.domain}</td>
                        <td colspan="6" class="py-2 px-4 text-red-500 text-xs">${row.error}</td>
                    </tr>`;
        }

        const ms      = row.ms              !== null ? row.ms                                      : '—';
        const kw      = row.organic_kw      !== null ? row.organic_kw.toLocaleString()             : '—';
        const traffic = row.organic_traffic !== null ? row.organic_traffic.toLocaleString()        : '—';

        return `<tr class="hover:bg-gray-50">
                    <td class="py-2 px-4 font-medium">
                        <a href="https://${row.domain}" target="_blank"
                           class="text-green-700 hover:underline">${row.domain}</a>
                    </td>
                    <td class="py-2 px-4 text-center font-semibold">${ms}</td>
                    <td class="py-2 px-4 text-center">${kw}</td>
                    <td class="py-2 px-4 text-center">${traffic}</td>
                    ${countryCell(c[0])}
                    ${countryCell(c[1])}
                    ${countryCell(c[2])}
                </tr>`;
    }

    // ── Main search ──
    function doSearch() {
        const domainsText = domainsInput.val().trim();
        const file        = csvFile[0].files[0];

        if (!domainsText && !file) {
            errorMsg.text('Please enter at least one domain or upload a CSV file.').removeClass('hidden');
            return;
        }

        errorMsg.addClass('hidden');
        resultsWrapper.addClass('hidden');
        emptyState.addClass('hidden');
        resultsBody.empty();
        setLoading(true);

        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('limit', limitSelect.val());
        if (domainsText) formData.append('domains', domainsText);
        if (file)        formData.append('csv_file', file);

        $.ajax({
            url: "{{ route('tools.traffic_distribution.search') }}",
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                setLoading(false);
                const rows = res.results || [];
                lastResults = rows;
                resultCount.text(rows.length);

                const hasData = rows.some(r => (r.countries && r.countries.length > 0) || r.ms !== null);

                if (!hasData && !rows.some(r => r.error)) {
                    emptyState.removeClass('hidden');
                    return;
                }

                rows.forEach(function (row) {
                    resultsBody.append(renderRow(row));
                });

                resultsWrapper.removeClass('hidden');
            },
            error: function (xhr) {
                setLoading(false);
                const msg = xhr.responseJSON?.error ?? 'Something went wrong. Please try again.';
                errorMsg.text(msg).removeClass('hidden');
            }
        });
    }

    btnSearch.on('click', doSearch);
});
</script>
@endpush
