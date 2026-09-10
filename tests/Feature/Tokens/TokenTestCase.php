<?php

namespace Tests\Feature\Tokens;

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Base case for the token system.
 *
 * Deliberately does NOT use RefreshDatabase. That trait runs migrate:fresh
 * over every migration, and this project's from-scratch chain is documented as
 * broken (a column literally named "(AS)"), so the whole suite would die in
 * setUp before reaching a single assertion.
 *
 * Instead: build the one table the token schema depends on (`users`), then run
 * ONLY the token migrations by path. That exercises the real migration files —
 * the ones that will run in production — while staying entirely inside the
 * in-memory SQLite database that phpunit.xml and Tests\TestCase enforce.
 */
abstract class TokenTestCase extends TestCase
{
    /** The migrations under test, in order. */
    private const TOKEN_MIGRATIONS = [
        'database/migrations/2026_08_11_000001_create_token_accounts_table.php',
        'database/migrations/2026_08_11_000002_create_token_transactions_table.php',
        'database/migrations/2026_08_11_000003_create_token_purchases_table.php',
        'database/migrations/2026_08_11_000004_create_payment_events_table.php',
        // Wallets belong to teams, so every token test needs them.
        'database/migrations/2026_09_10_000003_create_marketplace_teams_tables.php',
        'database/migrations/2026_09_10_000004_move_token_accounts_to_teams.php',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // SQLite ignores foreign keys unless asked; we want them enforced so a
        // broken constraint fails here rather than in production.
        if (config('database.default') === 'sqlite') {
            \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = ON');
        }

        $this->createUsersTable();

        foreach (self::TOKEN_MIGRATIONS as $path) {
            $this->artisan('migrate', ['--path' => $path, '--realpath' => false]);
        }
    }

    /**
     * A minimal `users` table — just enough for the foreign keys. Using the
     * real users migration would drag in the rest of the broken chain.
     */
    private function createUsersTable(): void
    {
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('role')->default('guest');
            $table->timestamps();
        });
    }

    protected function makeUser(array $attributes = []): User
    {
        static $n = 0;
        $n++;

        return User::create(array_merge([
            'name' => 'Test User '.$n,
            'email' => 'user'.$n.'@example.test',
            'password' => 'x',
            'role' => 'guest',
        ], $attributes));
    }
}
