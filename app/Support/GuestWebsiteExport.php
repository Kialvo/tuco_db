<?php

namespace App\Support;

use App\Models\Website;

class GuestWebsiteExport
{
    public static function columns(): array
    {
        return [
            'domain_name' => 'Domain',
            'notes' => 'Notes',
            'country_name' => 'Country',
            'language_name' => 'Language',
            'price' => 'Price',
            'sensitive_topic_price' => 'Sensitive Topic Price',
            'type_of_website' => 'Type of Website',
            'categories_list' => 'Categories',
            'DA' => 'DA',
            'PA' => 'PA',
            'TF' => 'TF',
            'CF' => 'CF',
            'DR' => 'DR',
            'UR' => 'UR',
            'ZA' => 'ZA',
            'as_metric' => 'AS',
            'seozoom' => 'SEO Zoom',
            'TF_vs_CF' => 'TF vs CF',
            'semrush_traffic' => 'Semrush Traffic',
            'ahrefs_keyword' => 'Ahrefs Keyword',
            'ahrefs_traffic' => 'Ahrefs Traffic',
            'keyword_vs_traffic' => 'Keyword vs Traffic',
            'betting' => 'Betting',
            'trading' => 'Trading',
        ];
    }

    public static function headers(): array
    {
        return array_values(self::columns());
    }

    public static function queryColumns(): array
    {
        return [
            'id',
            'domain_name',
            'notes',
            'country_id',
            'language_id',
            'price',
            'sensitive_topic_price',
            'type_of_website',
            'DA',
            'PA',
            'TF',
            'CF',
            'DR',
            'UR',
            'ZA',
            'as_metric',
            'seozoom',
            'TF_vs_CF',
            'semrush_traffic',
            'ahrefs_keyword',
            'ahrefs_traffic',
            'keyword_vs_traffic',
            'betting',
            'trading',
        ];
    }

    public static function row(Website $website): array
    {
        $country = self::relationOrNull($website, 'country');
        $language = self::relationOrNull($website, 'language');
        $categories = self::relationOrNull($website, 'categories') ?? collect();
        $yesNo = static fn ($value) => $value === null ? '' : ($value ? 'YES' : 'NO');

        return [
            'domain_name' => $website->domain_name,
            'notes' => $website->notes,
            'country_name' => $country?->country_name,
            'language_name' => $language?->name,
            'price' => $website->price,
            'sensitive_topic_price' => $website->sensitive_topic_price,
            'type_of_website' => $website->type_of_website,
            'categories_list' => $categories->pluck('name')->join(', '),
            'DA' => $website->DA,
            'PA' => $website->PA,
            'TF' => $website->TF,
            'CF' => $website->CF,
            'DR' => $website->DR,
            'UR' => $website->UR,
            'ZA' => $website->ZA,
            'as_metric' => $website->as_metric,
            'seozoom' => $website->seozoom,
            'TF_vs_CF' => $website->TF_vs_CF,
            'semrush_traffic' => $website->semrush_traffic,
            'ahrefs_keyword' => $website->ahrefs_keyword,
            'ahrefs_traffic' => $website->ahrefs_traffic,
            'keyword_vs_traffic' => $website->keyword_vs_traffic,
            'betting' => $yesNo($website->betting),
            'trading' => $yesNo($website->trading),
        ];
    }

    public static function values(Website $website): array
    {
        $row = self::row($website);

        return array_map(
            static fn (string $field) => $row[$field] ?? '',
            array_keys(self::columns())
        );
    }

    public static function rows(iterable $websites): array
    {
        $rows = [];

        foreach ($websites as $website) {
            $rows[] = self::values($website);
        }

        return $rows;
    }

    private static function relationOrNull(Website $website, string $relation): mixed
    {
        return $website->relationLoaded($relation)
            ? $website->getRelation($relation)
            : null;
    }
}
