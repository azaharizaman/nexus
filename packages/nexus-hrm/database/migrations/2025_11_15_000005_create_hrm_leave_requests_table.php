<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hrm_leave_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('employee_id')->index();
            $table->ulid('leave_type_id')->index();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days', 8, 2);
            $table->string('status')->index();
            $table->json('approval_chain')->nullable();
            $table->string('workflow_instance_id')->nullable()->index();
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'employee_id', 'status']);
            $table->index(['tenant_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_leave_requests');
    }
};
