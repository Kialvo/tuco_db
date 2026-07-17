@extends('layouts.dashboard')

@section('title', 'Database Statistics')

@section('subnav')
    @include('layouts.partials.stats-sidebar')
@endsection

@section('content')
    <div class="mx-auto max-w-7xl space-y-6 py-2">
        {{-- Header --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-semibold text-slate-900">Database Statistics</h1>
            <p class="mt-2 text-sm text-slate-600">
                Overview and health metrics for the domain database.
            </p>
        </div>

        {{-- Coming soon placeholder --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-10 shadow-sm">
            <div class="mx-auto flex max-w-md flex-col items-center text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-green-50 text-green-600">
                    <x-icon name="database" size="lg" />
                </div>
                <h2 class="mt-5 text-lg font-semibold text-slate-900">Coming soon</h2>
                <p class="mt-2 text-sm text-slate-600">
                    Database statistics are on the way. This section will show counts, breakdowns and
                    metric distributions across the domain database.
                </p>
            </div>
        </div>
    </div>
@endsection
