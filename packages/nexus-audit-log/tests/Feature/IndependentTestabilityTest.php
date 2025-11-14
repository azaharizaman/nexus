<?php

declare(strict_types=1);

use Nexus\AuditLog\Models\AuditLog;
use Nexus\AuditLog\Contracts\AuditLogRepositoryContract;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('atomic package can be tested independently', function () {
    // Test basic package functionality without external dependencies
    $this->assertTrue(class_exists(AuditLog::class));
    $this->assertTrue(interface_exists(AuditLogRepositoryContract::class));
});

test('audit log repository contract is bound', function () {
    // Verify the package service provider works in isolation
    $repository = app(AuditLogRepositoryContract::class);
    
    expect($repository)
        ->toBeInstanceOf(AuditLogRepositoryContract::class);
});

test('audit log model can be created', function () {
    // Test internal model functionality
    $auditLog = AuditLog::create([
        'log_name' => 'test',
        'description' => 'Test audit log entry',
        'event' => 'created',
        'tenant_id' => 'test-tenant',
        'properties' => ['test' => 'value'],
        'audit_level' => 1,
    ]);
    
    expect($auditLog)
        ->toBeInstanceOf(AuditLog::class)
        ->and($auditLog->log_name)->toBe('test')
        ->and($auditLog->description)->toBe('Test audit log entry')
        ->and($auditLog->tenant_id)->toBe('test-tenant')
        ->and($auditLog->properties)->toBe(['test' => 'value']);
});

test('audit log repository can create entries', function () {
    // Test repository functionality
    $repository = app(AuditLogRepositoryContract::class);
    
    $data = [
        'log_name' => 'repository_test',
        'description' => 'Repository test entry',
        'event' => 'created',
        'tenant_id' => 'repo-test-tenant',
        'properties' => ['repository' => 'test'],
    ];
    
    $auditLog = $repository->create($data);
    
    expect($auditLog)
        ->toBeInstanceOf(AuditLog::class)
        ->and($auditLog->log_name)->toBe('repository_test')
        ->and($auditLog->description)->toBe('Repository test entry')
        ->and($auditLog->tenant_id)->toBe('repo-test-tenant');
});

test('audit log scopes work correctly', function () {
    // Create test data
    AuditLog::create([
        'log_name' => 'test',
        'description' => 'Tenant 1 entry',
        'tenant_id' => 'tenant-1',
        'event' => 'created',
    ]);
    
    AuditLog::create([
        'log_name' => 'test',
        'description' => 'Tenant 2 entry', 
        'tenant_id' => 'tenant-2',
        'event' => 'updated',
    ]);
    
    // Test tenant scope
    $tenant1Logs = AuditLog::forTenant('tenant-1')->get();
    expect($tenant1Logs)->toHaveCount(1)
        ->and($tenant1Logs->first()->description)->toBe('Tenant 1 entry');
    
    // Test event scope
    $createdLogs = AuditLog::forEvent('created')->get();
    expect($createdLogs)->toHaveCount(1)
        ->and($createdLogs->first()->event)->toBe('created');
});

test('package configuration is loaded correctly', function () {
    // Verify package configuration is accessible
    expect(config('audit-logging.storage_driver'))->toBe('database')
        ->and(config('audit-logging.retention_days'))->toBe(30)
        ->and(config('audit-logging.notify_high_value_events'))->toBeFalse();
});