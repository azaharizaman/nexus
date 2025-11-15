<?php

declare(strict_types=1);

namespace Nexus\Hrm\Services;

use Illuminate\Support\Collection;
use Nexus\Hrm\Models\DisciplinaryCase;

class DisciplinaryService
{
    /**
     * Create a new disciplinary case
     */
    public function createCase(
        string $tenantId,
        string $employeeId,
        string $caseType,
        string $severity,
        string $description,
        string $incidentDate,
        string $reportedDate,
        ?string $handlerId = null
    ): DisciplinaryCase {
        return DisciplinaryCase::create([
            'tenant_id' => $tenantId,
            'employee_id' => $employeeId,
            'case_type' => $caseType,
            'severity' => $severity,
            'description' => $description,
            'incident_date' => $incidentDate,
            'reported_date' => $reportedDate,
            'handler_id' => $handlerId,
            'status' => 'investigating',
        ]);
    }

    /**
     * Update case status and resolution
     */
    public function resolveCase(
        string $caseId,
        string $resolution,
        string $resolutionDate,
        ?bool $followUpRequired = false,
        ?string $followUpDate = null
    ): DisciplinaryCase {
        $case = DisciplinaryCase::findOrFail($caseId);

        $case->update([
            'resolution' => $resolution,
            'resolution_date' => $resolutionDate,
            'status' => 'resolved',
            'follow_up_required' => $followUpRequired,
            'follow_up_date' => $followUpDate,
        ]);

        return $case;
    }

    /**
     * Add documents to a case
     */
    public function addDocuments(string $caseId, array $documents): DisciplinaryCase
    {
        $case = DisciplinaryCase::findOrFail($caseId);

        $existingDocuments = $case->documents ?? [];
        $case->update([
            'documents' => array_merge($existingDocuments, $documents),
        ]);

        return $case;
    }

    /**
     * Add witnesses to a case
     */
    public function addWitnesses(string $caseId, array $witnesses): DisciplinaryCase
    {
        $case = DisciplinaryCase::findOrFail($caseId);

        $existingWitnesses = $case->witnesses ?? [];
        $case->update([
            'witnesses' => array_merge($existingWitnesses, $witnesses),
        ]);

        return $case;
    }

    /**
     * Get cases for an employee
     */
    public function getEmployeeCases(string $tenantId, string $employeeId): Collection
    {
        return DisciplinaryCase::forTenant($tenantId)
            ->forEmployee($employeeId)
            ->with(['handler'])
            ->orderBy('incident_date', 'desc')
            ->get();
    }

    /**
     * Get cases handled by a specific handler
     */
    public function getHandlerCases(string $tenantId, string $handlerId): Collection
    {
        return DisciplinaryCase::forTenant($tenantId)
            ->where('handler_id', $handlerId)
            ->with(['employee'])
            ->orderBy('reported_date', 'desc')
            ->get();
    }

    /**
     * Get disciplinary analytics for a tenant
     */
    public function generateDisciplinaryAnalytics(string $tenantId, ?int $year = null): array
    {
        $query = DisciplinaryCase::forTenant($tenantId);

        if ($year) {
            $query->whereYear('incident_date', $year);
        }

        $cases = $query->get();

        $totalCases = $cases->count();
        $openCases = $cases->where('status', '!=', 'resolved')->where('status', '!=', 'dismissed')->count();
        $resolvedCases = $cases->where('status', 'resolved')->count();

        $casesByType = $cases->groupBy('case_type')->map->count();
        $casesBySeverity = $cases->groupBy('severity')->map->count();

        $followUpRequired = $cases->where('follow_up_required', true)->count();

        return [
            'total_cases' => $totalCases,
            'open_cases' => $openCases,
            'resolved_cases' => $resolvedCases,
            'resolution_rate' => $totalCases > 0 ? round(($resolvedCases / $totalCases) * 100, 2) : 0,
            'cases_by_type' => $casesByType,
            'cases_by_severity' => $casesBySeverity,
            'follow_up_required' => $followUpRequired,
        ];
    }

    /**
     * Get cases requiring follow-up
     */
    public function getCasesRequiringFollowUp(string $tenantId): Collection
    {
        return DisciplinaryCase::forTenant($tenantId)
            ->where('follow_up_required', true)
            ->where('follow_up_date', '>=', now())
            ->where('status', 'resolved')
            ->with(['employee', 'handler'])
            ->orderBy('follow_up_date')
            ->get();
    }

    /**
     * Get overdue follow-ups
     */
    public function getOverdueFollowUps(string $tenantId): Collection
    {
        return DisciplinaryCase::forTenant($tenantId)
            ->where('follow_up_required', true)
            ->where('follow_up_date', '<', now())
            ->where('status', 'resolved')
            ->with(['employee', 'handler'])
            ->orderBy('follow_up_date')
            ->get();
    }
}