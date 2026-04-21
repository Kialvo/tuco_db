@extends('layouts.dashboard')

@section('content')
<h1 class="text-lg font-bold text-gray-700 py-6">Outreach Templates</h1>

<div class="px-6 pb-10 bg-gray-50 min-h-screen">

    {{-- Token reference --}}
    <div class="bg-cyan-50 border border-cyan-200 rounded shadow-sm p-4 mb-6 max-w-4xl text-xs text-cyan-800">
        <span class="font-semibold">Available tokens:</span>
        <code class="mx-1 bg-cyan-100 px-1 rounded">[domain]</code>
        <code class="mx-1 bg-cyan-100 px-1 rounded">[publisher price]</code>
        <code class="mx-1 bg-cyan-100 px-1 rounded">[special topic price]</code>
        <code class="mx-1 bg-cyan-100 px-1 rounded">[brand]</code>
        <code class="mx-1 bg-cyan-100 px-1 rounded">[target url]</code>
        <code class="mx-1 bg-cyan-100 px-1 rounded">{{sensitive_line}}</code>
        <span class="text-cyan-600 ml-1">(first template only — auto-removed when no special topic price)</span>
    </div>

    {{-- Language tabs --}}
    <div class="max-w-4xl mb-4 flex flex-wrap gap-2">
        @foreach($languages as $code => $label)
            <button
                onclick="switchLang('{{ $code }}')"
                id="tab-{{ $code }}"
                class="lang-tab px-4 py-2 rounded text-sm font-medium border transition
                       {{ $loop->first ? 'bg-cyan-600 text-white border-cyan-600' : 'bg-white text-gray-600 border-gray-300 hover:border-cyan-400' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Language panels --}}
    @foreach($languages as $code => $label)
        <div id="panel-{{ $code }}" class="lang-panel max-w-4xl {{ $loop->first ? '' : 'hidden' }}">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                {{-- First email --}}
                <div class="bg-white border border-gray-200 rounded shadow-sm p-5">
                    <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                        <i class="fas fa-envelope text-cyan-600"></i> First Email
                    </h2>

                    <div class="mb-3">
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Subject</label>
                        <input type="text"
                               id="subject-{{ $code }}-first"
                               value="{{ $templates[$code]['first']['subject'] ?? '' }}"
                               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-cyan-500 focus:border-cyan-500"/>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Body</label>
                        <textarea id="body-{{ $code }}-first"
                                  rows="14"
                                  class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono focus:ring-cyan-500 focus:border-cyan-500">{{ $templates[$code]['first']['body'] ?? '' }}</textarea>
                    </div>

                    <div class="flex items-center gap-3">
                        <button onclick="saveTemplate('{{ $code }}', 'first')"
                                class="bg-cyan-600 text-white px-4 py-2 rounded text-sm hover:bg-cyan-700 transition flex items-center gap-2">
                            <i class="fas fa-save"></i> Save
                        </button>
                        <span id="status-{{ $code }}-first" class="text-xs hidden"></span>
                    </div>
                </div>

                {{-- Follow-up --}}
                <div class="bg-white border border-gray-200 rounded shadow-sm p-5">
                    <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                        <i class="fas fa-reply text-gray-500"></i> Follow-up
                    </h2>

                    <div class="mb-3">
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Subject</label>
                        <input type="text"
                               id="subject-{{ $code }}-followup"
                               value="{{ $templates[$code]['followup']['subject'] ?? '' }}"
                               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-cyan-500 focus:border-cyan-500"/>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Body</label>
                        <textarea id="body-{{ $code }}-followup"
                                  rows="14"
                                  class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono focus:ring-cyan-500 focus:border-cyan-500">{{ $templates[$code]['followup']['body'] ?? '' }}</textarea>
                    </div>

                    <div class="flex items-center gap-3">
                        <button onclick="saveTemplate('{{ $code }}', 'followup')"
                                class="bg-cyan-600 text-white px-4 py-2 rounded text-sm hover:bg-cyan-700 transition flex items-center gap-2">
                            <i class="fas fa-save"></i> Save
                        </button>
                        <span id="status-{{ $code }}-followup" class="text-xs hidden"></span>
                    </div>
                </div>

            </div>
        </div>
    @endforeach

</div>
@endsection

@push('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

function switchLang(code) {
    document.querySelectorAll('.lang-panel').forEach(p => p.classList.add('hidden'));
    document.querySelectorAll('.lang-tab').forEach(t => {
        t.classList.remove('bg-cyan-600', 'text-white', 'border-cyan-600');
        t.classList.add('bg-white', 'text-gray-600', 'border-gray-300');
    });

    document.getElementById('panel-' + code).classList.remove('hidden');
    const tab = document.getElementById('tab-' + code);
    tab.classList.add('bg-cyan-600', 'text-white', 'border-cyan-600');
    tab.classList.remove('bg-white', 'text-gray-600', 'border-gray-300');
}

function saveTemplate(lang, type) {
    const subject   = document.getElementById('subject-' + lang + '-' + type).value.trim();
    const body      = document.getElementById('body-' + lang + '-' + type).value;
    const statusEl  = document.getElementById('status-' + lang + '-' + type);

    if (!subject || !body.trim()) {
        showStatus(statusEl, 'Subject and body are required.', false);
        return;
    }

    fetch(`/admin/outreach-templates/${lang}/${type}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ subject, body }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showStatus(statusEl, '✓ Saved successfully', true);
        } else {
            showStatus(statusEl, data.error ?? 'Save failed.', false);
        }
    })
    .catch(() => showStatus(statusEl, 'Network error. Please try again.', false));
}

function showStatus(el, msg, ok) {
    el.textContent = msg;
    el.className = 'text-xs ' + (ok ? 'text-green-600' : 'text-red-600');
    el.classList.remove('hidden');
    setTimeout(() => el.classList.add('hidden'), 3000);
}
</script>
@endpush
