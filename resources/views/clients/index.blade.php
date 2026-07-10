
{{-- resources/views/clients/index.blade.php --}}
@extends('layouts.dashboard')
@section('title', 'Contacts')

@section('content')
    {{-- Page header --}}
    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between flex-shrink-0">
        <div>
            <h1 class="text-base font-bold text-gray-800">Contacts</h1>
            <p class="text-xs text-gray-500 mt-0.5">Companies you publish for.</p>
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
                <x-icon name="plus" size="sm" /> Create Client
            </button>
        </div>
    </div>

    <div class="px-6 py-6 bg-gray-50 min-h-full">
        <!-- ───── DataTable wrapper ───── -->
        <div class="bg-white border border-gray-200 rounded-xl shadow-card p-4 max-w-5xl mx-auto">
            <table id="clientsTable" class="text-sm text-gray-700" style="width:100%; table-layout:auto;">
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
                    { data: 'id',         name: 'id', className: 'text-right' },
                    { data: 'first_name', name: 'first_name', render: d => d || '<span class="text-gray-300">—</span>' },
                    { data: 'last_name',  name: 'last_name',  render: d => d || '<span class="text-gray-300">—</span>' },
                    { data: 'email',      name: 'email',      render: d => d
                        ? `<a href="mailto:${d}" class="text-green-600 hover:text-green-700 hover:underline">${d}</a>`
                        : '<span class="text-gray-300">—</span>' },
                    { data: 'company',    name: 'company',    render: d => d || '<span class="text-gray-300">—</span>' },
                    { data: 'action',     name: 'action', orderable:false, searchable:false }
                ],
                order: [[0,'desc']],
                responsive: true,
                autoWidth: false,
                dom: "<'dt-toolbar-top'lf>" +
                     "<'dt-scroll'rt>" +
                     "<'dt-toolbar-bottom'ip>",
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
                        'focus:ring-green-500 focus:border-green-500');

                // search
                let filt = $('div.dt-filter label');
                filt.addClass('flex items-center space-x-2 text-gray-600');
                filt.find('input')
                    .addClass('border border-gray-300 bg-white rounded-md px-3 py-1 text-gray-700 ' +
                        'focus:ring-green-500 focus:border-green-500');

                // pagination
                $('div.dt-pagination a')
                    .addClass('inline-block px-3 py-1 rounded hover:bg-gray-200 text-gray-700');

                // info
                $('div.dt-info').addClass('text-gray-600');
            });

            /* ───── Select2 for company fields ───── */
            const companySearchUrl = "{{ route('companies.search') }}";

            function initSelect2(selector) {
                $(selector).select2({
                    placeholder: 'Search company…',
                    allowClear: true,
                    minimumInputLength: 0,
                    ajax: {
                        url: companySearchUrl,
                        dataType: 'json',
                        delay: 250,
                        data: params => ({ q: params.term }),
                        processResults: data => ({ results: data.results }),
                        cache: true
                    },
                    dropdownParent: $(selector).closest('.relative, .fixed, body').first()
                });
            }

            initSelect2('#create_company_id');
            initSelect2('#edit_company_id');

            /* ───── modal logic ───── */
            const createModal = $('#createModal');
            const editModal   = $('#editModal');

            $('#btnOpenModal').on('click', () => {
                $('#create_company_id').val(null).trigger('change');
                createModal.removeClass('hidden').addClass('flex');
            });
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

                        // Populate company Select2
                        let sel = $('#edit_company_id');
                        sel.empty();
                        if (c.company_id && c.company_name) {
                            sel.append(new Option(c.company_name, c.company_id, true, true));
                        } else {
                            sel.val(null);
                        }
                        sel.trigger('change');

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
