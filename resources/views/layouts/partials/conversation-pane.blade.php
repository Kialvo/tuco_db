{{-- CRM-style conversation slide-over (updates + replies). One instance per
     page (included by the dashboard layout for staff); opened from anywhere:
       tucoConversations.open({type:'campaign'|'publication', id, label, detailsUrl, detailsLabel})
     Mirrors menford-crm's ConversationPane: composer on top, threads below,
     indented replies, Monday-style bottom reply pill with @-prefill,
     own-item Edit/Delete, (edited) tags, mark-entity-read on open. --}}
@php
    $convStaff = \App\Models\User::whereIn('role', ['admin', 'editor'])->orderBy('name')->get(['id', 'name']);
@endphp

<div id="convBackdrop" class="hidden fixed inset-0 z-[75] bg-black/30"></div>
<div id="convPane" class="hidden fixed right-0 top-0 z-[76] flex h-full w-[420px] max-w-full flex-col bg-white shadow-2xl">
    {{-- Header --}}
    <div class="flex shrink-0 items-center gap-3 border-b border-gray-200 px-4 py-3">
        <div class="min-w-0 shrink">
            <p id="convTypeTag" class="text-[10px] font-semibold uppercase tracking-[0.14em] text-violet-500"></p>
            <h2 id="convTitle" class="truncate text-[15px] font-bold text-gray-800"></h2>
        </div>
        <a id="convDetailsLink" href="#" class="mx-auto shrink-0 whitespace-nowrap rounded-md border border-green-500/40 px-2.5 py-1.5 text-[11px] font-semibold text-green-600 hover:bg-green-50"></a>
        <button type="button" id="convClose" aria-label="Close" class="shrink-0 rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12" /></svg>
        </button>
    </div>

    {{-- Compose new update --}}
    <div class="shrink-0 border-b border-gray-200 p-4 space-y-2">
        <div class="relative">
            <textarea id="convComposer" rows="3" placeholder="Write an update… (@ to mention)"
                class="w-full resize-none rounded-md border border-gray-300 px-3 py-2.5 text-sm focus:ring-green-500 focus:border-green-500 placeholder:text-gray-400"></textarea>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-[11px] text-gray-400">Ctrl+Enter to post · @ to mention</span>
            <button type="button" id="convPost" class="rounded-md bg-green-600 px-4 py-1.5 text-[13px] font-semibold text-white hover:bg-green-700 disabled:opacity-40">Update</button>
        </div>
    </div>

    {{-- Thread --}}
    <div id="convThread" class="flex-1 overflow-y-auto slim-scroll p-4 space-y-5"></div>
</div>

