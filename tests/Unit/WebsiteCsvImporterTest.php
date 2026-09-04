<?php

namespace Tests\Unit;

use App\Imports\WebsiteCsvImporter as Importer;
use PHPUnit\Framework\TestCase;

/**
 * CSV header recognition for the Domains import.
 *
 * Only the DB-free part is covered here: parse() needs countries, languages,
 * categories and the FX rate, and this repo's .env points at LIVE PRODUCTION,
 * so the unit suite must never reach a connection.
 *
 * The case that matters most is the pair of columns whose names differ by a
 * single character — "Link Builder €" holds money, "Link Builder" holds a
 * person's name. Mapping either to the other's field would write a price into
 * a name column without any error being raised.
 */
class WebsiteCsvImporterTest extends TestCase
{
    public function test_link_builder_money_and_person_columns_do_not_collide(): void
    {
        $this->assertSame('link_builder_amount', Importer::headerKey('Link Builder €'));
        $this->assertSame('linkbuilder', Importer::headerKey('Link Builder'));
    }

    /** Excel and Google Sheets round-trips change case and spacing. */
    public function test_headers_are_matched_case_and_space_insensitively(): void
    {
        $this->assertSame('link_builder_amount', Importer::headerKey('  LINK BUILDER €  '));
        $this->assertSame('linkbuilder', Importer::headerKey('link_builder'));
        $this->assertSame('publisher_price', Importer::headerKey('Publisher  Price'));
        $this->assertSame('extra_notes', Importer::headerKey('Internal Notes'));
        $this->assertSame('type_of_website', Importer::headerKey('Type'));
        $this->assertSame('category_names', Importer::headerKey('Category'));
        $this->assertSame('currency_code', Importer::headerKey('Currency'));
    }

    /** "EUR" spelled out is accepted for the money column, since € gets mangled. */
    public function test_link_builder_amount_accepts_the_spelled_out_currency(): void
    {
        $this->assertSame('link_builder_amount', Importer::headerKey('Link Builder EUR'));
        $this->assertSame('link_builder_amount', Importer::headerKey('Link Builder Amount'));
    }

    /** Unknown columns are ignored rather than guessed at. */
    public function test_unknown_headers_are_ignored(): void
    {
        $this->assertNull(Importer::headerKey('Kialvo Evaluation'));
        $this->assertNull(Importer::headerKey('Price'));
        $this->assertNull(Importer::headerKey('Sensitive Topic Price'));
        $this->assertNull(Importer::headerKey(''));
    }

    /**
     * Price and Sensitive Topic Price are calculated, never imported.
     *
     * If someone leaves them in their file they must be ignored, not written —
     * that is what keeps an imported domain priced identically to a
     * hand-entered one.
     */
    public function test_the_template_does_not_offer_calculated_columns(): void
    {
        $this->assertNotContains('Price', Importer::TEMPLATE_HEADERS);
        $this->assertNotContains('Sensitive Topic Price', Importer::TEMPLATE_HEADERS);
        $this->assertContains('Publisher Price', Importer::TEMPLATE_HEADERS);
        $this->assertCount(15, Importer::TEMPLATE_HEADERS);
    }

    /** Every template header must be one the parser actually recognises. */
    public function test_every_template_header_is_recognised(): void
    {
        foreach (Importer::TEMPLATE_HEADERS as $header) {
            $this->assertNotNull(
                Importer::headerKey($header),
                "Template header \"{$header}\" is not mapped to a field."
            );
        }
    }
}
