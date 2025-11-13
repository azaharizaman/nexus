---
plan: Implement Settings Management System
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, infrastructure, settings, configuration, multi-level, encryption, caching, core-infrastructure]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan covers the comprehensive Settings Management System for the Laravel ERP, providing hierarchical configuration management supporting system-level, tenant-level, and module-level settings with encryption and caching. This system enables flexible, secure, and performant configuration storage for the entire ERP system with support for typed values, default fallbacks, and event-driven change notification.

## 1. Requirements & Constraints

### Requirements

- **REQ-FR-SM-001**: Support Hierarchical Structure (system-level, tenant-level, module-level, user-level) with inheritance
- **REQ-FR-SM-002**: Provide settings caching layer with automatic invalidation on updates
- **REQ-FR-SM-003**: Support type-safe settings with validation (string, int, bool, array, JSON)
- **REQ-FR-SM-004**: Provide Default Values when setting not defined at tenant/module level
- **REQ-FR-SM-005**: Support encrypted settings for sensitive data (API keys, credentials)
- **REQ-BR-SM-001**: Settings MUST follow inheritance: user → module → tenant → system
- **REQ-BR-SM-002**: Encrypted settings MUST be stored with Laravel encryption
- **REQ-BR-SM-003**: Setting keys MUST be namespaced: `module.category.key`
- **REQ-BR-SM-004**: Tenant settings MUST NOT access other tenants' settings
- **REQ-BR-SM-005**: System settings can only be modified by super admins
- **REQ-DR-SM-001**: Settings MUST support types: string, integer, boolean, array, json, encrypted
- **REQ-DR-SM-002**: Settings MUST store: key, value, type, scope (system/tenant/module), metadata
- **REQ-DR-SM-003**: Settings MUST include validation rules in metadata
- **REQ-IR-SM-001**: Provide settings API for all modules to store/retrieve configuration
- **REQ-IR-SM-002**: Integrate with SUB01 (Multi-Tenancy) for tenant-scoped settings
- **REQ-PR-SM-001**: Frequently accessed settings MUST be cached with TTL to avoid database queries
- **REQ-PR-SM-002**: Setting retrieval MUST complete in < 10ms for cached values
- **REQ-PR-SM-003**: Cache invalidation MUST occur within 1 second of setting update
- **REQ-SR-SM-001**: Encrypt sensitive values (API keys, passwords, tokens) using AES-256 Laravel encryption
- **REQ-SR-SM-002**: Tenant isolation MUST prevent cross-tenant setting access
- **REQ-SR-SM-003**: Require admin permission for modifying system-level settings
- **REQ-SCR-SM-001**: Support 10,000+ settings per tenant with efficient caching
- **REQ-ARCH-SM-001**: Use Redis/Memcached for high-performance caching layer
- **REQ-ARCH-SM-002**: Support lazy loading of settings to minimize memory footprint
- **REQ-ARCH-SM-003**: Implement observer pattern for real-time setting updates
- **REQ-EV-SM-001**: Dispatch SettingUpdatedEvent when setting value is changed
- **REQ-EV-SM-002**: Dispatch SettingCreatedEvent when new setting is created
- **REQ-EV-SM-003**: Dispatch CacheInvalidatedEvent when setting cache needs refresh

### Security Constraints

- **SEC-001**: Encrypted settings must never be returned decrypted in API responses without explicit authorization
- **SEC-002**: Setting updates must verify scope permissions (system requires super-admin, tenant requires tenant-admin)
- **SEC-003**: Cross-tenant setting access must be blocked at query level with automatic tenant_id filtering
- **SEC-004**: Setting metadata (validation rules) must not contain executable code to prevent injection

### Guidelines

- **GUD-001**: All PHP files must include `declare(strict_types=1);`
- **GUD-002**: Use Laravel's built-in encryption (Crypt facade) for encrypted setting types
- **GUD-003**: Use Laravel 12+ conventions (anonymous migrations, modern factory syntax)
- **GUD-004**: Follow PSR-12 coding standards, enforced by Laravel Pint
- **GUD-005**: All setting keys must use dot notation (e.g., `email.smtp.host`)

