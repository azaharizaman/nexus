---
plan: Implement Multi-Tenancy Core Infrastructure
version: 1.0
date_created: 2025-11-11
last_updated: 2025-11-11
owner: Development Team
status: Planned
tags: [feature, multitenancy, core-infrastructure, database, security, architecture]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan covers the core infrastructure for the Multi-Tenancy System, establishing the foundational components required for secure tenant isolation. This includes the Tenant model, database schema, the BelongsToTenant trait for automatic tenant scoping, tenant context management services, and Redis-based caching infrastructure. This plan focuses on the foundational layer that all other tenant-aware features will depend on.

## 1. Requirements & Constraints

### Requirements

- **REQ-FR-MT-001**: Implement a Tenant Model with unique identifiers (UUID), names, domains, and status
- **REQ-FR-MT-002**: Ensure strict Tenant Data Isolation using global scopes and middleware
- **REQ-FR-MT-004**: Support tenant-specific configuration with cascading settings
- **REQ-FR-MT-007**: Implement tenant-scoped caching with automatic cache key prefixing
- **REQ-BR-MT-001**: Each tenant MUST have a unique UUID that never changes
- **REQ-BR-MT-002**: Tenant domain names MUST be unique across the system
- **REQ-BR-MT-003**: Tenant status can be ACTIVE, SUSPENDED, ARCHIVED
- **REQ-DR-MT-001**: Tenants table includes id (UUID), name, domain, status, configuration (encrypted JSON), timestamps, soft deletes
- **REQ-DR-MT-002**: All tenant-scoped models include tenant_id foreign key with composite indexes
- **REQ-SR-MT-001**: Prevent cross-tenant data exposure through Eloquent global scopes
- **REQ-SR-MT-003**: Encrypt tenant-specific configurations using Laravel encryption
- **REQ-PR-MT-001**: Tenant resolution and context loading < 100ms using Redis caching
- **REQ-PR-MT-002**: Database queries automatically include tenant_id in WHERE clauses
- **REQ-SCR-MT-001**: Support 10,000+ concurrent active tenants
- **REQ-SCR-MT-002**: Tenant context caching distributed across Redis cluster
- **REQ-ARCH-MT-002**: Implement Eloquent global scope trait (BelongsToTenant)

### Security Constraints

- **SEC-001**: All tenant configurations must be encrypted at rest using Laravel's encryption
- **SEC-002**: Cross-tenant data access must be prevented at the ORM level, not just application level
- **SEC-003**: Tenant context must never leak between requests in the same PHP process

### Guidelines

- **GUD-001**: All PHP files must include `declare(strict_types=1);`
- **GUD-002**: All models must have complete PHPDoc blocks with @property annotations
- **GUD-003**: Use Laravel 12+ conventions (anonymous migrations, modern factory syntax)
- **GUD-004**: Follow PSR-12 coding standards, enforced by Laravel Pint

### Patterns to Follow

- **PAT-001**: Use Eloquent traits for shared model behavior (BelongsToTenant)
- **PAT-002**: Use Laravel Actions pattern for business logic (lorisleiva/laravel-actions)
- **PAT-003**: Use Repository pattern with contracts for data access
- **PAT-004**: Use Service Provider for package registration and binding
- **PAT-005**: Use Enum classes (PHP 8.2+) for status values

### Constraints

- **CON-001**: Must support PostgreSQL 14+ and MySQL 8.0+
- **CON-002**: Redis 6+ is mandatory for caching (not optional)
- **CON-003**: Package must be installable independently via Composer
- **CON-004**: Must maintain backward compatibility within minor versions

## 2. Implementation Steps

