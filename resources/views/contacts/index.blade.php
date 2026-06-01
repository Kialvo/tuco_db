@extends('layouts.dashboard')
@section('title', 'Publishers')

@section('content')
    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between flex-shrink-0">
        <div>
            <h1 class="text-base font-bold text-gray-800">Publishers</h1>
            <p class="text-xs text-gray-500 mt-0.5">Domain owners and contact records.</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <label class="inline-flex items-center gap-2 text-sm text-gray-600 cursor-pointer select-none">
                <span>Show deleted</span>
                <label class="toggle-switch">
                    <input type="checkbox" id="filterShowDeleted">
                    <span class="toggle-track"></span>
                </label>
            </label>
            <a href="{{ route('contacts.create') }}"
               class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-sm">
                <x-icon name="plus" size="sm" /> Create Publisher
            </a>
        </div>
    </div>

    <div class="px-6 py-6 bg-gray-50 min-h-screen">

        <div id="contactsTableSearchWrap" class="table-search-wrap">
            <div class="flex items-center w-72 border border-gray-300 rounded-md bg-white shadow-sm
                        focus-within:ring-1 focus-within:ring-green-500 focus-within:border-green-500">
                <span class="px-3 text-gray-400"><x-icon name="search" size="sm" class="inline" /></span>
                <input id="contactsTableSearch" type="text"
                       class="w-full bg-transparent border-0 focus:ring-0 focus:outline-none py-2 pr-3 text-sm"
                       placeholder="Search publishers…">
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-card p-4 mt-4">
            <table id="contactsTable" class="text-sm text-gray-700" style="width:100%">
                <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 tracking-wider">
                    <th class="py-3 px-4 font-semibold">ID</th>
                    <th class="py-3 px-4 font-semibold">Name</th>
                    <th class="py-3 px-4 font-semibold">Company</th>
                    <th class="py-3 px-4 font-semibold">Primary Channel</th>
                    <th class="py-3 px-4 font-semibold">Country of Origin</th>
                    <th class="py-3 px-4 font-semibold">Action</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100"></tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    let table = $('#contactsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('contacts.data') }}",
            type: "POST",
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: d => { d.show_deleted = $('#filterShowDeleted').is(':checked'); }
        },
        columns: [
            { data: 'id',                   name: 'id',            className: 'text-right w-12' },
            { data: 'full_name',            name: 'full_name',     render: d => d || '<span class="text-gray-300">—</span>' },
            { data: 'company_name',         name: 'company_name',  render: d => d || '<span class="text-gray-300">—</span>' },
            { data: 'primary_channel_html', name: 'channel_1',     orderable: false },
            { data: 'origin_country',       name: 'origin_country',render: d => d || '<span class="text-gray-300">—</span>' },
            { data: 'action',               name: 'action',        orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        responsive: true,
        autoWidth: false,
        dom: "<'dt-toolbar-top'<'flex items-center gap-3'l<'dt-search'>>>" +
             "<'dt-scroll'rt>" +
             "<'dt-toolbar-bottom'ip>",
        language: {
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "No publishers found",
            zeroRecords: "No matching publishers found"
        }
    });

    $(table.table().container()).find('.dt-search').append($('#contactsTableSearchWrap'));

    let searchTimer;
    $('#contactsTableSearch').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => table.search(this.value).draw(), 300);
    });

    $('#filterShowDeleted').on('change', () => table.ajax.reload());

    table.on('init.dt', function () {
        $('div.dt-length label select').addClass('border border-gray-300 bg-white rounded-md px-3 py-1 text-gray-700 focus:ring-green-500 focus:border-green-500');
    });

    @if(session('status'))
    Swal.fire({ icon: 'success', title: 'Done!', text: @json(session('status')), timer: 3000, showConfirmButton: false });
    @endif
});
</script>
@endpush
