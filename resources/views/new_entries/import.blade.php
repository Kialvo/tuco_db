{{-- resources/views/new_entries/import.blade.php --}}
@extends('layouts.dashboard')

@section('content')
    <div class="px-6 py-6 bg-gray-50 min-h-screen text-sm">

        <h1 class="text-lg font-bold text-gray-700 mb-4">Bulk Import New Entries (CSV)</h1>

        <div class="bg-white rounded-xl shadow p-4 border">
            <form id="importForm"
                  method="POST"
                  action="{{ route('new_entries.import.preview') }}"  {{-- fallback if JS fails --}}
                  enctype="multipart/form-data"
                  class="space-y-4">
                @csrf

                <div class="flex items-center gap-3">
                    <input type="file" name="file" id="csvFile" accept=".csv,text/csv" required
                           class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4
                      file:rounded-md file:border-0 file:text-sm file:font-semibold
                      file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100"/>

                    <a href="{{ route('new_entries.import.sample') }}"
                       class="text-cyan-700 underline">Download sample</a>
                </div>

                <label class="flex items-center gap-2">
                    <input type="hidden" name="create_missing_categories" value="0">
                    <input type="checkbox" name="create_missing_categories" value="1" class="rounded">
                    <span>Create missing Categories</span>
                </label>

                <label class="flex items-center gap-2">
                    <input type="hidden" name="dedupe_by_domain" value="0">
                    <input type="checkbox" name="dedupe_by_domain" value="1" class="rounded" checked>
                    <span>Deduplicate by domain</span>
                </label>


                <div class="flex gap-2">
                    <button type="submit" id="btnPreview"
                            class="bg-cyan-600 text-white px-4 py-2 rounded shadow hover:bg-cyan-700">
                        Preview
                    </button>
                    <button type="button" id="btnConfirm" disabled
                            class="bg-emerald-600 text-white px-4 py-2 rounded shadow hover:bg-emerald-700 disabled:opacity-50">
                        Import All
                    </button>
                </div>
            </form>

        </div>

        <div id="previewBox" class="mt-6 hidden">
            <div class="bg-white rounded-xl shadow border">
                <div class="p-4 flex items-center justify-between">
                    <div>
                        <div class="font-semibold">Preview</div>
                        <div class="text-gray-500 text-xs">Showing first <span id="pvLimit"></span> rows</div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm">Rows in file: <span id="pvTotal" class="font-semibold">0</span></div>
                        <div class="text-sm">Errors (preview): <span id="pvErrors" class="font-semibold text-rose-600">0</span></div>
                    </div>
                </div>

                <div class="overflow-auto max-h-[60vh] border-t">
                    <table class="min-w-full text-xs">
                        <thead class="bg-gray-100 sticky top-0">
                        <tr>
                            <th class="px-3 py-2 text-left">Line</th>
                            <th class="px-3 py-2 text-left">Domain</th>
                            <th class="px-3 py-2 text-left">Country</th>
                            <th class="px-3 py-2 text-left">Contact</th>
                            <th class="px-3 py-2 text-left">Currency</th>
                            <th class="px-3 py-2 text-left">Prices</th>
                            <th class="px-3 py-2 text-left">SEO</th>
                            <th class="px-3 py-2 text-left">Categories</th>
                            <th class="px-3 py-2 text-left">Errors</th>
                        </tr>
                        </thead>
                        <tbody id="pvBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Busy overlay -->
        <div id="busy" class="hidden fixed inset-0 z-[90] flex items-center justify-center select-none">
            <div class="absolute inset-0 bg-black/40"></div>
            <div class="relative bg-white rounded-xl shadow-2xl p-6 w-[360px] text-center">
                <div class="mx-auto mb-3 h-8 w-8 rounded-full border-4 border-gray-300 border-t-transparent animate-spin"></div>
                <div id="busyTitle" class="font-semibold">Working…</div>
                <div id="busyMsg" class="text-gray-500 text-sm mt-1">Please wait.</div>
            </div>
        </div>

        {{-- Centered toast --}}
        <div id="toast" class="hidden fixed inset-0 z-[100] flex items-center justify-center">
            <div class="bg-black/50 absolute inset-0"></div>
            <div class="relative bg-white shadow-2xl rounded-xl p-6 w-[520px] text-center">
                <div id="toastTitle" class="text-lg font-semibold mb-1">Import complete</div>
                <div id="toastMsg" class="text-gray-600 text-sm"></div>
                <div class="mt-4">
                    <button id="closeToast" class="px-4 py-2 bg-cyan-600 text-white rounded hover:bg-cyan-700">
                        Close
                    </button>
                </div>
            </div>
        </div>


    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form       = document.getElementById('importForm');
            const fileInput  = document.getElementById('csvFile');
            const btnPreview = document.getElementById('btnPreview');
            const btnConfirm = document.getElementById('btnConfirm');

            const previewBox = document.getElementById('previewBox');
            const pvLimit  = document.getElementById('pvLimit');
            const pvTotal  = document.getElementById('pvTotal');
            const pvErrors = document.getElementById('pvErrors');
            const pvBody   = document.getElementById('pvBody');

            const toast      = document.getElementById('toast');
            const toastTitle = document.getElementById('toastTitle');
            const toastMsg   = document.getElementById('toastMsg');
            const closeToast = document.getElementById('closeToast');
            const busy      = document.getElementById('busy');
            const busyTitle = document.getElementById('busyTitle');
            const busyMsg   = document.getElementById('busyMsg');

            function showBusy(title = 'Working…', msg = 'Please wait.') {
                if (busy) {
                    if (busyTitle) busyTitle.textContent = title;
                    if (busyMsg)   busyMsg.textContent   = msg;
                    busy.classList.remove('hidden');
                    document.body.setAttribute('aria-busy', 'true');
                    document.body.style.overflow = 'hidden'; // lock scroll
                }
            }

            function hideBusy() {
                busy?.classList.add('hidden');
                document.body.removeAttribute('aria-busy');
                document.body.style.overflow = '';
            }


            let token = null;

            function showToast(title, msg) {
                if (toast && toastTitle && toastMsg) {
                    toastTitle.textContent = title || '';
                    toastMsg.textContent   = msg || '';
                    toast.classList.remove('hidden');
                } else {
                    alert(`${title}\n\n${msg}`);
                }
            }
            closeToast?.addEventListener('click', () => toast.classList.add('hidden'));

            function escapeHtml(s) {
                const d = document.createElement('div');
                d.innerText = String(s ?? '');
                return d.innerHTML;
            }

            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                if (!fileInput?.files?.length) {
                    showToast('Select a CSV', 'Please choose a CSV file first.');
                    return;
                }

                btnPreview.disabled = true;
                btnConfirm.disabled = true;
                pvBody && (pvBody.innerHTML = '');
                previewBox?.classList.add('hidden');

                const fd = new FormData(form);

                showBusy('Analyzing CSV…', 'Validating fields and preparing the preview.');

                let res;
                try {
                    res = await fetch("{{ route('new_entries.import.preview') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: fd
                    });
                } catch (err) {
                    hideBusy();
                    btnPreview.disabled = false;
                    showToast('Preview failed', err?.message || 'Network error.');
                    return;
                }

                if (res.redirected) {
                    hideBusy();
                    btnPreview.disabled = false;
                    showToast('Session issue', 'The server redirected the request. Please refresh and try again (login if needed).');
                    return;
                }

                let data;
                try {
                    data = await res.json();
                } catch (_) {
                    hideBusy();
                    btnPreview.disabled = false;
                    const text = await res.text();
                    showToast('Preview failed', (text || '').slice(0, 600));
                    return;
                }

                btnPreview.disabled = false;

                if (!res.ok || data.ok === false) {
                    hideBusy();
                    const msg = data?.message || 'Validation failed.';
                    const errs = data?.errors ? JSON.stringify(data.errors) : '';
                    showToast('Preview failed', `${msg}\n${errs}`);
                    return;
                }

                // success
                token = data.token;
                pvLimit && (pvLimit.textContent = data.limit);
                pvTotal && (pvTotal.textContent = data.stats?.total ?? 0);
                pvErrors && (pvErrors.textContent = data.errors_count ?? 0);

                const rows = data.preview || [];
                if (pvBody) {
                    pvBody.innerHTML = rows.map(r => {
                        const d = r.data || {};
                        const cats = (r.categories || []).join(', ');
                        const errs = (r.errors || [])
                            .map(x => `<div class="text-rose-700">• ${escapeHtml(x)}</div>`)
                            .join('');

                        const prices = [d.publisher_price, d.no_follow_price, d.special_topic_price]
                            .filter(x => x !== null && x !== undefined).join(' / ');

                        const seo = [
                            d.DA ? `DA:${d.DA}` : '',
                            d.PA ? `PA:${d.PA}` : '',
                            d.DR ? `DR:${d.DR}` : '',
                            d.ahrefs_keyword ? `KW:${d.ahrefs_keyword}` : '',
                            d.ahrefs_traffic ? `T:${d.ahrefs_traffic}` : '',
                            d.keyword_vs_traffic ? `K/T:${d.keyword_vs_traffic}` : ''
                        ].filter(Boolean).join('  ');

                        return `
              <tr class="border-b last:border-b-0">
                <td class="px-3 py-2">${r.line}</td>
                <td class="px-3 py-2">${escapeHtml(d.domain_name || '')}</td>
                <td class="px-3 py-2">${escapeHtml(d.country_id ? 'OK' : '')}</td>
                <td class="px-3 py-2">${escapeHtml(d.contact_id ? 'OK' : '')}</td>
                <td class="px-3 py-2">${escapeHtml(d.currency_code || '')}</td>
                <td class="px-3 py-2">${escapeHtml(prices)}</td>
                <td class="px-3 py-2 whitespace-nowrap">${escapeHtml(seo)}</td>
                <td class="px-3 py-2">${escapeHtml(cats)}</td>
                <td class="px-3 py-2">${errs || '<span class="text-emerald-700">OK</span>'}</td>
              </tr>`;
                    }).join('');
                }

                previewBox?.classList.remove('hidden');
                btnConfirm.disabled = false; // enable Import only after successful preview
                hideBusy();
            });


            btnConfirm?.addEventListener('click', async () => {
                if (!token) {
                    showToast('Run Preview first', 'Please preview the file, then click Import.');
                    return;
                }

                btnConfirm.disabled = true;
                showBusy('Importing…', 'Writing entries and linking categories. Please wait.');

                let res;
                try {
                    res = await fetch("{{ route('new_entries.import.commit') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ token })
                    });
                } catch (err) {
                    hideBusy();
                    btnConfirm.disabled = false;
                    showToast('Import failed', err?.message || 'Network error.');
                    return;
                }

                let data;
                try {
                    data = await res.json();
                } catch (_) {
                    hideBusy();
                    btnConfirm.disabled = false;
                    const text = await res.text();
                    showToast('Import failed', (text || '').slice(0, 600));
                    return;
                }

                hideBusy();
                btnConfirm.disabled = false;

                if (!res.ok || data.ok === false) {
                    const msg = data?.message || 'Import failed.';
                    const errs = data?.failures ? JSON.stringify(data.failures.slice(0,3)) : '';
                    showToast('Import failed', `${msg}\n${errs}`);
                    return;
                }

                showToast('Import complete', `Created: ${data.created} • Updated: ${data.updated} • Failed: ${data.failed}`);
            });

        });
    </script>


@endpush
