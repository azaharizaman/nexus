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
        Schema::create('crm_assignments', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Entity relationship
            $table->foreignUlid('entity_id')->constrained('crm_entities')->onDelete('cascade');

            // Assignment details
            $table->string('user_id'); // UUID or identifier of assigned user
            $table->string('assigned_by'); // UUID of user who made assignment
            $table->string('role')->default('owner'); // owner, collaborator, viewer

            // Assignment metadata
            $table->json('permissions')->nullable(); // Specific permissions for this assignment
            $table->timestamp('assigned_at');
            $table->timestamp('expires_at')->nullable();

            // Status
            $table->boolean('is_active')->default(true);

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index(['entity_id', 'user_id']);
            $table->index(['user_id', 'is_active']);
            $table->index('assigned_at');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_assignments');
    }
};