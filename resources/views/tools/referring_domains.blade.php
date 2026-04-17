@extends('layouts.dashboard')

@section('content')
    <h1 class="text-lg font-bold text-gray-700 py-6">Referring Domains</h1>

    <div class="px-6 pb-10 bg-gray-50 min-h-screen">

        {{-- ── Search bar ── --}}
        <div class="bg-white border border-gray-200 rounded shadow-sm p-6 mb-6 max-w-2xl">
            <p class="text-sm text-gray-500 mb-4">
                Enter a competitor domain to see its top 200 most authoritative referring domains (dofollow only, sorted by MS descending).
            </p>
            <div class="flex gap-3">
                <input type="text"
                       id="domainInput"
                       placeholder="e.g. corriere.it"
                       class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm
                              focus:ring-cyan-500 focus:border-cyan-500"/>
                <button id="btnSearch"
                        class="bg-cyan-600 text-white px-5 py-2 rounded shadow-sm text-sm
                               hover:bg-cyan-700 focus:outline-none focus:ring-2
                               focus:ring-offset-2 focus:ring-cyan-500 transition flex items-center gap-2">
                    <i class="fas fa-search"></i>
                    <span id="btnLabel">Search</span>
                </button>
            </div>
            <p id="errorMsg" class="text-red-600 text-sm mt-3 hidden"></p>
        </div>

        {{-- ── Results ── --}}
        <div id="resultsWrapper" class="hidden">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm text-gray-600">
                    Results for <strong id="resultDomain"></strong> —
                    <span id="resultCount"></span> referring domain(s) found.
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
                        <th class="py-3 px-4 font-semibold text-left">#</th>
                        <th class="py-3 px-4 font-semibold text-left">Referring Domain</th>
                        <th class="py-3 px-4 font-semibold text-center">Domain MS</th>
                        <th class="py-3 px-4 font-semibold text-left">Backlink Type</th>
                    </tr>
                    </thead>
                    <tbody id="resultsBody" class="divide-y divide-gray-100">
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── Empty state ── --}}
        <div id="emptyState" class="hidden text-center py-16 text-gray-400">
            <i class="fas fa-search text-4xl mb-3"></i>
            <p class="text-lg">No results found for this domain.</p>
        </div>

    </div>
@endsection

@push('scripts')
<script>
$(function () {
    const btnSearch      = $('#btnSearch');
    const btnLabel       = $('#btnLabel');
    const domainInput    = $('#domainInput');
    const errorMsg       = $('#errorMsg');
    const resultsWrapper = $('#resultsWrapper');
    const resultsBody    = $('#resultsBody');
    const emptyState     = $('#emptyState');
    const resultDomain   = $('#resultDomain');
    const resultCount    = $('#resultCount');

    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    let lastRows   = [];
    let lastDomain = '';

    // ── CSV export ──
    $('#btnExportCsv').on('click', function () {
        if (!lastRows.length) return;

        const headers = ['Referring Domain', 'Domain MS', 'Backlink Type'];
        const lines   = [headers.join(',')];

        lastRows.forEach(function (row) {
            const cols = [
                '"' + (row.domain      || '').replace(/"/g, '""') + '"',
                row.ms !== null ? row.ms : '',
                '"' + (row.backlink_type || '').replace(/"/g, '""') + '"',
            ];
            lines.push(cols.join(','));
        });

        const csv      = lines.join('\r\n');
        const blob     = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url      = URL.createObjectURL(blob);
        const filename = 'referring-domains-' + lastDomain + '.csv';

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
            btnSearch.prepend('<i class="fas fa-spinner fa-spin mr-1" id="spinner"></i>');
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
            data: { domain: domain },
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

                res.rows.forEach(function (row, i) {
                    const ms = row.ms !== null ? row.ms : '—';
                    const type = row.backlink_type || '—';
                    resultsBody.append(`
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 px-4 text-gray-400">${i + 1}</td>
                            <td class="py-2 px-4 font-medium">
                                <a href="https://${row.domain}" target="_blank"
                                   class="text-cyan-700 hover:underline">${row.domain}</a>
                            </td>
                            <td class="py-2 px-4 text-center font-semibold">${ms}</td>
                            <td class="py-2 px-4 text-gray-500">${type}</td>
                        </tr>
                    `);
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

    domainInput.on('keydown', function (e) {
        if (e.key === 'Enter') doSearch();
    });
});
</script>
@endpush
