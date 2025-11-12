---
plan: Implement Chart of Accounts REST API Endpoints
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, api, accounting, chart-of-accounts, rest-api, validation]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan delivers the RESTful API layer for Chart of Accounts management, exposing account operations via versioned endpoints (`/api/v1/accounts`). It implements API controllers using Laravel route attributes, Form Requests for validation, API Resources for response transformation, and specialized endpoints for tree operations, search, and bulk operations. The API provides secure, well-documented interfaces for frontend applications and external integrations to manage hierarchical account structures with authorization, rate limiting, and comprehensive error handling.

## 1. Requirements & Constraints

**Functional Requirements:**
- **FR-COA-001**: Manage **hierarchical account structure** via REST API
- **FR-COA-006**: Expose all account operations through **RESTful API endpoints**

**Performance Requirements:**
- **PR-COA-001**: Account tree retrieval MUST complete in **< 100ms** for 10,000 accounts

**Security Requirements:**
- **SR-COA-001**: Only **authenticated users** can access COA APIs
- **SR-COA-002**: Only **Accounting Manager** role can create/update/delete accounts
- **SR-COA-003**: Prevent **accidental account deletion** if transactions exist

**Constraints:**
- **CON-001**: All API endpoints must be versioned (`/api/v1/accounts`)
- **CON-002**: All responses must use API Resource transformers (no raw model returns)
- **CON-003**: Cannot modify system accounts (is_system=true)
- **CON-004**: Cannot delete accounts with children or transactions
- **CON-005**: All tree operations must maintain nested set integrity

**Guidelines:**
- **GUD-001**: Use Form Request classes for validation
- **GUD-002**: Use API Resource classes for response transformation
- **GUD-003**: Use Laravel Actions in controllers (thin controller pattern)
- **GUD-004**: Apply rate limiting (60 requests/minute per user)
- **GUD-005**: Provide OpenAPI/Swagger documentation

**Patterns:**
- **PAT-001**: RESTful resource routing with standard HTTP verbs
- **PAT-002**: Action-based controller methods (thin controllers)
- **PAT-003**: JSON:API compliant response formatting

## 2. Implementation Steps

### GOAL-001: Create Account API Controller with Authentication

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-COA-001, FR-COA-006, SR-COA-001, SR-COA-002, CON-001 | Implement RESTful API controller with authentication middleware, role-based authorization, and standard CRUD operations | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create `app/Http/Controllers/Api/V1/AccountController.php` with namespace. Add `declare(strict_types=1);`. Apply route attributes: `#[Prefix('api/v1')]`, `#[Middleware(['auth:sanctum', 'tenant'])]` to ensure authentication and tenant context | | |
| TASK-002 | Inject dependencies via constructor: `AccountRepositoryContract $repository`, `SearchAccountsAction $searchAction`, `GetAccountPathAction $pathAction`. Use readonly properties for immutability | | |
| TASK-003 | Implement `index(Request $request): JsonResponse` method. Add `#[Get('/accounts', name: 'accounts.index')]` attribute. Support query parameters: `?type={type}`, `?category={category}`, `?is_active={bool}`, `?is_header={bool}`, `?parent_id={int}`, `?search={term}`, `?per_page={int}`. Validate using inline rules. Return paginated results using `AccountResource::collection()` | | |
| TASK-004 | Implement `show(Account $account): JsonResponse` method. Add `#[Get('/accounts/{account}', name: 'accounts.show')]` attribute. Use route model binding. Return `AccountResource::make($account)->response()` with related data (parent, children count, transaction count) | | |
| TASK-005 | Implement `store(StoreAccountRequest $request): JsonResponse` method. Add `#[Post('/accounts', name: 'accounts.store')]`, `#[Middleware('can:create-accounts')]` attributes for role check. Use validated data to call `CreateAccountAction::run($request->validated())`. Return 201 Created with `AccountResource::make($account)->response()->setStatusCode(201)`. Include Location header with account URL | | |
| TASK-006 | Implement `update(UpdateAccountRequest $request, Account $account): JsonResponse` method. Add `#[Patch('/accounts/{account}', name: 'accounts.update')]`, `#[Middleware('can:update-accounts')]` attributes. Prevent updates to system accounts (is_system=true) - return 403 Forbidden. Use `UpdateAccountAction::run($account, $request->validated())`. Return `AccountResource::make($account)->response()` | | |
| TASK-007 | Implement `destroy(Account $account): JsonResponse` method. Add `#[Delete('/accounts/{account}', name: 'accounts.destroy')]`, `#[Middleware('can:delete-accounts')]` attributes. Check if account is system (403) or has children/transactions (409 Conflict). Use `DeleteAccountAction::run($account)`. Return 204 No Content on success | | |
| TASK-008 | Add rate limiting: Apply `#[Middleware('throttle:api')]` to all methods. Configure in `app/Providers/RouteServiceProvider.php`: `RateLimiter::for('api', fn(Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()))` | | |

