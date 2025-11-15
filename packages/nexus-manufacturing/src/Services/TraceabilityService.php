<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Services;

use Nexus\Manufacturing\Contracts\TraceabilityServiceContract;
use Nexus\Manufacturing\Contracts\WorkOrderRepositoryContract;
use Nexus\Manufacturing\Models\BatchGenealogy;
use Nexus\Manufacturing\Models\WorkOrder;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class TraceabilityService implements TraceabilityServiceContract
{
    public function __construct(
        private readonly WorkOrderRepositoryContract $workOrderRepository
    ) {}

    public function recordBatchGenealogy(string $workOrderId, string $finishedGoodsLot, array $rawMaterialLots): array
    {
        $workOrder = $this->workOrderRepository->find($workOrderId);
        if (!$workOrder) {
            throw new InvalidArgumentException("Work order not found: {$workOrderId}");
        }

        // Get or create batch genealogy for this work order
        $genealogy = BatchGenealogy::firstOrCreate([
            'work_order_id' => $workOrderId,
            'finished_good_lot' => $finishedGoodsLot,
        ]);

        // Attach raw material lots with quantities
        foreach ($rawMaterialLots as $lot) {
            $genealogy->rawMaterialLots()->attach($lot['lot_number'], [
                'product_id' => $lot['product_id'],
                'quantity_consumed' => $lot['quantity_consumed'],
            ]);
        }

        return [
            'genealogy_id' => $genealogy->id,
            'finished_good_lot' => $finishedGoodsLot,
            'raw_material_count' => count($rawMaterialLots),
            'created_at' => $genealogy->created_at->toDateTimeString(),
        ];
    }

    public function traceForward(string $lotNumber): Collection
    {
        // Find where this lot number was used as input (raw material)
        $genealogies = BatchGenealogy::whereHas('rawMaterialLots', function ($query) use ($lotNumber) {
            $query->where('batch_genealogy_materials.lot_number', $lotNumber);
        })->with('workOrder.product')->get();

        return $genealogies->map(function (BatchGenealogy $genealogy) use ($lotNumber) {
            $rawMaterial = $genealogy->rawMaterialLots()
                ->where('batch_genealogy_materials.lot_number', $lotNumber)
                ->first();

            return [
                'finished_good_lot' => $genealogy->finished_good_lot,
                'work_order_number' => $genealogy->workOrder->work_order_number,
                'product_id' => $genealogy->workOrder->product_id,
                'product_name' => $genealogy->workOrder->product->name ?? 'Unknown',
                'quantity_produced' => $genealogy->workOrder->quantity_completed,
                'production_date' => $genealogy->workOrder->actual_end_date,
                'quantity_consumed' => $rawMaterial->pivot->quantity_consumed ?? 0,
            ];
        });
    }

    public function traceBackward(string $lotNumber): Collection
    {
        // Find raw materials used to produce this lot
        $genealogy = BatchGenealogy::where('finished_good_lot', $lotNumber)
            ->with('rawMaterialLots')
            ->first();

        if (!$genealogy) {
            return collect([]);
        }

        return $genealogy->rawMaterialLots->map(function ($rawMaterial) {
            return [
                'raw_material_lot' => $rawMaterial->pivot->lot_number,
                'product_id' => $rawMaterial->pivot->product_id,
                'product_name' => $rawMaterial->name ?? 'Unknown',
                'quantity_consumed' => $rawMaterial->pivot->quantity_consumed,
            ];
        });
    }

    public function getCompleteChain(string $lotNumber): array
    {
        // Trace backward to find raw materials
        $rawMaterials = $this->traceBackward($lotNumber);

        // Trace forward to find what was produced from this lot
        $finishedGoods = $this->traceForward($lotNumber);

        // Get the genealogy record
        $genealogy = BatchGenealogy::where('finished_good_lot', $lotNumber)
            ->with('workOrder.product')
            ->first();

        return [
            'lot_number' => $lotNumber,
            'work_order_number' => $genealogy?->workOrder->work_order_number,
            'product_id' => $genealogy?->workOrder->product_id,
            'product_name' => $genealogy?->workOrder->product->name ?? 'Unknown',
            'production_date' => $genealogy?->workOrder->actual_end_date,
            'quantity_produced' => $genealogy?->workOrder->quantity_completed,
            'raw_materials' => $rawMaterials,
            'finished_goods' => $finishedGoods,
        ];
    }

    public function identifyRecallImpact(string $lotNumber): Collection
    {
        $impactedLots = [];
        $processedLots = [];

        // Recursively trace forward to find all impacted lots
        $this->recursiveTraceForward($lotNumber, $impactedLots, $processedLots);

        // Get details for all impacted lots
        $details = [];
        foreach ($impactedLots as $impactedLot) {
            $genealogy = BatchGenealogy::where('finished_good_lot', $impactedLot)
                ->with('workOrder.product')
                ->first();

            if ($genealogy) {
                $details[] = [
                    'lot_number' => $impactedLot,
                    'work_order_number' => $genealogy->workOrder->work_order_number,
                    'product_id' => $genealogy->workOrder->product_id,
                    'product_name' => $genealogy->workOrder->product->name ?? 'Unknown',
                    'quantity_produced' => $genealogy->workOrder->quantity_completed,
                    'production_date' => $genealogy->workOrder->actual_end_date,
                ];
            }
        }

        return collect($details);
    }

    private function recursiveTraceForward(
        string $lotNumber,
        array &$impactedLots,
        array &$processedLots,
        int $depth = 0
    ): void {
        // Prevent infinite loops
        if ($depth > 50 || in_array($lotNumber, $processedLots)) {
            return;
        }

        $processedLots[] = $lotNumber;

        // Find lots produced using this lot
        $downstreamLots = $this->traceForward($lotNumber);

        foreach ($downstreamLots as $downstream) {
            $finishedLot = $downstream['finished_good_lot'];
            
            if (!in_array($finishedLot, $impactedLots)) {
                $impactedLots[] = $finishedLot;
                
                // Recursively trace this lot forward
                $this->recursiveTraceForward($finishedLot, $impactedLots, $processedLots, $depth + 1);
            }
        }
    }

    private function calculateRecallScope(array $impactedLots): array
    {
        $totalQuantity = 0;
        $productBreakdown = [];

        foreach ($impactedLots as $lot) {
            $totalQuantity += $lot['quantity_produced'];
            
            $productId = $lot['product_id'];
            if (!isset($productBreakdown[$productId])) {
                $productBreakdown[$productId] = [
                    'product_name' => $lot['product_name'],
                    'lot_count' => 0,
                    'total_quantity' => 0,
                ];
            }
            
            $productBreakdown[$productId]['lot_count']++;
            $productBreakdown[$productId]['total_quantity'] += $lot['quantity_produced'];
        }

        return [
            'total_quantity' => $totalQuantity,
            'unique_products' => count($productBreakdown),
            'product_breakdown' => array_values($productBreakdown),
        ];
    }
}
