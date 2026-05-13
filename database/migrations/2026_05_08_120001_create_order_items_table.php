<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('website_id')->constrained()->restrictOnDelete();
            $table->enum('article_type', ['standard', 'sensitive'])->default('standard');
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->timestamps();

            // One website per order
            $table->unique(['order_id', 'website_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
