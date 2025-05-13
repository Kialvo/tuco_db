@extends('layouts.dashboard')

@section('content')

    <h1 class="text-lg font-bold text-gray-700 py-6">Copy Records</h1>

    <div class="px-6 py-6 bg-gray-50 min-h-screen">

        <!-- Toggle “Show Deleted” + “Create Copy” button -->
        <div class="flex items-center justify-between mb-4">

            <div class="flex items-center space-x-2">
                <label for="filterShowDeleted" class="text-gray-700 font-medium">Show Deleted</label>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="filterShowDeleted" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 rounded-full
                                peer dark:bg-gray-700 peer-checked:bg-cyan-600
                                peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-cyan-500
                                after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                after:bg-white after:border-gray-300 after:border
                                after:rounded-full after:h-5 after:w-5
                                after:transition-all peer-checked:after:translate-x-full
                                peer-checked:after:border-white"></div>
                </label>
            </div>

            <button id="btnOpenModal"
                    class="bg-cyan-600 text-white px-5 py-2 rounded shadow
                           hover:bg-cyan-700 focus:outline-none focus:ring-2
                           focus:ring-offset-2 focus:ring-cyan-500 transition">
                Create Copy
            </button>
        </div>

        <!-- DataTable -->
        <div class="bg-white border border-gray-200 rounded shadow-sm p-4">
            <table id="copyTable" class="w-full text-sm text-gray-700">
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
                dom       : "<'flex items-center justify-between mb-2'<'dt-length'l><'dt-filter'f>>" +
                    "tr" +
                    "<'flex items-center justify-between mt-2'<'dt-info'i><'dt-pagination'p>>",
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
                    'focus:ring-cyan-500 focus:border-cyan-500');
                let filterLbl = $('div.dt-filter label').addClass('flex items-center space-x-2 text-gray-600');
                filterLbl.find('input').addClass(
                    'border border-gray-300 bg-white rounded-md px-3 py-1 ' +
                    'focus:ring-cyan-500 focus:border-cyan-500 text-gray-700');
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
