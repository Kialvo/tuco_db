@extends('layouts.dashboard')

@section('content')
    <div class="container mx-auto p-4">
        <!-- Header -->
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold">Contacts (Full CRUD)</h1>
            <a href="{{ route('contacts.create') }}"
               class="bg-blue-600 text-white px-4 py-2 rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Create Contact
            </a>
        </div>

        <!-- FILTERS -->
        <div class="grid grid-cols-6 gap-4 mb-4">
            <!-- Show Deleted CheckBox -->
            <div>
                <label class="block mb-1">Show Deleted</label>
                <input type="checkbox" id="filterShowDeleted" />
            </div>

            <!-- Optional: a button to apply filters -->
            <div>
                <button id="btnSearch"
                        class="bg-gray-700 text-white px-3 py-2 rounded-md shadow-sm hover:bg-gray-800">
                    Search
                </button>
            </div>
        </div>

        <!-- Status Message -->
        @if(session('status'))
            <div class="bg-green-100 text-green-700 p-4 rounded mb-4">
                {{ session('status') }}
            </div>
        @endif

        <!-- DataTable -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg w-full p-6">
            <table id="contactsTable" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Facebook</th>
                    <th>Instagram</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200"></tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
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
                        // Pass the "show_deleted" checkbox value
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
            });

            // If you have a "Search" button, reload the table
            $('#btnSearch').click(function(){
                table.ajax.reload();
            });
        });
    </script>
@endpush
