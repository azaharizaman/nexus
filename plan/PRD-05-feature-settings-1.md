---
goal: Implement Hierarchical Settings Management System
version: 1.0
date_created: 2025-11-08
last_updated: 2025-11-08
owner: Core Domain Team
status: 'Planned'
tags: [feature, core, settings, configuration, phase-1, mvp]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan establishes a comprehensive, hierarchical settings management system for the Laravel ERP. The system provides global (system-wide), tenant-specific, and user-specific configuration storage with support for various data types, encryption for sensitive data, validation rules, and default values. This infrastructure enables flexible configuration of ERP behavior without code changes.

## 1. Requirements & Constraints

**Core Requirements:**
- **REQ-001**: Support three setting scopes: global (system), tenant, and user
- **REQ-002**: Implement setting hierarchy: user settings override tenant settings override global settings
- **REQ-003**: Support data types: string, integer, float, boolean, JSON, array
- **REQ-004**: Encrypt sensitive settings (API keys, passwords, tokens)
- **REQ-005**: Apply validation rules per setting (min/max, regex, enum values)
- **REQ-006**: Provide default values for all settings
- **REQ-007**: Support setting groups/categories for organization
- **REQ-008**: Implement caching for performance (1-hour TTL)
- **REQ-009**: Create fluent API for retrieving settings: settings()->get('key', 'default')
- **REQ-010**: Support dot notation for nested JSON settings: settings()->get('mail.smtp.host')
- **REQ-011**: Provide API endpoints for settings management
- **REQ-012**: Implement CLI commands for settings operations
- **REQ-013**: Log settings changes in audit trail
- **REQ-014**: Support bulk settings import/export

**Setting Categories:**
- **REQ-015**: System settings: app name, timezone, date format, locale, maintenance mode
- **REQ-016**: Tenant settings: company name, logo, primary color, default currency, tax rate
- **REQ-017**: Module settings: inventory valuation method, default warehouse, sales tax enabled
- **REQ-018**: Integration settings: email provider, SMS gateway, payment gateway credentials
- **REQ-019**: User preferences: language, theme, notifications enabled, dashboard layout

**Security Requirements:**
- **SEC-001**: Encrypt sensitive settings using Laravel's encryption
- **SEC-002**: Restrict global settings modification to Super Admin only
- **SEC-003**: Allow tenant admins to modify tenant settings only
- **SEC-004**: Allow users to modify own user preferences only
- **SEC-005**: Log all settings modifications with old/new values
- **SEC-006**: Prevent exposure of encrypted settings in API responses

**Performance Constraints:**
- **CON-001**: Settings retrieval must complete within 10ms (from cache)
- **CON-002**: Cache invalidation must be immediate on setting updates
- **CON-003**: Support minimum 1000 settings per tenant
- **CON-004**: Minimize database queries using eager loading and caching

**Integration Guidelines:**
- **GUD-001**: Use settings() helper function throughout application
- **GUD-002**: Define setting schemas in config file for validation
- **GUD-003**: Apply tenant scope to tenant and user settings
- **GUD-004**: Use repository pattern for settings data access
- **GUD-005**: Support environment variable fallback for critical settings

**Design Patterns:**
- **PAT-001**: Use repository pattern for settings CRUD operations
- **PAT-002**: Implement facade pattern for convenient settings access
- **PAT-003**: Apply strategy pattern for different setting scopes
- **PAT-004**: Use decorator pattern for encryption layer
- **PAT-005**: Implement observer pattern for cache invalidation

## 2. Implementation Steps

> **Note:** This implementation has been condensed from 15 phases into 6 logical phases for better manageability while preserving all 162 tasks and key details. Related components have been grouped together based on architectural layers and functional cohesion.

### Implementation Phase 1: Foundation (Database & Core Models)

