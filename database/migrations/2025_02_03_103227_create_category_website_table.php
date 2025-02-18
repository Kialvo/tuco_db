<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('category_website', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('website_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamps();

            // Foreign keys
            $table->foreign('website_id')
                ->references('id')->on('websites')
                ->onDelete('cascade');

            $table->foreign('category_id')
                ->references('id')->on('categories')
                ->onDelete('cascade');

            // Unique constraint if you want to ensure no duplicates
            $table->unique(['website_id', 'category_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('category_website');
    }
};
