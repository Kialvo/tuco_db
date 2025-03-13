<!-- CREATE CONTACT MODAL (hidden by default) -->
<div id="createModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <!-- The actual modal card -->
    <div class="bg-white border border-gray-200 p-6 rounded shadow-sm max-w-md w-full mx-2 relative">

        <!-- Close 'X' button in top-right corner -->
        <button id="modalCloseBtn" type="button"
                class="absolute top-2 right-2 text-gray-400 hover:text-gray-600 focus:outline-none">
            <i class="fas fa-times"></i>
        </button>

        <h2 class="text-xl font-bold text-gray-800 mb-4">Add New Contact</h2>

        {{-- Error display (only shows if user returns with validation errors) --}}
        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Contact Creation Form -->
        <form action="{{ route('contacts.store') }}" method="POST" class="space-y-5">
            @csrf

            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                    Name <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2
                           focus:ring-cyan-500 focus:border-cyan-500"
                />
            </div>

            <!-- Email -->
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

            <!-- Phone -->
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                    Phone
                </label>
                <input
                    type="text"
                    name="phone"
                    value="{{ old('phone') }}"
                    class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2
                           focus:ring-cyan-500 focus:border-cyan-500"
                />
            </div>

            <!-- Facebook URL -->
            <div>
                <label for="facebook" class="block text-sm font-medium text-gray-700 mb-1">
                    Facebook URL
                </label>
                <input
                    type="url"
                    name="facebook"
                    value="{{ old('facebook') }}"
                    class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2
                           focus:ring-cyan-500 focus:border-cyan-500"
                />
            </div>

            <!-- Instagram URL -->
            <div>
                <label for="instagram" class="block text-sm font-medium text-gray-700 mb-1">
                    Instagram URL
                </label>
                <input
                    type="url"
                    name="instagram"
                    value="{{ old('instagram') }}"
                    class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2
                           focus:ring-cyan-500 focus:border-cyan-500"
                />
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button
                    type="submit"
                    class="bg-cyan-600 text-white px-5 py-2 rounded shadow-sm hover:bg-cyan-700
                           focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 text-sm"
                >
                    Save Contact
                </button>
            </div>
        </form>
    </div>
</div>
