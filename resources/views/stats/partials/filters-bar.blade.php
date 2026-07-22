{{--
    Shared Stats top filter bar — the universal-filter note + the date-range picker.

    Vars:
      $route           route NAME the form submits to (e.g. 'stats.financial')
      $dateFrom        selected date_from ('Y-m-d' or null) — from the controller payload
      $dateTo          selected date_to
      $preserveArrays  optional [name => [values]] rendered as name[] hidden inputs, so a
                       date change does not wipe a widget's own filter (Production's
                       article_sites / spend_sites)
      $note            optional override for the filter note's leading text
      $noteStrong      optional bold suffix after $note; pass '' for none

    STICKY — the scroll container is <main class="flex-1 overflow-auto"> in
    layouts/dashboard.blade.php (body is h-screen overflow-hidden, so the page
    itself never scrolls). `sticky top-0` therefore pins to the top of the
    content scrollport. The bar MUST stay opaque: cards scroll underneath it,
    and z-30 both lifts it above them and gives the picker's absolutely
    positioned dropdown a stacking context above the widgets below.
--}}
@php
    $preserveArrays = $preserveArrays ?? [];
@endphp

{{-- Width is deliberately the content width (no negative margins): the bar then
     covers exactly what scrolls beneath it, without ever widening the page. --}}
<div class="sticky top-0 z-30 flex flex-wrap items-center justify-end gap-3 border-b border-slate-200 bg-gray-50/95 py-3 backdrop-blur">
    <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-600">
        <x-icon name="info" size="sm" class="shrink-0 text-slate-400" />
        {{ $note ?? 'All stats below show only storages with status' }}
        {{-- Pass noteStrong => '' to render the note with no bold suffix. --}}
        @if($noteStrong ?? 'Article Published')
            <span class="font-semibold text-slate-800">{{ $noteStrong ?? 'Article Published' }}</span>
        @endif
    </span>

    <form id="statsFiltersForm" method="GET" action="{{ route($route) }}"
          x-data="statsRangePicker({
              dateFrom: @js($dateFrom ?? ''),
              dateTo: @js($dateTo ?? ''),
          })"
          class="flex items-end gap-3">

        {{-- Keep sibling widget filters alive across a date change. --}}
        @foreach($preserveArrays as $name => $values)
            @foreach($values as $value)
                <input type="hidden" name="{{ $name }}[]" value="{{ $value }}">
            @endforeach
        @endforeach

        @include('stats.partials.date-range-picker', ['showDateLabel' => false])
    </form>
</div>
