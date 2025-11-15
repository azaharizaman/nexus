<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Services;

use Nexus\Manufacturing\Contracts\QualityManagementServiceContract;
use Nexus\Manufacturing\Contracts\QualityInspectionRepositoryContract;
use Nexus\Manufacturing\Contracts\WorkOrderRepositoryContract;
use Nexus\Manufacturing\Models\QualityInspection;
use Nexus\Manufacturing\Models\InspectionMeasurement;
use Nexus\Manufacturing\Enums\InspectionResult;
use Nexus\Manufacturing\Enums\DispositionType;
use InvalidArgumentException;

class QualityManagementService implements QualityManagementServiceContract
{
    public function __construct(
        private readonly QualityInspectionRepositoryContract $inspectionRepository,
        private readonly WorkOrderRepositoryContract $workOrderRepository
    ) {}

    public function performInspection(
        string $workOrderId,
        string $lotNumber,
        array $measurements
    ): QualityInspection {
        $workOrder = $this->workOrderRepository->find($workOrderId);
        if (!$workOrder) {
            throw new InvalidArgumentException("Work order not found: {$workOrderId}");
        }

        // Create inspection record
        $inspection = QualityInspection::create([
            'work_order_id' => $workOrderId,
            'lot_number' => $lotNumber,
            'inspection_plan_id' => $workOrder->product->inspectionPlans()->first()?->id,
            'inspector_id' => auth()->id(),
            'inspection_date' => now(),
            'result' => InspectionResult::PASSED, // Will be updated based on measurements
        ]);

        $allPassed = true;

        // Record measurements
        foreach ($measurements as $measurement) {
            $characteristic = $inspection->inspectionPlan->characteristics()
                ->find($measurement['characteristic_id']);

            if (!$characteristic) {
                continue;
            }

            // Determine if measurement passes
            $passes = $this->checkMeasurementPasses(
                $measurement['measured_value'],
                $characteristic->lower_limit,
                $characteristic->upper_limit,
                $characteristic->target_value
            );

            if (!$passes) {
                $allPassed = false;
            }

            InspectionMeasurement::create([
                'quality_inspection_id' => $inspection->id,
                'inspection_characteristic_id' => $characteristic->id,
                'measured_value' => $measurement['measured_value'],
                'passes' => $passes,
                'notes' => $measurement['notes'] ?? null,
            ]);
        }

        // Update overall inspection result
        $inspection->update([
            'result' => $allPassed ? InspectionResult::PASSED : InspectionResult::FAILED,
        ]);

        return $inspection->fresh('measurements');
    }

    public function setDisposition(
        string $inspectionId,
        string $disposition,
        string $notes = ''
    ): QualityInspection {
        $inspection = $this->inspectionRepository->find($inspectionId);
        if (!$inspection) {
            throw new InvalidArgumentException("Inspection not found: {$inspectionId}");
        }

        $dispositionType = DispositionType::from($disposition);

        if ($inspection->result === InspectionResult::PASSED && $dispositionType !== DispositionType::ACCEPT) {
            throw new InvalidArgumentException("Passed inspections can only have Accept disposition");
        }

        $inspection->update([
            'disposition' => $dispositionType,
            'disposition_date' => now(),
            'disposition_notes' => $notes,
        ]);

        // If disposition is quarantine, mark lot as quarantined
        if ($dispositionType === DispositionType::QUARANTINE) {
            $this->quarantineLot($inspection->lot_number, $notes);
        }

        return $inspection->fresh();
    }

    public function quarantineLot(string $lotNumber, string $reason): array
    {
        // Update all inspections for this lot
        $inspections = $this->inspectionRepository->getByLotNumber($lotNumber);
        
        $updatedInspections = [];
        foreach ($inspections as $inspection) {
            if (!$inspection->isQuarantined()) {
                $inspection->update([
                    'disposition' => DispositionType::QUARANTINE,
                    'disposition_date' => now(),
                    'disposition_notes' => $reason,
                ]);
                $updatedInspections[] = $inspection->fresh();
            }
        }

        // In production, would also:
        // - Update inventory status to quarantined
        // - Block lot from being used/shipped
        // - Create quarantine notification/alert

        return [
            'lot_number' => $lotNumber,
            'quarantine_date' => now()->toDateTimeString(),
            'reason' => $reason,
            'affected_inspections' => count($updatedInspections),
        ];
    }

    public function releaseQuarantine(string $lotNumber, string $approvedBy): bool
    {
        $inspections = $this->inspectionRepository->getByLotNumber($lotNumber);
        
        foreach ($inspections as $inspection) {
            if ($inspection->isQuarantined()) {
                // Only release if there's a valid non-quarantine disposition
                if ($inspection->result === InspectionResult::PASSED) {
                    $inspection->update([
                        'disposition' => DispositionType::ACCEPT,
                        'disposition_date' => now(),
                        'disposition_notes' => "Released by: {$approvedBy}",
                    ]);
                } else {
                    throw new InvalidArgumentException("Cannot release failed inspection without proper disposition");
                }
            }
        }

        // In production, would also:
        // - Update inventory status to available
        // - Create release notification

        return true;
    }

    public function getQualityMetrics(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        // Get all inspections within date range
        $inspections = QualityInspection::query()
            ->whereBetween('inspection_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        $totalInspections = $inspections->count();
        $passedInspections = $inspections->where('result', InspectionResult::PASSED)->count();
        $failedInspections = $inspections->where('result', InspectionResult::FAILED)->count();

        $quarantined = $inspections->where('disposition', DispositionType::QUARANTINE)->count();
        $rejected = $inspections->where('disposition', DispositionType::REJECT)->count();
        $rework = $inspections->where('disposition', DispositionType::REWORK)->count();

        return [
            'total_inspections' => $totalInspections,
            'passed' => $passedInspections,
            'failed' => $failedInspections,
            'pass_rate' => $totalInspections > 0 ? round(($passedInspections / $totalInspections) * 100, 2) : 0,
            'quarantined' => $quarantined,
            'rejected' => $rejected,
            'rework' => $rework,
            'first_pass_yield' => $totalInspections > 0 
                ? round((($passedInspections - $rework) / $totalInspections) * 100, 2) 
                : 0,
        ];
    }

    private function checkMeasurementPasses(
        float $measuredValue,
        ?float $lowerLimit,
        ?float $upperLimit,
        ?float $targetValue
    ): bool {
        if ($lowerLimit !== null && $measuredValue < $lowerLimit) {
            return false;
        }

        if ($upperLimit !== null && $measuredValue > $upperLimit) {
            return false;
        }

        return true;
    }
}
