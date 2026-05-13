<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', [
                'draft',         // open cart — only one per user at a time
                'submitted',     // user clicked Submit
                'confirmed',     // admin verified prices
                'approved',      // admin approved
                'in_progress',   // articles being placed
                'completed',     // fulfilled
                'cancelled',     // user/admin cancelled
            ])->default('draft')->index();
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
