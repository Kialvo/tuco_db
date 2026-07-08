{{-- resources/views/companies/show.blade.php — Link Building CRM company detail (admin-only) --}}
@extends('layouts.dashboard')
@section('title', $company->name)

@php
    $tone     = config('linkbuilding.tone_classes');
    $campTone = fn($s) => $tone[config('linkbuilding.campaign_status_tones')[$s] ?? 'gray'] ?? 'bg-gray-100 text-gray-700';
    $svcTone  = fn($s) => $tone[config('linkbuilding.service_tones')[$s] ?? 'gray'] ?? 'bg-gray-100 text-gray-700';
    $fd       = fn($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d/m/Y') : '—';
    $ini      = mb_strtoupper(collect(preg_split('/\s+/', trim($company->name)))->filter()->map(fn($w) => mb_substr($w, 0, 1))->take(2)->implode(''));
@endphp

@section('content')
<div class="px-6 py-6 bg-gray-50 min-h-screen">
    <a href="{{ route('companies.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-green-600 mb-4">
        <x-icon name="arrow-left" size="sm" /> Back to Companies
    </a>

    {{-- Header --}}
    <div class="flex items-center gap-4 mb-5">
        <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-green-100 text-green-700 text-base font-bold">{{ $ini }}</span>
        <div>
            <div class="text-2xl font-bold text-gray-900">{{ $company->name }}</div>
            <div class="text-sm text-gray-500">
                {{ $countryName ?? '—' }}
                @if($company->website) · <a href="{{ \Illuminate\Support\Str::startsWith($company->website, ['http://','https://']) ? $company->website : 'https://'.$company->website }}" target="_blank" class="text-green-600 hover:underline">{{ $company->website }}</a>@endif
            </div>
        </div>
    </div>

    {{-- Billing entity --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-card mb-5">
        <div class="px-5 py-3 border-b border-gray-100 text-xs font-bold uppercase tracking-wide text-gray-600">Billing Entity</div>
        <div class="px-5 py-4 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div><div class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1">Legal Name</div><div class="text-sm text-gray-800">{{ $company->name }}</div></div>
            <div><div class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1">VAT Number</div><div class="text-sm text-gray-800">{{ $company->vat_number ?: '—' }}</div></div>
            <div><div class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1">Country</div><div class="text-sm text-gray-800">{{ $countryName ?: '—' }}</div></div>
            <div><div class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1">Billing Email</div><div class="text-sm">@if($company->email)<a href="mailto:{{ $company->email }}" class="text-green-600 hover:underline">{{ $company->email }}</a>@else — @endif</div></div>
            <div><div class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1">Website</div><div class="text-sm">{{ $company->website ?: '—' }}</div></div>
        </div>
        @if($company->notes)
            <div class="px-5 pb-4 -mt-1"><div class="text-sm text-gray-600 border-t border-gray-100 pt-3 whitespace-pre-line">{{ $company->notes }}</div></div>
        @endif
    </div>

    {{-- Contacts --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-card mb-5">
        <div class="px-5 py-3 border-b border-gray-100 text-xs font-bold uppercase tracking-wide text-gray-600">Contacts <span class="ml-1 text-gray-400">{{ $company->clients->count() }}</span></div>
        @if($company->clients->isEmpty())
            <div class="text-center py-8 text-gray-400 text-sm">No contacts yet</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="bg-gray-50 text-[10px] uppercase tracking-wider text-gray-500">
                        <th class="text-left py-2.5 px-4 font-semibold">Name</th>
                        <th class="text-left py-2.5 px-4 font-semibold">Job Title</th>
                        <th class="text-left py-2.5 px-4 font-semibold">Email</th>
                        <th class="text-left py-2.5 px-4 font-semibold">Phone</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($company->clients as $ct)
                            <tr class="hover:bg-gray-50">
                                <td class="py-2.5 px-4"><a href="{{ route('crm.clients.show', $ct->id) }}" class="text-green-600 hover:underline">{{ trim($ct->first_name.' '.$ct->last_name) ?: 'Contact #'.$ct->id }}</a></td>
                                <td class="py-2.5 px-4 text-gray-600">{{ $ct->job_title ?: '—' }}</td>
                                <td class="py-2.5 px-4">@if($ct->email)<a href="mailto:{{ $ct->email }}" class="text-green-600 hover:underline">{{ $ct->email }}</a>@else — @endif</td>
                                <td class="py-2.5 px-4 text-gray-600">{{ $ct->phone ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Campaigns --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-card">
        <div class="px-5 py-3 border-b border-gray-100 text-xs font-bold uppercase tracking-wide text-gray-600">Campaigns <span class="ml-1 text-gray-400">{{ $campaigns->count() }}</span></div>
        @if($campaigns->isEmpty())
            <div class="text-center py-8 text-gray-400 text-sm">No campaigns yet</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="bg-gray-50 text-[10px] uppercase tracking-wider text-gray-500">
                        <th class="text-left py-2.5 px-4 font-semibold">Code</th>
                        <th class="text-left py-2.5 px-4 font-semibold">Service</th>
                        <th class="text-left py-2.5 px-4 font-semibold">Status</th>
                        <th class="text-left py-2.5 px-4 font-semibold">Target</th>
                        <th class="text-left py-2.5 px-4 font-semibold">Deadline</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($campaigns as $c)
                            @php $p = $c->progress; @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="py-2.5 px-4"><a href="{{ route('crm.campaigns.show', $c->id) }}" class="text-green-600 hover:underline font-medium">{{ $c->code }}</a></td>
                                <td class="py-2.5 px-4">@if($c->service)<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $svcTone($c->service) }}">{{ $c->service }}</span>@else — @endif</td>
                                <td class="py-2.5 px-4"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $campTone($c->status) }}">{{ $c->status }}</span></td>
                                <td class="py-2.5 px-4 text-gray-600">{{ $p['has'] ? $p['label'] : '—' }}</td>
                                <td class="py-2.5 px-4 text-gray-500">{{ $fd($c->deadline) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
