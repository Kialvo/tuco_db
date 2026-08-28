<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links an ordered site to the publication that fulfils it.
 *
 * Until now `order_items` and `storage` had no relationship at all, which is
 * why an order could not show the status of anything. One nullable column,
 * added — no existing row or column is touched, and every current order simply
 * keeps NULL (they are historic tests and are deliberately not backfilled).
 *
 * Run this file ONLY, never a blanket migrate:
 *   php artisan migrate --pretend --path=database/migrations/2026_08_27_000001_add_publication_link_to_order_items_table.php
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('storage_id')->nullable()->after('website_id')
                ->constrained('storage')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('storage_id');
        });
    }
};
