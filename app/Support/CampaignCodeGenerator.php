<?php

namespace App\Support;

/**
 * Campaign codes for marketplace orders: [4 LETTERS]_LIAB_[NNN].
 *
 * The letter rule is the one documented in the Menford Sales SOP (PHASE 1) for
 * company names, applied here to the guest's name because marketplace guests
 * have no company on file yet. Tag is _LIAB_ rather than _LB_ so marketplace
 * campaigns are distinguishable at a glance from Martina's own.
 *
 * NOTE for reviewers: the 185 existing campaign codes were written by hand and
 * do NOT follow this rule (Better Collective is BTCL, the rule yields BTTR).
 * Marketplace codes will therefore look consistent with each other and with the
 * SOP, but not with the legacy ones. That was accepted knowingly — see the
 * Monday thread.
 */
class CampaignCodeGenerator
{
    public const TAG = 'LIAB';

    /** Legal suffixes stripped before extracting letters. */
    private const SUFFIXES = [
        'srl', 's.r.l', 'ltd', 'limited', 'llc', 'inc', 'gmbh', 'bv', 'nv',
        'oü', 'ou', 'oy', 'ab', 'as', 'sa', 'spa', 's.p.a', 'plc', 'kft', 'sp',
    ];

    /**
     * The 4-letter prefix for a name.
     *
     * Rule: first 4 consonants, uppercased, concatenated across words, accents
     * transliterated, Y counts as a consonant. With fewer than 4 consonants,
     * fill from the vowels AFTER the last consonant in order; if still short,
     * use vowels BEFORE it, keeping each letter's original position in the name
     * (so "Lira" gives LIRA, not LRAI).
     */
    public function letters(string $name): string
    {
        $clean = $this->normalise($name);

        if ($clean === '') {
            return 'XXXX';
        }

        $letters = str_split($clean);

        // Index positions so the vowel fallback can preserve original order.
        $consonants = [];
        $vowels = [];
        foreach ($letters as $i => $ch) {
            if ($this->isVowel($ch)) {
                $vowels[$i] = $ch;
            } else {
                $consonants[$i] = $ch;
            }
        }

        if (count($consonants) >= 4) {
            return strtoupper(implode('', array_slice($consonants, 0, 4, true)));
        }

        $picked = $consonants;                       // keyed by position
        $lastConsonantAt = empty($consonants) ? -1 : array_key_last($consonants);

        // Vowels after the last consonant, in order.
        foreach ($vowels as $pos => $ch) {
            if (count($picked) >= 4) {
                break;
            }
            if ($pos > $lastConsonantAt) {
                $picked[$pos] = $ch;
            }
        }

        // Still short: vowels before it, still in original order.
        foreach ($vowels as $pos => $ch) {
            if (count($picked) >= 4) {
                break;
            }
            if ($pos < $lastConsonantAt) {
                $picked[$pos] = $ch;
            }
        }

        ksort($picked);
        $out = strtoupper(implode('', $picked));

        // Names shorter than 4 letters (e.g. "Al") pad rather than fail.
        return str_pad(substr($out, 0, 4), 4, 'X');
    }

    /**
     * The 5th-consonant swap the SOP prescribes when two names collide: the
     * older code is kept, the newer one replaces its 4th consonant.
     * Returns null when there is no 5th consonant to fall back on.
     */
    public function collisionVariant(string $name): ?string
    {
        $clean = $this->normalise($name);
        $consonants = array_values(array_filter(str_split($clean), fn ($c) => ! $this->isVowel($c)));

        if (count($consonants) < 5) {
            return null;
        }

        $variant = strtoupper($consonants[0].$consonants[1].$consonants[2].$consonants[4]);

        // When the 4th and 5th consonants are the same letter the swap is a
        // no-op ("Mario Rossi" -> MRRS either way) and resolves nothing. Say so
        // rather than handing back a "variant" that still collides.
        return $variant === $this->letters($name) ? null : $variant;
    }

    /** Assemble a full code. The number is a per-prefix progressive. */
    public function format(string $letters, int $sequence): string
    {
        return strtoupper($letters).'_'.self::TAG.'_'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    /** Strip accents, punctuation, digits and legal suffixes; letters only. */
    private function normalise(string $name): string
    {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT', $name);
        $ascii = $ascii === false ? $name : $ascii;
        $ascii = preg_replace('/[^A-Za-z ]/', '', $ascii) ?? '';

        $words = array_filter(preg_split('/\s+/', trim($ascii)) ?: []);
        $words = array_filter($words, fn ($w) => ! in_array(strtolower($w), self::SUFFIXES, true));

        return implode('', $words);
    }

    /** Y is a consonant here, per the SOP. */
    private function isVowel(string $ch): bool
    {
        return in_array(strtolower($ch), ['a', 'e', 'i', 'o', 'u'], true);
    }
}