- GOAL-001: Create settings table, model, enums, and seed default data
- **Combines:** Database Schema + Setting Model + Settings Seeder

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| **Database Schema** | | | |
| TASK-001 | Create settings table migration with columns: id, scope (enum: global/tenant/user), tenant_id (nullable FK), user_id (nullable FK), group, key, value (text), type (enum: string/integer/float/boolean/json/array), is_encrypted (boolean), created_at, updated_at | | |
| TASK-002 | Add unique constraint on (scope, tenant_id, user_id, key) to prevent duplicates | | |
| TASK-003 | Add indexes: (scope, key), (tenant_id, key), (user_id, key), group | | |
| TASK-004 | Create SettingScope enum in app/Domains/Core/Enums/SettingScope.php with values: GLOBAL, TENANT, USER | | |
| TASK-005 | Create SettingType enum in app/Domains/Core/Enums/SettingType.php with values: STRING, INTEGER, FLOAT, BOOLEAN, JSON, ARRAY | | |
| **Setting Model** | | | |
| TASK-006 | Create Setting model in app/Domains/Core/Models/Setting.php | | |
| TASK-007 | Add fillable fields: scope, tenant_id, user_id, group, key, value, type, is_encrypted | | |
| TASK-008 | Add casts: scope (SettingScope enum), type (SettingType enum), is_encrypted (boolean) | | |
| TASK-009 | Implement getValueAttribute() accessor to decrypt if is_encrypted is true | | |
| TASK-010 | Implement setValueAttribute() mutator to encrypt if setting is marked sensitive | | |
| TASK-011 | Implement automatic type casting based on type enum: cast to int, float, bool, array, object | | |
| TASK-012 | Add relationships: tenant() belongsTo, user() belongsTo | | |
| TASK-013 | Add LogsActivity trait for audit trail | | |
| TASK-014 | Add scope methods: scopeGlobal(), scopeTenant(), scopeUser() | | |
| **Settings Seeder** | | | |
| TASK-015 | Create SettingsSeeder in database/seeders/SettingsSeeder.php | | |
| TASK-016 | Seed global system settings with default values from schema | | |
| TASK-017 | Seed common tenant settings template | | |
| TASK-018 | Seed default user preferences template | | |
| TASK-019 | Apply scope and group correctly for each setting | | |
| TASK-020 | Call SettingsSeeder from DatabaseSeeder | | |

### Implementation Phase 2: Data Access Layer (Repository & Service)

- GOAL-002: Build repository, service layer, facade, and helper function
- **Combines:** Setting Repository + Settings Service + Settings Facade & Helper

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| **Setting Repository** | | | |
| TASK-021 | Create SettingsRepositoryContract in app/Domains/Core/Contracts/SettingsRepositoryContract.php | | |
| TASK-022 | Define methods: get(), set(), has(), forget(), all(), allByGroup(), getWithHierarchy() | | |
| TASK-023 | Implement SettingsRepository in app/Domains/Core/Repositories/SettingsRepository.php | | |
| TASK-024 | Implement get() method with scope parameter: look up user → tenant → global order | | |
| TASK-025 | Implement set() method to create or update setting | | |
| TASK-026 | Implement has() method to check if setting exists in hierarchy | | |
| TASK-027 | Implement forget() method to delete setting (soft delete if audit required) | | |
| TASK-028 | Implement all() method returning all settings for scope | | |
| TASK-029 | Implement allByGroup() method filtering by group | | |
| TASK-030 | Implement getWithHierarchy() to show effective value with source scope | | |
| TASK-031 | Apply caching with cache tags for efficient invalidation | | |
| TASK-032 | Bind SettingsRepositoryContract to SettingsRepository in service provider | | |
| **Settings Service** | | | |
| TASK-033 | Create SettingsService in app/Domains/Core/Services/SettingsService.php | | |
| TASK-034 | Inject SettingsRepository via constructor | | |
| TASK-035 | Implement get() method with default value support and dot notation parsing | | |
| TASK-036 | Implement set() method with validation against defined schema | | |
| TASK-037 | Implement has() method | | |
| TASK-038 | Implement forget() method | | |
| TASK-039 | Implement global() method to switch to global scope | | |
| TASK-040 | Implement tenant() method to switch to tenant scope | | |
| TASK-041 | Implement user() method to switch to user scope | | |
| TASK-042 | Implement group() method to filter by group | | |
| TASK-043 | Implement all() method returning collection of settings | | |
| TASK-044 | Implement cache invalidation on set/forget operations | | |
| **Settings Facade & Helper** | | | |
| TASK-045 | Create Settings facade in app/Facades/Settings.php | | |
| TASK-046 | Link facade to SettingsService | | |
| TASK-047 | Register facade in config/app.php aliases | | |
| TASK-048 | Create settings() helper function in app/Support/Helpers/settings.php | | |
| TASK-049 | Helper returns SettingsService instance or calls get() if key provided | | |
| TASK-050 | Support fluent usage: settings()->tenant()->get('key') | | |
| TASK-051 | Support simple usage: settings('key', 'default') | | |

