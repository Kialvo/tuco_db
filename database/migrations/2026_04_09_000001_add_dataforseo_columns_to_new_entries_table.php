<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('new_entries', function (Blueprint $table) {
            $table->unsignedInteger('ms')->nullable()->after('keyword_vs_traffic');
            $table->unsignedInteger('organic_keywords')->nullable()->after('ms');
            $table->unsignedInteger('organic_traffic')->nullable()->after('organic_keywords');
            $table->decimal('kw_traffic_ratio', 10, 2)->nullable()->after('organic_traffic');
        });
    }

    public function down(): void
    {
        Schema::table('new_entries', function (Blueprint $table) {
            $table->dropColumn(['ms', 'organic_keywords', 'organic_traffic', 'kw_traffic_ratio']);
        });
    }
};
