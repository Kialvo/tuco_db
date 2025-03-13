@extends('layouts.dashboard')

@section('content')
    <div class="px-6 py-6 bg-gray-50 min-h-screen">
        <!-- Page Header -->
        <div class="mb-6 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-700">Manage Users</h1>

            <!-- Create User Button (Opens Modal) -->
            <button id="btnOpenModal"
                    class="bg-cyan-600 text-white px-6 py-3 rounded-lg shadow-lg
                      hover:bg-cyan-700 focus:outline-none focus:ring-2 focus:ring-offset-2
                      focus:ring-cyan-500 text-sm font-semibold transition-all">
                + Create New User
            </button>
        </div>

        <!-- User Table Wrapper -->
        <div class="bg-white border border-gray-200 rounded shadow-sm p-6">
            <table class="w-full text-sm text-left text-gray-700 border-collapse">
                <thead class="bg-gray-100 text-xs uppercase text-gray-600 tracking-wider border-b">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                @forelse($users as $user)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 font-medium">{{ $user->id }}</td>
                        <td class="px-4 py-3">{{ $user->name }}</td>
                        <td class="px-4 py-3">{{ $user->email }}</td>
                        <td class="px-4 py-3">{{ ucfirst($user->role) }}</td>
                        <td class="px-4 py-3 text-right flex justify-end space-x-3">
                            <!-- Edit Button -->
                            <button type="button"
                                    class="editBtn bg-blue-500 text-white px-3 py-2 rounded-md shadow flex items-center space-x-2
               hover:bg-blue-600 transition focus:outline-none focus:ring-2 focus:ring-blue-400"
                                    data-user-id="{{ $user->id }}">
                                <i class="fas fa-edit"></i>
                                <span>Edit</span>
                            </button>


                            <!-- Delete Button -->
                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST"
                                  class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="bg-red-500 text-white px-3 py-2 rounded-md shadow flex items-center space-x-2
                       hover:bg-red-600 transition focus:outline-none focus:ring-2 focus:ring-red-400"
                                        onclick="return confirm('Are you sure you want to delete this user?')">
                                    <i class="fas fa-trash"></i>
                                    <span>Delete</span>
                                </button>
                            </form>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-4 text-center text-gray-500">No users found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Include Create User Modal -->
    @include('admin.users.partials.create-modal')
    @include('admin.users.partials.edit-modal')


@endsection

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // ========================
            // CREATE USER MODAL LOGIC
            // ========================
            let createModal = document.getElementById("createUserModal");
            let btnOpenModal = document.getElementById("btnOpenModal");
            let btnCloseModal = document.getElementById("closeModal");
            let createForm = document.getElementById("createUserForm");

            if (btnOpenModal && createModal) {
                btnOpenModal.addEventListener("click", function () {
                    createModal.classList.remove("hidden");
                    createModal.classList.add("flex");
                });
            }

            if (btnCloseModal && createModal) {
                btnCloseModal.addEventListener("click", function () {
                    createModal.classList.add("hidden");
                    createModal.classList.remove("flex");
                });

                createModal.addEventListener("click", function (e) {
                    if (e.target === createModal) {
                        createModal.classList.add("hidden");
                        createModal.classList.remove("flex");
                    }
                });
            }

            // Handle Create Form Submission via AJAX
            if (createForm) {
                createForm.addEventListener("submit", function (event) {
                    event.preventDefault(); // Prevent default form submission

                    let formData = new FormData(createForm);
                    let actionUrl = createForm.getAttribute("action");

                    fetch(actionUrl, {
                        method: "POST",
                        headers: {
                            "X-Requested-With": "XMLHttpRequest",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                        },
                        body: formData,
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === "success") {
                                Swal.fire({
                                    icon: "success",
                                    title: "Success",
                                    text: "User created successfully!",
                                    timer: 3000,
                                    timerProgressBar: true,
                                    showConfirmButton: false,
                                });

                                // Hide modal and reload page to show the new user
                                createModal.classList.add("hidden");
                                createModal.classList.remove("flex");

                                setTimeout(() => {
                                    location.reload();
                                }, 1000);
                            } else {
                                Swal.fire({
                                    icon: "error",
                                    title: "Error",
                                    text: data.message || "User creation failed.",
                                });
                            }
                        })
                        .catch(error => {
                            console.error("Error creating user:", error);
                            Swal.fire({
                                icon: "error",
                                title: "Error",
                                text: "User creation failed.",
                            });
                        });
                });
            }

            // ========================
            // EDIT USER MODAL LOGIC
            // ========================
            let editModal = document.getElementById("editUserModal");
            let btnCloseEditModal = document.getElementById("closeEditModal");
            let editForm = document.getElementById("editUserForm");

            document.body.addEventListener("click", function (event) {
                let button = event.target.closest(".editBtn");
                if (!button) return;

                let userId = button.getAttribute("data-user-id");

                if (!editModal || !editForm) {
                    console.error("Edit modal or form not found.");
                    return;
                }

                // Fetch user data via AJAX
                fetch(`/admin/users/${userId}/edit-ajax`, {
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                    },
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === "success") {
                            let user = data.data;
                            editForm.action = `/admin/users/${user.id}`;
                            document.getElementById("edit_name").value = user.name;
                            document.getElementById("edit_email").value = user.email;
                            document.getElementById("edit_role").value = user.role;

                            editModal.classList.remove("hidden");
                            editModal.classList.add("flex");
                        } else {
                            Swal.fire({
                                icon: "error",
                                title: "Error",
                                text: "User data could not be loaded.",
                            });
                        }
                    })
                    .catch(error => {
                        console.error("Error fetching user:", error);
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: "User data could not be loaded.",
                        });
                    });
            });

            if (btnCloseEditModal && editModal) {
                btnCloseEditModal.addEventListener("click", function () {
                    editModal.classList.add("hidden");
                    editModal.classList.remove("flex");
                });

                editModal.addEventListener("click", function (e) {
                    if (e.target === editModal) {
                        editModal.classList.add("hidden");
                        editModal.classList.remove("flex");
                    }
                });
            }

            // Handle Edit Form Submission via AJAX
            if (editForm) {
                editForm.addEventListener("submit", function (event) {
                    event.preventDefault(); // Prevent default form submission

                    let formData = new FormData(editForm);
                    let actionUrl = editForm.getAttribute("action");

                    fetch(actionUrl, {
                        method: "POST",
                        headers: {
                            "X-Requested-With": "XMLHttpRequest",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                        },
                        body: formData,
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === "success") {
                                Swal.fire({
                                    icon: "success",
                                    title: "Success",
                                    text: "User updated successfully!",
                                    timer: 3000,
                                    timerProgressBar: true,
                                    showConfirmButton: false,
                                });

                                // Hide modal and reload page
                                editModal.classList.add("hidden");
                                editModal.classList.remove("flex");

                                setTimeout(() => {
                                    location.reload();
                                }, 1000);
                            } else {
                                Swal.fire({
                                    icon: "error",
                                    title: "Update Failed",
                                    text: data.message || "Something went wrong!",
                                });
                            }
                        })
                        .catch(error => {
                            console.error("Error updating user:", error);
                            Swal.fire({
                                icon: "error",
                                title: "Error",
                                text: "User update failed.",
                            });
                        });
                });
            }

            // ========================
            // SUCCESS MESSAGE ALERTS
            // ========================
            @if (session('status'))
            Swal.fire({
                icon: "success",
                title: "Success",
                text: "{{ session('status') }}",
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false,
            });
            @endif
        });

    </script>
@endpush


