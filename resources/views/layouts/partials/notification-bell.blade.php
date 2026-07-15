{{-- Notification bell (topbar, internal staff only) — reads the org-wide hub
     scoped to source_app='tuco' for the logged-in user's email. --}}
<div class="relative" id="notifWrap">
    <button type="button" id="notifBellBtn" aria-label="Notifications" title="Notifications"
            class="relative flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition-colors hover:border-gray-300 hover:text-gray-800">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
        </svg>
        <span id="notifBadge" class="hidden absolute -right-1 -top-1 h-4 min-w-[16px] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white leading-none"></span>
    </button>

    <div id="notifPanel" class="hidden absolute right-0 top-[calc(100%+6px)] z-[70] w-[360px] rounded-xl border border-gray-200 bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
            <span class="text-[13px] font-bold text-gray-800">Notifications</span>
            <div class="flex items-center gap-3">
                <button type="button" id="notifMarkAll" class="hidden text-[11px] font-semibold text-green-600 hover:underline">Mark all read</button>
                <button type="button" id="notifClearAll" class="hidden text-[11px] font-semibold text-gray-400 hover:text-red-600">Clear all</button>
            </div>
        </div>
        <div id="notifList" class="max-h-[420px] overflow-y-auto slim-scroll"></div>
    </div>
</div>

{{-- INLINE script, deliberately NOT @push('scripts'): the layout's
     @stack('scripts') sits in <head> and renders BEFORE this partial is
     included, so anything pushed here would be silently dropped. Inline
     works because jQuery loads in <head>. --}}
<script>
/* ── Shared @mention utilities (loaded on every staff page via the bell) ──
   Wire format matches the Menford CRM: tokens `@[Name:id]` exist ONLY in
   storage. The composer always shows clean `@Name` text; serialize() converts
   names → tokens at submit time (longest names first, so "Simone DS" wins
   over "Simone"); deserialize() converts back for editing. */
window.tucoMentions = (function () {
    const esc = s => $('<i/>').text(s ?? '').html();
    const escRe = s => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

    function render(text) {
        const re = /@\[([^\]]+):(\d+)\]/g;
        let out = '', last = 0, m;
        while ((m = re.exec(text ?? '')) !== null) {
            out += esc(text.slice(last, m.index));
            out += '<span class="inline-block bg-green-100 text-green-700 font-semibold rounded px-1">@' + esc(m[1]) + '</span>';
            last = m.index + m[0].length;
        }
        return out + esc((text ?? '').slice(last));
    }

    // "@Super Admin hi" → "@[Super Admin:1] hi" (submit time)
    function serialize(text, users) {
        let out = text ?? '';
        [...users].sort((a, b) => b.name.length - a.name.length).forEach(u => {
            out = out.replace(new RegExp('@' + escRe(u.name) + '(?=\\s|$|[.,!?;:])', 'g'), '@[' + u.name + ':' + u.id + ']');
        });
        return out;
    }

    // "@[Super Admin:1] hi" → "@Super Admin hi" (edit prefill)
    function deserialize(body) {
        return (body ?? '').replace(/@\[([^\]]+):\d+\]/g, '@$1');
    }

    /* @-autocomplete on a textarea. Inserts clean `@Name ` — no tokens. */
    function attach($ta, users) {
        const $dd = $('<div class="hidden absolute z-[90] bg-white border border-gray-200 rounded-lg shadow-xl py-1 max-h-48 overflow-y-auto text-sm min-w-[200px]"></div>');
        $ta.parent().css('position', 'relative').append($dd);
        let matches = [], active = 0, fragStart = -1, fragLen = 0;

        function close() { $dd.addClass('hidden'); matches = []; }
        function renderDd() {
            $dd.html(matches.map((u, i) =>
                '<div class="mention-opt flex items-center gap-2 px-3 py-1.5 cursor-pointer ' + (i === active ? 'bg-green-50 text-green-700' : 'hover:bg-gray-50') + '" data-i="' + i + '">'
                + '<span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-green-100 text-[10px] font-bold text-green-700">' + esc(u.name.slice(0, 1).toUpperCase()) + '</span>'
                + esc(u.name) + '</div>'
            ).join(''));
            const pos = $ta.position();
            $dd.css({ left: pos.left, top: pos.top + $ta.outerHeight() + 2 }).removeClass('hidden');
        }
        function check() {
            const upToCaret = $ta.val().slice(0, $ta[0].selectionStart);
            const atIndex = upToCaret.lastIndexOf('@');
            if (atIndex === -1) { close(); return; }
            const frag = upToCaret.slice(atIndex + 1);
            if (!/^[a-zA-Z]*$/.test(frag)) { close(); return; }
            fragStart = atIndex; fragLen = frag.length;
            const q = frag.toLowerCase();
            matches = users.filter(u => u.name.toLowerCase().startsWith(q)).slice(0, 8);
            active = 0;
            matches.length ? renderDd() : close();
        }
        function pick(i) {
            const u = matches[i];
            if (!u) return;
            const v = $ta.val();
            // Insert the clean display name — serialize() tokenizes at submit.
            const insert = '@' + u.name + ' ';
            $ta.val(v.slice(0, fragStart) + insert + v.slice(fragStart + 1 + fragLen));
            const p = fragStart + insert.length;
            $ta[0].setSelectionRange(p, p);
            close();
            $ta.trigger('focus');
        }

        $ta.on('input click', check);
        $ta.on('keydown', function (e) {
            if ($dd.hasClass('hidden')) return;
            if (e.key === 'ArrowDown') { e.preventDefault(); active = (active + 1) % matches.length; renderDd(); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); active = (active - 1 + matches.length) % matches.length; renderDd(); }
            else if (e.key === 'Enter' || e.key === 'Tab') { e.preventDefault(); pick(active); }
            else if (e.key === 'Escape') { e.stopPropagation(); close(); }
        });
        // mousedown only prevents the textarea blur; selection happens on
        // click (stopPropagation) so the follow-up click can never hit a
        // modal/pane backdrop underneath and close it.
        $dd.on('mousedown', '.mention-opt', e => e.preventDefault());
        $dd.on('click', '.mention-opt', function (e) {
            e.preventDefault();
            e.stopPropagation();
            pick($(this).data('i'));
        });
        $ta.on('blur', () => setTimeout(close, 150));
    }

    return { render, serialize, deserialize, attach };
})();

