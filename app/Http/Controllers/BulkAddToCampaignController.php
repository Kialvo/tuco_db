<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Storage;
use App\Models\Website;
use App\Services\StorageCalculator;
use App\Support\PublicationStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * "Bulk Add to Campaign" — creates one publication (= `storage` row linked via
 * lb_campaign_id) per selected domain, from the Domains grid.
 *
 * Restricted to the `bulk-add-to-campaign` Gate (admin + allowlisted email;
 * see config/linkbuilding.php). The action lives on /websites, which guests
 * and editors can reach, so the endpoint is gated independently of the button.
 *
 * The Price is NEVER accepted from the client: the request carries only a
 * price TYPE, and the amount is read server-side from the domain record. That
 * makes an arbitrary revenue figure structurally impossible to submit, and it
 * is why the amount is read-only in the UI.
 */
class BulkAddToCampaignController extends Controller
{
    /** Hard cap on domains per operation (one popup row each). */
    private const MAX_ROWS = 50;

    /** price type => websites column holding the amount. */
    private const PRICE_COLUMNS = [
        'price' => 'price',
        'sensitive_topic_price' => 'sensitive_topic_price',
    ];

    private const PRICE_LABELS = [
        'price' => 'Price',
        'sensitive_topic_price' => 'Sensitive Topic Price',
    ];

    /**
     * `storage.menford` / `total_revenues` are decimal(8,2) — narrower than the
     * websites price columns, so an oversized domain price must be rejected
     * rather than silently truncated by MySQL.
     */
    private const MAX_AMOUNT = 999999.99;

    /*======================================================================
    |  PREVIEW — resolve prices + duplicates before anything is written.
    |  Called on modal open, on campaign change, and on any price-type change.
    ======================================================================*/
    public function preview(Request $request): JsonResponse
    {
        Gate::authorize('bulk-add-to-campaign');

        $data = $request->validate([
            'ids' => 'required|array|min:1|max:'.self::MAX_ROWS,
            'ids.*' => 'integer|distinct|exists:websites,id',
            'campaign_id' => ['nullable', 'integer', Rule::exists('lb_campaigns', 'id')->whereNull('deleted_at')],
            'price_types' => 'nullable|array',
            'price_types.*' => ['nullable', Rule::in(array_keys(self::PRICE_COLUMNS))],
        ]);

        $rows = $this->resolveRows(
            $data['ids'],
            $data['price_types'] ?? [],
            $data['campaign_id'] ?? null
        );

        return response()->json([
            'status' => 'success',
            'rows' => array_values($rows),
            'ready' => count(array_filter($rows, fn ($r) => ! $r['blocked'])),
            'blocked' => count(array_filter($rows, fn ($r) => $r['blocked'])),
        ]);
    }

    /*======================================================================
    |  STORE — create one storage row per non-blocked domain.
    |  Partial success by design: blocked domains are skipped and reported,
    |  the rest are created.
    ======================================================================*/
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('bulk-add-to-campaign');

        $data = $request->validate([
            // exists: does NOT honour soft deletes on its own — hence the whereNull.
            'campaign_id' => ['required', 'integer', Rule::exists('lb_campaigns', 'id')->whereNull('deleted_at')],
            'rows' => 'required|array|min:1|max:'.self::MAX_ROWS,
            'rows.*.website_id' => 'required|integer|distinct|exists:websites,id',
            'rows.*.status' => ['required', Rule::in(PublicationStatus::slugs())],
            'rows.*.price_type' => ['required', Rule::in(array_keys(self::PRICE_COLUMNS))],
        ]);

        $campaign = Campaign::findOrFail($data['campaign_id']);

        $statuses = [];
        $priceTypes = [];
        foreach ($data['rows'] as $row) {
            $statuses[$row['website_id']] = $row['status'];
            $priceTypes[$row['website_id']] = $row['price_type'];
        }

        // Re-resolve server-side: the duplicate/price situation may have changed
        // since the preview, and the amount is never taken from the client.
        $resolved = $this->resolveRows(array_keys($statuses), $priceTypes, $campaign->id);

