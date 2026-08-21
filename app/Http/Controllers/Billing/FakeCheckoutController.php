<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\TokenPurchase;
use App\Services\Payments\FakeGateway;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * A stand-in for the hosted checkout page, so the whole top-up flow can be
 * clicked through with no Stripe account and no money.
 *
 * It deliberately goes the long way round: pressing "Pay" does not credit
 * anything directly, it POSTs a properly signed webhook to the real webhook
 * route. That means local testing exercises signature verification, event
 * de-duplication and the ledger keys — the parts that are expensive to get
 * wrong in production — rather than a shortcut that skips all three.
 *
 * Hard-refuses to run unless the fake driver is active, so it can never be
 * reachable on a deployment wired to a real gateway.
 */
class FakeCheckoutController extends Controller
{
    public function __construct(private readonly PaymentGateway $gateway)
    {
        abort_unless($this->gateway instanceof FakeGateway, 404);
    }

    public function show(string $session)
    {
        $purchase = TokenPurchase::where('gateway_session_id', $session)->firstOrFail();

        abort_unless($purchase->user_id === request()->user()?->id, 403);

        return view('billing.fake-checkout', ['purchase' => $purchase, 'session' => $session]);
    }

    public function pay(Request $request, string $session)
    {
        return $this->deliver($request, $session, WebhookEvent::PAYMENT_SUCCEEDED);
    }

    public function fail(Request $request, string $session)
    {
        return $this->deliver($request, $session, WebhookEvent::PAYMENT_FAILED);
    }

    public function refund(Request $request, string $session)
    {
        return $this->deliver($request, $session, WebhookEvent::PAYMENT_REFUNDED);
    }

    /** Send ourselves a signed webhook, exactly as a real gateway would. */
    private function deliver(Request $request, string $session, string $type)
    {
        $purchase = TokenPurchase::where('gateway_session_id', $session)->firstOrFail();
        abort_unless($purchase->user_id === $request->user()?->id, 403);

        /** @var FakeGateway $gateway */
        $gateway = $this->gateway;
        [$payload, $headers] = $gateway->makeSignedWebhook($type, $session);

        $response = Http::withHeaders($headers)
            ->withBody($payload, 'application/json')
            ->post(route('billing.webhook', ['gateway' => 'fake']));

        $status = $response->successful() ? 'success' : 'failed';

        return redirect()
            ->route('billing.tokens.index', ['status' => $type === WebhookEvent::PAYMENT_SUCCEEDED ? $status : 'cancelled'])
            ->with('status', match ($type) {
                WebhookEvent::PAYMENT_SUCCEEDED => $response->successful()
                    ? 'Payment simulated — tokens credited.'
                    : 'Webhook was rejected: '.$response->status(),
                WebhookEvent::PAYMENT_FAILED => 'Payment failure simulated — nothing credited.',
                default => 'Refund simulated.',
            });
    }
}
