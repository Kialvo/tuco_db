@extends('layouts.dashboard')
@section('title', 'Publishers')

@section('content')
    {{-- Page header --}}
    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between flex-shrink-0">
        <div>
            <h1 class="text-base font-bold text-gray-800">Publishers</h1>
            <p class="text-xs text-gray-500 mt-0.5">Domain owners and contact records.</p>
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
                <x-icon name="plus" size="sm" /> Create Publisher
            </button>
        </div>
    </div>

    <div class="px-6 py-6 bg-gray-50 min-h-full">

        <div id="contactsTableSearchWrap" class="table-search-wrap">
            <div class="flex items-center w-72 border border-gray-300 rounded-md bg-white shadow-sm
                        focus-within:ring-1 focus-within:ring-green-500 focus-within:border-green-500">
                <span class="px-3 text-gray-400 text-base leading-none">
                    <x-icon name="search" size="sm" class="inline" />
                </span>
                <input id="contactsTableSearch" type="text"
                       class="w-full bg-transparent border-0 focus:ring-0 focus:outline-none py-2 pr-3 text-sm leading-5"
                       placeholder="Search publishers...">
            </div>
        </div>

        <!-- DataTable Container -->
        <div class="bg-white border border-gray-200 rounded-xl shadow-card p-4 max-w-5xl mx-auto">
            <table id="contactsTable" class="text-sm text-gray-700" style="width:100%; table-layout:auto;">
                <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 tracking-wider">
                    <th class="py-3 px-4 font-semibold">ID</th>
                    <th class="py-3 px-4 font-semibold">Name</th>
                    <th class="py-3 px-4 font-semibold">Email</th>
                    <th class="py-3 px-4 font-semibold">Phone</th>
                    <th class="py-3 px-4 font-semibold">Facebook</th>
                    <th class="py-3 px-4 font-semibold">Instagram</th>
                    <th class="py-3 px-4 font-semibold">Action</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                <!-- DataTables populates these rows -->
                </tbody>
            </table>
        </div>
    </div>

    {{-- Include the Create Contact modal partial --}}
    @include('contacts.partials.create-modal')
    @include('contacts.partials.edit-modal')
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // ==========================
            // Initialize DataTable
            // ==========================
            let table = $('#contactsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('contacts.data') }}",
                    type: "POST",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: function(d) {
                        d.show_deleted = $('#filterShowDeleted').is(':checked');
                    }
                },
                columns: [
                    { data: 'id', name: 'id', className: 'text-right' },
                    { data: 'name', name: 'contact_name', render: d => d || '<span class="text-gray-300">—</span>' },
                    { data: 'email', name: 'email', render: d => d
                        ? `<a href="mailto:${d}" class="text-green-600 hover:text-green-700 hover:underline">${d}</a>`
                        : '<span class="text-gray-300">—</span>' },
                    { data: 'phone', name: 'phone', render: d => d || '<span class="text-gray-300">—</span>' },
                    { data: 'facebook', name: 'facebook' },
                    { data: 'instagram', name: 'instagram' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                order: [[0, 'desc']],
                responsive: true,
                autoWidth: false,
                dom: "<'dt-toolbar-top'<'flex items-center gap-3'l<'dt-search'>>>" +
                     "<'dt-scroll'rt>" +
                     "<'dt-toolbar-bottom'ip>",
                language: {
                    lengthMenu: "Show _MENU_ entries",
                    search: "Search:",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "No publishers found",
                    zeroRecords: "No matching publishers found"
                }
            });

            // Move search box into the DataTable header (next to "Show entries")
            $(table.table().container()).find('.dt-search').append($('#contactsTableSearchWrap'));

            // Table search (debounced to avoid slow typing)
            let contactsSearchTimer;
            $('#contactsTableSearch').on('input', function() {
                const value = this.value;
                clearTimeout(contactsSearchTimer);
                contactsSearchTimer = setTimeout(() => {
                    table.search(value).draw();
                }, 300);
            });
            $('#contactsTableSearch').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    clearTimeout(contactsSearchTimer);
                    table.search(this.value).draw();
                }
            });

            // Toggle-based filter
            $('#filterShowDeleted').on('change', function() {
                table.ajax.reload();
            });

            // DataTables styling
            table.on('init.dt', function() {
                // 1) LENGTH MENU
                let lengthLabel = $('div.dt-length label');
                let lengthSelect = lengthLabel.find('select');
                let selectHtml   = lengthSelect[0].outerHTML;
                lengthLabel.html(`Show ${selectHtml} entries`);
                let newSelect    = lengthLabel.find('select');
                newSelect.addClass(
                    "border border-gray-300 bg-white rounded-md px-3 py-1 text-gray-700 " +
                    "focus:ring-green-500 focus:border-green-500"
                );
                lengthLabel.addClass("text-gray-600 flex items-center space-x-2");

                // 2) PAGINATION
                let paginationDiv = $('div.dt-pagination');
                paginationDiv.addClass("space-x-2");
                paginationDiv.find('a').addClass(
                    "inline-block px-3 py-1 rounded hover:bg-gray-200 text-gray-700"
                );

                // 3) INFO TEXT
                let infoDiv = $('div.dt-info');
                infoDiv.addClass("text-gray-600");
            });

            // ==========================
            // Modal Logic
            // ==========================
            let createModal     = document.getElementById('createModal');
            let modalCloseBtn   = document.getElementById('modalCloseBtn');
            let btnOpenModal    = document.getElementById('btnOpenModal');

            // Show the modal when "Create Contact" is clicked
            btnOpenModal.addEventListener('click', function() {
                createModal.classList.remove('hidden');
                createModal.classList.add('flex');
            });

            // Hide the modal when "X" is clicked
            modalCloseBtn.addEventListener('click', function() {
                createModal.classList.add('hidden');
                createModal.classList.remove('flex');
            });

            // Optionally close the modal if user clicks outside the white card
            createModal.addEventListener('click', function(e) {
                if (e.target === createModal) {
                    createModal.classList.add('hidden');
                    createModal.classList.remove('flex');
                }
            });

            // 3) Catch clicks on the server-generated "Edit" button
            $(document).on('click', '.editBtn', function() {
                let contactId = $(this).data('contact-id');
                openEditModal(contactId);
            });


            // 4) openEditModal fetches data & shows the modal
            function openEditModal(contactId) {
                // Clear any old error messages
                $('#editModalErrors').hide().empty();

                // Ajax GET to our "showAjax" route: /contacts/{id}
                $.ajax({
                    url: "/contacts/" + contactId + "/edit-ajax" ,
                    type: "GET",
                    success: function(response) {
                        if (response.status === "success") {
                            let c = response.data;
                            // Fill form fields
                            $('#edit_name').val(c.name);
                            $('#edit_email').val(c.email);
                            $('#edit_phone').val(c.phone);
                            $('#edit_facebook').val(c.facebook);
                            $('#edit_instagram').val(c.instagram);

                            // Update form action to PUT /contacts/{id}
                            let updateForm = $('#editContactForm');
                            let url = "{{ url('contacts') }}/" + contactId;
                            updateForm.attr('action', url);

                            // Show the modal
                            $('#editModal').removeClass('hidden').addClass('flex');
                        } else {
                            alert("Could not load contact data.");
                        }
                    },
                    error: function() {
                        alert("Error fetching publisher data. Check network or console.");
                    }
                });
            }

            // 5) Close modal if user clicks "X" or outside
            $('#editModalCloseBtn').click(function() {
                $('#editModal').addClass('hidden').removeClass('flex');
            });
            $('#editModal').click(function(e) {
                if (e.target === this) {
                    $('#editModal').addClass('hidden').removeClass('flex');
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            @if (session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '{{ session('success') }}',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false
            });
            @endif
        });
    </script>
@endpush
