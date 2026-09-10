<?php

namespace App\Services\Tokens;

use App\Models\User;

/**
 * The single answer to "does placing an order cost this person tokens?"
 *
 * One place rather than a config() call scattered through the controller and
 * the cart payload: the two must never disagree, or someone sees a balance
 * breakdown for an order that will not be charged, or worse, is blocked by a
 * client-side gate the server does not enforce.
 *
 * Two ways to be true:
 *
 *   1. The global switch is on — the marketplace is prepaid for everybody.
 *   2. The account is on the test allowlist — spending applies to them alone
 *      while everyone else keeps today's behaviour. That is what makes it
 *      possible to exercise holds and captures against real production data
 *      without blocking a single customer.
 *
 * The allowlist only ever ADDS accounts. It cannot exclude anyone once the
 * global switch is on, because a kill switch some accounts ignore is not one.
 */
class SpendingPolicy
{
    public static function appliesTo(?User $user): bool
    {
        if (config('tokens.spending_enabled')) {
            return true;
        }

        if (! $user || ! $user->email) {
            return false;
        }

        return in_array(
            mb_strtolower($user->email),
            (array) config('tokens.spending_test_users', []),
            true,
        );
    }

    /** True when spending is live for everyone, not just the allowlist. */
    public static function enabledGlobally(): bool
    {
        return (bool) config('tokens.spending_enabled');
    }
}
