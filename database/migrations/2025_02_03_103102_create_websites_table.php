<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('websites', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('domain_name');
            $table->string('status');
            // Foreign keys
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->unsignedBigInteger('language_id')->nullable();

            // Pricing / evaluation fields
            $table->decimal('publisher_price', 8, 2)->nullable();
            $table->dateTime('date_publisher_price')->nullable();
            $table->decimal('link_insertion_price', 8, 2)->nullable();
            $table->decimal('no_follow_price', 8, 2)->nullable();
            $table->decimal('special_topic_price', 8, 2)->nullable();
            $table->decimal('profit', 8, 2)->nullable();
            $table->decimal('automatic_evaluation', 8, 2)->nullable();
            $table->decimal('kialvo_evaluation', 8, 2)->nullable();
            $table->dateTime('date_kialvo_evaluation')->nullable();

            // Type of website (enum or string)
            // If you want a strict enum in MySQL, you can do:
            // $table->enum('type_of_website', ['blog', 'news', 'forum', '...'])->nullable();
            // For more flexibility, let's just do a string:
            $table->string('type_of_website')->nullable();

            // SEO metrics
            $table->unsignedSmallInteger('DA')->nullable();  // or integer
            $table->unsignedSmallInteger('PA')->nullable();
            $table->unsignedSmallInteger('TC')->nullable();
            $table->unsignedSmallInteger('CF')->nullable();
            $table->unsignedSmallInteger('DR')->nullable();
            $table->unsignedSmallInteger('UR')->nullable();
            $table->unsignedSmallInteger('ZA')->nullable();
            $table->unsignedSmallInteger('(AS)')->nullable();

            $table->decimal('TF_vs_CF', 5, 2)->nullable();
            $table->unsignedInteger('semrush_traffic')->nullable();
            $table->unsignedInteger('ahrefs_keyword')->nullable();
            $table->decimal('keyword_vs_traffic', 8, 2)->nullable();
            $table->dateTime('seo_metrics_date')->nullable();

            // Booleans
            $table->boolean('betting')->default(false);
            $table->boolean('trading')->default(false);
            $table->boolean('more_than_one_link')->default(false);
            $table->boolean('copywriting')->default(false);
            $table->boolean('no_sponsored_tag')->default(false);
            $table->boolean('social_media_sharing')->default(false);
            $table->boolean('post_in_homepage')->default(false);

            // Additional fields
            $table->dateTime('date_added')->nullable();
            $table->string('extra_notes', 500)->nullable();
            // or $table->text('extra_notes')->nullable(); if you expect a lot of text

            // Timestamps
            $table->timestamps();

            // Foreign key constraints (optional if you want DB-level constraints)
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('set null');
            $table->foreign('language_id')->references('id')->on('languages')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('websites');
    }
};
