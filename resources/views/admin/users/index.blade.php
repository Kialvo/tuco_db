<!-- resources/views/admin/users/index.blade.php -->
@extends('layouts.dashboard')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Manage Users</h1>

    @if(session('status'))
        <div class="mb-4 text-green-600">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 text-red-600">{{ session('error') }}</div>
    @endif

    <a href="{{ route('admin.users.create') }}"
       class="inline-block px-4 py-2 bg-blue-600 text-white rounded mb-4">
        Create New User
    </a>

    <table class="min-w-full border-collapse">
        <thead>
        <tr class="border-b">
            <th class="px-4 py-2 text-left">ID</th>
            <th class="px-4 py-2 text-left">Name</th>
            <th class="px-4 py-2 text-left">Email</th>
            <th class="px-4 py-2 text-left">Role</th>
            <th class="px-4 py-2"></th>
        </tr>
        </thead>
        <tbody>
        @forelse($users as $user)
            <tr class="border-b">
                <td class="px-4 py-2">{{ $user->id }}</td>
                <td class="px-4 py-2">{{ $user->name }}</td>
                <td class="px-4 py-2">{{ $user->email }}</td>
                <td class="px-4 py-2">{{ $user->role }}</td>
                <td class="px-4 py-2">
                    <a href="{{ route('admin.users.edit', $user->id) }}" class="text-blue-600 hover:underline">Edit</a>

                    <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="inline-block ml-2">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline"
                                onclick="return confirm('Are you sure you want to delete this user?')">
                            Delete
                        </button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-4 py-2 text-center">No users found.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
@endsection
