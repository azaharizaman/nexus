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
        Schema::create('backoffice_office_office_type', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('office_id');
            $table->unsignedBigInteger('office_type_id');
            $table->timestamps();
            
            $table->foreign('office_id')
                  ->references('id')
                  ->on('backoffice_offices')
                  ->onDelete('cascade');
                  
            $table->foreign('office_type_id')
                  ->references('id')
                  ->on('backoffice_office_types')
                  ->onDelete('cascade');
            
            $table->unique(['office_id', 'office_type_id']);
            $table->index(['office_id']);
            $table->index(['office_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backoffice_office_office_type');
    }
};