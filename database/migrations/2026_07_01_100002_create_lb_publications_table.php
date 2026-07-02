<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link Building CRM — publications (per-site outreach within a campaign).
 * NEW table owned by Linkinablink. Cascades only from its own parent lb_campaigns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lb_publications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lb_campaign_id')->constrained('lb_campaigns')->cascadeOnDelete();

            $table->string('site');
            $table->string('status')->index();
            $table->unsignedTinyInteger('status_group')->default(1); // 1 = Site Evaluation, 2 = Production

            $table->decimal('price', 12, 2)->default(0);

            $table->string('live_url', 500)->nullable();
            $table->date('live_date')->nullable();

            $table->date('date_to_copywriter')->nullable();
            $table->date('date_from_copywriter')->nullable(); // copy received
            $table->date('date_to_blog')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lb_publications');
    }
};
