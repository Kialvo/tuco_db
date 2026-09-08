<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which Monday item a publication came from, for the Network Sales migration
 * (board 582070825 — "Sales - Network Stats").
 *
 * Without it the import cannot be run twice: the rejected items on that board
 * carry no Article URL, so there is nothing else to recognise them by, and a
 * second run would create 1,372 duplicate publications. The unique index makes
 * that impossible at the database level rather than trusting the command.
 *
 * It also makes the import traceable and reversible — every row it creates can
 * be found again, which is not true of an ordinary bulk insert.
 *
 * Nullable, with no default: every publication that already exists keeps a null
 * here and is untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storage', function (Blueprint $table) {
            $table->unsignedBigInteger('monday_item_id')->nullable()->after('id');
            $table->unique('monday_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('storage', function (Blueprint $table) {
            $table->dropUnique(['monday_item_id']);
            $table->dropColumn('monday_item_id');
        });
    }
};
