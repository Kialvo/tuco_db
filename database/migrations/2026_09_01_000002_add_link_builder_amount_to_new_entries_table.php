<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirror of the same column on `websites`.
 *
 * New entries run the same price formula and are promoted into `websites`, so
 * without this a promoted entry would arrive with a Price computed as if no
 * link builder had been paid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('new_entries', function (Blueprint $table) {
            $table->decimal('link_builder_amount', 10, 0)->nullable()->after('publisher_price');
        });
    }

    public function down(): void
    {
        Schema::table('new_entries', function (Blueprint $table) {
            $table->dropColumn('link_builder_amount');
        });
    }
};
