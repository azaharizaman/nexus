<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hrm_attendance_records', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('employee_id')->index();
            $table->date('date')->index(); // For daily queries
            $table->datetime('clock_in_at');
            $table->datetime('clock_out_at')->nullable();
            $table->integer('break_minutes')->default(0);
            $table->integer('overtime_minutes')->default(0);
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'employee_id', 'date']);
            $table->index(['tenant_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_attendance_records');
    }
};