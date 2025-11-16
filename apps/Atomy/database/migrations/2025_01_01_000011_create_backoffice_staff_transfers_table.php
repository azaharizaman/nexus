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
        Schema::create('backoffice_staff_transfers', function (Blueprint $table) {
            $table->id();
            
            // Staff being transferred
            $table->foreignId('staff_id')
                ->constrained('backoffice_staff')
                ->onDelete('cascade');
            
            // Office transfer details
            $table->foreignId('from_office_id')
                ->constrained('backoffice_offices')
                ->onDelete('restrict');
            
            $table->foreignId('to_office_id')
                ->constrained('backoffice_offices')
                ->onDelete('restrict');
            
            // Department changes (optional)
            $table->foreignId('from_department_id')
                ->nullable()
                ->constrained('backoffice_departments')
                ->onDelete('restrict');
            
            $table->foreignId('to_department_id')
                ->nullable()
                ->constrained('backoffice_departments')
                ->onDelete('restrict');
            
            // Supervisor changes
            $table->foreignId('from_supervisor_id')
                ->nullable()
                ->constrained('backoffice_staff')
                ->onDelete('restrict');
            
            $table->foreignId('to_supervisor_id')
                ->nullable()
                ->constrained('backoffice_staff')
                ->onDelete('restrict');
            
            // Transfer details
            $table->string('status', 20)->default('pending');
            $table->date('effective_date');
            $table->datetime('requested_at');
            $table->datetime('approved_at')->nullable();
            $table->datetime('rejected_at')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->datetime('cancelled_at')->nullable();
            
            // Who initiated and processed the transfer
            $table->foreignId('requested_by_id')
                ->constrained('backoffice_staff')
                ->onDelete('restrict');
            
            $table->foreignId('approved_by_id')
                ->nullable()
                ->constrained('backoffice_staff')
                ->onDelete('restrict');
            
            $table->foreignId('rejected_by_id')
                ->nullable()
                ->constrained('backoffice_staff')
                ->onDelete('restrict');
            
            $table->foreignId('processed_by_id')
                ->nullable()
                ->constrained('backoffice_staff')
                ->onDelete('restrict');
            
            // Additional details
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Position changes
            $table->foreignId('from_position_id')
                ->nullable()
                ->constrained('backoffice_positions')
                ->onDelete('restrict');
            
            $table->foreignId('to_position_id')
                ->nullable()
                ->constrained('backoffice_positions')
                ->onDelete('restrict');
            
            // Metadata
            $table->boolean('is_immediate')->default(false);
            $table->json('metadata')->nullable(); // For additional transfer data
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['staff_id', 'status']);
            $table->index(['effective_date', 'status']);
            $table->index(['from_office_id', 'to_office_id']);
            $table->index(['requested_at']);
            $table->index(['approved_at']);
            $table->index(['completed_at']);
            
            // Ensure consistent transfer data
            $table->unique(['staff_id', 'requested_at'], 'unique_staff_transfer_request');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backoffice_staff_transfers');
    }
};