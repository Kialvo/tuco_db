<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A single top-up attempt: what was bought, in which currency, and how it went.
 *
 * amount_minor is in MINOR units (cents) as an integer — never a float.
 *
 * tokens is fixed from the package at creation time and is NEVER derived by
 * converting the amount paid: two customers buying the same package must
 * always receive the same tokens, whatever the rate did in between.
 *
 * billing_snapshot freezes the customer's invoicing details as they were at
 * purchase, so a later address or VAT change can never rewrite an issued
 * invoice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('token_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('token_account_id')->constrained()->cascadeOnDelete();

            $table->string('package_key', 40);
            $table->integer('tokens');              // base tokens
            $table->integer('bonus_tokens')->default(0);

            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3);

            $table->string('gateway', 20);          // fake | stripe
            $table->string('gateway_session_id')->nullable()->unique();
            $table->string('gateway_payment_id')->nullable()->unique();

            // pending | paid | failed | refunded | expired
            $table->string('status', 20)->default('pending');

            $table->json('billing_snapshot')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_purchases');
    }
};
