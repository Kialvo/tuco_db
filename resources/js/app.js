import './bootstrap';
import './swal-shim';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

/* ─────────────────────────────────────────────────────────────
 |  Global tooltip + modal
 |  Handles two trigger types:
 |    • .tip  (guest) — reads text from child .tip-box
 |    • .metric-info-btn  (admin) — reads text from data-info attr
 |
 |  Hover  → small dark tooltip  (#global-tooltip)
 |  Click  → Swal info modal (guest .tip) / white popover (admin)
 |
 |  All styling is inline CSS — app.js is NOT in Tailwind's content scan.
 *────────────────────────────────────────────────────────────*/
(function initGlobalTooltip() {
    /* ── State (admin popover only — guest uses Swal modal) ── */
    let tipEl       = null;
    let popEl       = null;
    let raf         = null;
    let open        = false;
    let openTrigger = null;

    /* ── Lazy create: dark mini tooltip ── */
    const ensureTip = () => {
        if (tipEl) return tipEl;
        tipEl = document.createElement('div');
        tipEl.id = 'global-tooltip';
        tipEl.style.cssText =
            'position:fixed;z-index:9999;pointer-events:none;' +
            'background:#1e293b;color:#fff;' +
            'font-size:11px;line-height:1.4;font-weight:400;' +
            'padding:6px 10px;border-radius:6px;' +
            'max-width:224px;white-space:normal;word-break:break-word;' +
            'opacity:0;transition:opacity 0.1s;';
        document.body.appendChild(tipEl);
        return tipEl;
    };

    /* ── Lazy create: white popover card (admin only) ── */
    const ensurePop = () => {
        if (popEl) return popEl;
        popEl = document.createElement('div');
        popEl.id = 'global-popover';
        popEl.style.cssText =
            'position:fixed;z-index:10000;pointer-events:none;' +
            'background:#fff;color:#374151;' +
            'font-size:12px;line-height:1.55;font-weight:400;' +
            'padding:12px 16px;border-radius:8px;' +
            'border:1px solid #d1d5db;' +
            'box-shadow:0 4px 16px rgba(0,0,0,0.13);' +
            'max-width:264px;white-space:normal;word-break:break-word;' +
            'opacity:0;transition:opacity 0.15s;';
        document.body.appendChild(popEl);
        return popEl;
    };

    /* ── Read text from a trigger element ── */
    const getText = (trigger) => {
        if (trigger.classList.contains('metric-info-btn')) {
            return (trigger.dataset.info || '').trim();
        }
        const box = trigger.querySelector(':scope > .tip-box');
        return box ? box.textContent.trim() : '';
    };

    /* ── Position an element above (or below) trigger ── */
    const placeEl = (node, trigger, maxW) => {
        const r  = trigger.getBoundingClientRect();
        const cx = r.left + r.width / 2;
        const left = Math.max(maxW / 2 + 8, Math.min(cx, window.innerWidth - maxW / 2 - 8));
        node.style.left = left + 'px';
        if (r.top > 70) {
            node.style.top       = r.top + 'px';
            node.style.transform = 'translate(-50%, calc(-100% - 8px))';
        } else {
            node.style.top       = r.bottom + 'px';
            node.style.transform = 'translate(-50%, 8px)';
        }
    };

    /* ── Hover tooltip helpers ── */
    const showTip = (trigger) => {
        if (open) return;
        if (window.Swal && Swal.isVisible()) return;
        const text = getText(trigger);
        if (!text) return;
        const tip = ensureTip();
        tip.textContent = text;
        placeEl(tip, trigger, 224);
        tip.style.opacity = '1';
    };

    const hideTip = () => {
        if (tipEl) tipEl.style.opacity = '0';
    };

    /* ── Admin popover helpers ── */
    const showPop = (trigger) => {
        const text = getText(trigger);
        if (!text) return;
        hideTip();
        const pop = ensurePop();
        pop.textContent = text;
        placeEl(pop, trigger, 264);
        pop.style.opacity = '1';
        open        = true;
        openTrigger = trigger;
    };

    const hidePop = () => {
        if (popEl) popEl.style.opacity = '0';
        open        = false;
        openTrigger = null;
    };

    /* ── Hover ── */
    document.addEventListener('mouseover', (e) => {
        if (open) return;
        if (window.Swal && Swal.isVisible()) return;
        const trigger = e.target.closest && e.target.closest('.tip, .metric-info-btn');
        if (!trigger) return;
        showTip(trigger);
    }, true);

    document.addEventListener('mouseout', (e) => {
        if (open) return;
        const trigger = e.target.closest && e.target.closest('.tip, .metric-info-btn');
        if (!trigger) return;
        const next = e.relatedTarget;
        if (next && trigger.contains(next)) return;
        hideTip();
    }, true);

    /* ── Click ── */
    document.addEventListener('click', (e) => {
        /* Admin: metric-info-btn → white popover card (toggle) */
        const infoBtn = e.target.closest && e.target.closest('.metric-info-btn');
        if (infoBtn) {
            e.stopPropagation();
            if (open && openTrigger === infoBtn) { hidePop(); }
            else { showPop(infoBtn); }
            return;
        }
        /* Guest: .tip → Swal info modal */
        const tipTrigger = e.target.closest && e.target.closest('.tip');
        if (tipTrigger) {
            const text = getText(tipTrigger);
            if (!text) return;
            hideTip();
            if (window.Swal) {
                Swal.fire({ icon: 'info', text: text });
            }
            return;
        }
        if (open) hidePop();
    }, true);

    /* ── Escape dismisses admin popover (Swal handles its own Escape) ── */
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && open) hidePop();
    });

    /* ── Reposition on scroll / resize ── */
    const reposition = () => {
        if (open && openTrigger) {
            placeEl(ensurePop(), openTrigger, 264);
            return;
        }
        if (tipEl && tipEl.style.opacity !== '0') {
            const hovered = document.querySelector('.tip:hover, .metric-info-btn:hover');
            if (!hovered) { hideTip(); return; }
            placeEl(tipEl, hovered, 224);
        }
    };
    window.addEventListener('scroll', () => {
        if (raf) cancelAnimationFrame(raf);
        raf = requestAnimationFrame(reposition);
    }, true);
    window.addEventListener('resize', reposition);
})();

