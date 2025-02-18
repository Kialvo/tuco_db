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
        Schema::create('staging_domains', function (Blueprint $table) {
            $table->id();
            // Just the domain name plus one column per category
            $table->string('domain_name')->unique();

            // Boolean columns for categories (default false => "NO")
            $table->boolean('betting')->default(false);
            $table->boolean('trading')->default(false);
            $table->boolean('sport')->default(false);
            $table->boolean('economy')->default(false);
            $table->boolean('travel')->default(false);
            $table->boolean('tech')->default(false);
            $table->boolean('design')->default(false);
            $table->boolean('food')->default(false);
            $table->boolean('wellness')->default(false);
            $table->boolean('hobby_and_diy')->default(false);
            $table->boolean('moda_fashion')->default(false);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('staging_domains');
    }
};
