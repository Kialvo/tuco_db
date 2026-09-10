<?php

namespace App\Services\Tokens;

use App\Models\OrderItem;
use App\Models\Storage;
use Illuminate\Support\Facades\Log;

/**
 * Turns a publication's outcome into money.
 *
 * The rules, agreed 2026-09-09:
 *
 *   article_published      -> CAPTURE. The placement went live, so the held
 *                             tokens become revenue. This is the only thing
 *                             that earns them.
 *   publisher_refused      -> RELEASE. The publisher would not honour the
 *                             price and we never absorb an increase.
 *   publisher_disappeared  -> RELEASE. Never confirmed, or confirmed then
 *                             vanished — identical from the client's side.
 *
 * Everything else, including a client going quiet, moves nothing.
 *
 * Deliberately narrow: it acts only on a publication that is actually linked
 * to a marketplace order item with a live hold. Publications created by the
 * CRM or by the Monday import have no order behind them, so a bulk status
 * change there can never move a customer's tokens.
 *
 * Failures are logged at ERROR and swallowed. Martina saving a publication
 * must not fail because of bookkeeping — but a swallowed money error is only
 * acceptable because `tokens:reconcile` re-derives the ledger and reports
 * drift. Without that command this class would be hiding lost revenue.
 */
class OrderSettlement
{
    /** Publication statuses that settle a hold, and how. */
    private const OUTCOMES = [
        'article_published' => 'capture',
        'publisher_refused' => OrderItem::RELEASE_PUBLISHER_REFUSED,
        'publisher_disappeared' => OrderItem::RELEASE_PUBLISHER_DISAPPEARED,
    ];

    public function __construct(private readonly TokenLedger $ledger) {}

    /**
     * Settle whatever this publication's current status implies.
     *
     * Safe to call on every save: statuses that mean nothing financially, and
     * publications with no order behind them, return immediately.
     */
    public function settle(Storage $publication): void
    {
        $outcome = self::OUTCOMES[(string) $publication->status] ?? null;

        if ($outcome === null) {
            return;
        }

        try {
            $item = OrderItem::with('order.user')
                ->where('storage_id', $publication->getKey())
                ->first();

            // A CRM or imported publication — no customer, no hold, nothing owed.
            if (! $item || ! $item->isHeld()) {
                return;
            }

            $user = $item->order?->user;

            if (! $user) {
                Log::error('[tokens] cannot settle order item '.$item->id.': the order has no user.');

                return;
            }

            $account = $this->ledger->accountFor($user);

            if ($outcome === 'capture') {
                $this->ledger->captureHold($account, $item);

                Log::info('[tokens] captured '.$item->tokens_held.' tokens for order item '.$item->id
                    .' (publication '.$publication->getKey().' published)');

                return;
            }

            $this->ledger->releaseHold($account, $item, $outcome);

            Log::info('[tokens] released '.$item->tokens_held.' tokens for order item '.$item->id
                .' (reason: '.$outcome.')');
        } catch (\Throwable $e) {
            // Loud, because this is money. Not fatal, because a publication
            // save must still succeed — tokens:reconcile is the safety net.
            Log::error('[tokens] SETTLEMENT FAILED for publication '.$publication->getKey()
                .' (status '.$publication->status.'): '.$e->getMessage());
        }
    }
}
