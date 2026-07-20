@extends('layouts.dashboard')

@section('title', 'Campaigns Statistics')

@section('subnav')
    @include('layouts.partials.stats-sidebar')
@endsection

@section('content')
    <div class="mx-auto max-w-7xl space-y-6 py-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-semibold text-slate-900">Campaigns Statistics</h1>
            <p class="mt-2 text-sm text-slate-600">
                Campaign performance widgets will live here.
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <x-ds.empty-state
                icon="briefcase"
                title="No widgets yet"
                hint="The first Campaigns Stats widgets will be added here." />
        </div>
    </div>
@endsection
