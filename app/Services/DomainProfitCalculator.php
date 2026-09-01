<?php

namespace App\Services;

/**
 * The one and only definition of a domain's Profit.
 *
 *     profit = kialvo_evaluation − publisher price (EUR) − link builder amount
 *
 * Before this class the figure was written in five different places that did
 * not agree: two PHP formulas in WebsiteController plus a third in
 * applyAutoCalculations() that counted banner + sitewide as revenue, and two
 * MySQL triggers that recomputed it again for USD rows and discarded whatever
 * PHP had just written. Consequently the same domain could show a different
 * Profit depending on whether it had been created, edited, or bulk-edited.
 *
 * The triggers no longer touch `profit` (see the 2026_09_01 trigger
 * migration), so this is now the single writer.
 *
 * Deliberately DB-free: the caller passes the publisher price already
 * converted to EUR, so this stays a pure function and the unit suite never
 * risks opening a connection to production.
 */
class DomainProfitCalculator
{
    /**
     * @param  mixed  $kialvoEvaluation  raw column value; blank means "unknown"
     * @param  float|null  $publisherPriceEur  publisher price already in EUR, WITHOUT the link builder amount
     * @param  mixed  $linkBuilderAmount  raw column value; blank counts as 0
     */
    public static function calculate($kialvoEvaluation, ?float $publisherPriceEur, $linkBuilderAmount): ?float
    {
        // Blank stays blank. 47 domains have no Kialvo Evaluation and show an
        // empty Profit today; without this they would silently become negative
        // numbers (0 − publisher − link builder) the next time anyone saved them.
        if ($kialvoEvaluation === null || $kialvoEvaluation === '') {
            return null;
        }

        $linkBuilder = ($linkBuilderAmount === null || $linkBuilderAmount === '')
            ? 0.0
            : (float) $linkBuilderAmount;

        // No publisher price behaves as 0, exactly as the old formulas did.
        return (float) $kialvoEvaluation - (float) ($publisherPriceEur ?? 0) - $linkBuilder;
    }
}
