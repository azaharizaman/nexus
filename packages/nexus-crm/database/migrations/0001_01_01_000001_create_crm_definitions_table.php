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
        Schema::create('crm_definitions', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Definition metadata
            $table->string('name');
            $table->string('type'); // 'lead', 'opportunity', 'contact', etc.
            $table->text('description')->nullable();

            // JSON schema for entity fields
            $table->json('schema');

            // Pipeline configuration
            $table->json('pipeline_config')->nullable();

            // Assignment strategies
            $table->json('assignment_config')->nullable();

            // Permissions and guards
            $table->json('permissions')->nullable();

            // Status
            $table->boolean('is_active')->default(true);

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('type');
            $table->index('is_active');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_definitions');
    }
};