### GOAL-001: Create Tenant Model and Database Schema

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-MT-001, BR-MT-001, BR-MT-002, BR-MT-003, DR-MT-001, SR-MT-003 | Implement the Tenant model with UUID primary key, unique domain validation, status enum, encrypted configuration, and soft deletes. Create database migration with proper indexes. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create `packages/multitenancy/src/Enums/TenantStatus.php` with PHP 8.2 enum containing values: ACTIVE, SUSPENDED, ARCHIVED. Include `label()` method returning human-readable labels. | | |
| TASK-002 | Create `packages/multitenancy/database/migrations/0001_01_01_000000_create_tenants_table.php` anonymous migration with: id (UUID primary key), name (VARCHAR 255 NOT NULL), domain (VARCHAR 255 UNIQUE NOT NULL), status (VARCHAR 20 NOT NULL DEFAULT 'active'), configuration (TEXT for encrypted JSON), created_at, updated_at, deleted_at (for soft deletes). Add indexes on domain, status, and created_at. | | |
| TASK-003 | Create `packages/multitenancy/src/Models/Tenant.php` extending `Illuminate\Database\Eloquent\Model`. Use `HasUuids` trait for UUID primary key, `SoftDeletes` trait. Set $fillable = ['name', 'domain', 'status', 'configuration']. Cast 'status' to TenantStatus enum, 'configuration' to 'encrypted:array'. Add unique validation rules for domain. | | |
| TASK-004 | Add PHPDoc block to Tenant model with @property annotations for id, name, domain, status, configuration, created_at, updated_at, deleted_at. Include @method annotations for query scopes. | | |
| TASK-005 | Create `packages/multitenancy/database/factories/TenantFactory.php` extending `Illuminate\Database\Eloquent\Factories\Factory`. Define default state returning fake name, unique domain (slug-based), status (ACTIVE), and sample configuration array. Add state methods: `suspended()`, `archived()`. | | |
| TASK-006 | Add validation to Tenant model using Laravel validation in a static `rules()` method: name required|string|max:255, domain required|string|unique:tenants,domain|max:255, status nullable|in:active,suspended,archived, configuration nullable|array. | | |

### GOAL-002: Implement BelongsToTenant Trait for Automatic Tenant Scoping

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-MT-002, DR-MT-002, SR-MT-001, PR-MT-002, ARCH-MT-002 | Create the BelongsToTenant trait that automatically adds tenant_id to models, applies global scopes to filter queries, and provides helper methods for cross-tenant queries. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-007 | Create `packages/multitenancy/src/Traits/BelongsToTenant.php` trait with `bootBelongsToTenant()` static method. Add global scope that appends `WHERE tenant_id = ?` to all queries using `static::addGlobalScope('tenant', function (Builder $query) { $query->where('tenant_id', app(TenantContext::class)->current()?->id); })`. | | |
| TASK-008 | In BelongsToTenant trait, add `creating` model event observer that automatically sets `tenant_id` from current tenant context: `static::creating(function ($model) { if (!$model->tenant_id) { $model->tenant_id = app(TenantContext::class)->current()?->id; } })`. | | |
| TASK-009 | Add `tenant()` relationship method to BelongsToTenant trait: `public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }`. | | |
| TASK-010 | Add helper methods to BelongsToTenant trait: `scopeWithoutTenantScope(Builder $query)` to temporarily disable global scope, `scopeWithAllTenants(Builder $query)` alias for withoutTenantScope, `getCurrentTenantIdForModel(): ?string` returning current tenant ID. | | |
| TASK-011 | Add PHPDoc to BelongsToTenant trait documenting all methods and indicating that models using this trait will automatically filter by tenant_id. Include usage example in docblock. | | |