/* ─────────────────────────────────────────────────────────────
 |  buildFilterChips(onRemove)
 |  Scans #filterForm, builds dismissible chips in #filterChipsBar,
 |  and updates the #filterActiveBadge count.
 |  onRemove: function called (and chips re-rendered) when a chip × is clicked.
 *────────────────────────────────────────────────────────────*/
window.buildFilterChips = function (onRemove) {
    var $form = $('#filterForm');
    if (!$form.length) return;

    var groups = [];
    var seenIds = new Set();

    // 1. Min/Max pairs (.mpair containers)
    $form.find('.mpair').each(function () {
        var $pair = $(this);
        var $inputs = $pair.find('input');
        var $min = $inputs.eq(0);
        var $max = $inputs.eq(1);
        var minVal = ($min.val() || '').trim();
        var maxVal = ($max.val() || '').trim();
        if (!minVal && !maxVal) return;

        var label = $pair.parent().find('> label').first().text().trim()
                 || $pair.parent().find('label').first().text().trim()
                 || 'Range';

        var display = (minVal && maxVal) ? (minVal + ' – ' + maxVal)
                    : minVal ? ('≥ ' + minVal)
                    : ('≤ ' + maxVal);

        var ids = [];
        if ($min.attr('id')) { ids.push({ type: 'input', id: $min.attr('id') }); seenIds.add($min.attr('id')); }
        if ($max.attr('id')) { ids.push({ type: 'input', id: $max.attr('id') }); seenIds.add($max.attr('id')); }
        groups.push({ label: label, value: display, ids: ids });
    });

    // 2. Single text / number / date inputs (not already in a pair)
    $form.find('input[type="text"],input[type="number"],input[type="date"]').each(function () {
        if (seenIds.has(this.id)) return;
        var val = ($(this).val() || '').trim();
        if (!val) return;
        var label = $(this).closest('div').find('label').first().text().trim() || this.id;
        groups.push({ label: label, value: val, ids: [{ type: 'input', id: this.id }] });
    });

    // 3. Selects
    $form.find('select').each(function () {
        var val = $(this).val();
        if (!val || val === '' || (Array.isArray(val) && !val.length)) return;
        var label = $(this).closest('div').find('label').first().text().trim() || this.id;
        var display = Array.isArray(val)
            ? val.length + ' selected'
            : $(this).find('option:selected').text().trim();
        groups.push({ label: label, value: display, ids: [{ type: 'select', id: this.id }] });
    });

    // 4. Checkboxes
    $form.find('input[type="checkbox"]:checked').each(function () {
        var label = $(this).closest('.flex').find('span.text-sm').first().text().trim()
                 || this.id.replace('filter', '').replace(/([A-Z])/g, ' $1').trim();
        groups.push({ label: label, value: '✓', ids: [{ type: 'checkbox', id: this.id }] });
    });

    // Render
    var $bar   = $('#filterChipsBar');
    var $badge = $('#filterActiveBadge');
    $bar.empty();

    if (!groups.length) {
        $bar.addClass('hidden');
        $badge.addClass('hidden');
        return;
    }

    $bar.removeClass('hidden');
    $badge.text(groups.length + ' active').removeClass('hidden');

    groups.forEach(function (g) {
        var $chip = $('<span class="inline-flex items-center gap-1 bg-green-50 border border-green-200 text-green-800 text-xs font-medium px-2 py-0.5 rounded-full"></span>');
        $('<span class="max-w-[120px] truncate"></span>').text(g.label + ': ' + g.value).appendTo($chip);
        var $x = $('<button type="button" aria-label="Remove filter" class="chip-remove flex-shrink-0 ml-0.5 text-green-400 hover:text-red-500 font-bold leading-none transition-colors">×</button>');
        $x.data('chipIds', g.ids);
        $x.appendTo($chip);
        $bar.append($chip);
    });

    $bar.find('.chip-remove').on('click', function () {
        var ids = $(this).data('chipIds');
        ids.forEach(function (item) {
            if (item.type === 'checkbox') {
                $('#' + item.id).prop('checked', false);
            } else if (item.type === 'select') {
                $('#' + item.id).val(null).trigger('change');
            } else {
                $('#' + item.id).val('');
            }
        });
        onRemove();
        window.buildFilterChips(onRemove);
    });
};

