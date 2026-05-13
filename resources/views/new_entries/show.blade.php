@php
    /* ── helpers ── */
    $fmtPrice = fn ($v) => $v !== null && $v !== '' ? '€ ' . number_format((float) $v, 2, '.', ',') : null;
    $fmtDate  = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->format('M j, Y') : null;
    $fmtInt   = fn ($v) => $v !== null && $v !== '' ? number_format((int) $v) : null;

    $statusTone = match (strtolower((string) $new_entry->status)) {
        'active'                                              => 'green',
        'past'                                                => 'gray',
        'negotiation', 'waiting_for_first_answer'             => 'blue',
        'read_but_never_answered'                             => 'amber',
        'refused_by_us', 'publisher_refused'                  => 'red',
        'never_opened'                                        => 'gray',
        default                                               => 'gray',
    };
@endphp

@extends('layouts.dashboard')
@section('title', $new_entry->domain_name)

@section('pageHeader')
    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between flex-shrink-0">
        <div class="min-w-0">
            <h1 class="text-base font-bold text-gray-800 truncate">{{ $new_entry->domain_name }}</h1>
            <p class="text-xs text-gray-500 mt-0.5">Entry detail</p>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="{{ route('new_entries.index') }}"
               class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300">
                <x-icon name="arrow-left" size="sm" /> Back
            </a>
            <a href="{{ route('new_entries.edit', $new_entry->id) }}"
               class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-sm">
                <x-icon name="pencil" size="sm" /> Edit
            </a>
        </div>
    </div>
@endsection