### GOAL-002: Implement Specialized Tree and Search Endpoints

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-COA-001, PR-COA-001 | Create specialized endpoints for hierarchical tree operations, account search, and path retrieval with optimized performance | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-009 | Implement `tree(Request $request): JsonResponse` method. Add `#[Get('/accounts/tree', name: 'accounts.tree')]` attribute. Support query parameters: `?type={type}`, `?include_inactive={bool}`, `?max_depth={int}`. Call repository's `getTree()` method which returns nested structure. Return `AccountTreeResource::collection($tree)` which formats hierarchical JSON with children arrays | | |
| TASK-010 | In tree() method, implement response caching: use `Cache::remember("accounts:tree:{$tenantId}:{$filters}", 3600, fn() => ...)` for 1-hour cache. Invalidate cache on account creation/update/deletion in respective Actions using `Cache::forget()`. This ensures PR-COA-001 compliance (< 100ms response) | | |
| TASK-011 | Implement `search(Request $request): JsonResponse` method. Add `#[Get('/accounts/search', name: 'accounts.search')]` attribute. Validate query parameter: `q` (required, string, min:2). Call `SearchAccountsAction::run($request->input('q'), $request->only(['type', 'category', 'is_active']))`. Return `AccountSearchResource::collection($results)` with relevance scores | | |
| TASK-012 | Implement `children(Account $account): JsonResponse` method. Add `#[Get('/accounts/{account}/children', name: 'accounts.children')]` attribute. Call repository's `getChildren($account->id)`. Return `AccountResource::collection($children)`. Include parent account info in meta | | |
| TASK-013 | Implement `ancestors(Account $account): JsonResponse` method. Add `#[Get('/accounts/{account}/ancestors', name: 'accounts.ancestors')]` attribute. Call repository's `getAncestors($account->id)`. Return ordered array from root to parent: `AccountResource::collection($ancestors)`. This provides breadcrumb data for UI | | |
| TASK-014 | Implement `path(Account $account): JsonResponse` method. Add `#[Get('/accounts/{account}/path', name: 'accounts.path')]` attribute. Call `GetAccountPathAction::run($account)` which returns string like "Assets > Current Assets > Cash". Return JSON: `['path' => $path, 'accounts' => AccountResource::collection($account->ancestors()->get()->push($account))]` | | |

