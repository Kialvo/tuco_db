<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every webhook the gateway sends us, recorded before it is acted on.
 *
 * event_id is UNIQUE: gateways retry deliveries, so without this a single
 * payment could be credited several times. Insert-then-process means a
 * duplicate delivery collides on the index and is ignored.
 *
 * The raw payload is kept for dispute forensics. Signature headers and secrets
 * are NOT stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 20);
            $table->string('event_id')->unique();
            $table->string('type', 60);
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('processing_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};