### Implementation Phase 3: Configuration & Validation

- GOAL-003: Define setting schemas, implement validation, and configure caching
- **Combines:** Setting Schema Definition + Setting Validation + Cache Implementation

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| **Setting Schema Definition** | | | |
| TASK-052 | Create config/settings.php for setting schema definitions | | |
| TASK-053 | Define structure: ['key' => ['type' => 'string', 'default' => 'value', 'rules' => ['required', 'min:3'], 'encrypted' => false, 'group' => 'system']] | | |
|         | **Note:** The structure above uses PHP array syntax and is intended for the `config/settings.php` file. | | |
| TASK-054 | Define system settings: app.name, app.timezone, app.locale, app.date_format, maintenance.enabled | | |
| TASK-055 | Define tenant settings: company.name, company.logo, company.primary_color, company.default_currency, company.tax_rate | | |
| TASK-056 | Define module settings: inventory.valuation_method, inventory.default_warehouse, sales.tax_enabled, sales.discount_allowed | | |
| TASK-057 | Define integration settings: mail.provider, mail.api_key (encrypted), sms.provider, sms.api_key (encrypted) | | |
| TASK-058 | Define user preferences: ui.language, ui.theme, ui.dashboard_layout, notifications.email_enabled, notifications.sms_enabled | | |
| TASK-059 | Add validation rules for each setting | | |
| **Setting Validation** | | | |
| TASK-060 | Create ValidateSettingAction in app/Domains/Core/Actions/Settings/ValidateSettingAction.php | | |
| TASK-061 | Load setting schema from config/settings.php | | |
| TASK-062 | Validate type matches schema definition | | |
| TASK-063 | Apply Laravel validation rules from schema | | |
| TASK-064 | Validate enum values if restricted set defined | | |
| TASK-065 | Return validation errors with field-specific messages | | |
| TASK-066 | Call ValidateSettingAction in SettingsService.set() method | | |
| TASK-067 | Throw SettingValidationException on validation failure | | |
| **Cache Implementation** | | | |
| TASK-068 | Use Cache::tags(['settings', 'settings:{scope}', 'settings:{tenant_id}']) for organized caching | | |
| TASK-069 | Cache settings on first retrieval with 1-hour TTL | | |
| TASK-070 | Invalidate specific setting cache on update/delete | | |
| TASK-071 | Invalidate tenant settings cache tag when any tenant setting changes | | |
| TASK-072 | Invalidate user settings cache tag when any user setting changes | | |
| TASK-073 | Implement cache warming for global settings on application boot | | |
| TASK-074 | Add cache:clear-settings command for manual cache clearing | | |

### Implementation Phase 4: API Layer (Endpoints & Resources)

