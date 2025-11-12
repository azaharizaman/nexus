<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uoms', function (Blueprint $table) {
            // Primary key and tenant
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable();

            // Basic information
            $table->string('code')->comment('UOM code: m, kg, L, etc.');
            $table->string('name')->comment('Full name: meter, kilogram, liter');
            $table->string('symbol')->comment('Symbol for display: m, kg, L');
            $table->string('category')->comment('Category: LENGTH, MASS, VOLUME, AREA, COUNT, TIME');

            // Conversion factors with high precision
            $table->decimal('conversion_factor', precision: 20, places: 10)
                ->default(1)
                ->comment('Conversion factor to base unit (20 digits, 10 decimals)');

            // System and status flags
            $table->boolean('is_system')
                ->default(false)
                ->comment('True for system UOMs (fixed), false for custom');
            $table->boolean('is_active')
                ->default(true)
                ->comment('Soft activation flag (for UI filtering)');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Unique constraints
            $table->unique(['tenant_id', 'code'], 'unique_tenant_code');

            // Indexes for query performance
            $table->index(['tenant_id', 'category'], 'idx_tenant_category');
            $table->index(['tenant_id', 'is_active'], 'idx_tenant_active');
            $table->index(['tenant_id', 'is_system'], 'idx_tenant_system');
            $table->index(['category', 'is_system'], 'idx_category_system');

            // Foreign key
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uoms');
    }
};
