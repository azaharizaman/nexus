<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\SerialNumbering\Database\Seeders;

use Azaharizaman\Erp\SerialNumbering\Enums\ResetPeriod;
use Azaharizaman\Erp\SerialNumbering\Models\Sequence;
use Illuminate\Database\Seeder;

/**
 * Default Sequence Seeder
 *
 * Seeds default sequence configurations for common business documents.
 * These are examples and should be customized per tenant.
 */
class DefaultSequenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $defaultSequences = [
            [
                'tenant_id' => '1', // Update with actual tenant ID
                'sequence_name' => 'invoices',
                'pattern' => 'INV-{YEAR}-{COUNTER:5}',
                'reset_period' => ResetPeriod::YEARLY,
                'padding' => 5,
                'metadata' => ['description' => 'Sales invoices'],
            ],
            [
                'tenant_id' => '1',
                'sequence_name' => 'purchase_orders',
                'pattern' => 'PO-{YEAR:2}{MONTH}-{COUNTER:4}',
                'reset_period' => ResetPeriod::MONTHLY,
                'padding' => 4,
                'metadata' => ['description' => 'Purchase orders'],
            ],
            [
                'tenant_id' => '1',
                'sequence_name' => 'receipts',
                'pattern' => 'RCP-{YEAR}-{COUNTER:6}',
                'reset_period' => ResetPeriod::YEARLY,
                'padding' => 6,
                'metadata' => ['description' => 'Payment receipts'],
            ],
            [
                'tenant_id' => '1',
                'sequence_name' => 'quotations',
                'pattern' => 'QT-{YEAR:2}{MONTH}-{COUNTER:4}',
                'reset_period' => ResetPeriod::YEARLY,
                'padding' => 4,
                'metadata' => ['description' => 'Sales quotations'],
            ],
            [
                'tenant_id' => '1',
                'sequence_name' => 'credit_notes',
                'pattern' => 'CN-{YEAR}-{COUNTER:5}',
                'reset_period' => ResetPeriod::YEARLY,
                'padding' => 5,
                'metadata' => ['description' => 'Credit notes'],
            ],
            [
                'tenant_id' => '1',
                'sequence_name' => 'delivery_orders',
                'pattern' => 'DO-{YEAR:2}{MONTH}{DAY}-{COUNTER:3}',
                'reset_period' => ResetPeriod::DAILY,
                'padding' => 3,
                'metadata' => ['description' => 'Delivery orders'],
            ],
        ];

        foreach ($defaultSequences as $sequenceData) {
            Sequence::updateOrCreate(
                [
                    'tenant_id' => $sequenceData['tenant_id'],
                    'sequence_name' => $sequenceData['sequence_name'],
                ],
                $sequenceData
            );
        }
    }
}
