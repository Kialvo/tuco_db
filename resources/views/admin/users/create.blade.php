<!-- resources/views/admin/users/create.blade.php -->
@extends('layouts.dashboard')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Create User</h1>

    @if(session('status'))
        <div class="mb-4 text-green-600">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
        @csrf

        <div>
            <label for="name" class="block mb-1">Name</label>
            <input type="text" name="name" id="name"
                   class="w-full border-gray-300 rounded px-2 py-1"
                   value="{{ old('name') }}" required>
            @error('name')
            <p class="text-red-500 text-sm">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="block mb-1">Email</label>
            <input type="email" name="email" id="email"
                   class="w-full border-gray-300 rounded px-2 py-1"
                   value="{{ old('email') }}" required>
            @error('email')
            <p class="text-red-500 text-sm">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block mb-1">Password</label>
            <input type="password" name="password" id="password"
                   class="w-full border-gray-300 rounded px-2 py-1" required>
            @error('password')
            <p class="text-red-500 text-sm">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block mb-1">Confirm Password</label>
            <input type="password" name="password_confirmation" id="password_confirmation"
                   class="w-full border-gray-300 rounded px-2 py-1" required>
        </div>

        <div>
            <label for="role" class="block mb-1">Role</label>
            <select name="role" id="role" class="w-full border-gray-300 rounded px-2 py-1">
                <option value="admin">Admin</option>
                <option value="editor" selected>Editor</option>
                <option value="guest">Guest</option>
            </select>
            @error('role')
            <p class="text-red-500 text-sm">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">
            Create User
        </button>
    </form>
@endsection
