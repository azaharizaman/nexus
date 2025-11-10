<?php

declare(strict_types=1);

use App\Domains\Core\Models\Tenant;
use App\Models\User;
use App\Support\Contracts\ActivityLoggerContract;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->logger = app(ActivityLoggerContract::class);
    $this->user = User::factory()->create();
    $this->tenant = Tenant::factory()->create();
});

test('can log activity for a subject', function () {
    $this->actingAs($this->user);

    $this->logger->log('Test activity', $this->tenant);

    $activities = $this->logger->getActivities($this->tenant);

    expect($activities)->toBeInstanceOf(Collection::class)
        ->and($activities)->toHaveCount(1)
        ->and($activities->first()->description)->toBe('Test activity')
        ->and($activities->first()->subject_id)->toBe($this->tenant->id)
        ->and($activities->first()->subject_type)->toBe(Tenant::class);
});

test('can log activity with custom causer', function () {
    $anotherUser = User::factory()->create();

    $this->logger->log('Test activity', $this->tenant, $anotherUser);

    $activities = $this->logger->getActivities($this->tenant);

    expect($activities->first()->causer_id)->toBe($anotherUser->id)
        ->and($activities->first()->causer_type)->toBe(User::class);
});

test('can log activity with properties', function () {
    $this->actingAs($this->user);

    $properties = [
        'old_status' => 'active',
        'new_status' => 'suspended',
    ];

    $this->logger->log('Status changed', $this->tenant, null, $properties);

    $activities = $this->logger->getActivities($this->tenant);

    expect($activities->first()->properties)->toBeArray()
        ->and($activities->first()->properties['old_status'])->toBe('active')
        ->and($activities->first()->properties['new_status'])->toBe('suspended');
});

test('can retrieve activities by date range', function () {
    $this->actingAs($this->user);

    // Log activities on different dates
    Carbon::setTestNow('2025-01-01');
    $this->logger->log('Activity 1', $this->tenant);

    Carbon::setTestNow('2025-01-15');
    $this->logger->log('Activity 2', $this->tenant);

    Carbon::setTestNow('2025-02-01');
    $this->logger->log('Activity 3', $this->tenant);

    Carbon::setTestNow(); // Reset

    // Get activities from January
    $januaryActivities = $this->logger->getByDateRange(
        Carbon::parse('2025-01-01'),
        Carbon::parse('2025-01-31')
    );

    expect($januaryActivities)->toHaveCount(2)
        ->and($januaryActivities->pluck('description')->toArray())
        ->toContain('Activity 1', 'Activity 2');
});

test('can retrieve activities by causer', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $this->logger->log('Activity by user 1', $this->tenant, $user1);
    $this->logger->log('Activity by user 2', $this->tenant, $user2);
    $this->logger->log('Another activity by user 1', $this->tenant, $user1);

    $user1Activities = $this->logger->getByCauser($user1);

    expect($user1Activities)->toHaveCount(2)
        ->and($user1Activities->every(fn ($activity) => $activity->causer_id === $user1->id))->toBeTrue();
});

test('can get activity statistics', function () {
    $this->actingAs($this->user);

    // Create multiple activities
    $this->logger->log('Activity 1', $this->tenant);
    $this->logger->log('Activity 2', $this->tenant);
    $this->logger->log('Activity 3', $this->tenant);

    $stats = $this->logger->getStatistics();

    expect($stats)->toBeArray()
        ->and($stats)->toHaveKey('total_count')
        ->and($stats['total_count'])->toBeGreaterThanOrEqual(3);
});

test('can cleanup old activities', function () {
    $this->actingAs($this->user);

    // Create old activity
    Carbon::setTestNow('2020-01-01');
    $this->logger->log('Old activity', $this->tenant);

    // Create recent activity
    Carbon::setTestNow();
    $this->logger->log('Recent activity', $this->tenant);

    // Cleanup activities older than 1 year
    $deleted = $this->logger->cleanup(Carbon::now()->subDays(365));

    expect($deleted)->toBeGreaterThan(0);

    $activities = $this->logger->getActivities($this->tenant);
    expect($activities)->toHaveCount(1)
        ->and($activities->first()->description)->toBe('Recent activity');
});

test('get activities returns empty collection for model with no activities', function () {
    $newTenant = Tenant::factory()->create();

    $activities = $this->logger->getActivities($newTenant);

    expect($activities)->toBeInstanceOf(Collection::class)
        ->and($activities)->toHaveCount(0);
});

test('can filter activities by log name', function () {
    $this->actingAs($this->user);

    // Log with specific log name
    $this->logger->log('Tenant activity', $this->tenant, null, [], 'tenants');
    $this->logger->log('System activity', $this->user, null, [], 'system');

    $tenantLogs = $this->logger->getByDateRange(
        Carbon::now()->subDay(),
        Carbon::now()->addDay(),
        'tenants'
    );

    expect($tenantLogs)->toHaveCount(1)
        ->and($tenantLogs->first()->description)->toBe('Tenant activity');
});
