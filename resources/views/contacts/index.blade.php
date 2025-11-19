@extends('layouts.dashboard')

@section('content')

    <h1 class="text-lg font-bold text-gray-700  py-6">Publishers</h1>
    <div class="px-6 py-6 bg-gray-50 min-h-screen">


        <!-- Row for the toggle (Show Deleted) + Create Contact button -->
        <div class="flex items-center justify-between mb-4">


            <!-- Show Deleted Toggle -->
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
                                peer-checked:after:border-white">
                    </div>
                </label>
            </div>

            <!-- Button that opens the Create Contact Modal -->
            <button id="btnOpenModal"
                    class="bg-cyan-600 text-white px-5 py-2 rounded shadow
                           hover:bg-cyan-700 focus:outline-none focus:ring-2
                           focus:ring-offset-2 focus:ring-cyan-500 transition">
                Create Publisher
            </button>
        </div>

        <!-- DataTable Container -->
        <div class="bg-white border border-gray-200 rounded shadow-sm p-4">
            <table id="contactsTable" class="w-full text-sm text-gray-700">
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
                    { data: 'id', name: 'id' },
                    { data: 'name', name: 'contact_name' },
                    { data: 'email', name: 'email' },
                    { data: 'phone', name: 'phone' },
                    { data: 'facebook', name: 'facebook' },
                    { data: 'instagram', name: 'instagram' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                order: [[0, 'desc']],
                responsive: true,
                autoWidth: false,
                dom: "<'flex items-center justify-between mb-2'<'dt-length'l><'dt-filter'f>>" +
                    "tr" +
                    "<'flex items-center justify-between mt-2'<'dt-info'i><'dt-pagination'p>>",
                language: {
                    lengthMenu: "Show _MENU_ entries",
                    search: "Search:",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "No publishers found",
                    zeroRecords: "No matching publishers found"
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
                    "focus:ring-cyan-500 focus:border-cyan-500"
                );
                lengthLabel.addClass("text-gray-600 flex items-center space-x-2");

                // 2) SEARCH FILTER
                let filterLabel = $('div.dt-filter label');
                let filterInput = filterLabel.find('input');
                filterLabel.addClass("flex items-center space-x-2 text-gray-600");
                filterInput.addClass(
                    "border border-gray-300 bg-white rounded-md px-3 py-1 " +
                    "focus:ring-cyan-500 focus:border-cyan-500 text-gray-700"
                );

                // 3) PAGINATION
                let paginationDiv = $('div.dt-pagination');
                paginationDiv.addClass("space-x-2");
                paginationDiv.find('a').addClass(
                    "inline-block px-3 py-1 rounded hover:bg-gray-200 text-gray-700"
                );

                // 4) INFO TEXT
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
