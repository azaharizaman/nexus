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
        Schema::create('crm_pipelines', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Pipeline metadata
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('entity_type'); // 'lead', 'opportunity'

            // Pipeline configuration
            $table->json('config'); // Stages, transitions, conditions

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('entity_type');
            $table->index('is_active');
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_pipelines');
    }
};