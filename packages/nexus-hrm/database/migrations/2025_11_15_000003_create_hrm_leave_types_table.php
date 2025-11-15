<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hrm_leave_types', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->string('code');
            $table->string('name');
            $table->boolean('requires_approval')->default(true);
            $table->boolean('allow_negative_balance')->default(false);
            $table->boolean('allow_carry_forward')->default(true);
            $table->integer('max_carry_forward_days')->default(0);
            $table->boolean('pro_rata')->default(true);
            $table->json('rules')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_leave_types');
    }
};
