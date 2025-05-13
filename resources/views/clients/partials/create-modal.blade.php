{{-- resources/views/clients/partials/create-modal.blade.php --}}

<!-- CREATE CLIENT MODAL (hidden by default) -->
<div id="createModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <!-- Modal card -->
    <div class="bg-white border border-gray-200 p-6 rounded shadow-sm max-w-md w-full mx-2 relative">

        <!-- Close “X” button -->
        <button id="modalCloseBtn" type="button"
                class="absolute top-2 right-2 text-gray-400 hover:text-gray-600 focus:outline-none">
            <i class="fas fa-times"></i>
        </button>

        <h2 class="text-xl font-bold text-gray-800 mb-4">Add New Client</h2>

        {{-- Validation errors --}}
        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Client creation form -->
        <form action="{{ route('clients.store') }}" method="POST" class="space-y-5">
            @csrf

            {{-- First Name --}}
            <div>
                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">
                    First Name <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    name="first_name"
                    value="{{ old('first_name') }}"
                    required
                    class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2
                           focus:ring-cyan-500 focus:border-cyan-500"
                />
            </div>

            {{-- Last Name --}}
            <div>
                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">
                    Last Name <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    name="last_name"
                    value="{{ old('last_name') }}"
                    required
                    class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2
                           focus:ring-cyan-500 focus:border-cyan-500"
                />
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                    Email <span class="text-red-500">*</span>
                </label>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2
                           focus:ring-cyan-500 focus:border-cyan-500"
                />
            </div>

            {{-- Company --}}
            <div>
                <label for="company" class="block text-sm font-medium text-gray-700 mb-1">
                    Company
                </label>
                <input
                    type="text"
                    name="company"
                    value="{{ old('company') }}"
                    class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2
                           focus:ring-cyan-500 focus:border-cyan-500"
                />
            </div>

            {{-- Submit --}}
            <div class="pt-2">
                <button
                    type="submit"
                    class="bg-cyan-600 text-white px-5 py-2 rounded shadow-sm hover:bg-cyan-700
                           focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 text-sm">
                    Save Client
                </button>
            </div>
        </form>
    </div>
</div>
