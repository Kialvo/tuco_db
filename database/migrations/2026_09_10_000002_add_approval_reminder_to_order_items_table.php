<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When we last warned the client that their article is about to be published
 * without their reply.
 *
 * There is no "clear this on a new draft" logic, deliberately. The reminder is
 * due when this timestamp is NULL or OLDER than the moment the publication
 * last entered client approval — so a corrected article, which re-enters that
 * status and writes a fresh status event, makes the old reminder stale by
 * comparison and a new one due. One column, no bookkeeping to get wrong.
 *
 * ADDITIVE ONLY. Nullable, no default, no backfill: every existing row keeps
 * NULL, which correctly reads as "never reminded".
 *
 * Run this file ONLY, never a blanket migrate:
 *   php artisan migrate --pretend --force --path=database/migrations/2026_09_10_000002_add_approval_reminder_to_order_items_table.php
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->timestamp('approval_reminder_sent_at')->nullable()->after('release_reason');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('approval_reminder_sent_at');
        });
    }
};
