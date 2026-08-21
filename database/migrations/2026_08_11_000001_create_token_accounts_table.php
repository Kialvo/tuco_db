<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One prepaid credit account per user.
 *
 * balance_cached is a CACHE, not the truth: token_transactions is the ledger.
 * It exists so a balance check is one indexed read instead of a SUM over the
 * account's whole history, and it is written inside the same transaction as
 * every ledger row. A scheduled reconciliation re-derives SUM(amount) and
 * alerts on drift.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('token_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->integer('balance_cached')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_accounts');
    }
};
