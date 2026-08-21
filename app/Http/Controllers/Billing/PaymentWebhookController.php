<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\PaymentEvent;
use App\Services\Payments\InvalidWebhookSignature;
use App\Services\Payments\PaymentGateway;
use App\Services\Tokens\TokenPurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Where the gateway tells us money moved. This is the ONLY place tokens are
 * credited from a payment — never the browser's success redirect, which proves
 * nothing and can be visited by hand.
 *
 * Order of operations matters:
 *   1. verify the signature   (an endpoint that trusts its body is a free-tokens API)
 *   2. record the event       (UNIQUE event_id makes a retry a no-op)
 *   3. apply it               (ledger keys make double-crediting impossible anyway)
 *
 * Always answers 200 for anything already handled, so the gateway stops
 * retrying a delivery we have accepted.
 */
class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentGateway $gateway, TokenPurchaseService $purchases)
    {
        try {
            $event = $gateway->parseWebhook($request->getContent(), $request->headers->all());
        } catch (InvalidWebhookSignature $e) {
            // Deliberately terse: never echo the payload or the reason back.
            Log::warning('[payments] rejected webhook: '.$e->getMessage());

            return response()->json(['error' => 'invalid signature'], 400);
        }

        $record = PaymentEvent::firstOrCreate(
            ['event_id' => $event->id],
            ['gateway' => $gateway->name(), 'type' => $event->type, 'payload' => $event->payload],
        );

        if ($record->processed_at !== null) {
            return response()->json(['status' => 'already processed']);
        }

        try {
            $purchases->applyEvent($event);
            $record->forceFill(['processed_at' => now()])->save();
        } catch (\Throwable $e) {
            // Keep the row so the failure is visible, but leave processed_at
            // null so a replay can retry it once the cause is fixed.
            $record->forceFill(['processing_error' => mb_substr($e->getMessage(), 0, 255)])->save();
            Log::error('[payments] webhook processing failed: '.$e->getMessage());

            return response()->json(['error' => 'processing failed'], 500);
        }

        return response()->json(['status' => 'ok']);
    }
}
