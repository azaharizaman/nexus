---
plan: Implement UOM API Endpoints and Module Integration
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, api, uom, integration, rest-api, validation]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan delivers the RESTful API layer for Unit of Measure management and integrates UOM functionality across Inventory, Sales, and Purchasing modules. It implements versioned API endpoints (`/api/v1/uoms`), request validation with Form Requests, API Resources for consistent response formatting, and provides integration points for other modules to perform UOM conversions and validations. This plan completes the UOM Management System by exposing all functionality via secure, well-documented APIs.

## 1. Requirements & Constraints

**Functional Requirements:**
- **FR-UOM-001**: Manage **UOMs with CRUD operations** (Create, Read, Update, Delete, List)
- **FR-UOM-005**: Provide **Automatic Conversion** logic to translate quantities between compatible units
- **FR-UOM-006**: Expose UOM functionality via **RESTful API endpoints**

**Integration Requirements:**
- **IR-UOM-001**: Provide UOM conversion API for **all modules** (Inventory, Sales, Purchasing)
- **IR-UOM-002**: Enable **UOM validation** in item master data and transaction documents

**Performance Requirements:**
- **PR-UOM-002**: Conversion calculations MUST complete in **< 5ms**

**Security Requirements:**
- **SR-UOM-001**: Only **authenticated users** can access UOM APIs
- **SR-UOM-002**: Only **tenant-scoped users** can create/update/delete custom UOMs

**Events:**
- **EV-UOM-001**: Dispatch `UomCreated` event when new UOM is created
- **EV-UOM-002**: Dispatch `UomUpdated` event when UOM is modified
- **EV-UOM-003**: Dispatch `UomDeleted` event when UOM is deleted (soft delete)

**Constraints:**
- **CON-001**: All API endpoints must be versioned (`/api/v1/uoms`)
- **CON-002**: All responses must use API Resource transformers (no raw model returns)
- **CON-003**: Custom tenant UOMs cannot override or delete system UOMs (is_system=true)
- **CON-004**: Cannot delete UOM if referenced by any transaction (inventory_items, purchase_order_items, etc.)

**Guidelines:**
- **GUD-001**: Use Form Request classes for validation (StoreUomRequest, UpdateUomRequest)
- **GUD-002**: Use API Resource classes for response transformation (UomResource, UomCollection)
- **GUD-003**: Use Laravel Actions in controllers (thin controller pattern)
- **GUD-004**: Apply rate limiting to API endpoints (60 requests/minute per user)

**Patterns:**
- **PAT-001**: RESTful resource routing with standard HTTP verbs
- **PAT-002**: Action-based controller methods (CreateUomAction, UpdateUomAction)
- **PAT-003**: Event-driven integration for cross-module updates

## 2. Implementation Steps

### GOAL-001: Create API Resource Controllers with Authentication

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-UOM-001, FR-UOM-006, SR-UOM-001, SR-UOM-002 | Implement RESTful API controller with authentication middleware, tenant scoping, and standard CRUD operations | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create `app/Http/Controllers/Api/V1/UomController.php` with namespace. Add `declare(strict_types=1);`. Apply route attributes: `#[Prefix('api/v1')]`, `#[Middleware(['auth:sanctum', 'tenant'])]`. This ensures all routes require authentication and tenant context | | |
| TASK-002 | Inject dependencies via constructor: `UomRepositoryContract $repository`, `UomConversionService $conversionService`. Use readonly properties for immutability | | |
| TASK-003 | Implement `index(Request $request): JsonResponse` method. Add `#[Get('/uoms', name: 'uoms.index')]` attribute. Support query parameters: `?category={category}`, `?is_active={bool}`, `?search={term}`, `?per_page={int}`. Return paginated results using `UomResource::collection($uoms)->response()` | | |
| TASK-004 | Implement `show(string $code): JsonResponse` method. Add `#[Get('/uoms/{code}', name: 'uoms.show')]` attribute. Use route model binding with `Uom::where('code', $code)`. Return 404 if not found. Return `UomResource::make($uom)->response()` | | |
| TASK-005 | Implement `store(StoreUomRequest $request): JsonResponse` method. Add `#[Post('/uoms', name: 'uoms.store')]` attribute. Use validated data to call `CreateUomAction::run($request->validated())`. Return 201 Created with `UomResource::make($uom)->response()->setStatusCode(201)`. Dispatch `UomCreated` event | | |
| TASK-006 | Implement `update(UpdateUomRequest $request, string $code): JsonResponse` method. Add `#[Patch('/uoms/{code}', name: 'uoms.update')]` attribute. Prevent updates to system UOMs (is_system=true) - return 403 Forbidden. Use `UpdateUomAction::run($uom, $request->validated())`. Return `UomResource::make($uom)->response()`. Dispatch `UomUpdated` event | | |
| TASK-007 | Implement `destroy(string $code): JsonResponse` method. Add `#[Delete('/uoms/{code}', name: 'uoms.destroy')]` attribute. Check if UOM is system (forbidden) or in use (conflict). Use soft delete. Return 204 No Content on success. Dispatch `UomDeleted` event | | |
| TASK-008 | Add rate limiting: Apply `#[Middleware('throttle:api')]` to all methods. Configure in `app/Providers/RouteServiceProvider.php`: `RateLimiter::for('api', fn(Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()))` | | |