### GOAL-003: Create Form Requests for Validation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-COA-001, CON-003, CON-004 | Implement validation rules for account creation and updates with business rule enforcement, uniqueness checks, and authorization | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-015 | Create `app/Http/Requests/Api/V1/StoreAccountRequest.php` extending FormRequest. Add `declare(strict_types=1);`. Implement `authorize(): bool` returning `$this->user()->can('create-accounts')` (checks role permission) | | |
| TASK-016 | In StoreAccountRequest, implement `rules(): array` returning validation rules: `['code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('accounts')->where('tenant_id', auth()->user()->tenant_id)], 'name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:1000'], 'account_type' => ['required', 'string', Rule::in(AccountType::values())], 'account_category' => ['nullable', 'string', Rule::in(AccountCategory::values())], 'parent_id' => ['nullable', 'integer', 'exists:accounts,id'], 'is_header' => ['nullable', 'boolean'], 'reporting_group' => ['nullable', 'string', Rule::in(['balance_sheet', 'income_statement', 'cash_flow'])], 'tax_category' => ['nullable', 'string', 'max:50'], 'metadata' => ['nullable', 'array']]` | | |
| TASK-017 | In StoreAccountRequest, add `messages(): array` with custom validation messages: code.unique → "Account code must be unique within your organization", parent_id.exists → "Parent account does not exist", account_type.in → "Invalid account type. Must be: asset, liability, equity, revenue, or expense" | | |
| TASK-018 | In StoreAccountRequest, implement `prepareForValidation(): void` to set defaults: `$this->merge(['tenant_id' => auth()->user()->tenant_id, 'is_active' => $this->is_active ?? true, 'is_header' => $this->is_header ?? false])` | | |
| TASK-019 | In StoreAccountRequest, add `withValidator(Validator $validator): void` to add after-validation business rules: if parent_id provided, validate parent account type matches requested account_type (CON-004). Fetch parent account and check: `if ($parent->account_type->value !== $this->account_type) { $validator->errors()->add('parent_id', 'Parent account type must match child account type'); }` | | |
| TASK-020 | Create `app/Http/Requests/Api/V1/UpdateAccountRequest.php` extending FormRequest. Copy authorize() checking 'update-accounts' permission. Copy rules() but make code validation use `Rule::unique()->ignore($this->route('account')->id)` for existing record. Exclude account_type from rules (immutable per CON-003) | | |
| TASK-021 | In UpdateAccountRequest, add withValidator() to prevent system account updates: check `$this->route('account')->is_system` and add error if true: `$validator->errors()->add('account', 'Cannot modify system accounts')` | | |

### GOAL-004: Create API Resources for Response Transformation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| CON-002, FR-COA-006 | Implement JSON:API compliant resource transformers with computed fields, relationships, and consistent formatting for flat lists, trees, and search results | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-022 | Create `app/Http/Resources/Api/V1/AccountResource.php` extending JsonResource. Add `declare(strict_types=1);`. Import `use Illuminate\Http\Request;` | | |
| TASK-023 | Implement `toArray(Request $request): array` method returning: `['id' => $this->id, 'code' => $this->code, 'name' => $this->name, 'description' => $this->description, 'account_type' => $this->account_type->value, 'account_type_label' => $this->account_type->label(), 'account_category' => $this->account_category?->value, 'account_category_label' => $this->account_category?->label(), 'parent_id' => $this->parent_id, 'level' => $this->level, 'is_active' => $this->is_active, 'is_header' => $this->is_header, 'normal_balance' => $this->normalBalance(), 'reporting_group' => $this->reporting_group, 'tax_category' => $this->tax_category, 'metadata' => $this->metadata, 'full_path' => $this->full_path, 'created_at' => $this->created_at?->toIso8601String(), 'updated_at' => $this->updated_at?->toIso8601String()]` | | |
| TASK-024 | Add conditional relationships to AccountResource: `'parent' => $this->whenLoaded('parent', fn() => new AccountResource($this->parent))`, `'children' => $this->whenLoaded('children', fn() => AccountResource::collection($this->children))`, `'children_count' => $this->when(isset($this->children_count), $this->children_count)`, `'transaction_count' => $this->when(isset($this->transaction_count), $this->transaction_count)`. This allows API clients to request ?include=parent,children | | |
| TASK-025 | Add `with(Request $request): array` method for meta information: `return ['meta' => ['version' => 'v1', 'timestamp' => now()->toIso8601String()]];` | | |
| TASK-026 | Create `app/Http/Resources/Api/V1/AccountTreeResource.php` extending JsonResource for hierarchical tree responses. Override `toArray()` to include: `['id' => $this->id, 'code' => $this->code, 'name' => $this->name, 'account_type' => $this->account_type->value, 'is_header' => $this->is_header, 'level' => $this->level, 'children' => AccountTreeResource::collection($this->whenLoaded('children'))]`. Recursive structure for frontend tree components | | |
| TASK-027 | Create `app/Http/Resources/Api/V1/AccountSearchResource.php` extending AccountResource. Add additional fields: `'similarity_score' => $this->when(isset($this->similarity_score), $this->similarity_score)`, `'match_field' => $this->when(isset($this->match_field), $this->match_field)` for search relevance display | | |
| TASK-028 | Create `app/Http/Resources/Api/V1/AccountCollection.php` extending ResourceCollection. Override `toArray()` to add pagination meta and links: `return ['data' => $this->collection, 'links' => ['self' => $request->url(), 'first' => $this->url(1), 'last' => $this->url($this->lastPage()), 'prev' => $this->previousPageUrl(), 'next' => $this->nextPageUrl()], 'meta' => ['total' => $this->total(), 'per_page' => $this->perPage(), 'current_page' => $this->currentPage(), 'last_page' => $this->lastPage(), 'from' => $this->firstItem(), 'to' => $this->lastItem()]];` | | |

