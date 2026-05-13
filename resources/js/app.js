import './bootstrap';
import './swal-shim';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

/* ─────────────────────────────────────────────────────────────
 |  Global tooltip: any element with class `.tip` and a
 |  child `.tip-box` will render its tooltip text via a
 |  body-level singleton, so it escapes parent overflow clips.
 *────────────────────────────────────────────────────────────*/
(function initGlobalTooltip() {
    let el = null;
    let raf = null;

    const ensureEl = () => {
        if (el) return el;
        el = document.createElement('div');
        el.id = 'global-tooltip';
        el.className = [
            'fixed z-[200] pointer-events-none',
            'bg-slate-800 text-white',
            'text-[11px] leading-none font-normal',
            'px-2 py-1 rounded-md whitespace-nowrap',
            'opacity-0 transition-opacity duration-100',
        ].join(' ');
        el.style.transform = 'translate(-50%, calc(-100% - 6px))';
        document.body.appendChild(el);
        return el;
    };

    const place = (trigger, text) => {
        const tip = ensureEl();
        tip.textContent = text;
        const r = trigger.getBoundingClientRect();
        tip.style.left = `${r.left + r.width / 2}px`;
        tip.style.top  = `${r.top}px`;
        tip.style.opacity = '1';
    };

    const hide = () => {
        if (el) el.style.opacity = '0';
    };

    document.addEventListener('mouseover', (e) => {
        const trigger = e.target.closest && e.target.closest('.tip');
        if (! trigger) return;
        const box = trigger.querySelector(':scope > .tip-box');
        if (! box) return;
        place(trigger, box.textContent.trim());
    }, true);

    document.addEventListener('mouseout', (e) => {
        const trigger = e.target.closest && e.target.closest('.tip');
        if (! trigger) return;
        // mouseout fires when crossing children too; only hide when leaving the .tip wrapper
        const next = e.relatedTarget;
        if (next && trigger.contains(next)) return;
        hide();
    }, true);

    // Reposition on scroll/resize while visible
    const reposition = () => {
        if (! el || el.style.opacity === '0') return;
        const trigger = document.querySelector('.tip:hover');
        if (! trigger) { hide(); return; }
        const box = trigger.querySelector(':scope > .tip-box');
        if (box) place(trigger, box.textContent.trim());
    };
    window.addEventListener('scroll', () => {
        if (raf) cancelAnimationFrame(raf);
        raf = requestAnimationFrame(reposition);
    }, true);
    window.addEventListener('resize', reposition);
})();
