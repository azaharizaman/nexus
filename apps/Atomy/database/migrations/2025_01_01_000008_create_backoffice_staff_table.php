<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('backoffice_staff', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->unsignedBigInteger('office_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('position_id')->nullable();
            $table->date('hire_date')->nullable();
            $table->date('resignation_date')->nullable();
            $table->text('resignation_reason')->nullable();
            $table->timestamp('resigned_at')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('supervisor_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('office_id')
                  ->references('id')
                  ->on('backoffice_offices')
                  ->onDelete('set null');
                  
            $table->foreign('department_id')
                  ->references('id')
                  ->on('backoffice_departments')
                  ->onDelete('set null');
            
            $table->foreign('position_id')
                  ->references('id')
                  ->on('backoffice_positions')
                  ->onDelete('set null');
                  
            $table->foreign('supervisor_id')
                  ->references('id')
                  ->on('backoffice_staff')
                  ->onDelete('set null');
            
            $table->index(['office_id']);
            $table->index(['department_id']);
            $table->index(['position_id']);
            $table->index(['supervisor_id']);
            $table->index(['status']);
            $table->index(['resignation_date']);
            $table->index(['is_active']);
            $table->index(['hire_date']);
            $table->index(['created_at']);
            $table->index(['first_name', 'last_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backoffice_staff');
    }
};