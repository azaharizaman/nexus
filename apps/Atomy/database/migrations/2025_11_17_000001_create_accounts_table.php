<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->string('code')->index();
            $table->string('name');
            $table->string('type')->index();
            $table->boolean('is_active')->default(true);
            $table->json('tags')->nullable();
            $table->string('reporting_group')->nullable();
            // Nested set fields
            $table->unsignedInteger('lft')->nullable()->index();
            $table->unsignedInteger('rgt')->nullable()->index();
            $table->timestamps();

            $table->unique(['tenant_id','code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
