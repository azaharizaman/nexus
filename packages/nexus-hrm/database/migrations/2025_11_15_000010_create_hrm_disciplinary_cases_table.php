<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_disciplinary_cases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id');
            $table->string('employee_id');
            $table->enum('case_type', ['verbal_warning', 'written_warning', 'performance_improvement', 'suspension', 'termination', 'other']);
            $table->enum('severity', ['minor', 'moderate', 'major', 'critical']);
            $table->text('description');
            $table->date('incident_date');
            $table->date('reported_date');
            $table->enum('status', ['investigating', 'pending_resolution', 'resolved', 'dismissed', 'appeal_pending'])->default('investigating');
            $table->string('handler_id')->nullable();
            $table->text('resolution')->nullable();
            $table->date('resolution_date')->nullable();
            $table->boolean('follow_up_required')->default(false);
            $table->date('follow_up_date')->nullable();
            $table->json('documents')->nullable();
            $table->json('witnesses')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'employee_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'severity']);
            $table->index(['incident_date']);
            $table->index(['handler_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_disciplinary_cases');
    }
};