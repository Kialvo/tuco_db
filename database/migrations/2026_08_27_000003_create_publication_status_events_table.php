<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When each publication reached each status — the history the order tracker
 * needs to put a date beside every step.
 *
 * Only the CURRENT status is stored on `storage`, so without this there is
 * nothing to date past steps with. Recording starts from the day this ships;
 * nothing is backfilled, which was agreed because every existing order is an
 * internal test.
 *
 * Append-only: a status change is a fact, never edited or deleted. No
 * updated_at, for the same reason.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publication_status_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storage_id')->constrained('storage')->cascadeOnDelete();
            $table->string('status', 50);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            // "the events for this publication, oldest first" is the only read.
            $table->index(['storage_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_status_events');
    }
};
