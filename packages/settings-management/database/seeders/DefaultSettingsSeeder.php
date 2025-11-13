<?php

declare(strict_types=1);

namespace Nexus\Erp\SettingsManagement\Database\Seeders;

use Nexus\Erp\SettingsManagement\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Default Settings Seeder
 *
 * Seeds system-level default settings from configuration.
 */
class DefaultSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $defaults = config('settings-management.defaults', []);

        foreach ($defaults as $key => $data) {
            // Check if setting already exists
            $exists = Setting::where('key', $key)
                ->where('scope', Setting::SCOPE_SYSTEM)
                ->whereNull('tenant_id')
                ->exists();

            if ($exists) {
                $this->command->warn("Setting '{$key}' already exists, skipping...");
                continue;
            }

            // Create system-level setting
            Setting::create([
                'key' => $key,
                'value' => $this->castToStorage($data['value'], $data['type']),
                'type' => $data['type'],
                'scope' => Setting::SCOPE_SYSTEM,
                'tenant_id' => null,
                'module_name' => null,
                'user_id' => null,
                'metadata' => $data['metadata'] ?? [],
            ]);

            $this->command->info("Created system setting: {$key}");
        }

        $this->command->info('Default settings seeded successfully.');
    }

    /**
     * Cast value to storage format
     *
     * @param mixed $value
     * @param string $type
     * @return string|null
     */
    protected function castToStorage(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'string' => (string) $value,
            'integer' => (string) $value,
            'boolean' => $value ? '1' : '0',
            'array', 'json' => json_encode($value),
            default => (string) $value,
        };
    }
}
