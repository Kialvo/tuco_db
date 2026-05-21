{{-- Domain → Storage slide-over drawer. Include once per page. --}}

{{-- Backdrop --}}
<div id="domainDrawerBackdrop"
     class="fixed inset-0 z-40 bg-black/30 transition-opacity duration-200 opacity-0 pointer-events-none"></div>

{{-- Drawer panel --}}
<div id="domainDrawer"
     class="fixed inset-y-0 right-0 z-50 flex flex-col w-full sm:w-[580px] bg-white shadow-2xl
            transition-transform duration-300 translate-x-full">

    {{-- Header --}}
    <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-200 bg-gray-50 flex-shrink-0">
        <div>
            <h2 id="drawerDomainName" class="text-sm font-bold text-gray-800"></h2>
            <p class="text-xs text-gray-500 mt-0.5">
                <span id="drawerEntryCount">—</span> storage entries
            </p>
        </div>
        <button id="domainDrawerClose"
                class="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Body --}}
    <div class="flex-1 overflow-y-auto">
        {{-- Spinner --}}
        <div id="drawerSpinner" class="hidden flex flex-col items-center justify-center py-16 text-gray-400">
            <svg class="w-6 h-6 animate-spin mb-2 text-green-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
            <span class="text-sm">Loading entries…</span>
        </div>

        {{-- Empty state --}}
        <div id="drawerEmpty" class="hidden text-center py-16 text-gray-400 text-sm">
            No storage entries found for this domain.
        </div>

        {{-- Table --}}
        <table id="drawerTable" class="hidden w-full text-xs text-gray-700">
            <thead>
                <tr class="text-[11px] uppercase text-gray-500 tracking-wider border-b border-gray-200 bg-gray-50 sticky top-0">
                    <th class="py-2 px-3 text-left font-semibold">ID</th>
                    <th class="py-2 px-3 text-left font-semibold">Status</th>
                    <th class="py-2 px-3 text-left font-semibold">Campaign</th>
                    <th class="py-2 px-3 text-left font-semibold">Anchor</th>
                    <th class="py-2 px-3 text-left font-semibold">Published</th>
                    <th class="py-2 px-3 text-right font-semibold">Profit</th>
                    <th class="py-2 px-3 text-center font-semibold">Article</th>
                </tr>
            </thead>
            <tbody id="drawerTableBody" class="divide-y divide-gray-100"></tbody>
        </table>
    </div>

    {{-- Footer --}}
    <div class="flex-shrink-0 flex items-center justify-between px-5 py-3 border-t border-gray-100 bg-gray-50">
        <a id="drawerViewAll" href="#" target="_blank"
           class="inline-flex items-center gap-1 text-xs font-medium text-green-700 hover:underline">
            View all in Storage
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
        </a>
        <button onclick="window.closeDomainDrawer()"
                class="text-xs text-gray-500 hover:text-gray-800 px-3 py-1.5 rounded-lg hover:bg-gray-200 transition">
            Close
        </button>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    const PREVIEW_URL = "{{ route('storages.domain_preview') }}";
    const STORAGE_URL = "{{ route('storages.index') }}";

    const $drawer   = $('#domainDrawer');
    const $backdrop = $('#domainDrawerBackdrop');

    function openDomainDrawer(domain) {
        $('#drawerDomainName').text(domain);
        $('#drawerEntryCount').text('…');
        $('#drawerViewAll').attr('href', STORAGE_URL + '?domain=' + encodeURIComponent(domain));

        $('#drawerSpinner').removeClass('hidden');
        $('#drawerEmpty').addClass('hidden');
        $('#drawerTable').addClass('hidden');
        $('#drawerTableBody').empty();

        $backdrop.removeClass('opacity-0 pointer-events-none').addClass('opacity-100');
        $drawer.removeClass('translate-x-full').addClass('translate-x-0');
        $('body').addClass('overflow-hidden');

        $.getJSON(PREVIEW_URL, { domain: domain })
            .done(function (res) {
                $('#drawerSpinner').addClass('hidden');
                $('#drawerEntryCount').text(res.total);

                if (!res.total) {
                    $('#drawerEmpty').removeClass('hidden');
                    return;
                }

                let html = '';
                res.entries.forEach(function (e) {
                    const campaign  = e.campaign    ? esc(e.campaign)    : '<span class="text-gray-300">—</span>';
                    const anchor    = e.anchor_text  ? esc(e.anchor_text) : '<span class="text-gray-300">—</span>';
                    const pubDate   = e.publication_date
                        ? new Date(e.publication_date).toLocaleDateString('en-GB')
                        : '<span class="text-gray-300">—</span>';
                    const profitNum = parseFloat(e.profit);
                    const profitHtml = (e.profit !== null && e.profit !== undefined)
                        ? `<span class="font-semibold ${profitNum < 0 ? 'text-red-600' : 'text-gray-800'}">€&thinsp;${e.profit}</span>`
                        : '<span class="text-gray-300">—</span>';
                    const articleLink = e.article_url
                        ? `<a href="${esc(e.article_url)}" target="_blank" rel="noopener" class="text-blue-600 hover:underline" title="${esc(e.article_url)}">Link</a>`
                        : '<span class="text-gray-300">—</span>';

                    html += `<tr class="hover:bg-green-50 transition-colors">
                        <td class="py-2 px-3">
                            <a href="${e.edit_url}" class="text-green-700 font-semibold hover:underline">#${e.id}</a>
                        </td>
                        <td class="py-2 px-3">${statusBadge(e.status)}</td>
                        <td class="py-2 px-3 max-w-[120px]">
                            <span class="block truncate" title="${esc(e.campaign || '')}">${campaign}</span>
                        </td>
                        <td class="py-2 px-3 max-w-[110px]">
                            <span class="block truncate" title="${esc(e.anchor_text || '')}">${anchor}</span>
                        </td>
                        <td class="py-2 px-3 whitespace-nowrap">${pubDate}</td>
                        <td class="py-2 px-3 text-right">${profitHtml}</td>
                        <td class="py-2 px-3 text-center">${articleLink}</td>
                    </tr>`;
                });

                $('#drawerTableBody').html(html);
                $('#drawerTable').removeClass('hidden');
            })
            .fail(function () {
                $('#drawerSpinner').addClass('hidden');
                $('#drawerEmpty').text('Failed to load entries. Please try again.').removeClass('hidden');
            });
    }

    window.closeDomainDrawer = function () {
        $drawer.removeClass('translate-x-0').addClass('translate-x-full');
        $backdrop.removeClass('opacity-100').addClass('opacity-0 pointer-events-none');
        $('body').removeClass('overflow-hidden');
    };

    function statusBadge(s) {
        if (!s) return '<span class="text-gray-300">—</span>';
        const palette = {
            'published'  : 'bg-green-100 text-green-800',
            'live'       : 'bg-green-100 text-green-800',
            'pending'    : 'bg-yellow-100 text-yellow-800',
            'in progress': 'bg-blue-100 text-blue-800',
            'rejected'   : 'bg-red-100 text-red-800',
            'draft'      : 'bg-gray-100 text-gray-600',
            'cancelled'  : 'bg-red-50 text-red-500',
        };
        const cls = palette[s.toLowerCase()] || 'bg-gray-100 text-gray-600';
        return `<span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide ${cls}">${esc(s)}</span>`;
    }

    function esc(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    $(document).on('click', '.domain-storage-link', function (e) {
        e.preventDefault();
        const domain = $(this).data('domain');
        if (domain) openDomainDrawer(domain);
    });

    $backdrop.on('click', window.closeDomainDrawer);
    $('#domainDrawerClose').on('click', window.closeDomainDrawer);
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') window.closeDomainDrawer();
    });
});
</script>
@endpush
