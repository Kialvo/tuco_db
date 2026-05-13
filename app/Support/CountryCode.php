<?php

namespace App\Support;

class CountryCode
{
    /**
     * Map country name → ISO 3166-1 alpha-2 lowercase code.
     * Covers every UN-recognised country plus common aliases.
     */
    private const MAP = [
        'afghanistan' => 'af',  'albania' => 'al',  'algeria' => 'dz',  'andorra' => 'ad',
        'angola' => 'ao',  'antigua and barbuda' => 'ag',  'argentina' => 'ar',  'armenia' => 'am',
        'australia' => 'au',  'austria' => 'at',  'azerbaijan' => 'az',  'bahamas' => 'bs',
        'bahrain' => 'bh',  'bangladesh' => 'bd',  'barbados' => 'bb',  'belarus' => 'by',
        'belgium' => 'be',  'belize' => 'bz',  'benin' => 'bj',  'bhutan' => 'bt',
        'bolivia' => 'bo',  'bosnia and herzegovina' => 'ba',  'bosnia' => 'ba',  'botswana' => 'bw',
        'brazil' => 'br',  'brunei' => 'bn',  'bulgaria' => 'bg',  'burkina faso' => 'bf',
        'burundi' => 'bi',  'cambodia' => 'kh',  'cameroon' => 'cm',  'canada' => 'ca',
        'cape verde' => 'cv',  'central african republic' => 'cf',  'chad' => 'td',  'chile' => 'cl',
        'china' => 'cn',  'colombia' => 'co',  'comoros' => 'km',  'congo' => 'cg',
        'democratic republic of the congo' => 'cd',  'dr congo' => 'cd',  'costa rica' => 'cr',
        'croatia' => 'hr',  'cuba' => 'cu',  'cyprus' => 'cy',  'czech republic' => 'cz',  'czechia' => 'cz',
        'denmark' => 'dk',  'djibouti' => 'dj',  'dominica' => 'dm',  'dominican republic' => 'do',
        'ecuador' => 'ec',  'egypt' => 'eg',  'el salvador' => 'sv',  'equatorial guinea' => 'gq',
        'eritrea' => 'er',  'estonia' => 'ee',  'eswatini' => 'sz',  'ethiopia' => 'et',
        'fiji' => 'fj',  'finland' => 'fi',  'france' => 'fr',  'gabon' => 'ga',
        'gambia' => 'gm',  'georgia' => 'ge',  'germany' => 'de',  'ghana' => 'gh',
        'greece' => 'gr',  'grenada' => 'gd',  'guatemala' => 'gt',  'guinea' => 'gn',
        'guinea-bissau' => 'gw',  'guyana' => 'gy',  'haiti' => 'ht',  'honduras' => 'hn',
        'hong kong' => 'hk',  'hungary' => 'hu',  'iceland' => 'is',  'india' => 'in',
        'indonesia' => 'id',  'iran' => 'ir',  'iraq' => 'iq',  'ireland' => 'ie',
        'israel' => 'il',  'italy' => 'it',  'ivory coast' => 'ci',  "cote d'ivoire" => 'ci',
        'jamaica' => 'jm',  'japan' => 'jp',  'jordan' => 'jo',  'kazakhstan' => 'kz',
        'kenya' => 'ke',  'kiribati' => 'ki',  'kuwait' => 'kw',  'kyrgyzstan' => 'kg',
        'laos' => 'la',  'latvia' => 'lv',  'lebanon' => 'lb',  'lesotho' => 'ls',
        'liberia' => 'lr',  'libya' => 'ly',  'liechtenstein' => 'li',  'lithuania' => 'lt',
        'luxembourg' => 'lu',  'macao' => 'mo',  'macau' => 'mo',  'madagascar' => 'mg',
        'malawi' => 'mw',  'malaysia' => 'my',  'maldives' => 'mv',  'mali' => 'ml',
        'malta' => 'mt',  'marshall islands' => 'mh',  'mauritania' => 'mr',  'mauritius' => 'mu',
        'mexico' => 'mx',  'micronesia' => 'fm',  'moldova' => 'md',  'monaco' => 'mc',
        'mongolia' => 'mn',  'montenegro' => 'me',  'morocco' => 'ma',  'mozambique' => 'mz',
        'myanmar' => 'mm',  'burma' => 'mm',  'namibia' => 'na',  'nauru' => 'nr',
        'nepal' => 'np',  'netherlands' => 'nl',  'holland' => 'nl',  'new zealand' => 'nz',
        'nicaragua' => 'ni',  'niger' => 'ne',  'nigeria' => 'ng',  'north korea' => 'kp',
        'north macedonia' => 'mk',  'macedonia' => 'mk',  'norway' => 'no',  'oman' => 'om',
        'pakistan' => 'pk',  'palau' => 'pw',  'palestine' => 'ps',  'panama' => 'pa',
        'papua new guinea' => 'pg',  'paraguay' => 'py',  'peru' => 'pe',  'philippines' => 'ph',
        'poland' => 'pl',  'portugal' => 'pt',  'qatar' => 'qa',  'romania' => 'ro',
        'russia' => 'ru',  'russian federation' => 'ru',  'rwanda' => 'rw',
        'saint kitts and nevis' => 'kn',  'saint lucia' => 'lc',
        'saint vincent and the grenadines' => 'vc',  'samoa' => 'ws',  'san marino' => 'sm',
        'sao tome and principe' => 'st',  'saudi arabia' => 'sa',  'senegal' => 'sn',
        'serbia' => 'rs',  'seychelles' => 'sc',  'sierra leone' => 'sl',  'singapore' => 'sg',
        'slovakia' => 'sk',  'slovenia' => 'si',  'solomon islands' => 'sb',  'somalia' => 'so',
        'south africa' => 'za',  'south korea' => 'kr',  'korea' => 'kr',  'south sudan' => 'ss',
        'spain' => 'es',  'sri lanka' => 'lk',  'sudan' => 'sd',  'suriname' => 'sr',
        'sweden' => 'se',  'switzerland' => 'ch',  'syria' => 'sy',  'taiwan' => 'tw',
        'tajikistan' => 'tj',  'tanzania' => 'tz',  'thailand' => 'th',  'timor-leste' => 'tl',
        'east timor' => 'tl',  'togo' => 'tg',  'tonga' => 'to',  'trinidad and tobago' => 'tt',
        'tunisia' => 'tn',  'turkey' => 'tr',  'turkmenistan' => 'tm',  'tuvalu' => 'tv',
        'uganda' => 'ug',  'ukraine' => 'ua',  'united arab emirates' => 'ae',  'uae' => 'ae',
        'united kingdom' => 'gb',  'uk' => 'gb',  'great britain' => 'gb',  'britain' => 'gb',
        'england' => 'gb',  'scotland' => 'gb',  'wales' => 'gb',  'northern ireland' => 'gb',
        'united states' => 'us',  'usa' => 'us',  'us' => 'us',  'america' => 'us',
        'united states of america' => 'us',
        'uruguay' => 'uy',  'uzbekistan' => 'uz',  'vanuatu' => 'vu',  'vatican' => 'va',
        'vatican city' => 'va',  'holy see' => 'va',  'venezuela' => 've',  'vietnam' => 'vn',
        'viet nam' => 'vn',  'yemen' => 'ye',  'zambia' => 'zm',  'zimbabwe' => 'zw',
    ];

    public static function iso(?string $countryName): ?string
    {
        if (! $countryName) return null;
        $key = strtolower(trim($countryName));
        return self::MAP[$key] ?? null;
    }

    public static function flagUrl(?string $countryName, int $width = 24, int $height = 18): ?string
    {
        $iso = self::iso($countryName);
        return $iso ? "https://flagcdn.com/{$width}x{$height}/{$iso}.png" : null;
    }
}
