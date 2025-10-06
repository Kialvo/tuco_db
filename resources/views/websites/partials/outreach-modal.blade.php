@php
    $outreach   = config('outreach');
    $languages  = $outreach['languages'] ?? [];
    $templates  = $outreach['templates'] ?? [];

    $defaultLang = 'en';
    $defaultKind = 'first';

    $subjectDefault = $templates[$defaultLang][$defaultKind]['subject'] ?? '';
    $bodyDefault    = $templates[$defaultLang][$defaultKind]['body']    ?? '';
@endphp

<div id="bulkOutreachModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-3xl">
        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-800">Send outreach</h3>
            <button id="boCloseTop" type="button" class="text-gray-500 hover:text-gray-700" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="p-4 space-y-3 text-xs">
            <!-- Language + Template -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Language</label>
                    <select id="boLanguage"
                            class="w-full border border-gray-300 rounded px-2 py-2 focus:ring-cyan-500 focus:border-cyan-500">
                        @foreach($languages as $code => $label)
                            <option value="{{ $code }}" @selected($code === $defaultLang)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-1">Template</label>
                    <select id="boTemplate"
                            class="w-full border border-gray-300 rounded px-2 py-2 focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="first" @selected($defaultKind==='first')>First email</option>
                        <option value="followup" @selected($defaultKind==='followup')>Follow-up</option>
                    </select>
                </div>

                <div class="text-[11px] text-gray-600 bg-gray-50 border border-gray-200 rounded p-2">
                    In the <b>First</b> template, the part about <i>“sensitive topics”</i> is inserted per recipient via
                    <code>@{{ sensitive_line }}</code> and <b>automatically removed</b> when a site has no
                    <code>special_topic_price</code>.
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Target URL (optional)</label>
                    <input type="url" id="boTargetUrl" placeholder="https://client.com/landing"
                           class="w-full border border-gray-300 rounded px-2 py-2 focus:ring-cyan-500 focus:border-cyan-500">
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-1">Brand (optional)</label>
                    <input type="text" id="boBrand" placeholder="Client / brand name"
                           class="w-full border border-gray-300 rounded px-2 py-2 focus:ring-cyan-500 focus:border-cyan-500">
                </div>

                <!-- Subject -->
                <div class="col-span-1 md:col-span-2">
                    <label class="block text-gray-700 font-medium mb-1">Subject *</label>
                    <input type="text" id="boSubject"
                           value="{{ $subjectDefault }}"
                           class="w-full border border-gray-300 rounded px-2 py-2 focus:ring-cyan-500 focus:border-cyan-500">
                </div>

                <!-- Body -->
                <div class="col-span-1 md:col-span-2">
                    <label class="block text-gray-700 font-medium mb-1">
                        Email body *
                        <span class="text-gray-500 font-normal">
                            (placeholders: <code>[domain] [publisher price] [special topic price] [brand] [target url]</code>;
                            the first template also uses <code>@{{ sensitive_line }}</code>)
                        </span>
                    </label>
                    <textarea id="boBody" rows="10"
                              class="w-full border border-gray-300 rounded px-2 py-2 leading-5 font-mono focus:ring-cyan-500 focus:border-cyan-500">{{ $bodyDefault }}</textarea>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <input id="boOnlyPast" type="checkbox" class="h-4 w-4 text-cyan-600" checked>
                <label for="boOnlyPast" class="text-gray-700">
                    Only send to websites with Status = <b>past</b> (prior collaboration)
                </label>
            </div>

            <div id="boPreviewBox" class="hidden border border-gray-200 rounded p-2">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-gray-700">Total selected: <b id="boSelTotal">0</b></span>
                    <span class="text-green-700">Eligible: <b id="boEligible">0</b></span>
                    <span class="text-red-700">Skipped: <b id="boSkipped">0</b></span>
                    <span class="text-amber-700">Sensitive-clause skipped for: <b id="boNoSpecialCount">0</b></span>
                </div>
                <div id="boSkippedList" class="mt-2 max-h-40 overflow-auto text-[11px] text-gray-600"></div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-4 py-3 border-t border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <button id="boCheck" type="button"
                        class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300 text-gray-800 text-xs">
                    Check recipients
                </button>
            </div>
            <div class="flex items-center gap-2">
                <button id="boCloseBottom" type="button"
                        class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300 text-gray-800 text-xs">
                    Cancel
                </button>
                <button id="boSend" type="button"
                        class="px-3 py-2 rounded bg-cyan-700 hover:bg-cyan-800 text-white text-xs disabled:opacity-50 disabled:cursor-not-allowed">
                    Send
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Provide templates to JS safely --}}
<script>
    window.BO_TEMPLATES     = @json($templates);
    window.BO_LANGUAGES     = @json($languages);
    window.BO_DEFAULT_LANG  = @json($defaultLang);
    window.BO_DEFAULT_KIND  = @json($defaultKind);

    (function () {
        const langSel = document.getElementById('boLanguage');
        const kindSel = document.getElementById('boTemplate');
        const subjEl  = document.getElementById('boSubject');
        const bodyEl  = document.getElementById('boBody');

        function loadTemplate() {
            const lang = (langSel?.value || window.BO_DEFAULT_LANG || 'en');
            const kind = (kindSel?.value || window.BO_DEFAULT_KIND || 'first');
            const t = (window.BO_TEMPLATES[lang] && window.BO_TEMPLATES[lang][kind]) ? window.BO_TEMPLATES[lang][kind] : null;
            if (t) {
                subjEl.value = t.subject || '';
                bodyEl.value = t.body || '';
            }
        }

        if (langSel && kindSel) {
            langSel.addEventListener('change', loadTemplate);
            kindSel.addEventListener('change', loadTemplate);
        }
        // Ensure defaults are painted once on load:
        loadTemplate();
        // Also expose for the outer page script (boOpenModal) to force-refresh when opening:
        window.BO_loadTemplate = loadTemplate;
    })();
</script>

