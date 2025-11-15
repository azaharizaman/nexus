<?php

declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use Illuminate\Support\Str;
use Nexus\Hrm\Models\Training;
use Nexus\Hrm\Models\TrainingEnrollment;
use Nexus\Hrm\Services\TrainingService;

class TrainingServiceTest extends \Nexus\Hrm\Tests\TestCase
{
    public function test_can_create_training(): void
    {
        $tenant = (string) Str::ulid();
        $creator = (string) Str::ulid();

        $service = new TrainingService();
        $training = $service->createTraining(
            $tenant,
            'Laravel Advanced Development',
            'Advanced Laravel concepts and best practices',
            'Technical',
            'classroom',
            16.0,
            'Internal Training Team',
            500.00,
            20,
            ['Basic PHP knowledge', 'Laravel fundamentals'],
            ['Master advanced Laravel features', 'Implement best practices'],
            [['name' => 'Course Materials', 'type' => 'pdf']],
            $creator
        );

        $this->assertInstanceOf(Training::class, $training);
        $this->assertEquals($tenant, $training->tenant_id);
        $this->assertEquals('Laravel Advanced Development', $training->title);
        $this->assertEquals('Technical', $training->category);
        $this->assertEquals('classroom', $training->training_type);
        $this->assertEquals(16.0, $training->duration_hours);
        $this->assertEquals(500.00, $training->cost);
        $this->assertEquals(20, $training->max_participants);
        $this->assertTrue($training->is_active);
    }

    public function test_can_enroll_employee_in_training(): void
    {
        $tenant = (string) Str::ulid();
        $employee = (string) Str::ulid();

        $service = new TrainingService();
        $training = $service->createTraining(
            $tenant,
            'Safety Training',
            'Workplace safety procedures',
            'Safety',
            'online',
            4.0
        );

        $enrollment = $service->enrollEmployee(
            $tenant,
            $employee,
            $training->id,
            '2025-12-01'
        );

        $this->assertInstanceOf(TrainingEnrollment::class, $enrollment);
        $this->assertEquals($tenant, $enrollment->tenant_id);
        $this->assertEquals($employee, $enrollment->employee_id);
        $this->assertEquals($training->id, $enrollment->training_id);
        $this->assertEquals('enrolled', $enrollment->status);
        $this->assertEquals('2025-12-01', $enrollment->scheduled_date->toDateString());
    }

    public function test_cannot_enroll_when_training_at_capacity(): void
    {
        $tenant = (string) Str::ulid();
        $employee1 = (string) Str::ulid();
        $employee2 = (string) Str::ulid();

        $service = new TrainingService();
        $training = $service->createTraining(
            $tenant,
            'Small Group Training',
            'Limited capacity training',
            'Technical',
            'workshop',
            8.0,
            null,
            0,
            1 // Only 1 spot available
        );

        // First enrollment should work
        $service->enrollEmployee($tenant, $employee1, $training->id);

        // Second enrollment should fail
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Training is at maximum capacity');

        $service->enrollEmployee($tenant, $employee2, $training->id);
    }

    public function test_can_complete_training(): void
    {
        $tenant = (string) Str::ulid();
        $employee = (string) Str::ulid();

        $service = new TrainingService();
        $training = $service->createTraining(
            $tenant,
            'Certification Course',
            'Professional certification training',
            'Certification',
            'online',
            40.0
        );

        $enrollment = $service->enrollEmployee($tenant, $employee, $training->id);

        $completed = $service->completeTraining(
            $enrollment->id,
            '2025-11-20',
            85.5,
            'Excellent course with practical applications',
            true,
            'CERT-12345',
            '2027-11-20'
        );

        $this->assertEquals('completed', $completed->status);
        $this->assertEquals('2025-11-20', $completed->completion_date->toDateString());
        $this->assertEquals(85.5, $completed->score);
        $this->assertEquals('Excellent course with practical applications', $completed->feedback);
        $this->assertTrue($completed->certificate_issued);
        $this->assertEquals('CERT-12345', $completed->certificate_number);
        $this->assertEquals('2027-11-20', $completed->certificate_expiry->toDateString());
    }

    public function test_can_get_employee_enrollments(): void
    {
        $tenant = (string) Str::ulid();
        $employee = (string) Str::ulid();

        $service = new TrainingService();

        $training1 = $service->createTraining($tenant, 'Training A', 'Description A', 'Technical', 'online', 8.0);
        $training2 = $service->createTraining($tenant, 'Training B', 'Description B', 'Leadership', 'classroom', 16.0);

        $service->enrollEmployee($tenant, $employee, $training1->id);
        $service->enrollEmployee($tenant, $employee, $training2->id);

        $enrollments = $service->getEmployeeEnrollments($tenant, $employee);

        $this->assertCount(2, $enrollments);
        $titles = $enrollments->pluck('training.title')->sort();
        $this->assertEquals(['Training A', 'Training B'], $titles->values()->all());
    }

    public function test_can_generate_training_analytics(): void
    {
        $tenant = (string) Str::ulid();
        $employee1 = (string) Str::ulid();
        $employee2 = (string) Str::ulid();

        $service = new TrainingService();

        // Create trainings
        $training1 = $service->createTraining($tenant, 'Safety Training', 'Safety procedures', 'Safety', 'online', 4.0);
        $training2 = $service->createTraining($tenant, 'Leadership Training', 'Leadership skills', 'Leadership', 'classroom', 16.0);

        // Create enrollments
        $enrollment1 = $service->enrollEmployee($tenant, $employee1, $training1->id);
        $enrollment2 = $service->enrollEmployee($tenant, $employee2, $training2->id);

        // Complete one enrollment
        $service->completeTraining($enrollment1->id, '2025-11-15', 90.0, 'Great training', true);

        $analytics = $service->generateTrainingAnalytics($tenant);

        $this->assertEquals(2, $analytics['total_trainings']);
        $this->assertEquals(2, $analytics['active_trainings']);
        $this->assertEquals(2, $analytics['total_enrollments']);
        $this->assertEquals(1, $analytics['completed_enrollments']);
        $this->assertEquals(50.0, $analytics['completion_rate']);
        $this->assertEquals(90.0, $analytics['average_score']);
        $this->assertArrayHasKey('enrolled', $analytics['enrollments_by_status']);
        $this->assertArrayHasKey('completed', $analytics['enrollments_by_status']);
    }

    public function test_can_cancel_enrollment(): void
    {
        $tenant = (string) Str::ulid();
        $employee = (string) Str::ulid();

        $service = new TrainingService();
        $training = $service->createTraining($tenant, 'Cancelled Training', 'Will be cancelled', 'Technical', 'online', 8.0);

        $enrollment = $service->enrollEmployee($tenant, $employee, $training->id);

        $cancelled = $service->cancelEnrollment($enrollment->id, 'Employee requested cancellation');

        $this->assertEquals('cancelled', $cancelled->status);
        $this->assertEquals('Employee requested cancellation', $cancelled->notes);
    }

    public function test_cannot_cancel_completed_enrollment(): void
    {
        $tenant = (string) Str::ulid();
        $employee = (string) Str::ulid();

        $service = new TrainingService();
        $training = $service->createTraining($tenant, 'Completed Training', 'Already completed', 'Technical', 'online', 8.0);

        $enrollment = $service->enrollEmployee($tenant, $employee, $training->id);
        $service->completeTraining($enrollment->id, '2025-11-10', 85.0);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot cancel completed training enrollment');

        $service->cancelEnrollment($enrollment->id);
    }
}