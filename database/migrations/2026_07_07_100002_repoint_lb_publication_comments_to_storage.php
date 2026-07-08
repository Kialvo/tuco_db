<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — publication comments now hang off the storage row itself
 * (the storage row IS the publication). Table is empty at migration time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lb_publication_comments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lb_publication_id');
            $table->foreignId('storage_id')
                ->after('id')
                ->constrained('storage')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lb_publication_comments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('storage_id');
            $table->foreignId('lb_publication_id')
                ->after('id')
                ->constrained('lb_publications')
                ->cascadeOnDelete();
        });
    }
};
