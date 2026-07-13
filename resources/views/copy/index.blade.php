@extends('layouts.dashboard')
@section('title', 'Copywriters')

@section('content')
    {{-- Page header --}}
    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between flex-shrink-0">
        <div>
            <h1 class="text-base font-bold text-gray-800">Copywriters</h1>
            <p class="text-xs text-gray-500 mt-0.5">Reusable copy snippets used in storages.</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <label for="filterShowDeleted" class="inline-flex items-center gap-2 text-sm text-gray-600 cursor-pointer select-none">
                <span>Show deleted</span>
                <label class="toggle-switch">
                    <input type="checkbox" id="filterShowDeleted">
                    <span class="toggle-track"></span>
                </label>
            </label>
            <button id="btnOpenModal"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-sm">
                <x-icon name="plus" size="sm" /> Create Copy
            </button>
        </div>
    </div>

    <div class="px-6 py-6 bg-gray-50 min-h-full">
        <!-- DataTable -->
        <div class="bg-white border border-gray-200 rounded-xl shadow-card p-4 max-w-4xl mx-auto">
            <table id="copyTable" class="text-sm text-gray-700" style="width:100%; table-layout:auto;">
                <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 tracking-wider">
                    <th class="py-3 px-4 font-semibold">ID</th>
                    <th class="py-3 px-4 font-semibold">Copy Value</th>
                    <th class="py-3 px-4 font-semibold">Action</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100"><!-- DataTables fills rows --></tbody>
            </table>
        </div>
    </div>

    {{-- Modals --}}
    @include('copy.partials.create-modal')
    @include('copy.partials.edit-modal')
@endsection

@push('scripts')
    <script>
        $(function () {
            /* ---------- DataTable ---------- */
            let table = $('#copyTable').DataTable({
                processing : true,
                serverSide : true,
                ajax : {
                    url     : "{{ route('copy.data') }}",
                    type    : "POST",
                    headers : { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data    : d => { d.show_deleted = $('#filterShowDeleted').is(':checked'); }
                },
                columns: [
                    { data: 'id',       name: 'id'       },
                    { data: 'copy_val', name: 'copy_val' },
                    { data: 'action',   name: 'action', orderable:false, searchable:false }
                ],
                order     : [[0,'desc']],
                responsive: true,
                autoWidth : false,
                dom       : "<'dt-toolbar-top'lf>" +
                            "<'dt-scroll'rt>" +
                            "<'dt-toolbar-bottom'ip>",
                language  : {
                    lengthMenu : "Show _MENU_ entries",
                    search     : "Search:",
                    info       : "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty  : "No copies found",
                    zeroRecords: "No matching copies found"
                }
            });

            $('#filterShowDeleted').on('change', () => table.ajax.reload());

            /* Nice‑looking widgets */
            table.on('init.dt', function () {
                let lbl = $('div.dt-length label');
                lbl.addClass('text-gray-600 flex items-center space-x-2');
                lbl.find('select').addClass(
                    'border border-gray-300 bg-white rounded-md px-3 py-1 text-gray-700 ' +
                    'focus:ring-green-500 focus:border-green-500');
                let filterLbl = $('div.dt-filter label').addClass('flex items-center space-x-2 text-gray-600');
                filterLbl.find('input').addClass(
                    'border border-gray-300 bg-white rounded-md px-3 py-1 ' +
                    'focus:ring-green-500 focus:border-green-500 text-gray-700');
                $('div.dt-pagination').addClass('space-x-2')
                    .find('a').addClass('inline-block px-3 py-1 rounded hover:bg-gray-200 text-gray-700');
                $('div.dt-info').addClass('text-gray-600');
            });

            /* ---------- Modal logic ---------- */
            const createModal = $('#createModal'),
                editModal   = $('#editModal');

            $('#btnOpenModal').on('click', () => createModal.removeClass('hidden').addClass('flex'));
            $('#modalCloseBtn').on('click', () => createModal.addClass('hidden').removeClass('flex'));
            createModal.on('click', e => { if(e.target===createModal[0]) createModal.addClass('hidden').removeClass('flex'); });

            /* Open Edit modal */
            $(document).on('click','.editBtn', function () {
                const id = $(this).data('copy-id');
                $('#editModalErrors').hide().empty();

                $.get(`/copy/${id}/edit-ajax`, res => {
                    if(res.status==='success'){
                        $('#edit_copy_val').val(res.data.copy_val);
                        $('#editCopyForm').attr('action', `{{ url('copy') }}/${id}`);
                        editModal.removeClass('hidden').addClass('flex');
                    } else { alert('Could not load copy.'); }
                }).fail(()=>alert('Error fetching copy.'));
            });

            $('#editModalCloseBtn').on('click', () => editModal.addClass('hidden').removeClass('flex'));
            editModal.on('click', e => { if(e.target===editModal[0]) editModal.addClass('hidden').removeClass('flex'); });
        });
    </script>
@endpush
