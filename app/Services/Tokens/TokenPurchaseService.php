<?php

namespace App\Services\Tokens;

use App\Models\TokenPurchase;
use App\Models\TokenTransaction;
use App\Models\User;
use App\Services\Payments\CheckoutSession;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\WebhookEvent;
use Illuminate\Support\Facades\DB;

/**
 * Buying tokens: create the purchase, open a checkout, and credit the account
 * when — and only when — the gateway confirms payment.
 *
 * Two rules this class exists to enforce:
 *
 *  1. The PRICE is computed here, server-side, from config('tokens.packages').
 *     An amount arriving from the browser is never trusted.
 *
 *  2. The TOKENS credited come from the package definition, never from
 *     converting the money received. Two customers buying the same package
 *     get the same tokens whatever the exchange rate did in between.
 */
class TokenPurchaseService
{
    public function __construct(
        private readonly TokenLedger $ledger,
        private readonly PaymentGateway $gateway,
    ) {}

    /**
     * Start a top-up. Returns the pending purchase and where to send the user.
     *
     * @return array{0: TokenPurchase, 1: CheckoutSession}
     */
    public function startPurchase(User $user, string $packageKey, string $currency, string $successUrl, string $cancelUrl): array
    {
        $package = $this->package($packageKey);
        $currency = strtoupper($currency);

        if (! in_array($currency, config('tokens.payment_currencies', []), true)) {
            throw new \InvalidArgumentException("Currency [{$currency}] is not accepted.");
        }

        $amountMinor = $package['prices'][$currency] ?? null;

        if ($amountMinor === null) {
            throw new \InvalidArgumentException("Package [{$packageKey}] has no price in {$currency}.");
        }

        $account = $this->ledger->accountFor($user);

        $purchase = TokenPurchase::create([
            'user_id' => $user->id,
            'token_account_id' => $account->id,
            'package_key' => $packageKey,
            'tokens' => $package['tokens'],
            'bonus_tokens' => $package['bonus'] ?? 0,
            'amount_minor' => $amountMinor,
            'currency' => $currency,
            'gateway' => $this->gateway->name(),
            'status' => TokenPurchase::STATUS_PENDING,
        ]);

        $session = $this->gateway->createCheckoutSession($purchase, $successUrl, $cancelUrl);

        $purchase->forceFill(['gateway_session_id' => $session->id])->save();

        return [$purchase, $session];
    }

    /**
     * Start a top-up for a customer-chosen number of tokens.
     *
     * The caller supplies a token COUNT, never a price. The amount charged is
     * derived here from config, exactly as it is for a fixed package, so the
     * browser still cannot influence what anything costs.
     *
     * @return array{0: TokenPurchase, 1: CheckoutSession}
     */
    public function startCustomPurchase(User $user, int $tokens, string $currency, string $successUrl, string $cancelUrl): array
    {
        $currency = strtoupper($currency);
        $min = (int) config('tokens.custom.min_tokens', 50);
        $max = (int) config('tokens.custom.max_tokens', 10000);

        if ($tokens < $min || $tokens > $max) {
            throw new \InvalidArgumentException("Custom amount must be between {$min} and {$max} tokens.");
        }

        if (! in_array($currency, config('tokens.payment_currencies', []), true)) {
            throw new \InvalidArgumentException("Currency [{$currency}] is not accepted.");
        }

        $multiplier = config('tokens.custom.multipliers.'.$currency);

        if ($multiplier === null) {
            throw new \InvalidArgumentException("No custom-amount rate configured for {$currency}.");
        }

        // 1 token = 1 EUR, so the euro amount IS the token count; other
        // currencies apply the published multiplier. Rounded to whole minor
        // units — money never touches a float beyond this line.
        $amountMinor = (int) round($tokens * 100 * (float) $multiplier);

        $account = $this->ledger->accountFor($user);

        $purchase = TokenPurchase::create([
            'user_id' => $user->id,
            'token_account_id' => $account->id,
            'package_key' => 'custom',
            'tokens' => $tokens,
            'bonus_tokens' => 0,
            'amount_minor' => $amountMinor,
            'currency' => $currency,
            'gateway' => $this->gateway->name(),
            'status' => TokenPurchase::STATUS_PENDING,
        ]);

        $session = $this->gateway->createCheckoutSession($purchase, $successUrl, $cancelUrl);

        $purchase->forceFill(['gateway_session_id' => $session->id])->save();

        return [$purchase, $session];
    }

