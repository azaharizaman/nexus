<?php

namespace Nexus\InventoryManagement\Database\Factories;

use Nexus\InventoryManagement\Models\Item;
use Nexus\UomManagement\Models\UomUnit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition()
    {
        return [
            'name' => $this->faker->words(2, true),
            'sku' => Str::upper($this->faker->unique()->lexify('SKU-????')), 
            'uom_id' => UomUnit::factory(),
        ];
    }
}
