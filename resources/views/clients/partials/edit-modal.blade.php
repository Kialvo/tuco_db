{{-- resources/views/clients/partials/edit-modal.blade.php --}}

<!-- EDIT CLIENT MODAL (hidden by default) -->
<div id="editModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white border border-gray-200 p-6 rounded shadow-sm max-w-md w-full mx-2 relative">

        <!-- Close “X” -->
        <button type="button" id="editModalCloseBtn"
                class="absolute top-2 right-2 text-gray-400 hover:text-gray-600 focus:outline-none">
            <i class="fas fa-times"></i>
        </button>

        <h2 class="text-xl font-bold text-gray-800 mb-4">Edit Client</h2>

        <!-- Error box (populated via JS if needed) -->
        <div id="editModalErrors"
             class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4 hidden"></div>

        <!-- Edit form (action + client‑ID injected by JS) -->
        <form id="editClientForm" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            {{-- First Name --}}
            <div>
                <label for="edit_first_name" class="block text-sm font-medium text-gray-700 mb-1">
                    First Name <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="edit_first_name"
                    name="first_name"
                    required
                    class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2
                           focus:ring-cyan-500 focus:border-cyan-500"
                />
            </div>

            {{-- Last Name --}}
            <div>
                <label for="edit_last_name" class="block text-sm font-medium text-gray-700 mb-1">
                    Last Name <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="edit_last_name"
                    name="last_name"
                    required
                    class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2
                           focus:ring-cyan-500 focus:border-cyan-500"
                />
            </div>

            {{-- Email --}}
            <div>
                <label for="edit_email" class="block text-sm font-medium text-gray-700 mb-1">
                    Email <span class="text-red-500">*</span>
                </label>
                <input
                    type="email"
                    id="edit_email"
                    name="email"
                    required
                    class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2
                           focus:ring-cyan-500 focus:border-cyan-500"
                />
            </div>

            {{-- Company --}}
            <div>
                <label for="edit_company" class="block text-sm font-medium text-gray-700 mb-1">
                    Company
                </label>
                <input
                    type="text"
                    id="edit_company"
                    name="company"
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
                    Update Client
                </button>
            </div>
        </form>
    </div>
</div>
