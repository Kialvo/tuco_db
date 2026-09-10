<?php

/*
|--------------------------------------------------------------------------
| Token / credit system
|--------------------------------------------------------------------------
| A token is a unit of prepaid credit pegged to the euro. The peg NEVER
| moves: 1 token = EUR 1, always, and balances are stored as integers.
|
| Currency appears in exactly two places and neither of them is the ledger:
|   - what we CHARGE (a package can be sold in USD, GBP, ...)
|   - what we DISPLAY (an indicative conversion of the euro balance)
| Redemption never touches an exchange rate: a EUR 527 placement costs 527
| tokens whatever currency the customer is looking at.
|
| NO SECRETS IN THIS FILE. Gateway credentials live in config/services.php
| and are read from the environment.
*/

return [

    /*
    |----------------------------------------------------------------------
    | The peg
    |----------------------------------------------------------------------
    | Declared rather than hardcoded so it is greppable and testable, but it
    | is NOT a setting to change: altering it would silently revalue every
    | historical balance and make past orders unauditable.
    */
    'currency' => 'EUR',
    'tokens_per_unit' => 1,          // 1 token = 1 EUR

    /*
    |----------------------------------------------------------------------
    | Payment gateway driver
    |----------------------------------------------------------------------
    | 'fake'   — no network, no credentials, deterministic. Local + tests.
    | 'stripe' — real Stripe Checkout. Requires services.stripe.* and the
    |            stripe/stripe-php package (added in the Stripe phase).
    |
    | Defaults to 'fake' so a missing PAYMENTS_DRIVER can never accidentally
    | put a half-configured live gateway in front of customers.
    */
    'driver' => env('PAYMENTS_DRIVER', 'fake'),

    /*
    |----------------------------------------------------------------------
    | Does placing an order SPEND tokens?
    |----------------------------------------------------------------------
    | The kill switch for the whole money-out half of the system.
    |
    | OFF (the default): submitting an order behaves exactly as it always
    | has — no balance is checked, no tokens are held, and the quote flow is
    | untouched. The code can therefore ship to production and sit dormant.
    |
    | ON: an order holds its tokens per site at submission, and a guest with
    | no balance CANNOT place one. That is a deliberate, announced change to
    | how the marketplace works — never something a deploy should switch on
    | by accident, which is why this defaults to false rather than true.
    |
    | Existing holds settle either way: turning this off stops NEW holds
    | being taken, it does not strand money already committed.
    */
    'spending_enabled' => env('TOKENS_SPENDING_ENABLED', false),

    /*
    |----------------------------------------------------------------------
    | Packages
    |----------------------------------------------------------------------
    | `tokens` is what the customer receives; `bonus` is the discount,
    | expressed as extra tokens rather than a cheaper rate so the peg stays
    | intact and the accounting stays simple.
    |
    | Prices are per-currency and EXPLICIT — never computed from a live rate
    | at checkout. A published price does not move while a customer is
    | deciding, and the small FX drift is absorbed deliberately. The USD
    | figures sit slightly above a pure conversion to cover Stripe's
    | cross-currency settlement fee.
    |
    | prices are in MINOR units (cents) to keep money away from floats.
    */
    'packages' => [
        'starter' => [
            'tokens' => 250,
            'bonus' => 0,
            'prices' => ['EUR' => 25000, 'USD' => 29500],
        ],
        'standard' => [
            'tokens' => 500,
            'bonus' => 15,
            'prices' => ['EUR' => 50000, 'USD' => 59000],
        ],
        'pro' => [
            'tokens' => 1000,
            'bonus' => 50,
            'prices' => ['EUR' => 100000, 'USD' => 117500],
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | Custom amount
    |----------------------------------------------------------------------
    | Replaces the old fixed 2,500 tier. A placement can cost EUR 130, so a
    | EUR 250 minimum was pushing people into buying more credit than the job
    | needed — friction at exactly the wrong moment.
    |
    | The browser sends a TOKEN COUNT, never a price: the amount charged is
    | derived here, server-side. `multipliers` keeps non-euro pricing on a
    | published, periodically reviewed number rather than a live rate, so a
    | custom amount behaves the same way a package does — and the USD figure
    | sits above a pure conversion to cover cross-currency settlement.
    |
    | No bonus on custom amounts: the bonus is what the fixed packages are
    | FOR. See the nudge in the wallet view.
    */
    'custom' => [
        'min_tokens' => 50,
        'max_tokens' => 10000,
        'multipliers' => ['EUR' => 1.00, 'USD' => 1.175],
    ],

    /*
    |----------------------------------------------------------------------
    | Currencies a customer may PAY in. EUR must always be present: it is
    | the peg, and the only one guaranteed to need no conversion.
    |----------------------------------------------------------------------
    */
    'payment_currencies' => ['EUR', 'USD'],

    /*
    |----------------------------------------------------------------------
    | Currencies a balance may be DISPLAYED in, on top of the euro figure.
    | Purely cosmetic. When a rate is stale or unavailable the conversion is
    | HIDDEN rather than guessed — a missing figure is fine, a wrong one
    | costs trust in the balance itself.
    |----------------------------------------------------------------------
    */
    'display_currencies' => ['EUR', 'USD', 'GBP'],

    'rate_max_age_minutes' => 1440,   // older than this -> hide the conversion
];
