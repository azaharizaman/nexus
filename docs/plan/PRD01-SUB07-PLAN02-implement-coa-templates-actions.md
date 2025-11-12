---
plan: Implement COA Template System and Account Actions
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, business-logic, accounting, chart-of-accounts, templates, actions]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan builds the business logic layer for Chart of Accounts management, including Laravel Actions for CRUD operations, COA template system for quick setup, and tenant onboarding integration. It implements CreateAccountAction, UpdateAccountAction, DeleteAccountAction with comprehensive validation, event dispatching, and audit logging. The template system provides industry-specific COA structures (Manufacturing, Retail, Professional Services) that new tenants can clone during signup. This plan completes the COA management system by providing the operational interface between the database foundation and API/UI layers.

## 1. Requirements & Constraints

**Functional Requirements:**
- **FR-COA-001**: Manage **hierarchical account structure** with parent-child relationships
- **FR-COA-005**: Provide **COA templates** for different industries
- **FR-COA-006**: Enable **COA cloning** for new tenant setup

**Business Rules:**
- **BR-COA-001**: Each account MUST have an **account type** determining normal balance
- **BR-COA-002**: Account codes MUST be **unique within tenant**
- **BR-COA-003**: Only **leaf accounts** (non-header) can have transactions posted

**Data Requirements:**
- **DR-COA-002**: Support **unlimited depth** hierarchies

**Security Requirements:**
- **SR-COA-001**: Prevent **accidental account deletion** if transactions exist

**Events:**
- **EV-COA-001**: Dispatch `AccountCreated` event when new account is added
- **EV-COA-002**: Dispatch `AccountUpdated` event when account is modified
- **EV-COA-003**: Dispatch `AccountDeleted` event when account is removed
- **EV-COA-006**: Dispatch `COAImportedEvent` after template import

**Constraints:**
- **CON-003**: Account type cannot be changed after creation (immutable)
- **CON-004**: Parent account type must match child account type
- **CON-005**: Cannot delete account if has children or transactions

**Guidelines:**
- **GUD-001**: Use Laravel Actions for all account operations (AsAction trait)
- **GUD-002**: Log all account changes using Spatie Activity Log
- **GUD-003**: Validate business rules before database operations
- **GUD-004**: Dispatch events after successful operations for cross-module integration

**Patterns:**
- **PAT-001**: Action pattern for CreateAccountAction, UpdateAccountAction, DeleteAccountAction
- **PAT-002**: Template pattern for COA templates (interface + implementations)
- **PAT-003**: Strategy pattern for different validation rules per action

## 2. Implementation Steps

### GOAL-001: Create Account Management Actions

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-COA-001, BR-COA-001, BR-COA-002, BR-COA-003, EV-COA-001, EV-COA-002, EV-COA-003 | Implement Laravel Actions for account CRUD operations with comprehensive validation, audit logging, and event dispatching | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create `app/Domains/Accounting/Actions/CreateAccountAction.php` using AsAction trait. Add `declare(strict_types=1);`. Inject `AccountRepositoryContract` via constructor | | |
| TASK-002 | In CreateAccountAction, implement `handle(array $data): Account` method. Validate inputs: code format (alphanumeric + dash), name required, account_type in enum values, parent_id exists if provided. Use Laravel validator: `$validated = Validator::make($data, $this->rules())->validate();` | | |
| TASK-003 | In handle(), validate parent-child type compatibility (CON-004): if parent_id provided, fetch parent account and assert `$parent->account_type === $data['account_type']`. Throw `IncompatibleAccountTypeException` if mismatch with message "Child account type must match parent account type" | | |
| TASK-004 | In handle(), validate code uniqueness within tenant (BR-COA-002): check if Account with same code exists for tenant using repository. Throw `DuplicateAccountCodeException` if found | | |
| TASK-005 | In handle(), set defaults: `tenant_id` from auth()->user()->tenant_id, `is_active => true`, `level` calculated by NodeTrait based on parent. Call `$account = $this->repository->create($validated);`. NodeTrait automatically maintains lft/rgt | | |
| TASK-006 | After creation, dispatch `AccountCreated` event with account model. Log activity using `activity()->performedOn($account)->causedBy(auth()->user())->log('Account created');`. Return created account | | |
| TASK-007 | In CreateAccountAction, implement `rules(): array` method returning validation rules: `['code' => ['required', 'string', 'max:50', 'alpha_dash'], 'name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'account_type' => ['required', 'string', Rule::in(AccountType::values())], 'account_category' => ['nullable', 'string', Rule::in(AccountCategory::values())], 'parent_id' => ['nullable', 'integer', 'exists:accounts,id'], 'is_header' => ['nullable', 'boolean'], 'reporting_group' => ['nullable', 'string', Rule::in(['balance_sheet', 'income_statement', 'cash_flow'])], 'tax_category' => ['nullable', 'string', 'max:50'], 'metadata' => ['nullable', 'array']]` | | |
| TASK-008 | Implement `asCommand()` method to expose as Artisan command: `php artisan account:create {code} {name} {type}`. Accept options: `--parent-id=`, `--category=`, `--header`. Output success message with created account ID | | |

