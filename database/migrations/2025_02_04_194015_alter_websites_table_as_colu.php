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
        Schema::table('websites', function (Blueprint $table) {
            // Rename the 'AS' column to a safer name, e.g. 'as_metric'
            $table->renameColumn('AS', 'as_metric');
        });
    }

    public function down()
    {
        Schema::table('websites', function (Blueprint $table) {
            // If you ever need to revert, rename 'as_metric' back to 'AS'
            $table->renameColumn('as_metric', 'AS');
        });
    }
};
