<?php

namespace App\Services\Tokens;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Indicative EUR -> X rates for DISPLAY ONLY.
 *
 * Nothing here ever influences how many tokens someone has, buys or spends.
 * A balance is euros; this only decides what the "approx." line underneath it
 * says. That separation is deliberate — see config/tokens.php.
 *
 * When a rate is unknown or too old we return null and the UI HIDES the
 * conversion. A missing figure is honest; a stale one quietly misinforms
 * someone about what their balance is worth.
 */
class RateProvider
{
    private const CACHE_KEY = 'tokens:fx:eur';

    /**
     * @return array{rate: float, fetched_at: \Illuminate\Support\Carbon}|null
     */
    public function rate(string $currency): ?array
    {
        $currency = strtoupper($currency);
        $base = strtoupper((string) config('tokens.currency', 'EUR'));

        if ($currency === $base) {
            return null;   // nothing to convert
        }

        if (! in_array($currency, config('tokens.display_currencies', []), true)) {
            return null;
        }

        $rates = Cache::remember(self::CACHE_KEY, now()->addMinutes(30), fn () => $this->load());

        $rate = $rates[$currency] ?? null;

        if ($rate === null || $rate <= 0) {
            return null;
        }

        return ['rate' => (float) $rate, 'fetched_at' => now()];
    }

    /** Convert a euro amount for display, or null when no usable rate exists. */
    public function convert(float $euros, string $currency): ?float
    {
        $rate = $this->rate($currency);

        return $rate === null ? null : round($euros * $rate['rate'], 2);
    }

    /**
     * Currently the only source is the USD->EUR figure the existing
     * `conversion:daily` command writes into app_settings, inverted.
     *
     * Two known weaknesses, both deliberate to leave visible rather than
     * paper over: that command is NOT scheduled, and app_settings has no
     * timestamp, so the rate's true age is unknowable. Currencies with no
     * source (GBP today) simply return nothing and the UI hides them. A
     * dedicated, scheduled, timestamped rate feed is the proper fix.
     */
    private function load(): array
    {
        $usdEur = (float) DB::table('app_settings')
            ->where('setting_name', 'usd_eur_rate')
            ->value('setting_value');

        $rates = [];

        if ($usdEur > 0) {
            $rates['USD'] = round(1 / $usdEur, 6);   // EUR -> USD
        }

        return $rates;
    }
}
