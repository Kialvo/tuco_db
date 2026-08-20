<?php

namespace Tests\Unit;

use App\Support\MenfordPriceCalculator as Calc;
use PHPUnit\Framework\TestCase;

/**
 * Client-price formula.
 *
 * Targets calculateForLanguage() rather than calculate(): the latter resolves a
 * language id through the Language model, and this repo's .env points at LIVE
 * PRODUCTION, so nothing in the unit suite may risk opening a connection.
 */
class MenfordPriceCalculatorTest extends TestCase
{
    /** Above 500 the margin is a flat +20% and the language is irrelevant. */
    public function test_over_500_is_twenty_percent_regardless_of_language(): void
    {
        foreach (['greek', 'italian', 'english', 'klingon', null] as $lang) {
            if ($lang === null) {
                continue;   // no language is a separate case, below
            }
            $this->assertSame(601.0, Calc::calculateForLanguage(501, $lang));
            $this->assertSame(720.0, Calc::calculateForLanguage(600, $lang));
        }
    }

    /** Tier 1: +87 under 300, +107 from 300 to 500. Ukrainian joined 2026-08-08. */
    public function test_tier_1_margins(): void
    {
        foreach (['italian', 'portuguese', 'russian', 'ukrainian'] as $lang) {
            $this->assertSame(187.0, Calc::calculateForLanguage(100, $lang), $lang);
            $this->assertSame(407.0, Calc::calculateForLanguage(300, $lang), $lang);
        }
    }

    /** Tier 2: +97 / +117. */
    public function test_tier_2_margins(): void
    {
        foreach (['english', 'french', 'german', 'polish', 'spanish', 'lithuanian'] as $lang) {
            $this->assertSame(197.0, Calc::calculateForLanguage(100, $lang), $lang);
            $this->assertSame(417.0, Calc::calculateForLanguage(300, $lang), $lang);
        }
    }

    /** Tier 3: +107 / +127 — including the seven added 2026-08-08. */
    public function test_tier_3_margins(): void
    {
        $tier3 = ['dutch', 'romanian', 'danish', 'greek', 'japanese', 'malay', 'norwegian', 'swahili', 'turkish'];

        foreach ($tier3 as $lang) {
            $this->assertSame(207.0, Calc::calculateForLanguage(100, $lang), $lang);
            $this->assertSame(427.0, Calc::calculateForLanguage(300, $lang), $lang);
        }
    }

    /** The reported bug: Greek under 500 used to return null. */
    public function test_greek_under_500_is_priced(): void
    {
        $this->assertSame(177.0, Calc::calculateForLanguage(70, 'greek'));
        $this->assertSame(207.0, Calc::calculateForLanguage(100, 'greek'));
        $this->assertSame(307.0, Calc::calculateForLanguage(200, 'greek'));
        $this->assertSame(487.0, Calc::calculateForLanguage(360, 'greek'));
        $this->assertSame(527.0, Calc::calculateForLanguage(400, 'greek'));
    }

    /** An untiered language falls back to TIER_3, never to null. */
    public function test_unlisted_language_falls_back_to_tier_3(): void
    {
        foreach (['bulgarian', 'croatian', 'serbian', 'a-language-we-have-never-seen'] as $lang) {
            $this->assertSame(207.0, Calc::calculateForLanguage(100, $lang), $lang);
            $this->assertSame(427.0, Calc::calculateForLanguage(300, $lang), $lang);
        }
    }

    /** Names are normalised the same way languages.name is read. */
    public function test_language_matching_is_case_and_space_insensitive(): void
    {
        $this->assertSame(207.0, Calc::calculateForLanguage(100, '  Greek '));
        $this->assertSame(187.0, Calc::calculateForLanguage(100, 'UKRAINIAN'));
    }

    /** Missing data must not become a price. */
    public function test_missing_language_or_price_returns_null(): void
    {
        $this->assertNull(Calc::calculateForLanguage(100, null));
        $this->assertNull(Calc::calculateForLanguage(100, ''));
        $this->assertNull(Calc::calculateForLanguage(100, '   '));
        $this->assertNull(Calc::calculateForLanguage(null, 'greek'));
        $this->assertNull(Calc::calculateForLanguage(-1, 'greek'));
    }

    /** The 300 boundary belongs to the upper band. */
    public function test_the_300_and_500_boundaries(): void
    {
        $this->assertSame(386.0, Calc::calculateForLanguage(299, 'italian'));   // 299 + 87
        $this->assertSame(407.0, Calc::calculateForLanguage(300, 'italian'));   // 300 + 107
        $this->assertSame(607.0, Calc::calculateForLanguage(500, 'italian'));   // still the margin
        $this->assertSame(601.0, Calc::calculateForLanguage(501, 'italian'));   // now +20%
    }
}
