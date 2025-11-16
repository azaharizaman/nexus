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
        Schema::create('crm_entities', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Entity metadata
            $table->string('entity_type'); // 'lead', 'opportunity', 'contact'
            $table->ulid('definition_id');
            $table->ulid('owner_id'); // User who owns this entity

            // Entity data (JSON)
            $table->json('data');

            // Status and workflow
            $table->string('status')->default('active');
            $table->ulid('current_stage_id')->nullable();
            $table->integer('stage_order')->default(0);

            // Assignment
            $table->json('assigned_users')->nullable(); // Array of user IDs
            $table->string('assignment_strategy')->nullable(); // 'unison', 'majority', 'quorum'

            // Scoring and priority
            $table->decimal('score', 5, 2)->nullable();
            $table->string('priority')->default('medium');

            // Timestamps
            $table->timestamps();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('due_date')->nullable();

            // Soft deletes
            $table->softDeletes();

            // Indexes
            $table->index(['entity_type', 'status']);
            $table->index('definition_id');
            $table->index('owner_id');
            $table->index('current_stage_id');
            $table->index('score');
            $table->index('priority');
            $table->index('last_activity_at');
            $table->index('due_date');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_entities');
    }
};