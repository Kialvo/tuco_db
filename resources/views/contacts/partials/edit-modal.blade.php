<!-- resources/views/contacts/partials/edit-modal.blade.php -->

<div id="editModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white border border-gray-200 p-6 rounded shadow-sm max-w-md w-full mx-2 relative">
        <!-- Close 'X' -->
        <button type="button" id="editModalCloseBtn"
                class="absolute top-2 right-2 text-gray-400 hover:text-gray-600 focus:outline-none">
            <i class="fas fa-times"></i>
        </button>

        <h2 class="text-xl font-bold text-gray-800 mb-4">Edit Contact</h2>

        <!-- Potential error display if using AJAX validation -->
        <div id="editModalErrors" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4 hidden">
            <!-- We'll fill with error messages if needed -->
        </div>

        <!-- Edit Form (method PUT) but we'll set the action via JS -->
        <form id="editContactForm" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="edit_name" class="block text-sm font-medium text-gray-700 mb-1">
                    Name <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="edit_name"
                    name="name"
                    required
                    class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-cyan-500 focus:border-cyan-500"
                />
            </div>

            <div>
                <label for="edit_email" class="block text-sm font-medium text-gray-700 mb-1">
                    Email <span class="text-red-500">*</span>
                </label>
                <input
                    type="email"
                    id="edit_email"
                    name="email"
                    required
                    class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-cyan-500 focus:border-cyan-500"
                />
            </div>

            <div>
                <label for="edit_phone" class="block text-sm font-medium text-gray-700 mb-1">
                    Phone
                </label>
                <input
                    type="text"
                    id="edit_phone"
                    name="phone"
                    class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-cyan-500 focus:border-cyan-500"
                />
            </div>

            <div>
                <label for="edit_facebook" class="block text-sm font-medium text-gray-700 mb-1">
                    Facebook URL
                </label>
                <input
                    type="url"
                    id="edit_facebook"
                    name="facebook"
                    class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-cyan-500 focus:border-cyan-500"
                />
            </div>

            <div>
                <label for="edit_instagram" class="block text-sm font-medium text-gray-700 mb-1">
                    Instagram URL
                </label>
                <input
                    type="url"
                    id="edit_instagram"
                    name="instagram"
                    class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-cyan-500 focus:border-cyan-500"
                />
            </div>

            <div class="pt-2">
                <button
                    type="submit"
                    class="bg-cyan-600 text-white px-5 py-2 rounded shadow-sm hover:bg-cyan-700
                           focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 text-sm"
                >
                    Update Contact
                </button>
            </div>
        </form>
    </div>
</div>
