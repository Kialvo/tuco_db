<?php

namespace App\Support;

use App\Models\Language;

class MenfordPriceCalculator
{
    private const TIER_1 = ['italian', 'portuguese', 'russian'];
    private const TIER_2 = ['english', 'french', 'german', 'polish', 'spanish', 'lithuanian'];
    private const TIER_3 = ['dutch', 'finnish', 'swedish', 'czech', 'slovak', 'hungarian', 'romanian'];

    /**
     * Formula:
     * - < 300: fixed margin by language tier
     * - 300-499.99: fixed margin by language tier
     * - > 500: +20%
     * Unknown language:
     * - <= 500: null (no deterministic tier)
     * - > 500: +20% (same rule for all tiers)
     */
    public static function calculate(?float $publisherPrice, ?int $languageId): ?float
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

        $language = self::languageNameById($languageId);
        if ($language === null) {
            return null;
        }

        $margin = self::marginForTier($publisher, $language);
        if ($margin === null) {
            return null;
        }

        return (float) round($publisher + $margin, 0);
    }

    private static function marginForTier(float $publisherPrice, string $language): ?float
    {
        $isLowRange = $publisherPrice < 300;

        if (in_array($language, self::TIER_1, true)) {
            return $isLowRange ? 87.0 : 107.0;
        }

        if (in_array($language, self::TIER_2, true)) {
            return $isLowRange ? 97.0 : 117.0;
        }

        if (in_array($language, self::TIER_3, true)) {
            return $isLowRange ? 107.0 : 127.0;
        }

        return null;
    }

    private static function languageNameById(?int $languageId): ?string
    {
        if (!$languageId) {
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
