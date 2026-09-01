<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What we pay an external link builder for a domain, entered by hand.
 *
 * Always in EUR, whatever currency the domain is priced in, so this column is
 * deliberately NOT mirrored by an `original_link_builder_amount` and is never
 * touched by the USD->EUR triggers or by `conversion:daily`.
 *
 * decimal(10,0): whole euros, no cents — same shape as `storage.publisher_amount`.
 * Nullable with no default, like every neighbouring money column; code reads it
 * through COALESCE(..., 0) so empty behaves exactly as zero.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->decimal('link_builder_amount', 10, 0)->nullable()->after('publisher_price');
        });
    }

    public function down(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->dropColumn('link_builder_amount');
        });
    }
};
