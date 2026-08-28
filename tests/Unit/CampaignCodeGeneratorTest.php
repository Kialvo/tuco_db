<?php

namespace Tests\Unit;

use App\Support\CampaignCodeGenerator;
use PHPUnit\Framework\TestCase;

/**
 * The Menford Sales SOP (PHASE 1) letter rule, applied to guest names.
 * Pure string logic — no container, no database.
 */
class CampaignCodeGeneratorTest extends TestCase
{
    private CampaignCodeGenerator $gen;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gen = new CampaignCodeGenerator;
    }

    public function test_first_four_consonants_across_words(): void
    {
        $this->assertSame('MRRS', $this->gen->letters('Mario Rossi'));
        $this->assertSame('RDBL', $this->gen->letters('Red Bull'));      // SOP example
        $this->assertSame('BTTR', $this->gen->letters('Better Collective'));
    }

    public function test_accents_are_transliterated(): void
    {
        $this->assertSame('MLLR', $this->gen->letters('Müller'));        // SOP example
        $this->assertSame('BRNN', $this->gen->letters('Bruno Nicolò'));
    }

    /** Y is a consonant, per the SOP. */
    public function test_y_counts_as_a_consonant(): void
    {
        $this->assertSame('YNGY', $this->gen->letters('Yang Yu'));
        $this->assertSame('MYRS', $this->gen->letters('Mya Rossi'));
    }

    public function test_legal_suffixes_are_stripped(): void
    {
        $this->assertSame('BTAI', $this->gen->letters('Betaioa Ltd'));   // SOP example
        $this->assertSame('LIRA', $this->gen->letters('Lira Ltd'));      // SOP example
        $this->assertSame('WBLE', $this->gen->letters('Weble srl'));
    }

    /**
     * Fewer than four consonants: fill from vowels AFTER the last consonant,
     * then before it, each keeping its original position in the name.
     */
    public function test_vowel_fallback_preserves_original_order(): void
    {
        // Lira: consonants L,R — vowels I(1) before R, A(3) after R.
        // A comes first (after the last consonant), then I, but re-sorted by
        // position the result reads in name order: L I R A.
        $this->assertSame('LIRA', $this->gen->letters('Lira'));
    }

    public function test_digits_and_punctuation_are_ignored(): void
    {
        $this->assertSame('CMMN', $this->gen->letters('e2 Communications'));
        $this->assertSame('OBRN', $this->gen->letters("O'Brien"));
    }

    public function test_very_short_names_are_padded_not_broken(): void
    {
        $this->assertSame(4, strlen($this->gen->letters('Al')));
        $this->assertSame(4, strlen($this->gen->letters('Bo')));
    }

    /** A name with nothing usable must still yield a valid code. */
    public function test_unusable_names_fall_back(): void
    {
        $this->assertSame('XXXX', $this->gen->letters('12345'));
        $this->assertSame('XXXX', $this->gen->letters(''));
        $this->assertSame('XXXX', $this->gen->letters('!!!'));
    }

    public function test_always_returns_exactly_four_characters(): void
    {
        foreach (['Mario Rossi', 'Al', '', '12345', 'Yang Yu', 'Lira Ltd', 'X'] as $name) {
            $this->assertSame(4, strlen($this->gen->letters($name)), "name: {$name}");
        }
    }

    /** Collision rule: newer name swaps its 4th consonant for its 5th. */
    public function test_collision_variant_uses_the_fifth_consonant(): void
    {
        // Podstars: P,D,S,T,R,S -> normally PDST, variant swaps 4th for 5th.
        $this->assertSame('PDSR', $this->gen->collisionVariant('Podstars'));
    }

    public function test_collision_variant_is_null_without_a_fifth_consonant(): void
    {
        $this->assertNull($this->gen->collisionVariant('Mario Rossi'));   // only 4
        $this->assertNull($this->gen->collisionVariant('Al'));
    }

    public function test_code_format(): void
    {
        $this->assertSame('MRRS_LIAB_001', $this->gen->format('MRRS', 1));
        $this->assertSame('MRRS_LIAB_012', $this->gen->format('MRRS', 12));
        $this->assertSame('MRRS_LIAB_999', $this->gen->format('mrrs', 999));
    }
}
