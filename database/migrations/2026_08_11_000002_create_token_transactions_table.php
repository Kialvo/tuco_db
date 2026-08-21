<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The ledger. APPEND ONLY — never UPDATE, never DELETE a row here.
 *
 * A correction is a new compensating row, so the history always explains the
 * balance. amount is a SIGNED INTEGER of tokens: positive credits, negative
 * debits. No floats anywhere near money.
 *
 * idempotency_key is the most important column in the schema: it is what stops
 * a retried Stripe webhook, a double-clicked button or a replayed job from
 * crediting or spending twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('token_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('token_account_id')->constrained()->cascadeOnDelete();

            // purchase | spend | refund | adjustment | bonus | expiry
            $table->string('type', 20);

            $table->integer('amount');              // signed: + credit, - debit
            $table->integer('balance_after');       // snapshot for audit

            $table->string('reference_type', 40)->nullable();
            $table->string('reference_id', 64)->nullable();

            $table->string('idempotency_key')->unique();

            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // created_at only: these rows are immutable, so updated_at would lie.
            $table->timestamp('created_at')->nullable();

            $table->index(['token_account_id', 'id']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_transactions');
    }
};
