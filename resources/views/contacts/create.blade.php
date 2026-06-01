@extends('layouts.dashboard')
@section('title', 'Add Publisher')

@section('pageHeader')
    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between flex-shrink-0">
        <div>
            <h1 class="text-base font-bold text-gray-800">Add Publisher</h1>
            <p class="text-xs text-gray-500 mt-0.5">Create a new publisher contact.</p>
        </div>
        <a href="{{ route('contacts.index') }}"
           class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300">
            <x-icon name="arrow-left" size="sm" /> Back
        </a>
    </div>
@endsection

@section('content')
    <div class="px-6 py-6 bg-gray-50 min-h-screen">
        <div class="form-card max-w-2xl mx-auto">
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('contacts.store') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="name">Name <span class="text-red-500">*</span></label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required />
                </div>

                <div>
                    <label for="email">Email <span class="text-red-500">*</span></label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required />
                </div>

                <div>
                    <label for="phone">Phone</label>
                    <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" />
                </div>

                <div>
                    <label for="facebook">Facebook URL</label>
                    <input id="facebook" type="url" name="facebook" value="{{ old('facebook') }}" />
                </div>

                <div>
                    <label for="instagram">Instagram URL</label>
                    <input id="instagram" type="url" name="instagram" value="{{ old('instagram') }}" />
                </div>

                <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-100 mt-6">
                    <a href="{{ route('contacts.index') }}"
                       class="inline-flex items-center justify-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-1.5 px-6 py-2.5 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <x-icon name="check" size="sm" /> Save Publisher
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