### GOAL-005: Implement Bulk Operations and Template Endpoints

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-COA-005, FR-COA-006 | Create endpoints for bulk account operations, COA template listing and application for efficient multi-account management | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-029 | Implement `bulkUpdate(BulkUpdateAccountsRequest $request): JsonResponse` method. Add `#[Patch('/accounts/bulk', name: 'accounts.bulk-update')]`, `#[Middleware('can:update-accounts')]` attributes. Accept array of account updates: `[{'id': 1, 'is_active': false}, {'id': 2, 'name': 'Updated'}]`. Use DB transaction for atomicity. Call UpdateAccountAction for each. Return success count and errors array | | |
| TASK-030 | Implement `bulkDelete(BulkDeleteAccountsRequest $request): JsonResponse` method. Add `#[Delete('/accounts/bulk', name: 'accounts.bulk-delete')]`, `#[Middleware('can:delete-accounts')]` attributes. Accept array of account IDs. Validate each can be deleted (no children, no transactions). Use DB transaction. Call DeleteAccountAction for each. Return success/failure counts | | |
| TASK-031 | Implement `activate(Account $account): JsonResponse` method. Add `#[Post('/accounts/{account}/activate', name: 'accounts.activate')]`, `#[Middleware('can:update-accounts')]` attribute. Call `ActivateAccountAction::run($account)`. Return `AccountResource::make($account->fresh())` | | |
| TASK-032 | Implement `deactivate(Account $account): JsonResponse` method. Add `#[Post('/accounts/{account}/deactivate', name: 'accounts.deactivate')]`, `#[Middleware('can:update-accounts')]` attribute. Validate no active children. Call `DeactivateAccountAction::run($account)`. Return `AccountResource::make($account->fresh())` | | |
| TASK-033 | Implement `templates(): JsonResponse` method. Add `#[Get('/accounts/templates', name: 'accounts.templates')]` attribute. Resolve all COA templates from container using `app()->tagged('coa-templates')`. Return array: `[['name' => 'manufacturing', 'label' => 'Manufacturing', 'description' => '...', 'account_count' => 60], ...]`. No authentication required (public endpoint) | | |
| TASK-034 | Implement `applyTemplate(ApplyTemplateRequest $request): JsonResponse` method. Add `#[Post('/accounts/templates/apply', name: 'accounts.templates.apply')]`, `#[Middleware('can:create-accounts')]` attribute. Validate template name. Use queued job `ApplyCoaTemplateJob` to apply template asynchronously. Return 202 Accepted with job ID for status polling: `['job_id' => $jobId, 'status' => 'processing', 'message' => 'Template application started']` | | |
| TASK-035 | Create `app/Jobs/ApplyCoaTemplateJob.php` implementing ShouldQueue. In handle() method, resolve template, call `CloneCoaForTenantAction::run()`, dispatch `COATemplateAppliedEvent` on success. Implement failure handling with retry (3 attempts) | | |

