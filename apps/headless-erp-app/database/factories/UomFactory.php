<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UomCategory;
use App\Models\Uom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Uom>
 */
class UomFactory extends Factory
{
    protected $model = Uom::class;

    /**
     * Define the model's default state
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $category = $this->faker->randomElement(UomCategory::cases());

        return [
            'tenant_id' => null, // Will be set by withTenant() state
            'code' => strtoupper($this->faker->unique()->bothify('???-##')),
            'name' => $this->faker->words(2, true),
            'symbol' => strtoupper($this->faker->lexify('???')),
            'category' => $category,
            'conversion_factor' => '1.0000000000',
            'is_system' => false,
            'is_active' => true,
        ];
    }

    /**
     * Mark UOM as system (global) rather than custom (tenant-specific)
     *
     * @return self
     */
    public function system(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => true,
            'tenant_id' => null,
        ]);
    }

    /**
     * Mark UOM as custom (tenant-specific)
     *
     * @return self
     */
    public function custom(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => false,
            'tenant_id' => $attributes['tenant_id'] ?? \Illuminate\Support\Str::uuid(),
        ]);
    }

    /**
     * Mark UOM as inactive
     *
     * @return self
     */
    public function inactive(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create UOM for specific category
     *
     * @param  UomCategory  $category
     * @return self
     */
    public function forCategory(UomCategory $category): self
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
        ]);
    }

    /**
     * Create a length UOM (meter-based)
     *
     * @return self
     */
    public function length(): self
    {
        return $this->forCategory(UomCategory::LENGTH);
    }

    /**
     * Create a mass UOM (kilogram-based)
     *
     * @return self
     */
    public function mass(): self
    {
        return $this->forCategory(UomCategory::MASS);
    }

    /**
     * Create a volume UOM (liter-based)
     *
     * @return self
     */
    public function volume(): self
    {
        return $this->forCategory(UomCategory::VOLUME);
    }

    /**
     * Create an area UOM (square meter-based)
     *
     * @return self
     */
    public function area(): self
    {
        return $this->forCategory(UomCategory::AREA);
    }

    /**
     * Create a count UOM (piece-based)
     *
     * @return self
     */
    public function count(): self
    {
        return $this->forCategory(UomCategory::COUNT);
    }

    /**
     * Create a time UOM (second-based)
     *
     * @return self
     */
    public function time(): self
    {
        return $this->forCategory(UomCategory::TIME);
    }

    /**
     * Create a base unit (conversion_factor = 1.0)
     *
     * @return self
     */
    public function baseUnit(): self
    {
        return $this->state(fn (array $attributes) => [
            'conversion_factor' => '1.0000000000',
        ]);
    }

    /**
     * Create with custom conversion factor
     *
     * @param  string  $factor High-precision decimal factor
     * @return self
     */
    public function withConversionFactor(string $factor): self
    {
        return $this->state(fn (array $attributes) => [
            'conversion_factor' => $factor,
        ]);
    }
}