### Patterns to Follow

- **PAT-001**: Use Repository pattern for setting storage with multi-level inheritance resolution
- **PAT-002**: Use Service pattern for type casting, validation, and encryption/decryption
- **PAT-003**: Use Cache-Aside pattern for setting retrieval (check cache, fallback to DB, populate cache)
- **PAT-004**: Use Events for setting change notifications (trigger cache invalidation, notify listeners)
- **PAT-005**: Use Facade pattern to provide simple API for setting access (`Settings::get('key')`)

### Constraints

- **CON-001**: Must support PostgreSQL 14+ and MySQL 8.0+ for setting storage
- **CON-002**: Redis or Memcached is mandatory for caching (not optional)
- **CON-003**: Package must be installable independently via Composer
- **CON-004**: Setting keys must be max 255 characters, values max 65,535 characters (TEXT field)
- **CON-005**: Maximum 4 inheritance levels: system → tenant → module → user

## 2. Implementation Steps

### GOAL-001: Package Setup and Hierarchical Storage Schema

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-SM-001, DR-SM-001, DR-SM-002, BR-SM-003 | Set up settings-management package structure with Composer, create database schema supporting hierarchical storage with system/tenant/module/user scopes, type safety, and metadata. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create package directory structure: `packages/settings-management/` with subdirectories `src/`, `database/migrations/`, `database/seeders/`, `config/`, `tests/`. Initialize `composer.json` with package name `azaharizaman/erp-settings-management`, namespace `Nexus\Erp\SettingsManagement`, require Laravel 12+, PHP 8.2+. | | |
| TASK-002 | Create migration `database/migrations/create_settings_table.php` (anonymous class): Define `settings` table with columns: id (BIGSERIAL), key (VARCHAR(255), indexed, NOT NULL), value (TEXT, nullable for encrypted types), type (ENUM: 'string', 'integer', 'boolean', 'array', 'json', 'encrypted'), scope (ENUM: 'system', 'tenant', 'module', 'user'), tenant_id (UUID/BIGINT, indexed, nullable for system scope), module_name (VARCHAR(255), indexed, nullable), user_id (BIGINT, indexed, nullable), metadata (JSONB for validation rules, defaults, descriptions), created_at, updated_at. Add composite unique constraint on (key, scope, tenant_id, module_name, user_id). Add indexes on (tenant_id, module_name), (scope), (key). | | |
| TASK-003 | Create migration `database/migrations/create_settings_history_table.php` (anonymous class): Define `settings_history` table with columns: id (BIGSERIAL), setting_id (BIGINT, foreign key to settings.id), old_value (TEXT), new_value (TEXT), changed_by (BIGINT, nullable, foreign key to users.id), changed_at (TIMESTAMP, indexed), reason (TEXT, nullable). This tracks all setting changes for audit trail. Add index on (setting_id, changed_at). | | |
| TASK-004 | Create `config/settings-management.php` configuration file with settings: enabled (bool, default true), cache_enabled (bool, default true), cache_driver (string, default 'redis'), cache_prefix (string, default 'settings:'), cache_ttl (int, default 3600 seconds), encryption_enabled (bool, default true), enable_history (bool, default true), default_scope (string, default 'tenant'), lazy_loading (bool, default true). | | |
| TASK-005 | Create `src/SettingsManagementServiceProvider.php`: Register config, migrations, service bindings. Bind `SettingsRepositoryContract` to `DatabaseSettingsRepository`. Bind `SettingsServiceContract` to `SettingsService`. Register `Settings` facade. Register event listeners for cache invalidation. Publish config and migrations. | | |

