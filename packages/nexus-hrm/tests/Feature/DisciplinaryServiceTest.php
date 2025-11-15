<?php

declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use Illuminate\Support\Str;
use Nexus\Hrm\Models\DisciplinaryCase;
use Nexus\Hrm\Services\DisciplinaryService;

class DisciplinaryServiceTest extends \Nexus\Hrm\Tests\TestCase
{
    public function test_can_create_disciplinary_case(): void
    {
        $tenant = (string) Str::ulid();
        $employee = (string) Str::ulid();
        $handler = (string) Str::ulid();

        $service = new DisciplinaryService();
        $case = $service->createCase(
            $tenant,
            $employee,
            'written_warning',
            'moderate',
            'Violation of company policy',
            '2025-11-10',
            '2025-11-11',
            $handler
        );

        $this->assertInstanceOf(DisciplinaryCase::class, $case);
        $this->assertEquals($tenant, $case->tenant_id);
        $this->assertEquals($employee, $case->employee_id);
        $this->assertEquals('written_warning', $case->case_type);
        $this->assertEquals('moderate', $case->severity);
        $this->assertEquals('investigating', $case->status);
        $this->assertEquals($handler, $case->handler_id);
    }

    public function test_can_resolve_disciplinary_case(): void
    {
        $tenant = (string) Str::ulid();
        $employee = (string) Str::ulid();

        $service = new DisciplinaryService();
        $case = $service->createCase(
            $tenant,
            $employee,
            'verbal_warning',
            'minor',
            'Minor policy violation',
            '2025-11-01',
            '2025-11-02'
        );

        $resolved = $service->resolveCase(
            $case->id,
            'Employee has been counseled and understands the policy',
            '2025-11-15',
            true,
            '2025-12-15'
        );

        $this->assertEquals('resolved', $resolved->status);
        $this->assertEquals('Employee has been counseled and understands the policy', $resolved->resolution);
        $this->assertEquals('2025-11-15', $resolved->resolution_date->toDateString());
        $this->assertTrue($resolved->follow_up_required);
        $this->assertEquals('2025-12-15', $resolved->follow_up_date->toDateString());
    }

    public function test_can_add_documents_to_case(): void
    {
        $tenant = (string) Str::ulid();
        $employee = (string) Str::ulid();

        $service = new DisciplinaryService();
        $case = $service->createCase(
            $tenant,
            $employee,
            'performance_improvement',
            'moderate',
            'Performance issues identified',
            '2025-10-01',
            '2025-10-05'
        );

        $documents = [
            ['name' => 'performance_review.pdf', 'url' => 'https://example.com/docs/review.pdf'],
            ['name' => 'improvement_plan.pdf', 'url' => 'https://example.com/docs/plan.pdf'],
        ];

        $updated = $service->addDocuments($case->id, $documents);

        $this->assertEquals($documents, $updated->documents);
    }

    public function test_can_get_employee_cases(): void
    {
        $tenant = (string) Str::ulid();
        $employee = (string) Str::ulid();

        $service = new DisciplinaryService();

        // Create multiple cases
        $service->createCase($tenant, $employee, 'verbal_warning', 'minor', 'First warning', '2025-08-01', '2025-08-02');
        $service->createCase($tenant, $employee, 'written_warning', 'moderate', 'Second warning', '2025-10-01', '2025-10-02');

        $cases = $service->getEmployeeCases($tenant, $employee);

        $this->assertCount(2, $cases);
        $this->assertEquals('written_warning', $cases->first()->case_type);
        $this->assertEquals('verbal_warning', $cases->last()->case_type);
    }

    public function test_can_generate_disciplinary_analytics(): void
    {
        $tenant = (string) Str::ulid();
        $employee1 = (string) Str::ulid();
        $employee2 = (string) Str::ulid();

        $service = new DisciplinaryService();

        // Create cases
        $case1 = $service->createCase($tenant, $employee1, 'verbal_warning', 'minor', 'Minor issue', '2025-01-01', '2025-01-02');
        $case2 = $service->createCase($tenant, $employee2, 'written_warning', 'moderate', 'Moderate issue', '2025-02-01', '2025-02-02');
        $service->resolveCase($case1->id, 'Resolved', '2025-01-15');
        $service->resolveCase($case2->id, 'Resolved with follow-up', '2025-02-15', true, '2025-05-15');

        $analytics = $service->generateDisciplinaryAnalytics($tenant);

        $this->assertEquals(2, $analytics['total_cases']);
        $this->assertEquals(0, $analytics['open_cases']);
        $this->assertEquals(2, $analytics['resolved_cases']);
        $this->assertEquals(100.0, $analytics['resolution_rate']);
        $this->assertArrayHasKey('verbal_warning', $analytics['cases_by_type']);
        $this->assertArrayHasKey('written_warning', $analytics['cases_by_type']);
        $this->assertEquals(1, $analytics['follow_up_required']);
    }
}