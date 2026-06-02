<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->decimal('mention_price', 10, 2)->nullable()->after('sitewide_link_price');
        });

        Schema::table('new_entries', function (Blueprint $table) {
            $table->decimal('mention_price', 10, 2)->nullable()->after('sitewide_link_price');
        });
    }

    public function down(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->dropColumn('mention_price');
        });

        Schema::table('new_entries', function (Blueprint $table) {
            $table->dropColumn('mention_price');
        });
    }
};
