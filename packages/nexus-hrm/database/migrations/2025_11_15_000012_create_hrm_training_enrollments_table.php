<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_training_enrollments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id');
            $table->string('employee_id');
            $table->string('training_id');
            $table->timestamp('enrolled_at');
            $table->date('scheduled_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->enum('status', ['enrolled', 'scheduled', 'completed', 'cancelled', 'no_show', 'withdrawn'])->default('enrolled');
            $table->decimal('score', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->boolean('certificate_issued')->default(false);
            $table->string('certificate_number')->nullable();
            $table->date('certificate_expiry')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'employee_id']);
            $table->index(['tenant_id', 'training_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['scheduled_date']);
            $table->index(['completion_date']);
            $table->index(['certificate_expiry']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_training_enrollments');
    }
};