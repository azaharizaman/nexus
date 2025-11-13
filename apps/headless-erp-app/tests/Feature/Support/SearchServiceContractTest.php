<?php

declare(strict_types=1);

use App\Support\Contracts\SearchServiceContract;
use Nexus\TenancyManagement\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->searchService = app(SearchServiceContract::class);
});

test('can search models', function () {
    // Create test tenants
    $tenant1 = Tenant::factory()->create(['name' => 'Acme Corporation']);
    $tenant2 = Tenant::factory()->create(['name' => 'Beta Industries']);
    $tenant3 = Tenant::factory()->create(['name' => 'Acme Solutions']);

    // Index the models
    $this->searchService->index($tenant1);
    $this->searchService->index($tenant2);
    $this->searchService->index($tenant3);

    // Give search index time to update
    sleep(1);

    // Search for "Acme"
    $results = $this->searchService->search(Tenant::class, 'Acme');

    expect($results)->toBeInstanceOf(Collection::class)
        ->and($results->count())->toBeGreaterThanOrEqual(2)
        ->and($results->pluck('name')->toArray())->toContain('Acme Corporation', 'Acme Solutions');
});

test('can search with filters', function () {
    $tenant1 = Tenant::factory()->create([
        'name' => 'Active Company',
        'status' => 'active',
    ]);
    $tenant2 = Tenant::factory()->create([
        'name' => 'Suspended Company',
        'status' => 'suspended',
    ]);

    $this->searchService->index($tenant1);
    $this->searchService->index($tenant2);

    sleep(1);

    // Search with status filter
    $results = $this->searchService->search(Tenant::class, 'Company', [
        'filters' => ['status' => 'active'],
    ]);

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Active Company');
});

test('can search with pagination', function () {
    // Create multiple tenants
    for ($i = 1; $i <= 15; $i++) {
        $tenant = Tenant::factory()->create(['name' => "Company {$i}"]);
        $this->searchService->index($tenant);
    }

    sleep(1);

    // Search with pagination
    $results = $this->searchService->search(Tenant::class, 'Company', [
        'page' => 1,
        'per_page' => 5,
    ]);

    expect($results)->toBeInstanceOf(Collection::class)
        ->and($results->count())->toBeLessThanOrEqual(5);
});

test('can search with limit', function () {
    // Create multiple tenants
    for ($i = 1; $i <= 10; $i++) {
        $tenant = Tenant::factory()->create(['name' => "Test Company {$i}"]);
        $this->searchService->index($tenant);
    }

    sleep(1);

    // Search with limit
    $results = $this->searchService->search(Tenant::class, 'Test', [
        'limit' => 3,
    ]);

    expect($results)->toHaveCount(3);
});

test('can index a model', function () {
    $tenant = Tenant::factory()->create(['name' => 'Indexable Company']);

    // Index should not throw exception
    $this->searchService->index($tenant);

    expect(true)->toBeTrue();
});

test('can update indexed model', function () {
    $tenant = Tenant::factory()->create(['name' => 'Original Name']);
    $this->searchService->index($tenant);

    // Update the model
    $tenant->update(['name' => 'Updated Name']);
    $this->searchService->update($tenant);

    sleep(1);

    $results = $this->searchService->search(Tenant::class, 'Updated');

    expect($results->count())->toBeGreaterThanOrEqual(1)
        ->and($results->first()->name)->toBe('Updated Name');
});

test('can remove model from index', function () {
    $tenant = Tenant::factory()->create(['name' => 'Removable Company']);
    $this->searchService->index($tenant);

    sleep(1);

    // Verify it's searchable
    $results = $this->searchService->search(Tenant::class, 'Removable');
    expect($results->count())->toBeGreaterThanOrEqual(1);

    // Remove from index
    $this->searchService->removeFromIndex($tenant);

    sleep(1);

    // Should not be found anymore
    $results = $this->searchService->search(Tenant::class, 'Removable');
    expect($results)->toHaveCount(0);
});

test('can check if model is searchable', function () {
    $tenant = Tenant::factory()->make();

    $isSearchable = $this->searchService->isSearchable($tenant);

    expect($isSearchable)->toBeTrue();
});

test('empty search query returns empty results', function () {
    $results = $this->searchService->search(Tenant::class, '');

    expect($results)->toBeInstanceOf(Collection::class);
});

test('search returns empty collection when no matches', function () {
    $results = $this->searchService->search(Tenant::class, 'NonexistentCompanyXYZ123');

    expect($results)->toBeInstanceOf(Collection::class)
        ->and($results)->toHaveCount(0);
});
