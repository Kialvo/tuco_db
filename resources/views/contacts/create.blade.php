@extends('layouts.dashboard')

@section('content')
    <!-- Outer container with consistent background & spacing -->
    <div class="px-6 py-6 bg-gray-50 min-h-screen">
        <!-- Inner card to visually separate the form -->
        <div class="max-w-xl mx-auto bg-white border border-gray-200 p-6 rounded shadow-sm">
            <h1 class="text-xl font-bold text-gray-800 mb-4">Add New Publisher</h1>

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
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
                        Save Publisher
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
