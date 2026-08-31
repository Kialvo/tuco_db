<?php

namespace App\Support;

/**
 * Translates the INTERNAL publication status Martina sets into the customer-
 * facing tracker shown inside an order.
 *
 * The two vocabularies are deliberately different: internal statuses are
 * granular and built for campaign tracking, and several of them would be
 * meaningless or alarming to a customer. Approved mapping is from the Monday
 * thread (Simone 2026-08-27, approved by Fabrizio).
 *
 * Note the deliberate OFF-BY-ONE: a customer step is marked complete when the
 * internal status has moved PAST it. "Publisher Confirmation" lights up once we
 * are internally at "Waiting Copywriter", because that is the point at which
 * the confirmation actually happened.
 */
class PublicationProgress
{
    /** Ordered customer-facing steps. Index = how far the order has come. */
    public const STEPS = [
        'order_submitted' => 'Order Submitted',
        'publisher_confirmation' => 'Publisher Confirmation',
        'article_ready' => 'Article Ready',
        'article_approved' => 'Article Approved',
        'article_published' => 'Article Published',
    ];

    /**
     * Internal status => how many customer steps are COMPLETE once it is set.
     *
     * waiting_blog_price_confirmation is the status a publication is created
     * with, and it corresponds to "Order Submitted" being done (1).
     */
    private const REACHED = [
        'waiting_blog_price_confirmation' => 1,   // Order Submitted done
        'waiting_copywriter' => 2,   // Publisher Confirmation done
        'waiting_client_article_approval' => 3,   // Article Ready done
        'waiting_blog_publication' => 4,   // Article Approved done
        'article_published' => 5,   // all done
    ];

    /**
     * Internal statuses that stop the tracker and show a red flag instead.
     *
     * publisher_refused is mapped alongside high_price on purpose. Simone's
     * spec named only high_price ("the final refusal after we have tried to
     * push the old price"), but publisher_refused remains selectable and means
     * the same thing to a customer. Left unmapped it would leave a dead order
     * displaying "in progress" forever, which is worse than telling them.
     */
    private const EXCEPTIONS = [
        'high_price' => 'Blog Refused to Publish',
        'publisher_refused' => 'Blog Refused to Publish',
        'publisher_disappeared' => 'Publisher Disappeared',
    ];

    /**
     * How many steps are complete for a given internal status.
     *
     * Unmapped statuses (Accepted, Potential Substitute, the traditional-LB
     * refusals…) return null: the caller keeps whatever the order had reached
     * before, so an internal-only status never moves or resets the customer's
     * view — and never leaks its name.
     */
    public static function reached(?string $status): ?int
    {
        return self::REACHED[$status] ?? null;
    }

    /** The red-flag label for a status, or null when it is not an exception. */
    public static function exception(?string $status): ?string
    {
        return self::EXCEPTIONS[$status] ?? null;
    }

    public static function isException(?string $status): bool
    {
        return self::exception($status) !== null;
    }

    /**
     * Build the tracker for one publication.
     *
     * @param  string|null  $status  the internal storage status
     * @param  array<string, string|null>  $timestamps  step key => ISO date reached
     * @return array{steps: array<int, array{key:string,label:string,done:bool,at:?string}>, exception: ?string}
     */
    public static function build(?string $status, array $timestamps = []): array
    {
        $exception = self::exception($status);

        // On an exception the tracker freezes where it was; the red flag is
        // shown alongside rather than pretending progress continued.
        $reached = self::reached($status) ?? self::reachedFromTimestamps($timestamps);

        $steps = [];
        $i = 0;
        foreach (self::STEPS as $key => $label) {
            $i++;
            $steps[] = [
                'key' => $key,
                'label' => $label,
                'done' => $i <= $reached,
                'at' => $timestamps[$key] ?? null,
            ];
        }

        return ['steps' => $steps, 'exception' => $exception];
    }

    /**
     * Fallback for an unmapped status: infer progress from the steps that have
     * actually been timestamped, so the customer keeps the furthest point the
     * order genuinely reached.
     */
    private static function reachedFromTimestamps(array $timestamps): int
    {
        $reached = 0;
        $i = 0;

        foreach (array_keys(self::STEPS) as $key) {
            $i++;
            if (! empty($timestamps[$key])) {
                $reached = $i;
            }
        }

        return $reached;
    }

    /**
     * What a customer would SEE happen if a publication were moved to this
     * status — or null when the status is invisible to them.
     *
     * Used to warn Martina before a change reaches a customer. It names the
     * consequence rather than warning in the abstract, because the internal
     * and customer vocabularies are deliberately offset: "Waiting Copywriter"
     * is what completes "Publisher Confirmation", and a warning that did not
     * say so would be easy to misread.
     */
    public static function customerImpact(?string $status): ?string
    {
        if ($exception = self::exception($status)) {
            return 'a red "'.$exception.'" notice';
        }

        $key = self::stepKeyFor($status);

        return $key === null ? null : '"'.self::STEPS[$key].'" marked complete';
    }

    /**
     * Every customer-visible status => its consequence, for the front-end.
     *
     * Built from the same two tables the tracker itself reads, so a status can
     * never be added to one and forgotten in the other.
     *
     * @return array<string, string>
     */
    public static function impactMap(): array
    {
        $map = [];

        foreach (array_merge(array_keys(self::REACHED), array_keys(self::EXCEPTIONS)) as $slug) {
            $map[$slug] = self::customerImpact($slug);
        }

        return $map;
    }

    /** Which customer step a status completes, as a step key (for logging). */
    public static function stepKeyFor(?string $status): ?string
    {
        $reached = self::reached($status);

        if ($reached === null || $reached < 1) {
            return null;
        }

        return array_keys(self::STEPS)[$reached - 1] ?? null;
    }
}
