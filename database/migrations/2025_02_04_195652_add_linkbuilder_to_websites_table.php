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
            // Add the new column (nullable if you don't always have a country)
            $table->string('linkbuilder')->nullable()->after('profit');

        });
    }

    public function down()
    {
        Schema::table('websites', function (Blueprint $table) {

            $table->dropColumn('linkbuilder');
        });
    }
};
