{{-- resources/views/clients/show.blade.php — Link Building CRM contact detail (admin-only) --}}
@extends('layouts.dashboard')
@section('title', trim($client->first_name.' '.$client->last_name))

@php
    $tone     = config('linkbuilding.tone_classes');
    $campTone = fn($s) => $tone[config('linkbuilding.campaign_status_tones')[$s] ?? 'gray'] ?? 'bg-gray-100 text-gray-700';
    $svcTone  = fn($s) => $tone[config('linkbuilding.service_tones')[$s] ?? 'gray'] ?? 'bg-gray-100 text-gray-700';
    $fd       = fn($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d/m/Y') : '—';
    $fullName = trim($client->first_name.' '.$client->last_name) ?: 'Contact #'.$client->id;
    $ini      = mb_strtoupper(collect(preg_split('/\s+/', trim($fullName)))->filter()->map(fn($w) => mb_substr($w, 0, 1))->take(2)->implode(''));
@endphp

@section('content')
<div class="px-6 py-6 bg-gray-50 min-h-screen">
    <a href="{{ route('clients.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-green-600 mb-4">
        <x-icon name="arrow-left" size="sm" /> Back to Contacts
    </a>

    {{-- Header --}}
    <div class="flex items-center gap-4 mb-5">
        <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-sky-100 text-sky-700 text-base font-bold">{{ $ini }}</span>
        <div>
            <div class="text-2xl font-bold text-gray-900">{{ $fullName }}</div>
            <div class="text-sm text-gray-500">
                {{ $client->job_title ?: '—' }}
                @if($client->company) · <a href="{{ route('crm.companies.show', $client->company_id) }}" class="text-green-600 hover:underline">{{ $client->company->name }}</a>@endif
            </div>
        </div>
    </div>

    {{-- Identity + contact details --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
        <div class="bg-white border border-gray-200 rounded-xl shadow-card">
            <div class="px-5 py-3 border-b border-gray-100 text-xs font-bold uppercase tracking-wide text-gray-600">Identity</div>
            <div class="px-5 py-4 grid grid-cols-2 gap-4">
                <div><div class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1">First Name</div><div class="text-sm text-gray-800">{{ $client->first_name ?: '—' }}</div></div>
                <div><div class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1">Last Name</div><div class="text-sm text-gray-800">{{ $client->last_name ?: '—' }}</div></div>
                <div><div class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1">Job Title</div><div class="text-sm text-gray-800">{{ $client->job_title ?: '—' }}</div></div>
                <div><div class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1">Email</div><div class="text-sm">@if($client->email)<a href="mailto:{{ $client->email }}" class="text-green-600 hover:underline">{{ $client->email }}</a>@else — @endif</div></div>
                <div><div class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1">First Contact</div><div class="text-sm text-gray-800">{{ $fd($client->first_contact_date) }}</div></div>
                <div><div class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1">Company</div><div class="text-sm">@if($client->company)<a href="{{ route('crm.companies.show', $client->company_id) }}" class="text-green-600 hover:underline">{{ $client->company->name }}</a>@else — @endif</div></div>
            </div>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl shadow-card">
            <div class="px-5 py-3 border-b border-gray-100 text-xs font-bold uppercase tracking-wide text-gray-600">Contact Details</div>
            <div class="px-5 py-4 grid grid-cols-2 gap-4">
                <div><div class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1">Phone</div><div class="text-sm text-gray-800">{{ $client->phone ?: '—' }}</div></div>
                <div><div class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1">WhatsApp</div><div class="text-sm text-gray-800">{{ $client->whatsapp ?: '—' }}</div></div>
                <div><div class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1">Telegram</div><div class="text-sm text-gray-800">{{ $client->telegram ?: '—' }}</div></div>
            </div>
        </div>
    </div>

    {{-- Campaigns --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-card">
        <div class="px-5 py-3 border-b border-gray-100 text-xs font-bold uppercase tracking-wide text-gray-600">Campaigns <span class="ml-1 text-gray-400">{{ $campaigns->count() }}</span></div>
        @if($campaigns->isEmpty())
            <div class="text-center py-8 text-gray-400 text-sm">No campaigns linked to this contact</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="bg-gray-50 text-[10px] uppercase tracking-wider text-gray-500">
                        <th class="text-left py-2.5 px-4 font-semibold">Code</th>
                        <th class="text-left py-2.5 px-4 font-semibold">Company</th>
                        <th class="text-left py-2.5 px-4 font-semibold">Service</th>
                        <th class="text-left py-2.5 px-4 font-semibold">Status</th>
                        <th class="text-left py-2.5 px-4 font-semibold">Deadline</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($campaigns as $c)
                            <tr class="hover:bg-gray-50">
                                <td class="py-2.5 px-4"><a href="{{ route('crm.campaigns.show', $c->id) }}" class="text-green-600 hover:underline font-medium">{{ $c->code }}</a></td>
                                <td class="py-2.5 px-4">@if($c->company)<a href="{{ route('crm.companies.show', $c->company_id) }}" class="text-green-600 hover:underline">{{ $c->company->name }}</a>@else — @endif</td>
                                <td class="py-2.5 px-4">@if($c->service)<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $svcTone($c->service) }}">{{ $c->service }}</span>@else — @endif</td>
                                <td class="py-2.5 px-4"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $campTone($c->status) }}">{{ $c->status }}</span></td>
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
