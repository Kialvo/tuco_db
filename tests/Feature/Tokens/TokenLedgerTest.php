<?php

namespace Tests\Feature\Tokens;

use App\Exceptions\InsufficientTokens;
use App\Models\TokenTransaction;
use App\Services\Tokens\TokenLedger;
use Illuminate\Support\Facades\DB;

/**
 * The ledger's guarantees: balances that add up, movements that cannot be
 * applied twice, and debits that refuse to overdraw.
 */
class TokenLedgerTest extends TokenTestCase
{
    private function ledger(): TokenLedger
    {
        return app(TokenLedger::class);
    }

    public function test_a_new_account_starts_empty(): void
    {
        $user = $this->makeUser();

        $this->assertSame(0, $this->ledger()->balance($user));
    }

    public function test_credit_increases_the_balance_and_writes_one_ledger_row(): void
    {
        $user = $this->makeUser();
        $account = $this->ledger()->accountFor($user);

        $tx = $this->ledger()->credit($account, 500, TokenTransaction::TYPE_PURCHASE, 'test:credit:1');

        $this->assertSame(500, $tx->amount);
        $this->assertSame(500, $tx->balance_after);
        $this->assertSame(500, (int) $account->fresh()->balance_cached);
        $this->assertSame(1, TokenTransaction::count());
    }

    public function test_debit_decreases_the_balance(): void
    {
        $user = $this->makeUser();
        $account = $this->ledger()->accountFor($user);
        $this->ledger()->credit($account, 500, TokenTransaction::TYPE_PURCHASE, 'test:credit:1');

        $tx = $this->ledger()->debit($account, 173, TokenTransaction::TYPE_SPEND, 'test:spend:1');

        $this->assertSame(-173, $tx->amount);
        $this->assertSame(327, $tx->balance_after);
        $this->assertSame(327, (int) $account->fresh()->balance_cached);
    }

    public function test_a_debit_larger_than_the_balance_is_refused(): void
    {
        $user = $this->makeUser();
        $account = $this->ledger()->accountFor($user);
        $this->ledger()->credit($account, 100, TokenTransaction::TYPE_PURCHASE, 'test:credit:1');

        try {
            $this->ledger()->debit($account, 101, TokenTransaction::TYPE_SPEND, 'test:spend:1');
            $this->fail('Expected InsufficientTokens.');
        } catch (InsufficientTokens $e) {
            $this->assertSame(1, $e->shortfall());
        }

        // The failed attempt must leave nothing behind.
        $this->assertSame(100, (int) $account->fresh()->balance_cached);
        $this->assertSame(1, TokenTransaction::count());
    }

    public function test_spending_the_exact_balance_is_allowed(): void
    {
        $user = $this->makeUser();
        $account = $this->ledger()->accountFor($user);
        $this->ledger()->credit($account, 250, TokenTransaction::TYPE_PURCHASE, 'test:credit:1');

        $this->ledger()->debit($account, 250, TokenTransaction::TYPE_SPEND, 'test:spend:1');

        $this->assertSame(0, (int) $account->fresh()->balance_cached);
    }

    /** The property the whole design rests on. */
    public function test_the_same_idempotency_key_moves_tokens_only_once(): void
    {
        $user = $this->makeUser();
        $account = $this->ledger()->accountFor($user);

        $first = $this->ledger()->credit($account, 500, TokenTransaction::TYPE_PURCHASE, 'purchase:99:tokens');
        $again = $this->ledger()->credit($account, 500, TokenTransaction::TYPE_PURCHASE, 'purchase:99:tokens');
        $third = $this->ledger()->credit($account, 500, TokenTransaction::TYPE_PURCHASE, 'purchase:99:tokens');

        $this->assertSame($first->id, $again->id);
        $this->assertSame($first->id, $third->id);
        $this->assertSame(500, (int) $account->fresh()->balance_cached);
        $this->assertSame(1, TokenTransaction::count());
    }

    public function test_idempotency_also_protects_debits(): void
    {
        $user = $this->makeUser();
        $account = $this->ledger()->accountFor($user);
        $this->ledger()->credit($account, 500, TokenTransaction::TYPE_PURCHASE, 'test:credit:1');

        $this->ledger()->debit($account, 200, TokenTransaction::TYPE_SPEND, 'order:7:spend');
        $this->ledger()->debit($account, 200, TokenTransaction::TYPE_SPEND, 'order:7:spend');

        $this->assertSame(300, (int) $account->fresh()->balance_cached);
    }

