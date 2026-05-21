{{-- Domain → Storage slide-over drawer. Include once per page. --}}

{{-- Backdrop --}}
<div id="domainDrawerBackdrop"
     class="fixed inset-0 z-40 bg-black/30 transition-opacity duration-200 opacity-0 pointer-events-none"></div>

{{-- Drawer panel --}}
<div id="domainDrawer"
     class="fixed inset-y-0 right-0 z-50 flex flex-col w-full sm:w-[620px] bg-white shadow-2xl
            transition-transform duration-300 translate-x-full">

    {{-- Header --}}
    <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-200 bg-gray-50 flex-shrink-0">
        <div class="min-w-0 flex-1 mr-3">
            <h2 id="drawerDomainName" class="text-sm font-bold text-gray-800 truncate"></h2>
            <p class="text-xs text-gray-500 mt-0.5">
                <span id="drawerEntryCount">—</span> storage entries
            </p>
        </div>
        <button id="domainDrawerClose"
                class="flex-shrink-0 p-1.5 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Domain search bar --}}
    <div class="px-3 py-2.5 border-b border-gray-100 bg-white flex-shrink-0">
        <div class="flex items-center gap-2 border border-gray-300 rounded-lg px-3 py-1.5
                    focus-within:ring-2 focus-within:ring-green-500 focus-within:border-green-500 bg-white">
            <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor"
                 viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
            </svg>
            <input id="drawerDomainSearch"
                   type="text"
                   placeholder="Search another domain…"
                   autocomplete="off"
                   class="flex-1 text-xs bg-transparent border-0 outline-none focus:ring-0 text-gray-700 placeholder-gray-400 min-w-0"/>
            <button id="drawerDomainSearchClear"
                    class="hidden flex-shrink-0 text-gray-300 hover:text-gray-500 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Body --}}
    <div class="flex-1 overflow-y-auto p-3">
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
        {{-- Cards list --}}
        <div id="drawerCards"></div>
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

    /* ── open ── */
    function openDomainDrawer(domain) {
        $('#drawerDomainName').text(domain);
        $('#drawerEntryCount').text('…');
        $('#drawerDomainSearch').val('');
        $('#drawerDomainSearchClear').addClass('hidden');
        $('#drawerViewAll').attr('href', STORAGE_URL + '?domain=' + encodeURIComponent(domain));
        $('#drawerSpinner').removeClass('hidden');
        $('#drawerEmpty').addClass('hidden');
        $('#drawerCards').empty();

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

                res.entries.forEach(function (e) {
                    $('#drawerCards').append(buildCard(e));
                });
            })
            .fail(function () {
                $('#drawerSpinner').addClass('hidden');
                $('#drawerEmpty').text('Failed to load entries. Please try again.').removeClass('hidden');
            });
    }

    /* ── close ── */
    window.closeDomainDrawer = function () {
        $drawer.removeClass('translate-x-0').addClass('translate-x-full');
        $backdrop.removeClass('opacity-100').addClass('opacity-0 pointer-events-none');
        $('body').removeClass('overflow-hidden');
    };

    /* ── card toggle ── */
    $(document).on('click', '.drawer-card-header', function () {
        const $body = $(this).next('.drawer-card-body');
        const $icon = $(this).find('.drawer-chevron');
        $body.toggleClass('hidden');
        $icon.toggleClass('rotate-180');
    });

    /* ── trigger ── */
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

    /* ── domain search bar ── */
    let searchTimer = null;

    $('#drawerDomainSearch').on('input', function () {
        const val = $(this).val().trim();
        $('#drawerDomainSearchClear').toggleClass('hidden', !val);
        clearTimeout(searchTimer);
        if (!val) return;
        searchTimer = setTimeout(function () {
            openDomainDrawer(val);
        }, 420);
    });

    $('#drawerDomainSearch').on('keydown', function (e) {
        if (e.key === 'Enter') {
            const val = $(this).val().trim();
            if (val) { clearTimeout(searchTimer); openDomainDrawer(val); }
        }
    });

    $('#drawerDomainSearchClear').on('click', function () {
        $('#drawerDomainSearch').val('').focus();
        $(this).addClass('hidden');
    });

    /* ══════════════════════════════════════════════
       Card builder
    ══════════════════════════════════════════════ */
    function buildCard(e) {
        const profitNum  = parseFloat(e.profit);
        const profitCls  = !isNaN(profitNum) && profitNum < 0 ? 'text-red-600' : 'text-gray-800';
        const profitDisp = e.profit !== null && e.profit !== undefined
            ? `<span class="font-semibold ${profitCls}">€ ${e.profit}</span>`
            : dash();

        return `
<div class="border border-gray-200 rounded-lg mb-2 overflow-hidden text-xs">

    {{-- ── summary row (always visible, click to expand) ── --}}
    <div class="drawer-card-header flex items-center gap-2 px-3 py-2.5 bg-gray-50
                hover:bg-gray-100 cursor-pointer select-none">
        <a href="${esc(e.edit_url)}" onclick="event.stopPropagation()"
           class="text-green-700 font-bold hover:underline flex-shrink-0">#${e.id}</a>
        ${statusBadge(e.status)}
        <span class="flex-1 truncate text-gray-700 font-medium" title="${esc(e.campaign || '')}">${e.campaign ? esc(e.campaign) : dash()}</span>
        <span class="text-gray-500 whitespace-nowrap flex-shrink-0">${fmtDate(e.publication_date)}</span>
        <span class="flex-shrink-0">${profitDisp}</span>
        <svg class="drawer-chevron w-3.5 h-3.5 text-gray-400 flex-shrink-0 transition-transform duration-200"
             fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
        </svg>
    </div>

    {{-- ── expanded details ── --}}
    <div class="drawer-card-body hidden px-3 py-3 bg-white border-t border-gray-100 space-y-3">

        ${section('General', [
            ['Status',       statusBadge(e.status)],
            ['LB',           e.LB],
            ['Client',       e.client_name],
            ['Contact',      e.contact_name],
            ['Country',      e.country_name],
            ['Language',     e.language_name],
            ['Copywriter',   e.copywriter_name],
            ['Categories',   e.categories_list],
            ['Added',        e.created_at],
        ])}

        ${section('Campaign', [
            ['Target Domain',  e.campaign],
            ['Anchor Text',    e.anchor_text],
            ['Target URL',     linkOrDash(e.target_url)],
            ['Campaign Code',  e.campaign_code],
            ['Article URL',    linkOrDash(e.article_url)],
        ])}

        ${section('Financial', [
            ['Currency',         e.publisher_currency],
            ['Publisher Amount', moneyRaw(e.publisher_amount, e.publisher_currency)],
            ['Publisher €',      money(e.publisher)],
            ['Copywriter €',     money(e.copy_nr)],
            ['Total Cost',       money(e.total_cost)],
            ['Menford',          money(e.menford)],
            ['Client Copy',      money(e.client_copy)],
            ['Total Revenues',   money(e.total_revenues)],
            ['Profit',           profitDisp],
        ])}

        ${section('Timeline', [
            ['CW Commission',   fmtDate(e.copywriter_commision_date)],
            ['CW Submission',   fmtDate(e.copywriter_submission_date)],
            ['CW Period (days)',  e.copywriter_period],
            ['Article Sent',    fmtDate(e.article_sent_to_publisher)],
            ['Published',       fmtDate(e.publication_date)],
            ['Expires',         fmtDate(e.expiration_date)],
            ['Pub Period (days)', e.publisher_period],
        ])}

        ${section('Invoicing', [
            ['Method → Us',          e.method_payment_to_us],
            ['Invoice Menford',      fmtDate(e.invoice_menford)],
            ['Invoice Nr',           e.invoice_menford_nr],
            ['Invoice Company',      e.invoice_company],
            ['Payment To Us',        fmtDate(e.payment_to_us_date)],
            ['Bill Publisher',       e.bill_publisher_name],
            ['Bill Nr',              e.bill_publisher_nr],
            ['Bill Date',            fmtDate(e.bill_publisher_date)],
            ['Payment To Publisher', fmtDate(e.payment_to_publisher_date)],
            ['Method → Publisher',   e.method_payment_to_publisher],
        ])}

        ${e.files ? section('Files', [['Files', esc(e.files)]]) : ''}

        <div class="pt-1">
            <a href="${esc(e.edit_url)}"
               class="inline-flex items-center gap-1 text-xs font-medium text-green-700 hover:underline">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit this entry
            </a>
        </div>
    </div>
</div>`;
    }

    /* ── helpers ── */
    function section(title, rows) {
        const filtered = rows.filter(([, v]) => v !== null && v !== undefined && v !== '' && v !== dash());
        if (!filtered.length) return '';
        const items = filtered.map(([label, value]) =>
            `<div class="flex gap-1.5">
                <dt class="w-36 flex-shrink-0 text-gray-400 font-medium">${esc(label)}</dt>
                <dd class="text-gray-800 min-w-0 break-words">${value}</dd>
            </div>`
        ).join('');
        return `<div>
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1.5">${title}</p>
            <dl class="space-y-1">${items}</dl>
        </div>`;
    }

    function dash() { return '<span class="text-gray-300">—</span>'; }

    function fmtDate(v) {
        if (!v) return null;
        try { return new Date(v).toLocaleDateString('en-GB'); } catch(e) { return v; }
    }

    function money(v) {
        if (v === null || v === undefined || v === '') return null;
        return `€ ${v}`;
    }

    function moneyRaw(v, currency) {
        if (v === null || v === undefined || v === '') return null;
        const sym = currency && currency.toUpperCase() !== 'EUR' ? currency + ' ' : '€ ';
        return `${sym}${v}`;
    }

    function linkOrDash(url) {
        if (!url) return null;
        return `<a href="${esc(url)}" target="_blank" rel="noopener"
                   class="text-blue-600 hover:underline break-all">${esc(url)}</a>`;
    }

    function statusBadge(s) {
        if (!s) return dash();
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
        return `<span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide flex-shrink-0 ${cls}">${esc(s)}</span>`;
    }

    function esc(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
});
</script>
@endpush
