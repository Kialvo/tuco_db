<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-publication office attribution, shown on the campaign page only for the
 * companies listed in config('linkbuilding.office_code_company_ids')
 * (Better Collective). Valid values live in config('linkbuilding.office_codes')
 * and are enforced by PublicationController — deliberately NOT a DB enum, so
 * adding an office stays a config change with no second migration.
 *
 * Additive and nullable: no existing row or column is touched.
 *
 * Run this file ONLY, never a blanket migrate:
 *   php artisan migrate --pretend --path=database/migrations/2026_08_07_000001_add_office_code_to_storage_table.php
 *   php artisan migrate --force   --path=database/migrations/2026_08_07_000001_add_office_code_to_storage_table.php
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storage', function (Blueprint $table) {
            $table->string('office_code', 10)->nullable()->after('invoice_company');
        });
    }

    public function down(): void
    {
        Schema::table('storage', function (Blueprint $table) {
            $table->dropColumn('office_code');
        });
    }
};
