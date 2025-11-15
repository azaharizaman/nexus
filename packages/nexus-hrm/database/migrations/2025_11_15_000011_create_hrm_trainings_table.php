<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_trainings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category');
            $table->enum('training_type', ['internal', 'external', 'online', 'classroom', 'workshop', 'seminar']);
            $table->decimal('duration_hours', 5, 2);
            $table->string('provider')->nullable();
            $table->decimal('cost', 10, 2)->default(0);
            $table->integer('max_participants')->nullable();
            $table->json('prerequisites')->nullable();
            $table->json('objectives')->nullable();
            $table->json('materials')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'category']);
            $table->index(['tenant_id', 'training_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_trainings');
    }
};