<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Serial Number Logs Table Migration
 *
 * This migration creates the serial_number_logs table for comprehensive
 * audit trail of all generated serial numbers. This table is append-only
 * to maintain immutable audit records.
 *
 * Key Features:
 * - Complete audit trail of all generated numbers
 * - Tenant isolation for multi-tenant systems
 * - Causer tracking (who generated the number)
 * - Request context for debugging
 * - Metadata for additional context
 *
 * Performance Indexes:
 * - Index on (tenant_id, created_at) for tenant-scoped queries
 * - Index on (tenant_id, sequence_name) for sequence-specific logs
 * - Index on generated_number for uniqueness checks
 * - Index on causer_id for user audit queries
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('serial_number_logs', function (Blueprint $table) {
            $table->id();

            // Tenant isolation
            $table->string('tenant_id')->index();

            // Sequence identification
            $table->string('sequence_name', 255)->index();

            // Generated number
            $table->string('generated_number', 255)->index();

            // Causer (who generated this number)
            $table->string('causer_type', 255)->nullable();
            $table->unsignedBigInteger('causer_id')->nullable();

            // Additional context
            $table->json('metadata')->nullable();

            // Timestamp (append-only table)
            $table->timestamp('created_at')->index();

            // Composite indexes for performance
            $table->index(['tenant_id', 'created_at'], 'idx_logs_tenant_created');
            $table->index(['tenant_id', 'sequence_name'], 'idx_logs_tenant_sequence');
            $table->index('causer_id', 'idx_logs_causer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serial_number_logs');
    }
};
