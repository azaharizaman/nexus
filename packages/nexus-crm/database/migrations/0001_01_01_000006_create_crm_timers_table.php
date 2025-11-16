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
        Schema::create('crm_timers', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Entity relationship
            $table->foreignUlid('entity_id')->constrained('crm_entities')->onDelete('cascade');

            // Timer configuration
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type'); // 'stage_timeout', 'follow_up', 'escalation'

            // Timing
            $table->timestamp('scheduled_at');
            $table->timestamp('executed_at')->nullable();
            $table->integer('duration_minutes')->nullable(); // For relative timers

            // Action configuration
            $table->json('action_config'); // What to do when timer fires

            // Status
            $table->string('status')->default('pending'); // pending, executed, cancelled
            $table->boolean('is_recurring')->default(false);

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index(['entity_id', 'status']);
            $table->index('scheduled_at');
            $table->index('executed_at');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_timers');
    }
};