## 3. Alternatives

- **ALT-001**: Use GraphQL instead of REST API - **Rejected** because REST is simpler for CRUD, better Laravel support, easier caching. GraphQL adds complexity without clear benefit for hierarchical data (REST can handle with tree endpoint)
- **ALT-002**: Return full tree in every response - **Rejected** because it creates massive payloads for large COAs (10,000 accounts = megabytes). Dedicated tree endpoint with caching is better
- **ALT-003**: Include transaction count in every account response - **Rejected** because it requires expensive JOIN on every query. Make it opt-in via ?include=transaction_count query parameter
- **ALT-004**: Use POST for all operations (no PUT/PATCH/DELETE) - **Rejected** because it violates RESTful principles, reduces clarity, breaks HTTP semantic conventions
- **ALT-005**: Synchronous template application - **Rejected** because applying 100+ account template takes 5-10 seconds, blocking HTTP request. Async with job queue provides better UX

## 4. Dependencies

**Package Dependencies:**
- **DEP-001**: `laravel/sanctum` ^4.0 (API authentication)
- **DEP-002**: `lorisleiva/laravel-actions` ^2.0 (Action pattern)

**Internal Dependencies:**
- **DEP-003**: PRD01-SUB07-PLAN01 (COA Foundation) - Account model, repository, enums
- **DEP-004**: PRD01-SUB07-PLAN02 (COA Actions & Templates) - All Actions consumed by API
- **DEP-005**: PRD01-SUB02 (Authentication System) - Sanctum token authentication
- **DEP-006**: PRD01-SUB01 (Multi-Tenancy) - Tenant middleware and scoping

**Infrastructure:**
- **DEP-007**: Redis (recommended for tree caching and rate limiting)
- **DEP-008**: Queue system (Redis, SQS, or database) for async template application

## 5. Files

**Controllers:**
- `app/Http/Controllers/Api/V1/AccountController.php` - Main RESTful API controller with 15+ endpoints

**Form Requests:**
- `app/Http/Requests/Api/V1/StoreAccountRequest.php` - Validation for account creation
- `app/Http/Requests/Api/V1/UpdateAccountRequest.php` - Validation for account updates
- `app/Http/Requests/Api/V1/BulkUpdateAccountsRequest.php` - Validation for bulk updates
- `app/Http/Requests/Api/V1/BulkDeleteAccountsRequest.php` - Validation for bulk deletes
- `app/Http/Requests/Api/V1/ApplyTemplateRequest.php` - Validation for template application

**API Resources:**
- `app/Http/Resources/Api/V1/AccountResource.php` - Single account response transformer
- `app/Http/Resources/Api/V1/AccountTreeResource.php` - Hierarchical tree response transformer
- `app/Http/Resources/Api/V1/AccountSearchResource.php` - Search result transformer with relevance
- `app/Http/Resources/Api/V1/AccountCollection.php` - Paginated account list transformer

**Jobs:**
- `app/Jobs/ApplyCoaTemplateJob.php` - Async template application job

**Events (dispatched from Actions, consumed by API):**
- Events already defined in PLAN02, API will dispatch them via Actions

**Configuration (updated):**
- `config/sanctum.php` - API token configuration
- `app/Providers/RouteServiceProvider.php` - Rate limiting configuration

**Documentation:**
- `docs/api/openapi.yaml` - OpenAPI 3.0 specification for all COA endpoints

## 6. Testing

