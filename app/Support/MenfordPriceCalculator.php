<?php

namespace App\Support;

use App\Models\Language;

class MenfordPriceCalculator
{
    /**
     * Language pricing tiers. Names are compared lowercased + trimmed against
     * `languages.name`.
     *
     * 2026-08-08: Ukrainian added to TIER_1 and Danish/Greek/Japanese/Malay/
     * Norwegian/Swahili/Turkish to TIER_3 (Martina). These languages had been
     * priced this way historically — their stored prices already match these
     * margins — but were missing from the lists, so every domain under €500 in
     * those languages silently produced a blank client price.
     */
    private const TIER_1 = ['italian', 'portuguese', 'russian', 'ukrainian'];

    private const TIER_2 = ['english', 'french', 'german', 'polish', 'spanish', 'lithuanian'];

    private const TIER_3 = [
        'dutch', 'finnish', 'swedish', 'czech', 'slovak', 'hungarian', 'romanian',
        'danish', 'greek', 'japanese', 'malay', 'norwegian', 'swahili', 'turkish',
    ];

    /**
     * Formula:
     * - > 500: +20%, language irrelevant
     * - <= 500: a fixed margin by language tier, split at 300
     * Returns null only when the language is UNKNOWN (not set / not in the
     * `languages` table) — missing data must not be turned into a price.
     */
    public static function calculate(?float $publisherPrice, ?int $languageId): ?float
    {
        return static::calculateForLanguage($publisherPrice, self::languageNameById($languageId));
    }

    /**
     * The pricing rule itself, keyed by language NAME.
     *
     * Split out from calculate() so it can be unit-tested without a database:
     * calculate() has to resolve the id through the Language model, which needs
     * a connection, and this repo's .env points at live production.
     */
    public static function calculateForLanguage(?float $publisherPrice, ?string $languageName): ?float
    {
        if ($publisherPrice === null) {
            return null;
        }

        $publisher = (float) $publisherPrice;
        if ($publisher < 0) {
            return null;
        }

        if ($publisher > 500) {
            return (float) round($publisher * 1.20, 0);
        }

        $language = $languageName === null ? null : mb_strtolower(trim($languageName));
        if ($language === null || $language === '') {
            return null;
        }

        return (float) round($publisher + self::marginForTier($publisher, $language), 0);
    }

    /**
     * TIER_3 doubles as the fallback for any language not listed above: it
     * carries the highest margin, so an untiered language can never underprice.
     * Before 2026-08-08 this returned null instead, which is what produced the
     * blank prices — a new language silently made domains unsellable.
     */
    private static function marginForTier(float $publisherPrice, string $language): float
    {
        $isLowRange = $publisherPrice < 300;

        if (in_array($language, self::TIER_1, true)) {
            return $isLowRange ? 87.0 : 107.0;
        }

        if (in_array($language, self::TIER_2, true)) {
            return $isLowRange ? 97.0 : 117.0;
        }

        return $isLowRange ? 107.0 : 127.0;
    }

    private static function languageNameById(?int $languageId): ?string
    {
        if (! $languageId) {
            return null;
        }

        static $byId = null;
        if ($byId === null) {
            $byId = Language::query()
                ->pluck('name', 'id')
                ->mapWithKeys(fn ($name, $id) => [(int) $id => mb_strtolower(trim((string) $name))])
                ->all();
        }

        return $byId[(int) $languageId] ?? null;
    }
}
