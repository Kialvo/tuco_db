<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('websites', function (Blueprint $table) {
            // Add the new column (nullable if you don't always have a country)
            $table->unsignedBigInteger('country_id')->nullable()->after('contact_id');

            // Optionally add a foreign key constraint
            $table->foreign('country_id')
                ->references('id')->on('countries')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('websites', function (Blueprint $table) {
            // Drop the foreign key first (if you used one)
            $table->dropForeign(['country_id']);
            // Then drop the column
            $table->dropColumn('country_id');
        });
    }
};
