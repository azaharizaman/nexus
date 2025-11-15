<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $itemsTable = Config::get('inventory-management.table_names.items', 'items');

        Schema::create($itemsTable, function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            // Avoid hard cross-package foreign key. Enforce at application level.
            $table->unsignedBigInteger('uom_id')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $itemsTable = Config::get('inventory-management.table_names.items', 'items');

        Schema::dropIfExists($itemsTable);
    }
};
