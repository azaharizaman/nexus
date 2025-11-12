<?php

declare(strict_types=1);

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
        Schema::create(config('settings-management.table_name', 'settings'), function (Blueprint $table) {
            $table->id();
            
            // Setting identification
            $table->string('key', 255)->comment('Setting key in dot notation (e.g., email.smtp.host)');
            
            // Value storage
            $table->text('value')->nullable()->comment('Setting value (encrypted for type=encrypted)');
            $table->string('type', 50)->default('string')->comment('Value type: string, integer, boolean, array, json, encrypted');
            
            // Scope identification
            $table->string('scope', 50)->default('system')->comment('Scope level: system, tenant, module, user');
            
            // Scope references (nullable for system-level settings)
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade')->comment('Reference to tenant (null for system scope)');
            $table->string('module_name', 100)->nullable()->comment('Module name for module-scoped settings');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade')->comment('Reference to user (null for non-user scope)');
            
            // Metadata
            $table->json('metadata')->nullable()->comment('Additional metadata: validation rules, default value, description, category');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['key', 'scope'], 'idx_settings_key_scope');
            $table->index(['tenant_id', 'key'], 'idx_settings_tenant_key');
            $table->index(['module_name', 'key'], 'idx_settings_module_key');
            $table->index(['user_id', 'key'], 'idx_settings_user_key');
            $table->index(['scope', 'key'], 'idx_settings_scope_key');
            
            // Unique constraint for combination of key and scope identifiers
            // System scope: key must be unique
            // Tenant scope: key + tenant_id must be unique
            // Module scope: key + module_name + tenant_id must be unique
            // User scope: key + user_id + tenant_id must be unique
            $table->unique(['key', 'scope', 'tenant_id', 'module_name', 'user_id'], 'uq_settings_composite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('settings-management.table_name', 'settings'));
    }
};
