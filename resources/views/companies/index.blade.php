{{-- resources/views/companies/index.blade.php --}}
@extends('layouts.dashboard')

@section('content')

    <h1 class="text-lg font-bold text-gray-700 py-6">Companies</h1>

    <div class="px-6 py-6 bg-gray-50 min-h-screen">

        <!-- ───── Action bar ───── -->
        <div class="flex items-center justify-end mb-4">
            <button id="btnOpenModal"
                    class="bg-cyan-600 text-white px-5 py-2 rounded shadow
                           hover:bg-cyan-700 focus:outline-none focus:ring-2
                           focus:ring-offset-2 focus:ring-cyan-500 transition">
                <i class="fas fa-plus mr-1"></i> Add Company
            </button>
        </div>

        <!-- ───── DataTable ───── -->
        <div class="bg-white border border-gray-200 rounded shadow-sm p-4">
            <table id="companiesTable" class="w-full text-sm text-gray-700">
                <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 tracking-wider">
                    <th class="py-3 px-4 font-semibold">ID</th>
                    <th class="py-3 px-4 font-semibold">Name</th>
                    <th class="py-3 px-4 font-semibold">Clients</th>
                    <th class="py-3 px-4 font-semibold">Action</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100"></tbody>
            </table>
        </div>
    </div>

    <!-- ═══════════════════════  CREATE MODAL  ═══════════════════════ -->
    <div id="createModal"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white border border-gray-200 p-6 rounded shadow-sm max-w-md w-full mx-2 relative">
            <button id="modalCloseBtn" type="button"
                    class="absolute top-2 right-2 text-gray-400 hover:text-gray-600 focus:outline-none">
                <i class="fas fa-times"></i>
            </button>
            <h2 class="text-xl font-bold text-gray-800 mb-4">Add Company</h2>
            <div id="createErrors"
                 class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4 hidden"></div>
            <form id="createCompanyForm" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="create_name" name="name" required
                           class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500"/>
                </div>
                <div class="pt-2">
                    <button type="submit"
                            class="bg-cyan-600 text-white px-5 py-2 rounded shadow-sm hover:bg-cyan-700
                                   focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 text-sm">
                        Save Company
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ═══════════════════════  EDIT MODAL  ═══════════════════════ -->
    <div id="editModal"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white border border-gray-200 p-6 rounded shadow-sm max-w-md w-full mx-2 relative">
            <button id="editModalCloseBtn" type="button"
                    class="absolute top-2 right-2 text-gray-400 hover:text-gray-600 focus:outline-none">
                <i class="fas fa-times"></i>
            </button>
            <h2 class="text-xl font-bold text-gray-800 mb-4">Edit Company</h2>
            <div id="editErrors"
                 class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4 hidden"></div>
            <form id="editCompanyForm" class="space-y-5">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_company_id"/>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="edit_name" name="name" required
                           class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2
                                  focus:ring-cyan-500 focus:border-cyan-500"/>
                </div>
                <div class="pt-2">
                    <button type="submit"
                            class="bg-cyan-600 text-white px-5 py-2 rounded shadow-sm hover:bg-cyan-700
                                   focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 text-sm">
                        Update Company
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(function () {

    /* ─── DataTable ─── */
    const table = $('#companiesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('companies.data') }}",
            type: "POST",
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        },
        columns: [
            { data: 'id',            name: 'id',            width: '60px' },
            { data: 'name',          name: 'name' },
            { data: 'clients_count', name: 'clients_count', searchable: false },
            { data: 'action',        name: 'action',        orderable: false, searchable: false }
        ],
        order: [[1,'asc']],
        responsive: true,
        autoWidth: false,
        dom: "<'flex items-center justify-between mb-2'<'dt-length'l><'dt-filter'f>>" +
             "tr" +
             "<'flex items-center justify-between mt-2'<'dt-info'i><'dt-pagination'p>>",
        language: {
            lengthMenu: "Show _MENU_ entries",
            search:     "Search:",
            info:       "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty:  "No companies found",
            zeroRecords:"No matching companies found"
        }
    });

    table.on('init.dt', function () {
        $('div.dt-length label').addClass('text-gray-600 flex items-center space-x-2')
            .find('select').addClass('border border-gray-300 bg-white rounded-md px-3 py-1 text-gray-700 focus:ring-cyan-500 focus:border-cyan-500');
        $('div.dt-filter label').addClass('flex items-center space-x-2 text-gray-600')
            .find('input').addClass('border border-gray-300 bg-white rounded-md px-3 py-1 text-gray-700 focus:ring-cyan-500 focus:border-cyan-500');
        $('div.dt-pagination a').addClass('inline-block px-3 py-1 rounded hover:bg-gray-200 text-gray-700');
        $('div.dt-info').addClass('text-gray-600');
    });

    /* ─── Create modal ─── */
    const createModal = $('#createModal');
    const editModal   = $('#editModal');

    $('#btnOpenModal').on('click', () => {
        $('#create_name').val('');
        $('#createErrors').hide().empty();
        createModal.removeClass('hidden').addClass('flex');
    });
    $('#modalCloseBtn').on('click', () => createModal.addClass('hidden').removeClass('flex'));
    createModal.on('click', e => { if (e.target === createModal[0]) createModal.addClass('hidden').removeClass('flex'); });

    $('#createCompanyForm').on('submit', function (e) {
        e.preventDefault();
        $('#createErrors').hide().empty();
        $.ajax({
            url: "{{ route('companies.store') }}",
            method: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content'), name: $('#create_name').val() },
            success: function (res) {
                createModal.addClass('hidden').removeClass('flex');
                table.ajax.reload();
                Swal.fire({ icon: 'success', title: 'Created!', text: 'Company added successfully.', timer: 2000, showConfirmButton: false });
            },
            error: function (xhr) {
                let errors = xhr.responseJSON?.errors ?? {};
                let msg = Object.values(errors).flat().join('<br>') || 'An error occurred.';
                $('#createErrors').html(msg).show();
            }
        });
    });

    /* ─── Edit modal ─── */
    $(document).on('click', '.editBtn', function () {
        let id   = $(this).data('company-id');
        let name = $(this).data('company-name');
        $('#edit_company_id').val(id);
        $('#edit_name').val(name);
        $('#editErrors').hide().empty();
        editModal.removeClass('hidden').addClass('flex');
    });

    $('#editModalCloseBtn').on('click', () => editModal.addClass('hidden').removeClass('flex'));
    editModal.on('click', e => { if (e.target === editModal[0]) editModal.addClass('hidden').removeClass('flex'); });

    $('#editCompanyForm').on('submit', function (e) {
        e.preventDefault();
        let id = $('#edit_company_id').val();
        $('#editErrors').hide().empty();
        $.ajax({
            url: `/companies/${id}`,
            method: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content'), _method: 'PUT', name: $('#edit_name').val() },
            success: function () {
                editModal.addClass('hidden').removeClass('flex');
                table.ajax.reload();
                Swal.fire({ icon: 'success', title: 'Updated!', text: 'Company updated successfully.', timer: 2000, showConfirmButton: false });
            },
            error: function (xhr) {
                let errors = xhr.responseJSON?.errors ?? {};
                let msg = Object.values(errors).flat().join('<br>') || 'An error occurred.';
                $('#editErrors').html(msg).show();
            }
        });
    });

    /* ─── Flash ─── */
    @if(session('success'))
    Swal.fire({ icon:'success', title:'Success!', text:'{{ session('success') }}', timer:3000, timerProgressBar:true, showConfirmButton:false });
    @endif
});
</script>
@endpush