$(function () {
    const LIST_URL = "{{ route('notifications.index') }}";
    const READ_URL = "{{ route('notifications.read') }}";

    const $btn = $('#notifBellBtn'), $badge = $('#notifBadge'),
          $panel = $('#notifPanel'), $list = $('#notifList');
    let open = false, count = 0, items = [];

    const esc = s => $('<i/>').text(s ?? '').html();

    function fmtRelative(iso) {
        const mins = Math.floor((Date.now() - new Date(iso).getTime()) / 60000);
        if (mins < 1) return 'just now';
        if (mins < 60) return mins + 'm ago';
        const hrs = Math.floor(mins / 60);
        if (hrs < 24) return hrs + 'h ago';
        return Math.floor(hrs / 24) + 'd ago';
    }

    function initialsOf(name) {
        if (!name) return '•';
        return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
    }

    const BASE_TITLE = document.title.replace(/^\(\d+\+?\)\s*/, '');
    function setCount(n) {
        count = Math.max(0, n);
        $badge.toggleClass('hidden', count === 0).toggleClass('flex', count > 0)
              .text(count > 99 ? '99+' : count);
        $('#notifMarkAll').toggleClass('hidden', count === 0);
        document.title = count > 0 ? '(' + (count > 99 ? '99+' : count) + ') ' + BASE_TITLE : BASE_TITLE;
    }

    /* ── polling (3s, visibility-aware — exact CRM cadence) ── */
    let timer = null;
    function fetchCount() {
        $.getJSON(LIST_URL + '?unread=1', d => {
            const next = d.count ?? 0;
            if (next > count && open) fetchList();
            setCount(next);
        });
    }
    function start() { if (timer) return; fetchCount(); timer = setInterval(fetchCount, 3000); }
    function stop()  { if (timer) { clearInterval(timer); timer = null; } }
    if (document.visibilityState === 'visible') start();
    $(window).on('focus', start);
    $(document).on('visibilitychange', () => document.visibilityState === 'visible' ? start() : stop());
    // Pages fire this after thread-level mark-read so the badge drops instantly.
    $(document).on('tuco:notif-refresh', fetchCount);

    /* ── render ── */
    function render() {
        $('#notifClearAll').toggleClass('hidden', items.length === 0);
        if (!items.length) {
            $list.html('<p class="px-4 py-6 text-center text-[13px] italic text-gray-400">No notifications yet.</p>');
            return;
        }
        $list.html(items.map(n => {
            const unread = !n.read_at;
            return `
            <div class="notif-row group relative border-b border-gray-50 last:border-0 ${n.link ? 'cursor-pointer' : ''}" data-id="${n.id}">
                <div class="flex items-start gap-3 px-4 py-3 ${unread ? 'bg-green-50/60' : ''} hover:bg-gray-50">
                    ${n.from_user_photo
                        ? `<img src="${esc(n.from_user_photo)}" alt="" class="mt-0.5 h-7 w-7 shrink-0 rounded-full object-cover border border-gray-200">`
                        : `<span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-green-100 text-[10px] font-bold text-green-700">${esc(initialsOf(n.from_user_name))}</span>`}
                    <div class="min-w-0 flex-1">
                        ${n.entity_label ? `<div class="truncate text-[12px] font-semibold text-gray-800">${esc(n.entity_label)}</div>` : ''}
                        <p class="mt-0.5 text-[12px] text-gray-600" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">${n.from_user_name ? '<strong>' + esc(n.from_user_name) + '</strong> ' : ''}${esc(n.body)}</p>
                        <p class="mt-0.5 text-[11px] text-gray-400">${fmtRelative(n.created_at)}</p>
                    </div>
                    ${unread ? '<span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-green-500"></span>' : ''}
                </div>
                <button type="button" class="notif-dismiss absolute right-2 top-2 hidden h-5 w-5 items-center justify-center rounded-full bg-white text-gray-400 shadow group-hover:flex hover:text-red-600" title="Dismiss" data-id="${n.id}">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>`;
        }).join(''));
    }

    function fetchList(cb) {
        $.getJSON(LIST_URL, d => { items = d.notifications ?? []; render(); if (cb) cb(); });
    }

    /* ── open/close ── */
    $btn.on('click', function (e) {
        e.stopPropagation();
        if (open) { $panel.addClass('hidden'); open = false; return; }
        open = true;
        $list.html('<p class="px-4 py-6 text-center text-[13px] text-gray-400">Loading…</p>');
        $panel.removeClass('hidden');
        fetchList();
    });
    $panel.on('click', e => e.stopPropagation());
    $(document).on('click', () => { if (open) { $panel.addClass('hidden'); open = false; } });
    $(document).on('keydown', e => { if (e.key === 'Escape' && open) { $panel.addClass('hidden'); open = false; } });

    /* ── actions ── */
    function markRead(id, cb) {
        $.ajax({ url: READ_URL, method: 'PATCH', data: { id }, complete: cb });
    }

    $list.on('click', '.notif-row', function () {
        const id = $(this).data('id');
        const n = items.find(x => x.id === id);
        if (!n) return;
        if (!n.read_at) { n.read_at = new Date().toISOString(); setCount(count - 1); render(); }
        if (n.link) {
            let sameOrigin = true;
            try { sameOrigin = new URL(n.link, window.location.href).origin === window.location.origin; } catch (e) {}
            markRead(id, () => {
                if (sameOrigin) window.location.assign(n.link);
                else window.open(n.link, '_blank', 'noopener,noreferrer');
            });
        } else {
            markRead(id);
        }
    });

    $list.on('click', '.notif-dismiss', function (e) {
        e.stopPropagation();
        const id = $(this).data('id');
        const n = items.find(x => x.id === id);
        items = items.filter(x => x.id !== id);
        if (n && !n.read_at) setCount(count - 1);
        render();
        $.ajax({ url: "{{ url('notifications') }}/" + id, method: 'DELETE' });
    });

    $('#notifMarkAll').on('click', function () {
        items.forEach(n => { n.read_at = n.read_at ?? new Date().toISOString(); });
        setCount(0);
        render();
        $.ajax({ url: READ_URL, method: 'PATCH', data: { all: 1 } });
    });

    $('#notifClearAll').on('click', function () {
        items = [];
        setCount(0);
        render();
        $.ajax({ url: "{{ url('notifications') }}?all=1", method: 'DELETE' });
    });
});
</script>
