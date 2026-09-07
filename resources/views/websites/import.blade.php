{{-- resources/views/websites/import.blade.php --}}
@extends('layouts.dashboard')
@section('title', 'Import Domains')

@section('content')
    {{-- The layout only renders @section('pageHeader') on pages that also have
         a filters aside, so this page carries its own header. --}}
    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between flex-shrink-0">
        <div>
            <h1 class="text-base font-bold text-gray-800">Import Domains</h1>
            <p class="text-xs text-gray-500 mt-0.5">
                Create domains in bulk from a CSV. Price and Sensitive Topic Price are calculated for you.
            </p>
        </div>
        <a href="{{ route('websites.index') }}"
           class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
            Back to Domains
        </a>
    </div>

    <div class="px-6 py-6 bg-gray-50 min-h-full text-sm">

        {{-- Imported domains are visible to customers immediately. Say so once, loudly. --}}
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900">
            <p class="font-semibold">Imported domains go live straight away.</p>
            <p class="text-xs mt-0.5">
                They are created as <strong>active</strong>, which means customers can see them as soon as the import
                finishes. Nothing is saved until you press Import, so check the preview first.
            </p>
        </div>

        {{-- Step 1 — upload --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <form id="importForm" class="space-y-4" onsubmit="return false;">
                @csrf
                <div class="flex flex-wrap items-center gap-3">
                    <input type="file" name="file" id="csvFile" accept=".csv,text/csv" required
                           class="block w-full sm:w-auto text-sm text-gray-700 file:mr-4 file:py-2 file:px-4
                                  file:rounded-md file:border-0 file:text-sm file:font-semibold
                                  file:bg-green-50 file:text-green-700 hover:file:bg-green-100"/>

                    <a href="{{ route('websites.import.sample') }}" class="text-green-700 underline whitespace-nowrap">
                        Download template
                    </a>
                </div>

                <label class="flex items-center gap-2">
                    <input type="checkbox" id="hasHeader" class="rounded" checked>
                    <span>First row has headers</span>
                </label>

                <div class="flex flex-wrap gap-2">
                    <button type="button" id="btnPreview"
                            class="bg-green-600 text-white px-4 py-2 rounded-lg shadow hover:bg-green-700 disabled:opacity-50">
                        Preview
                    </button>
                    <button type="button" id="btnImport" disabled
                            class="bg-emerald-700 text-white px-4 py-2 rounded-lg shadow hover:bg-emerald-800 disabled:opacity-50">
                        Import
                    </button>
                </div>
            </form>

            <details class="mt-5 text-xs text-gray-600">
                <summary class="cursor-pointer font-semibold text-gray-700">What the file must contain</summary>
                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full text-left border border-gray-200 rounded">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-3 py-1.5 font-semibold">Column</th>
                                <th class="px-3 py-1.5 font-semibold">Required</th>
                                <th class="px-3 py-1.5 font-semibold">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr><td class="px-3 py-1.5">Domain</td><td class="px-3 py-1.5 font-semibold">Yes</td><td class="px-3 py-1.5"></td></tr>
                            <tr><td class="px-3 py-1.5">DR</td><td class="px-3 py-1.5">No</td><td class="px-3 py-1.5"></td></tr>
                            <tr><td class="px-3 py-1.5">Link Builder €</td><td class="px-3 py-1.5">No</td><td class="px-3 py-1.5">Always in euros, whatever the currency</td></tr>
                            <tr><td class="px-3 py-1.5">Publisher Price</td><td class="px-3 py-1.5 font-semibold">Yes</td><td class="px-3 py-1.5">In the site's own currency</td></tr>
                            <tr><td class="px-3 py-1.5">Special Topic Price</td><td class="px-3 py-1.5">No</td><td class="px-3 py-1.5">In the site's own currency</td></tr>
                            <tr><td class="px-3 py-1.5">Language</td><td class="px-3 py-1.5 font-semibold">Yes</td><td class="px-3 py-1.5">Must already exist</td></tr>
                            <tr><td class="px-3 py-1.5">Country</td><td class="px-3 py-1.5 font-semibold">Yes</td><td class="px-3 py-1.5">Must already exist</td></tr>
                            <tr><td class="px-3 py-1.5">Link Builder</td><td class="px-3 py-1.5 font-semibold">Yes</td><td class="px-3 py-1.5">{{ implode(' / ', $allowedLinkBuilders) }}</td></tr>
                            <tr><td class="px-3 py-1.5">Notes</td><td class="px-3 py-1.5">No</td><td class="px-3 py-1.5 text-amber-700 font-semibold">Customers can see this</td></tr>
                            <tr><td class="px-3 py-1.5">Internal Notes</td><td class="px-3 py-1.5">No</td><td class="px-3 py-1.5">Internal only</td></tr>
                            <tr><td class="px-3 py-1.5">Type</td><td class="px-3 py-1.5 font-semibold">Yes</td><td class="px-3 py-1.5">{{ implode(' / ', $allowedTypes) }}</td></tr>
                            <tr><td class="px-3 py-1.5">Category</td><td class="px-3 py-1.5 font-semibold">Yes</td><td class="px-3 py-1.5">Comma-separated, must already exist</td></tr>
                            <tr><td class="px-3 py-1.5">Betting</td><td class="px-3 py-1.5 font-semibold">Yes</td><td class="px-3 py-1.5">Yes / No</td></tr>
                            <tr><td class="px-3 py-1.5">Trading</td><td class="px-3 py-1.5 font-semibold">Yes</td><td class="px-3 py-1.5">Yes / No</td></tr>
                            <tr><td class="px-3 py-1.5">Currency</td><td class="px-3 py-1.5 font-semibold">Yes</td><td class="px-3 py-1.5">EUR or USD (blank = EUR)</td></tr>
                        </tbody>
                    </table>
                    <p class="mt-2">
                        Do not include Price or Sensitive Topic Price — they are calculated from Publisher Price,
                        Special Topic Price, Link Builder € and the language. USD prices are converted automatically.
                    </p>
                </div>
            </details>
        </div>

        {{-- Step 2 — results --}}
        <div id="resultBox" class="mt-6 hidden">
            <div id="statsRow" class="flex flex-wrap gap-2 mb-4"></div>
            <div id="messageBox" class="hidden mb-4 rounded-lg px-4 py-3"></div>

            {{-- Domains already in the system --}}
            <div id="duplicatesBox" class="hidden bg-white border border-gray-200 rounded-xl shadow-sm p-5 mb-4">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <div>
                        <h2 class="font-semibold text-gray-800">These domains are already in the system</h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Choose what to do with each one. <strong>Update</strong> refreshes only the columns you
                            filled in and never changes the domain's current status. <strong>Skip</strong> leaves it
                            untouched.
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" id="btnUpdateAll"
                                class="px-3 py-1.5 text-xs rounded border border-gray-300 hover:bg-gray-50">Update all</button>
                        <button type="button" id="btnSkipAll"
                                class="px-3 py-1.5 text-xs rounded border border-gray-300 hover:bg-gray-50">Skip all</button>
                    </div>
                </div>
                <div class="overflow-x-auto max-h-72 overflow-y-auto">
                    <table class="min-w-full text-xs">
                        <thead class="bg-gray-50 text-gray-600 sticky top-0">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold">Line</th>
                                <th class="px-3 py-2 text-left font-semibold">Domain</th>
                                <th class="px-3 py-2 text-left font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody id="duplicatesBody" class="divide-y divide-gray-100"></tbody>
                    </table>
                </div>
            </div>

            {{-- Full preview --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
                <h2 class="font-semibold text-gray-800 mb-3">Preview</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold">Line</th>
                                <th class="px-3 py-2 text-left font-semibold">Domain</th>
                                <th class="px-3 py-2 text-left font-semibold">Cur.</th>
                                <th class="px-3 py-2 text-right font-semibold">Publisher</th>
                                <th class="px-3 py-2 text-right font-semibold">Special topic</th>
                                <th class="px-3 py-2 text-right font-semibold">Link builder €</th>
                                <th class="px-3 py-2 text-right font-semibold">Price</th>
                                <th class="px-3 py-2 text-right font-semibold">Sensitive</th>
                                <th class="px-3 py-2 text-center font-semibold">Cat.</th>
                                <th class="px-3 py-2 text-center font-semibold">Bet</th>
                                <th class="px-3 py-2 text-center font-semibold">Trade</th>
                                <th class="px-3 py-2 text-left font-semibold">Result</th>
                            </tr>
                        </thead>
                        <tbody id="previewBody" class="divide-y divide-gray-100"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
/*
 * The layout renders the scripts stack inside <head>, so this file runs before
 * the body exists. Without DOMContentLoaded every getElementById below returns
 * null, the first addEventListener throws, and no button is ever wired up —
 * clicking Preview silently does nothing.
 */
document.addEventListener('DOMContentLoaded', function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const previewUrl = "{{ route('websites.import.preview') }}";
    const commitUrl  = "{{ route('websites.import.commit') }}";

    const btnPreview = document.getElementById('btnPreview');
    const btnImport  = document.getElementById('btnImport');
    const resultBox  = document.getElementById('resultBox');
    const statsRow   = document.getElementById('statsRow');
    const messageBox = document.getElementById('messageBox');
    const dupBox     = document.getElementById('duplicatesBox');
    const dupBody    = document.getElementById('duplicatesBody');
    const previewBody = document.getElementById('previewBody');

    let token = null;

    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[c]);

    const money = (v) => (v === null || v === undefined || v === '') ? '—' : Number(v).toLocaleString('en-US');
    const yesNo = (v) => v === true ? 'Yes' : (v === false ? 'No' : '—');

    function badge(label, value, tone) {
        const tones = {
            green: 'bg-green-50 text-green-700 border-green-200',
            amber: 'bg-amber-50 text-amber-800 border-amber-200',
            red:   'bg-red-50 text-red-700 border-red-200',
            gray:  'bg-gray-50 text-gray-700 border-gray-200',
        };
        return `<span class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 ${tones[tone]}">
                    <strong>${value}</strong> ${esc(label)}
                </span>`;
    }

    function showMessage(text, tone) {
        const tones = {
            green: 'bg-green-50 text-green-800 border border-green-200',
            red:   'bg-red-50 text-red-800 border border-red-200',
            amber: 'bg-amber-50 text-amber-900 border border-amber-200',
        };
        messageBox.className = `mb-4 rounded-lg px-4 py-3 ${tones[tone]}`;
        messageBox.innerHTML = text;
        messageBox.classList.remove('hidden');
    }

    function renderPreview(payload) {
        const s = payload.stats;
        statsRow.innerHTML =
            badge('to create', s.new, 'green') +
            badge('already exist', s.existing, 'amber') +
            badge('rejected', s.invalid, s.invalid ? 'red' : 'gray') +
            badge('rows read', s.total, 'gray');

        if (payload.truncated) {
            showMessage(`Only the first ${payload.preview_limit} rows were read. Split the file and import it in parts.`, 'amber');
        }

        previewBody.innerHTML = payload.rows.map((r) => {
            let result;
            if (!r.valid) {
                result = `<span class="text-red-700">${r.errors.map(esc).join('<br>')}</span>`;
            } else if (r.exists) {
                result = `<span class="text-amber-700">Already exists${r.existing_trashed ? ' (deleted)' : ''}</span>`;
            } else {
                result = '<span class="text-green-700">Will be created</span>';
            }

            return `<tr class="${r.valid ? '' : 'bg-red-50/40'}">
                <td class="px-3 py-1.5 text-gray-500">${r.line}</td>
                <td class="px-3 py-1.5 font-medium">${esc(r.domain_name)}</td>
                <td class="px-3 py-1.5">${esc(r.currency_code)}</td>
                <td class="px-3 py-1.5 text-right">${money(r.publisher_price)}</td>
                <td class="px-3 py-1.5 text-right">${money(r.special_topic_price)}</td>
                <td class="px-3 py-1.5 text-right">${money(r.link_builder_amount)}</td>
                <td class="px-3 py-1.5 text-right font-semibold">${money(r.price)}</td>
                <td class="px-3 py-1.5 text-right font-semibold">${money(r.sensitive_topic_price)}</td>
                <td class="px-3 py-1.5 text-center">${r.categories}</td>
                <td class="px-3 py-1.5 text-center">${yesNo(r.betting)}</td>
                <td class="px-3 py-1.5 text-center">${yesNo(r.trading)}</td>
                <td class="px-3 py-1.5">${result}</td>
            </tr>`;
        }).join('');

        const dupes = payload.rows.filter((r) => r.valid && r.exists);
        if (dupes.length) {
            dupBody.innerHTML = dupes.map((r) => `
                <tr>
                    <td class="px-3 py-1.5 text-gray-500">${r.line}</td>
                    <td class="px-3 py-1.5 font-medium">${esc(r.domain_name)}${r.existing_trashed ? ' <span class="text-gray-400">(deleted)</span>' : ''}</td>
                    <td class="px-3 py-1.5">
                        <label class="mr-3"><input type="radio" name="dup_${esc(r.domain_name)}" value="update" data-domain="${esc(r.domain_name)}"> Update</label>
                        <label><input type="radio" name="dup_${esc(r.domain_name)}" value="skip" data-domain="${esc(r.domain_name)}" checked> Skip</label>
                    </td>
                </tr>`).join('');
            dupBox.classList.remove('hidden');
        } else {
            dupBox.classList.add('hidden');
        }

        resultBox.classList.remove('hidden');
        btnImport.disabled = (s.new + s.existing) === 0;
    }

    function collectExistingActions() {
        const actions = {};
        dupBody.querySelectorAll('input[type=radio]:checked').forEach((el) => {
            actions[el.dataset.domain] = el.value;
        });
        return actions;
    }

    btnPreview.addEventListener('click', async () => {
        const file = document.getElementById('csvFile').files[0];
        if (!file) { alert('Choose a CSV file first.'); return; }

        btnPreview.disabled = true;
        btnPreview.textContent = 'Reading…';
        messageBox.classList.add('hidden');

        const fd = new FormData();
        fd.append('file', file);
        fd.append('has_header', document.getElementById('hasHeader').checked ? '1' : '0');

        try {
            const res = await fetch(previewUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: fd,
            });
            const json = await res.json();

            if (!res.ok || !json.ok) {
                const detail = json.errors ? Object.values(json.errors).flat().join(' ') : (json.message || 'Could not read that file.');
                resultBox.classList.remove('hidden');
                showMessage(esc(detail), 'red');
                return;
            }

            token = json.token;
            renderPreview(json);
        } catch (e) {
            resultBox.classList.remove('hidden');
            showMessage('Something went wrong reading the file.', 'red');
        } finally {
            btnPreview.disabled = false;
            btnPreview.textContent = 'Preview';
        }
    });

    document.getElementById('btnUpdateAll').addEventListener('click', () => {
        dupBody.querySelectorAll('input[value=update]').forEach((el) => { el.checked = true; });
    });
    document.getElementById('btnSkipAll').addEventListener('click', () => {
        dupBody.querySelectorAll('input[value=skip]').forEach((el) => { el.checked = true; });
    });

    btnImport.addEventListener('click', async () => {
        if (!token) return;
        if (!confirm('Import these domains? They will be visible to customers immediately.')) return;

        btnImport.disabled = true;
        btnImport.textContent = 'Importing…';

        try {
            const res = await fetch(commitUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    token: token,
                    default_existing_action: 'skip',
                    existing_actions: collectExistingActions(),
                }),
            });
            const json = await res.json();

            if (!res.ok || !json.ok) {
                showMessage(esc(json.message || 'The import failed. Nothing was saved.'), 'red');
                return;
            }

            token = null;
            showMessage(
                `<strong>Import finished.</strong> ${json.created} created, ${json.updated} updated, ` +
                `${json.skipped} skipped, ${json.failed} rejected. ` +
                `<a href="{{ route('websites.index') }}" class="underline">Open Domains</a>`,
                'green'
            );
        } catch (e) {
            showMessage('Something went wrong. Nothing was saved.', 'red');
        } finally {
            btnImport.textContent = 'Import';
        }
    });
});
</script>
@endpush
