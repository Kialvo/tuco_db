<?php

namespace App\Console\Commands;

use App\Models\Website;
use App\Support\MenfordPriceCalculator;
use Illuminate\Console\Command;

/**
 * Fills the client-facing price on websites left blank by the untiered-language
 * bug (see MenfordPriceCalculator, fixed 2026-08-08).
 *
 * The calculator only runs when a website is saved, so fixing the formula does
 * NOT repair rows that are already blank — they stay blank, and blank is
 * dangerous: guestMarketplace() does not filter them and OrderItem::refreshPrice()
 * falls back to 0, so an active domain with no price is orderable at EUR 0.00.
 *
 * ONLY blank columns are written. Rows that already carry a price are never
 * touched, because a blanket recalculation would silently overwrite deliberate
 * manual pricing (verified: dagangnews.com 300, radiojambo.co.ke 390 and
 * amongtech.com 250 would all move).
 *
 * Dry run by default; pass --apply to write.
 */
class BackfillNullWebsitePrices extends Command
{
    protected $signature = 'websites:backfill-null-prices {--apply : Write the changes (default is a dry run)}';

    protected $description = 'Fill blank price / sensitive_topic_price on websites, without touching existing values';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $websites = Website::query()
            ->whereNotNull('language_id')
            ->whereNotNull('publisher_price')
            ->where(fn ($q) => $q->whereNull('price')->orWhereNull('sensitive_topic_price'))
            ->with('language:id,name')
            ->get(['id', 'domain_name', 'language_id', 'publisher_price', 'special_topic_price', 'price', 'sensitive_topic_price']);

        if ($websites->isEmpty()) {
            $this->info('Nothing to backfill.');

            return self::SUCCESS;
        }

        $rows = [];
        $updates = [];

        foreach ($websites as $w) {
            $lang = $w->language?->name;

            // price: the client price for a standard placement.
            $newPrice = $w->price === null
                ? MenfordPriceCalculator::calculateForLanguage((float) $w->publisher_price, $lang)
                : null;

            // sensitive: derived from special_topic_price when set, else it
            // mirrors price — the same rule WebsiteController applies on save.
            $newSensitive = null;
            if ($w->sensitive_topic_price === null) {
                $newSensitive = filled($w->special_topic_price)
                    ? MenfordPriceCalculator::calculateForLanguage((float) $w->special_topic_price, $lang)
                    : ($w->price ?? $newPrice);
            }

            if ($newPrice === null && $newSensitive === null) {
                continue;
            }

            $rows[] = [
                $w->domain_name,
                $lang ?? '—',
                (string) $w->publisher_price,
                $w->price ?? 'NULL',
                $newPrice === null ? '(kept)' : (string) $newPrice,
                $w->sensitive_topic_price ?? 'NULL',
                $newSensitive === null ? '(kept)' : (string) $newSensitive,
            ];

            $payload = [];
            if ($newPrice !== null) {
                $payload['price'] = $newPrice;
            }
            if ($newSensitive !== null) {
                $payload['sensitive_topic_price'] = $newSensitive;
            }
            $updates[$w->id] = $payload;
        }

        if (! $rows) {
            $this->info('Nothing to backfill.');

            return self::SUCCESS;
        }

        $this->table(
            ['Domain', 'Language', 'Publisher', 'Price now', 'Price new', 'Sens now', 'Sens new'],
            $rows
        );

        if (! $apply) {
            $this->warn(count($rows).' row(s) would be updated. DRY RUN — nothing written. Re-run with --apply.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Write these '.count($rows).' row(s)?', false)) {
            $this->info('Aborted, nothing written.');

            return self::SUCCESS;
        }

        // saveQuietly + targeted columns: never re-run the model's other
        // derived-field logic, and never touch a column we did not compute.
        foreach ($updates as $id => $payload) {
            Website::withoutTimestamps(fn () => Website::whereKey($id)->update($payload));
        }

        $this->info(count($updates).' row(s) updated.');

        return self::SUCCESS;
    }
}
