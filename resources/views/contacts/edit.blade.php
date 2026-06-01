@extends('layouts.dashboard')

@section('content')
    <div class="container">
        <h1>Edit Publisher</h1>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('contacts.update', $contact->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label for="name" class="form-label">Name *</label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $contact->name) }}" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email *</label>
                <input type="email" class="form-control" name="email" value="{{ old('email', $contact->email) }}" required>
            </div>
            <div class="mb-3">
                <label for="phone" class="form-label">Phone</label>
                <input type="text" class="form-control" name="phone" value="{{ old('phone', $contact->phone) }}">
            </div>
            <div class="mb-3">
                <label for="facebook" class="form-label">Facebook URL</label>
                <input type="url" class="form-control" name="facebook" value="{{ old('facebook', $contact->facebook) }}">
            </div>
            <div class="mb-3">
                <label for="instagram" class="form-label">Instagram URL</label>
                <input type="url" class="form-control" name="instagram" value="{{ old('instagram', $contact->instagram) }}">
            </div>
            <button type="submit" class="btn btn-primary">Update Publisher</button>
        </form>
    </div>
@endsection