### GOAL-002: Type System and Value Handling

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-SM-003, FR-SM-005, DR-SM-001, BR-SM-002, SR-SM-001 | Implement type-safe value handling with automatic casting, validation, encryption/decryption for sensitive settings, and support for complex types (array, JSON). | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-006 | Create `src/Contracts/SettingsServiceContract.php` interface: Define methods: `get(string $key, mixed $default = null, ?string $scope = null): mixed` (retrieves value with type casting), `set(string $key, mixed $value, string $type, string $scope, array $metadata = []): void` (stores value), `has(string $key, ?string $scope = null): bool` (checks existence), `forget(string $key, ?string $scope = null): void` (deletes setting), `all(string $scope, ?string $module = null): array` (retrieves all settings in scope). All methods must have PHPDoc with parameter types and return types. | | |
| TASK-007 | Create `src/Services/SettingsService.php` implementing `SettingsServiceContract`: Implement `get()` to resolve hierarchical inheritance (user → module → tenant → system) using `resolveHierarchy()` helper. For each level, check cache first (using `getCacheKey()`), if miss, query database, cast value to correct type using `castValue()`, store in cache. If encrypted type, decrypt using `Crypt::decryptString()`. Return value or default if not found. | | |
| TASK-008 | Implement `set()` in `SettingsService`: Validate metadata (ensure validation rules are valid), determine scope (system requires super-admin check, tenant requires tenant-admin check), cast value to storage format using `castToStorage()`. If type is 'encrypted', encrypt value using `Crypt::encryptString()` before saving. Call `SettingsRepositoryContract::createOrUpdate()`, invalidate cache for this key and all parent scopes. Dispatch `SettingUpdatedEvent`. If new setting, dispatch `SettingCreatedEvent`. Record change in settings_history table. | | |
| TASK-009 | Implement `castValue()` helper in `SettingsService`: Switch on type: 'string' => (string)$value, 'integer' => (int)$value, 'boolean' => (bool)$value or check string 'true'/'false', 'array' => json_decode($value, true) with error handling, 'json' => json_decode($value) as object, 'encrypted' => decrypt and cast as string. Throw `InvalidSettingTypeException` if casting fails. | | |
| TASK-010 | Implement `castToStorage()` helper in `SettingsService`: Switch on type: 'string'/'integer'/'boolean' => cast to string for storage, 'array'/'json' => json_encode($value) with JSON_THROW_ON_ERROR flag, 'encrypted' => encrypt using `Crypt::encryptString()` then store. Validate value before encoding (e.g., arrays must be valid arrays, JSON must be valid JSON). | | |
| TASK-011 | Create `src/Contracts/SettingsRepositoryContract.php` interface: Define methods: `find(string $key, string $scope, ?string $tenantId, ?string $module, ?int $userId): ?Setting` (retrieves single setting), `findAll(string $scope, ?string $tenantId, ?string $module): Collection` (retrieves all settings in scope), `createOrUpdate(array $data): Setting` (creates or updates setting), `delete(string $key, string $scope, ?string $tenantId, ?string $module, ?int $userId): bool` (deletes setting), `exists(string $key, string $scope, ?string $tenantId, ?string $module): bool` (checks existence). | | |

### GOAL-003: Hierarchical Inheritance and Resolution

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-SM-001, FR-SM-004, BR-SM-001, BR-SM-004 | Implement hierarchical setting resolution with inheritance from system → tenant → module → user levels, default fallback logic, and tenant isolation. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-012 | Implement `resolveHierarchy()` in `SettingsService`: Accept key, current tenant_id, module_name (nullable), user_id (nullable). Query settings in order: 1) user-level (if user_id provided), 2) module-level (if module_name provided), 3) tenant-level (if tenant_id provided), 4) system-level. Return first found value. Use cache for each level check (cache key includes scope, tenant, module, user). If not found at any level and metadata contains default value, return default. Otherwise return null or provided default parameter. | | |
| TASK-013 | Create `src/Repositories/DatabaseSettingsRepository.php` implementing `SettingsRepositoryContract`: Use Eloquent model `Setting`. Implement `find()` with query: `Setting::where('key', $key)->where('scope', $scope)`. For tenant scope, add `->where('tenant_id', $tenantId)`. For module scope, add `->where('module_name', $module)`. For user scope, add `->where('user_id', $userId)`. Always apply tenant_id filter for non-system scopes to enforce isolation. | | |
| TASK-014 | Implement automatic tenant_id injection in `SettingsService::set()`: If scope is 'tenant', 'module', or 'user', automatically inject current tenant_id from `TenantManager::current()->id` or `auth()->user()->tenant_id`. Never allow cross-tenant setting creation/update. For 'system' scope, ensure tenant_id is null and check super-admin permission via `Gate::authorize('manage-system-settings')`. | | |
| TASK-015 | Implement `all()` in `SettingsService`: Retrieve all settings for given scope (system, tenant, module). For tenant scope, return all settings with matching tenant_id. For module scope, return all settings with matching tenant_id and module_name. Apply hierarchical merging: start with system settings, overlay tenant settings (overwrite duplicates), overlay module settings, overlay user settings. Return merged array with resolved values. Cache merged result with scope-specific cache key. | | |
| TASK-016 | Create helper method `getDefault()` in `SettingsService`: Extract default value from metadata JSON. Metadata structure: `{"default": "value", "validation": ["required"], "description": "Setting description"}`. If metadata contains 'default' key, return it cast to appropriate type. Otherwise return null. This provides fallback when setting not defined at any level. | | |

