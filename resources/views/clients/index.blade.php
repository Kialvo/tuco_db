
{{-- resources/views/clients/index.blade.php --}}
@extends('layouts.dashboard')

@section('content')

    <h1 class="text-lg font-bold text-gray-700 py-6">Clients</h1>

    <div class="px-6 py-6 bg-gray-50 min-h-screen">
        <!-- ───── Row with “Show Deleted” toggle + “Create Client” button ───── -->
        <div class="flex items-center justify-between mb-4">
            <!-- Show‑Deleted -->
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

            <!-- Create‑Client -->
            <button id="btnOpenModal"
                    class="bg-cyan-600 text-white px-5 py-2 rounded shadow
                           hover:bg-cyan-700 focus:outline-none focus:ring-2
                           focus:ring-offset-2 focus:ring-cyan-500 transition">
                Create Client
            </button>
        </div>

        <!-- ───── DataTable wrapper ───── -->
        <div class="bg-white border border-gray-200 rounded shadow-sm p-4">
            <table id="clientsTable" class="w-full text-sm text-gray-700">
                <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 tracking-wider">
                    <th class="py-3 px-4 font-semibold">ID</th>
                    <th class="py-3 px-4 font-semibold">First Name</th>
                    <th class="py-3 px-4 font-semibold">Last Name</th>
                    <th class="py-3 px-4 font-semibold">Email</th>
                    <th class="py-3 px-4 font-semibold">Company</th>
                    <th class="py-3 px-4 font-semibold">Action</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                {{-- DataTables will inject rows --}}
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modals --}}
    @include('clients.partials.create-modal')
    @include('clients.partials.edit-modal')
@endsection

@push('scripts')
    <script>
        $(function () {
            /* ───── DataTable ───── */
            const table = $('#clientsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('clients.data') }}",
                    type: "POST",
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: d => { d.show_deleted = $('#filterShowDeleted').is(':checked'); }
                },
                columns: [
                    { data: 'id',         name: 'id' },
                    { data: 'first_name', name: 'first_name' },
                    { data: 'last_name',  name: 'last_name' },
                    { data: 'email',      name: 'email' },
                    { data: 'company',    name: 'company' },
                    { data: 'action',     name: 'action', orderable:false, searchable:false }
                ],
                order: [[0,'desc']],
                responsive: true,
                autoWidth: false,
                dom: "<'flex items-center justify-between mb-2'<'dt-length'l><'dt-filter'f>>" +
                    "tr" +
                    "<'flex items-center justify-between mt-2'<'dt-info'i><'dt-pagination'p>>",
                language: {
                    lengthMenu: "Show _MENU_ entries",
                    search:     "Search:",
                    info:       "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty:  "No clients found",
                    zeroRecords:"No matching clients found"
                }
            });

            /* ───── toggle reload ───── */
            $('#filterShowDeleted').on('change', () => table.ajax.reload());

            /* ───── beautify default DT elements (once) ───── */
            table.on('init.dt', function(){
                // length menu
                let len = $('div.dt-length label');
                len.addClass('text-gray-600 flex items-center space-x-2');
                len.find('select')
                    .addClass('border border-gray-300 bg-white rounded-md px-3 py-1 text-gray-700 ' +
                        'focus:ring-cyan-500 focus:border-cyan-500');

                // search
                let filt = $('div.dt-filter label');
                filt.addClass('flex items-center space-x-2 text-gray-600');
                filt.find('input')
                    .addClass('border border-gray-300 bg-white rounded-md px-3 py-1 text-gray-700 ' +
                        'focus:ring-cyan-500 focus:border-cyan-500');

                // pagination
                $('div.dt-pagination a')
                    .addClass('inline-block px-3 py-1 rounded hover:bg-gray-200 text-gray-700');

                // info
                $('div.dt-info').addClass('text-gray-600');
            });

            /* ───── modal logic ───── */
            const createModal = $('#createModal');
            const editModal   = $('#editModal');

            $('#btnOpenModal').on('click', () => createModal.removeClass('hidden').addClass('flex'));
            $('#modalCloseBtn').on('click', () => createModal.addClass('hidden').removeClass('flex'));
            createModal.on('click', e => { if (e.target === createModal[0]) createModal.addClass('hidden').removeClass('flex'); });

            // delegate edit buttons generated by server
            $(document).on('click', '.editBtn', function () {
                let id = $(this).data('client-id');
                loadEditForm(id);
            });

            $('#editModalCloseBtn').on('click', () => editModal.addClass('hidden').removeClass('flex'));
            editModal.on('click', e => { if (e.target === editModal[0]) editModal.addClass('hidden').removeClass('flex'); });

            function loadEditForm(id){
                $('#editModalErrors').hide().empty();
                $.get(`/clients/${id}/edit-ajax`, res => {
                    if(res.status === 'success'){
                        let c = res.data;
                        $('#edit_first_name').val(c.first_name);
                        $('#edit_last_name').val(c.last_name);
                        $('#edit_email').val(c.email);
                        $('#edit_company').val(c.company);
                        $('#editClientForm').attr('action', `/clients/${id}`);
                        editModal.removeClass('hidden').addClass('flex');
                    }else{
                        alert('Unable to load client.');
                    }
                }).fail(()=>alert('Error fetching client.'));
            }

            /* ───── flash success ───── */
            @if(session('success'))
            Swal.fire({
                icon:'success', title:'Success!', text:'{{ session('success') }}',
                timer:3000, timerProgressBar:true, showConfirmButton:false
            });
            @endif
        });
    </script>
@endpush
