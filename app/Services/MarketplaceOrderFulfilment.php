<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Order;
use App\Models\Storage;
use App\Support\CampaignCodeGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Turns a submitted marketplace order into work Martina can track.
 *
 * One campaign per order, one publication per ordered site, created at order
 * time — deliberately, so orders that never go anywhere are still visible
 * rather than vanishing (Simone, Monday 2026-08-27). Martina then works on them
 * exactly as she does on any other campaign, and the customer sees a translated
 * view of the status she sets.
 *
 * Everything here is non-fatal: a guest's order must succeed even if campaign
 * creation fails. A missing campaign is a support ticket; a failed order is
 * lost revenue.
 */
class MarketplaceOrderFulfilment
{
    /** Campaign service tag — already defined in config, previously unused. */
    public const SERVICE = 'LIAB Marketplace';

    /** Campaign status new marketplace campaigns open in. */
    public const CAMPAIGN_STATUS = 'Publishing';

    /** Publication status every ordered site starts at. */
    public const INITIAL_PUBLICATION_STATUS = 'waiting_blog_price_confirmation';

    public function __construct(private readonly CampaignCodeGenerator $codes) {}

    /**
     * Create the campaign and publications for an order.
     *
     * Idempotent: an order that already has a campaign is left alone, so a
     * retried submission or a double-click cannot produce duplicates.
     */
    public function fulfil(Order $order): ?Campaign
    {
        if ($order->lb_campaign_id) {
            return $order->campaign;
        }

        try {
            return DB::transaction(function () use ($order) {
                $campaign = $this->createCampaign($order);

                foreach ($order->items as $item) {
                    if ($item->storage_id) {
                        continue;   // already fulfilled
                    }

                    $publication = $this->createPublication($order, $campaign, $item);
                    $item->forceFill(['storage_id' => $publication->id])->save();
                }

                $order->forceFill(['lb_campaign_id' => $campaign->id])->save();
                $campaign->recomputeProgress();

                return $campaign;
            });
        } catch (\Throwable $e) {
            // Never break the customer's order over back-office bookkeeping.
            Log::error('[marketplace-fulfilment] order '.$order->id.' failed: '.$e->getMessage());

            return null;
        }
    }

    private function createCampaign(Order $order): Campaign
    {
        $siteCount = $order->items->count();

        return Campaign::create([
            'code' => $this->uniqueCode($order),
            // Guests have no company or contact on file yet — the service tag
            // is what identifies these until guest company registration exists.
            'company_id' => null,
            'contact_id' => null,
            'responsible_user_id' => $this->marketplaceOwnerId(),
            'service' => self::SERVICE,
            'status' => self::CAMPAIGN_STATUS,
            'deal_value' => (float) $order->total_amount,
            'target_type' => 'publications',
            'target_value' => $siteCount,
            'live_count' => 0,
        ]);
    }

    private function createPublication(Order $order, Campaign $campaign, $item): Storage
    {
        $website = $item->website;

        $attrs = [
            'website_id' => $item->website_id,
            'status' => self::INITIAL_PUBLICATION_STATUS,
            'lb_campaign_id' => $campaign->id,
            'campaign_code' => $campaign->code,
            'country_id' => $website?->country_id,
            'language_id' => $website?->language_id,
        ];

        // The price the customer actually agreed to, through the same
        // calculator the rest of the system uses — never written directly.
        StorageCalculator::setPrice($attrs, (float) $item->unit_price);

        return Storage::create($attrs);
    }

    /**
     * [4 LETTERS]_LIAB_[NNN] from the guest's name.
     *
     * The progressive is per prefix. If two different guests reduce to the same
     * four letters the SOP's 5th-consonant swap is tried first; if that cannot
     * help (or also collides) the shared prefix simply continues its numbering,
     * which stays unique even if it is no longer per-person.
     */
    private function uniqueCode(Order $order): string
    {
        $name = (string) ($order->user->name ?? '');
        $letters = $this->codes->letters($name);

        // Only try the variant when someone ELSE already owns this prefix.
        if ($this->prefixOwnedByAnotherUser($letters, $order)) {
            $variant = $this->codes->collisionVariant($name);
            if ($variant !== null && ! $this->prefixOwnedByAnotherUser($variant, $order)) {
                $letters = $variant;
            }
        }

        return $this->codes->format($letters, $this->nextSequence($letters));
    }

    private function nextSequence(string $letters): int
    {
        $prefix = strtoupper($letters).'_'.CampaignCodeGenerator::TAG.'_';

        $last = Campaign::withTrashed()
            ->where('code', 'like', $prefix.'%')
            ->orderByDesc('code')
            ->value('code');

        if ($last === null) {
            return 1;
        }

        return ((int) substr($last, strlen($prefix))) + 1;
    }

    /** True when this prefix already belongs to a different guest. */
    private function prefixOwnedByAnotherUser(string $letters, Order $order): bool
    {
        $prefix = strtoupper($letters).'_'.CampaignCodeGenerator::TAG.'_';

        return Campaign::withTrashed()
            ->where('code', 'like', $prefix.'%')
            ->whereHas('marketplaceOrders', fn ($q) => $q->where('user_id', '!=', $order->user_id))
            ->exists();
    }

    /** Martina owns marketplace campaigns; falls back to nobody if not found. */
    private function marketplaceOwnerId(): ?int
    {
        $email = config('linkbuilding.marketplace_campaign_owner_email');

        if (! $email) {
            return null;
        }

        return \App\Models\User::where('email', $email)->value('id');
    }
}