**Unit Tests (10 tests):**
- **TEST-001**: `test_account_resource_transforms_model_correctly` - Verify all fields present and correctly formatted
- **TEST-002**: `test_account_tree_resource_formats_hierarchy_correctly` - Verify children array structure
- **TEST-003**: `test_account_collection_includes_pagination_meta` - Verify meta and links fields
- **TEST-004**: `test_store_request_validates_required_fields` - Test validation rules
- **TEST-005**: `test_store_request_enforces_code_uniqueness` - Test uniqueness validation
- **TEST-006**: `test_update_request_prevents_type_change` - Test immutable field validation
- **TEST-007**: `test_update_request_prevents_system_account_modification` - Test system account check
- **TEST-008**: `test_bulk_update_request_validates_array_structure` - Test bulk validation
- **TEST-009**: `test_apply_template_request_validates_template_name` - Test template validation
- **TEST-010**: `test_authorization_checks_work_correctly` - Test can() permissions

**Feature Tests (20 tests):**
- **TEST-011**: `test_index_returns_paginated_accounts` - GET /api/v1/accounts
- **TEST-012**: `test_index_filters_by_type_and_category` - GET with query params
- **TEST-013**: `test_show_returns_single_account` - GET /api/v1/accounts/{id}
- **TEST-014**: `test_show_returns_404_for_invalid_id` - GET with non-existent ID
- **TEST-015**: `test_store_creates_new_account` - POST /api/v1/accounts
- **TEST-016**: `test_store_returns_validation_errors` - POST with invalid data
- **TEST-017**: `test_store_requires_accounting_manager_role` - POST returns 403 for non-manager
- **TEST-018**: `test_update_modifies_existing_account` - PATCH /api/v1/accounts/{id}
- **TEST-019**: `test_update_prevents_system_account_modification` - PATCH system account returns 403
- **TEST-020**: `test_destroy_soft_deletes_account` - DELETE /api/v1/accounts/{id}
- **TEST-021**: `test_destroy_prevents_deletion_if_has_children` - DELETE returns 409
- **TEST-022**: `test_tree_endpoint_returns_hierarchical_structure` - GET /api/v1/accounts/tree
- **TEST-023**: `test_tree_endpoint_uses_caching` - Verify cache hit on second request
- **TEST-024**: `test_search_endpoint_finds_accounts_by_keyword` - GET /api/v1/accounts/search?q=cash
- **TEST-025**: `test_children_endpoint_returns_direct_descendants` - GET /api/v1/accounts/{id}/children
- **TEST-026**: `test_ancestors_endpoint_returns_parent_chain` - GET /api/v1/accounts/{id}/ancestors
- **TEST-027**: `test_path_endpoint_returns_full_hierarchy_path` - GET /api/v1/accounts/{id}/path
- **TEST-028**: `test_bulk_update_updates_multiple_accounts` - PATCH /api/v1/accounts/bulk
- **TEST-029**: `test_bulk_delete_deletes_multiple_accounts` - DELETE /api/v1/accounts/bulk
- **TEST-030**: `test_activate_deactivate_endpoints_toggle_status` - POST activate/deactivate

**Integration Tests (10 tests):**
- **TEST-031**: `test_api_requires_authentication` - Unauthenticated request returns 401
- **TEST-032**: `test_api_respects_tenant_scoping` - User cannot see other tenant's accounts
- **TEST-033**: `test_rate_limiting_enforced` - 61st request in minute returns 429
- **TEST-034**: `test_templates_endpoint_lists_all_templates` - GET /api/v1/accounts/templates
- **TEST-035**: `test_apply_template_queues_job` - POST /api/v1/accounts/templates/apply dispatches job
- **TEST-036**: `test_apply_template_job_creates_accounts` - Job execution creates accounts
- **TEST-037**: `test_api_responses_cached_correctly` - Verify cache keys and invalidation
- **TEST-038**: `test_actions_dispatched_from_api_endpoints` - Verify Action calls
- **TEST-039**: `test_activity_log_records_api_changes` - Verify audit logging
- **TEST-040**: `test_cors_headers_present_for_spa` - Verify CORS configuration

**API Documentation Tests (5 tests):**
- **TEST-041**: `test_all_endpoints_documented_in_openapi` - Verify OpenAPI spec complete
- **TEST-042**: `test_response_examples_match_actual_responses` - Verify doc accuracy
- **TEST-043**: `test_error_responses_documented` - Verify 4xx/5xx responses documented
- **TEST-044**: `test_authentication_requirements_documented` - Verify auth docs
- **TEST-045**: `test_rate_limiting_documented` - Verify rate limit docs