### GOAL-002: Implement Update and Delete Account Actions

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-COA-001, SR-COA-001, EV-COA-002, EV-COA-003, CON-003, CON-005 | Create actions for account updates (with immutability checks) and safe deletion (with transaction validation) | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-009 | Create `app/Domains/Accounting/Actions/UpdateAccountAction.php` using AsAction trait. Add `declare(strict_types=1);`. Inject AccountRepositoryContract | | |
| TASK-010 | In UpdateAccountAction, implement `handle(Account $account, array $data): Account` method. Validate rules same as CreateAccountAction but exclude code (immutable) and account_type (immutable per CON-003). If either provided in $data, throw `ImmutableFieldException` | | |
| TASK-011 | In handle(), if parent_id being changed, validate new parent compatibility (CON-004). Fetch new parent, assert type matches. Also validate not creating circular reference: new parent cannot be descendant of current account. Use `$account->descendants()->pluck('id')->contains($data['parent_id'])` check | | |
| TASK-012 | Call `$updated = $this->repository->update($account, $data);`. NodeTrait handles lft/rgt adjustments if parent changes. Dispatch `AccountUpdated` event with model and changes: `event(new AccountUpdated($updated, $account->getChanges()));`. Log activity with changes. Return updated account | | |
| TASK-013 | Create `app/Domains/Accounting/Actions/DeleteAccountAction.php` using AsAction trait. Inject AccountRepositoryContract | | |
| TASK-014 | In DeleteAccountAction, implement `handle(Account $account, bool $force = false): bool` method. First validate deletion allowed: check `$account->children()->exists()` - if true, throw `AccountHasChildrenException` with message "Cannot delete account with child accounts. Delete children first or use cascade." | | |
| TASK-015 | In handle(), check if account has transactions using `$this->repository->isInUse($account)`. If true and $force=false, throw `AccountInUseException` with message "Cannot delete account with existing transactions. Archive instead or force delete to cascade." | | |
| TASK-016 | If validations pass, perform soft delete: `$result = $account->delete();`. NodeTrait maintains lft/rgt integrity after deletion. Dispatch `AccountDeleted` event with account code and type. Log activity. Return true if successful | | |

