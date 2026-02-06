<!-- resources/views/admin/users/edit.blade.php -->
@extends('layouts.dashboard')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Edit User: {{ $user->name }}</h1>

    @if(session('status'))
        <div class="mb-4 text-green-600">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 text-red-600">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.users.update', $user->id) }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="block mb-1">Name</label>
            <input type="text" name="name" id="name"
                   class="w-full border-gray-300 rounded px-2 py-1"
                   value="{{ old('name', $user->name) }}" required>
            @error('name')
            <p class="text-red-500 text-sm">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="block mb-1">Email</label>
            <input type="email" name="email" id="email"
                   class="w-full border-gray-300 rounded px-2 py-1"
                   value="{{ old('email', $user->email) }}" required>
            @error('email')
            <p class="text-red-500 text-sm">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block mb-1">New Password (Optional)</label>
            <input type="password" name="password" id="password"
                   class="w-full border-gray-300 rounded px-2 py-1">
            @error('password')
            <p class="text-red-500 text-sm">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block mb-1">Confirm New Password</label>
            <input type="password" name="password_confirmation" id="password_confirmation"
                   class="w-full border-gray-300 rounded px-2 py-1">
        </div>

        <div>
            <label for="role" class="block mb-1">Role</label>
            <select name="role" id="role" class="w-full border-gray-300 rounded px-2 py-1">
                <option value="admin" @if($user->role === 'admin') selected @endif>Admin</option>
                <option value="editor" @if($user->role === 'editor') selected @endif>Editor</option>
                <option value="guest" @if($user->role === 'guest') selected @endif>Guest</option>
            </select>
            @error('role')
            <p class="text-red-500 text-sm">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">
            Update User
        </button>
    </form>
@endsection
