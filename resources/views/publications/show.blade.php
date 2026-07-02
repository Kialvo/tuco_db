{{-- resources/views/publications/show.blade.php --}}
@extends('layouts.dashboard')
@section('title', $publication->site)

@php
    $tone    = config('linkbuilding.tone_classes');
    $pubTone = fn($s) => $tone[config('linkbuilding.publication_status_tones')[$s] ?? 'gray'] ?? 'bg-gray-100 text-gray-700';
    $fd      = fn($d) => $d ? $d->format('d/m/Y') : '—';
    $cam     = $publication->campaign;
    $co      = $cam?->company;
@endphp

@section('content')
<div class="px-6 py-6 bg-gray-50 min-h-screen">
    <a href="{{ $cam ? route('crm.campaigns.show', $cam->id) : route('crm.campaigns.index') }}"
       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-green-600 mb-4">
        <x-icon name="arrow-left" size="sm" /> Back to {{ $cam?->code ?? 'Campaigns' }}
    </a>

    {{-- Header --}}
    <div class="flex items-start justify-between flex-wrap gap-3 mb-5">
        <div>
            <div class="text-2xl font-bold text-gray-900">{{ $publication->site }}</div>
            <div class="text-sm text-gray-500 mt-1">
                @if($cam)<a href="{{ route('crm.campaigns.show', $cam->id) }}" class="text-green-600 hover:underline">{{ $cam->code }}</a>@endif
                @if($co) · <a href="{{ route('crm.companies.show', $co->id) }}" class="text-green-600 hover:underline">{{ $co->name }}</a>@endif
            </div>
        </div>
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $pubTone($publication->status) }}">{{ $publication->status }}</span>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-5">
        @php
            $stat = fn($l, $v) => '<div class="bg-white border border-gray-200 rounded-xl shadow-card px-4 py-3">'
                . '<div class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1">' . $l . '</div>'
                . '<div class="text-sm font-bold text-gray-800">' . $v . '</div></div>';
        @endphp
        {!! $stat('Price', '€'.number_format((float)$publication->price, 0)) !!}
        {!! $stat('Sent to Copywriter', $fd($publication->date_to_copywriter)) !!}
        {!! $stat('Copy Received', $fd($publication->date_from_copywriter)) !!}
        {!! $stat('Sent to Blog', $fd($publication->date_to_blog)) !!}
        {!! $stat('Live Date', $fd($publication->live_date)) !!}
    </div>

    {{-- Details --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-card">
        <div class="px-5 py-3 border-b border-gray-100 text-xs font-bold uppercase tracking-wide text-gray-600">Details</div>
        <div class="px-5 py-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div><div class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1">Publisher</div><div class="text-sm text-gray-800">{{ $publication->site }}</div></div>
                <div><div class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1">Status</div><div><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $pubTone($publication->status) }}">{{ $publication->status }}</span></div></div>
                <div><div class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1">Live URL</div><div class="text-sm">
                    @if($publication->live_url)<a href="{{ $publication->live_url }}" target="_blank" class="text-green-600 hover:underline break-all">{{ $publication->live_url }}</a>@else — @endif
                </div></div>
                <div><div class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1">Campaign</div><div class="text-sm">@if($cam)<a href="{{ route('crm.campaigns.show', $cam->id) }}" class="text-green-600 hover:underline">{{ $cam->code }}</a>@else — @endif</div></div>
                <div><div class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1">Client</div><div class="text-sm">@if($co)<a href="{{ route('crm.companies.show', $co->id) }}" class="text-green-600 hover:underline">{{ $co->name }}</a>@else — @endif</div></div>
            </div>
            @if($publication->notes)
                <div class="mt-4 pt-3 border-t border-gray-100">
                    <div class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1">Notes</div>
                    <div class="text-sm text-gray-600 whitespace-pre-line">{{ $publication->notes }}</div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