### GOAL-003: Create COA Template System with Industry Templates

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-COA-005, EV-COA-006 | Implement template interface and concrete templates for Manufacturing, Retail, and Professional Services industries with clone functionality | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-017 | Create `app/Domains/Accounting/Contracts/CoaTemplateContract.php` interface. Add `declare(strict_types=1);`. Define methods: `getName(): string`, `getDescription(): string`, `getIndustry(): string`, `getAccounts(): array` (returns structured array of account definitions), `apply(string $tenantId): Collection` (creates accounts for tenant and returns created accounts) | | |
| TASK-018 | Create abstract base class `app/Domains/Accounting/Templates/AbstractCoaTemplate.php` implementing CoaTemplateContract. Implement `apply()` method with logic: iterate through getAccounts(), create each account using CreateAccountAction, maintain parent-child relationships using associative array for ID mapping (template IDs → actual IDs), handle nested structures recursively | | |
| TASK-019 | In AbstractCoaTemplate::apply(), wrap account creation in DB transaction for atomicity. If any account fails, rollback all. After successful creation, dispatch `COAImportedEvent` with count of accounts created and template name. Return collection of created accounts | | |
| TASK-020 | Create `app/Domains/Accounting/Templates/ManufacturingCoaTemplate.php` extending AbstractCoaTemplate. Implement getAccounts() returning array structure: Assets (1000-1999) including Raw Materials Inventory (1135), Work-in-Progress Inventory (1136), Finished Goods Inventory (1137), Manufacturing Equipment (1215). Expenses including Direct Labor (5130), Manufacturing Overhead (5140), Factory Utilities (5141). Total ~60 accounts covering manufacturing operations | | |
| TASK-021 | Create `app/Domains/Accounting/Templates/RetailCoaTemplate.php` extending AbstractCoaTemplate. Implement getAccounts() with retail-specific accounts: Merchandise Inventory (1131), Store Fixtures (1213), Sales Revenue (4110), Sales Returns (4115), Cost of Goods Sold (5110), Store Rent (5221), POS System Expenses (5242). Total ~50 accounts for retail business | | |
| TASK-022 | Create `app/Domains/Accounting/Templates/ProfessionalServicesCoaTemplate.php` extending AbstractCoaTemplate. Implement getAccounts() with service business focus: Accounts Receivable (1120), Service Revenue (4120), Consulting Fees (4121), Professional Fees Expense (5250), Contract Labor (5251). No inventory accounts. Total ~45 accounts for consulting, legal, accounting firms | | |
| TASK-023 | Register templates in service provider: `app/Providers/AccountingServiceProvider.php`. In register() method, bind templates to container: `$this->app->tag([ManufacturingCoaTemplate::class, RetailCoaTemplate::class, ProfessionalServicesCoaTemplate::class], 'coa-templates');`. This allows resolving all templates via `app()->tagged('coa-templates')` | | |

