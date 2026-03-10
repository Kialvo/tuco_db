<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Country;
use App\Models\Language;
use App\Models\Website;
use App\Support\GuestWebsiteExport;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use PHPUnit\Framework\TestCase;

class GuestWebsiteExportTest extends TestCase
{
    public function test_guest_export_headers_match_guest_table_order(): void
    {
        $this->assertSame([
            'Domain',
            'Notes',
            'Country',
            'Language',
            'Price',
            'Sensitive Topic Price',
            'Type of Website',
            'Categories',
            'DA',
            'PA',
            'TF',
            'CF',
            'DR',
            'UR',
            'ZA',
            'AS',
            'SEO Zoom',
            'TF vs CF',
            'Semrush Traffic',
            'Ahrefs Keyword',
            'Ahrefs Traffic',
            'Keyword vs Traffic',
            'Betting',
            'Trading',
        ], GuestWebsiteExport::headers());
    }

    public function test_guest_export_row_and_values_follow_the_same_order(): void
    {
        $country = new Country();
        $country->country_name = 'Italy';

        $language = new Language();
        $language->name = 'English';

        $finance = new Category();
        $finance->name = 'Finance';

        $tech = new Category();
        $tech->name = 'Tech';

        $website = new Website([
            'domain_name' => 'example.com',
            'notes' => 'Guest note',
            'price' => 120.5,
            'sensitive_topic_price' => 180.75,
            'type_of_website' => 'GENERALIST',
            'DA' => 50,
            'PA' => 44,
            'TF' => 30,
            'CF' => 28,
            'DR' => 61,
            'UR' => 27,
            'ZA' => 15,
            'as_metric' => 19,
            'seozoom' => 4200,
            'TF_vs_CF' => 1.07,
            'semrush_traffic' => 8300,
            'ahrefs_keyword' => 1700,
            'ahrefs_traffic' => 7600,
            'keyword_vs_traffic' => 0.22,
            'betting' => true,
            'trading' => false,
        ]);
        $website->setRelation('country', $country);
        $website->setRelation('language', $language);
        $website->setRelation('categories', new EloquentCollection([$finance, $tech]));

        $this->assertSame(
            array_keys(GuestWebsiteExport::columns()),
            array_keys(GuestWebsiteExport::row($website))
        );

        $this->assertSame([
            'example.com',
            'Guest note',
            'Italy',
            'English',
            120.5,
            180.75,
            'GENERALIST',
            'Finance, Tech',
            50,
            44,
            30,
            28,
            61,
            27,
            15,
            19,
            4200,
            1.07,
            8300,
            1700,
            7600,
            0.22,
            'YES',
            'NO',
        ], GuestWebsiteExport::values($website));
    }
}
