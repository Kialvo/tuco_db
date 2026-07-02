<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link Building CRM — campaigns.
 *
 * NEW table, owned by Linkinablink. Named `lb_campaigns` to avoid ANY collision
 * with the shared Menford CRM `campaigns` table. FKs to shared tables use
 * SET NULL on delete so this table can never block or destructively cascade
 * into companies / clients / users data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lb_campaigns', function (Blueprint $table) {
            $table->id();

            $table->string('code')->index();

            // References to SHARED tables — SET NULL on delete (never cascade)
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('service')->nullable();
            $table->string('status')->index();

            $table->decimal('deal_value', 12, 2)->default(0);

            $table->string('target_type')->default('budget'); // 'budget' | 'publications'
            $table->decimal('target_value', 12, 2)->default(0);
            $table->decimal('live_count', 12, 2)->default(0);  // manual progress toward target

            $table->date('budget_approval_date')->nullable();
            $table->date('offer_ready_date')->nullable();
            $table->date('deadline')->nullable();
            $table->date('completion_date')->nullable();
            $table->date('next_update_date')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lb_campaigns');
    }
};
