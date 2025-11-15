<?php

declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use Illuminate\Support\Str;
use Nexus\Hrm\Models\PerformanceCycle;
use Nexus\Hrm\Models\PerformanceReview;
use Nexus\Hrm\Models\PerformanceTemplate;
use Nexus\Hrm\Services\PerformanceService;

class PerformanceServiceTest extends \Nexus\Hrm\Tests\TestCase
{
    public function test_can_create_performance_cycle(): void
    {
        $tenant = (string) Str::ulid();

        $service = new PerformanceService();
        $cycle = $service->createPerformanceCycle(
            $tenant,
            'Q4 2025 Review',
            'Quarterly performance review cycle',
            '2025-10-01',
            '2025-12-31',
            'quarterly',
            true,
            30,
            7
        );

        $this->assertInstanceOf(PerformanceCycle::class, $cycle);
        $this->assertEquals($tenant, $cycle->tenant_id);
        $this->assertEquals('Q4 2025 Review', $cycle->name);
        $this->assertEquals('quarterly', $cycle->frequency);
        $this->assertEquals('draft', $cycle->status);
        $this->assertTrue($cycle->auto_schedule_reviews);
    }

    public function test_can_create_performance_review(): void
    {
        $tenant = (string) Str::ulid();
        $employee = (string) Str::ulid();
        $reviewer = (string) Str::ulid();
        $cycle = (string) Str::ulid();

        $service = new PerformanceService();
        $review = $service->createPerformanceReview(
            $tenant,
            $employee,
            $reviewer,
            $cycle,
            null,
            '2025-11-15'
        );

        $this->assertInstanceOf(PerformanceReview::class, $review);
        $this->assertEquals($tenant, $review->tenant_id);
        $this->assertEquals($employee, $review->employee_id);
        $this->assertEquals($reviewer, $review->reviewer_id);
        $this->assertEquals($cycle, $review->performance_cycle_id);
        $this->assertEquals('2025-11-15', $review->review_date->toDateString());
        $this->assertEquals('draft', $review->status);
    }

    public function test_can_submit_performance_review(): void
    {
        $tenant = (string) Str::ulid();
        $employee = (string) Str::ulid();
        $reviewer = (string) Str::ulid();
        $cycle = (string) Str::ulid();

        $service = new PerformanceService();
        $review = $service->createPerformanceReview(
            $tenant,
            $employee,
            $reviewer,
            $cycle,
            null,
            '2025-11-15'
        );

        $scores = [
            ['kpi' => 'Quality', 'score' => 4, 'weight' => 25, 'comments' => 'Good work'],
            ['kpi' => 'Productivity', 'score' => 5, 'weight' => 25, 'comments' => 'Excellent'],
            ['kpi' => 'Teamwork', 'score' => 3, 'weight' => 25, 'comments' => 'Needs improvement'],
        ];

        $submitted = $service->submitPerformanceReview(
            $review->id,
            $scores,
            'Overall good performance with room for improvement in teamwork.',
            'Thank you for the feedback.',
            [['goal' => 'Improve teamwork', 'progress' => 75, 'assessment' => 'Making progress']],
            [['area' => 'Communication', 'action' => 'Attend workshop', 'timeline' => 'Next quarter']]
        );

        $this->assertEquals('completed', $submitted->status);
        $this->assertEquals(4.0, $submitted->overall_rating); // (4*25 + 5*25 + 3*25) / 75 = 12/3 = 4.0
        $this->assertEquals('Overall good performance with room for improvement in teamwork.', $submitted->reviewer_comments);
        $this->assertEquals('Thank you for the feedback.', $submitted->employee_comments);
        $this->assertNotNull($submitted->scores);
        $this->assertNotNull($submitted->goals_assessment);
        $this->assertNotNull($submitted->development_plan);
    }

    public function test_can_get_employee_reviews(): void
    {
        $tenant = (string) Str::ulid();
        $employee = (string) Str::ulid();
        $reviewer = (string) Str::ulid();
        $cycle = (string) Str::ulid();

        $service = new PerformanceService();

        // Create multiple reviews
        $service->createPerformanceReview($tenant, $employee, $reviewer, $cycle, null, '2025-06-15');
        $service->createPerformanceReview($tenant, $employee, $reviewer, $cycle, null, '2025-11-15');

        $reviews = $service->getEmployeeReviews($tenant, $employee);

        $this->assertCount(2, $reviews);
        $this->assertEquals('2025-11-15', $reviews->first()->review_date->toDateString());
        $this->assertEquals('2025-06-15', $reviews->last()->review_date->toDateString());
    }

    public function test_can_generate_performance_analytics(): void
    {
        $tenant = (string) Str::ulid();
        $employee1 = (string) Str::ulid();
        $employee2 = (string) Str::ulid();
        $reviewer = (string) Str::ulid();
        $cycle = (string) Str::ulid();

        $service = new PerformanceService();

        // Create completed reviews
        $review1 = $service->createPerformanceReview($tenant, $employee1, $reviewer, $cycle, null, '2025-11-15');
        $service->submitPerformanceReview($review1->id, [
            ['kpi' => 'Quality', 'score' => 5, 'weight' => 50],
            ['kpi' => 'Productivity', 'score' => 4, 'weight' => 50],
        ], 'Excellent work', null, null, null);

        $review2 = $service->createPerformanceReview($tenant, $employee2, $reviewer, $cycle, null, '2025-11-15');
        $service->submitPerformanceReview($review2->id, [
            ['kpi' => 'Quality', 'score' => 3, 'weight' => 50],
            ['kpi' => 'Productivity', 'score' => 3, 'weight' => 50],
        ], 'Good work', null, null, null);

        $analytics = $service->generatePerformanceAnalytics($tenant);

        $this->assertEquals(2, $analytics['total_reviews']);
        $this->assertEquals(3.75, $analytics['average_rating']); // (4.5 + 3.0) / 2
        $this->assertArrayHasKey('rating_distribution', $analytics);
        $this->assertArrayHasKey('top_performers', $analytics);
        $this->assertArrayHasKey('needs_improvement_count', $analytics);
        $this->assertGreaterThan(0, $analytics['completion_rate']);
    }
}