- GOAL-004: Build RESTful API endpoints, routes, and authorization policies
- **Combines:** API Endpoints + Routes Definition + Authorization Policies

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| **API Endpoints** | | | |
| TASK-075 | Create SettingsController in app/Http/Controllers/Api/V1/SettingsController.php | | |
| TASK-076 | Implement index() method listing settings by scope and group with filtering | | |
| TASK-077 | Implement show() method returning single setting by key | | |
| TASK-078 | Implement store() method creating new setting | | |
| TASK-079 | Implement update() method updating setting value | | |
| TASK-080 | Implement destroy() method deleting setting | | |
| TASK-081 | Implement bulkUpdate() method for updating multiple settings at once | | |
| TASK-082 | Create SettingResource in app/Http/Resources/SettingResource.php | | |
| TASK-083 | Hide encrypted values in API responses (show masked value or indicator) | | |
| TASK-084 | Include effective value with source scope information | | |
| TASK-085 | Create StoreSettingRequest in app/Http/Requests/StoreSettingRequest.php | | |
| TASK-086 | Create UpdateSettingRequest in app/Http/Requests/UpdateSettingRequest.php | | |
| TASK-087 | Apply validation using ValidateSettingAction | | |
| **Routes Definition** | | | |
| TASK-088 | Define routes in routes/api.php under /api/v1/settings prefix | | |
| TASK-089 | GET /api/v1/settings - List settings with filters | | |
| TASK-090 | GET /api/v1/settings/{key} - Get single setting | | |
| TASK-091 | POST /api/v1/settings - Create setting | | |
| TASK-092 | PUT /api/v1/settings/{key} - Update setting | | |
| TASK-093 | DELETE /api/v1/settings/{key} - Delete setting | | |
| TASK-094 | POST /api/v1/settings/bulk - Bulk update settings | | |
| TASK-095 | GET /api/v1/settings/export - Export settings | | |
| TASK-096 | POST /api/v1/settings/import - Import settings | | |
| TASK-097 | Apply auth:sanctum middleware to all routes | | |
| **Authorization Policies** | | | |
| TASK-098 | Create SettingPolicy in app/Domains/Core/Policies/SettingPolicy.php | | |
| TASK-099 | Implement viewAny() checking scope: global requires 'manage global settings' permission, tenant requires 'manage tenant settings' | | |
| TASK-100 | Implement view() checking setting scope and user permissions | | |
| TASK-101 | Implement create() checking scope-appropriate permission | | |
| TASK-102 | Implement update() checking scope and same tenant | | |
| TASK-103 | Implement delete() checking scope and permissions | | |
| TASK-104 | Implement special check: users can always manage own user-scoped settings | | |
| TASK-105 | Register SettingPolicy in AuthServiceProvider | | |
| TASK-106 | Apply policy checks in SettingsController | | |

### Implementation Phase 5: Bulk Operations & CLI

- GOAL-005: Implement import/export and CLI commands for settings management
- **Combines:** Settings Import/Export + CLI Commands

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| **Settings Import/Export** | | | |
| TASK-107 | Create ExportSettingsAction in app/Domains/Core/Actions/Settings/ExportSettingsAction.php | | |
| TASK-108 | Accept scope and group filters | | |
| TASK-109 | Export to JSON format with structure: {key: value} | | |
| TASK-110 | Mask encrypted settings in export | | |
| TASK-111 | Create ImportSettingsAction in app/Domains/Core/Actions/Settings/ImportSettingsAction.php | | |
| TASK-112 | Accept JSON file and scope parameter | | |
| TASK-113 | Validate each setting against schema | | |
| TASK-114 | Bulk upsert settings | | |
| TASK-115 | Return import summary: created, updated, failed counts | | |
| TASK-116 | Add export endpoint: GET /api/v1/settings/export | | |
| TASK-117 | Add import endpoint: POST /api/v1/settings/import | | |
| **CLI Commands** | | | |
| TASK-118 | Create ListSettingsCommand in app/Console/Commands/Settings/ListSettingsCommand.php with signature erp:settings:list | | |
| TASK-119 | Add options: --scope, --group, --tenant | | |
| TASK-120 | Display table with columns: Key, Value, Type, Scope, Group | | |
| TASK-121 | Mask encrypted values in output | | |
| TASK-122 | Create GetSettingCommand in app/Console/Commands/Settings/GetSettingCommand.php with signature erp:settings:get {key} | | |
| TASK-123 | Display setting value, type, scope, and effective source | | |
| TASK-124 | Create SetSettingCommand in app/Console/Commands/Settings/SetSettingCommand.php with signature erp:settings:set {key} {value} | | |
| TASK-125 | Add options: --scope, --type, --encrypted, --tenant, --user | | |
| TASK-126 | Validate value against schema | | |
| TASK-127 | Display confirmation of setting update | | |
| TASK-128 | Create ExportSettingsCommand in app/Console/Commands/Settings/ExportSettingsCommand.php with signature erp:settings:export {file} | | |
| TASK-129 | Add options: --scope, --group, --tenant | | |
| TASK-130 | Export to specified JSON file | | |
| TASK-131 | Create ImportSettingsCommand in app/Console/Commands/Settings/ImportSettingsCommand.php with signature erp:settings:import {file} | | |
| TASK-132 | Add options: --scope, --tenant, --dry-run | | |
| TASK-133 | Import from JSON file and display summary | | |
| TASK-134 | Register commands in app/Console/Kernel.php | | |

### Implementation Phase 6: Testing & Verification

