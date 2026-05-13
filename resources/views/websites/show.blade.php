@php
    $isGuestUser = auth()->check() && auth()->user()->isGuest();

    /* ── helpers ── */
    $fmtPrice = fn ($v) => $v !== null && $v !== '' ? '€ ' . number_format((float) $v, 2, '.', ',') : null;
    $fmtDate  = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->format('M j, Y') : null;
    $fmtInt   = fn ($v) => $v !== null && $v !== '' ? number_format((int) $v) : null;
    $bool     = fn ($v) => $v ? 'yes' : 'no';

    $statusTone = match (strtolower((string) $website->status)) {
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
@section('title', $website->domain_name)

@section('pageHeader')
    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between flex-shrink-0">
        <div class="min-w-0">
            <h1 class="text-base font-bold text-gray-800 truncate">{{ $website->domain_name }}</h1>
            <p class="text-xs text-gray-500 mt-0.5">Domain detail</p>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="{{ route('websites.index') }}"
               class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300">
                <x-icon name="arrow-left" size="sm" /> Back
            </a>
            @unless($isGuestUser)
                <a href="{{ route('websites.edit', $website->id) }}"
                   class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-sm">
                    <x-icon name="pencil" size="sm" /> Edit
                </a>
            @endunless
        </div>
    </div>
@endsection

@section('content')
    @php
        /* Small inline render helper for "label / value" rows. */
        $row = function ($label, $value, $extraClass = '') {
            $val = $value === null || $value === '' ? '<span class="text-gray-300">—</span>' : e($value);
            return '<div class="py-2 grid grid-cols-3 gap-2 ' . $extraClass . '">
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
                    <x-flag :country="optional($website->country)->country_name" :width="28" :height="20" />
                    <div class="min-w-0">
                        <div class="text-lg font-bold text-gray-800 truncate">{{ $website->domain_name }}</div>
                        <div class="text-xs text-gray-500 mt-0.5">
                            {{ optional($website->country)->country_name ?? '—' }}
                            · {{ optional($website->language)->name ?? '—' }}
                            @if($website->type_of_website)
                                · {{ ucfirst(strtolower($website->type_of_website)) }}
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    @unless($isGuestUser)
                        @if($website->status)
                            <x-ds.pill :tone="$statusTone" size="md">{{ ucfirst(str_replace('_',' ', $website->status)) }}</x-ds.pill>
                        @endif
                    @endunless
                    @if($website->price)
                        <div class="text-right">
                            <div class="text-xs text-gray-400 uppercase tracking-wider">Price</div>
                            <div class="text-xl font-bold text-green-700">{{ $fmtPrice($website->price) }}</div>
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
                    {!! $row('Domain',  $website->domain_name) !!}
                    {!! $row('Country', optional($website->country)->country_name) !!}
                    {!! $row('Language', optional($website->language)->name) !!}
                    {!! $row('Type', $website->type_of_website ? ucfirst(strtolower($website->type_of_website)) : null) !!}
                    @unless($isGuestUser)
                        {!! $row('Publisher', optional($website->contact)->name) !!}
                        {!! $row('Currency', $website->currency_code) !!}
                        {!! $row('Linkbuilder', $website->linkbuilder) !!}
                        {!! $row('Date added', $fmtDate($website->created_at)) !!}
                    @endunless
                </dl>
            </section>

            {{-- ─── PRICING ─── --}}
            @unless($isGuestUser)
                <section class="bg-white rounded-xl border border-gray-200 shadow-card">
                    <div class="px-5 py-3 border-b border-gray-100">
                        <h2 class="text-sm font-bold text-gray-800">Pricing</h2>
                    </div>
                    <div class="px-5 py-4 grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-3 text-sm">
                        @foreach([
                            ['Publisher',         $website->publisher_price],
                            ['No-follow',         $website->no_follow_price],
                            ['Special topic',     $website->special_topic_price],
                            ['Link insertion',    $website->link_insertion_price],
                            ['Banner',            $website->banner_price],
                            ['Site-wide',         $website->sitewide_link_price],
                            ['Sensitive topic',   $website->sensitive_topic_price],
                            ['Calculated price',  $website->price],
                            ['Kialvo evaluation', $website->kialvo_evaluation],
                            ['Profit',            $website->profit],
                        ] as [$label, $val])
                            <div>
                                <div class="text-xs text-gray-500 uppercase tracking-wider mb-0.5">{{ $label }}</div>
                                <div class="font-semibold {{ ($label === 'Profit' && (float)$val < 0) ? 'text-red-600' : 'text-gray-800' }}">
                                    {{ $fmtPrice($val) ?? '—' }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @else
                @if($website->sensitive_topic_price)
                    <section class="bg-white rounded-xl border border-gray-200 shadow-card">
                        <div class="px-5 py-3 border-b border-gray-100">
                            <h2 class="text-sm font-bold text-gray-800">Pricing</h2>
                        </div>
                        <div class="px-5 py-4 grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                            <div>
                                <div class="text-xs text-gray-500 uppercase tracking-wider mb-0.5">Standard</div>
                                <div class="font-semibold text-gray-800">{{ $fmtPrice($website->price) ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500 uppercase tracking-wider mb-0.5">Sensitive topic</div>
                                <div class="font-semibold text-sensitive-text">{{ $fmtPrice($website->sensitive_topic_price) ?? '—' }}</div>
                            </div>
                        </div>
                    </section>
                @endif
            @endunless

            {{-- ─── SEO METRICS ─── --}}
            <section class="bg-white rounded-xl border border-gray-200 shadow-card">
                <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-gray-800">SEO metrics</h2>
                    @if($website->seo_metrics_date)
                        <span class="text-xs text-gray-400">Updated {{ $fmtDate($website->seo_metrics_date) }}</span>
                    @endif
                </div>
                <div class="px-5 py-4 grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-x-6 gap-y-3 text-sm">
                    @php
                        $metrics = [
                            'DA'  => $website->DA,
                            'PA'  => $website->PA,
                            'TF'  => $website->TF,
                            'CF'  => $website->CF,
                            'DR'  => $website->DR,
                            'UR'  => $website->UR,
                            'ZA'  => $website->ZA,
                            'AS'  => $website->as_metric,
                            'TF vs CF' => $website->TF_vs_CF,
                            'SEO Zoom' => $website->seozoom,
                        ];
                    @endphp
                    @foreach($metrics as $k => $v)
                        <div>
                            <div class="text-xs text-gray-500 uppercase tracking-wider mb-0.5">{{ $k }}</div>
                            <div class="font-bold text-gray-800">{{ $v ?? '—' }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="px-5 pb-5 grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-3 text-sm border-t border-gray-100 pt-4">
                    @php
                        $traffic = [
                            'Semrush traffic'    => $fmtInt($website->semrush_traffic),
                            'Ahrefs keywords'    => $fmtInt($website->ahrefs_keyword),
                            'Ahrefs traffic'     => $fmtInt($website->ahrefs_traffic),
                            'KW vs traffic'      => $website->keyword_vs_traffic,
                            'MS'                 => $website->ms,
                            'Organic keywords'   => $fmtInt($website->organic_keywords),
                            'Organic traffic'    => $fmtInt($website->organic_traffic),
                            'KW/Traffic ratio'   => $website->kw_traffic_ratio,
                        ];
                    @endphp
                    @foreach($traffic as $k => $v)
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
                        'Betting'             => $website->betting,
                        'Trading'             => $website->trading,
                        'Permanent link'      => $website->permanent_link,
                        'More than 1 link'    => $website->more_than_one_link,
                        'Copywriting'         => $website->copywriting,
                        'Sponsored tag'       => ! $website->no_sponsored_tag,
                        'Social media share'  => $website->social_media_sharing,
                        'Post in homepage'    => $website->post_in_homepage,
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
            @if($website->categories->isNotEmpty())
                <section class="bg-white rounded-xl border border-gray-200 shadow-card">
                    <div class="px-5 py-3 border-b border-gray-100">
                        <h2 class="text-sm font-bold text-gray-800">Categories</h2>
                    </div>
                    <div class="px-5 py-4 flex flex-wrap gap-1.5">
                        @foreach($website->categories as $cat)
                            <x-ds.pill tone="green" size="sm">{{ $cat->name }}</x-ds.pill>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- ─── NOTES ─── --}}
            @if($website->notes || (! $isGuestUser && $website->extra_notes))
                <section class="bg-white rounded-xl border border-gray-200 shadow-card">
                    <div class="px-5 py-3 border-b border-gray-100">
                        <h2 class="text-sm font-bold text-gray-800">Notes</h2>
                    </div>
                    <div class="px-5 py-4 space-y-3">
                        @if($website->notes)
                            <div>
                                <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Public notes</div>
                                <p class="text-sm text-gray-700 whitespace-pre-line leading-relaxed">{{ $website->notes }}</p>
                            </div>
                        @endif
                        @unless($isGuestUser)
                            @if($website->extra_notes)
                                <div class="pt-3 border-t border-gray-100">
                                    <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Internal notes</div>
                                    <p class="text-sm text-gray-700 whitespace-pre-line leading-relaxed">{{ $website->extra_notes }}</p>
                                </div>
                            @endif
                        @endunless
                    </div>
                </section>
            @endif

        </div>
    </div>
@endsection