### GOAL-002: Implement Form Requests for Validation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-UOM-001, SR-UOM-002, CON-003 | Create validation rules for UOM creation and updates with tenant scoping, uniqueness checks, and business rule enforcement | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-009 | Create `app/Http/Requests/Api/V1/StoreUomRequest.php` extending FormRequest. Add `declare(strict_types=1);`. Implement `authorize(): bool` returning `$this->user()->can('create', Uom::class)` | | |
| TASK-010 | In StoreUomRequest, implement `rules(): array` returning validation rules: `['code' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('uoms')->where('tenant_id', auth()->user()->tenant_id)], 'name' => ['required', 'string', 'max:100'], 'category' => ['required', 'string', Rule::in(UomCategory::values())], 'base_unit' => ['required', 'string', 'exists:uoms,code'], 'conversion_factor' => ['required', 'numeric', 'gt:0'], 'precision' => ['nullable', 'integer', 'between:0,10'], 'is_active' => ['nullable', 'boolean'], 'metadata' => ['nullable', 'array']]` | | |
| TASK-011 | In StoreUomRequest, add `messages(): array` with custom validation messages: code.unique → "UOM code must be unique within your tenant", base_unit.exists → "Base unit must be an existing UOM code", etc. | | |
| TASK-012 | In StoreUomRequest, implement `prepareForValidation(): void` to set defaults: `$this->merge(['tenant_id' => auth()->user()->tenant_id, 'is_system' => false, 'is_active' => $this->is_active ?? true, 'precision' => $this->precision ?? 4])` | | |
| TASK-013 | Create `app/Http/Requests/Api/V1/UpdateUomRequest.php` extending FormRequest. Copy authorize() from StoreUomRequest but check 'update' permission. Copy rules() but make code validation use `Rule::unique()->where('tenant_id', ...)->ignore($this->route('code'), 'code')` for existing record | | |
| TASK-014 | In UpdateUomRequest, add `withValidator(Validator $validator): void` to add after-validation check: prevent updating system UOMs by checking `Uom::where('code', $this->route('code'))->value('is_system')`. Add error using `$validator->errors()->add('code', 'Cannot modify system UOMs')` | | |

### GOAL-003: Create API Resources for Response Transformation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| CON-002, FR-UOM-006 | Implement JSON:API compliant resource transformers with computed fields, relationships, and consistent formatting | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-015 | Create `app/Http/Resources/Api/V1/UomResource.php` extending JsonResource. Add `declare(strict_types=1);`. Import `use Illuminate\Http\Request;` | | |
| TASK-016 | Implement `toArray(Request $request): array` method returning: `['id' => $this->id, 'code' => $this->code, 'name' => $this->name, 'category' => $this->category->value, 'category_label' => $this->category->label(), 'base_unit' => $this->base_unit, 'conversion_factor' => (string) $this->conversion_factor, 'precision' => $this->precision, 'is_base_unit' => $this->isBaseUnit(), 'is_active' => $this->is_active, 'is_system' => $this->is_system, 'metadata' => $this->metadata, 'tenant_id' => $this->tenant_id, 'created_at' => $this->created_at?->toIso8601String(), 'updated_at' => $this->updated_at?->toIso8601String()]`. Note conversion_factor cast to string to preserve precision | | |
| TASK-017 | Add `with(Request $request): array` method for meta information: `return ['meta' => ['version' => 'v1', 'timestamp' => now()->toIso8601String()]];` | | |
| TASK-018 | Create `app/Http/Resources/Api/V1/UomCollection.php` extending ResourceCollection. Override `toArray()` to add pagination meta: `return ['data' => $this->collection, 'links' => ['self' => $request->url()], 'meta' => ['total' => $this->total(), 'per_page' => $this->perPage(), 'current_page' => $this->currentPage(), 'last_page' => $this->lastPage()]]` | | |