- GOAL-006: Create comprehensive test suite for settings system

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| **Unit Tests** | | | |
| TASK-135 | Create SettingsTest feature test in tests/Feature/Core/SettingsTest.php | | |
| TASK-136 | Test settings() helper returns SettingsService instance | | |
| TASK-137 | Test get() with hierarchy: user setting overrides tenant overrides global | | |
| TASK-138 | Test set() creates new setting | | |
| TASK-139 | Test set() updates existing setting | | |
| TASK-140 | Test set() validates value against schema | | |
| TASK-141 | Test encrypted setting stores encrypted value | | |
| TASK-142 | Test encrypted setting retrieves decrypted value | | |
| TASK-143 | Test has() returns true for existing setting | | |
| TASK-144 | Test forget() deletes setting | | |
| TASK-145 | Test dot notation for nested JSON settings | | |
| **API Tests** | | | |
| TASK-146 | Test GET /api/v1/settings returns settings list | | |
| TASK-147 | Test filtering by scope and group | | |
| TASK-148 | Test PUT /api/v1/settings/{key} updates setting | | |
| TASK-149 | Test unauthorized user cannot modify global settings (403) | | |
| TASK-150 | Test tenant admin can modify tenant settings | | |
| TASK-151 | Test user can modify own user settings | | |
| TASK-152 | Test settings export generates JSON | | |
| TASK-153 | Test settings import updates settings | | |
| **Repository & Service Tests** | | | |
| TASK-154 | Create SettingsRepositoryTest unit test in tests/Unit/Core/SettingsRepositoryTest.php | | |
| TASK-155 | Test hierarchy resolution logic | | |
| TASK-156 | Test caching behavior | | |
| TASK-157 | Test cache invalidation on update | | |
| **CLI Tests** | | | |
| TASK-158 | Test CLI command php artisan erp:settings:list | | |
| TASK-159 | Test CLI command php artisan erp:settings:set | | |
| **Integration Tests** | | | |
| TASK-160 | Test settings hierarchy across all three scopes | | |
| TASK-161 | Test settings audit logging captures changes | | |
| TASK-162 | Test cache performance improvement | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-135 | Create SettingsTest feature test in tests/Feature/Core/SettingsTest.php | | |
| TASK-136 | Test settings() helper returns SettingsService instance | | |
| TASK-137 | Test get() with hierarchy: user setting overrides tenant overrides global | | |
| TASK-138 | Test set() creates new setting | | |
| TASK-139 | Test set() updates existing setting | | |
| TASK-140 | Test set() validates value against schema | | |
| TASK-141 | Test encrypted setting stores encrypted value | | |
| TASK-142 | Test encrypted setting retrieves decrypted value | | |
| TASK-143 | Test has() returns true for existing setting | | |
| TASK-144 | Test forget() deletes setting | | |
| TASK-145 | Test dot notation for nested JSON settings | | |
| TASK-146 | Test GET /api/v1/settings returns settings list | | |
| TASK-147 | Test filtering by scope and group | | |
| TASK-148 | Test PUT /api/v1/settings/{key} updates setting | | |
| TASK-149 | Test unauthorized user cannot modify global settings (403) | | |
| TASK-150 | Test tenant admin can modify tenant settings | | |
| TASK-151 | Test user can modify own user settings | | |
| TASK-152 | Test settings export generates JSON | | |
| TASK-153 | Test settings import updates settings | | |
| TASK-154 | Create SettingsRepositoryTest unit test in tests/Unit/Core/SettingsRepositoryTest.php | | |
| TASK-155 | Test hierarchy resolution logic | | |
| TASK-156 | Test caching behavior | | |
| TASK-157 | Test cache invalidation on update | | |

## 3. Alternatives

- **ALT-001**: Use Laravel's built-in config() system - Rejected because config is for application configuration, not runtime user settings. Config requires code deployment to change, doesn't support multi-tenant scoping.

- **ALT-002**: Third-party package (e.g., spatie/laravel-settings) - Considered but custom implementation provides exact requirements (hierarchy, encryption, validation) without package limitations.

- **ALT-003**: Store settings in JSON columns on tenant/user models - Rejected because it lacks structured querying, validation, audit trail, and makes cross-tenant settings management difficult.

- **ALT-004**: Redis-only storage for settings - Rejected because settings need persistence and audit trail. Redis used only for caching layer.

