@extends('layouts.dashboard')

@section('content')
    <h1 class="text-lg font-bold text-gray-700 py-6">Clean Ahrefs CSV</h1>

    <div class="bg-white p-6 rounded shadow w-full max-w-3xl">
        <form action="{{ route('tools.ahrefs.run') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <p class="text-sm text-gray-600">
                Upload the CSV you exported from Ahrefs. We’ll remove:
                <br>• .gov / .edu / .org domains (including multi-label like <code>.gov.ro</code>)
                <br>• Big platforms (Facebook, Instagram, Amazon, etc.) from the list provided
                <br>• Domains already in your database
                <br>• Domains in “New Entry” with status “Waiting for 1st Answer” and first contact &lt; 15 days ago
            </p>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">CSV file</label>
                <input type="file" name="csv" accept=".csv,text/csv" required
                       class="block w-full border border-gray-300 rounded px-3 py-2">
                @error('csv')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3">
                <button class="bg-cyan-600 text-white px-4 py-2 rounded hover:bg-cyan-700">
                    Clean & Download
                </button>
            </div>
        </form>
    </div>
@endsection