### GOAL-004: Implement Conversion API Endpoint

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-UOM-005, IR-UOM-001, PR-UOM-002 | Expose UOM conversion functionality via dedicated API endpoint for use by Inventory, Sales, and Purchasing modules | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-019 | Add `convert(ConvertUomRequest $request): JsonResponse` method to UomController. Add `#[Post('/uoms/convert', name: 'uoms.convert')]` attribute. This endpoint will be used by all modules for quantity conversions | | |
| TASK-020 | In convert() method, extract validated data: `$quantity = $request->input('quantity'); $fromUom = $request->input('from_uom'); $toUom = $request->input('to_uom');`. Call `ConvertQuantityAction::run($quantity, $fromUom, $toUom, auth()->user()->tenant_id)`. Return JSON: `['result' => ['quantity' => $result['quantity'], 'from_uom' => $result['from_uom'], 'to_uom' => $result['to_uom'], 'conversion_factor' => $result['conversion_factor'], 'converted_at' => now()->toIso8601String()]]` | | |
| TASK-021 | Create `app/Http/Requests/Api/V1/ConvertUomRequest.php` with validation rules: `['quantity' => ['required', 'numeric', 'gt:0'], 'from_uom' => ['required', 'string', 'exists:uoms,code'], 'to_uom' => ['required', 'string', 'exists:uoms,code']]`. Add custom rule to validate compatibility: `$this->after(function($validator) { if (!ValidateUomCompatibilityAction::run($this->from_uom, $this->to_uom)) { $validator->errors()->add('to_uom', 'Incompatible unit categories'); }})` | | |
| TASK-022 | Add performance logging to convert() method: log warning if conversion takes > 5ms. Use microtime(true) before/after action call. Log includes: duration, from_uom, to_uom, quantity for performance monitoring | | |

### GOAL-005: Create UOM Events and Integration Helpers

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| EV-UOM-001, EV-UOM-002, EV-UOM-003, IR-UOM-002 | Implement domain events for UOM lifecycle changes and provide helper functions for seamless module integration | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-023 | Create `app/Domains/UnitOfMeasure/Events/UomCreated.php` implementing `ShouldBroadcast`. Add `declare(strict_types=1);`. Constructor accepts `public readonly Uom $uom`. Implement `broadcastOn(): array` returning `[new Channel('uoms')]`. This event notifies other modules of new UOM availability | | |
| TASK-024 | Create `app/Domains/UnitOfMeasure/Events/UomUpdated.php` following same pattern as UomCreated. Include both old and new values: `public readonly Uom $uom, public readonly array $changes`. Broadcast on `uoms` channel | | |
| TASK-025 | Create `app/Domains/UnitOfMeasure/Events/UomDeleted.php` with `public readonly string $code, public readonly string $category`. Broadcast on `uoms` channel. This allows modules to handle UOM removal gracefully | | |
| TASK-026 | Dispatch events in Actions: Update `CreateUomAction` to dispatch `UomCreated` after creation. Update `UpdateUomAction` to dispatch `UomUpdated` with `$model->getChanges()`. Update soft delete to dispatch `UomDeleted` | | |
| TASK-027 | Create helper file `app/Domains/UnitOfMeasure/helpers.php` with functions: `function uom_convert(string|float $quantity, string $fromUom, string $toUom): string`, `function uom_compatible(string $uom1, string $uom2): bool`, `function uom_category(string $code): string`. Register in `composer.json` autoload.files | | |
| TASK-028 | Create `app/Domains/UnitOfMeasure/Traits/HasUom.php` trait for use in models (InventoryItem, PurchaseOrderItem, SalesOrderItem). Trait provides: `uom()` relationship, `convertTo(string $targetUom): string` method that converts model's quantity, `isCompatibleWith(string $targetUom): bool` validation method | | |

## 3. Alternatives

- **ALT-001**: Use GraphQL instead of REST API - **Rejected** because REST is simpler for CRUD operations, better supported by Laravel, and easier to cache. GraphQL adds complexity without clear benefit for UOM use case
- **ALT-002**: Include conversion endpoint in each module (Inventory, Sales) - **Rejected** because DRY principle violation, harder to maintain, and no single source of truth
- **ALT-003**: Use POST for all operations (no PUT/PATCH/DELETE) - **Rejected** because violates RESTful principles, reduces clarity, and breaks HTTP semantic conventions
- **ALT-004**: Return nested tenant information in UomResource - **Rejected** because creates N+1 query issues, violates single responsibility, and increases payload size unnecessarily
- **ALT-005**: Store API responses in database for auditing - **Rejected** because Activity Log already tracks changes, storage costs high, and query performance impact

