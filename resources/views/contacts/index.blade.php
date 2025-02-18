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

        <!-- Status Message -->
        @if(session('status'))
            <div class="bg-green-100 text-green-700 p-4 rounded mb-4">
                {{ session('status') }}
            </div>
        @endif

        <!-- DataTable Wrapper with extra padding -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg w-full p-6">
            <table id="contactsTable" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        ID
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Name
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Email
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Phone
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Facebook
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Instagram
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Action
                    </th>
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
                },
                type: "POST",  // <-- Make this a POST request
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'name', name: 'contact_name' },
                    { data: 'email', name: 'email' },
                    { data: 'phone', name: 'phone' },
                    { data: 'facebook', name: 'facebook' },
                    { data: 'instagram', name: 'instagram' },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            return `
                            <div class="flex space-x-2">
                                <a href="/contacts/${row.id}/edit"
                                   class="bg-yellow-500 text-white px-2 py-1 rounded-md shadow-sm hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                                    <i class="fas fa-pen"></i> Edit
                                </a>
                                <form action="/contacts/${row.id}" method="POST"
                                      onsubmit="return confirm('Are you sure you want to delete this contact?');"
                                      class="inline">
                                    @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="bg-red-600 text-white px-2 py-1 rounded-md shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
`;
                        }
                    }
                ],
                order: [[0, 'desc']],
                responsive: true,
                autoWidth: false,
            });
        });
    </script>
@endpush
