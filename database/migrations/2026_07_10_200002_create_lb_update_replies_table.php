<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replies under an lb_update — mirrors the CRM's `update_replies`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lb_update_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lb_update_id')->constrained('lb_updates')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');  // author
            $table->text('body');
            $table->timestamp('edited_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lb_update_replies');
    }
};
