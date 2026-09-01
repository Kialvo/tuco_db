<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-publication link builder cost, filled in by hand exactly like
 * `publisher_amount`, and added to `total_cost` by StorageCalculator.
 *
 * Nothing is backfilled: there are no link builder amounts for past
 * publications, so every existing row keeps the total_cost it has today.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storage', function (Blueprint $table) {
            $table->decimal('link_builder_amount', 10, 0)->nullable()->after('publisher_amount');
        });
    }

    public function down(): void
    {
        Schema::table('storage', function (Blueprint $table) {
            $table->dropColumn('link_builder_amount');
        });
    }
};