- **ALT-005**: Environment variables for all settings - Rejected because env vars don't support runtime changes, multi-tenancy, or user preferences.

## 4. Dependencies

- **DEP-001**: Laravel 12.x framework installed and configured
- **DEP-002**: PHP 8.2+ for enums and typed properties
- **DEP-003**: Tenant system (PRD-01) for tenant-scoped settings
- **DEP-004**: User authentication (PRD-02) for user-scoped settings
- **DEP-005**: Audit logging (PRD-03) for settings change tracking
- **DEP-006**: Cache driver (Redis recommended) for performance
- **DEP-007**: Laravel's encryption key configured in .env

## 5. Files

**New Files to Create:**
- **FILE-001**: database/migrations/YYYY_MM_DD_HHMMSS_create_settings_table.php - Settings table
- **FILE-002**: app/Domains/Core/Enums/SettingScope.php - Scope enum
- **FILE-003**: app/Domains/Core/Enums/SettingType.php - Type enum
- **FILE-004**: app/Domains/Core/Models/Setting.php - Setting model
- **FILE-005**: app/Domains/Core/Contracts/SettingsRepositoryContract.php - Repository interface
- **FILE-006**: app/Domains/Core/Repositories/SettingsRepository.php - Repository implementation
- **FILE-007**: app/Domains/Core/Services/SettingsService.php - Settings service
- **FILE-008**: app/Facades/Settings.php - Settings facade
- **FILE-009**: app/Support/Helpers/settings.php - Helper function
- **FILE-010**: app/Domains/Core/Actions/Settings/ValidateSettingAction.php - Validation action
- **FILE-011**: app/Domains/Core/Actions/Settings/ExportSettingsAction.php - Export action
- **FILE-012**: app/Domains/Core/Actions/Settings/ImportSettingsAction.php - Import action
- **FILE-013**: app/Domains/Core/Exceptions/SettingValidationException.php - Custom exception
- **FILE-014**: app/Http/Controllers/Api/V1/SettingsController.php - API controller
- **FILE-015**: app/Http/Resources/SettingResource.php - API resource
- **FILE-016**: app/Http/Requests/StoreSettingRequest.php - Store validation
- **FILE-017**: app/Http/Requests/UpdateSettingRequest.php - Update validation
- **FILE-018**: app/Domains/Core/Policies/SettingPolicy.php - Authorization policy
- **FILE-019**: app/Console/Commands/Settings/ListSettingsCommand.php - CLI list
- **FILE-020**: app/Console/Commands/Settings/GetSettingCommand.php - CLI get
- **FILE-021**: app/Console/Commands/Settings/SetSettingCommand.php - CLI set
- **FILE-022**: app/Console/Commands/Settings/ExportSettingsCommand.php - CLI export
- **FILE-023**: app/Console/Commands/Settings/ImportSettingsCommand.php - CLI import
- **FILE-024**: database/seeders/SettingsSeeder.php - Settings seeder
- **FILE-025**: config/settings.php - Setting schema definitions

**Files to Modify:**
- **FILE-026**: config/app.php - Register Settings facade
- **FILE-027**: app/Providers/AuthServiceProvider.php - Register SettingPolicy
- **FILE-028**: routes/api.php - Define settings routes
- **FILE-029**: database/seeders/DatabaseSeeder.php - Call SettingsSeeder

**Test Files:**
- **FILE-030**: tests/Feature/Core/SettingsTest.php - Feature tests
- **FILE-031**: tests/Unit/Core/SettingsRepositoryTest.php - Repository unit tests
- **FILE-032**: tests/Unit/Core/SettingsServiceTest.php - Service unit tests

## 6. Testing

**Unit Tests:**
- **TEST-001**: Test Setting model encrypts sensitive values
- **TEST-002**: Test Setting model decrypts on retrieval
- **TEST-003**: Test Setting model casts values based on type
- **TEST-004**: Test SettingsRepository get() with hierarchy
- **TEST-005**: Test SettingsRepository caching behavior
- **TEST-006**: Test SettingsRepository cache invalidation
- **TEST-007**: Test ValidateSettingAction validates types
- **TEST-008**: Test ValidateSettingAction applies rules
- **TEST-009**: Test SettingsService dot notation parsing

