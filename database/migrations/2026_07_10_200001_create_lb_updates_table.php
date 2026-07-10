<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CRM-style conversations (tuco-owned) — mirrors the Menford CRM's `updates`
 * table structure, but authored by tuco `users` (the CRM's shared table is
 * authored by crm_users and is not writable by tuco). One row = one top-level
 * update on an entity; replies live in lb_update_replies.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lb_updates', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 20);          // 'campaign' | 'publication'
            $table->string('entity_id', 64);            // campaign id | storage id
            $table->foreignId('user_id')->constrained('users');  // author
            $table->text('body');
            $table->timestamp('edited_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lb_updates');
    }
};
