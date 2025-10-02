@php
    // ===============================
    //  Two built-in templates (backend defaults)
    // ===============================
    // Use square-bracket placeholders so they render safely: [domain], [publisher price], [special topic price]
    $boTemplates = [
        'first' => [
            'label'   => 'First email',
            'subject' => 'Quick confirmation on guest post rates',
            'body'    => "Hi,

I hope you’re doing well. This is Martina from Menford.

We’ve already collaborated with you on [domain] and really appreciated the experience.

I’m reaching out to confirm your current rates for publishing a guest post with a permanent dofollow link, without the sponsored tag. Could you please confirm that the rate for a standard article is [publisher price], and that the rate for an article on sensitive topics is [special topic price]?

Looking forward to your reply so I can offer your site to our client.

Best,
Martina",
        ],
        'followup' => [
            'label'   => 'Follow-up',
            'subject' => 'Following up on guest post rates',
            'body'    => "Hi,

Just following up on my previous email — could you confirm your current rates for a guest post with a permanent dofollow link?

It would help me propose your site to our client.

Best,
Martina",
        ],
    ];

    // initial template = first
    $tplKey = 'first';
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
            <!-- Template selector -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="md:col-span-1">
                    <label for="boTemplate" class="block text-gray-700 font-medium mb-1">Template</label>
                    <select id="boTemplate"
                            class="w-full border border-gray-300 rounded px-2 py-2 focus:ring-cyan-500 focus:border-cyan-500">
                        @foreach($boTemplates as $key => $tpl)
                            <option value="{{ $key }}" @selected($key === $tplKey)>{{ $tpl['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <div class="text-[11px] text-gray-600 bg-gray-50 border border-gray-200 rounded p-2">
                        For the <b>First email</b>, the sentence about <i>“sensitive topics”</i> will be
                        <b>automatically removed</b> for any site where <code>special_topic_price</code> is empty.
                        This happens per recipient during bulk send.
                    </div>
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

                <div class="col-span-1 md:col-span-2">
                    <label class="block text-gray-700 font-medium mb-1">Subject *</label>
                    <input type="text" id="boSubject"
                           value="{{ $boTemplates[$tplKey]['subject'] }}"
                           class="w-full border border-gray-300 rounded px-2 py-2 focus:ring-cyan-500 focus:border-cyan-500">
                </div>

                <div class="col-span-1 md:col-span-2">
                    <label class="block text-gray-700 font-medium mb-1">
                        Email body *
                        <span class="text-gray-500 font-normal">
                            (placeholders you can use: <code>[domain] [publisher price] [special topic price] [brand] [target url]</code>)
                        </span>
                    </label>
                    <textarea id="boBody" rows="10"
                              class="w-full border border-gray-300 rounded px-2 py-2 leading-5 font-mono focus:ring-cyan-500 focus:border-cyan-500">{{ $boTemplates[$tplKey]['body'] }}</textarea>
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

{{-- Provide templates to JS in a safe way --}}
<script>
    window.BO_TEMPLATES = @json($boTemplates);
</script>