    /**
     * Apply a verified gateway event.
     *
     * Safe to call repeatedly with the same event: the ledger key is derived
     * from the purchase, so a retried delivery credits nothing further.
     */
    public function applyEvent(WebhookEvent $event): ?TokenPurchase
    {
        if ($event->sessionId === null) {
            return null;
        }

        $purchase = TokenPurchase::where('gateway_session_id', $event->sessionId)->first();

        if (! $purchase) {
            return null;
        }

        return match ($event->type) {
            WebhookEvent::PAYMENT_SUCCEEDED => $this->markPaid($purchase, $event),
            WebhookEvent::PAYMENT_FAILED => $this->markFailed($purchase),
            WebhookEvent::PAYMENT_REFUNDED => $this->markRefunded($purchase),
            default => $purchase,
        };
    }

    private function markPaid(TokenPurchase $purchase, WebhookEvent $event): TokenPurchase
    {
        return DB::transaction(function () use ($purchase, $event) {
            $account = $purchase->account;

            // Base tokens and the discount are separate ledger rows so a
            // statement shows what was bought and what was given.
            $this->ledger->credit(
                $account,
                (int) $purchase->tokens,
                TokenTransaction::TYPE_PURCHASE,
                'purchase:'.$purchase->id.':tokens',
                $purchase,
                ['package' => $purchase->package_key, 'currency' => $purchase->currency],
            );

            if ((int) $purchase->bonus_tokens > 0) {
                $this->ledger->credit(
                    $account,
                    (int) $purchase->bonus_tokens,
                    TokenTransaction::TYPE_BONUS,
                    'purchase:'.$purchase->id.':bonus',
                    $purchase,
                    ['package' => $purchase->package_key],
                );
            }

            if ($purchase->status !== TokenPurchase::STATUS_PAID) {
                $purchase->forceFill([
                    'status' => TokenPurchase::STATUS_PAID,
                    'gateway_payment_id' => $event->paymentId,
                    'paid_at' => now(),
                ])->save();
            }

            return $purchase->refresh();
        });
    }

    private function markFailed(TokenPurchase $purchase): TokenPurchase
    {
        // Never downgrade a paid purchase: a late "failed" for an already
        // settled payment must not strip a customer's tokens.
        if ($purchase->status === TokenPurchase::STATUS_PENDING) {
            $purchase->forceFill(['status' => TokenPurchase::STATUS_FAILED])->save();
        }

        return $purchase;
    }

    private function markRefunded(TokenPurchase $purchase): TokenPurchase
    {
        return DB::transaction(function () use ($purchase) {
            if ($purchase->status !== TokenPurchase::STATUS_PAID) {
                return $purchase;
            }

            // Reverse exactly what was granted. This can push the balance
            // negative-by-intent, so it is a debit and will throw if the
            // customer has already spent the tokens — a deliberate signal
            // that a human has to decide what happens next.
            $this->ledger->debit(
                $purchase->account,
                $purchase->totalTokens(),
                TokenTransaction::TYPE_REFUND,
                'purchase:'.$purchase->id.':reversal',
                $purchase,
            );

            $purchase->forceFill(['status' => TokenPurchase::STATUS_REFUNDED])->save();

            return $purchase->refresh();
        });
    }

    private function package(string $key): array
    {
        $package = config('tokens.packages.'.$key);

        if (! is_array($package)) {
            throw new \InvalidArgumentException("Unknown token package [{$key}].");
        }

        return $package;
    }
}
