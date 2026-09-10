<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The token hold on a single ordered site.
 *
 * Money is committed PER SITE, not per order: one publisher refusing out of
 * five must release only its own tokens and leave the rest of the order
 * running (Fabrizio, 2026-09-09).
 *
 * `tokens_held` is frozen at the moment of the hold and is NOT re-derived from
 * unit_price later. A publisher renegotiating a price, or a catalogue update,
 * must never silently change what a customer already committed.
 *
 * The three timestamps ARE the state machine, so there is no status column to
 * fall out of step with them:
 *
 *   held_at null                         -> nothing committed
 *   held_at set, other two null          -> tokens held, order in flight
 *   captured_at set                      -> published, tokens became revenue
 *   released_at set                      -> the site fell through, tokens back
 *
 * captured_at and released_at are mutually exclusive by construction: the
 * ledger's idempotency keys make a second terminal write a no-op.
 *
 * ADDITIVE ONLY. Every column is nullable with no default backfill, so the
 * 17 existing order_items keep NULL and behave exactly as they do today.
 *
 * Run this file ONLY, never a blanket migrate:
 *   php artisan migrate --pretend --force --path=database/migrations/2026_09_10_000001_add_token_holds_to_order_items_table.php
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedInteger('tokens_held')->nullable()->after('unit_price');
            $table->timestamp('held_at')->nullable()->after('tokens_held');
            $table->timestamp('captured_at')->nullable()->after('held_at');
            $table->timestamp('released_at')->nullable()->after('captured_at');

            // Why the tokens went back: publisher_refused, publisher_disappeared,
            // cancelled_by_client, admin. Reporting needs to tell a publisher
            // who haggled from one who vanished — they are different problems.
            $table->string('release_reason', 40)->nullable()->after('released_at');

            // Drives the sweep that finds live holds ("what is this account
            // currently committing?") without scanning every order ever placed.
            $table->index(['held_at', 'captured_at', 'released_at'], 'order_items_hold_state_index');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('order_items_hold_state_index');
            $table->dropColumn(['tokens_held', 'held_at', 'captured_at', 'released_at', 'release_reason']);
        });
    }
};
