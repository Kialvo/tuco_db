<?php

namespace App\Services;

use App\Support\MenfordPriceCalculator;
use Illuminate\Support\Facades\DB;

/**
 * The client-facing prices of a domain: Price and Sensitive Topic Price.
 *
 * This logic was copy-pasted into WebsiteController, NewEntryController and
 * NewEntryImportController. The CSV importer needs to produce exactly the same
 * numbers as the Add-domain form — if the two ever disagree, an imported
 * domain is priced differently from a hand-entered one and nobody notices —
 * so it lives in one place instead of a fourth copy.
 *
 * Two rules the whole thing rests on:
 *
 * 1. USD rows are converted from `original_*` × today's rate rather than read
 *    from the stored EUR column, so Price and Sensitive Topic Price always use
 *    the same rate within one request. Reading the stored value instead is what
 *    caused the 1 EUR gap between them in May 2026.
 *
 * 2. The link builder amount is added AFTER that conversion. It is always
 *    entered in euros whatever the domain's currency, so running it through the
 *    rate would silently shrink it (100 EUR becoming ~86).
 *
 * Everything except usdEurRate() is a pure function of the array it is given,
 * so it can be unit tested without a connection — this repo's .env points at
 * live production.
 */
class DomainPriceCalculator
{
    /** What we pay an external link builder. Always EUR; blank counts as 0. */
    public static function linkBuilderAmount(array $data): float
    {
        $amount = $data['link_builder_amount'] ?? null;

        return ($amount === null || $amount === '') ? 0.0 : (float) $amount;
    }

    /**
     * The publisher price in EUR, WITHOUT the link builder amount.
     *
     * Profit subtracts the publisher price and the link builder amount as two
     * distinct terms, so folding them together here would subtract the link
     * builder cost twice. See DomainProfitCalculator.
     */
    public static function publisherPriceInEur(array $data, float $usdEurRate): ?float
    {
        if (! array_key_exists('publisher_price', $data) || $data['publisher_price'] === null || $data['publisher_price'] === '') {
            return null;
        }

        if (strtoupper((string) ($data['currency_code'] ?? '')) !== 'USD') {
            return (float) $data['publisher_price'];
        }

        $baseUsd = $data['original_publisher_price'] ?? $data['publisher_price'];
        if ($baseUsd === null || $baseUsd === '') {
            return null;
        }

        return (float) $baseUsd * $usdEurRate;
    }

    /**
     * The cost base the tier margin is applied to: publisher price + link
     * builder amount, both in EUR.
     *
     * No publisher price means no price at all — a link builder amount on its
     * own never invents one.
     */
    public static function priceBase(array $data, float $usdEurRate): ?float
    {
        $publisherEur = static::publisherPriceInEur($data, $usdEurRate);

        return $publisherEur === null
            ? null
            : $publisherEur + static::linkBuilderAmount($data);
    }

    /** Price = calculate(publisher price + link builder, language). */
    public static function price(array $data, float $usdEurRate): ?float
    {
        return MenfordPriceCalculator::calculate(
            static::priceBase($data, $usdEurRate),
            isset($data['language_id']) ? (int) $data['language_id'] : null
        );
    }

    /**
     * Sensitive Topic Price = calculate(special topic price + link builder, language).
     *
     * Falls back to whatever `price` is already in the array when there is no
     * special topic price — the agreed behaviour, unchanged for years. Callers
     * must therefore set `price` before calling this.
     */
    public static function sensitiveTopicPrice(array $data, float $usdEurRate): ?float
    {
        $langId = isset($data['language_id']) ? (int) $data['language_id'] : null;

        $raw = $data['special_topic_price'] ?? null;
        if ($raw === null || $raw === '') {
            return $data['price'] ?? null;
        }

        if (strtoupper((string) ($data['currency_code'] ?? '')) === 'USD') {
            $baseUsd = $data['original_special_topic_price'] ?? $raw;
            if ($baseUsd === null || $baseUsd === '') {
                return $data['price'] ?? null;
            }
            $eurValue = (float) $baseUsd * $usdEurRate;
        } else {
            $eurValue = (float) $raw;
        }

        // Same rule as the Price formula: EUR amount, added after conversion.
        $eurValue += static::linkBuilderAmount($data);

        return MenfordPriceCalculator::calculate($eurValue, $langId);
    }

    /** Today's USD->EUR rate, as written by `conversion:daily`. */
    public static function usdEurRate(): float
    {
        static $rate = null;

        if ($rate === null) {
            $rate = (float) DB::table('app_settings')
                ->where('setting_name', 'usd_eur_rate')
                ->value('setting_value');

            $rate = $rate > 0 ? $rate : 1.0;
        }

        return $rate;
    }
}