**Performance Tests (3 tests):**
- **TEST-046**: `test_tree_endpoint_completes_under_100ms` - Verify PR-COA-001
- **TEST-047**: `test_cached_tree_response_under_10ms` - Verify cache effectiveness
- **TEST-048**: `test_search_endpoint_completes_under_100ms` - Verify search performance

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: Breaking API changes in future versions affect existing integrations - **Mitigation**: Use semantic versioning (`/api/v1/`, `/api/v2/`), maintain v1 for 12 months after v2 release, provide migration guide and deprecation warnings
- **RISK-002**: Tree endpoint with 10,000 accounts causes memory issues - **Mitigation**: Implement max_depth parameter, paginate tree levels, use streaming responses for very large trees, aggressive Redis caching
- **RISK-003**: Bulk operations timeout with large datasets - **Mitigation**: Limit bulk operations to 100 accounts per request, use async jobs for larger batches, implement chunking
- **RISK-004**: Cache invalidation misses cause stale data - **Mitigation**: Use cache tags (Redis) for grouped invalidation, implement cache versioning, monitor cache hit rates
- **RISK-005**: Template application job fails mid-process leaving partial COA - **Mitigation**: Wrap in database transaction, implement idempotent job logic, provide rollback mechanism, retry with exponential backoff

**Assumptions:**
- **ASSUMPTION-001**: 90% of API requests will be GET (reads), 10% writes (creates/updates/deletes)
- **ASSUMPTION-002**: Most clients will fetch tree once per session and cache locally
- **ASSUMPTION-003**: Average tenant has < 500 accounts (tree response < 100KB JSON)
- **ASSUMPTION-004**: Bulk operations typically affect < 50 accounts (not thousands)
- **ASSUMPTION-005**: Template application happens once per tenant (during onboarding), not frequently

## 8. KIV for future implementations

- **KIV-001**: Implement WebSocket real-time updates for account changes (push to connected clients)
- **KIV-002**: Add account export to Excel/CSV endpoint with customizable columns
- **KIV-003**: Implement account import from Excel with validation preview and dry-run
- **KIV-004**: Add GraphQL endpoint as alternative to REST (if frontend team requests)
- **KIV-005**: Implement field-level permissions (e.g., can view accounts but not tax_category field)
- **KIV-006**: Add webhook support for account changes (external system notifications)
- **KIV-007**: Implement API versioning negotiation via Accept header (Accept: application/vnd.erp.v2+json)
- **KIV-008**: Add HATEOAS links in responses for true REST compliance
- **KIV-009**: Implement batch endpoint for multiple operations in single request (GraphQL-style batching)

## 9. Related PRD / Further Reading

- **Master PRD:** [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD:** [../prd/prd-01/PRD01-SUB07-CHART-OF-ACCOUNTS.md](../prd/prd-01/PRD01-SUB07-CHART-OF-ACCOUNTS.md)
- **Related Plans:**
  - PRD01-SUB07-PLAN01 (COA Foundation) - Database schema and models consumed by API
  - PRD01-SUB07-PLAN02 (COA Actions & Templates) - Business logic consumed by API
  - PRD01-SUB02 (Authentication) - Sanctum integration
- **External Documentation:**
  - Laravel API Resources: https://laravel.com/docs/11.x/eloquent-resources
  - Laravel Form Requests: https://laravel.com/docs/11.x/validation#form-request-validation
  - Laravel Rate Limiting: https://laravel.com/docs/11.x/routing#rate-limiting
  - Laravel Queue Jobs: https://laravel.com/docs/11.x/queues
  - REST API Best Practices: https://restfulapi.net/
  - JSON:API Specification: https://jsonapi.org/
  - OpenAPI 3.0 Specification: https://swagger.io/specification/
