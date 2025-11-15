<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_performance_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id');
            $table->string('employee_id');
            $table->string('reviewer_id');
            $table->string('performance_cycle_id');
            $table->string('review_template_id')->nullable();
            $table->date('review_date');
            $table->decimal('overall_rating', 3, 2)->nullable(); // 0.00 to 5.00
            $table->text('reviewer_comments')->nullable();
            $table->text('employee_comments')->nullable();
            $table->enum('status', ['draft', 'pending', 'in_progress', 'completed', 'cancelled'])->default('draft');
            $table->json('scores')->nullable(); // Array of KPI scores with weights
            $table->json('goals_assessment')->nullable(); // OKR progress assessment
            $table->json('development_plan')->nullable(); // Development recommendations
            $table->date('next_review_date')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'employee_id']);
            $table->index(['performance_cycle_id']);
            $table->index(['reviewer_id']);
            $table->index(['status']);
            $table->index(['review_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_performance_reviews');
    }
};