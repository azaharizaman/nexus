<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('org_reporting_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('manager_employee_id')->index();
            $table->ulid('subordinate_employee_id')->index();
            $table->ulid('position_id')->nullable()->index();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'manager_employee_id']);
            $table->index(['tenant_id', 'subordinate_employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('org_reporting_lines');
    }
};
