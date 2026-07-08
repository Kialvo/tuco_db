<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link Building CRM — per-publication comments thread (Phase 2).
 * NEW lb_ table owned by Linkinablink. Authors are Linkinablink users.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lb_publication_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lb_publication_id')->constrained('lb_publications')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lb_publication_comments');
    }
};
