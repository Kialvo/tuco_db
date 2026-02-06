<div id="editUserModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
        <!-- Modal Header -->
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold text-gray-700">Edit User</h2>
            <button id="closeEditModal" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
        </div>

        <!-- Edit User Form -->
        <form id="editUserForm" method="POST">
            @csrf
            @method('PUT')

            <!-- Name -->
            <div class="mb-4">
                <label class="block text-gray-600 font-medium">Name</label>
                <input type="text" name="name" id="edit_name" required
                       class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-cyan-500 focus:border-cyan-500">
                <p class="text-red-500 text-sm hidden" id="error_edit_name"></p>
            </div>

            <!-- Email -->
            <div class="mb-4">
                <label class="block text-gray-600 font-medium">Email</label>
                <input type="email" name="email" id="edit_email" required
                       class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-cyan-500 focus:border-cyan-500">
                <p class="text-red-500 text-sm hidden" id="error_edit_email"></p>
            </div>

            <!-- Password (Optional) -->
            <div class="mb-4">
                <label class="block text-gray-600 font-medium">New Password (Optional)</label>
                <input type="password" name="password" id="edit_password"
                       class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-cyan-500 focus:border-cyan-500">
            </div>

            <!-- Confirm Password -->
            <div class="mb-4">
                <label class="block text-gray-600 font-medium">Confirm New Password</label>
                <input type="password" name="password_confirmation" id="edit_password_confirmation"
                       class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-cyan-500 focus:border-cyan-500">
            </div>

            <!-- Role -->
            <div class="mb-6">
                <label class="block text-gray-600 font-medium">Role</label>
                <select name="role" id="edit_role"
                        class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-cyan-500 focus:border-cyan-500">
                    <option value="admin">Admin</option>
                    <option value="editor">Editor</option>
                    <option value="guest">Guest</option>
                </select>
            </div>

            <!-- Submit Button -->
            <button type="submit"
                    class="w-full bg-blue-600 text-white py-3 rounded-lg shadow-md
                           hover:bg-blue-700 focus:outline-none focus:ring-2
                           focus:ring-offset-2 focus:ring-blue-500 text-lg font-semibold transition">
                Update User
            </button>
        </form>
    </div>
</div>