### GOAL-004: High-Performance Caching Layer

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-SM-002, PR-SM-001, PR-SM-002, PR-SM-003, ARCH-SM-001, ARCH-SM-002 | Implement Redis/Memcached caching layer with automatic invalidation on updates, lazy loading, and sub-10ms retrieval for cached values. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-017 | Implement cache layer in `SettingsService::get()`: Generate cache key using `getCacheKey($key, $scope, $tenantId, $module, $userId)` format: `settings:{scope}:{tenant_id}:{module}:{user_id}:{key}`. Check cache using `Cache::get($cacheKey)`. If hit, return cached value (already type-cast). If miss, query database, cast value, store in cache with TTL from config, return value. Use Cache::remember() for atomic cache-aside pattern. | | |
| TASK-018 | Implement `getCacheKey()` helper in `SettingsService`: Build cache key string with format: `{config.cache_prefix}{scope}:{tenant_id}:{module_name}:{user_id}:{key}`. For system scope, omit tenant/module/user. For tenant scope, include tenant_id only. For module scope, include tenant_id and module_name. For user scope, include all. This ensures unique cache keys per hierarchy level. | | |
| TASK-019 | Implement cache invalidation in `SettingsService::set()`: After successful save, invalidate cache for current level and all parent levels. For user-level setting update, invalidate: user cache key, module cache key (if applicable), tenant cache key, system cache key (if setting exists at system level). Use `Cache::forget($cacheKey)` for each level. Dispatch `CacheInvalidatedEvent` with affected keys. This ensures cache freshness within 1 second of update. | | |
| TASK-020 | Implement lazy loading in `SettingsService::all()`: Instead of loading all settings into memory, return lazy collection using `LazyCollection` or generator. Query database in chunks (1000 settings per chunk), apply hierarchical merging incrementally. Cache final merged result with scope-specific cache key. This minimizes memory footprint for tenants with 10,000+ settings. | | |
| TASK-021 | Create cache warming command `src/Commands/WarmSettingsCacheCommand.php`: Artisan command signature `settings:warm-cache`. Iterate through all tenants, for each tenant, retrieve all settings using `SettingsService::all('tenant')`, this populates cache. Optionally support `--scope` option to warm specific scope (system, tenant, module). Schedule command to run daily at off-peak hours via Task Scheduler. This ensures frequently accessed settings are always cached. | | |