@section('content')
    @php
        $row = function ($label, $value) {
            $val = $value === null || $value === '' ? '<span class="text-gray-300">—</span>' : e($value);
            return '<div class="py-2 grid grid-cols-3 gap-2">
                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">' . e($label) . '</dt>
                <dd class="col-span-2 text-sm text-gray-800 break-words">' . $val . '</dd>
            </div>';
        };
    @endphp

    <div class="px-6 py-6 bg-gray-50 min-h-screen">
        <div class="max-w-5xl mx-auto space-y-5">

            {{-- ─── SUMMARY HEADER CARD ─── --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-card p-5 flex items-start justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-3 min-w-0">
                    <x-flag :country="optional($new_entry->country)->country_name" :width="28" :height="20" />
                    <div class="min-w-0">
                        <div class="text-lg font-bold text-gray-800 truncate">{{ $new_entry->domain_name }}</div>
                        <div class="text-xs text-gray-500 mt-0.5">
                            {{ optional($new_entry->country)->country_name ?? '—' }}
                            · {{ optional($new_entry->language)->name ?? '—' }}
                            @if($new_entry->type_of_website)
                                · {{ ucfirst(strtolower($new_entry->type_of_website)) }}
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    @if($new_entry->status)
                        <x-ds.pill :tone="$statusTone" size="md">{{ ucfirst(str_replace('_',' ', $new_entry->status)) }}</x-ds.pill>
                    @endif
                    @if($new_entry->publisher_price)
                        <div class="text-right">
                            <div class="text-xs text-gray-400 uppercase tracking-wider">Publisher</div>
                            <div class="text-xl font-bold text-green-700">{{ $fmtPrice($new_entry->publisher_price) }}</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ─── GENERAL ─── --}}
            <section class="bg-white rounded-xl border border-gray-200 shadow-card">
                <div class="px-5 py-3 border-b border-gray-100">
                    <h2 class="text-sm font-bold text-gray-800">General information</h2>
                </div>
                <dl class="px-5 py-2 divide-y divide-gray-100">
                    {!! $row('Domain', $new_entry->domain_name) !!}
                    {!! $row('Country', optional($new_entry->country)->country_name) !!}
                    {!! $row('Language', optional($new_entry->language)->name) !!}
                    {!! $row('Type', $new_entry->type_of_website ? ucfirst(strtolower($new_entry->type_of_website)) : null) !!}
                    {!! $row('Contact', optional($new_entry->contact)->name) !!}
                    {!! $row('Linkbuilder', $new_entry->linkbuilder) !!}
                    {!! $row('First contact', $fmtDate($new_entry->first_contact_date)) !!}
                    {!! $row('Date added', $fmtDate($new_entry->date_added)) !!}
                </dl>
            </section>

            {{-- ─── PRICING ─── --}}
            <section class="bg-white rounded-xl border border-gray-200 shadow-card">
                <div class="px-5 py-3 border-b border-gray-100">
                    <h2 class="text-sm font-bold text-gray-800">Pricing</h2>
                </div>
                <div class="px-5 py-4 grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-3 text-sm">
                    @foreach([
                        ['Publisher',                $new_entry->publisher_price],
                        ['Original publisher',       $new_entry->original_publisher_price],
                        ['Link insertion',           $new_entry->link_insertion_price],
                        ['Original link insertion',  $new_entry->original_link_insertion_price],
                        ['No-follow',                $new_entry->no_follow_price],
                        ['Original no-follow',       $new_entry->original_no_follow_price],
                        ['Special topic',            $new_entry->special_topic_price],
                        ['Original special topic',   $new_entry->original_special_topic_price],
                        ['Banner',                   $new_entry->banner_price],
                        ['Site-wide',                $new_entry->sitewide_link_price],
                        ['Automatic evaluation',     $new_entry->automatic_evaluation],
                        ['Kialvo evaluation',        $new_entry->kialvo_evaluation],
                        ['Profit',                   $new_entry->profit],
                    ] as [$label, $val])
                        <div>
                            <div class="text-xs text-gray-500 uppercase tracking-wider mb-0.5">{{ $label }}</div>
                            <div class="font-semibold {{ ($label === 'Profit' && (float)$val < 0) ? 'text-red-600' : 'text-gray-800' }}">
                                {{ $fmtPrice($val) ?? '—' }}
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($new_entry->date_publisher_price || $new_entry->date_kialvo_evaluation)
                    <div class="px-5 pb-4 grid grid-cols-2 gap-x-6 gap-y-3 text-sm border-t border-gray-100 pt-3">
                        <div>
                            <div class="text-xs text-gray-500 uppercase tracking-wider mb-0.5">Date publisher price</div>
                            <div class="text-gray-800">{{ $fmtDate($new_entry->date_publisher_price) ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 uppercase tracking-wider mb-0.5">Date Kialvo evaluation</div>
                            <div class="text-gray-800">{{ $fmtDate($new_entry->date_kialvo_evaluation) ?? '—' }}</div>
                        </div>
                    </div>
                @endif
            </section>

            {{-- ─── SEO METRICS ─── --}}
            <section class="bg-white rounded-xl border border-gray-200 shadow-card">
                <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-gray-800">SEO metrics</h2>
                    @if($new_entry->seo_metrics_date)
                        <span class="text-xs text-gray-400">Updated {{ $fmtDate($new_entry->seo_metrics_date) }}</span>
                    @endif
                </div>
                <div class="px-5 py-4 grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-x-6 gap-y-3 text-sm">
                    @foreach([
                        'DA' => $new_entry->DA,
                        'PA' => $new_entry->PA,
                        'TF' => $new_entry->TF,
                        'CF' => $new_entry->CF,
                        'DR' => $new_entry->DR,
                        'UR' => $new_entry->UR,
                        'ZA' => $new_entry->ZA,
                        'AS' => $new_entry->as_metric,
                        'TF vs CF' => $new_entry->TF_vs_CF,
                        'SEO Zoom' => $new_entry->seozoom,
                    ] as $k => $v)
                        <div>
                            <div class="text-xs text-gray-500 uppercase tracking-wider mb-0.5">{{ $k }}</div>
                            <div class="font-bold text-gray-800">{{ $v ?? '—' }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="px-5 pb-5 grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-3 text-sm border-t border-gray-100 pt-4">
                    @foreach([
                        'Semrush traffic'  => $fmtInt($new_entry->semrush_traffic),
                        'Ahrefs keywords'  => $fmtInt($new_entry->ahrefs_keyword),
                        'Ahrefs traffic'   => $fmtInt($new_entry->ahrefs_traffic),
                        'KW vs traffic'    => $new_entry->keyword_vs_traffic,
                    ] as $k => $v)
                        <div>
                            <div class="text-xs text-gray-500 uppercase tracking-wider mb-0.5">{{ $k }}</div>
                            <div class="font-semibold text-gray-800">{{ $v ?? '—' }}</div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- ─── CONTENT FLAGS ─── --}}
            <section class="bg-white rounded-xl border border-gray-200 shadow-card">
                <div class="px-5 py-3 border-b border-gray-100">
                    <h2 class="text-sm font-bold text-gray-800">Content flags</h2>
                </div>
                <div class="px-5 py-4 grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                    @foreach([
                        'Betting'             => $new_entry->betting,
                        'Trading'             => $new_entry->trading,
                        'Permanent link'      => $new_entry->permanent_link,
                        'More than 1 link'    => $new_entry->more_than_one_link,
                        'Copywriting'         => $new_entry->copywriting,
                        'Sponsored tag'       => ! $new_entry->no_sponsored_tag,
                        'Social media share'  => $new_entry->social_media_sharing,
                        'Post in homepage'    => $new_entry->post_in_homepage,
                        'Copied to overview'  => $new_entry->copied_to_overview,
                    ] as $label => $val)
                        <div class="flex items-center justify-between gap-3 px-3 py-2 rounded-lg border border-gray-100 bg-gray-50">
                            <span class="text-gray-700">{{ $label }}</span>
                            @if($val)
                                <x-ds.pill tone="green" size="sm">Yes</x-ds.pill>
                            @else
                                <x-ds.pill tone="gray" size="sm">No</x-ds.pill>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- ─── CATEGORIES ─── --}}
            @if($new_entry->categories->isNotEmpty())
                <section class="bg-white rounded-xl border border-gray-200 shadow-card">
                    <div class="px-5 py-3 border-b border-gray-100">
                        <h2 class="text-sm font-bold text-gray-800">Categories</h2>
                    </div>
                    <div class="px-5 py-4 flex flex-wrap gap-1.5">
                        @foreach($new_entry->categories as $cat)
                            <x-ds.pill tone="green" size="sm">{{ $cat->name }}</x-ds.pill>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- ─── NOTES ─── --}}
            @if($new_entry->extra_notes)
                <section class="bg-white rounded-xl border border-gray-200 shadow-card">
                    <div class="px-5 py-3 border-b border-gray-100">
                        <h2 class="text-sm font-bold text-gray-800">Extra notes</h2>
                    </div>
                    <div class="px-5 py-4">
                        <p class="text-sm text-gray-700 whitespace-pre-line leading-relaxed">{{ $new_entry->extra_notes }}</p>
                    </div>
                </section>
            @endif

        </div>
    </div>
@endsection
