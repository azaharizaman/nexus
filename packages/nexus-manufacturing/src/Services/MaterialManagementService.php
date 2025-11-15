<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Services;

use Nexus\Manufacturing\Contracts\MaterialManagementServiceContract;
use Nexus\Manufacturing\Contracts\WorkOrderRepositoryContract;
use Nexus\Manufacturing\Models\WorkOrder;
use Nexus\Manufacturing\Models\MaterialAllocation;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class MaterialManagementService implements MaterialManagementServiceContract
{
    public function __construct(
        private readonly WorkOrderRepositoryContract $workOrderRepository
    ) {}

    public function issueMaterials(string $workOrderId, array $materials): array
    {
        $workOrder = $this->workOrderRepository->find($workOrderId);
        if (!$workOrder) {
            throw new InvalidArgumentException("Work order not found: {$workOrderId}");
        }

        if (!$workOrder->canStartProduction() && $workOrder->status->value !== 'in_production') {
            throw new InvalidArgumentException("Work order must be released or in production to issue materials");
        }

        $updatedAllocations = [];

        foreach ($materials as $material) {
            $allocation = $workOrder->materialAllocations()
                ->where('component_product_id', $material['component_product_id'])
                ->first();

            if (!$allocation) {
                throw new InvalidArgumentException("Component not found in work order allocations");
            }

            // Update issued quantity and lot number
            $allocation->update([
                'quantity_issued' => $allocation->quantity_issued + $material['quantity'],
                'lot_number' => $material['lot_number'] ?? $allocation->lot_number,
            ]);

            $updatedAllocations[] = $allocation->fresh();

            // Trigger MaterialConsumed event (placeholder for event dispatch)
            // event(new MaterialConsumed($workOrder, $allocation, $material['quantity']));
        }

        return $updatedAllocations;
    }

    public function backflushMaterials(string $workOrderId, float $quantityProduced): array
    {
        $workOrder = $this->workOrderRepository->find($workOrderId);
        if (!$workOrder) {
            throw new InvalidArgumentException("Work order not found: {$workOrderId}");
        }

        $updatedAllocations = [];

        // Backflush materials based on BOM quantities and actual production
        foreach ($workOrder->materialAllocations as $allocation) {
            // Calculate quantity to consume based on production quantity
            $bomItem = $workOrder->billOfMaterial->components()
                ->where('component_product_id', $allocation->component_product_id)
                ->first();

            if ($bomItem) {
                $quantityPerUnit = $bomItem->getTotalQuantityNeeded();
                $quantityToConsume = $quantityPerUnit * $quantityProduced;

                // Update consumed quantity
                $allocation->update([
                    'quantity_issued' => $allocation->quantity_issued + $quantityToConsume,
                    'quantity_consumed' => $allocation->quantity_consumed + $quantityToConsume,
                ]);

                $updatedAllocations[] = $allocation->fresh();

                // Trigger MaterialConsumed event
                // event(new MaterialConsumed($workOrder, $allocation, $quantityToConsume));
            }
        }

        return $updatedAllocations;
    }

    public function returnMaterial(string $allocationId, float $quantity): MaterialAllocation
    {
        $allocation = MaterialAllocation::find($allocationId);

        if (!$allocation) {
            throw new InvalidArgumentException("Material allocation not found: {$allocationId}");
        }

        if ($quantity > $allocation->quantity_issued) {
            throw new InvalidArgumentException("Return quantity exceeds issued quantity");
        }

        // Reduce issued quantity
        $allocation->update([
            'quantity_issued' => $allocation->quantity_issued - $quantity,
        ]);

        // Log the return (in production, would create MaterialReturn record)
        // MaterialReturn::create([...]);

        return $allocation->fresh();
    }

    public function getMaterialVariance(string $workOrderId): array
    {
        $workOrder = $this->workOrderRepository->find($workOrderId);
        if (!$workOrder) {
            throw new InvalidArgumentException("Work order not found: {$workOrderId}");
        }

        return $workOrder->materialAllocations->map(function (MaterialAllocation $allocation) {
            return [
                'component_product_id' => $allocation->component_product_id,
                'component_name' => $allocation->componentProduct->name ?? 'Unknown',
                'quantity_required' => $allocation->quantity_required,
                'quantity_consumed' => $allocation->quantity_consumed,
                'variance' => $allocation->getVariance(),
                'variance_percentage' => $allocation->getVariancePercentage(),
                'is_over_consumed' => $allocation->getVariance() > 0,
            ];
        })->toArray();
    }

    public function checkMaterialAvailability(string $workOrderId): array
    {
        $workOrder = $this->workOrderRepository->find($workOrderId);
        if (!$workOrder) {
            throw new InvalidArgumentException("Work order not found: {$workOrderId}");
        }

        $shortages = [];
        $allAvailable = true;

        foreach ($workOrder->materialAllocations as $allocation) {
            $componentProduct = $allocation->componentProduct;
            
            // Check inventory availability (placeholder - would integrate with inventory system)
            // $availableQuantity = $componentProduct->inventory->available_quantity ?? 0;
            $availableQuantity = 1000; // Placeholder - would come from inventory system

            $shortage = max(0, $allocation->quantity_required - $availableQuantity);
            
            if ($shortage > 0) {
                $allAvailable = false;
                $shortages[] = [
                    'component_product_id' => $allocation->component_product_id,
                    'component_name' => $componentProduct->name ?? 'Unknown',
                    'quantity_required' => $allocation->quantity_required,
                    'available_quantity' => $availableQuantity,
                    'shortage' => $shortage,
                ];
            }
        }

        return [
            'all_available' => $allAvailable,
            'shortages' => $shortages,
            'total_items' => $workOrder->materialAllocations->count(),
            'items_short' => count($shortages),
        ];
    }
}
