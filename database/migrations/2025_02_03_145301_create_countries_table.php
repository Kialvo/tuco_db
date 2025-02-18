<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->bigIncrements('id');     // Auto-generated primary key
            $table->string('country_name');  // e.g. "Italy", "France", etc.
        });
    }

    public function down()
    {
        Schema::dropIfExists('countries');
    }
};
