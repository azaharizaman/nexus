<?php

declare(strict_types=1);

namespace Nexus\Hrm\Services;

use Illuminate\Support\Collection;
use Nexus\Hrm\Models\PerformanceCycle;
use Nexus\Hrm\Models\PerformanceReview;
use Nexus\Hrm\Models\PerformanceTemplate;

class PerformanceService
{
    /**
     * Create a new performance cycle
     */
    public function createPerformanceCycle(
        string $tenantId,
        string $name,
        string $description,
        string $startDate,
        string $endDate,
        string $frequency,
        bool $autoScheduleReviews = false,
        int $reviewDeadlineDays = 30,
        int $reminderDaysBefore = 7
    ): PerformanceCycle {
        return PerformanceCycle::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'description' => $description,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'frequency' => $frequency,
            'status' => 'draft',
            'auto_schedule_reviews' => $autoScheduleReviews,
            'review_deadline_days' => $reviewDeadlineDays,
            'reminder_days_before' => $reminderDaysBefore,
        ]);
    }

    /**
     * Create a performance review
     */
    public function createPerformanceReview(
        string $tenantId,
        string $employeeId,
        string $reviewerId,
        string $performanceCycleId,
        ?string $reviewTemplateId = null,
        string $reviewDate
    ): PerformanceReview {
        return PerformanceReview::create([
            'tenant_id' => $tenantId,
            'employee_id' => $employeeId,
            'reviewer_id' => $reviewerId,
            'performance_cycle_id' => $performanceCycleId,
            'review_template_id' => $reviewTemplateId,
            'review_date' => $reviewDate,
            'status' => 'draft',
        ]);
    }

    /**
     * Submit performance review with scores and comments
     */
    public function submitPerformanceReview(
        string $reviewId,
        array $scores,
        string $reviewerComments,
        ?string $employeeComments = null,
        ?array $goalsAssessment = null,
        ?array $developmentPlan = null,
        ?string $nextReviewDate = null
    ): PerformanceReview {
        $review = PerformanceReview::findOrFail($reviewId);

        $overallRating = $this->calculateOverallRating($scores);

        $review->update([
            'scores' => $scores,
            'reviewer_comments' => $reviewerComments,
            'employee_comments' => $employeeComments,
            'goals_assessment' => $goalsAssessment,
            'development_plan' => $developmentPlan,
            'overall_rating' => $overallRating,
            'next_review_date' => $nextReviewDate,
            'status' => 'completed',
        ]);

        return $review;
    }

    /**
     * Get performance reviews for an employee
     */
    public function getEmployeeReviews(string $tenantId, string $employeeId): Collection
    {
        return PerformanceReview::forTenant($tenantId)
            ->forEmployee($employeeId)
            ->with(['performanceCycle', 'reviewTemplate', 'reviewer'])
            ->orderBy('review_date', 'desc')
            ->get();
    }

    /**
     * Get performance reviews for a reviewer (manager)
     */
    public function getReviewerReviews(string $tenantId, string $reviewerId): Collection
    {
        return PerformanceReview::forTenant($tenantId)
            ->where('reviewer_id', $reviewerId)
            ->with(['employee', 'performanceCycle', 'reviewTemplate'])
            ->orderBy('review_date', 'desc')
            ->get();
    }

    /**
     * Generate performance analytics for a tenant
     */
    public function generatePerformanceAnalytics(string $tenantId, ?string $cycleId = null): array
    {
        $query = PerformanceReview::forTenant($tenantId)->withStatus('completed');

        if ($cycleId) {
            $query->where('performance_cycle_id', $cycleId);
        }

        $reviews = $query->get();

        $totalReviews = $reviews->count();
        $averageRating = $reviews->avg('overall_rating');
        $ratingDistribution = $this->calculateRatingDistribution($reviews);

        $topPerformers = $reviews->sortByDesc('overall_rating')->take(10);
        $needsImprovement = $reviews->where('overall_rating', '<', 3.0);

        return [
            'total_reviews' => $totalReviews,
            'average_rating' => round($averageRating, 2),
            'rating_distribution' => $ratingDistribution,
            'top_performers' => $topPerformers->pluck('employee.name', 'overall_rating'),
            'needs_improvement_count' => $needsImprovement->count(),
            'completion_rate' => $this->calculateCompletionRate($tenantId, $cycleId),
        ];
    }

    /**
     * Calculate overall rating from scores
     */
    private function calculateOverallRating(array $scores): float
    {
        if (empty($scores)) {
            return 0.0;
        }

        $totalWeightedScore = 0;
        $totalWeight = 0;

        foreach ($scores as $score) {
            $weight = $score['weight'] ?? 1;
            $totalWeightedScore += $score['score'] * $weight;
            $totalWeight += $weight;
        }

        return $totalWeight > 0 ? round($totalWeightedScore / $totalWeight, 2) : 0.0;
    }

    /**
     * Calculate rating distribution
     */
    private function calculateRatingDistribution(Collection $reviews): array
    {
        $distribution = [
            'excellent' => 0, // 4.5-5.0
            'good' => 0,      // 3.5-4.4
            'average' => 0,   // 2.5-3.4
            'below_average' => 0, // 1.5-2.4
            'poor' => 0,      // 0-1.4
        ];

        foreach ($reviews as $review) {
            $rating = $review->overall_rating;

            if ($rating >= 4.5) {
                $distribution['excellent']++;
            } elseif ($rating >= 3.5) {
                $distribution['good']++;
            } elseif ($rating >= 2.5) {
                $distribution['average']++;
            } elseif ($rating >= 1.5) {
                $distribution['below_average']++;
            } else {
                $distribution['poor']++;
            }
        }

        return $distribution;
    }

    /**
     * Calculate completion rate for reviews
     */
    private function calculateCompletionRate(string $tenantId, ?string $cycleId = null): float
    {
        $query = PerformanceReview::forTenant($tenantId);

        if ($cycleId) {
            $query->where('performance_cycle_id', $cycleId);
        }

        $total = $query->count();
        $completed = $query->withStatus('completed')->count();

        return $total > 0 ? round(($completed / $total) * 100, 2) : 0.0;
    }
}