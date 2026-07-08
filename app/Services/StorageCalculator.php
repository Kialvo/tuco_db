<?php

namespace App\Services;

use Illuminate\Support\Carbon;

/**
 * Derived-field rules for `storage` rows, extracted from StorageController so
 * the Campaigns module applies the exact same math when it writes publications.
 *
 * total_cost     = publisher_amount (or publisher) + copy_nr
 * total_revenues = menford + client_copy          ← ALWAYS derived; never write it directly
 * profit         = total_revenues − total_cost
 */
class StorageCalculator
{
    public static function apply(array &$data): void
    {
        /* ---------------- prices / profit -------------------------------- */
        $publisher        = (float) ($data['publisher_amount'] ?? $data['publisher'] ?? 0);
        $copywriterAmount = (float) ($data['copy_nr'] ?? 0);
        $data['total_cost'] = $publisher + $copywriterAmount;

        $menford    = (float) ($data['menford'] ?? 0);
        $clientCopy = (float) ($data['client_copy'] ?? 0);
        $data['total_revenues'] = $menford + $clientCopy;

        $data['profit'] = $data['total_revenues'] - $data['total_cost'];

        /* ---------------- Copy period  (submission − commission) --------- */
        if (!empty($data['copywriter_commision_date']) && !empty($data['copywriter_submission_date'])) {
            $from = Carbon::parse($data['copywriter_commision_date']);
            $to   = Carbon::parse($data['copywriter_submission_date']);
            $data['copywriter_period'] = $from->diffInDays($to);
        }

        /* ---------------- Publisher period  (publication − sent) --------- */
        if (!empty($data['article_sent_to_publisher']) && !empty($data['publication_date'])) {
            $from = Carbon::parse($data['article_sent_to_publisher']);
            $to   = Carbon::parse($data['publication_date']);
            $data['publisher_period'] = $from->diffInDays($to);
        }
    }

    /**
     * Set the Campaigns-facing "Price" (= desired total_revenues) on a storage
     * attribute array by adjusting `menford` so the derived total lands on it.
     */
    public static function setPrice(array &$data, float $price): void
    {
        $clientCopy      = (float) ($data['client_copy'] ?? 0);
        $data['menford'] = max(0, $price - $clientCopy);

        static::apply($data);
    }
}