### GOAL-003: Build Tenant Context Management Service

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-MT-002, FR-MT-007, PR-MT-001, SCR-MT-001, SCR-MT-002 | Create TenantContext service to manage the current tenant state throughout request lifecycle with Redis caching for performance. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-012 | Create `packages/multitenancy/src/Services/TenantContext.php` class with private properties: `?Tenant $currentTenant`, `CacheManager $cache`. Inject CacheManager via constructor. | | |
| TASK-013 | In TenantContext, implement `setCurrentTenant(Tenant $tenant): void` method that stores tenant in memory and caches in Redis with key `tenant:context:{$tenant->id}` for 1 hour TTL. | | |
| TASK-014 | In TenantContext, implement `current(): ?Tenant` method that returns cached tenant or loads from Redis cache if available. Return null if no tenant set. | | |
| TASK-015 | In TenantContext, implement `forget(): void` method that clears in-memory tenant and removes from Redis cache. | | |
| TASK-016 | In TenantContext, implement `getCacheKey(string $key): string` helper that prefixes any cache key with current tenant ID: `tenant:{$this->current()->id}:{$key}`. Throw exception if no current tenant. | | |
| TASK-017 | Add PHPDoc to TenantContext with usage examples and method documentation. Include @throws annotations for methods that require active tenant. | | |

### GOAL-004: Create Tenant Repository with Contract

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-MT-001, FR-MT-005, BR-MT-002 | Implement repository pattern for Tenant data access with contract interface, enabling testability and future implementation swapping. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-018 | Create `packages/multitenancy/src/Contracts/TenantRepositoryContract.php` interface with methods: `findById(string $id): ?Tenant`, `findByDomain(string $domain): ?Tenant`, `create(array $data): Tenant`, `update(Tenant $tenant, array $data): Tenant`, `delete(Tenant $tenant): bool`, `getAll(array $filters = [], int $perPage = 15)`, `countActive(): int`. | | |
| TASK-019 | Create `packages/multitenancy/src/Repositories/TenantRepository.php` implementing TenantRepositoryContract. Inject Tenant model. Implement all interface methods using Eloquent query builder. | | |
| TASK-020 | In TenantRepository::create(), validate data using Tenant::rules(), encrypt configuration field, ensure domain is unique, and dispatch TenantCreatedEvent after successful creation. | | |
| TASK-021 | In TenantRepository::update(), validate changes, re-encrypt configuration if modified, ensure domain uniqueness (excluding current tenant), and dispatch TenantUpdatedEvent. | | |
| TASK-022 | In TenantRepository::getAll(), support filtering by status, domain (LIKE search), and created date range. Support pagination. Apply filters using query builder where clauses. | | |
| TASK-023 | Add comprehensive PHPDoc to TenantRepositoryContract and TenantRepository with @param, @return, and @throws annotations for all methods. | | |

### GOAL-005: Set Up Package Service Provider and Configuration

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| CON-003, CON-004 | Create Laravel service provider for package registration, binding contracts, publishing migrations and config, enabling independent package installation. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-024 | Create `packages/multitenancy/src/MultitenancyServiceProvider.php` extending `Illuminate\Support\ServiceProvider`. Implement `register()` method to bind TenantRepositoryContract to TenantRepository implementation as singleton. | | |
| TASK-025 | In MultitenancyServiceProvider::register(), bind TenantContext as singleton in container. | | |
| TASK-026 | In MultitenancyServiceProvider::boot(), register publishable migrations: `$this->publishes([__DIR__.'/../database/migrations' => database_path('migrations')], 'multitenancy-migrations')`. | | |
| TASK-027 | In MultitenancyServiceProvider::boot(), register publishable config: `$this->publishes([__DIR__.'/../config/multitenancy.php' => config_path('multitenancy.php')], 'multitenancy-config')`. Merge config: `$this->mergeConfigFrom(__DIR__.'/../config/multitenancy.php', 'multitenancy')`. | | |
| TASK-028 | Create `packages/multitenancy/config/multitenancy.php` with configuration options: cache_ttl (default 3600 seconds), cache_prefix (default 'tenant'), default_status (default 'active'), domain_validation_regex, impersonation_timeout (default 3600 seconds). | | |
| TASK-029 | Create `packages/multitenancy/composer.json` with package metadata: name "azaharizaman/erp-multitenancy", type "library", require PHP ^8.2 and laravel/framework ^12.0, autoload PSR-4 namespace, extra.laravel.providers array with MultitenancyServiceProvider. | | |
| TASK-030 | Create `packages/multitenancy/README.md` with installation instructions, basic usage examples, configuration options, and migration steps. Include example of using BelongsToTenant trait. | | |