## 4. Dependencies

**Package Dependencies:**
- **DEP-001**: `laravel/sanctum` ^4.0 (API authentication)
- **DEP-002**: `lorisleiva/laravel-actions` ^2.0 (Action pattern)

**Internal Dependencies:**
- **DEP-003**: PRD01-SUB06-PLAN01 (UOM Foundation) - Uom model and repository
- **DEP-004**: PRD01-SUB06-PLAN02 (UOM Conversion Engine) - ConvertQuantityAction, UomConversionService
- **DEP-005**: PRD01-SUB02 (Authentication System) - Sanctum token authentication
- **DEP-006**: PRD01-SUB01 (Multi-Tenancy) - Tenant middleware and scoping

**Infrastructure:**
- **DEP-007**: Redis (recommended for rate limiting and API response caching)

## 5. Files

**Controllers:**
- `app/Http/Controllers/Api/V1/UomController.php` - RESTful API controller with 7 endpoints

**Form Requests:**
- `app/Http/Requests/Api/V1/StoreUomRequest.php` - Validation for UOM creation
- `app/Http/Requests/Api/V1/UpdateUomRequest.php` - Validation for UOM updates
- `app/Http/Requests/Api/V1/ConvertUomRequest.php` - Validation for conversion requests

**API Resources:**
- `app/Http/Resources/Api/V1/UomResource.php` - Single UOM response transformer
- `app/Http/Resources/Api/V1/UomCollection.php` - Paginated UOM list transformer

**Events:**
- `app/Domains/UnitOfMeasure/Events/UomCreated.php` - UOM creation event
- `app/Domains/UnitOfMeasure/Events/UomUpdated.php` - UOM update event
- `app/Domains/UnitOfMeasure/Events/UomDeleted.php` - UOM deletion event

**Actions (updated):**
- `app/Domains/UnitOfMeasure/Actions/CreateUomAction.php` - Add event dispatching
- `app/Domains/UnitOfMeasure/Actions/UpdateUomAction.php` - Add event dispatching

**Helpers:**
- `app/Domains/UnitOfMeasure/helpers.php` - Global helper functions

**Traits:**
- `app/Domains/UnitOfMeasure/Traits/HasUom.php` - Trait for models with UOM relationships

**Configuration (updated):**
- `config/sanctum.php` - API token configuration
- `app/Providers/RouteServiceProvider.php` - Rate limiting configuration
- `composer.json` - Register helpers.php in autoload.files

## 6. Testing

**Unit Tests (8 tests):**
- **TEST-001**: `test_uom_resource_transforms_model_correctly` - Verify all fields present and correctly formatted
- **TEST-002**: `test_uom_resource_casts_conversion_factor_to_string` - Verify precision preservation
- **TEST-003**: `test_uom_collection_includes_pagination_meta` - Verify meta fields present
- **TEST-004**: `test_store_request_validates_required_fields` - Test validation rules
- **TEST-005**: `test_store_request_prevents_duplicate_code` - Test uniqueness validation
- **TEST-006**: `test_update_request_allows_same_code` - Test ignore validation
- **TEST-007**: `test_convert_request_validates_compatibility` - Test custom validation rule
- **TEST-008**: `test_helper_functions_work_correctly` - Test uom_convert(), uom_compatible()

**Feature Tests (12 tests):**
- **TEST-009**: `test_index_returns_paginated_uoms` - GET /api/v1/uoms
- **TEST-010**: `test_index_filters_by_category` - GET /api/v1/uoms?category=mass
- **TEST-011**: `test_show_returns_single_uom` - GET /api/v1/uoms/{code}
- **TEST-012**: `test_show_returns_404_for_invalid_code` - GET /api/v1/uoms/invalid
- **TEST-013**: `test_store_creates_new_uom` - POST /api/v1/uoms
- **TEST-014**: `test_store_returns_validation_errors` - POST with invalid data
- **TEST-015**: `test_update_modifies_existing_uom` - PATCH /api/v1/uoms/{code}
- **TEST-016**: `test_update_prevents_system_uom_modification` - PATCH system UOM returns 403
- **TEST-017**: `test_destroy_soft_deletes_uom` - DELETE /api/v1/uoms/{code}
- **TEST-018**: `test_destroy_prevents_deletion_if_in_use` - DELETE returns 409 if referenced
- **TEST-019**: `test_convert_endpoint_returns_correct_result` - POST /api/v1/uoms/convert
- **TEST-020**: `test_convert_endpoint_validates_compatibility` - POST with incompatible units returns 422

