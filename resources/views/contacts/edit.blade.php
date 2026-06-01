@extends('layouts.dashboard')
@section('title', 'Edit Publisher')

@section('pageHeader')
    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between flex-shrink-0">
        <div>
            <h1 class="text-base font-bold text-gray-800">Edit Publisher</h1>
            <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $contact->first_name }} {{ $contact->last_name }}</p>
        </div>
        <a href="{{ route('contacts.index') }}"
           class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300">
            <x-icon name="arrow-left" size="sm" /> Back
        </a>
    </div>
@endsection

@section('content')
<div class="px-6 py-6 bg-gray-50 min-h-screen">
<div class="form-card max-w-6xl">

@if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm">
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<form action="{{ route('contacts.update', $contact->id) }}" method="POST" class="space-y-6">
@csrf
@method('PUT')

{{-- ── IDENTITY ── --}}
<div>
    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-3">Identity</p>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-gray-700 font-medium mb-1">First Name <span class="text-red-500">*</span></label>
            <input type="text" name="first_name" value="{{ old('first_name', $contact->first_name) }}"
                   class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-green-500 focus:border-green-500" required>
        </div>
        <div>
            <label class="block text-gray-700 font-medium mb-1">Last Name</label>
            <input type="text" name="last_name" value="{{ old('last_name', $contact->last_name) }}"
                   class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-green-500 focus:border-green-500">
        </div>
    </div>
</div>

{{-- ── PROFESSIONAL ── --}}
<div class="pt-4 border-t border-gray-100">
    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-3">Professional</p>
    <div class="grid grid-cols-3 gap-4">
        <div>
            <label class="block text-gray-700 font-medium mb-1">Company</label>
            <select name="company_id" id="companySelect"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-green-500 focus:border-green-500">
                <option value="">— None —</option>
                @if($contact->company)
                    <option value="{{ $contact->company->id }}" selected>{{ $contact->company->name }}</option>
                @endif
            </select>
        </div>
        <div>
            <label class="block text-gray-700 font-medium mb-1">Job Title</label>
            <input type="text" name="job_title" value="{{ old('job_title', $contact->job_title) }}"
                   class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-green-500 focus:border-green-500">
        </div>
        <div>
            <label class="block text-gray-700 font-medium mb-1">First Contact Date</label>
            <input type="text" name="first_contact_date"
                   value="{{ old('first_contact_date', $contact->first_contact_date?->format('d/m/Y')) }}"
                   class="date-input w-full border border-gray-300 rounded px-2 py-1 focus:ring-green-500 focus:border-green-500"
                   placeholder="DD/MM/YYYY">
        </div>
    </div>
</div>

