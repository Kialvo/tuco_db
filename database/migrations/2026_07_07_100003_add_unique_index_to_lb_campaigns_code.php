<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — campaign code is now the human key mirrored onto storage rows,
 * so duplicates must be impossible. Existing codes verified unique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lb_campaigns', function (Blueprint $table) {
            $table->unique('code');
        });
    }

    public function down(): void
    {
        Schema::table('lb_campaigns', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });
    }
};