/* ─────────────────────────────────────────────────────────────
 |  initDtStickyHeader(dt)
 |  Call once after each admin DataTable is initialised.
 |  CSS position:sticky cannot work on .dataTables_scrollHead because
 |  DataTables sets overflow:hidden inline on .dataTables_scroll (its
 |  parent), trapping sticky inside a non-scrolling container.
 |  This function creates a body-level position:fixed clone instead —
 |  the same architecture as initDsTableSticky below.
 |
 |  Horizontal sync uses negative marginLeft on the cloned inner element
 |  rather than scrollLeft: Chrome 88+ silently ignores scrollLeft on
 |  elements with overflow:hidden, so the header would never move.
 *────────────────────────────────────────────────────────────*/
window.initDtStickyHeader = function (dt) {
    var container  = dt.table().container();
    var scrollHead = container.querySelector('.dataTables_scrollHead');
    var scrollBody = container.querySelector('.dataTables_scrollBody');
    var mainEl     = document.querySelector('main');
    if (!scrollHead || !scrollBody || !mainEl) return;

    /* Build fixed bar */
    var bar = document.createElement('div');
    bar.style.cssText =
        'position:fixed;z-index:60;overflow:hidden;display:none;' +
        'background:#f9fafb;border-bottom:1px solid #e5e7eb;' +
        'box-shadow:0 1px 3px rgba(0,0,0,.06);';
    document.body.appendChild(bar);

    function applyScrollOffset() {
        var cloned = bar.querySelector('.dataTables_scrollHeadInner');
        if (cloned) cloned.style.marginLeft = '-' + scrollBody.scrollLeft + 'px';
    }

    function syncBar() {
        var mainRect = mainEl.getBoundingClientRect();
        var bodyRect = scrollBody.getBoundingClientRect();

        bar.style.left  = bodyRect.left   + 'px';
        bar.style.top   = mainRect.top    + 'px';
        bar.style.width = scrollBody.clientWidth + 'px';

        /* Clone DataTables' own scrollHeadInner — it already has
           column widths set inline by DataTables. */
        var inner = scrollHead.querySelector('.dataTables_scrollHeadInner');
        if (inner) {
            bar.innerHTML = '';
            bar.appendChild(inner.cloneNode(true));
        }
        applyScrollOffset();
    }

    function onMainScroll() {
        var mainRect  = mainEl.getBoundingClientRect();
        var headRect  = scrollHead.getBoundingClientRect();
        if (headRect.bottom <= mainRect.top) {
            syncBar();
            bar.style.display = 'block';
        } else {
            bar.style.display = 'none';
        }
    }

    mainEl.addEventListener('scroll', onMainScroll, { passive: true });

    /* Sync horizontal offset while the sticky bar is visible */
    scrollBody.addEventListener('scroll', function () {
        if (bar.style.display !== 'none') applyScrollOffset();
    }, { passive: true });

    /* Re-sync after AJAX redraws (column widths can change) */
    dt.on('draw.stickyHdr', function () {
        if (bar.style.display !== 'none') syncBar();
    });

    window.addEventListener('resize', function () {
        if (bar.style.display !== 'none') syncBar(); else onMainScroll();
    });
};

