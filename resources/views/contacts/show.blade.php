@extends('layouts.dashboard')

@section('content')
    <div class="container">
        <h1>Contacts</h1>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <a href="{{ route('contacts.create') }}" class="btn btn-primary mb-3">Add New Contact</a>

        <table class="table table-bordered">
            <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Facebook</th>
                <th>Instagram</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @foreach($contacts as $contact)
                <tr>
                    <td>{{ $contact->name }}</td>
                    <td>{{ $contact->email }}</td>
                    <td>{{ $contact->phone }}</td>
                    <td>
                        @if($contact->facebook)
                            <a href="{{ $contact->facebook }}" target="_blank">View</a>
                        @endif
                    </td>
                    <td>
                        @if($contact->instagram)
                            <a href="{{ $contact->instagram }}" target="_blank">View</a>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('contacts.edit', $contact->id) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('contacts.destroy', $contact->id) }}" method="POST" style="display:inline-block;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger"
                                    onclick="return confirm('Are you sure you want to delete this contact?');">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