{{-- INLINE script (not @push — head-stack renders before layout partials) --}}
<script>
window.tucoConversations = (function () {
    const STAFF = @json($convStaff);
    const ME_ID = {{ auth()->id() ?? 'null' }};
    const BASE  = "{{ url('conversations') }}";
    const READ_URL = "{{ route('notifications.read') }}";

    const esc = s => $('<i/>').text(s ?? '').html();
    let ctx = null;          // {type, id, label, detailsUrl, detailsLabel}
    let updates = [];

    function fmtTime(iso) {
        const d = new Date(iso);
        return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
            + ' · ' + d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
    }
    function initialsOf(name) {
        return (name || '?').split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
    }
    function avatar(name, size) {
        return `<span class="inline-flex shrink-0 items-center justify-center rounded-full bg-green-100 text-[10px] font-bold text-green-700" style="width:${size}px;height:${size}px">${esc(initialsOf(name))}</span>`;
    }
    const editedTag = '<span class="text-[10px] italic text-gray-400">(edited)</span>';

    /* ── rendering ── */
    function replyHtml(r) {
        return `
        <div class="flex gap-2" data-reply="${r.id}">
            ${avatar(r.author.name, 24)}
            <div class="flex-1 min-w-0">
                <div class="flex items-baseline gap-2 flex-wrap">
                    <span class="text-[12px] font-semibold text-gray-800">${esc(r.author.name)}</span>
                    <span class="text-[11px] text-gray-400">${fmtTime(r.created_at)}</span>
                    ${r.edited_at ? editedTag : ''}
                    ${r.own ? '<button type="button" class="conv-edit-reply ml-auto text-[11px] text-gray-400 hover:text-green-600" data-id="' + r.id + '">Edit</button>' : ''}
                </div>
                <div class="conv-reply-body">
                    <p class="text-[12px] leading-relaxed text-gray-600 break-words whitespace-pre-line">${tucoMentions.render(r.body)}</p>
                    <button type="button" class="conv-reply-to mt-0.5 text-[11px] font-semibold text-green-600 hover:underline" data-author="${r.own ? '' : esc(r.author.name)}">Reply</button>
                </div>
            </div>
        </div>`;
    }

    function updateHtml(u) {
        return `
        <div class="space-y-2 conv-update" data-update="${u.id}">
            <div class="flex gap-3">
                ${avatar(u.author.name, 32)}
                <div class="flex-1 min-w-0">
                    <div class="flex items-baseline gap-2 flex-wrap">
                        <span class="text-[13px] font-semibold text-gray-800">${esc(u.author.name)}</span>
                        <span class="text-[11px] text-gray-400">${fmtTime(u.created_at)}</span>
                        ${u.edited_at ? editedTag : ''}
                        ${u.own ? `<span class="ml-auto flex gap-3">
                            <button type="button" class="conv-edit-update text-[11px] text-gray-400 hover:text-green-600">Edit</button>
                            <button type="button" class="conv-del-update text-[11px] text-red-400 hover:text-red-600">Delete</button>
                        </span>` : ''}
                    </div>
                    <div class="conv-update-body">
                        <p class="mt-1 text-[13px] leading-relaxed text-gray-600 break-words whitespace-pre-line">${tucoMentions.render(u.body)}</p>
                        <button type="button" class="conv-reply-to mt-1 text-[11px] font-semibold text-green-600 hover:underline" data-author="${u.own ? '' : esc(u.author.name)}">Reply</button>
                    </div>
                </div>
            </div>
            ${u.replies.length ? `<div class="ml-11 space-y-2 border-l-2 border-gray-100 pl-3 conv-replies">${u.replies.map(replyHtml).join('')}</div>`
                               : '<div class="ml-11 space-y-2 conv-replies hidden border-l-2 border-gray-100 pl-3"></div>'}
            <div class="ml-11 conv-reply-slot">
                <button type="button" class="conv-reply-pill w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-left text-[12px] text-gray-400 hover:border-green-500">
                    Write a reply and mention others with @
                </button>
            </div>
        </div>`;
    }

    function renderThread() {
        const $t = $('#convThread');
        if (!updates.length) {
            $t.html('<p class="text-center text-[13px] italic text-gray-400">No updates yet. Be the first to add one.</p>');
            return;
        }
        $t.html(updates.map(updateHtml).join(''));
    }

    /* ── open / close ── */
    function open(opts) {
        ctx = opts;
        $('#convTypeTag').text(opts.type).toggleClass('text-violet-500', opts.type === 'campaign').toggleClass('text-sky-500', opts.type !== 'campaign');
        $('#convTitle').text(opts.label || ('#' + opts.id));
        $('#convDetailsLink').attr('href', opts.detailsUrl || '#').text(opts.detailsLabel || (opts.type === 'campaign' ? 'CAMPAIGN DETAILS' : 'PUBLICATION DETAILS'));
        $('#convComposer').val('');
        $('#convThread').html('<p class="text-center text-[13px] text-gray-400">Loading…</p>');
        $('#convBackdrop, #convPane').removeClass('hidden');

        $.getJSON(BASE + '/' + opts.type + '/' + opts.id, d => { updates = d.updates ?? []; renderThread(); });

        // Opening the thread reads all its notifications (bubble + bell drop).
        $.ajax({ url: READ_URL, method: 'PATCH',
            data: { entity_type: opts.type, entity_id: String(opts.id) },
            success: () => $(document).trigger('tuco:notif-refresh') });
        $(document).trigger('tuco:conv-opened', [opts]);
    }

    function close() {
        $('#convBackdrop, #convPane').addClass('hidden');
        ctx = null;
        $(document).trigger('tuco:conv-closed');
    }

    $(function () {
        tucoMentions.attach($('#convComposer'), STAFF);

        $('#convClose, #convBackdrop').on('click', close);
        $(document).on('keydown', e => { if (e.key === 'Escape' && ctx) close(); });

        /* post new update */
        function postUpdate() {
            const raw = $('#convComposer').val().trim();
            if (!raw || !ctx) return;
            $('#convPost').prop('disabled', true);
            $.post(BASE + '/' + ctx.type + '/' + ctx.id, { body: tucoMentions.serialize(raw, STAFF) })
                .done(d => {
                    updates.push(d.update);
                    renderThread();
                    $('#convComposer').val('');
                    const $t = $('#convThread');
                    $t.scrollTop($t[0].scrollHeight);
                })
                .always(() => $('#convPost').prop('disabled', false));
        }
        $('#convPost').on('click', postUpdate);
        $('#convComposer').on('keydown', e => { if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') { e.preventDefault(); postUpdate(); } });

        /* reply composer (per update, Monday-style bottom slot) */
        function openReplyBox($update, prefillAuthor) {
            const $slot = $update.find('.conv-reply-slot');
            let $box = $slot.find('textarea');
            if (!$box.length) {
                $slot.html(`
                    <div class="space-y-2">
                        <div class="relative">
                            <textarea rows="2" placeholder="Write a reply and mention others with @"
                                class="w-full resize-none rounded-md border border-gray-300 px-3 py-2 text-[13px] focus:ring-green-500 focus:border-green-500"></textarea>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" class="conv-reply-send rounded-md bg-green-600 px-3 py-1.5 text-[12px] font-semibold text-white hover:bg-green-700">Reply</button>
                            <button type="button" class="conv-reply-cancel text-[12px] text-gray-400 hover:text-gray-700">Cancel</button>
                        </div>
                    </div>`);
                $box = $slot.find('textarea');
                tucoMentions.attach($box, STAFF);
            }
            if (prefillAuthor) {
                const cur = $box.val();
                if (!cur.includes('@' + prefillAuthor)) $box.val(cur ? cur + '@' + prefillAuthor + ' ' : '@' + prefillAuthor + ' ');
            }
            $box.trigger('focus');
            const end = $box.val().length;
            $box[0].setSelectionRange(end, end);
        }

        $(document).on('click', '#convPane .conv-reply-pill', function () {
            openReplyBox($(this).closest('.conv-update'), null);
        });
        $(document).on('click', '#convPane .conv-reply-to', function () {
            openReplyBox($(this).closest('.conv-update'), $(this).data('author') || null);
        });
        $(document).on('click', '#convPane .conv-reply-cancel', function () {
            $(this).closest('.conv-reply-slot').html('<button type="button" class="conv-reply-pill w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-left text-[12px] text-gray-400 hover:border-green-500">Write a reply and mention others with @</button>');
        });
        $(document).on('click', '#convPane .conv-reply-send', function () {
            const $update = $(this).closest('.conv-update');
            const uid = $update.data('update');
            const raw = $update.find('.conv-reply-slot textarea').val().trim();
            if (!raw) return;
            $.post(BASE + '/updates/' + uid + '/replies', { body: tucoMentions.serialize(raw, STAFF) })
                .done(d => {
                    const u = updates.find(x => x.id === uid);
                    if (u) { u.replies.push(d.reply); renderThread(); }
                });
        });
        $(document).on('keydown', '#convPane .conv-reply-slot textarea', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') { e.preventDefault(); $(this).closest('.conv-reply-slot').find('.conv-reply-send').trigger('click'); }
        });

        /* inline edit — update */
        $(document).on('click', '#convPane .conv-edit-update', function () {
            const $update = $(this).closest('.conv-update');
            const uid = $update.data('update');
            const u = updates.find(x => x.id === uid);
            inlineEditor($update.find('.conv-update-body'), u.body, body => {
                $.ajax({ url: BASE + '/updates/' + uid, method: 'PATCH', data: { body } })
                    .done(() => { u.body = body; u.edited_at = new Date().toISOString(); renderThread(); });
            });
        });

        /* inline edit — reply */
        $(document).on('click', '#convPane .conv-edit-reply', function () {
            const rid = $(this).data('id');
            const $row = $(this).closest('[data-reply]');
            const uid = $row.closest('.conv-update').data('update');
            const u = updates.find(x => x.id === uid);
            const r = u.replies.find(x => x.id === rid);
            inlineEditor($row.find('.conv-reply-body'), r.body, body => {
                $.ajax({ url: BASE + '/replies/' + rid, method: 'PATCH', data: { body } })
                    .done(() => { r.body = body; r.edited_at = new Date().toISOString(); renderThread(); });
            });
        });

        function inlineEditor($bodyEl, storedBody, onSave) {
            $bodyEl.data('orig', $bodyEl.html());
            $bodyEl.html(`
                <div class="mt-1 space-y-2">
                    <div class="relative">
                        <textarea rows="2" class="w-full resize-none rounded-md border border-gray-300 px-3 py-2 text-[13px] focus:ring-green-500 focus:border-green-500"></textarea>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" class="conv-edit-save rounded-md bg-green-600 px-3 py-1.5 text-[12px] font-semibold text-white hover:bg-green-700">Save</button>
                        <button type="button" class="conv-edit-cancel text-[12px] text-gray-400 hover:text-gray-700">Cancel</button>
                    </div>
                </div>`);
            const $ta = $bodyEl.find('textarea');
            $ta.val(tucoMentions.deserialize(storedBody));
            tucoMentions.attach($ta, STAFF);
            $ta.trigger('focus');
            $bodyEl.find('.conv-edit-save').on('click', () => {
                const raw = $ta.val().trim();
                if (raw) onSave(tucoMentions.serialize(raw, STAFF));
            });
            $bodyEl.find('.conv-edit-cancel').on('click', () => $bodyEl.html($bodyEl.data('orig')));
        }

        /* delete own update */
        $(document).on('click', '#convPane .conv-del-update', function () {
            const uid = $(this).closest('.conv-update').data('update');
            Swal.fire({ icon: 'warning', title: 'Delete this update?', text: 'Its replies will be hidden too.', showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#dc2626' })
                .then(res => {
                    if (!res.isConfirmed) return;
                    $.ajax({ url: BASE + '/updates/' + uid, method: 'DELETE' })
                        .done(() => { updates = updates.filter(x => x.id !== uid); renderThread(); });
                });
        });
    });

    return { open, close };
})();
</script>