/* ─────────────────────────────────────────────────────────────
 |  initDsTableSticky
 |  .ds-table has overflow-x:auto which traps CSS position:sticky.
 |  This creates a body-level fixed clone of the <thead> that
 |  appears when the real header scrolls above <main>'s top edge,
 |  and syncs horizontal scroll with the card.
 |
 |  Same marginLeft trick as initDtStickyHeader above.
 *────────────────────────────────────────────────────────────*/
(function initDsTableSticky() {
    var card  = document.querySelector('.ds-table');
    if (!card) return;
    var table = card.querySelector('table');
    var thead = table && table.querySelector('thead');
    var main  = document.querySelector('main');
    if (!thead || !main) return;

    /* Build the fixed clone bar */
    var bar      = document.createElement('div');
    var barTable = document.createElement('table');
    bar.style.cssText =
        'position:fixed;z-index:60;overflow:hidden;display:none;' +
        'background:#f9fafb;border-bottom:1px solid #e5e7eb;' +
        'box-shadow:0 1px 3px rgba(0,0,0,.06);pointer-events:none;';
    barTable.style.cssText = 'border-collapse:separate;border-spacing:0;margin:0;';
    bar.appendChild(barTable);
    document.body.appendChild(bar);

    function sync() {
        var mainRect = main.getBoundingClientRect();
        var cardRect = card.getBoundingClientRect();

        bar.style.left  = cardRect.left + 'px';
        bar.style.top   = mainRect.top  + 'px';
        bar.style.width = card.clientWidth + 'px';

        /* Rebuild clone so it stays in sync with DataTables redraws */
        barTable.innerHTML = '';
        var clone    = thead.cloneNode(true);
        barTable.appendChild(clone);

        /* Mirror column widths exactly */
        var origThs  = thead.querySelectorAll('th');
        var cloneThs = clone.querySelectorAll('th');
        var totalW   = 0;
        origThs.forEach(function (th, i) {
            var w = th.getBoundingClientRect().width;
            totalW += w;
            if (cloneThs[i]) {
                cloneThs[i].style.width    = w + 'px';
                cloneThs[i].style.minWidth = w + 'px';
            }
        });
        barTable.style.width = totalW + 'px';

        barTable.style.marginLeft = '-' + card.scrollLeft + 'px';
    }

    function onScroll() {
        var mainRect  = main.getBoundingClientRect();
        var theadRect = thead.getBoundingClientRect();
        if (theadRect.bottom <= mainRect.top) {
            sync();
            bar.style.display = 'block';
        } else {
            bar.style.display = 'none';
        }
    }

    main.addEventListener('scroll',  onScroll, { passive: true });
    card.addEventListener('scroll', function () {
        if (bar.style.display !== 'none') barTable.style.marginLeft = '-' + card.scrollLeft + 'px';
    }, { passive: true });
    window.addEventListener('resize', function () {
        if (bar.style.display !== 'none') sync();
        else onScroll();
    });
})();
