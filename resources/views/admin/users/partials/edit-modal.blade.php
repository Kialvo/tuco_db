<div id="editUserModal" class="hidden fixed inset-0 bg-black/50 flex justify-center items-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 relative">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold text-gray-700">Edit User</h2>
            <button id="closeEditModal" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
        </div>

        <form id="editUserForm" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-gray-600 font-medium">Name</label>
                <input type="text" name="name" id="edit_name" required
                       class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-green-500 focus:border-green-500">
                <p class="text-red-500 text-sm hidden" id="error_edit_name"></p>
            </div>

            <div class="mb-4">
                <label class="block text-gray-600 font-medium">Email</label>
                <input type="email" name="email" id="edit_email" required
                       class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-green-500 focus:border-green-500">
                <p class="text-red-500 text-sm hidden" id="error_edit_email"></p>
            </div>

            <div class="mb-6">
                <label class="block text-gray-600 font-medium">Role</label>
                <select name="role" id="edit_role"
                        class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-green-500 focus:border-green-500">
                    <option value="admin">Admin</option>
                    <option value="editor">Editor</option>
                    <option value="guest">Guest</option>
                </select>
                <p class="text-red-500 text-sm hidden" id="error_edit_role"></p>
            </div>

            <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded">
                <p class="text-sm text-gray-700 mb-2">
                    To change this user's password, click below. A temporary password will be emailed to them.
                    They will be required to set a new one on their next login.
                </p>
                <button type="button" id="btnResetPassword"
                        data-user-id=""
                        class="bg-amber-500 text-white px-4 py-2 rounded shadow hover:bg-amber-600 text-sm font-semibold">
                    <x-icon name="key" size="sm" class="inline me-1" /> Reset Password & Email
                </button>
            </div>

            <button type="submit"
                    class="w-full bg-blue-600 text-white py-3 rounded-lg shadow-md
                           hover:bg-blue-700 focus:outline-none focus:ring-2
                           focus:ring-offset-2 focus:ring-blue-500 text-lg font-semibold transition">
                Update User
            </button>
        </form>
    </div>
</div>
