{{-- Notification bell (internal staff only) — reads the org-wide hub scoped
     to source_app='tuco' for the logged-in user's email. Rendered inside the
     sidebar's user block; the panel opens to the right of the sidebar. --}}
<button type="button" id="notifBellBtn"
        class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-gray-400 hover:bg-white/10 hover:text-white transition-all">
    <span class="relative flex-shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
        </svg>
        <span id="notifBadge" class="hidden absolute -right-2 -top-2 h-4 min-w-[16px] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white leading-none"></span>
    </span>
    Notifications
</button>

<div id="notifPanel" class="hidden fixed z-[70] w-[360px] rounded-xl border border-gray-200 bg-white shadow-2xl">
    <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
        <span class="text-[13px] font-bold text-gray-800">Notifications</span>
        <div class="flex items-center gap-3">
            <button type="button" id="notifMarkAll" class="hidden text-[11px] font-semibold text-green-600 hover:underline">Mark all read</button>
            <button type="button" id="notifClearAll" class="hidden text-[11px] font-semibold text-gray-400 hover:text-red-600">Clear all</button>
        </div>
    </div>
    <div id="notifList" class="max-h-[420px] overflow-y-auto slim-scroll"></div>
</div>

@push('scripts')
<script>
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

    function setCount(n) {
        count = Math.max(0, n);
        $badge.toggleClass('hidden', count === 0).toggleClass('flex', count > 0)
              .text(count > 99 ? '99+' : count);
        $('#notifMarkAll').toggleClass('hidden', count === 0);
    }

    /* ── polling (15s, visibility-aware — mirrors the SOPs bell) ── */
    let timer = null;
    function fetchCount() {
        $.getJSON(LIST_URL + '?unread=1', d => {
            const next = d.count ?? 0;
            if (next > count && open) fetchList();
            setCount(next);
        });
    }
    function start() { if (timer) return; fetchCount(); timer = setInterval(fetchCount, 15000); }
    function stop()  { if (timer) { clearInterval(timer); timer = null; } }
    if (document.visibilityState === 'visible') start();
    $(window).on('focus', start);
    $(document).on('visibilitychange', () => document.visibilityState === 'visible' ? start() : stop());

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
                    <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-green-100 text-[10px] font-bold text-green-700">${esc(initialsOf(n.from_user_name))}</span>
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

    /* ── open/close (panel opens right of the sidebar, above the button) ── */
    function position() {
        const r = $btn[0].getBoundingClientRect();
        const ph = $panel.outerHeight();
        const top = Math.max(8, Math.min(r.top - ph + r.height, window.innerHeight - ph - 8));
        $panel.css({ left: (r.right + 10) + 'px', top: top + 'px' });
    }
    $btn.on('click', function (e) {
        e.stopPropagation();
        if (open) { $panel.addClass('hidden'); open = false; return; }
        open = true;
        $list.html('<p class="px-4 py-6 text-center text-[13px] text-gray-400">Loading…</p>');
        $panel.removeClass('hidden');
        position();
        fetchList(position);
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
@endpush