{{-- ── PERSONAL ── --}}
<div class="pt-4 border-t border-gray-100">
    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-3">Personal</p>
    <div class="grid grid-cols-3 gap-4">
        <div>
            <label class="block text-gray-700 font-medium mb-1">Birthday</label>
            <input type="text" name="birthday"
                   value="{{ old('birthday', $contact->birthday?->format('d/m/Y')) }}"
                   class="date-input w-full border border-gray-300 rounded px-2 py-1 focus:ring-green-500 focus:border-green-500"
                   placeholder="DD/MM/YYYY">
        </div>
        <div>
            <label class="block text-gray-700 font-medium mb-1">Religion</label>
            <select name="religion"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-green-500 focus:border-green-500">
                <option value="">— None —</option>
                @foreach(['Christianity','Islam','Judaism','Buddhism','Hinduism','Other'] as $r)
                    <option value="{{ $r }}" {{ old('religion', $contact->religion) === $r ? 'selected' : '' }}>{{ $r }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-gray-700 font-medium mb-1">Country of Origin</label>
            <select name="country_of_origin_id"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-green-500 focus:border-green-500">
                <option value="">— None —</option>
                @foreach($countries as $c)
                    <option value="{{ $c->id }}" {{ old('country_of_origin_id', $contact->country_of_origin_id) == $c->id ? 'selected' : '' }}>
                        {{ $c->country_name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>

{{-- ── LANGUAGE ── --}}
<div class="pt-4 border-t border-gray-100">
    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-3">Language</p>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-gray-700 font-medium mb-1">Primary Language</label>
            <select name="primary_language_id"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-green-500 focus:border-green-500">
                <option value="">— None —</option>
                @foreach($languages as $lang)
                    <option value="{{ $lang->id }}" {{ old('primary_language_id', $contact->primary_language_id) == $lang->id ? 'selected' : '' }}>
                        {{ $lang->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>

{{-- ── COMMUNICATION CHANNELS ── --}}
<div class="pt-4 border-t border-gray-100">
    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-3">Communication Channels</p>
    <p class="text-xs text-gray-500 mb-3">Select the preferred contact channels. Values come from the contact details below.</p>
    <div class="space-y-2">
        @foreach([1,2,3] as $n)
        <div class="flex items-center gap-3">
            <span class="text-xs text-gray-400 w-6 text-right flex-shrink-0">{{ $n }}.</span>
            <select name="channel_{{ $n }}" id="channel_{{ $n }}"
                    class="border border-gray-300 rounded px-2 py-1.5 text-sm w-44 focus:ring-green-500 focus:border-green-500">
                <option value="">— none —</option>
                @foreach(['email','phone','whatsapp','telegram','linkedin','facebook','discord'] as $ch)
                    <option value="{{ $ch }}" {{ old('channel_'.$n, $contact->{'channel_'.$n}) === $ch ? 'selected' : '' }}>
                        {{ ucfirst($ch) }}
                    </option>
                @endforeach
            </select>
            <div id="channel_preview_{{ $n }}" class="flex-1 min-h-[32px] flex items-center"></div>
        </div>
        @endforeach
    </div>
</div>

{{-- ── CONTACT DETAILS ── --}}
<div class="pt-4 border-t border-gray-100">
    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-3">Contact Details</p>
    <div class="grid grid-cols-2 gap-3">

        <div>
            <label class="block text-gray-700 font-medium mb-1">Email</label>
            <div class="flex rounded border border-gray-300 overflow-hidden focus-within:ring-1 focus-within:ring-green-500 focus-within:border-green-500">
                <span class="flex items-center justify-center w-9 bg-gray-500 text-white flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </span>
                <input type="email" name="email" id="field_email" value="{{ old('email', $contact->email) }}"
                       class="flex-1 px-3 py-1.5 border-0 focus:ring-0 text-sm" placeholder="email@example.com">
            </div>
        </div>

        <div>
            <label class="block text-gray-700 font-medium mb-1">Phone</label>
            <div class="flex rounded border border-gray-300 overflow-hidden focus-within:ring-1 focus-within:ring-green-500 focus-within:border-green-500">
                <span class="flex items-center justify-center w-9 bg-gray-500 text-white flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                </span>
                <input type="tel" name="phone" id="field_phone" value="{{ old('phone', $contact->phone) }}"
                       class="flex-1 px-3 py-1.5 border-0 focus:ring-0 text-sm" placeholder="+1 234 567 890">
            </div>
        </div>

        <div>
            <label class="block text-gray-700 font-medium mb-1">WhatsApp</label>
            <div class="flex rounded border border-gray-300 overflow-hidden focus-within:ring-1 focus-within:ring-green-500 focus-within:border-green-500">
                <span class="flex items-center justify-center w-9 flex-shrink-0" style="background:#25D366">
                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                </span>
                <input type="tel" name="whatsapp" id="field_whatsapp" value="{{ old('whatsapp', $contact->whatsapp) }}"
                       class="flex-1 px-3 py-1.5 border-0 focus:ring-0 text-sm" placeholder="+1 234 567 890">
            </div>
        </div>

        <div>
            <label class="block text-gray-700 font-medium mb-1">Telegram</label>
            <div class="flex rounded border border-gray-300 overflow-hidden focus-within:ring-1 focus-within:ring-green-500 focus-within:border-green-500">
                <span class="flex items-center justify-center w-9 flex-shrink-0" style="background:#2AABEE">
                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 000 12a12 12 0 0012 12 12 12 0 0012-12A12 12 0 0012 0a12 12 0 00-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 01.171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                </span>
                <input type="text" name="telegram" id="field_telegram" value="{{ old('telegram', $contact->telegram) }}"
                       class="flex-1 px-3 py-1.5 border-0 focus:ring-0 text-sm" placeholder="@username">
            </div>
        </div>

        <div>
            <label class="block text-gray-700 font-medium mb-1">LinkedIn</label>
            <div class="flex rounded border border-gray-300 overflow-hidden focus-within:ring-1 focus-within:ring-green-500 focus-within:border-green-500">
                <span class="flex items-center justify-center w-9 flex-shrink-0" style="background:#0A66C2">
                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2zM4 6a2 2 0 100-4 2 2 0 000 4z"/></svg>
                </span>
                <input type="url" name="linkedin" id="field_linkedin" value="{{ old('linkedin', $contact->linkedin) }}"
                       class="flex-1 px-3 py-1.5 border-0 focus:ring-0 text-sm" placeholder="https://linkedin.com/in/...">
            </div>
        </div>

        <div>
            <label class="block text-gray-700 font-medium mb-1">Facebook</label>
            <div class="flex rounded border border-gray-300 overflow-hidden focus-within:ring-1 focus-within:ring-green-500 focus-within:border-green-500">
                <span class="flex items-center justify-center w-9 flex-shrink-0" style="background:#1877F2">
                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
                </span>
                <input type="url" name="facebook" id="field_facebook" value="{{ old('facebook', $contact->facebook) }}"
                       class="flex-1 px-3 py-1.5 border-0 focus:ring-0 text-sm" placeholder="https://facebook.com/...">
            </div>
        </div>

        <div>
            <label class="block text-gray-700 font-medium mb-1">Discord</label>
            <div class="flex rounded border border-gray-300 overflow-hidden focus-within:ring-1 focus-within:ring-green-500 focus-within:border-green-500">
                <span class="flex items-center justify-center w-9 flex-shrink-0" style="background:#5865F2">
                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M20.317 4.37a19.791 19.791 0 00-4.885-1.515.074.074 0 00-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 00-5.487 0 12.64 12.64 0 00-.617-1.25.077.077 0 00-.079-.037A19.736 19.736 0 003.677 4.37a.07.07 0 00-.032.027C.533 9.046-.32 13.58.099 18.057.102 18.081.114 18.105.128 18.12a19.9 19.9 0 005.993 3.03.078.078 0 00.084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 00-.041-.106 13.107 13.107 0 01-1.872-.892.077.077 0 01-.008-.128 10.2 10.2 0 00.372-.292.074.074 0 01.077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 01.078.01c.12.098.246.198.373.292a.077.077 0 01-.006.127 12.299 12.299 0 01-1.873.892.077.077 0 00-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 00.084.028 19.839 19.839 0 006.002-3.03.077.077 0 00.032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 00-.031-.03z"/></svg>
                </span>
                <input type="text" name="discord" id="field_discord" value="{{ old('discord', $contact->discord) }}"
                       class="flex-1 px-3 py-1.5 border-0 focus:ring-0 text-sm" placeholder="username#0000">
            </div>
        </div>

    </div>
</div>

{{-- ── LOCATION ── --}}
<div class="pt-4 border-t border-gray-100">
    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-3">Location</p>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-gray-700 font-medium mb-1">Address</label>
            <input type="text" name="location_address" value="{{ old('location_address', $contact->location_address) }}"
                   class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-green-500 focus:border-green-500"
                   placeholder="Street, City, ZIP">
        </div>
        <div>
            <label class="block text-gray-700 font-medium mb-1">Country</label>
            <select name="location_country_id"
                    class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-green-500 focus:border-green-500">
                <option value="">— None —</option>
                @foreach($countries as $c)
                    <option value="{{ $c->id }}" {{ old('location_country_id', $contact->location_country_id) == $c->id ? 'selected' : '' }}>
                        {{ $c->country_name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-gray-700 font-medium mb-1">Latitude</label>
            <input type="number" step="any" name="location_lat" value="{{ old('location_lat', $contact->location_lat) }}"
                   class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-green-500 focus:border-green-500"
                   placeholder="e.g. 41.9028">
        </div>
        <div>
            <label class="block text-gray-700 font-medium mb-1">Longitude</label>
            <input type="number" step="any" name="location_lng" value="{{ old('location_lng', $contact->location_lng) }}"
                   class="w-full border border-gray-300 rounded px-2 py-1 focus:ring-green-500 focus:border-green-500"
                   placeholder="e.g. 12.4964">
        </div>
    </div>
</div>

{{-- ── SUBMIT ── --}}
<div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-100">
    <a href="{{ route('contacts.index') }}"
       class="inline-flex items-center justify-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
        Cancel
    </a>
    <button type="submit"
            class="inline-flex items-center justify-center gap-1.5 px-6 py-2.5 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
        <x-icon name="check" size="sm" /> Update Publisher
    </button>
</div>

</form>
</div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    flatpickr('.date-input', { dateFormat: 'd/m/Y', allowInput: true });

    $('#companySelect').select2({
        placeholder: 'Search company…',
        allowClear: true,
        width: '100%',
        ajax: {
            url: '{{ route('companies.search') }}',
            dataType: 'json',
            delay: 250,
            data: d => ({ q: d.term }),
            processResults: data => ({ results: data.map(c => ({ id: c.id, text: c.name })) }),
            cache: true
        }
    });

    const fieldMap = {
        email:    { label: 'Email',    bg: '#6B7280' },
        phone:    { label: 'Phone',    bg: '#6B7280' },
        whatsapp: { label: 'WhatsApp', bg: '#25D366' },
        telegram: { label: 'Telegram', bg: '#2AABEE' },
        linkedin: { label: 'LinkedIn', bg: '#0A66C2' },
        facebook: { label: 'Facebook', bg: '#1877F2' },
        discord:  { label: 'Discord',  bg: '#5865F2' },
    };

    function renderChannelPreview(n) {
        const type    = document.getElementById(`channel_${n}`).value;
        const preview = document.getElementById(`channel_preview_${n}`);
        if (!type || !fieldMap[type]) {
            preview.innerHTML = '<span class="text-xs text-gray-300">— no channel selected —</span>';
            return;
        }
        const cfg = fieldMap[type];
        const input = document.getElementById(`field_${type}`);
        const val   = input ? input.value.trim() : '';
        preview.innerHTML = `
            <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-sm shadow-sm">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-white text-[10px] font-semibold"
                      style="background:${cfg.bg}">${cfg.label}</span>
                <span class="${val ? 'text-gray-700' : 'text-gray-300'}">${val || 'not set yet'}</span>
            </div>`;
    }

    [1, 2, 3].forEach(n => {
        renderChannelPreview(n);
        document.getElementById(`channel_${n}`).addEventListener('change', () => renderChannelPreview(n));
    });

    ['email','phone','whatsapp','telegram','linkedin','facebook','discord'].forEach(field => {
        const el = document.getElementById(`field_${field}`);
        if (el) el.addEventListener('input', () => {
            [1,2,3].forEach(n => {
                if (document.getElementById(`channel_${n}`).value === field) renderChannelPreview(n);
            });
        });
    });
});
</script>
@endpush
