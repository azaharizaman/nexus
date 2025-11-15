<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Services;

use Nexus\Manufacturing\Contracts\ProductionCostingServiceContract;
use Nexus\Manufacturing\Contracts\WorkOrderRepositoryContract;
use Nexus\Manufacturing\Models\ProductionCosting;
use Nexus\Manufacturing\Models\WorkOrder;
use InvalidArgumentException;

class ProductionCostingService implements ProductionCostingServiceContract
{
    public function __construct(
        private readonly WorkOrderRepositoryContract $workOrderRepository
    ) {}

    public function calculateCosting(string $workOrderId): ProductionCosting
    {
        $workOrder = $this->workOrderRepository->find($workOrderId);
        if (!$workOrder) {
            throw new InvalidArgumentException("Work order not found: {$workOrderId}");
        }

        // Calculate standard costs
        $standardMaterialCost = $this->calculateStandardMaterialCost($workOrder);
        $standardLaborCost = $this->calculateStandardLaborCost($workOrder);
        $standardOverheadCost = $this->calculateStandardOverheadCost($workOrder, $standardLaborCost);

        // Calculate actual costs (if work order is completed)
        $actualMaterialCost = $this->calculateActualMaterialCost($workOrder);
        $actualLaborCost = $this->calculateActualLaborCost($workOrder);
        $actualOverheadCost = $this->calculateActualOverheadCost($workOrder, $actualLaborCost);

        // Create or update costing record
        $costing = ProductionCosting::updateOrCreate(
            ['work_order_id' => $workOrderId],
            [
                'standard_material_cost' => $standardMaterialCost,
                'standard_labor_cost' => $standardLaborCost,
                'standard_overhead_cost' => $standardOverheadCost,
                'actual_material_cost' => $actualMaterialCost,
                'actual_labor_cost' => $actualLaborCost,
                'actual_overhead_cost' => $actualOverheadCost,
            ]
        );

        return $costing;
    }

    public function getStandardCost(string $workOrderId): array
    {
        $workOrder = $this->workOrderRepository->find($workOrderId);
        if (!$workOrder) {
            throw new InvalidArgumentException("Work order not found: {$workOrderId}");
        }

        $materialCost = $this->calculateStandardMaterialCost($workOrder);
        $laborCost = $this->calculateStandardLaborCost($workOrder);
        $overheadCost = $this->calculateStandardOverheadCost($workOrder, $laborCost);

        return [
            'material_cost' => $materialCost,
            'labor_cost' => $laborCost,
            'overhead_cost' => $overheadCost,
            'total_cost' => $materialCost + $laborCost + $overheadCost,
        ];
    }

    public function getActualCost(string $workOrderId): array
    {
        $workOrder = $this->workOrderRepository->find($workOrderId);
        if (!$workOrder) {
            throw new InvalidArgumentException("Work order not found: {$workOrderId}");
        }

        $materialCost = $this->calculateActualMaterialCost($workOrder);
        $laborCost = $this->calculateActualLaborCost($workOrder);
        $overheadCost = $this->calculateActualOverheadCost($workOrder, $laborCost);

        return [
            'material_cost' => $materialCost,
            'labor_cost' => $laborCost,
            'overhead_cost' => $overheadCost,
            'total_cost' => $materialCost + $laborCost + $overheadCost,
        ];
    }

    public function getVarianceAnalysis(string $workOrderId): array
    {
        $costing = ProductionCosting::where('work_order_id', $workOrderId)->first();
        if (!$costing) {
            throw new InvalidArgumentException("Costing record not found for work order: {$workOrderId}");
        }

        return [
            'material_variance' => [
                'standard' => $costing->standard_material_cost,
                'actual' => $costing->actual_material_cost,
                'variance' => $costing->getMaterialVariance(),
                'variance_percentage' => $costing->getVariancePercentage('material'),
                'is_favorable' => $costing->isFavorableVariance('material'),
            ],
            'labor_variance' => [
                'standard' => $costing->standard_labor_cost,
                'actual' => $costing->actual_labor_cost,
                'variance' => $costing->getLaborVariance(),
                'variance_percentage' => $costing->getVariancePercentage('labor'),
                'is_favorable' => $costing->isFavorableVariance('labor'),
            ],
            'overhead_variance' => [
                'standard' => $costing->standard_overhead_cost,
                'actual' => $costing->actual_overhead_cost,
                'variance' => $costing->getOverheadVariance(),
                'variance_percentage' => $costing->getVariancePercentage('overhead'),
                'is_favorable' => $costing->isFavorableVariance('overhead'),
            ],
            'total_variance' => [
                'standard' => $costing->standard_material_cost + $costing->standard_labor_cost + $costing->standard_overhead_cost,
                'actual' => $costing->actual_material_cost + $costing->actual_labor_cost + $costing->actual_overhead_cost,
                'variance' => $costing->getMaterialVariance() + $costing->getLaborVariance() + $costing->getOverheadVariance(),
            ],
        ];
    }

    public function getCostPerUnit(string $workOrderId): float
    {
        $workOrder = $this->workOrderRepository->find($workOrderId);
        if (!$workOrder) {
            throw new InvalidArgumentException("Work order not found: {$workOrderId}");
        }

        $actualCost = $this->getActualCost($workOrderId);
        $quantityCompleted = $workOrder->quantity_completed;

        if ($quantityCompleted <= 0) {
            return 0.0;
        }

        return round($actualCost['total_cost'] / $quantityCompleted, 2);
    }

    private function calculateStandardMaterialCost(WorkOrder $workOrder): float
    {
        $totalCost = 0;

        foreach ($workOrder->materialAllocations as $allocation) {
            $standardCost = $allocation->componentProduct->standard_cost ?? 0;
            $totalCost += $allocation->quantity_required * $standardCost;
        }

        return round($totalCost, 2);
    }

    private function calculateStandardLaborCost(WorkOrder $workOrder): float
    {
        if (!$workOrder->routing) {
            return 0;
        }

        $totalLaborHours = 0;

        foreach ($workOrder->routing->operations as $operation) {
            $totalLaborHours += $operation->calculateLaborHours($workOrder->quantity_ordered);
        }

        $laborRate = config('manufacturing.costing.labor_rate', 25.00);
        
        return round($totalLaborHours * $laborRate, 2);
    }

    private function calculateStandardOverheadCost(WorkOrder $workOrder, float $laborCost): float
    {
        $overheadRate = config('manufacturing.costing.overhead_rate', 1.5);
        
        return round($laborCost * $overheadRate, 2);
    }

    private function calculateActualMaterialCost(WorkOrder $workOrder): float
    {
        $totalCost = 0;

        foreach ($workOrder->materialAllocations as $allocation) {
            // Use actual cost if available, otherwise use standard cost
            $actualCost = $allocation->componentProduct->actual_cost 
                ?? $allocation->componentProduct->standard_cost 
                ?? 0;
            
            $totalCost += $allocation->quantity_consumed * $actualCost;
        }

        return round($totalCost, 2);
    }

    private function calculateActualLaborCost(WorkOrder $workOrder): float
    {
        $totalLaborHours = $workOrder->productionReports->sum('labor_hours');
        $laborRate = config('manufacturing.costing.labor_rate', 25.00);
        
        return round($totalLaborHours * $laborRate, 2);
    }

    private function calculateActualOverheadCost(WorkOrder $workOrder, float $actualLaborCost): float
    {
        $overheadRate = config('manufacturing.costing.overhead_rate', 1.5);
        
        return round($actualLaborCost * $overheadRate, 2);
    }
}
