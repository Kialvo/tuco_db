<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('status_changed_at')->nullable()->after('submitted_at');
        });

        // Backfill existing rows so the column isn't empty for current orders
        DB::statement('UPDATE orders SET status_changed_at = updated_at WHERE status_changed_at IS NULL');
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('status_changed_at');
        });
    }
};