## 3. Alternatives

- **ALT-001**: Use database-per-tenant instead of shared database with tenant_id - Rejected because it significantly increases infrastructure complexity and doesn't scale well for SaaS with thousands of tenants.
- **ALT-002**: Use request-scoped tenant context instead of singleton service - Rejected because Laravel's service container already provides request-scoped singletons, and explicit singleton binding is clearer.
- **ALT-003**: Store tenant configuration in separate table instead of JSON column - Rejected because it adds query complexity and JSON is sufficient for hierarchical configuration data with Laravel's encrypted casting.
- **ALT-004**: Use Laravel Passport instead of custom tenant context - Rejected because Passport is for OAuth, not tenant context management, and would add unnecessary complexity.

## 4. Dependencies

### Package Dependencies

- **DEP-001**: laravel/framework ^12.0 (Core framework, Eloquent ORM, caching)
- **DEP-002**: illuminate/database ^12.0 (Database query builder, migrations)
- **DEP-003**: illuminate/cache ^12.0 (Redis cache manager)
- **DEP-004**: illuminate/support ^12.0 (Service provider, helper functions)

### Infrastructure Dependencies

- **DEP-005**: PostgreSQL 14+ or MySQL 8.0+ (Database storage)
- **DEP-006**: Redis 6+ (Required for tenant context caching and cache key prefixing)
- **DEP-007**: PHP 8.2+ (Required for enums, readonly properties, strict types)

### Development Dependencies

- **DEP-008**: pestphp/pest ^4.0 (Testing framework)
- **DEP-009**: laravel/pint ^1.0 (Code formatting)

## 5. Files

### Core Model and Database

- **packages/multitenancy/src/Models/Tenant.php**: Main Tenant eloquent model with UUID, soft deletes, encrypted configuration
- **packages/multitenancy/src/Enums/TenantStatus.php**: Enum for tenant status values (ACTIVE, SUSPENDED, ARCHIVED)
- **packages/multitenancy/database/migrations/0001_01_01_000000_create_tenants_table.php**: Database migration creating tenants table with indexes
- **packages/multitenancy/database/factories/TenantFactory.php**: Factory for generating test tenant data

### Traits and Scopes

- **packages/multitenancy/src/Traits/BelongsToTenant.php**: Trait for automatic tenant scoping on models

### Services and Repositories

- **packages/multitenancy/src/Services/TenantContext.php**: Service managing current tenant state and caching
- **packages/multitenancy/src/Contracts/TenantRepositoryContract.php**: Interface defining tenant data access methods
- **packages/multitenancy/src/Repositories/TenantRepository.php**: Implementation of tenant repository with validation

### Package Infrastructure

- **packages/multitenancy/src/MultitenancyServiceProvider.php**: Laravel service provider for package registration
- **packages/multitenancy/config/multitenancy.php**: Package configuration file
- **packages/multitenancy/composer.json**: Composer package definition
- **packages/multitenancy/README.md**: Package documentation

## 6. Testing

### Unit Tests

- **TEST-001**: Test TenantStatus enum has correct values and labels
- **TEST-002**: Test Tenant model casts status to TenantStatus enum correctly
- **TEST-003**: Test Tenant model encrypts and decrypts configuration correctly
- **TEST-004**: Test Tenant model enforces unique domain constraint
- **TEST-005**: Test TenantFactory creates valid tenant instances with all required fields
- **TEST-006**: Test BelongsToTenant trait automatically sets tenant_id on model creation
- **TEST-007**: Test BelongsToTenant trait applies global scope filtering queries by tenant_id
- **TEST-008**: Test BelongsToTenant trait withoutTenantScope() disables global filtering
- **TEST-009**: Test TenantContext::setCurrentTenant() stores tenant in memory and Redis cache
- **TEST-010**: Test TenantContext::current() retrieves tenant from cache without additional DB queries
- **TEST-011**: Test TenantContext::getCacheKey() prefixes keys with tenant ID
- **TEST-012**: Test TenantRepository::create() validates data and dispatches TenantCreatedEvent
- **TEST-013**: Test TenantRepository::update() validates changes and dispatches TenantUpdatedEvent
- **TEST-014**: Test TenantRepository::findByDomain() returns correct tenant or null
- **TEST-015**: Test TenantRepository::getAll() filters by status, domain, and date range

