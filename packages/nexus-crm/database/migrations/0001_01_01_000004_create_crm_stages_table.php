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
        Schema::create('crm_stages', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Stage metadata
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color')->nullable(); // Hex color for UI

            // Pipeline relationship
            $table->foreignUlid('pipeline_id')->constrained('crm_pipelines')->onDelete('cascade');

            // Stage configuration
            $table->integer('order')->default(0);
            $table->json('config'); // Entry/exit conditions, actions, timers

            // Status
            $table->boolean('is_active')->default(true);

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index(['pipeline_id', 'order']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_stages');
    }
};