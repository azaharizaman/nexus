<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enhanced Activity Log Table Migration
 *
 * This migration extends the basic activity_log table with additional fields
 * required for comprehensive audit logging including tenant isolation,
 * request context, and event tracking.
 *
 * Fields Added:
 * - tenant_id: For multi-tenant data isolation
 * - event: Event type (created, updated, deleted, etc.)
 * - ip_address: IP address of the requester
 * - user_agent: User agent string from request
 * - request_id: Unique request identifier for tracing
 *
 * Performance Indexes:
 * - Composite index on (tenant_id, created_at) for tenant-scoped queries
 * - Composite index on (tenant_id, event) for filtering by event type
 * - Composite index on (tenant_id, subject_type, subject_id) for subject lookups
 * - Index on request_id for request tracing
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table(config('activitylog.table_name', 'activity_log'), function (Blueprint $table) {
            // Add tenant_id for multi-tenancy support
            // Using string type to support both UUID and integer-based tenant systems
            // Nullable to support system-level logs without tenant context
            $table->string('tenant_id')->nullable()->after('id');

            // Add event type column for filtering (created, updated, deleted, etc.)
            if (! Schema::hasColumn(config('activitylog.table_name', 'activity_log'), 'event')) {
                $table->string('event')->nullable()->after('description');
            }

            // Add request context fields for audit trail
            $table->string('ip_address', 45)->nullable()->after('properties');
            $table->text('user_agent')->nullable()->after('ip_address');
            $table->string('request_id')->nullable()->after('user_agent');

            // Add performance indexes
            $table->index(['tenant_id', 'created_at'], 'idx_activity_log_tenant_created');
            $table->index(['tenant_id', 'event'], 'idx_activity_log_tenant_event');
            $table->index(['tenant_id', 'subject_type', 'subject_id'], 'idx_activity_log_tenant_subject');
            $table->index('request_id', 'idx_activity_log_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(config('activitylog.table_name', 'activity_log'), function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex('idx_activity_log_tenant_created');
            $table->dropIndex('idx_activity_log_tenant_event');
            $table->dropIndex('idx_activity_log_tenant_subject');
            $table->dropIndex('idx_activity_log_request_id');

            // Drop columns
            $table->dropColumn(['tenant_id', 'ip_address', 'user_agent', 'request_id']);

            // Note: We don't drop 'event' column as it might have been added by Spatie package
        });
    }
};
