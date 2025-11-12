<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Serial Number Sequences Table Migration
 *
 * This migration creates the serial_number_sequences table for storing
 * sequence configurations with support for tenant isolation, pattern-based
 * generation, and automatic reset periods.
 *
 * Key Features:
 * - Tenant-scoped sequences with isolation
 * - Configurable patterns with variable substitution
 * - Reset periods: never, daily, monthly, yearly
 * - Optimistic locking with version column
 * - Row-level locking support for atomic counter increment
 *
 * Performance Indexes:
 * - Unique constraint on (tenant_id, sequence_name)
 * - Index on (tenant_id, sequence_name) for fast lookups
 * - Index on reset_period for automated reset queries
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('serial_number_sequences', function (Blueprint $table) {
            $table->id();

            // Tenant isolation
            // Using string to support UUID and integer-based tenant systems
            $table->string('tenant_id')->index();

            // Sequence identification
            $table->string('sequence_name', 255);

            // Pattern configuration
            $table->string('pattern', 500);

            // Reset behavior
            $table->enum('reset_period', ['never', 'daily', 'monthly', 'yearly'])->default('yearly');

            // Counter configuration
            $table->unsignedTinyInteger('padding')->default(5);
            $table->unsignedBigInteger('current_value')->default(0);

            // Reset tracking
            $table->timestamp('last_reset_at')->nullable();

            // Additional context (stored as JSON)
            $table->json('metadata')->nullable();

            // Optimistic locking for concurrent updates
            $table->unsignedInteger('version')->default(0);

            $table->timestamps();

            // Unique constraint: One sequence per tenant
            $table->unique(['tenant_id', 'sequence_name'], 'uq_sequences_tenant_name');

            // Index for fast tenant-scoped lookups
            $table->index(['tenant_id', 'sequence_name'], 'idx_sequences_tenant_name');

            // Index for automated reset queries
            $table->index('reset_period', 'idx_sequences_reset_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serial_number_sequences');
    }
};