**Integration Tests (8 tests):**
- **TEST-021**: `test_api_requires_authentication` - Unauthenticated request returns 401
- **TEST-022**: `test_api_respects_tenant_scoping` - User cannot see other tenant's UOMs
- **TEST-023**: `test_rate_limiting_enforced` - 61st request in minute returns 429
- **TEST-024**: `test_events_dispatched_on_crud_operations` - Verify events fired
- **TEST-025**: `test_has_uom_trait_works_in_inventory_item` - Test trait integration
- **TEST-026**: `test_activity_log_records_api_changes` - Verify audit logging
- **TEST-027**: `test_api_responses_cached_correctly` - Test response caching
- **TEST-028**: `test_conversion_performance_under_5ms` - Verify PR-UOM-002

**API Documentation Tests (3 tests):**
- **TEST-029**: `test_all_endpoints_documented_in_openapi` - Verify OpenAPI spec complete
- **TEST-030**: `test_response_examples_match_actual_responses` - Verify doc accuracy
- **TEST-031**: `test_error_responses_documented` - Verify 4xx/5xx responses documented

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: Breaking API changes in future versions affect existing integrations - **Mitigation**: Use semantic versioning (`/api/v1/`, `/api/v2/`), maintain v1 for 12 months after v2 release, provide migration guide
- **RISK-002**: Rate limiting too strict blocks legitimate high-volume operations - **Mitigation**: Implement tiered rate limits (60/min for standard, 600/min for batch operations), allow configuration per tenant
- **RISK-003**: Payload size grows large with nested relationships - **Mitigation**: Keep responses flat, use `?include=` query parameter for optional relationships, implement field filtering (`?fields=code,name`)
- **RISK-004**: Conversion endpoint becomes bottleneck under load - **Mitigation**: Aggressive caching of conversion factors, implement Redis-based rate limiting, consider read replicas for high traffic
- **RISK-005**: Broadcast events flood queue with high UOM change frequency - **Mitigation**: Implement event throttling (max 1 event per UOM per second), use ShouldQueue for async processing

**Assumptions:**
- **ASSUMPTION-001**: 95% of API requests will be GET (reads), 5% writes (creates/updates/deletes)
- **ASSUMPTION-002**: Most conversions will be between system UOMs (cached), not custom tenant UOMs
- **ASSUMPTION-003**: Average tenant has < 50 custom UOMs (keeps queries fast without additional optimization)
- **ASSUMPTION-004**: API clients can handle eventual consistency (events processed asynchronously)
- **ASSUMPTION-005**: Redis is available for rate limiting and caching (fallback to database cache if not)

## 8. KIV for future implementations

- **KIV-001**: Implement bulk UOM import/export endpoints (POST /api/v1/uoms/bulk-import, GET /api/v1/uoms/export)
- **KIV-002**: Add UOM versioning to track conversion factor changes over time (historical accuracy for old transactions)
- **KIV-003**: Implement UOM templates (common industry sets like "Manufacturing Standard", "Retail Standard")
- **KIV-004**: Add GraphQL endpoint as alternative to REST (if demand from mobile/frontend teams)
- **KIV-005**: Implement webhook support for UOM changes (allow external systems to subscribe)
- **KIV-006**: Add field-level permissions (some users can view but not edit conversion factors)
- **KIV-007**: Implement UOM approval workflow (custom UOMs require admin approval before activation)

## 9. Related PRD / Further Reading

- **Master PRD:** [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD:** [../prd/prd-01/PRD01-SUB06-UOM.md](../prd/prd-01/PRD01-SUB06-UOM.md)
- **Related Plans:**
  - PRD01-SUB06-PLAN01 (UOM Foundation) - Model and repository
  - PRD01-SUB06-PLAN02 (UOM Conversion Engine) - Business logic consumed by API
  - PRD01-SUB02 (Authentication) - Sanctum integration
- **External Documentation:**
  - Laravel API Resources: https://laravel.com/docs/11.x/eloquent-resources
  - Laravel Form Requests: https://laravel.com/docs/11.x/validation#form-request-validation
  - Laravel Rate Limiting: https://laravel.com/docs/11.x/routing#rate-limiting
  - Laravel Broadcasting: https://laravel.com/docs/11.x/broadcasting
  - REST API Best Practices: https://restfulapi.net/
  - JSON:API Specification: https://jsonapi.org/
