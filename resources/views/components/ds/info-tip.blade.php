@props([
    'text',              // plain-text explanation of how the metric is calculated
    'label' => null,     // aria-label; defaults to a generic "How is this calculated?"
    'width' => 'w-72',   // Tailwind width class for the hover bubble
])

{{--
    "i" info affordance for a stats widget — explains HOW the number is calculated.

    Wraps the app's existing global tooltip mechanism (app.js → initGlobalTooltip):
      • hover  → dark mini tooltip built from data-info
      • click  → Swal info modal with the same text (useful on touch, and for text
                 longer than the mini tooltip comfortably holds)

    The .metric-info-text span is the CSS-only fallback, so the explanation is still
    reachable if app.js has not booted yet. Both copies read from $text, so they can
    never drift apart — the duplication in the older hand-rolled call sites is the
    reason this component exists.
--}}
<span {{ $attributes->merge(['class' => 'relative inline-flex group cursor-help align-middle']) }}>
    <button type="button"
            {{-- slate-500, not slate-400: an icon is non-text content, so it needs
                 3:1 against white and slate-400 only reaches 2.56:1. --}}
            class="metric-info-btn text-slate-500 transition-colors hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-green-200 focus-visible:rounded"
            data-info="{{ $text }}"
            aria-label="{{ $label ?? 'How is this calculated?' }}">
        <x-icon name="info" size="sm" class="inline" />
    </button>
    <span class="metric-info-text pointer-events-none absolute left-1/2 top-full z-30 mt-1 hidden {{ $width }} -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-[10px] normal-case whitespace-normal break-words font-normal leading-4 tracking-normal text-white shadow-lg group-hover:block group-focus-within:block">
        {{ $text }}
    </span>
</span>
