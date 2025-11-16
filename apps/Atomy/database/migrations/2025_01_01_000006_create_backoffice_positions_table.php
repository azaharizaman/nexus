<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('backoffice_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('backoffice_companies')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('backoffice_departments')->nullOnDelete();
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->string('gred', 50)->nullable();
            $table->string('type', 50); // PositionType enum
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'type']);
            $table->index('department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backoffice_positions');
    }
};