    public function test_the_database_itself_rejects_a_duplicate_key(): void
    {
        $user = $this->makeUser();
        $account = $this->ledger()->accountFor($user);
        $this->ledger()->credit($account, 10, TokenTransaction::TYPE_PURCHASE, 'dupe');

        // Bypassing the service must still be impossible: the guarantee is a
        // UNIQUE index, not merely a check in PHP.
        $this->expectException(\Illuminate\Database\QueryException::class);

        TokenTransaction::create([
            'token_account_id' => $account->id,
            'type' => TokenTransaction::TYPE_PURCHASE,
            'amount' => 10,
            'balance_after' => 20,
            'idempotency_key' => 'dupe',
        ]);
    }

    public function test_cached_balance_always_equals_the_ledger(): void
    {
        $user = $this->makeUser();
        $account = $this->ledger()->accountFor($user);

        $this->ledger()->credit($account, 1000, TokenTransaction::TYPE_PURCHASE, 'k1');
        $this->ledger()->credit($account, 50, TokenTransaction::TYPE_BONUS, 'k2');
        $this->ledger()->debit($account, 527, TokenTransaction::TYPE_SPEND, 'k3');
        $this->ledger()->debit($account, 100, TokenTransaction::TYPE_SPEND, 'k4');
        $this->ledger()->credit($account, 100, TokenTransaction::TYPE_REFUND, 'k5');

        $account = $account->fresh();

        $this->assertSame(523, (int) $account->balance_cached);
        $this->assertSame(523, $account->derivedBalance());
        $this->assertFalse($account->hasDrifted());
    }

    public function test_balance_after_tells_the_story_of_the_account(): void
    {
        $user = $this->makeUser();
        $account = $this->ledger()->accountFor($user);

        $this->ledger()->credit($account, 300, TokenTransaction::TYPE_PURCHASE, 'a');
        $this->ledger()->debit($account, 100, TokenTransaction::TYPE_SPEND, 'b');
        $this->ledger()->credit($account, 50, TokenTransaction::TYPE_REFUND, 'c');

        $this->assertSame(
            [300, 200, 250],
            TokenTransaction::orderBy('id')->pluck('balance_after')->map(fn ($v) => (int) $v)->all()
        );
    }

    public function test_zero_and_negative_amounts_are_rejected_outright(): void
    {
        $user = $this->makeUser();
        $account = $this->ledger()->accountFor($user);

        foreach ([0, -5] as $bad) {
            try {
                $this->ledger()->credit($account, $bad, TokenTransaction::TYPE_PURCHASE, 'bad:credit:'.$bad);
                $this->fail("credit() accepted {$bad}");
            } catch (\InvalidArgumentException) {
            }

            try {
                $this->ledger()->debit($account, $bad, TokenTransaction::TYPE_SPEND, 'bad:debit:'.$bad);
                $this->fail("debit() accepted {$bad}");
            } catch (\InvalidArgumentException) {
            }
        }

        $this->assertSame(0, TokenTransaction::count());
    }

    public function test_a_failed_debit_rolls_back_completely(): void
    {
        $user = $this->makeUser();
        $account = $this->ledger()->accountFor($user);
        $this->ledger()->credit($account, 100, TokenTransaction::TYPE_PURCHASE, 'seed');

        $before = DB::table('token_transactions')->count();

        try {
            $this->ledger()->debit($account, 5000, TokenTransaction::TYPE_SPEND, 'too-big');
        } catch (InsufficientTokens) {
        }

        $this->assertSame($before, DB::table('token_transactions')->count());
        $this->assertSame(100, (int) $account->fresh()->balance_cached);
    }

    public function test_accounts_are_isolated_from_each_other(): void
    {
        $a = $this->ledger()->accountFor($this->makeUser());
        $b = $this->ledger()->accountFor($this->makeUser());

        $this->ledger()->credit($a, 500, TokenTransaction::TYPE_PURCHASE, 'a:1');
        $this->ledger()->credit($b, 900, TokenTransaction::TYPE_PURCHASE, 'b:1');
        $this->ledger()->debit($a, 200, TokenTransaction::TYPE_SPEND, 'a:2');

        $this->assertSame(300, (int) $a->fresh()->balance_cached);
        $this->assertSame(900, (int) $b->fresh()->balance_cached);
    }
}
