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
                    Clean
                </button>

                {{-- Show a download button only when we have a cleaned file ready --}}
                @isset($download_data_url)
                    <a href="{{ $download_data_url }}"
                       download="cleaned_domains.csv"
                       class="bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-800">
                        Download Cleaned CSV
                    </a>
                @endisset
            </div>
        </form>
    </div>

    {{-- Results panel (only renders after a run) --}}
    @isset($removed)
        <div class="bg-white mt-6 p-6 rounded shadow w-full max-w-3xl">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-gray-800">Results</h2>
                <div class="text-sm text-gray-600">
                    Total: <strong>{{ $total_count ?? 0 }}</strong> •
                    Kept: <strong class="text-green-700">{{ $kept_count ?? 0 }}</strong> •
                    Removed: <strong class="text-red-700">{{ $removed_count ?? 0 }}</strong>
                </div>
            </div>

            <h3 class="font-medium text-gray-700 mb-2">Removed domains (with reason)</h3>

            @if(empty($removed))
                <p class="text-sm text-gray-500">Nothing was removed.</p>
            @else
                <div class="border rounded max-h-96 overflow-y-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-100 sticky top-0">
                        <tr>
                            <th class="text-left px-3 py-2 font-semibold text-gray-700 w-1/2">Domain</th>
                            <th class="text-left px-3 py-2 font-semibold text-gray-700">Reason(s)</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($removed as $row)
                            <tr class="border-t">
                                <td class="px-3 py-2">{{ $row['domain'] ?? $row[0] ?? '' }}</td>
                                <td class="px-3 py-2 text-gray-700">
                                    {{-- Reason can be a string or an array of strings --}}
                                    @php
                                        $reasons = is_array($row['reason'] ?? null) ? ($row['reason'] ?? []) : [($row['reason'] ?? '')];
                                        $reasons = array_filter($reasons, fn($r) => trim($r) !== '');
                                    @endphp
                                    @forelse($reasons as $reason)
                                        <span class="inline-block bg-gray-200 text-gray-800 text-xs px-2 py-1 rounded mr-2 mb-1">
                                                {{ $reason }}
                                            </span>
                                    @empty
                                        <span class="text-gray-400">—</span>
                                    @endforelse
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endisset
@endsection