### GOAL-005: API Endpoints and Integration

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| IR-SM-001, IR-SM-002, BR-SM-005, SR-SM-003, EV-SM-001, EV-SM-002, EV-SM-003 | Build RESTful API endpoints for setting management (CRUD, bulk operations) with proper authorization, tenant isolation, and integration with multi-tenancy and event system. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-022 | Create `src/Http/Controllers/SettingsController.php`: Implement methods: `index(Request $request): JsonResponse` (list settings for current tenant/module with filtering by key prefix), `show(string $key): JsonResponse` (get single setting with inheritance resolution), `store(CreateSettingRequest $request): JsonResponse` (create new setting), `update(UpdateSettingRequest $request, string $key): JsonResponse` (update existing setting), `destroy(string $key): JsonResponse` (delete setting - revert to default). Apply `auth:sanctum` and `tenant` middleware. | | |
| TASK-023 | Implement `bulk()` endpoint in SettingsController: `bulk(BulkUpdateSettingsRequest $request): JsonResponse` handles POST /api/v1/settings/bulk. Accept array of settings: `[{key: 'email.smtp.host', value: 'smtp.gmail.com', type: 'string'}, ...]`. Validate each setting, call `SettingsService::set()` for each. Use database transaction for atomicity. Return count of updated settings. This allows UI to save multiple settings in one request. | | |
| TASK-024 | Implement `export()` endpoint in SettingsController: `export(Request $request): Response` handles GET /api/v1/settings/export. Retrieve all settings for current tenant using `SettingsService::all('tenant')`. Optionally filter by module or key prefix. Format as JSON or CSV based on request. Check authorization (Gate::authorize('export-settings')). Return download response. This allows backup/migration of tenant settings. | | |
| TASK-025 | Implement `import()` endpoint in SettingsController: `import(ImportSettingsRequest $request): JsonResponse` handles POST /api/v1/settings/import. Accept uploaded JSON/CSV file with settings. Validate file format and setting values. For each setting, call `SettingsService::set()` with appropriate scope. Use database transaction. Check authorization (Gate::authorize('import-settings')). Return count of imported settings. This allows bulk import from backup or template. | | |
| TASK-026 | Create Form Requests: `CreateSettingRequest.php` (validation: key required|regex:/^[a-z0-9._-]+$/i|max:255, value required, type required|in:string,integer,boolean,array,json,encrypted, scope required|in:system,tenant,module,user, metadata nullable|json), `UpdateSettingRequest.php` (same validation, all fields optional), `BulkUpdateSettingsRequest.php` (validation: settings required|array, settings.*.key required, settings.*.value required). Authorization: check scope permissions (system requires super-admin, tenant requires admin). | | |
| TASK-027 | Create API Resource `src/Http/Resources/SettingResource.php`: Transform Setting model to JSON:API format with fields: key, value (decrypted if encrypted type and user authorized), type, scope, tenant_id (only if super-admin), module_name, user_id, metadata (including validation rules, default, description), is_default (boolean indicating if using default value), created_at, updated_at. Conditionally hide sensitive fields (encrypted values) unless user has 'view-encrypted-settings' permission. | | |
| TASK-028 | Create Policy `src/Policies/SettingPolicy.php`: Implement methods: `viewAny(User $user): bool` checks authenticated, `view(User $user, Setting $setting): bool` checks same tenant or super-admin, `create(User $user): bool` checks admin permission for tenant/module, super-admin for system, `update(User $user, Setting $setting): bool` checks scope permission and tenant match, `delete(User $user, Setting $setting): bool` checks scope permission. Register policy in SettingsManagementServiceProvider. | | |
| TASK-029 | Integrate with SUB01 Multi-Tenancy: Create middleware `src/Http/Middleware/InjectTenantToSettings.php` to automatically inject tenant_id from current tenant context into setting operations. Ensure all setting queries include tenant_id filter for non-system scopes. Register middleware in routes. | | |
| TASK-030 | Create `src/Facades/Settings.php` facade: Provide simple API for accessing settings in code: `Settings::get('email.smtp.host')`, `Settings::set('email.smtp.host', 'smtp.gmail.com', 'string')`, `Settings::has('email.smtp.host')`, `Settings::forget('email.smtp.host')`. This wraps `SettingsServiceContract` with convenient static methods. Register facade in service provider via `app()->bind('settings', SettingsServiceContract::class)`. | | |
| TASK-031 | Add API routes in SettingsManagementServiceProvider: `Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/v1/settings')->group(function() { Route::get('/', [SettingsController::class, 'index']); Route::post('/', [SettingsController::class, 'store'])->middleware('can:create,App\Models\Setting'); Route::get('/{key}', [SettingsController::class, 'show']); Route::patch('/{key}', [SettingsController::class, 'update'])->middleware('can:update,setting'); Route::delete('/{key}', [SettingsController::class, 'destroy'])->middleware('can:delete,setting'); Route::post('/bulk', [SettingsController::class, 'bulk'])->middleware('can:create,App\Models\Setting'); Route::get('/export', [SettingsController::class, 'export'])->middleware('can:export-settings'); Route::post('/import', [SettingsController::class, 'import'])->middleware('can:import-settings'); })`. | | |
| TASK-032 | Create Events: `src/Events/SettingUpdatedEvent.php` (properties: setting_id, key, old_value, new_value, scope, tenant_id, changed_by), `src/Events/SettingCreatedEvent.php` (properties: setting_id, key, value, type, scope, tenant_id, created_by), `src/Events/CacheInvalidatedEvent.php` (properties: cache_keys array, reason). Dispatch events after successful operations. Allow other modules to react to setting changes. | | |

