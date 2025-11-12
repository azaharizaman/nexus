<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UomCategory;
use App\Models\Uom;
use Illuminate\Database\Seeder;

/**
 * UOM Seeder
 *
 * Seeds standard system UOMs across all 6 categories with proper conversion factors.
 * System UOMs are marked with is_system=true and tenant_id=NULL.
 */
class UomSeeder extends Seeder
{
    /**
     * Run the database seeds
     *
     * @return void
     */
    public function run(): void
    {
        // Clear existing system UOMs
        Uom::where('is_system', true)->forceDelete();

        // LENGTH - Base unit: meter (m)
        $this->seedLengthUoms();

        // MASS - Base unit: kilogram (kg)
        $this->seedMassUoms();

        // VOLUME - Base unit: liter (L)
        $this->seedVolumeUoms();

        // AREA - Base unit: square meter (m²)
        $this->seedAreaUoms();

        // COUNT - Base unit: piece (pc)
        $this->seedCountUoms();

        // TIME - Base unit: second (s)
        $this->seedTimeUoms();
    }

    /**
     * Seed length units (base: meter)
     */
    private function seedLengthUoms(): void
    {
        $units = [
            ['code' => 'mm', 'name' => 'Millimeter', 'symbol' => 'mm', 'factor' => '0.0010000000'],
            ['code' => 'cm', 'name' => 'Centimeter', 'symbol' => 'cm', 'factor' => '0.0100000000'],
            ['code' => 'm', 'name' => 'Meter', 'symbol' => 'm', 'factor' => '1.0000000000'],
            ['code' => 'km', 'name' => 'Kilometer', 'symbol' => 'km', 'factor' => '1000.0000000000'],
            ['code' => 'in', 'name' => 'Inch', 'symbol' => '"', 'factor' => '0.0254000000'],
            ['code' => 'ft', 'name' => 'Foot', 'symbol' => '\'', 'factor' => '0.3048000000'],
            ['code' => 'yd', 'name' => 'Yard', 'symbol' => 'yd', 'factor' => '0.9144000000'],
            ['code' => 'mi', 'name' => 'Mile', 'symbol' => 'mi', 'factor' => '1609.3440000000'],
        ];

        foreach ($units as $unit) {
            Uom::updateOrCreate(
                ['tenant_id' => null, 'code' => $unit['code']],
                [
                    'name' => $unit['name'],
                    'symbol' => $unit['symbol'],
                    'category' => UomCategory::LENGTH,
                    'conversion_factor' => $unit['factor'],
                    'is_system' => true,
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Seed mass units (base: kilogram)
     */
    private function seedMassUoms(): void
    {
        $units = [
            ['code' => 'mg', 'name' => 'Milligram', 'symbol' => 'mg', 'factor' => '0.0000010000'],
            ['code' => 'g', 'name' => 'Gram', 'symbol' => 'g', 'factor' => '0.0010000000'],
            ['code' => 'kg', 'name' => 'Kilogram', 'symbol' => 'kg', 'factor' => '1.0000000000'],
            ['code' => 't', 'name' => 'Metric Ton', 'symbol' => 't', 'factor' => '1000.0000000000'],
            ['code' => 'oz', 'name' => 'Ounce', 'symbol' => 'oz', 'factor' => '0.0283495000'],
            ['code' => 'lb', 'name' => 'Pound', 'symbol' => 'lb', 'factor' => '0.4535924000'],
            ['code' => 'ton', 'name' => 'US Ton (Short Ton)', 'symbol' => 'ton', 'factor' => '907.1847000000'],
        ];

        foreach ($units as $unit) {
            Uom::updateOrCreate(
                ['tenant_id' => null, 'code' => $unit['code']],
                [
                    'name' => $unit['name'],
                    'symbol' => $unit['symbol'],
                    'category' => UomCategory::MASS,
                    'conversion_factor' => $unit['factor'],
                    'is_system' => true,
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Seed volume units (base: liter)
     */
    private function seedVolumeUoms(): void
    {
        $units = [
            ['code' => 'mL', 'name' => 'Milliliter', 'symbol' => 'mL', 'factor' => '0.0010000000'],
            ['code' => 'L', 'name' => 'Liter', 'symbol' => 'L', 'factor' => '1.0000000000'],
            ['code' => 'm³', 'name' => 'Cubic Meter', 'symbol' => 'm³', 'factor' => '1000.0000000000'],
            ['code' => 'fl oz', 'name' => 'US Fluid Ounce', 'symbol' => 'fl oz', 'factor' => '0.0295735000'],
            ['code' => 'cup', 'name' => 'US Cup', 'symbol' => 'cup', 'factor' => '0.2365882000'],
            ['code' => 'pt', 'name' => 'US Pint', 'symbol' => 'pt', 'factor' => '0.4731765000'],
            ['code' => 'qt', 'name' => 'US Quart', 'symbol' => 'qt', 'factor' => '0.9463529000'],
            ['code' => 'gal', 'name' => 'US Gallon', 'symbol' => 'gal', 'factor' => '3.7854118000'],
        ];

        foreach ($units as $unit) {
            Uom::updateOrCreate(
                ['tenant_id' => null, 'code' => $unit['code']],
                [
                    'name' => $unit['name'],
                    'symbol' => $unit['symbol'],
                    'category' => UomCategory::VOLUME,
                    'conversion_factor' => $unit['factor'],
                    'is_system' => true,
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Seed area units (base: square meter)
     */
    private function seedAreaUoms(): void
    {
        $units = [
            ['code' => 'mm²', 'name' => 'Square Millimeter', 'symbol' => 'mm²', 'factor' => '0.0000010000'],
            ['code' => 'cm²', 'name' => 'Square Centimeter', 'symbol' => 'cm²', 'factor' => '0.0001000000'],
            ['code' => 'm²', 'name' => 'Square Meter', 'symbol' => 'm²', 'factor' => '1.0000000000'],
            ['code' => 'ha', 'name' => 'Hectare', 'symbol' => 'ha', 'factor' => '10000.0000000000'],
            ['code' => 'km²', 'name' => 'Square Kilometer', 'symbol' => 'km²', 'factor' => '1000000.0000000000'],
            ['code' => 'sq in', 'name' => 'Square Inch', 'symbol' => 'sq in', 'factor' => '0.0006452000'],
            ['code' => 'sq ft', 'name' => 'Square Foot', 'symbol' => 'sq ft', 'factor' => '0.0929030000'],
            ['code' => 'ac', 'name' => 'Acre', 'symbol' => 'ac', 'factor' => '4046.8564224000'],
        ];

        foreach ($units as $unit) {
            Uom::updateOrCreate(
                ['tenant_id' => null, 'code' => $unit['code']],
                [
                    'name' => $unit['name'],
                    'symbol' => $unit['symbol'],
                    'category' => UomCategory::AREA,
                    'conversion_factor' => $unit['factor'],
                    'is_system' => true,
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Seed count units (base: piece)
     */
    private function seedCountUoms(): void
    {
        $units = [
            ['code' => 'pc', 'name' => 'Piece', 'symbol' => 'pc', 'factor' => '1.0000000000'],
            ['code' => 'doz', 'name' => 'Dozen', 'symbol' => 'doz', 'factor' => '12.0000000000'],
            ['code' => 'gr', 'name' => 'Gross', 'symbol' => 'gr', 'factor' => '144.0000000000'],
            ['code' => '100', 'name' => 'Hundred', 'symbol' => '100', 'factor' => '100.0000000000'],
            ['code' => '1000', 'name' => 'Thousand', 'symbol' => '1000', 'factor' => '1000.0000000000'],
        ];

        foreach ($units as $unit) {
            Uom::updateOrCreate(
                ['tenant_id' => null, 'code' => $unit['code']],
                [
                    'name' => $unit['name'],
                    'symbol' => $unit['symbol'],
                    'category' => UomCategory::COUNT,
                    'conversion_factor' => $unit['factor'],
                    'is_system' => true,
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Seed time units (base: second)
     */
    private function seedTimeUoms(): void
    {
        $units = [
            ['code' => 's', 'name' => 'Second', 'symbol' => 's', 'factor' => '1.0000000000'],
            ['code' => 'min', 'name' => 'Minute', 'symbol' => 'min', 'factor' => '60.0000000000'],
            ['code' => 'hr', 'name' => 'Hour', 'symbol' => 'hr', 'factor' => '3600.0000000000'],
            ['code' => 'day', 'name' => 'Day', 'symbol' => 'day', 'factor' => '86400.0000000000'],
            ['code' => 'wk', 'name' => 'Week', 'symbol' => 'wk', 'factor' => '604800.0000000000'],
        ];

        foreach ($units as $unit) {
            Uom::updateOrCreate(
                ['tenant_id' => null, 'code' => $unit['code']],
                [
                    'name' => $unit['name'],
                    'symbol' => $unit['symbol'],
                    'category' => UomCategory::TIME,
                    'conversion_factor' => $unit['factor'],
                    'is_system' => true,
                    'is_active' => true,
                ]
            );
        }
    }
}