### Integration Tests

- **TEST-016**: Test that models using BelongsToTenant trait cannot access other tenant's data
- **TEST-017**: Test tenant context persists across multiple service calls in same request
- **TEST-018**: Test Redis cache invalidation when tenant is updated
- **TEST-019**: Test migrations run successfully on PostgreSQL and MySQL
- **TEST-020**: Test package can be installed independently via Composer

### Performance Tests

- **TEST-021**: Test tenant resolution from cache completes in < 100ms (PR-MT-001)
- **TEST-022**: Test database queries automatically include tenant_id in WHERE clause (PR-MT-002)
- **TEST-023**: Test system handles 10,000+ concurrent tenants without performance degradation (SCR-MT-001)

## 7. Risks & Assumptions

### Risks

- **RISK-001**: **Cache failure risk** - If Redis becomes unavailable, tenant context resolution will fail. Mitigation: Implement graceful degradation to database lookups with performance warning logs.
- **RISK-002**: **Global scope leakage** - Developers might accidentally bypass tenant scoping using raw queries. Mitigation: Comprehensive documentation, linting rules, and security audit tests.
- **RISK-003**: **Migration complexity** - Adding tenant_id to existing tables in production requires careful data migration. Mitigation: Provide migration guide with zero-downtime strategies.
- **RISK-004**: **Performance degradation** - Automatic tenant_id filtering on every query might slow down complex queries. Mitigation: Ensure proper composite indexes (tenant_id, other_columns) are created.

### Assumptions

- **ASSUMPTION-001**: Redis is available and properly configured in all environments (dev, staging, production)
- **ASSUMPTION-002**: Database supports UUID data type (PostgreSQL has native UUID, MySQL uses CHAR(36))
- **ASSUMPTION-003**: All tenant-scoped models will use the BelongsToTenant trait (enforced by convention, not technically)
- **ASSUMPTION-004**: Tenant count will not exceed 100,000 in the first year of operation
- **ASSUMPTION-005**: Average tenant has 10-50 users, affecting cache sizing calculations
- **ASSUMPTION-006**: Tenant configuration JSON will not exceed 64KB (TEXT column limit in MySQL)

## 8. Related PRD / Further Reading

### Primary Documentation

- **PRD01-SUB01: Multi-Tenancy System**: [/docs/prd/prd-01/PRD01-SUB01-MULTITENANCY.md](../prd/prd-01/PRD01-SUB01-MULTITENANCY.md)
- **Master PRD**: [/docs/prd/PRD01-MVP.md](../prd/PRD01-MVP.md)

### Architectural Documentation

- **Package Decoupling Strategy**: [/docs/architecture/PACKAGE-DECOUPLING-STRATEGY.md](../architecture/PACKAGE-DECOUPLING-STRATEGY.md)
- **Coding Guidelines**: [/CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)

### Laravel Documentation

- **Laravel 12 Eloquent Scopes**: https://laravel.com/docs/12.x/eloquent#global-scopes
- **Laravel 12 Encryption**: https://laravel.com/docs/12.x/encryption
- **Laravel 12 Caching**: https://laravel.com/docs/12.x/cache
- **Laravel 12 Service Providers**: https://laravel.com/docs/12.x/providers
- **Laravel 12 Package Development**: https://laravel.com/docs/12.x/packages

### External Resources

- **Multi-Tenancy Patterns**: https://docs.microsoft.com/en-us/azure/architecture/guide/multitenant/overview
- **SaaS Tenant Isolation**: https://aws.amazon.com/blogs/apn/isolation-in-saas-applications/