## 3. Alternatives

- **ALT-001**: Store all settings in single JSON column per tenant instead of individual rows
  - *Pros*: Simpler schema, faster bulk retrieval, less database rows
  - *Cons*: No indexing on individual keys, harder to enforce validation per setting, no hierarchical inheritance
  - *Decision*: Not chosen - normalized schema better for type safety and validation

- **ALT-002**: Use environment variables (.env) for all configuration instead of database
  - *Pros*: Standard Laravel convention, no database queries needed, version controlled
  - *Cons*: Not runtime-configurable, no multi-tenancy support, no hierarchical inheritance, requires deployment for changes
  - *Decision*: Not chosen - ERP requires runtime configuration changes per tenant

- **ALT-003**: Use dedicated configuration service (Consul, etcd) instead of database + cache
  - *Pros*: Built for distributed config, watch for changes, high availability
  - *Cons*: Additional infrastructure, increased complexity, overkill for single-app ERP
  - *Decision*: Not chosen for MVP - database + Redis sufficient; can migrate later if needed

- **ALT-004**: Store encrypted settings in separate table
  - *Pros*: Isolates sensitive data, easier to apply specific security policies
  - *Cons*: Split schema complicates queries, no practical security benefit over column-level encryption
  - *Decision*: Not chosen - single table with encrypted type is simpler and equally secure

## 4. Dependencies

**Package Dependencies:**
- None (pure Laravel implementation using Eloquent, Cache, and Crypt facades)

**Internal Dependencies:**
- **azaharizaman/erp-multitenancy** (PRD01-SUB01) - Required for tenant context resolution
- **azaharizaman/erp-authentication** (PRD01-SUB02) - Optional for user-level settings
- **azaharizaman/erp-audit-logging** (PRD01-SUB03) - Optional for setting change audit trail

**Infrastructure Dependencies:**
- PostgreSQL 14+ OR MySQL 8.0+ for setting storage
- Redis 6+ OR Memcached for caching (mandatory, not optional)

## 5. Files

**Configuration:**
- `packages/settings-management/config/settings-management.php` - Package configuration

**Database:**
- `packages/settings-management/database/migrations/create_settings_table.php` - Settings storage
- `packages/settings-management/database/migrations/create_settings_history_table.php` - Change history
- `packages/settings-management/database/seeders/DefaultSettingsSeeder.php` - System default settings

**Models:**
- `packages/settings-management/src/Models/Setting.php` - Setting Eloquent model
- `packages/settings-management/src/Models/SettingHistory.php` - History Eloquent model

**Contracts:**
- `packages/settings-management/src/Contracts/SettingsServiceContract.php` - Service interface
- `packages/settings-management/src/Contracts/SettingsRepositoryContract.php` - Repository interface

**Repositories:**
- `packages/settings-management/src/Repositories/DatabaseSettingsRepository.php` - Database implementation

**Services:**
- `packages/settings-management/src/Services/SettingsService.php` - Core business logic

**Facades:**
- `packages/settings-management/src/Facades/Settings.php` - Convenient static API

**Controllers:**
- `packages/settings-management/src/Http/Controllers/SettingsController.php` - API endpoints

