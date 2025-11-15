<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Services;

use Nexus\Manufacturing\Contracts\BillOfMaterialRepositoryContract;
use Nexus\Manufacturing\Contracts\BOMExplosionServiceContract;
use Nexus\Manufacturing\Contracts\WorkOrderPlanningServiceContract;
use Nexus\Manufacturing\Contracts\WorkOrderRepositoryContract;
use Nexus\Manufacturing\Enums\WorkOrderStatus;
use Nexus\Manufacturing\Models\MaterialAllocation;
use Nexus\Manufacturing\Models\WorkOrder;

class WorkOrderPlanningService implements WorkOrderPlanningServiceContract
{
    public function __construct(
        protected WorkOrderRepositoryContract $workOrderRepository,
        protected BillOfMaterialRepositoryContract $bomRepository,
        protected BOMExplosionServiceContract $bomExplosionService
    ) {}

    public function createWorkOrder(array $data): WorkOrder
    {
        // Validate data
        $validation = $this->validateWorkOrder($data);
        
        if (!$validation['valid']) {
            throw new \InvalidArgumentException('Invalid work order data: ' . implode(', ', $validation['errors']));
        }

        // Get active BOM for product
        if (!isset($data['bom_id'])) {
            $bom = $this->bomRepository->findActiveForProduct($data['product_id']);
            
            if (!$bom) {
                throw new \RuntimeException('No active BOM found for product');
            }
            
            $data['bom_id'] = $bom->id;
        }

        // Set default status
        $data['status'] = $data['status'] ?? WorkOrderStatus::PLANNED;

        // Create work order
        return $this->workOrderRepository->create($data);
    }

    public function releaseWorkOrder(string $workOrderId): WorkOrder
    {
        $workOrder = $this->workOrderRepository->find($workOrderId);
        
        if (!$workOrder) {
            throw new \RuntimeException("Work order not found: {$workOrderId}");
        }

        if (!$workOrder->canRelease()) {
            throw new \RuntimeException('Work order cannot be released in current status');
        }

        // Allocate materials
        $this->allocateMaterials($workOrderId);

        // Change status to released
        return $this->workOrderRepository->changeStatus($workOrderId, WorkOrderStatus::RELEASED);
    }

    public function allocateMaterials(string $workOrderId): array
    {
        $workOrder = $this->workOrderRepository->find($workOrderId);
        
        if (!$workOrder) {
            throw new \RuntimeException("Work order not found: {$workOrderId}");
        }

        // Explode BOM to get material requirements
        $components = $this->bomExplosionService->explode(
            $workOrder->bom_id,
            $workOrder->quantity_ordered
        );

        $allocations = [];

        foreach ($components as $component) {
            // Create material allocation
            $allocation = MaterialAllocation::create([
                'work_order_id' => $workOrder->id,
                'component_product_id' => $component['component_product_id'],
                'quantity_required' => $component['total_quantity_required'],
                'quantity_issued' => 0,
                'quantity_consumed' => 0,
            ]);

            $allocations[] = $allocation;
        }

        return $allocations;
    }

    public function calculateLeadTime(string $productId, float $quantity): array
    {
        $bom = $this->bomRepository->findActiveForProduct($productId);
        
        if (!$bom) {
            return [
                'lead_time_days' => 0,
                'breakdown' => ['No BOM found for product'],
            ];
        }

        $leadTimeDays = 0;
        $breakdown = [];

        // Add procurement lead time for components
        $components = $this->bomExplosionService->explode($bom->id, $quantity);
        
        $maxComponentLeadTime = $components->max(fn($c) => $c['product']->lead_time_days ?? 0);
        $leadTimeDays += $maxComponentLeadTime;
        $breakdown[] = "Component procurement: {$maxComponentLeadTime} days";

        // Add production lead time from routing
        if ($bom->routing) {
            $productionTime = $bom->routing->calculateTotalTime($quantity);
            $productionDays = ceil($productionTime / (8 * 60)); // Convert minutes to days (8-hour days)
            $leadTimeDays += $productionDays;
            $breakdown[] = "Production time: {$productionDays} days";
        }

        return [
            'lead_time_days' => $leadTimeDays,
            'breakdown' => $breakdown,
        ];
    }

    public function validateWorkOrder(array $data): array
    {
        $errors = [];

        if (!isset($data['product_id'])) {
            $errors[] = 'Product ID is required';
        }

        if (!isset($data['quantity_ordered']) || $data['quantity_ordered'] <= 0) {
            $errors[] = 'Quantity ordered must be greater than 0';
        }

        if (isset($data['product_id'])) {
            $bom = $this->bomRepository->findActiveForProduct($data['product_id']);
            
            if (!$bom) {
                $errors[] = 'No active BOM found for product';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    public function scheduleWorkOrder(string $workOrderId, ?\DateTimeInterface $startDate = null): WorkOrder
    {
        $workOrder = $this->workOrderRepository->find($workOrderId);
        
        if (!$workOrder) {
            throw new \RuntimeException("Work order not found: {$workOrderId}");
        }

        $startDate = $startDate ?? now();

        // Calculate lead time
        $leadTime = $this->calculateLeadTime(
            $workOrder->product_id,
            $workOrder->quantity_ordered
        );

        // Set planned dates
        $endDate = now()->parse($startDate->format('Y-m-d'))->addDays($leadTime['lead_time_days']);

        return $this->workOrderRepository->update($workOrderId, [
            'planned_start_date' => $startDate,
            'planned_end_date' => $endDate,
        ]);
    }
}
