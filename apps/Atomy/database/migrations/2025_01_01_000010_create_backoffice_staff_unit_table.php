<?php

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
        Schema::create('backoffice_staff_unit', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->unsignedBigInteger('unit_id');
            $table->timestamps();
            
            $table->foreign('staff_id')
                  ->references('id')
                  ->on('backoffice_staff')
                  ->onDelete('cascade');
                  
            $table->foreign('unit_id')
                  ->references('id')
                  ->on('backoffice_units')
                  ->onDelete('cascade');
            
            $table->unique(['staff_id', 'unit_id']);
            $table->index(['staff_id']);
            $table->index(['unit_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backoffice_staff_unit');
    }
};