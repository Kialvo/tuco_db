{{--
    Filter form rendered into the layout's $filters slot.
    Submits as GET to /websites — applyFilters() reads everything off the Request.
--}}
@php
    $f = (array) ($filters ?? []);
    $get = fn (string $key, $default = '') => $f[$key] ?? $default;
@endphp

<form method="GET" action="{{ route('websites.index') }}" id="filterForm" class="contents">

    {{-- Preserve sort + per_page across filter submits --}}
    @if(($sort ?? null) !== 'ms')<input type="hidden" name="sort" value="{{ $sort }}">@endif
    @if(($direction ?? null) !== 'desc')<input type="hidden" name="direction" value="{{ $direction }}">@endif
    @if(($perPage ?? 10) !== 10)<input type="hidden" name="per_page" value="{{ $perPage }}">@endif

    <x-ds.filter-panel :active-count="$activeCount ?? 0"
                       :clear-url="route('websites.index')">

        {{-- Active filter chips --}}
        @php
            $typeLabels = ['FORUM'=>'Forum','GENERALIST'=>'Generalist','NICHE'=>'Niche','NEWS'=>'News','VERTICAL'=>'Vertical','LOCAL'=>'Local'];

            $langName = '';
            foreach ($languages as $l) {
                if ((string)$l->id === (string)$get('language_id')) { $langName = $l->name; break; }
            }
            $countryName = '';
            foreach ($countries as $c) {
                if ((string)$c->id === (string)$get('country_id')) { $countryName = $c->country_name; break; }
            }
            $catName = '';
            foreach ($categories as $cat) {
                if ((string)$cat->id === (string)$get('category_ids')) { $catName = $cat->name; break; }
            }

            $mkChips = array_values(array_filter([
                $get('domain_name')     ? ['label'=>'Domain',    'val'=>$get('domain_name'),                               'remove'=>['domain_name']]     : null,
                $get('type_of_website') ? ['label'=>'Type',      'val'=>$typeLabels[$get('type_of_website')] ?? $get('type_of_website'), 'remove'=>['type_of_website']] : null,
                $langName               ? ['label'=>'Language',  'val'=>$langName,                                         'remove'=>['language_id']]     : null,
                $countryName            ? ['label'=>'Country',   'val'=>$countryName,                                      'remove'=>['country_id']]      : null,
                $catName                ? ['label'=>'Category',  'val'=>$catName,                                          'remove'=>['category_ids']]    : null,
                $get('betting')=='1'    ? ['label'=>'Betting',   'val'=>'✓',                                               'remove'=>['betting']]         : null,
                $get('trading')=='1'    ? ['label'=>'Trading',   'val'=>'✓',                                               'remove'=>['trading']]         : null,
            ]));

            foreach ([
                ['keys'=>['price_min','price_max'],                                  'label'=>'Price (€)'],
                ['keys'=>['sensitive_topic_price_min','sensitive_topic_price_max'],   'label'=>'Sensitive (€)'],
                ['keys'=>['DA_min','DA_max'],                                         'label'=>'DA'],
                ['keys'=>['PA_min','PA_max'],                                         'label'=>'PA'],
                ['keys'=>['UR_min','UR_max'],                                         'label'=>'UR'],
                ['keys'=>['ZA_min','ZA_max'],                                         'label'=>'ZA'],
                ['keys'=>['SR_min','SR_max'],                                         'label'=>'AS'],
                ['keys'=>['semrush_traffic_min','semrush_traffic_max'],               'label'=>'Semrush Traffic'],
                ['keys'=>['ms_min','ms_max'],                                         'label'=>'MS'],
                ['keys'=>['organic_keywords_min','organic_keywords_max'],             'label'=>'Organic KW'],
                ['keys'=>['organic_traffic_min','organic_traffic_max'],               'label'=>'Organic Traffic'],
            ] as $pair) {
                $pMin = $get($pair['keys'][0]);
                $pMax = $get($pair['keys'][1]);
                if ($pMin === '' && $pMax === '') continue;
                $pDisplay = ($pMin !== '' && $pMax !== '') ? ($pMin.' – '.$pMax)
                          : ($pMin !== '' ? '≥ '.$pMin : '≤ '.$pMax);
                $mkChips[] = ['label'=>$pair['label'], 'val'=>$pDisplay, 'remove'=>$pair['keys']];
            }
        @endphp

        @if(count($mkChips))
            <x-slot name="chips">
                @foreach($mkChips as $chip)
                    @php
                        $removeUrl = route('websites.index').'?'.http_build_query(
                            collect($f)->except($chip['remove'])->filter(fn($v) => $v !== '' && $v !== null)->all()
                        );
                    @endphp
                    <a href="{{ $removeUrl }}"
                       class="inline-flex items-center gap-1 bg-green-50 border border-green-200 text-green-800 text-xs font-medium px-2 py-0.5 rounded-full hover:bg-red-50 hover:border-red-200 hover:text-red-700 transition-colors">
                        <span class="max-w-[110px] truncate">{{ $chip['label'] }}: {{ $chip['val'] }}</span>
                        <span class="flex-shrink-0 font-bold ml-0.5">×</span>
                    </a>
                @endforeach
            </x-slot>
        @endif

        {{-- Domain search --}}
        <x-ds.filter-input name="domain_name" label="Domain" placeholder="e.g. techbullion.com"
                           value="{{ $get('domain_name') }}" />

        {{-- Type --}}
        <x-ds.filter-input name="type_of_website" label="Type" as="select">
            <option value="">All types</option>
            @foreach(['FORUM' => 'Forum', 'GENERALIST' => 'Generalist', 'NICHE' => 'Niche', 'NEWS' => 'News', 'VERTICAL' => 'Vertical', 'LOCAL' => 'Local'] as $val => $lbl)
                <option value="{{ $val }}" @selected($get('type_of_website') === $val)>{{ $lbl }}</option>
            @endforeach
        </x-ds.filter-input>

        {{-- Language --}}
        <x-ds.filter-input name="language_id" label="Language" as="select">
            <option value="">All languages</option>
            @foreach($languages as $lang)
                <option value="{{ $lang->id }}" @selected((string) $get('language_id') === (string) $lang->id)>{{ $lang->name }}</option>
            @endforeach
        </x-ds.filter-input>

        {{-- Country (single, simple include) --}}
        <x-ds.filter-input name="country_id" label="Country" as="select">
            <option value="">All countries</option>
            @foreach($countries as $c)
                <option value="{{ $c->id }}" @selected((string) $get('country_id') === (string) $c->id)>{{ $c->country_name }}</option>
            @endforeach
        </x-ds.filter-input>

        {{-- Price min/max --}}
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Price (€)</label>
            <div class="mpair">
                <input type="number" name="price_min" placeholder="Min" value="{{ $get('price_min') }}" class="fi">
                <input type="number" name="price_max" placeholder="Max" value="{{ $get('price_max') }}" class="fi">
            </div>
        </div>

        {{-- Sensitive topic price --}}
        <div>
            <div class="flex items-center gap-1.5 mb-1.5">
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Sensitive Topic Price (€)</label>
                <span class="tip">
                    <x-icon name="info" size="sm" class="text-gray-400 cursor-help" />
                    <span class="tip-box">Price for betting, trading or adult content</span>
                </span>
            </div>
            <div class="mpair">
                <input type="number" name="sensitive_topic_price_min" placeholder="Min" value="{{ $get('sensitive_topic_price_min') }}" class="fi">
                <input type="number" name="sensitive_topic_price_max" placeholder="Max" value="{{ $get('sensitive_topic_price_max') }}" class="fi">
            </div>
        </div>

        {{-- Authority metrics --}}
        @php
            $metrics = [
                'DA' => ['label' => 'DA',  'tip' => 'Domain Authority (Moz) — 0 to 100'],
                'PA' => ['label' => 'PA',  'tip' => 'Page Authority (Moz) — 0 to 100'],
                'UR' => ['label' => 'UR',  'tip' => 'URL Rating (Ahrefs) — 0 to 100'],
                'ZA' => ['label' => 'ZA',  'tip' => 'Zino Authority — composite link metric'],
                'SR' => ['label' => 'AS',  'tip' => 'Authority Score (Semrush) — 0 to 100'],
            ];
        @endphp
        @foreach($metrics as $key => $m)
            <div>
                <div class="flex items-center gap-1.5 mb-1.5">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $m['label'] }}</label>
                    <span class="tip">
                        <x-icon name="info" size="sm" class="text-gray-400 cursor-help" />
                        <span class="tip-box">{{ $m['tip'] }}</span>
                    </span>
                </div>
                <div class="mpair">
                    <input type="number" name="{{ $key }}_min" placeholder="Min" value="{{ $get($key.'_min') }}" class="fi">
                    <input type="number" name="{{ $key }}_max" placeholder="Max" value="{{ $get($key.'_max') }}" class="fi">
                </div>
            </div>
        @endforeach

        {{-- Semrush Traffic --}}
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Semrush Traffic</label>
            <div class="mpair">
                <input type="number" name="semrush_traffic_min" placeholder="Min" value="{{ $get('semrush_traffic_min') }}" class="fi">
                <input type="number" name="semrush_traffic_max" placeholder="Max" value="{{ $get('semrush_traffic_max') }}" class="fi">
            </div>
        </div>

        {{-- MS (Menford Score) --}}
        <div>
            <div class="flex items-center gap-1.5 mb-1.5">
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">MS</label>
                <span class="tip">
                    <x-icon name="info" size="sm" class="text-gray-400 cursor-help" />
                    <span class="tip-box">Menford Score — proprietary quality rating 0–100</span>
                </span>
            </div>
            <div class="mpair">
                <input type="number" name="ms_min" placeholder="Min" value="{{ $get('ms_min') }}" class="fi">
                <input type="number" name="ms_max" placeholder="Max" value="{{ $get('ms_max') }}" class="fi">
            </div>
        </div>

        {{-- Organic Keywords --}}
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Organic Keywords</label>
            <div class="mpair">
                <input type="number" name="organic_keywords_min" placeholder="Min" value="{{ $get('organic_keywords_min') }}" class="fi">
                <input type="number" name="organic_keywords_max" placeholder="Max" value="{{ $get('organic_keywords_max') }}" class="fi">
            </div>
        </div>

        {{-- Organic Traffic --}}
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Organic Traffic</label>
            <div class="mpair">
                <input type="number" name="organic_traffic_min" placeholder="Min" value="{{ $get('organic_traffic_min') }}" class="fi">
                <input type="number" name="organic_traffic_max" placeholder="Max" value="{{ $get('organic_traffic_max') }}" class="fi">
            </div>
        </div>

        {{-- Categories --}}
        <x-ds.filter-input name="category_ids" label="Categories" as="select">
            <option value="">All categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" @selected((string) $get('category_ids') === (string) $cat->id)>{{ $cat->name }}</option>
            @endforeach
        </x-ds.filter-input>

        {{-- Sensitive content toggles --}}
        <div class="pt-3 border-t border-gray-100 space-y-4">
            <x-ds.toggle name="betting" label="Accept Betting Content"
                         hint="Show sites that publish gambling and sports betting articles"
                         value="1" :checked="$get('betting') == '1'" />
            <x-ds.toggle name="trading" label="Accept Trading Content"
                         hint="Show sites that publish crypto, forex and trading articles"
                         value="1" :checked="$get('trading') == '1'" />
        </div>

        <x-slot name="search">
            <x-ds.button type="submit" variant="primary" block>
                <x-icon name="search" size="sm" /> Search
            </x-ds.button>
        </x-slot>
    </x-ds.filter-panel>
</form>
