<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (Schema::hasColumn('websites', 'deleted_at')) {
            return; // already exists, skip
        }
        Schema::table('websites', function (Blueprint $table) {
            $table->softDeletes(); // adds 'deleted_at' column
        });
    }

    public function down()
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->dropSoftDeletes(); // removes 'deleted_at'
        });
    }
};
