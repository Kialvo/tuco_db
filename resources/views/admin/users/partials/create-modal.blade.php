<div id="createUserModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
        <!-- Modal Header -->
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold text-gray-700">Create User</h2>
            <button id="closeModal" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
        </div>

        <!-- Form -->
        <form id="createUserForm" action="{{ route('admin.users.store') }}" method="POST" novalidate>
            @csrf

            <!-- Name -->
            <div class="mb-4">
                <label for="create_name" class="block text-gray-600 font-medium">Name</label>
                <input type="text" name="name" id="create_name" required
                       class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-cyan-500 focus:border-cyan-500">
                <p class="text-red-500 text-sm hidden" id="error_create_name"></p>
            </div>

            <!-- Email -->
            <div class="mb-4">
                <label for="create_email" class="block text-gray-600 font-medium">Email</label>
                <input type="email" name="email" id="create_email" required
                       class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-cyan-500 focus:border-cyan-500">
                <p class="text-red-500 text-sm hidden" id="error_create_email"></p>
            </div>

            <!-- Password -->
            <div class="mb-4">
                <label for="create_password" class="block text-gray-600 font-medium">Password</label>
                <input type="password" name="password" id="create_password" required minlength="8"
                       placeholder="Minimum 8 characters"
                       class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-cyan-500 focus:border-cyan-500">
                <p class="text-red-500 text-sm hidden" id="error_create_password"></p>
            </div>

            <!-- Confirm Password -->
            <div class="mb-4">
                <label for="create_password_confirmation" class="block text-gray-600 font-medium">Confirm Password</label>
                <input type="password" name="password_confirmation" id="create_password_confirmation" required minlength="8"
                       class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-cyan-500 focus:border-cyan-500">
                <p class="text-red-500 text-sm hidden" id="error_create_password_confirmation"></p>
            </div>

            <!-- Role -->
            <div class="mb-6">
                <label for="create_role" class="block text-gray-600 font-medium">Role</label>
                <select name="role" id="create_role"
                        class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-cyan-500 focus:border-cyan-500">
                    <option value="admin">Admin</option>
                    <option value="editor" selected>Editor</option>
                    <option value="guest">Guest</option>
                </select>
                <p class="text-red-500 text-sm hidden" id="error_create_role"></p>
            </div>

            <!-- Submit Button -->
            <button type="submit"
                    class="w-full bg-blue-600 text-white py-3 rounded-lg shadow-md
                           hover:bg-blue-700 focus:outline-none focus:ring-2
                           focus:ring-offset-2 focus:ring-blue-500 text-lg font-semibold transition">
                Save User
            </button>
        </form>
    </div>
</div>
