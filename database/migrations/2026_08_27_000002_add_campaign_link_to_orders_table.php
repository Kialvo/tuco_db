<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links a marketplace order to the campaign opened for it.
 *
 * One campaign per order, tagged with the "LIAB Marketplace" service so it is
 * distinguishable from Martina's own campaigns in the Campaigns list.
 *
 * Nullable and additive: existing orders keep NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('lb_campaign_id')->nullable()->after('user_id')
                ->constrained('lb_campaigns')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lb_campaign_id');
        });
    }
};
