<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100)->unique(); // e.g. "English"
            $table->string('code', 5)->nullable(); // e.g. "en"
        });
    }

    public function down()
    {
        Schema::dropIfExists('languages');
    }
};
