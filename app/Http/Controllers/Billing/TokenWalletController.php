<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\TokenTransaction;
use App\Services\Tokens\RateProvider;
use App\Services\Tokens\TokenLedger;
use App\Services\Tokens\TokenPurchaseService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The customer's wallet: balance, packages, and the ledger rendered as a
 * statement.
 */
class TokenWalletController extends Controller
{
    public function __construct(
        private readonly TokenLedger $ledger,
        private readonly RateProvider $rates,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $account = $this->ledger->accountFor($user);

        $balance = (int) $account->balance_cached;

        // Tokens committed to placements still in flight. They are ALREADY
        // debited out of the balance above, so this is not a subtraction — it
        // is the explanation for why the figure is lower than the last top-up
        // implies. An agency with a big order running would otherwise read its
        // balance as money that went missing.
        $held = $this->ledger->heldTotal($account);

        // Display currency is a preference, never part of the balance itself.
        $display = strtoupper((string) $request->query('display', 'EUR'));
        if (! in_array($display, config('tokens.display_currencies', []), true)) {
            $display = 'EUR';
        }

        $converted = $display === 'EUR' ? null : $this->rates->rate($display);

        return view('billing.wallet', [
            'balance' => $balance,
            'held' => $held,
            'packages' => config('tokens.packages'),
            'paymentCurrencies' => config('tokens.payment_currencies'),
            'displayCurrencies' => config('tokens.display_currencies'),
            'display' => $display,
            'converted' => $converted,
            'transactions' => $account->transactions()->latest('id')->limit(50)->get(),
            'isFakeGateway' => config('tokens.driver') === 'fake',
            'customMin' => (int) config('tokens.custom.min_tokens', 50),
            'customMax' => (int) config('tokens.custom.max_tokens', 10000),
            'customMultipliers' => config('tokens.custom.multipliers', []),
            // Built here rather than inline in the view: @json() cannot parse a
            // multi-line closure containing array literals — it balances
            // brackets and mis-reads the ']' as closing the directive.
            'nudgePackages' => collect(config('tokens.packages', []))
                ->map(fn ($p, $k) => [
                    'euros' => ($p['prices']['EUR'] ?? 0) / 100,
                    'total' => ($p['tokens'] ?? 0) + ($p['bonus'] ?? 0),
                    'label' => ucfirst($k),
                ])
                ->values()
                ->all(),
        ]);
    }

    /**
     * Open a checkout. The price is resolved server-side from the package
     * definition — the browser sends a package key, never an amount.
     */
    public function checkout(Request $request, TokenPurchaseService $purchases)
    {
        $packageKeys = array_keys(config('tokens.packages', []));

        $data = $request->validate([
            'package' => ['required', Rule::in([...$packageKeys, 'custom'])],
            'currency' => ['required', Rule::in(config('tokens.payment_currencies', []))],
            // Only meaningful for a custom amount. Bounds are enforced again in
            // the service, so a crafted request cannot slip past this.
            'tokens' => [
                'required_if:package,custom',
                'integer',
                'min:'.(int) config('tokens.custom.min_tokens', 50),
                'max:'.(int) config('tokens.custom.max_tokens', 10000),
            ],
        ], [
            'tokens.min' => 'The minimum top-up is :min tokens (€:min).',
            'tokens.max' => 'The maximum top-up is :max tokens in one go.',
            'tokens.integer' => 'Enter a whole number of tokens — 1 token = €1.',
        ]);

        $success = route('billing.tokens.index').'?status=success';
        $cancel = route('billing.tokens.index').'?status=cancelled';

        [, $session] = $data['package'] === 'custom'
            ? $purchases->startCustomPurchase($request->user(), (int) $data['tokens'], $data['currency'], $success, $cancel)
            : $purchases->startPurchase($request->user(), $data['package'], $data['currency'], $success, $cancel);

        return redirect()->away($session->url);
    }

    /** Human label for a ledger row. */
    public static function describe(TokenTransaction $tx): string
    {
        return match ($tx->type) {
            TokenTransaction::TYPE_PURCHASE => 'Token purchase',
            TokenTransaction::TYPE_BONUS => 'Bonus tokens',
            TokenTransaction::TYPE_SPEND => 'Order placement',
            TokenTransaction::TYPE_REFUND => 'Refund',
            TokenTransaction::TYPE_ADJUSTMENT => 'Manual adjustment',
            TokenTransaction::TYPE_EXPIRY => 'Expiry',
            default => ucfirst($tx->type),
        };
    }
}
