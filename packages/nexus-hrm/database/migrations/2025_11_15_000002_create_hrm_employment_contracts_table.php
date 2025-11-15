<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hrm_employment_contracts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('employee_id')->index();
            $table->string('contract_type');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->integer('probation_period_days')->nullable();
            $table->date('probation_end_date')->nullable();
            $table->string('position');
            $table->string('department_id')->nullable();
            $table->string('reporting_to_employee_id')->nullable();
            $table->string('employment_grade')->nullable();
            $table->decimal('salary', 12, 2)->nullable();
            $table->string('salary_currency', 3)->nullable();
            $table->json('benefits')->nullable();
            $table->string('work_schedule')->nullable();
            $table->integer('standard_work_hours_per_week')->nullable();
            $table->boolean('is_current')->default(false)->index();
            $table->string('contract_document_path')->nullable();
            $table->json('terms_and_conditions')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'employee_id', 'is_current']);
            $table->index(['tenant_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_employment_contracts');
    }
};
