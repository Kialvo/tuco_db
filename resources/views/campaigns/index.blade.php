{{-- resources/views/campaigns/index.blade.php --}}
@extends('layouts.dashboard')
@section('title', 'Campaigns')

@php
    $services    = config('linkbuilding.services');
    $statusGrp   = config('linkbuilding.campaign_statuses');
    $targetTypes = config('linkbuilding.target_types');
@endphp

@section('content')
    {{-- Page header --}}
    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between flex-shrink-0">
        <div>
            <h1 class="text-base font-bold text-gray-800">Campaigns</h1>
            <p class="text-xs text-gray-500 mt-0.5">Link building campaigns &amp; publications.</p>
        </div>
        <button id="btnNewCampaign"
                class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-sm">
            <x-icon name="plus" size="sm" /> New Campaign
        </button>
    </div>

    <div class="px-6 py-6 bg-gray-50 min-h-full">

        {{-- Filters --}}
        <div class="flex flex-wrap items-center gap-2 mb-4">
            <input type="text" id="f_search" placeholder="Search code or client…"
                   class="border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500 min-w-[220px]">

            <select id="f_company" class="text-sm min-w-[200px]"></select>

            <select id="f_service" class="border border-gray-300 rounded-lg text-sm px-3 py-2 bg-white focus:ring-green-500 focus:border-green-500">
                <option value="">All services</option>
                @foreach($services as $s)
                    <option value="{{ $s }}">{{ $s }}</option>
                @endforeach
            </select>

            <select id="f_status" class="border border-gray-300 rounded-lg text-sm px-3 py-2 bg-white focus:ring-green-500 focus:border-green-500 max-w-[220px]">
                <option value="">All statuses</option>
                @foreach($statusGrp as $group => $statuses)
                    <optgroup label="{{ $group }}">
                        @foreach($statuses as $st)
                            <option value="{{ $st }}">{{ $st }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>

            <button id="f_today" type="button"
                    class="filter-toggle border border-gray-300 rounded-lg text-xs font-semibold px-3 py-2 bg-white text-gray-600 hover:bg-gray-50">
                📅 Today
            </button>
            <button id="f_group" type="button"
                    class="filter-toggle border border-gray-300 rounded-lg text-xs font-semibold px-3 py-2 bg-white text-gray-600 hover:bg-gray-50">
                By client
            </button>
            <button id="f_clear" type="button"
                    class="border border-red-200 text-red-600 rounded-lg text-xs font-semibold px-3 py-2 bg-white hover:bg-red-50 hidden">
                ✕ Clear
            </button>
        </div>

        {{-- DataTable --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-card p-4">
            <table id="campaignsTable" class="text-sm text-gray-700" style="width:100%; table-layout:auto;">
                <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 tracking-wider">
                    <th class="py-3 px-3 font-semibold text-left">Code</th>
                    <th class="py-3 px-3 font-semibold text-left">Company</th>
                    <th class="py-3 px-3 font-semibold text-left">Service</th>
                    <th class="py-3 px-3 font-semibold text-left">Status</th>
                    <th class="py-3 px-3 font-semibold text-right">Value</th>
                    <th class="py-3 px-3 font-semibold text-left">Target</th>
                    <th class="py-3 px-3 font-semibold text-right">Revenues</th>
                    <th class="py-3 px-3 font-semibold text-right">Costs</th>
                    <th class="py-3 px-3 font-semibold text-right">Profit&nbsp;€</th>
                    <th class="py-3 px-3 font-semibold text-right">Profit&nbsp;%</th>
                    <th class="py-3 px-3 font-semibold text-left">Budget&nbsp;Appr.</th>
                    <th class="py-3 px-3 font-semibold text-left">Offer&nbsp;Ready</th>
                    <th class="py-3 px-3 font-semibold text-left">Deadline</th>
                    <th class="py-3 px-3 font-semibold text-left">Next&nbsp;Update</th>
                    <th class="py-3 px-3 font-semibold text-left">Completion</th>
                    <th class="py-3 px-3 font-semibold text-left">Responsible</th>
                    <th class="py-3 px-3 font-semibold text-center">💬</th>
                    <th class="py-3 px-3 font-semibold text-center">Action</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100"></tbody>
            </table>
        </div>
    </div>

    {{-- ═══════════ Campaign create/edit modal ═══════════ --}}
    <div id="campaignModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-2xl w-full max-w-2xl relative max-h-[92vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 sticky top-0 bg-white z-10">
                <h2 id="campaignModalTitle" class="text-lg font-bold text-gray-800">New Campaign</h2>
                <button type="button" class="js-close-campaign text-gray-400 hover:text-gray-600"><x-icon name="x" size="sm" /></button>
            </div>
            <div id="campaignErrors" class="hidden mx-6 mt-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm"></div>
            <form id="campaignForm" class="px-6 py-5 space-y-4">
                <input type="hidden" id="c_id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Campaign Code <span class="text-red-500">*</span></label>
                        <input type="text" id="c_code" class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500" placeholder="e.g. IGMN_LB_006">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Company</label>
                        <select id="c_company_id" class="w-full"></select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Primary Contact</label>
                        <select id="c_contact_id" class="w-full"></select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Service</label>
                        <select id="c_service" class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 bg-white focus:ring-green-500 focus:border-green-500">
                            <option value="">—</option>
                            @foreach($services as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Status <span class="text-red-500">*</span></label>
                        <select id="c_status" class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 bg-white focus:ring-green-500 focus:border-green-500">
                            <option value="" disabled>— select status —</option>
                            @foreach($statusGrp as $group => $statuses)
                                <optgroup label="{{ $group }}">
                                    @foreach($statuses as $st)<option value="{{ $st }}">{{ $st }}</option>@endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Deal Value (€)</label>
                        <input type="number" step="0.01" id="c_deal_value" class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500" placeholder="0">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Target Type <span class="text-red-500">*</span></label>
                        <select id="c_target_type" class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 bg-white focus:ring-green-500 focus:border-green-500">
                            <option value="" disabled>— select target type —</option>
                            @foreach($targetTypes as $val => $lbl)<option value="{{ $val }}">{{ $lbl }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label id="c_target_label" class="block text-xs font-semibold text-gray-600 mb-1">Target Amount (€)</label>
                        <input type="number" step="0.01" id="c_target_value" class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500" placeholder="0">
                        <p class="text-[10px] text-gray-400 mt-1">Progress is tracked automatically as publications go live.</p>
                    </div>
                </div>

                <div class="pt-2 text-[11px] font-bold uppercase tracking-wide text-gray-400 border-t border-gray-100">Dates</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Budget Approval</label>
                        <input type="text" id="c_budget_approval_date" class="js-date block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500" placeholder="YYYY-MM-DD">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Offer Ready</label>
                        <input type="text" id="c_offer_ready_date" class="js-date block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500" placeholder="YYYY-MM-DD">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Deadline</label>
                        <input type="text" id="c_deadline" class="js-date block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500" placeholder="YYYY-MM-DD">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Completion <span class="text-gray-400 font-normal">(auto)</span></label>
                        <input type="text" id="c_completion_date" readonly class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 bg-gray-100 text-gray-500 cursor-not-allowed" placeholder="—">
                    </div>
                </div>

                <div class="pt-2 text-[11px] font-bold uppercase tracking-wide text-gray-400 border-t border-gray-100">Assignment</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Next Update</label>
                        <input type="text" id="c_next_update_date" class="js-date block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-green-500 focus:border-green-500" placeholder="YYYY-MM-DD">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Responsible</label>
                        <select id="c_responsible_user_id" class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 bg-white focus:ring-green-500 focus:border-green-500">
                            <option value="">—</option>
                            @foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                        </select>
                    </div>
                </div>
            </form>
            <div class="flex justify-end gap-2 px-6 py-4 border-t border-gray-100 sticky bottom-0 bg-white">
                <button type="button" class="js-close-campaign px-4 py-2 text-sm font-semibold text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="button" id="c_save" class="px-4 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-sm">Create Campaign</button>
            </div>
        </div>
    </div>

    {{-- Comments now open in the CRM-style conversation pane (layout partial) --}}

    {{-- floating dropdowns (populated by JS) --}}
    <div id="statusMenu" class="hidden fixed z-[60] bg-white border border-gray-200 rounded-lg shadow-xl py-1 max-h-72 overflow-y-auto min-w-[220px] text-sm"></div>
    <div id="serviceMenu" class="hidden fixed z-[60] bg-white border border-gray-200 rounded-lg shadow-xl py-1 max-h-72 overflow-y-auto min-w-[220px] text-sm"></div>
    <div id="respMenu" class="hidden fixed z-[60] bg-white border border-gray-200 rounded-lg shadow-xl py-1 max-h-72 overflow-y-auto min-w-[200px] text-sm"></div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/rowgroup/1.3.1/css/rowGroup.dataTables.min.css">
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/rowgroup/1.3.1/js/dataTables.rowGroup.min.js"></script>
<script>
$(function () {
    const csrf = $('meta[name="csrf-token"]').attr('content');
    const CAMPAIGN_STATUSES = @json($statusGrp);
    const SERVICES = @json($services);
    const TEAM = @json($users);
    let grouped = false;

    // Only one floating quick-edit menu may be visible at a time: badge
    // clicks stopPropagation (so the document-level closer never fires for
    // them) — every open call must close the others first.
    function closeFloatingMenus() { $('#statusMenu, #serviceMenu, #respMenu').addClass('hidden'); }

    // Position a fixed dropdown near a trigger, flipping up if it would overflow the viewport bottom.
    function positionMenu($menu, rect) {
        $menu.removeClass('hidden');
        const mh = $menu.outerHeight(), mw = $menu.outerWidth();
        const top = (rect.bottom + mh > window.innerHeight - 8) ? Math.max(8, rect.top - mh - 4) : rect.bottom + 4;
        const left = Math.max(8, Math.min(rect.left, window.innerWidth - mw - 8));
        $menu.css({ top: top + 'px', left: left + 'px' });
    }

    /* ─────────────── DataTable ─────────────── */
    const table = $('#campaignsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('crm.campaigns.data') }}",
            type: "POST",
            headers: { 'X-CSRF-TOKEN': csrf },
            data: function (d) {
                d.company_id = $('#f_company').val() || '';
                d.status     = $('#f_status').val() || '';
                d.service    = $('#f_service').val() || '';
                d.today      = $('#f_today').hasClass('active') ? 1 : 0;
            }
        },
        columns: [
            { data: 'code_cell',            name: 'code' },
            { data: 'company_name',         name: 'company_name', visible: false },
            { data: 'service_badge',        name: 'service',              searchable: false },
            { data: 'status_badge',         name: 'status',               searchable: false },
            { data: 'deal',                 name: 'deal_value',           searchable: false, className: 'text-right whitespace-nowrap' },
            { data: 'target',               name: 'target', orderable: false, searchable: false },
            { data: 'campaign_revenues',    name: 'campaign_revenues',    searchable: false, className: 'text-right whitespace-nowrap' },
            { data: 'campaign_costs',       name: 'campaign_costs',       searchable: false, className: 'text-right whitespace-nowrap' },
            { data: 'campaign_profit',      name: 'campaign_profit',      searchable: false, className: 'text-right whitespace-nowrap' },
            { data: 'campaign_profit_pct',  name: 'campaign_profit_pct',  searchable: false, className: 'text-right whitespace-nowrap' },
            { data: 'budget_approval_date', name: 'budget_approval_date', searchable: false, className: 'whitespace-nowrap' },
            { data: 'offer_ready_date',     name: 'offer_ready_date',     searchable: false, className: 'whitespace-nowrap' },
            { data: 'deadline',             name: 'deadline',             searchable: false, className: 'whitespace-nowrap' },
            { data: 'next_update_date',     name: 'next_update_date',     searchable: false, className: 'whitespace-nowrap' },
            { data: 'completion_date',      name: 'completion_date', orderable: false, searchable: false, className: 'whitespace-nowrap' },
            { data: 'responsible',          name: 'responsible', orderable: false, searchable: false },
            { data: 'comments_btn',         name: 'comments_btn', orderable: false, searchable: false, className: 'text-center' },
            { data: 'action',               name: 'action', orderable: false, searchable: false, className: 'text-center' },
        ],
        order: [[0, 'asc']],
        autoWidth: false,
        responsive: false,
        scrollX: true,
        rowGroup: { dataSrc: 'company_name', enable: false,
            startRender: function (rows, group) {
                return $('<tr/>').append(
                    '<td colspan="13" class="bg-gray-50 font-semibold text-gray-700 py-2 px-3 border-t-2 border-gray-200">'
                    + $('<div/>').text(group || '—').html()
                    + ' <span class="ml-1 text-xs text-gray-400">(' + rows.count() + ')</span></td>'
                );
            }
        },
        dom: "<'dt-toolbar-top'l>" + "<'dt-scroll'rt>" + "<'dt-toolbar-bottom'ip>",
        language: {
            lengthMenu: "Show _MENU_ campaigns",
            info: "Showing _START_ to _END_ of _TOTAL_ campaigns",
            infoEmpty: "No campaigns found",
            zeroRecords: "No matching campaigns found"
        }
    });

    table.on('init.dt', function () {
        $('div.dt-length label').addClass('text-gray-600 flex items-center space-x-2')
            .find('select').addClass('border border-gray-300 bg-white rounded-md px-3 py-1 text-gray-700 focus:ring-green-500 focus:border-green-500');
        $('div.dt-pagination a').addClass('inline-block px-3 py-1 rounded hover:bg-gray-200 text-gray-700');
        $('div.dt-info').addClass('text-gray-600');
    });

    /* ─────────────── Filters ─────────────── */
    $('#f_company').select2({
        placeholder: 'All companies', allowClear: true, minimumInputLength: 0, width: '200px',
        ajax: { url: "{{ route('companies.search') }}", dataType: 'json', delay: 250,
                data: p => ({ q: p.term }), processResults: d => ({ results: d.results }), cache: true }
    });

    function syncClear() {
        const active = $('#f_search').val() || $('#f_company').val() || $('#f_service').val()
            || $('#f_status').val() || $('#f_today').hasClass('active') || grouped;
        $('#f_clear').toggleClass('hidden', !active);
    }

    let searchTimer;
    $('#f_search').on('keyup', function () {
        clearTimeout(searchTimer);
        const v = this.value;
        searchTimer = setTimeout(() => { table.search(v).draw(); syncClear(); }, 300);
    });
    $('#f_company, #f_service, #f_status').on('change', () => { table.ajax.reload(); syncClear(); });

    // Today and By-Client are INDEPENDENT toggles.
    $('#f_today').on('click', function () {
        $(this).toggleClass('active bg-amber-50 text-amber-700 border-amber-300');
        table.ajax.reload();
        syncClear();
    });
    $('#f_group').on('click', function () {
        grouped = !grouped;
        $(this).toggleClass('active bg-green-50 text-green-700 border-green-300', grouped);
        if (grouped) { table.order([1, 'asc']); table.rowGroup().enable(true).draw(); }
        else { table.rowGroup().enable(false); table.order([0, 'asc']).draw(); }
        syncClear();
    });

    $('#f_clear').on('click', function () {
        $('#f_search').val('');
        $('#f_company').val(null).trigger('change.select2');
        $('#f_service').val('');
        $('#f_status').val('');
        $('#f_today').removeClass('active bg-amber-50 text-amber-700 border-amber-300');
        grouped = false;
        $('#f_group').removeClass('active bg-green-50 text-green-700 border-green-300');
        table.rowGroup().enable(false);
        table.search('').order([0, 'asc']);
        table.ajax.reload();
        syncClear();
    });

    /* ─────────────── Inline editable cells ─────────────── */
    $(document).on('click', '.js-cell-edit', function () {
        const cell = $(this);
        if (cell.hasClass('editing')) return;
        const id = cell.data('id'), field = cell.data('field'), type = cell.data('type');
        const cur = cell.attr('data-value') || '';
        cell.addClass('editing');

        let input;
        if (type === 'date') {
            input = $('<input type="date" class="border border-gray-300 rounded px-1 py-0.5 text-xs w-36">').val(cur);
        } else {
            const step = type === 'int' ? '1' : '0.01';
            input = $('<input type="number" min="0" step="' + step + '" class="border border-gray-300 rounded px-1 py-0.5 text-xs w-24">').val(cur);
        }
        cell.empty().append(input);
        input.trigger('focus');
        input.on('click', ev => ev.stopPropagation());

        let done = false;
        function commit(save) {
            if (done) return; done = true;
            if (!save) { table.ajax.reload(null, false); return; }
            $.ajax({
                url: "{{ url('campaigns') }}/" + id + "/inline", method: 'PUT',
                data: { field: field, value: input.val() }, headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                success: () => table.ajax.reload(null, false),
                error: (xhr) => {
                    table.ajax.reload(null, false);
                    const msg = Object.values(xhr.responseJSON?.errors ?? {}).flat().join(' ') || 'Update failed.';
                    Swal.fire({ icon: 'error', title: 'Invalid value', text: msg, timer: 2600, showConfirmButton: false });
                }
            });
        }
        input.on('keydown', function (ev) {
            if (ev.key === 'Enter') { ev.preventDefault(); commit(true); }
            else if (ev.key === 'Escape') { commit(false); }
        });
        input.on('blur', () => commit(true));
    });

    /* ─────────────── Responsible dropdown ─────────────── */
    const respMenu = $('#respMenu');
    let respTargetId = null;
    (function buildResp() {
        let h = '<div class="js-resp-opt px-3 py-1.5 hover:bg-gray-50 cursor-pointer text-gray-400" data-uid="">— Unassigned</div>';
        TEAM.forEach(u => { h += '<div class="js-resp-opt px-3 py-1.5 hover:bg-gray-50 cursor-pointer" data-uid="' + u.id + '">' + $('<i/>').text(u.name).html() + '</div>'; });
        respMenu.html(h);
    })();
    $(document).on('click', '.js-resp-edit', function (e) {
        e.stopPropagation();
        closeFloatingMenus();
        respTargetId = $(this).data('id');
        const r = this.getBoundingClientRect();
        positionMenu(respMenu, r);
    });
    $(document).on('click', '.js-resp-opt', function () {
        const uid = $(this).data('uid');
        $.ajax({
            url: "{{ url('campaigns') }}/" + respTargetId + "/inline", method: 'PUT',
            data: { field: 'responsible_user_id', value: uid || '' }, headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            success: () => { respMenu.addClass('hidden'); table.ajax.reload(null, false); }
        });
    });

    /* ─────────────── Campaign modal ─────────────── */
    const campaignModal = $('#campaignModal');
    let campaignMode = 'create';

    function openCampaignModal() { campaignModal.removeClass('hidden').addClass('flex'); }
    function closeCampaignModal() { campaignModal.addClass('hidden').removeClass('flex'); }
    $('.js-close-campaign').on('click', closeCampaignModal);
    campaignModal.on('click', e => { if (e.target === campaignModal[0]) closeCampaignModal(); });

    $('#c_company_id').select2({
        placeholder: 'Search company…', allowClear: true, minimumInputLength: 0, width: '100%',
        dropdownParent: campaignModal,
        ajax: { url: "{{ route('companies.search') }}", dataType: 'json', delay: 250,
                data: p => ({ q: p.term }), processResults: d => ({ results: d.results }), cache: true }
    });
    $('#c_contact_id').select2({ placeholder: 'Select company first…', width: '100%', dropdownParent: campaignModal });

    function loadContacts(companyId, selectedId) {
        const $c = $('#c_contact_id');
        $c.empty();
        if (!companyId) { $c.append(new Option('— select company first —', '')); $c.trigger('change'); return; }
        $.get("{{ url('companies') }}/" + companyId + "/contacts", function (res) {
            $c.append(new Option('—', ''));
            (res.results || []).forEach(o => $c.append(new Option(o.text, o.id, false, String(selectedId) === String(o.id))));
            $c.trigger('change');
        });
    }
    $('#c_company_id').on('change', function () { if (campaignMode) loadContacts($(this).val(), null); });

    function updTargetLabel() {
        const t = $('#c_target_type').val();
        $('#c_target_label').text(t === 'budget' ? 'Target Amount (€)' : t === 'publications' ? 'Nr. of Publications' : 'Target');
    }
    $('#c_target_type').on('change', updTargetLabel);

    flatpickr('.js-date', { dateFormat: 'Y-m-d', allowInput: true });

    const DATE_FIELDS = ['budget_approval_date', 'offer_ready_date', 'deadline', 'completion_date', 'next_update_date'];
    function setDate(field, val) {
        const el = document.getElementById('c_' + field);
        if (el && el._flatpickr) { val ? el._flatpickr.setDate(val, true) : el._flatpickr.clear(); }
        else if (el) { el.value = val || ''; }
    }

    function resetCampaignForm() {
        $('#campaignErrors').addClass('hidden').empty();
        $('#c_id').val('');
        $('#c_code, #c_deal_value, #c_target_value').val('');
        $('#c_service, #c_status, #c_responsible_user_id').val('');
        // No silent default: picking budget vs publications decides the
        // progress unit — an unnoticed 'budget' default froze campaign #6's
        // € sum under a "pubs" label.
        $('#c_target_type').val(''); updTargetLabel();
        $('#c_company_id').val(null).trigger('change');
        $('#c_contact_id').empty().append(new Option('— select company first —', '')).trigger('change');
        DATE_FIELDS.forEach(f => setDate(f, ''));
    }

    $('#btnNewCampaign').on('click', function () {
        campaignMode = 'create';
        resetCampaignForm();
        $('#campaignModalTitle').text('New Campaign');
        $('#c_save').text('Create Campaign');
        openCampaignModal();
    });

    $(document).on('click', '.js-edit-campaign', function () {
        const id = $(this).data('id');
        campaignMode = 'edit';
        resetCampaignForm();
        $('#campaignModalTitle').text('Edit Campaign');
        $('#c_save').text('Save Changes');
        $.get("{{ url('campaigns') }}/" + id + "/edit-ajax", function (res) {
            const d = res.data;
            $('#c_id').val(d.id);
            $('#c_code').val(d.code);
            $('#c_service').val(d.service || '');
            $('#c_status').val(d.status);
            $('#c_deal_value').val(d.deal_value || '');
            $('#c_target_type').val(d.target_type || 'budget'); updTargetLabel();
            $('#c_target_value').val(d.target_value || '');
            $('#c_responsible_user_id').val(d.responsible_user_id || '');
            DATE_FIELDS.forEach(f => setDate(f, d[f]));
            if (d.company_id) {
                $('#c_company_id').append(new Option(d.company_name || ('Company #' + d.company_id), d.company_id, true, true)).trigger('change');
                loadContacts(d.company_id, d.contact_id);
            }
            openCampaignModal();
        }).fail(() => alert('Unable to load campaign.'));
    });

    // Client-side required check: red border + message, no request sent.
    function markInvalid($el, bad) {
        ($el.hasClass('select2-hidden-accessible') ? $el.next('.select2').find('.select2-selection') : $el)
            .toggleClass('border-red-500 ring-1 ring-red-300', bad);
    }
    function requireFields(pairs, $errorBox) {
        const missing = [];
        pairs.forEach(([$el, label]) => {
            const bad = !(($el.val() || '').toString().trim());
            markInvalid($el, bad);
            if (bad) missing.push(label);
        });
        if (missing.length) {
            $errorBox.html('Required: ' + missing.join(', ') + '.').removeClass('hidden');
            return false;
        }
        $errorBox.addClass('hidden').empty();
        return true;
    }

    $('#c_save').on('click', function () {
        $('#campaignErrors').addClass('hidden').empty();
        if (!requireFields([
            [$('#c_code'), 'Campaign Code'],
            [$('#c_status'), 'Status'],
            [$('#c_target_type'), 'Target Type'],
        ], $('#campaignErrors'))) return;
        const payload = {
            code: $('#c_code').val(),
            company_id: $('#c_company_id').val() || '',
            contact_id: $('#c_contact_id').val() || '',
            responsible_user_id: $('#c_responsible_user_id').val() || '',
            service: $('#c_service').val() || '',
            status: $('#c_status').val(),
            deal_value: $('#c_deal_value').val() || 0,
            target_type: $('#c_target_type').val(),
            target_value: $('#c_target_value').val() || 0,
            budget_approval_date: $('#c_budget_approval_date').val() || '',
            offer_ready_date: $('#c_offer_ready_date').val() || '',
            deadline: $('#c_deadline').val() || '',
            // completion_date is auto-derived + read-only — not submitted.
            next_update_date: $('#c_next_update_date').val() || '',
            _token: csrf
        };
        const id = $('#c_id').val();
        const url = id ? "{{ url('campaigns') }}/" + id : "{{ route('crm.campaigns.store') }}";
        if (id) payload._method = 'PUT';

        $.ajax({
            url, method: 'POST', data: payload, headers: { 'Accept': 'application/json' },
            success: function () {
                closeCampaignModal();
                table.ajax.reload(null, false);
                Swal.fire({ icon: 'success', title: 'Saved!', timer: 1500, showConfirmButton: false });
            },
            error: function (xhr) {
                const errs = xhr.responseJSON?.errors ?? {};
                const msg = Object.values(errs).flat().join('<br>') || 'An error occurred.';
                $('#campaignErrors').html(msg).removeClass('hidden');
            }
        });
    });

    /* ─────────────── Delete campaign ─────────────── */
    $(document).on('click', '.js-del-campaign', function () {
        const id = $(this).data('id'), code = $(this).data('code');
        Swal.fire({
            icon: 'warning', title: 'Delete campaign?',
            text: code + ' and its publications will be removed.',
            showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#dc2626'
        }).then(r => {
            if (!r.isConfirmed) return;
            $.ajax({
                url: "{{ url('campaigns') }}/" + id, method: 'POST',
                data: { _method: 'DELETE', _token: csrf }, headers: { 'Accept': 'application/json' },
                success: () => { table.ajax.reload(null, false); Swal.fire({ icon: 'success', title: 'Deleted', timer: 1200, showConfirmButton: false }); }
            });
        });
    });

    /* ─────────────── Inline status dropdown ─────────────── */
    const statusMenu = $('#statusMenu');
    let statusTargetId = null;

    (function buildStatusMenu() {
        let h = '';
        $.each(CAMPAIGN_STATUSES, function (group, list) {
            h += '<div class="px-3 pt-2 pb-1 text-[9px] uppercase tracking-wider text-gray-400 font-bold">' + group + '</div>';
            list.forEach(s => { h += '<div class="js-status-opt px-3 py-1.5 hover:bg-gray-50 cursor-pointer" data-status="' + s.replace(/"/g, '&quot;') + '">' + s + '</div>'; });
        });
        statusMenu.html(h);
    })();

    $(document).on('click', '.js-status-badge', function (e) {
        e.stopPropagation();
        closeFloatingMenus();
        statusTargetId = $(this).data('id');
        const r = this.getBoundingClientRect();
        positionMenu(statusMenu, r);
    });
    $(document).on('click', '.js-status-opt', function () {
        const status = $(this).data('status');
        $.ajax({
            url: "{{ url('campaigns') }}/" + statusTargetId + "/status", method: 'PUT',
            data: { status }, headers: { 'X-CSRF-TOKEN': csrf },
            success: () => { statusMenu.addClass('hidden'); table.ajax.reload(null, false); }
        });
    });

    /* ─────────────── Inline service dropdown ─────────────── */
    const serviceMenu = $('#serviceMenu');
    let serviceTargetId = null;

    (function buildServiceMenu() {
        let h = '<div class="js-service-opt px-3 py-1.5 hover:bg-gray-50 cursor-pointer text-gray-400" data-service="">— None</div>';
        SERVICES.forEach(s => {
            h += '<div class="js-service-opt px-3 py-1.5 hover:bg-gray-50 cursor-pointer" data-service="' + s.replace(/"/g, '&quot;') + '">' + s + '</div>';
        });
        serviceMenu.html(h);
    })();

    $(document).on('click', '.js-service-badge', function (e) {
        e.stopPropagation();
        closeFloatingMenus();
        serviceTargetId = $(this).data('id');
        const r = this.getBoundingClientRect();
        positionMenu(serviceMenu, r);
    });
    $(document).on('click', '.js-service-opt', function () {
        const service = $(this).data('service');
        $.ajax({
            url: "{{ url('campaigns') }}/" + serviceTargetId + "/inline", method: 'PUT',
            data: { field: 'service', value: service || '' }, headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            success: () => { serviceMenu.addClass('hidden'); table.ajax.reload(null, false); }
        });
    });

    // Close floating menus on any outside click
    $(document).on('click', closeFloatingMenus);

    /* ─────────────── Conversations (CRM-style pane) ─────────────── */
    // 💬 badge = total messages (updates + replies) + red unread bubble.
    function refreshConvBadges() {
        $.getJSON("{{ route('crm.conversations.counts', 'campaign') }}", d => {
            const counts = d.counts || {};
            $('.js-comments-btn').each(function () {
                const id = String($(this).data('id'));
                $(this).find('.conv-count').remove();
                if (counts[id]) {
                    $(this).append('<span class="conv-count ml-0.5 inline-flex items-center justify-center min-w-[16px] h-4 px-1 rounded-full bg-blue-100 text-blue-700 text-[9px] font-bold">' + counts[id] + '</span>');
                }
            });
        });
        $.getJSON("{{ route('notifications.index') }}?unread=1&entityType=campaign", d => {
            const map = d.unread || {};
            $('.js-comments-btn').each(function () {
                const id = String($(this).data('id'));
                $(this).find('.notif-bubble').remove();
                if (map[id]) {
                    $(this).append('<span class="notif-bubble ml-0.5 inline-flex items-center justify-center min-w-[16px] h-4 px-1 rounded-full bg-red-500 text-white text-[9px] font-bold">' + map[id] + '</span>');
                }
            });
        });
    }
    table.on('draw', refreshConvBadges);
    $(document).on('tuco:conv-closed tuco:conv-opened', refreshConvBadges);

    function openThread(id, code) {
        tucoConversations.open({
            type: 'campaign',
            id: id,
            label: code || ('#' + id),
            detailsUrl: "{{ url('campaigns') }}/" + id,
            detailsLabel: 'CAMPAIGN DETAILS'
        });
    }

    $(document).on('click', '.js-comments-btn', function () {
        openThread($(this).data('id'), $(this).data('code'));
    });

    // Deep link from a notification: /campaigns?thread=<id> auto-opens the
    // pane, then removes the param so a refresh doesn't reopen it (CRM-style).
    (function () {
        const params = new URLSearchParams(window.location.search);
        const threadId = params.get('thread');
        if (!threadId) return;
        params.delete('thread');
        history.replaceState({}, '', window.location.pathname + (params.toString() ? '?' + params.toString() : ''));
        $.get("{{ url('campaigns') }}/" + threadId + "/edit-ajax")
            .done(res => openThread(threadId, res.data?.code))
            .fail(() => openThread(threadId, null));
    })();
});
</script>
@endpush
