/* ──────────────────────────────────────────────────────────────
 |  SweetAlert2 API-compatible shim built on the design system.
 |  Replaces the ~50KB SweetAlert CDN with ~3KB of inline code.
 |
 |  Supports the API surface actually used in this codebase:
 |    icon: 'success' | 'error' | 'warning' | 'info' | 'question'
 |    title, text, html
 |    timer, timerProgressBar, showConfirmButton (default true)
 |    showCancelButton, confirmButtonText, cancelButtonText
 |    confirmButtonColor (ignored — we use design tokens)
 |    Returns Promise resolving to { isConfirmed, isDismissed, isDenied, value }
 *─────────────────────────────────────────────────────────────*/
(function () {
    if (window.Swal && !window.Swal.__shim) return; // real SweetAlert already present

    const ICONS = {
        success: { color: 'text-green-600',   bg: 'bg-green-100',   svg: '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>' },
        error:   { color: 'text-red-600',     bg: 'bg-red-100',     svg: '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>' },
        warning: { color: 'text-amber-600',   bg: 'bg-amber-100',   svg: '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>' },
        info:    { color: 'text-blue-600',    bg: 'bg-blue-100',    svg: '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' },
        question:{ color: 'text-gray-600',    bg: 'bg-gray-100',    svg: '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' },
    };

    let activeBackdrop = null;

    function close(result) {
        if (activeBackdrop) {
            activeBackdrop.remove();
            activeBackdrop = null;
        }
        if (currentResolve) {
            currentResolve(result);
            currentResolve = null;
        }
    }

    let currentResolve = null;

    function fire(options) {
        // Allow legacy "Swal.fire(title, text, icon)" 3-arg call
        if (typeof options === 'string') {
            options = { title: options, text: arguments[1] || '', icon: arguments[2] || undefined };
        }
        options = options || {};

        const o = {
            icon: options.icon || null,
            title: options.title || '',
            text: options.text || '',
            html: options.html || '',
            footer: options.footer || '',
            showConfirmButton: options.showConfirmButton !== false,
            showCancelButton: options.showCancelButton === true,
            showDenyButton: options.showDenyButton === true,
            confirmButtonText: options.confirmButtonText || 'OK',
            cancelButtonText:  options.cancelButtonText  || 'Cancel',
            denyButtonText:    options.denyButtonText    || 'No',
            timer: options.timer || 0,
            timerProgressBar: options.timerProgressBar === true,
            allowOutsideClick: options.allowOutsideClick !== false,
            allowEscapeKey:    options.allowEscapeKey    !== false,
        };

        // Dismiss any open dialog
        close({ isConfirmed: false, isDismissed: true, dismiss: 'replaced' });

        return new Promise((resolve) => {
            currentResolve = resolve;

            const backdrop = document.createElement('div');
            backdrop.className = 'fixed inset-0 z-[200] flex items-center justify-center bg-black/50 p-4';
            backdrop.style.opacity = '0';
            backdrop.style.transition = 'opacity .15s ease';

            const card = document.createElement('div');
            card.className = 'bg-white rounded-2xl shadow-2xl w-full max-w-sm text-center p-6 transform scale-95 opacity-0';
            card.style.transition = 'transform .15s ease, opacity .15s ease';

            // Icon
            let iconHtml = '';
            if (o.icon && ICONS[o.icon]) {
                const ic = ICONS[o.icon];
                iconHtml = `<div class="w-16 h-16 rounded-full ${ic.bg} ${ic.color} flex items-center justify-center mx-auto mb-3">${ic.svg}</div>`;
            }

            // Body
            const titleHtml = o.title ? `<h2 class="text-base font-bold text-gray-800 mb-1">${o.title}</h2>` : '';
            const textHtml  = o.text  ? `<p class="text-sm text-gray-500 leading-relaxed">${escapeHtml(o.text)}</p>` : '';
            const htmlBody  = o.html  ? `<div class="text-sm text-gray-600 leading-relaxed">${o.html}</div>` : '';

            // Buttons row
            let buttonsHtml = '<div class="mt-5 flex items-center justify-center gap-2">';
            if (o.showConfirmButton) {
                buttonsHtml += `<button type="button" data-swal-confirm
                    class="inline-flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">${escapeHtml(o.confirmButtonText)}</button>`;
            }
            if (o.showDenyButton) {
                buttonsHtml += `<button type="button" data-swal-deny
                    class="inline-flex items-center justify-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">${escapeHtml(o.denyButtonText)}</button>`;
            }
            if (o.showCancelButton) {
                buttonsHtml += `<button type="button" data-swal-cancel
                    class="inline-flex items-center justify-center px-4 py-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 text-sm font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-300">${escapeHtml(o.cancelButtonText)}</button>`;
            }
            buttonsHtml += '</div>';

            // Timer progress bar
            const progressHtml = (o.timer && o.timerProgressBar)
                ? '<div class="mt-4 h-1 bg-gray-100 rounded-full overflow-hidden"><div data-swal-progress class="h-full bg-green-500" style="width:100%; transition: width '+o.timer+'ms linear"></div></div>'
                : '';

            const footerHtml = o.footer ? `<div class="mt-3 pt-3 border-t border-gray-100 text-xs text-gray-500">${o.footer}</div>` : '';

            card.innerHTML = iconHtml + titleHtml + textHtml + htmlBody + buttonsHtml + progressHtml + footerHtml;
            backdrop.appendChild(card);
            document.body.appendChild(backdrop);
            activeBackdrop = backdrop;

            requestAnimationFrame(() => {
                backdrop.style.opacity = '1';
                card.style.transform = 'scale(1)';
                card.style.opacity = '1';
            });

            // Wire buttons
            card.querySelector('[data-swal-confirm]')?.addEventListener('click', () => {
                close({ isConfirmed: true, isDismissed: false, isDenied: false, value: true });
            });
            card.querySelector('[data-swal-deny]')?.addEventListener('click', () => {
                close({ isConfirmed: false, isDismissed: false, isDenied: true });
            });
            card.querySelector('[data-swal-cancel]')?.addEventListener('click', () => {
                close({ isConfirmed: false, isDismissed: true, isDenied: false, dismiss: 'cancel' });
            });

            // Backdrop click + ESC
            if (o.allowOutsideClick) {
                backdrop.addEventListener('click', (e) => {
                    if (e.target === backdrop) close({ isConfirmed: false, isDismissed: true, dismiss: 'backdrop' });
                });
            }
            const onKey = (e) => {
                if (e.key === 'Escape' && o.allowEscapeKey) {
                    document.removeEventListener('keydown', onKey);
                    close({ isConfirmed: false, isDismissed: true, dismiss: 'esc' });
                }
            };
            document.addEventListener('keydown', onKey);

            // Trigger progress bar animation
            if (o.timer && o.timerProgressBar) {
                requestAnimationFrame(() => {
                    const bar = card.querySelector('[data-swal-progress]');
                    if (bar) bar.style.width = '0%';
                });
            }

            // Auto-dismiss
            if (o.timer) {
                setTimeout(() => {
                    document.removeEventListener('keydown', onKey);
                    close({ isConfirmed: false, isDismissed: true, dismiss: 'timer' });
                }, o.timer);
            }
        });
    }

    function escapeHtml(s) {
        return String(s)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');
    }

    window.Swal = {
        __shim: true,
        fire,
        close: () => close({ isDismissed: true, dismiss: 'close' }),
        // Toast/mixin no-ops to avoid blowing up legacy code
        mixin: (defaults) => ({
            fire: (opts) => fire({ ...(defaults || {}), ...(opts || {}) }),
        }),
        isVisible: () => !!activeBackdrop,
    };
})();