        $created = 0;
        $skipped = [];

        DB::transaction(function () use ($resolved, $statuses, $campaign, &$created, &$skipped) {
            foreach ($resolved as $websiteId => $row) {
                if ($row['blocked']) {
                    $skipped[] = ['domain' => $row['domain_name'], 'reason' => $row['reason']];

                    continue;
                }

                $attrs = [
                    'website_id' => $websiteId,
                    'status' => $statuses[$websiteId],
                    'lb_campaign_id' => $campaign->id,
                    'campaign_code' => $campaign->code,
                    'country_id' => $row['country_id'],
                    'language_id' => $row['language_id'],
                ];

                // Derives menford/total_cost/total_revenues/profit — never write
                // total_revenues directly (see StorageCalculator).
                StorageCalculator::setPrice($attrs, (float) $row['amount']);

                Storage::create($attrs);
                $created++;
            }
        });

        return response()->json([
            'status' => 'success',
            'created' => $created,
            'skipped' => $skipped,
            'campaign' => $campaign->code,
        ]);
    }

    /*======================================================================
    |  Shared resolution — the ONE place prices and duplicates are decided.
    |  preview() and store() both call it so they can never disagree.
    |
    |  @return array<int, array> keyed by website id, in the requested order
    ======================================================================*/
    private function resolveRows(array $websiteIds, array $priceTypes, ?int $campaignId): array
    {
        $websites = Website::whereIn('id', $websiteIds)
            ->get(['id', 'domain_name', 'price', 'sensitive_topic_price', 'country_id', 'language_id'])
            ->keyBy('id');

        // Domains already published in this campaign. website_id only: legacy
        // rows linked by the free-text `website` column are ignored by design.
        // Storage's default scope excludes soft-deleted rows, so a soft-deleted
        // publication does NOT block re-adding the domain (as specified).
        $duplicates = $campaignId
            ? Storage::where('lb_campaign_id', $campaignId)
                ->whereIn('website_id', $websiteIds)
                ->pluck('website_id')
                ->all()
            : [];

        $rows = [];

        foreach ($websiteIds as $id) {
            $website = $websites->get($id);
            if (! $website) {
                continue;
            }

            $priceType = $priceTypes[$id] ?? 'price';
            if (! isset(self::PRICE_COLUMNS[$priceType])) {
                $priceType = 'price';
            }

            $amount = $website->{self::PRICE_COLUMNS[$priceType]};
            $amount = ($amount === null || $amount === '') ? null : (float) $amount;

            [$blocked, $reason] = $this->blockReason($id, $amount, $priceType, $duplicates, $campaignId);

            $rows[$id] = [
                'website_id' => $id,
                'domain_name' => $website->domain_name,
                'price' => $website->price !== null ? (float) $website->price : null,
                'sensitive_topic_price' => $website->sensitive_topic_price !== null ? (float) $website->sensitive_topic_price : null,
                'price_type' => $priceType,
                'amount' => $amount,
                'country_id' => $website->country_id,
                'language_id' => $website->language_id,
                'blocked' => $blocked,
                'reason' => $reason,
                'edit_url' => route('websites.edit', $id),
            ];
        }

        return $rows;
    }

    /** @return array{0: bool, 1: ?string} */
    private function blockReason(int $id, ?float $amount, string $priceType, array $duplicates, ?int $campaignId): array
    {
        // Missing price first: it is the actionable one (Martina fixes the
        // domain record), and it applies whether or not a campaign is chosen.
        if ($amount === null) {
            return [true, 'No '.self::PRICE_LABELS[$priceType].' set for this domain — add it on the domain record, then retry.'];
        }

        if ($amount > self::MAX_AMOUNT) {
            return [true, 'Price exceeds the maximum a publication can store ('.number_format(self::MAX_AMOUNT, 2).').'];
        }

        if ($campaignId && in_array($id, $duplicates)) {
            return [true, 'Already a publication in this campaign.'];
        }

        return [false, null];
    }
}