**Requests:**
- `packages/settings-management/src/Http/Requests/CreateSettingRequest.php` - Validation for creation
- `packages/settings-management/src/Http/Requests/UpdateSettingRequest.php` - Validation for updates
- `packages/settings-management/src/Http/Requests/BulkUpdateSettingsRequest.php` - Validation for bulk
- `packages/settings-management/src/Http/Requests/ImportSettingsRequest.php` - Validation for import

**Resources:**
- `packages/settings-management/src/Http/Resources/SettingResource.php` - JSON:API transformation
- `packages/settings-management/src/Http/Resources/SettingHistoryResource.php` - History transformation

**Middleware:**
- `packages/settings-management/src/Http/Middleware/InjectTenantToSettings.php` - Tenant context injection

**Policies:**
- `packages/settings-management/src/Policies/SettingPolicy.php` - Authorization policies

**Commands:**
- `packages/settings-management/src/Commands/WarmSettingsCacheCommand.php` - Cache warming

**Events:**
- `packages/settings-management/src/Events/SettingUpdatedEvent.php` - Setting updated notification
- `packages/settings-management/src/Events/SettingCreatedEvent.php` - Setting created notification
- `packages/settings-management/src/Events/CacheInvalidatedEvent.php` - Cache invalidation notification

**Exceptions:**
- `packages/settings-management/src/Exceptions/InvalidSettingTypeException.php` - Type casting errors
- `packages/settings-management/src/Exceptions/SettingNotFoundException.php` - Setting not found
- `packages/settings-management/src/Exceptions/UnauthorizedSettingScopeException.php` - Scope permission errors

**Service Provider:**
- `packages/settings-management/src/SettingsManagementServiceProvider.php` - Package registration

## 6. Testing

**Unit Tests (15):**
- **TEST-001**: SettingsService::castValue() correctly casts string, int, bool, array, json types
- **TEST-002**: SettingsService::castToStorage() correctly encodes array and JSON for storage
- **TEST-003**: SettingsService::get() resolves hierarchical inheritance (user → module → tenant → system)
- **TEST-004**: SettingsService::get() returns default value from metadata if setting not found
- **TEST-005**: SettingsService::set() encrypts value when type is 'encrypted' using Crypt facade
- **TEST-006**: SettingsService::set() invalidates cache for current and parent scopes
- **TEST-007**: DatabaseSettingsRepository::find() applies tenant_id filter for non-system scopes
- **TEST-008**: SettingsService::resolveHierarchy() checks user level before module level
- **TEST-009**: SettingsService::all() merges settings from system, tenant, module levels (overlay)
- **TEST-010**: SettingsService::getCacheKey() generates unique keys per scope, tenant, module, user
- **TEST-011**: SettingPolicy::create() requires super-admin for system scope
- **TEST-012**: SettingPolicy::update() prevents cross-tenant setting modification
- **TEST-013**: Setting model enforces unique constraint on (key, scope, tenant_id, module_name, user_id)
- **TEST-014**: Encrypted setting value is stored encrypted and decrypted on retrieval
- **TEST-015**: Setting with type 'array' is stored as JSON and returned as PHP array

**Feature Tests (12):**
- **TEST-016**: GET /api/v1/settings lists all settings for current tenant only
- **TEST-017**: GET /api/v1/settings/{key} resolves value from hierarchical inheritance
- **TEST-018**: POST /api/v1/settings creates new tenant-level setting
- **TEST-019**: POST /api/v1/settings with scope='system' requires super-admin (403 without)
- **TEST-020**: PATCH /api/v1/settings/{key} updates setting and invalidates cache
- **TEST-021**: DELETE /api/v1/settings/{key} deletes setting (reverts to default)
- **TEST-022**: POST /api/v1/settings/bulk updates multiple settings atomically
- **TEST-023**: GET /api/v1/settings/export returns JSON with all tenant settings
- **TEST-024**: POST /api/v1/settings/import imports settings from JSON file
- **TEST-025**: Tenant A cannot access or modify Tenant B's settings (tenant isolation)
- **TEST-026**: User-level setting overrides module-level setting for same key
- **TEST-027**: System-level default is used when tenant-level setting not defined

