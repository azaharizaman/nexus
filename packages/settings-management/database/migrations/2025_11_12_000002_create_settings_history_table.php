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
        Schema::create(config('settings-management.history_table_name', 'settings_history'), function (Blueprint $table) {
            $table->id();
            
            // Reference to setting (nullable as setting might be deleted)
            $table->unsignedBigInteger('setting_id')->nullable()->comment('Reference to settings.id');
            
            // Setting identification (stored for history even if setting deleted)
            $table->string('key', 255)->comment('Setting key at time of change');
            $table->string('scope', 50)->comment('Scope at time of change');
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->string('module_name', 100)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Change tracking
            $table->text('old_value')->nullable()->comment('Previous value');
            $table->text('new_value')->nullable()->comment('New value');
            $table->string('old_type', 50)->nullable()->comment('Previous type');
            $table->string('new_type', 50)->nullable()->comment('New type');
            
            // Change metadata
            $table->string('action', 50)->comment('Action: created, updated, deleted');
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null')->comment('User who made the change');
            $table->string('ip_address', 45)->nullable()->comment('IP address of change');
            $table->text('user_agent')->nullable()->comment('User agent string');
            
            // Timestamp
            $table->timestamp('changed_at')->useCurrent()->comment('When the change occurred');
            
            // Indexes
            $table->index(['setting_id', 'changed_at'], 'idx_history_setting_time');
            $table->index(['key', 'changed_at'], 'idx_history_key_time');
            $table->index(['tenant_id', 'changed_at'], 'idx_history_tenant_time');
            $table->index(['changed_by', 'changed_at'], 'idx_history_user_time');
            $table->index('action', 'idx_history_action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('settings-management.history_table_name', 'settings_history'));
    }
};