**Feature Tests:**
- **TEST-010**: Test settings() helper function works
- **TEST-011**: Test get() returns default if setting doesn't exist
- **TEST-012**: Test get() returns user setting over tenant setting
- **TEST-013**: Test get() returns tenant setting over global setting
- **TEST-014**: Test set() creates new setting
- **TEST-015**: Test set() updates existing setting
- **TEST-016**: Test set() validates against schema (422 on invalid)
- **TEST-017**: Test encrypted setting stores/retrieves correctly
- **TEST-018**: Test has() returns correct boolean
- **TEST-019**: Test forget() deletes setting
- **TEST-020**: Test GET /api/v1/settings returns filtered list
- **TEST-021**: Test POST /api/v1/settings creates setting
- **TEST-022**: Test PUT /api/v1/settings/{key} updates setting
- **TEST-023**: Test DELETE /api/v1/settings/{key} deletes setting
- **TEST-024**: Test authorization: Super Admin can modify global
- **TEST-025**: Test authorization: Tenant Admin can modify tenant
- **TEST-026**: Test authorization: User can modify own settings
- **TEST-027**: Test export generates JSON correctly
- **TEST-028**: Test import updates settings from JSON
- **TEST-029**: Test CLI command php artisan erp:settings:list
- **TEST-030**: Test CLI command php artisan erp:settings:set

**Integration Tests:**
- **TEST-031**: Test settings hierarchy across all three scopes
- **TEST-032**: Test settings audit logging captures changes
- **TEST-033**: Test cache performance improvement

**Test Coverage by Phase:**
- **Phase 1 (Foundation):** TEST-001, TEST-002, TEST-003
- **Phase 2 (Data Access Layer):** TEST-004, TEST-005, TEST-006, TEST-009, TEST-010, TEST-011, TEST-012, TEST-013
- **Phase 3 (Configuration & Validation):** TEST-007, TEST-008, TEST-016
- **Phase 4 (API Layer):** TEST-020, TEST-021, TEST-022, TEST-023, TEST-024, TEST-025, TEST-026
- **Phase 5 (Bulk Operations & CLI):** TEST-027, TEST-028, TEST-029, TEST-030
- **Phase 6 (Testing & Verification):** TEST-014, TEST-015, TEST-017, TEST-018, TEST-019, TEST-031, TEST-032, TEST-033

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: Cache inconsistency across multiple servers - Mitigation: Use shared cache (Redis), implement cache tagging, monitor cache health
- **RISK-002**: Encryption key rotation complexity - Mitigation: Document key rotation procedure, support re-encryption command
- **RISK-003**: Schema changes requiring setting migration - Mitigation: Version schemas, support backward compatibility
- **RISK-004**: Performance degradation with large setting count - Mitigation: Pagination, indexing, query optimization

**Assumptions:**
- **ASSUMPTION-001**: Settings count per tenant remains under 1000
- **ASSUMPTION-002**: Setting value size remains under 64KB (text column limit)
- **ASSUMPTION-003**: 1-hour cache TTL is acceptable for most settings
- **ASSUMPTION-004**: Three-level hierarchy (global/tenant/user) is sufficient
- **ASSUMPTION-005**: JSON format is adequate for import/export
- **ASSUMPTION-006**: Setting schema definitions in config file are manageable
- **ASSUMPTION-007**: Encrypted settings don't need to be searchable

## 8. Related Specifications / Further Reading

- [PHASE-1-MVP.md](../docs/prd/PHASE-1-MVP.md) - Overall Phase 1 requirements
- [PRD-01-infrastructure-multitenancy-1.md](./PRD-01-infrastructure-multitenancy-1.md) - Multi-tenancy system
- [PRD-02-infrastructure-auth-1.md](./PRD-02-infrastructure-auth-1.md) - Authentication system
- [PRD-03-infrastructure-audit-1.md](./PRD-03-infrastructure-audit-1.md) - Audit logging
- [Laravel Cache Documentation](https://laravel.com/docs/12.x/cache)
- [Laravel Encryption Documentation](https://laravel.com/docs/12.x/encryption)
- [Configuration Management Best Practices](https://12factor.net/config)
- [MODULE-DEVELOPMENT.md](../docs/prd/MODULE-DEVELOPMENT.md) - Module development guidelines
