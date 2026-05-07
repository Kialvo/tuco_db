@extends('layouts.dashboard')

@section('content')
    <div class="px-6 py-6 bg-gray-50 min-h-screen">
        <div class="mb-6 flex flex-wrap justify-between items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-700">Manage Users</h1>

            <button id="btnOpenModal"
                    class="bg-cyan-600 text-white px-6 py-3 rounded-lg shadow-lg
                      hover:bg-cyan-700 focus:outline-none focus:ring-2 focus:ring-offset-2
                      focus:ring-cyan-500 text-sm font-semibold transition-all">
                + Create New User
            </button>
        </div>

        {{-- Tabs --}}
        <div class="flex gap-2 mb-4 border-b border-gray-200">
            <button data-tab="system"
                    class="user-tab px-5 py-2 text-sm font-semibold border-b-2 transition
                           {{ ($tab ?? 'system') === 'system' ? 'border-cyan-600 text-cyan-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                <i class="fas fa-user-shield mr-1"></i> System Users
            </button>
            <button data-tab="guests"
                    class="user-tab px-5 py-2 text-sm font-semibold border-b-2 transition
                           {{ ($tab ?? 'system') === 'guests' ? 'border-cyan-600 text-cyan-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                <i class="fas fa-user-friends mr-1"></i> Guests
            </button>
        </div>

        <div class="bg-white border border-gray-200 rounded shadow-sm p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex items-center w-72 border border-gray-300 rounded-md bg-white shadow-sm
                            focus-within:ring-1 focus-within:ring-cyan-500 focus-within:border-cyan-500">
                    <span class="px-3 text-gray-400 text-base leading-none">
                        <i class="fas fa-search"></i>
                    </span>
                    <input id="usersTableSearch" type="text"
                           class="w-full bg-transparent border-0 focus:ring-0 focus:outline-none py-2 pr-3 text-sm leading-5"
                           placeholder="Search users...">
                </div>
            </div>

            <table id="usersTable" class="w-full text-sm text-left text-gray-700 border-collapse">
                <thead class="bg-gray-100 text-xs uppercase text-gray-600 tracking-wider border-b">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Verified</th>
                    <th class="px-4 py-3">Google</th>
                    <th class="px-4 py-3">Registered</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200"></tbody>
            </table>
        </div>
    </div>

    @include('admin.users.partials.create-modal')
    @include('admin.users.partials.edit-modal')
@endsection

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
            let currentTab = @json($tab ?? 'system');

            const usersTable = $('#usersTable').DataTable({
                processing: true,
                serverSide: true,
                searching: true,
                lengthChange: false,
                pageLength: 25,
                order: [[0, 'desc']],
                ajax: {
                    url: '{{ route('admin.users.data') }}',
                    data: (d) => { d.tab = currentTab; }
                },
                columns: [
                    { data: 'id' },
                    { data: 'name' },
                    { data: 'email' },
                    { data: 'role', render: r => r ? r.charAt(0).toUpperCase() + r.slice(1) : '' },
                    { data: 'email_verified_at' },
                    { data: 'google_id' },
                    { data: 'created_at' },
                    {
                        data: 'id',
                        orderable: false,
                        searchable: false,
                        className: 'text-right',
                        render: function (id, type, row) {
                            const favBtn = (row.role === 'guest')
                                ? `<a href="/admin/users/${id}/favorites" class="bg-amber-500 hover:bg-amber-600 text-white px-2 py-1 rounded text-xs"><i class="fas fa-star"></i> Favorites</a>`
                                : '';
                            return `
                                <div class="flex items-center justify-end gap-2">
                                    ${favBtn}
                                    <button class="editBtn bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded text-xs" data-user-id="${id}"><i class="fas fa-edit"></i> Edit</button>
                                    <button class="resetPwdBtn bg-amber-600 hover:bg-amber-700 text-white px-2 py-1 rounded text-xs" data-user-id="${id}" data-user-email="${row.email}"><i class="fas fa-key"></i> Reset PW</button>
                                    <form action="/admin/users/${id}" method="POST" class="inline-block">
                                        <input type="hidden" name="_token" value="${csrfToken}">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-xs" onclick="return confirm('Delete this user?')"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>`;
                        }
                    }
                ],
                dom: "t<'flex items-center justify-between mt-4'<'dt-info'i><'dt-pagination'p>>",
            });

            // Tab switching
            document.querySelectorAll('.user-tab').forEach(btn => {
                btn.addEventListener('click', () => {
                    currentTab = btn.dataset.tab;
                    document.querySelectorAll('.user-tab').forEach(b => {
                        b.classList.remove('border-cyan-600', 'text-cyan-700');
                        b.classList.add('border-transparent', 'text-gray-500');
                    });
                    btn.classList.remove('border-transparent', 'text-gray-500');
                    btn.classList.add('border-cyan-600', 'text-cyan-700');
                    usersTable.ajax.reload();
                });
            });

            // Search
            const searchInput = document.getElementById('usersTableSearch');
            if (searchInput) {
                let t;
                searchInput.addEventListener('input', function () {
                    clearTimeout(t);
                    t = setTimeout(() => usersTable.search(this.value).draw(), 250);
                });
            }

            // Inline error helpers
            const setInlineError = (id, message) => {
                const node = document.getElementById(id);
                if (!node) return;
                if (!message) { node.textContent = ''; node.classList.add('hidden'); return; }
                node.textContent = message;
                node.classList.remove('hidden');
            };
            const clearInlineErrors = (ids) => ids.forEach(id => setInlineError(id, null));
            const getFirstMessage = (errors, key) => (errors && errors[key] && errors[key][0]) || null;

            // ===== CREATE MODAL =====
            const createModal = document.getElementById("createUserModal");
            const btnOpenModal = document.getElementById("btnOpenModal");
            const btnCloseModal = document.getElementById("closeModal");
            const createForm = document.getElementById("createUserForm");
            const createErrorIds = ['error_create_name','error_create_email','error_create_password','error_create_password_confirmation','error_create_role'];

            btnOpenModal?.addEventListener("click", () => {
                clearInlineErrors(createErrorIds);
                createForm?.reset();
                createModal.classList.remove("hidden");
                createModal.classList.add("flex");
            });
            btnCloseModal?.addEventListener("click", () => {
                clearInlineErrors(createErrorIds);
                createModal.classList.add("hidden");
                createModal.classList.remove("flex");
            });
            createModal?.addEventListener("click", (e) => {
                if (e.target === createModal) {
                    clearInlineErrors(createErrorIds);
                    createModal.classList.add("hidden");
                    createModal.classList.remove("flex");
                }
            });

            createForm?.addEventListener("submit", async (e) => {
                e.preventDefault();
                clearInlineErrors(createErrorIds);

                try {
                    const response = await fetch(createForm.action, {
                        method: "POST",
                        headers: { "Accept": "application/json", "X-Requested-With": "XMLHttpRequest", "X-CSRF-TOKEN": csrfToken },
                        body: new FormData(createForm),
                    });
                    const data = await response.json();
                    if (response.ok && data.status === "success") {
                        Swal.fire({ icon: "success", title: "User created", timer: 1500, showConfirmButton: false });
                        createModal.classList.add("hidden");
                        createModal.classList.remove("flex");
                        usersTable.ajax.reload();
                        return;
                    }
                    if (response.status === 422 && data.errors) {
                        setInlineError('error_create_name', getFirstMessage(data.errors, 'name'));
                        setInlineError('error_create_email', getFirstMessage(data.errors, 'email'));
                        setInlineError('error_create_password', getFirstMessage(data.errors, 'password'));
                        setInlineError('error_create_password_confirmation', getFirstMessage(data.errors, 'password_confirmation'));
                        setInlineError('error_create_role', getFirstMessage(data.errors, 'role'));
                    }
                } catch (err) {
                    console.error(err);
                    setInlineError('error_create_email', 'Could not create user.');
                }
            });

            // ===== EDIT MODAL =====
            const editModal = document.getElementById("editUserModal");
            const btnCloseEditModal = document.getElementById("closeEditModal");
            const editForm = document.getElementById("editUserForm");
            const btnResetPassword = document.getElementById("btnResetPassword");
            const editErrorIds = ['error_edit_name','error_edit_email','error_edit_role'];

            document.body.addEventListener("click", (event) => {
                const button = event.target.closest(".editBtn");
                if (!button) return;
                const userId = button.dataset.userId;
                fetch(`/admin/users/${userId}/edit-ajax`, {
                    headers: { "X-Requested-With": "XMLHttpRequest", "X-CSRF-TOKEN": csrfToken },
                })
                    .then(r => r.json())
                    .then(d => {
                        if (d.status !== "success") return;
                        const u = d.data;
                        editForm.action = `/admin/users/${u.id}`;
                        document.getElementById("edit_name").value = u.name;
                        document.getElementById("edit_email").value = u.email;
                        document.getElementById("edit_role").value = u.role;
                        btnResetPassword.dataset.userId = u.id;
                        btnResetPassword.dataset.userEmail = u.email;
                        clearInlineErrors(editErrorIds);
                        editModal.classList.remove("hidden");
                        editModal.classList.add("flex");
                    });
            });

            btnCloseEditModal?.addEventListener("click", () => {
                clearInlineErrors(editErrorIds);
                editModal.classList.add("hidden");
                editModal.classList.remove("flex");
            });
            editModal?.addEventListener("click", (e) => {
                if (e.target === editModal) {
                    clearInlineErrors(editErrorIds);
                    editModal.classList.add("hidden");
                    editModal.classList.remove("flex");
                }
            });

            editForm?.addEventListener("submit", async (e) => {
                e.preventDefault();
                clearInlineErrors(editErrorIds);
                try {
                    const response = await fetch(editForm.action, {
                        method: "POST",
                        headers: { "Accept": "application/json", "X-Requested-With": "XMLHttpRequest", "X-CSRF-TOKEN": csrfToken },
                        body: new FormData(editForm),
                    });
                    const data = await response.json();
                    if (response.ok && data.status === "success") {
                        editModal.classList.add("hidden");
                        editModal.classList.remove("flex");
                        usersTable.ajax.reload();
                        return;
                    }
                    if (response.status === 422 && data.errors) {
                        setInlineError('error_edit_name', getFirstMessage(data.errors, 'name'));
                        setInlineError('error_edit_email', getFirstMessage(data.errors, 'email'));
                        setInlineError('error_edit_role', getFirstMessage(data.errors, 'role'));
                    }
                } catch (err) { console.error(err); }
            });

            // ===== RESET PASSWORD =====
            const triggerResetPassword = async (userId, email) => {
                const confirm = await Swal.fire({
                    icon: 'warning',
                    title: 'Reset password?',
                    text: `A temporary password will be emailed to ${email}. The user will need to change it on their next login.`,
                    showCancelButton: true,
                    confirmButtonText: 'Yes, reset & email',
                    confirmButtonColor: '#d97706',
                });
                if (!confirm.isConfirmed) return;

                try {
                    const response = await fetch(`/admin/users/${userId}/reset-password`, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
                    });
                    const data = await response.json();
                    if (response.ok && data.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'Password reset', text: data.message, timer: 2500, showConfirmButton: false });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Failed', text: data.message || 'Could not reset password.' });
                    }
                } catch (err) {
                    Swal.fire({ icon: 'error', title: 'Failed', text: 'Network error.' });
                }
            };

            btnResetPassword?.addEventListener("click", () => {
                const userId = btnResetPassword.dataset.userId;
                const email = btnResetPassword.dataset.userEmail;
                if (userId) triggerResetPassword(userId, email);
            });

            document.body.addEventListener("click", (e) => {
                const btn = e.target.closest(".resetPwdBtn");
                if (!btn) return;
                triggerResetPassword(btn.dataset.userId, btn.dataset.userEmail);
            });
        });
    </script>
@endpush
