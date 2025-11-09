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
        Schema::create('tenants', function (Blueprint $table) {
            // UUID primary key
            $table->uuid('id')->primary();

            // Basic information
            $table->string('name');
            $table->string('domain')->unique();
            $table->string('status')->default('active');

            // Configuration and subscription
            $table->json('configuration')->nullable();
            $table->string('subscription_plan')->nullable();

            // Billing information
            $table->string('billing_email')->nullable();

            // Contact information
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
