<?php

declare(strict_types=1);

namespace Nexus\Hrm\Services;

use Illuminate\Support\Collection;
use Nexus\Hrm\Models\Training;
use Nexus\Hrm\Models\TrainingEnrollment;

class TrainingService
{
    /**
     * Create a new training program
     */
    public function createTraining(
        string $tenantId,
        string $title,
        string $description,
        string $category,
        string $trainingType,
        float $durationHours,
        ?string $provider = null,
        float $cost = 0,
        ?int $maxParticipants = null,
        ?array $prerequisites = null,
        ?array $objectives = null,
        ?array $materials = null,
        ?string $createdBy = null
    ): Training {
        return Training::create([
            'tenant_id' => $tenantId,
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'training_type' => $trainingType,
            'duration_hours' => $durationHours,
            'provider' => $provider,
            'cost' => $cost,
            'max_participants' => $maxParticipants,
            'prerequisites' => $prerequisites,
            'objectives' => $objectives,
            'materials' => $materials,
            'is_active' => true,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * Enroll an employee in training
     */
    public function enrollEmployee(
        string $tenantId,
        string $employeeId,
        string $trainingId,
        ?string $scheduledDate = null
    ): TrainingEnrollment {
        $training = Training::findOrFail($trainingId);

        if (!$training->hasAvailableSpots()) {
            throw new \RuntimeException('Training is at maximum capacity');
        }

        return TrainingEnrollment::create([
            'tenant_id' => $tenantId,
            'employee_id' => $employeeId,
            'training_id' => $trainingId,
            'enrolled_at' => now(),
            'scheduled_date' => $scheduledDate,
            'status' => 'enrolled',
        ]);
    }

    /**
     * Mark training as completed
     */
    public function completeTraining(
        string $enrollmentId,
        string $completionDate,
        ?float $score = null,
        ?string $feedback = null,
        ?bool $certificateIssued = false,
        ?string $certificateNumber = null,
        ?string $certificateExpiry = null
    ): TrainingEnrollment {
        $enrollment = TrainingEnrollment::findOrFail($enrollmentId);

        $enrollment->update([
            'completion_date' => $completionDate,
            'status' => 'completed',
            'score' => $score,
            'feedback' => $feedback,
            'certificate_issued' => $certificateIssued,
            'certificate_number' => $certificateNumber,
            'certificate_expiry' => $certificateExpiry,
        ]);

        return $enrollment;
    }

    /**
     * Get trainings for a tenant
     */
    public function getTenantTrainings(string $tenantId, ?string $category = null, ?string $type = null): Collection
    {
        $query = Training::forTenant($tenantId)->active();

        if ($category) {
            $query->inCategory($category);
        }

        if ($type) {
            $query->ofType($type);
        }

        return $query->orderBy('title')->get();
    }

    /**
     * Get employee enrollments
     */
    public function getEmployeeEnrollments(string $tenantId, string $employeeId): Collection
    {
        return TrainingEnrollment::forTenant($tenantId)
            ->forEmployee($employeeId)
            ->with(['training'])
            ->orderBy('enrolled_at', 'desc')
            ->get();
    }

    /**
     * Get training enrollments
     */
    public function getTrainingEnrollments(string $tenantId, string $trainingId): Collection
    {
        return TrainingEnrollment::forTenant($tenantId)
            ->forTraining($trainingId)
            ->with(['employee'])
            ->orderBy('enrolled_at')
            ->get();
    }

    /**
     * Generate training analytics
     */
    public function generateTrainingAnalytics(string $tenantId, ?int $year = null): array
    {
        $trainingsQuery = Training::forTenant($tenantId);
        $enrollmentsQuery = TrainingEnrollment::forTenant($tenantId);

        if ($year) {
            $enrollmentsQuery->whereYear('enrolled_at', $year);
        }

        $totalTrainings = $trainingsQuery->count();
        $activeTrainings = $trainingsQuery->active()->count();
        $enrollments = $enrollmentsQuery->get();

        $totalEnrollments = $enrollments->count();
        $completedEnrollments = $enrollments->where('status', 'completed')->count();
        $completionRate = $totalEnrollments > 0 ? round(($completedEnrollments / $totalEnrollments) * 100, 2) : 0;

        $enrollmentsByStatus = $enrollments->groupBy('status')->map->count();
        $trainingsByCategory = $trainingsQuery->get()->groupBy('category')->map->count();
        $trainingsByType = $trainingsQuery->get()->groupBy('training_type')->map->count();

        $averageScore = $enrollments->where('status', 'completed')->whereNotNull('score')->avg('score');

        return [
            'total_trainings' => $totalTrainings,
            'active_trainings' => $activeTrainings,
            'total_enrollments' => $totalEnrollments,
            'completed_enrollments' => $completedEnrollments,
            'completion_rate' => $completionRate,
            'average_score' => $averageScore ? round($averageScore, 2) : null,
            'enrollments_by_status' => $enrollmentsByStatus,
            'trainings_by_category' => $trainingsByCategory,
            'trainings_by_type' => $trainingsByType,
        ];
    }

    /**
     * Get expiring certificates
     */
    public function getExpiringCertificates(string $tenantId, int $daysAhead = 30): Collection
    {
        return TrainingEnrollment::forTenant($tenantId)
            ->where('certificate_issued', true)
            ->whereNotNull('certificate_expiry')
            ->where('certificate_expiry', '>=', now())
            ->where('certificate_expiry', '<=', now()->addDays($daysAhead))
            ->with(['employee', 'training'])
            ->orderBy('certificate_expiry')
            ->get();
    }

    /**
     * Get expired certificates
     */
    public function getExpiredCertificates(string $tenantId): Collection
    {
        return TrainingEnrollment::forTenant($tenantId)
            ->where('certificate_issued', true)
            ->whereNotNull('certificate_expiry')
            ->where('certificate_expiry', '<', now())
            ->with(['employee', 'training'])
            ->orderBy('certificate_expiry', 'desc')
            ->get();
    }

    /**
     * Cancel enrollment
     */
    public function cancelEnrollment(string $enrollmentId, ?string $notes = null): TrainingEnrollment
    {
        $enrollment = TrainingEnrollment::findOrFail($enrollmentId);

        if ($enrollment->status === 'completed') {
            throw new \RuntimeException('Cannot cancel completed training enrollment');
        }

        $enrollment->update([
            'status' => 'cancelled',
            'notes' => $notes,
        ]);

        return $enrollment;
    }
}