@extends('layouts.dashboard')
@section('title', 'Dashboard')

@section('content')
    @php
        $user = Auth::user();
        $stats = [
            ['label' => 'Active domains',   'value' => \App\Models\Website::whereNull('deleted_at')->count(),         'href' => route('websites.index'),    'icon' => 'globe',         'tone' => 'green'],
            ['label' => 'New entries',      'value' => \App\Models\NewEntry::count(),                                'href' => route('new_entries.index'), 'icon' => 'folder-plus',   'tone' => 'amber'],
            ['label' => 'Storages',         'value' => \App\Models\Storage::count(),                                 'href' => route('storages.index'),    'icon' => 'warehouse',     'tone' => 'blue'],
            ['label' => 'Submitted orders', 'value' => \App\Models\Order::whereNotIn('status', ['draft','cancelled'])->count(), 'href' => route('admin.orders.index'),'icon' => 'orders',        'tone' => 'purple'],
        ];
        $toneBg = ['green' => 'bg-green-50 text-green-700', 'amber' => 'bg-amber-50 text-amber-700', 'blue' => 'bg-blue-50 text-blue-700', 'purple' => 'bg-purple-50 text-purple-700'];
    @endphp

    <div class="px-6 py-6 max-w-7xl">

        <header class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Welcome back, {{ explode(' ', $user->name)[0] }}</h1>
            <p class="text-sm text-gray-500 mt-1">Here's a quick snapshot of what's in the system.</p>
        </header>

        {{-- Stat cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            @foreach($stats as $s)
                <a href="{{ $s['href'] }}"
                   class="block bg-white rounded-xl border border-gray-200 shadow-card p-5 hover:border-green-200 hover:shadow-md transition group">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ $toneBg[$s['tone']] }}">
                            <x-icon :name="$s['icon']" size="lg" />
                        </div>
                        <x-icon name="arrow-right" size="sm" class="text-gray-300 group-hover:text-green-600 transition" />
                    </div>
                    <div class="text-2xl font-bold text-gray-800">{{ number_format($s['value']) }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ $s['label'] }}</div>
                </a>
            @endforeach
        </div>

        {{-- Quick actions --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-card p-6">
            <h2 class="text-base font-bold text-gray-800 mb-4">Quick actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <a href="{{ route('websites.create') }}"
                   class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-green-300 hover:bg-green-50 transition">
                    <x-icon name="plus" class="text-green-600" />
                    <span class="text-sm font-medium text-gray-700">Add a domain</span>
                </a>
                <a href="{{ route('new_entries.create') }}"
                   class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-green-300 hover:bg-green-50 transition">
                    <x-icon name="folder-plus" class="text-green-600" />
                    <span class="text-sm font-medium text-gray-700">New entry</span>
                </a>
                <a href="{{ route('tools.discover') }}"
                   class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-green-300 hover:bg-green-50 transition">
                    <x-icon name="search" class="text-green-600" />
                    <span class="text-sm font-medium text-gray-700">Discover domains</span>
                </a>
            </div>
        </div>
    </div>
@endsection