### GOAL-004: Implement COA Clone and Tenant Onboarding Integration

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-COA-006 | Create action to clone system COA (or template) to new tenant during signup with customization support | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-024 | Create `app/Domains/Accounting/Actions/CloneCoaForTenantAction.php` using AsAction trait. Add `declare(strict_types=1);`. Inject AccountRepositoryContract | | |
| TASK-025 | In CloneCoaForTenantAction, implement `handle(string $tenantId, ?string $templateName = 'standard'): Collection` method. If $templateName provided, resolve template from container using tagged services. If 'standard', use StandardCoaSeeder structure (system accounts with tenant_id=NULL). Return collection of created accounts | | |
| TASK-026 | In handle(), for template-based clone: call `$template->apply($tenantId)` which uses CreateAccountAction internally. For system COA clone: fetch all system accounts (tenant_id=NULL) using repository, iterate and create copies with new tenant_id, maintain parent-child relationships using ID mapping array | | |
| TASK-027 | Wrap clone operation in DB transaction. Use DB::transaction() with retry logic (deadlock prevention). If transaction fails, log error and throw `CoaCloneException` with tenant ID for debugging. On success, log activity and dispatch `COATenantInitializedEvent` | | |
| TASK-028 | Add option to customize cloned accounts: accept `$customizations` array parameter with format `['1110' => ['name' => 'Business Checking Account', 'code' => '1111'], ...]`. After cloning, apply customizations using UpdateAccountAction. This allows tenants to rename default accounts during onboarding | | |
| TASK-029 | Integrate with tenant signup flow: in `TenantCreated` event listener, automatically call `CloneCoaForTenantAction::run($tenant->id, $tenant->industry)`. This ensures every new tenant has working COA immediately. Use queued listener for async processing (don't block signup) | | |

### GOAL-005: Create Account Search and Helper Actions

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-COA-001 | Implement utility actions for account search, validation, and common operations to support API and UI layers | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-030 | Create `app/Domains/Accounting/Actions/SearchAccountsAction.php` using AsAction trait. Inject AccountRepositoryContract | | |
| TASK-031 | In SearchAccountsAction, implement `handle(string $query, array $filters = []): Collection` method. Search across code, name, and description using LIKE or full-text search. Apply filters: `type` (AccountType), `category` (AccountCategory), `is_active` (boolean), `is_header` (boolean), `parent_id` (integer). Use repository methods with query builder chaining | | |
| TASK-032 | In SearchAccountsAction, implement fuzzy matching using Levenshtein distance for typo tolerance. If exact match not found, calculate similarity score for all accounts and return top 10 matches. Add `similarity_score` to results for ranking | | |
| TASK-033 | Create `app/Domains/Accounting/Actions/ValidateAccountCodeAction.php` using AsAction trait. Implement `handle(string $code, ?string $tenantId = null, ?int $excludeAccountId = null): array` method. Return validation result: `['valid' => bool, 'errors' => array, 'suggestions' => array]`. Check format, uniqueness, and conventions. Suggest alternative codes if invalid | | |
| TASK-034 | Create `app/Domains/Accounting/Actions/GetAccountPathAction.php` using AsAction trait. Implement `handle(Account $account): string` method returning full hierarchical path (e.g., "Assets > Current Assets > Cash and Cash Equivalents"). Use account's ancestors relationship with caching for performance | | |
| TASK-035 | Create `app/Domains/Accounting/Actions/ActivateAccountAction.php` and `DeactivateAccountAction.php` using AsAction trait. Implement simple toggles: set `is_active` flag, dispatch `AccountActivatedEvent` or `AccountDeactivatedEvent`, log activity. Deactivation checks: cannot deactivate if children are active (validate first) | | |

## 3. Alternatives

- **ALT-001**: Store templates in database instead of code classes - **Rejected** because templates are rarely changed, code-based provides type safety and version control, database adds unnecessary complexity
- **ALT-002**: Use JSON files for template definitions - **Rejected** because PHP classes provide better IDE support, type checking, and allow inheritance for shared logic
- **ALT-003**: Clone COA synchronously during tenant signup - **Rejected** because it blocks signup flow (can take 5-10 seconds for large COA). Async queued job is better UX
- **ALT-004**: Allow account type changes with cascade updates - **Rejected** because changing Asset → Liability breaks accounting equation and requires re-validating all historical transactions (too risky)
- **ALT-005**: Implement soft delete cascade for child accounts - **Rejected** because it can accidentally delete large subtrees. Explicit cascade or manual deletion safer

## 4. Dependencies

**Package Dependencies:**
- **DEP-001**: `lorisleiva/laravel-actions` ^2.0 (Action pattern support)
- **DEP-002**: `spatie/laravel-activitylog` ^4.0 (Audit logging)
- **DEP-003**: `kalnoy/nestedset` ^6.0 (Nested set model - from PLAN01)

**Internal Dependencies:**
- **DEP-004**: PRD01-SUB07-PLAN01 (COA Foundation) - MUST be completed first for Account model, repository, enums
- **DEP-005**: PRD01-SUB01 (Multi-Tenancy) - For tenant context and isolation
- **DEP-006**: PRD01-SUB03 (Audit Logging) - For activity tracking

**Infrastructure:**
- **DEP-007**: Redis (recommended for account path caching and search result caching)

## 5. Files

**Actions:**
- `app/Domains/Accounting/Actions/CreateAccountAction.php` - Create new account with validation
- `app/Domains/Accounting/Actions/UpdateAccountAction.php` - Update account with immutability checks
- `app/Domains/Accounting/Actions/DeleteAccountAction.php` - Safe account deletion
- `app/Domains/Accounting/Actions/CloneCoaForTenantAction.php` - Clone COA to new tenant
- `app/Domains/Accounting/Actions/SearchAccountsAction.php` - Account search with filters
- `app/Domains/Accounting/Actions/ValidateAccountCodeAction.php` - Code validation
- `app/Domains/Accounting/Actions/GetAccountPathAction.php` - Hierarchical path builder
- `app/Domains/Accounting/Actions/ActivateAccountAction.php` - Activate account
- `app/Domains/Accounting/Actions/DeactivateAccountAction.php` - Deactivate account

**Templates:**
- `app/Domains/Accounting/Contracts/CoaTemplateContract.php` - Template interface
- `app/Domains/Accounting/Templates/AbstractCoaTemplate.php` - Base template class
- `app/Domains/Accounting/Templates/ManufacturingCoaTemplate.php` - Manufacturing industry template
- `app/Domains/Accounting/Templates/RetailCoaTemplate.php` - Retail industry template
- `app/Domains/Accounting/Templates/ProfessionalServicesCoaTemplate.php` - Service industry template

**Events:**
- `app/Domains/Accounting/Events/AccountCreated.php` - Account creation event
- `app/Domains/Accounting/Events/AccountUpdated.php` - Account update event
- `app/Domains/Accounting/Events/AccountDeleted.php` - Account deletion event
- `app/Domains/Accounting/Events/AccountActivated.php` - Activation event
- `app/Domains/Accounting/Events/AccountDeactivated.php` - Deactivation event
- `app/Domains/Accounting/Events/COAImportedEvent.php` - Template import completion event
- `app/Domains/Accounting/Events/COATenantInitializedEvent.php` - Tenant COA setup completion event

**Exceptions:**
- `app/Domains/Accounting/Exceptions/IncompatibleAccountTypeException.php` - Type mismatch error
- `app/Domains/Accounting/Exceptions/DuplicateAccountCodeException.php` - Code uniqueness violation
- `app/Domains/Accounting/Exceptions/ImmutableFieldException.php` - Attempted to change immutable field
- `app/Domains/Accounting/Exceptions/AccountHasChildrenException.php` - Cannot delete with children
- `app/Domains/Accounting/Exceptions/AccountInUseException.php` - Cannot delete with transactions
- `app/Domains/Accounting/Exceptions/CoaCloneException.php` - Clone operation failure

**Listeners:**
- `app/Domains/Accounting/Listeners/InitializeTenantCoaListener.php` - Listens to TenantCreated event

**Service Provider (updated):**
- `app/Providers/AccountingServiceProvider.php` - Register templates with tag

## 6. Testing

**Unit Tests (15 tests):**
- **TEST-001**: `test_create_account_action_validates_required_fields` - Test rules()
- **TEST-002**: `test_create_account_action_checks_parent_type_compatibility` - Test CON-004
- **TEST-003**: `test_create_account_action_enforces_code_uniqueness` - Test BR-COA-002
- **TEST-004**: `test_create_account_action_dispatches_event` - Verify event fired
- **TEST-005**: `test_update_account_action_prevents_type_change` - Test CON-003
- **TEST-006**: `test_update_account_action_prevents_code_change` - Test immutability
- **TEST-007**: `test_update_account_action_validates_parent_change` - Test circular reference
- **TEST-008**: `test_delete_account_action_checks_children` - Test CON-005
- **TEST-009**: `test_delete_account_action_checks_transactions` - Test SR-COA-001
- **TEST-010**: `test_manufacturing_template_returns_correct_accounts` - Verify 60 accounts
- **TEST-011**: `test_retail_template_returns_correct_accounts` - Verify 50 accounts
- **TEST-012**: `test_template_apply_creates_accounts_with_hierarchy` - Test nested structure
- **TEST-013**: `test_search_action_filters_by_type` - Test filter application
- **TEST-014**: `test_validate_code_action_suggests_alternatives` - Test suggestion logic
- **TEST-015**: `test_get_path_action_returns_correct_hierarchy` - Test path building

**Feature Tests (12 tests):**
- **TEST-016**: `test_create_account_via_action_persists_correctly` - End-to-end creation
- **TEST-017**: `test_update_account_maintains_nested_set_integrity` - Test lft/rgt after update
- **TEST-018**: `test_delete_account_soft_deletes_and_preserves_tree` - Test soft delete
- **TEST-019**: `test_clone_coa_creates_all_accounts_for_tenant` - Test cloning
- **TEST-020**: `test_clone_coa_applies_customizations` - Test customization parameter
- **TEST-021**: `test_template_application_in_transaction` - Test atomicity
- **TEST-022**: `test_template_application_dispatches_event` - Verify COAImportedEvent
- **TEST-023**: `test_tenant_signup_triggers_coa_initialization` - Test listener integration
- **TEST-024**: `test_search_accounts_returns_relevant_results` - Test search accuracy
- **TEST-025**: `test_search_accounts_with_fuzzy_matching` - Test typo tolerance
- **TEST-026**: `test_activate_deactivate_account_toggles_status` - Test activation actions
- **TEST-027**: `test_activity_log_records_all_account_operations` - Verify audit trail

**Integration Tests (8 tests):**
- **TEST-028**: `test_actions_integrate_with_repository` - Test repository injection
- **TEST-029**: `test_events_dispatched_to_listeners` - Test event system
- **TEST-030**: `test_template_registry_resolves_all_templates` - Test container tagging
- **TEST-031**: `test_artisan_commands_work_correctly` - Test asCommand() methods
- **TEST-032**: `test_concurrent_account_creation_handles_uniqueness` - Test race conditions
- **TEST-033**: `test_clone_coa_handles_large_templates` - Test performance with 1000+ accounts
- **TEST-034**: `test_transaction_rollback_on_clone_failure` - Test atomicity
- **TEST-035**: `test_nested_set_integrity_after_complex_operations` - Test lft/rgt consistency

**Performance Tests (3 tests):**
- **TEST-036**: `test_clone_coa_completes_within_10_seconds` - Test async acceptable for UX
- **TEST-037**: `test_search_accounts_completes_within_100ms` - Test search performance
- **TEST-038**: `test_get_path_caching_reduces_query_count` - Test caching effectiveness

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: Tenant COA initialization fails during signup causing broken tenant - **Mitigation**: Use queued job with retry, fallback to minimal COA (Assets/Liabilities/Equity/Revenue/Expense only), send alert to admin
- **RISK-002**: Template definitions become outdated with accounting standard changes - **Mitigation**: Version templates (ManufacturingCoaTemplateV2), document update process, provide template migration tools
- **RISK-003**: Circular reference validation misses edge cases causing infinite loops - **Mitigation**: Comprehensive edge case testing, limit tree depth to 10 levels, add circuit breaker
- **RISK-004**: Account deletion with force flag accidentally removes important accounts - **Mitigation**: Require additional confirmation (--confirm flag), log deletion with high severity, implement undelete within 30 days
- **RISK-005**: Large COA clone (1000+ accounts) times out in queue worker - **Mitigation**: Increase queue timeout to 300 seconds, chunk cloning into batches of 100 accounts, implement progress tracking

**Assumptions:**
- **ASSUMPTION-001**: Industry templates cover 80% of business needs (Manufacturing, Retail, Services)
- **ASSUMPTION-002**: Tenants clone COA during signup, rarely import later (optimize for initial setup)
- **ASSUMPTION-003**: Account structure changes are infrequent (monthly at most, not daily)
- **ASSUMPTION-004**: Most tenants use < 500 accounts (cloning takes < 5 seconds)
- **ASSUMPTION-005**: Account search queries are simple keyword-based, not complex boolean logic

## 8. KIV for future implementations

- **KIV-001**: Add more industry templates (Healthcare, Construction, Hospitality, Non-Profit, Government)
- **KIV-002**: Implement COA export to Excel/PDF for offline review and printing
- **KIV-003**: Add bulk account import from Excel with validation and preview
- **KIV-004**: Implement account merge functionality (combine duplicate accounts, reassign transactions)
- **KIV-005**: Add account approval workflow (new accounts require manager approval before activation)
- **KIV-006**: Implement COA comparison tool (show differences between two COA structures)
- **KIV-007**: Add account usage analytics (which accounts are most/least used, identify unused accounts)
- **KIV-008**: Implement multi-COA support per tenant (different COA for different business units/regions)
- **KIV-009**: Add AI-powered account recommendation based on transaction description

## 9. Related PRD / Further Reading

- **Master PRD:** [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD:** [../prd/prd-01/PRD01-SUB07-CHART-OF-ACCOUNTS.md](../prd/prd-01/PRD01-SUB07-CHART-OF-ACCOUNTS.md)
- **Related Plans:**
  - PRD01-SUB07-PLAN01 (COA Foundation) - Prerequisites: Account model, repository, enums
  - PRD01-SUB07-PLAN03 (COA API) - Consumers: REST API will use these actions
  - PRD01-SUB01 (Multi-Tenancy) - Tenant context and isolation
- **External Documentation:**
  - Laravel Actions Package: https://laravelactions.com/
  - Chart of Accounts Standards: https://www.accountingtools.com/articles/what-is-a-chart-of-accounts.html
  - Industry-Specific COA Examples: https://www.freshbooks.com/hub/accounting/chart-of-accounts-examples
  - GAAP Accounting Standards: https://www.fasb.org/standards