**Integration Tests (8):**
- **TEST-028**: SettingUpdatedEvent is dispatched when setting value changes
- **TEST-029**: SettingCreatedEvent is dispatched when new setting is created
- **TEST-030**: CacheInvalidatedEvent is dispatched after setting update
- **TEST-031**: Cache invalidation propagates to all parent scopes (user invalidates module, tenant, system)
- **TEST-032**: Multi-tenancy middleware injects tenant_id automatically into setting operations
- **TEST-033**: Settings::get() facade method retrieves value with correct type casting
- **TEST-034**: Settings history table records old and new values on update
- **TEST-035**: Cache warming command populates Redis with all tenant settings

**Performance Tests (5):**
- **TEST-036**: Cached setting retrieval completes in < 10ms (95th percentile)
- **TEST-037**: Cache miss retrieval completes in < 100ms (includes DB query, type cast, cache write)
- **TEST-038**: Bulk update of 1000 settings completes in < 30 seconds
- **TEST-039**: Settings query uses composite index on (key, scope, tenant_id) - verify with EXPLAIN
- **TEST-040**: Lazy loading of 10,000 tenant settings uses < 50MB memory

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: Cache staleness if cache invalidation fails (Redis unavailable during update)
  - *Mitigation*: Implement fallback to always-query-DB mode if cache unreachable, add health check for Redis, set short TTL (1 hour)
  
- **RISK-002**: Hierarchical resolution could cause N+1 queries if not cached properly
  - *Mitigation*: Always use cache-aside pattern, preload common settings via warming command, use lazy loading for bulk operations
  
- **RISK-003**: Encrypted setting keys could be accidentally logged or exposed in API responses
  - *Mitigation*: Never return encrypted values in API without explicit permission check, mask in logs, audit API responses
  
- **RISK-004**: Type casting errors could cause unexpected application behavior
  - *Mitigation*: Comprehensive validation in metadata, throw exceptions on invalid casts, add type validation tests
  
- **RISK-005**: Large metadata JSON could impact query performance
  - *Mitigation*: Limit metadata size to 1KB, index only essential columns, cache heavily, consider separate metadata table if needed

**Assumptions:**
- **ASSUMPTION-001**: Most tenants will have < 1000 active settings (10,000 is edge case)
- **ASSUMPTION-002**: Settings are read-heavy (90% reads, 10% writes) - cache is effective
- **ASSUMPTION-003**: Redis is reliable and available (99.9% uptime) for caching layer
- **ASSUMPTION-004**: Hierarchical inheritance is sufficient (no need for more than 4 levels)
- **ASSUMPTION-005**: Laravel's Crypt facade provides adequate encryption for sensitive settings (AES-256)
- **ASSUMPTION-006**: Setting keys follow dot notation convention consistently across all modules
- **ASSUMPTION-007**: Most settings changes are infrequent (< 100 updates/day per tenant)

## 8. Related PRD / Further Reading

**Primary PRD:**
- [PRD01-SUB05: Settings Management System](../prd/prd-01/PRD01-SUB05-SETTINGS-MANAGEMENT.md) - Complete feature requirements

**Related Sub-PRDs:**
- [PRD01-SUB01: Multi-Tenancy System](../prd/prd-01/PRD01-SUB01-MULTITENANCY.md) - Tenant context resolution
- [PRD01-SUB02: Authentication System](../prd/prd-01/PRD01-SUB02-AUTHENTICATION.md) - User identification
- [PRD01-SUB03: Audit Logging System](../prd/prd-01/PRD01-SUB03-AUDIT-LOGGING.md) - Setting change audit trail

**Master PRD:**
- [PRD01-MVP: Laravel ERP MVP](../prd/PRD01-MVP.md) - Overall system architecture

**External Documentation:**
- [Laravel Encryption](https://laravel.com/docs/12.x/encryption) - Crypt facade and encryption
- [Laravel Cache](https://laravel.com/docs/12.x/cache) - Cache-aside pattern and Redis
- [Cache-Aside Pattern](https://docs.microsoft.com/en-us/azure/architecture/patterns/cache-aside) - Caching best practices
- [Multi-Level Cache Hierarchy](https://en.wikipedia.org/wiki/Cache_hierarchy) - Hierarchical caching concepts
