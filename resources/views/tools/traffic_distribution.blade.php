@extends('layouts.dashboard')

@section('content')
    <h1 class="text-lg font-bold text-gray-700 py-6">Traffic Distribution by Country</h1>

    <div class="px-6 pb-10 bg-gray-50 min-h-screen">

        {{-- ── Input card ── --}}
        <div class="bg-white border border-gray-200 rounded shadow-sm p-6 mb-6 max-w-2xl">
            <p class="text-sm text-gray-500 mb-4">
                Enter up to 50 domains (one per line) or upload a CSV file. The tool will show the top 3 traffic countries and their share for each domain.
            </p>

            {{-- Textarea --}}
            <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">
                    Domains (one per line)
                </label>
                <textarea id="domainsInput" rows="6"
                          placeholder="corriere.it&#10;repubblica.it&#10;gazzetta.it"
                          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm
                                 focus:ring-cyan-500 focus:border-cyan-500 font-mono"></textarea>
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
                              file:bg-cyan-50 file:text-cyan-700
                              hover:file:bg-cyan-100 cursor-pointer"/>
            </div>

            <div class="flex items-center gap-3">
                <button id="btnSearch"
                        class="bg-cyan-600 text-white px-5 py-2 rounded shadow-sm text-sm
                               hover:bg-cyan-700 focus:outline-none focus:ring-2
                               focus:ring-offset-2 focus:ring-cyan-500 transition flex items-center gap-2">
                    <i class="fas fa-globe-europe"></i>
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
                    <i class="fas fa-file-csv"></i> Export CSV
                </button>
            </div>

            <div class="bg-white border border-gray-200 rounded shadow-sm overflow-x-auto">
                <table class="w-full text-sm text-gray-700">
                    <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 tracking-wider">
                        <th class="py-3 px-4 font-semibold text-left">Domain</th>
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
            <i class="fas fa-globe text-4xl mb-3"></i>
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
            'Domain',
            '1st Country', '1st Country %',
            '2nd Country', '2nd Country %',
            '3rd Country', '3rd Country %',
        ];
        const lines = [headers.join(',')];

        lastResults.forEach(function (row) {
            const c = row.countries || [];
            const cols = [
                '"' + (row.domain || '').replace(/"/g, '""') + '"',
                '"' + (c[0] ? c[0].name : '').replace(/"/g, '""') + '"',
                c[0] ? c[0].pct : '',
                '"' + (c[1] ? c[1].name : '').replace(/"/g, '""') + '"',
                c[1] ? c[1].pct : '',
                '"' + (c[2] ? c[2].name : '').replace(/"/g, '""') + '"',
                c[2] ? c[2].pct : '',
            ];
            lines.push(cols.join(','));
        });

        const csv      = lines.join('\r\n');
        const blob     = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url      = URL.createObjectURL(blob);
        const a        = document.createElement('a');
        a.href         = url;
        a.download     = 'traffic-distribution.csv';
        a.click();
        URL.revokeObjectURL(url);
    });

    // ── Loading state ──
    function setLoading(on) {
        if (on) {
            btnSearch.prop('disabled', true);
            btnLabel.text('Analysing…');
            btnSearch.prepend('<i class="fas fa-spinner fa-spin mr-1" id="spinner"></i>');
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

        function cell(entry) {
            if (!entry) return '<td class="py-2 px-4 text-gray-400">—</td>';
            const pct = entry.pct;
            let badgeClass = 'bg-gray-100 text-gray-600';
            if (pct >= 50) badgeClass = 'bg-cyan-100 text-cyan-800';
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
                        <td colspan="3" class="py-2 px-4 text-red-500 text-xs">${row.error}</td>
                    </tr>`;
        }

        return `<tr class="hover:bg-gray-50">
                    <td class="py-2 px-4 font-medium">
                        <a href="https://${row.domain}" target="_blank"
                           class="text-cyan-700 hover:underline">${row.domain}</a>
                    </td>
                    ${cell(c[0])}
                    ${cell(c[1])}
                    ${cell(c[2])}
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

                const hasData = rows.some(r => r.countries && r.countries.length > 0);

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
