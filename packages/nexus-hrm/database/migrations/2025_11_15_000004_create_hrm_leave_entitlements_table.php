<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hrm_leave_entitlements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('employee_id')->index();
            $table->ulid('leave_type_id')->index();
            $table->integer('year')->index();
            $table->decimal('entitled_days', 8, 2)->default(0);
            $table->decimal('used_days', 8, 2)->default(0);
            $table->decimal('carried_forward_days', 8, 2)->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'employee_id', 'leave_type_id', 'year'], 'uniq_entitlement');
            $table->index(['tenant_id', 'leave_type_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_leave_entitlements');
    }
};
