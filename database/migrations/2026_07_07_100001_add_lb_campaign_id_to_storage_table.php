<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — Campaigns ↔ Storage link.
 * Additive only: a nullable FK on `storage` pointing at `lb_campaigns`.
 * The legacy free-text `campaign_code` column is kept untouched (display/mirror only).
 * Also drops the accidental `'0'` default on `storage.status` (affects future inserts only).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storage', function (Blueprint $table) {
            $table->foreignId('lb_campaign_id')
                ->nullable()
                ->after('campaign_code')
                ->constrained('lb_campaigns')
                ->nullOnDelete();
        });

        DB::statement('ALTER TABLE `storage` ALTER COLUMN `status` DROP DEFAULT');
    }

    public function down(): void
    {
        Schema::table('storage', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lb_campaign_id');
        });

        DB::statement("ALTER TABLE `storage` ALTER COLUMN `status` SET DEFAULT '0'");
    }
};
