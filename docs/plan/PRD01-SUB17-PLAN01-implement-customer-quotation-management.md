---
plan: Customer & Quotation Management
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, sales, customer-management, quotation, business-logic, revenue-management]
---

# PRD01-SUB17-PLAN01: Implement Customer & Quotation Management

![Status: Planned](https://img.shields.io/badge/Status-Planned-blue)

This implementation plan covers the foundational components of the Sales module, including customer master data management, sales quotation creation and management, and basic pricing infrastructure. This establishes the essential building blocks for the order-to-cash cycle.

## 1. Requirements & Constraints

### Functional Requirements
- **FR-SD-001**: Support sales quotation creation with pricing, discounts, and validity periods
- **FR-SD-003**: Manage customer master data including billing/shipping addresses and credit terms
- **FR-SD-005**: Implement pricing management with customer-specific pricing and volume discounts

### Business Rules
- **BR-SD-001**: Orders cannot exceed customer credit limit without management override
- **BR-SD-004**: Customers with active orders cannot be deleted

### Data Requirements
- **DR-SD-001**: Store complete order history including revisions and approvals
- **DR-SD-002**: Maintain pricing history for audit and analysis

### Performance Requirements
- **PR-SD-002**: Customer search must return results in < 100ms

### Security Requirements
- **SR-SD-001**: Implement customer-specific pricing access controls
- **SR-SD-002**: Log all order modifications with user and timestamp

### Constraints
- **CON-001**: Must integrate with SUB01 (Multi-Tenancy) for tenant isolation
- **CON-002**: Must integrate with SUB03 (Audit Logging) for activity tracking
- **CON-003**: Must integrate with SUB15 (Backoffice) for credit limit checking

### Guidelines
- **GUD-001**: Follow repository pattern for all data access
- **GUD-002**: Use Laravel Actions for all business logic
- **GUD-003**: Implement soft deletes for audit compliance
- **GUD-004**: Use decimal(15,2) precision for all financial amounts

### Patterns
- **PAT-001**: Repository pattern with contracts for data access
- **PAT-002**: Observer pattern for automatic tracking
- **PAT-003**: Event-driven architecture for cross-module communication
- **PAT-004**: API Resource pattern for response transformation

## 2. Implementation Steps

### GOAL-001: Customer Master Data Management

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-SD-003 | Implement complete customer master data with billing/shipping addresses, credit terms, payment terms | | |
| BR-SD-004 | Prevent deletion of customers with active orders | | |
| PR-SD-002 | Ensure customer search completes in < 100ms | | |
| SR-SD-002 | Log all customer modifications | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `2025_01_01_000001_create_customers_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK), customer_code (VARCHAR 50 unique per tenant), customer_name (VARCHAR 255), contact_person (VARCHAR 255 nullable), email (VARCHAR 255 nullable), phone (VARCHAR 50 nullable), billing_address (TEXT nullable), shipping_address (TEXT nullable), city (VARCHAR 100), state (VARCHAR 100), postal_code (VARCHAR 20), country (VARCHAR 100), tax_id (VARCHAR 100), payment_terms (VARCHAR 100: NET30/NET60/COD), credit_limit (DECIMAL 15,2 nullable), current_balance (DECIMAL 15,2 default 0), price_list_id (BIGINT nullable), is_active (BOOLEAN default TRUE), timestamps, soft deletes; indexes: tenant_id, customer_code, is_active, email | | |
| TASK-002 | Create enum `PaymentTerms` with values: NET30, NET60, NET90, COD, PREPAID | | |
| TASK-003 | Create model `packages/sales/src/Models/Customer.php` with traits: BelongsToTenant, HasActivityLogging, SoftDeletes; fillable: customer_code, customer_name, contact_person, email, phone, billing_address, shipping_address, city, state, postal_code, country, tax_id, payment_terms, credit_limit, is_active; casts: payment_terms → PaymentTerms enum, credit_limit → float, current_balance → float, is_active → boolean; relationships: priceList (belongsTo), salesOrders (hasMany), quotations (hasMany); scopes: active(), searchable(string $term); computed: available_credit (credit_limit - current_balance), is_credit_available | | |
| TASK-004 | Create factory `CustomerFactory.php` with realistic data; states: withCreditLimit(float $amount), withZeroCredit(), withExceededCredit(), inactive() | | |
| TASK-005 | Create contract `packages/sales/src/Contracts/CustomerRepositoryContract.php` with methods: findById(int $id): ?Customer, findByCode(string $code, string $tenantId): ?Customer, search(string $term, array $filters = []): Collection, create(array $data): Customer, update(Customer $customer, array $data): Customer, delete(Customer $customer): bool, paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator, getActiveCustomers(string $tenantId): Collection, checkCreditAvailability(Customer $customer, float $amount): bool | | |
| TASK-006 | Implement `CustomerRepository.php` with optimized search using database indexes; implement filter support for: is_active, payment_terms, credit_status (available/exceeded); cache customer data with 5-minute TTL for performance | | |
| TASK-007 | Create action `CreateCustomerAction.php` using AsAction; inject CustomerRepositoryContract, ActivityLoggerContract; validate uniqueness of customer_code per tenant; validate credit_limit >= 0; create customer; log activity "Customer created"; dispatch CustomerCreatedEvent; return Customer | | |
| TASK-008 | Create action `UpdateCustomerAction.php`; validate credit_limit changes (cannot decrease below current_balance); update customer; log activity "Customer updated" with changed fields; dispatch CustomerUpdatedEvent | | |
| TASK-009 | Create action `DeleteCustomerAction.php`; check for active sales orders/quotations; if found throw ValidationException "Cannot delete customer with active transactions"; soft delete; log activity "Customer deleted" | | |
| TASK-010 | Create service `packages/sales/src/Services/CreditCheckService.php` with methods: checkCreditLimit(Customer $customer, float $amount): bool, calculateAvailableCredit(Customer $customer): float, getCreditStatus(Customer $customer): string (available/warning/exceeded), requiresApproval(Customer $customer, float $amount): bool (returns true if exceeds credit limit) | | |
| TASK-011 | Create event `CustomerCreatedEvent` with properties: Customer $customer, User $createdBy | | |
| TASK-012 | Create event `CustomerUpdatedEvent` with properties: Customer $customer, array $changes, User $updatedBy | | |
| TASK-013 | Create observer `CustomerObserver.php` with deleting() method to prevent deletion with active orders | | |
| TASK-014 | Create policy `CustomerPolicy.php` with methods: viewAny(User $user): bool, view(User $user, Customer $customer): bool, create(User $user): bool, update(User $user, Customer $customer): bool, delete(User $user, Customer $customer): bool; require 'manage-customers' permission; enforce tenant scope | | |
| TASK-015 | Create API controller `packages/sales/src/Http/Controllers/CustomerController.php` with routes: index (GET /sales/customers), store (POST /sales/customers), show (GET /sales/customers/{id}), update (PATCH /sales/customers/{id}), destroy (DELETE /sales/customers/{id}), creditStatus (GET /sales/customers/{id}/credit-status); authorize all actions; inject CustomerRepositoryContract, CreditCheckService | | |
| TASK-016 | Create form request `StoreCustomerRequest.php` with validation: customer_code (required, max:50, unique per tenant), customer_name (required, max:255), email (nullable, email, max:255), phone (nullable, max:50), payment_terms (required, in:PaymentTerms), credit_limit (nullable, numeric, min:0), billing_address (nullable, string), shipping_address (nullable, string) | | |
| TASK-017 | Create form request `UpdateCustomerRequest.php` extending StoreCustomerRequest; customer_code unique excluding current record | | |
| TASK-018 | Create API resource `CustomerResource.php` with fields: id, customer_code, customer_name, contact_person, email, phone, billing_address, shipping_address, payment_terms, credit_limit, current_balance, available_credit, credit_status, is_active, created_at; include relationships: active_orders_count, open_quotations_count | | |
| TASK-019 | Create API resource `CustomerCreditStatusResource.php` with fields: credit_limit, current_balance, available_credit, credit_status, can_place_order | | |
| TASK-020 | Write unit tests for Customer model: test relationships, test available_credit calculation, test credit status determination | | |
| TASK-021 | Write unit tests for CreditCheckService: test checkCreditLimit with various scenarios (within limit, at limit, exceeds limit), test requiresApproval logic | | |
| TASK-022 | Write feature tests for CustomerController: test complete CRUD via API, test customer search with various filters, test credit status endpoint, test authorization enforcement, test cannot delete customer with active orders | | |
| TASK-023 | Write performance test: seed 10,000 customers, test search query completes in < 100ms (PR-SD-002) | | |

### GOAL-002: Sales Quotation Foundation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-SD-001 | Implement sales quotation creation with pricing, discounts, validity periods | | |
| DR-SD-001 | Store complete quotation history with revisions | | |
| SR-SD-002 | Log all quotation modifications | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-024 | Create migration `2025_01_01_000002_create_sales_quotations_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK), quotation_number (VARCHAR 100 unique per tenant), quotation_date (DATE), customer_id (BIGINT FK customers), valid_until (DATE nullable), delivery_address (TEXT nullable), currency_code (VARCHAR 10), subtotal (DECIMAL 15,2 default 0), discount_amount (DECIMAL 15,2 default 0), tax_amount (DECIMAL 15,2 default 0), total_amount (DECIMAL 15,2 default 0), status (VARCHAR 20 default 'draft': draft/sent/accepted/rejected/expired/converted), notes (TEXT nullable), created_by (BIGINT FK users), timestamps, soft deletes; indexes: tenant_id, quotation_number, customer_id, status, quotation_date | | |
| TASK-025 | Create migration `2025_01_01_000003_create_sales_quotation_lines_table.php` with columns: id (BIGSERIAL), quotation_id (BIGINT FK sales_quotations cascade), line_number (INT), item_id (BIGINT nullable FK inventory_items), item_description (TEXT), quantity (DECIMAL 15,4), uom_id (BIGINT FK uoms), unit_price (DECIMAL 15,2), discount_percent (DECIMAL 5,2 default 0), discount_amount (DECIMAL 15,2 default 0), line_total (DECIMAL 15,2), timestamps; indexes: quotation_id, item_id | | |
| TASK-026 | Create enum `QuotationStatus` with values: DRAFT, SENT, ACCEPTED, REJECTED, EXPIRED, CONVERTED | | |
| TASK-027 | Create model `SalesQuotation.php` with traits: BelongsToTenant, HasActivityLogging, SoftDeletes; fillable: quotation_number, quotation_date, customer_id, valid_until, delivery_address, currency_code, notes; casts: quotation_date → date, valid_until → date, status → QuotationStatus enum, subtotal → float, total_amount → float; relationships: customer (belongsTo), lines (hasMany SalesQuotationLine), createdBy (belongsTo User), salesOrder (hasOne); computed: is_expired (valid_until < today), days_until_expiry; scopes: active(), byStatus(QuotationStatus $status), expiringWithinDays(int $days) | | |
| TASK-028 | Create model `SalesQuotationLine.php` with fillable: line_number, item_id, item_description, quantity, uom_id, unit_price, discount_percent; casts: quantity → float, unit_price → float, discount_percent → float, line_total → float; relationships: quotation (belongsTo), item (belongsTo InventoryItem), uom (belongsTo); accessor: line_total (calculated as quantity * unit_price - discount_amount) | | |
| TASK-029 | Create factory `SalesQuotationFactory.php` with realistic data; states: withLines(int $count = 3), expired(), converting(), accepted() | | |
| TASK-030 | Create factory `SalesQuotationLineFactory.php` with realistic pricing data | | |
| TASK-031 | Create contract `QuotationRepositoryContract.php` with methods: findById(int $id): ?SalesQuotation, findByNumber(string $number, string $tenantId): ?SalesQuotation, create(array $data): SalesQuotation, update(SalesQuotation $quotation, array $data): SalesQuotation, delete(SalesQuotation $quotation): bool, paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator, getExpiringSoon(int $days = 7): Collection | | |
| TASK-032 | Implement `QuotationRepository.php` with eager loading for customer and lines; implement filters: status, customer_id, date_range, expiring_soon | | |
| TASK-033 | Create service `QuotationCalculationService.php` with methods: calculateLineTotal(array $lineData): float, calculateSubtotal(SalesQuotation $quotation): float, calculateTotal(SalesQuotation $quotation): float, applyDiscount(float $amount, float $discountPercent): float, recalculateQuotation(SalesQuotation $quotation): void (updates all totals) | | |
| TASK-034 | Create action `CreateSalesQuotationAction.php` using AsAction; inject QuotationRepositoryContract, QuotationCalculationService, ActivityLoggerContract; validate customer exists and is active; generate quotation_number using document sequence; validate valid_until >= quotation_date; create quotation header; create quotation lines; calculate and update totals; log activity "Quotation created"; dispatch QuotationCreatedEvent; return SalesQuotation | | |
| TASK-035 | Create action `UpdateSalesQuotationAction.php`; validate quotation status is DRAFT; validate line changes; recalculate totals; update quotation; log activity "Quotation updated"; dispatch QuotationUpdatedEvent | | |
| TASK-036 | Create action `SendQuotationAction.php`; validate quotation status is DRAFT; validate has lines; update status to SENT; log activity "Quotation sent to customer"; dispatch QuotationSentEvent | | |
| TASK-037 | Create action `AcceptQuotationAction.php`; validate status is SENT; validate not expired; update status to ACCEPTED; log activity "Quotation accepted"; dispatch QuotationAcceptedEvent | | |
| TASK-038 | Create action `RejectQuotationAction.php`; validate status is SENT; update status to REJECTED with reason; log activity "Quotation rejected" | | |
| TASK-039 | Create event `QuotationCreatedEvent` with properties: SalesQuotation $quotation, User $createdBy | | |
| TASK-040 | Create event `QuotationSentEvent` with properties: SalesQuotation $quotation, Customer $customer | | |
| TASK-041 | Create event `QuotationAcceptedEvent` with properties: SalesQuotation $quotation, Customer $customer | | |
| TASK-042 | Create observer `SalesQuotationObserver.php` with creating() method to auto-generate quotation_number; updating() method to recalculate totals when lines change | | |
| TASK-043 | Create policy `SalesQuotationPolicy.php` with authorization methods requiring 'manage-quotations' permission | | |
| TASK-044 | Create API controller `QuotationController.php` with routes: index, store, show, update, destroy, send (POST /quotations/{id}/send), accept (POST /quotations/{id}/accept), reject (POST /quotations/{id}/reject); authorize actions; inject QuotationRepositoryContract | | |
| TASK-045 | Create form request `StoreQuotationRequest.php` with validation: customer_id (required, exists:customers), quotation_date (required, date), valid_until (nullable, date, after:quotation_date), lines (required, array, min:1), lines.*.item_id (nullable, exists:inventory_items), lines.*.quantity (required, numeric, min:0.0001), lines.*.unit_price (required, numeric, min:0) | | |
| TASK-046 | Create form request `UpdateQuotationRequest.php` extending StoreQuotationRequest; add status validation (must be DRAFT to update) | | |
| TASK-047 | Create API resource `SalesQuotationResource.php` with fields: id, quotation_number, quotation_date, valid_until, customer (nested CustomerResource), lines (nested collection), subtotal, discount_amount, tax_amount, total_amount, status, is_expired, days_until_expiry, created_at | | |
| TASK-048 | Create API resource `SalesQuotationLineResource.php` with fields: line_number, item, item_description, quantity, uom, unit_price, discount_percent, discount_amount, line_total | | |
| TASK-049 | Write unit tests for SalesQuotation model: test status transitions, test is_expired logic, test relationships | | |
| TASK-050 | Write unit tests for QuotationCalculationService: test line total calculation, test discount application, test subtotal/total calculation with various scenarios | | |
| TASK-051 | Write feature tests for QuotationController: test complete quotation lifecycle via API (create, send, accept), test quotation with multiple lines, test cannot update non-draft quotation, test authorization | | |

### GOAL-003: Pricing Management Foundation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-SD-005 | Implement pricing management with customer-specific pricing and volume discounts | | |
| DR-SD-002 | Maintain pricing history for audit and analysis | | |
| SR-SD-001 | Implement customer-specific pricing access controls | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-052 | Create migration `2025_01_01_000004_create_price_lists_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK), price_list_code (VARCHAR 50 unique per tenant), price_list_name (VARCHAR 255), currency_code (VARCHAR 10), is_default (BOOLEAN default FALSE), valid_from (DATE nullable), valid_to (DATE nullable), is_active (BOOLEAN default TRUE), timestamps; indexes: tenant_id, price_list_code, is_active, is_default | | |
| TASK-053 | Create migration `2025_01_01_000005_create_price_list_items_table.php` with columns: id (BIGSERIAL), price_list_id (BIGINT FK price_lists cascade), item_id (BIGINT FK inventory_items), unit_price (DECIMAL 15,2), discount_percent (DECIMAL 5,2 default 0), min_quantity (DECIMAL 15,4 default 0), valid_from (DATE nullable), valid_to (DATE nullable), timestamps; unique constraint: (price_list_id, item_id, min_quantity); indexes: price_list_id, item_id | | |
| TASK-054 | Create model `PriceList.php` with traits: BelongsToTenant, HasActivityLogging; fillable: price_list_code, price_list_name, currency_code, is_default, valid_from, valid_to, is_active; casts: is_default → boolean, is_active → boolean, valid_from → date, valid_to → date; relationships: items (hasMany PriceListItem), customers (hasMany Customer); scopes: active(), default(), validOn(Carbon $date); computed: is_valid_now | | |
| TASK-055 | Create model `PriceListItem.php` with fillable: item_id, unit_price, discount_percent, min_quantity, valid_from, valid_to; casts: unit_price → float, discount_percent → float, min_quantity → float; relationships: priceList (belongsTo), item (belongsTo InventoryItem); computed: effective_price (unit_price - discount) | | |
| TASK-056 | Create factory `PriceListFactory.php` with states: default(), withItems(int $count = 10), expired() | | |
| TASK-057 | Create factory `PriceListItemFactory.php` with states: withVolumeDiscount(), withDateRange() | | |
| TASK-058 | Create contract `PricingServiceContract.php` with methods: getPrice(Customer $customer, int $itemId, float $quantity, ?Carbon $date = null): float, getPriceBreakdown(Customer $customer, int $itemId, float $quantity): array, getEffectivePriceList(Customer $customer, ?Carbon $date = null): ?PriceList, getVolumeDiscounts(int $itemId, int $priceListId): Collection | | |
| TASK-059 | Implement `PricingService.php` implementing PricingServiceContract; implement pricing hierarchy: 1) customer-specific price list, 2) volume discounts, 3) default price list, 4) item base price; cache pricing data with 15-minute TTL; log pricing queries for audit (DR-SD-002) | | |
| TASK-060 | Create action `CreatePriceListAction.php`; validate price_list_code uniqueness; validate only one default price list per tenant; create price list; log activity "Price list created" | | |
| TASK-061 | Create action `UpdatePriceListAction.php`; validate cannot change default if other price lists exist; update price list; log activity "Price list updated" with changes | | |
| TASK-062 | Create action `AddPriceListItemAction.php`; validate item exists; validate min_quantity >= 0; validate unit_price > 0; create price list item; clear pricing cache; log activity "Price added to list" | | |
| TASK-063 | Create action `CalculatePriceAction.php` using AsAction; inject PricingServiceContract; get effective price for customer/item/quantity; return price breakdown with: base_price, volume_discount, customer_discount, final_price | | |
| TASK-064 | Create policy `PriceListPolicy.php` requiring 'manage-pricing' permission for all operations; implement view restrictions based on customer assignment (SR-SD-001) | | |
| TASK-065 | Create API controller `PriceListController.php` with routes: index, store, show, update, destroy, items (GET /price-lists/{id}/items), addItem (POST /price-lists/{id}/items); authorize actions | | |
| TASK-066 | Create API controller `PricingController.php` with route: getPrice (GET /pricing/{customerId}/{itemId}?quantity=X) to retrieve effective pricing | | |
| TASK-067 | Create form request `StorePriceListRequest.php` with validation: price_list_code (required, unique per tenant), price_list_name (required), currency_code (required, size:3), is_default (boolean), valid_from (nullable, date), valid_to (nullable, date, after:valid_from) | | |
| TASK-068 | Create form request `AddPriceListItemRequest.php` with validation: item_id (required, exists:inventory_items), unit_price (required, numeric, min:0.01), discount_percent (nullable, numeric, min:0, max:100), min_quantity (nullable, numeric, min:0) | | |
| TASK-069 | Create API resource `PriceListResource.php` with fields: id, price_list_code, price_list_name, currency_code, is_default, is_active, valid_from, valid_to, items_count | | |
| TASK-070 | Create API resource `PriceListItemResource.php` with fields: item (nested), unit_price, discount_percent, effective_price, min_quantity, valid_from, valid_to | | |
| TASK-071 | Create API resource `PriceBreakdownResource.php` with fields: customer, item, quantity, base_price, volume_discount, customer_discount, final_price, price_list_used | | |
| TASK-072 | Write unit tests for PricingService: test pricing hierarchy (customer price > volume discount > default), test volume discount calculation, test cache effectiveness | | |
| TASK-073 | Write unit tests for PriceList model: test is_valid_now logic, test default scope, test relationships | | |
| TASK-074 | Write feature tests for PricingController: test get price endpoint with various scenarios (customer-specific, volume discount, default), test price breakdown response | | |
| TASK-075 | Write integration test: create customer with price list, create items with volume discounts, verify correct price returned for various quantities | | |

### GOAL-004: Service Provider & Infrastructure

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| CON-001 | Integration with multi-tenancy module | | |
| CON-002 | Integration with audit logging | | |
| SR-SD-002 | Log all modifications | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-076 | Create `packages/sales/src/SalesServiceProvider.php`; register bindings: CustomerRepositoryContract → CustomerRepository, QuotationRepositoryContract → QuotationRepository, PricingServiceContract → PricingService; register CreditCheckService, QuotationCalculationService; register routes from routes/api.php; register migrations; register config; register observers (CustomerObserver, SalesQuotationObserver); register policies | | |
| TASK-077 | Create config `packages/sales/config/sales.php` with settings: quotation_validity_days (default 30), credit_limit_warning_threshold (default 0.8), pricing_cache_ttl (default 900), default_currency (default 'USD'), auto_generate_quotation_number (default true), quotation_number_prefix (default 'QT'), customer_code_prefix (default 'CUST') | | |
| TASK-078 | Create base service `BaseSalesService.php` with common methods: validateTenantScope(Model $model), logActivity(string $description, Model $subject), getCacheKey(string $prefix, string $identifier), clearCache(string $pattern) | | |
| TASK-079 | Create middleware `ValidateSalesAccess.php` to check user has required sales permissions; throw 403 if unauthorized | | |
| TASK-080 | Register routes in `packages/sales/routes/api.php` with prefix '/sales', middleware: ['auth:sanctum', 'tenant', 'validate-sales-access']; group customers, quotations, price-lists, pricing routes | | |
| TASK-081 | Create seeder `SalesSeeder.php` to seed sample customers, price lists, and quotations for development/testing | | |
| TASK-082 | Create README.md for sales package with installation, configuration, usage examples, API documentation | | |
| TASK-083 | Create composer.json for sales package with metadata: name "azaharizaman/erp-sales", namespace "Nexus\\Erp\\Sales", require: php ^8.2, laravel/framework ^12.0, azaharizaman/erp-core ^1.0, azaharizaman/erp-inventory-management ^1.0, lorisleiva/laravel-actions ^2.0; autoload PSR-4 | | |
| TASK-084 | Write unit tests for SalesServiceProvider: test all bindings registered, test routes loaded, test config published | | |
| TASK-085 | Write feature tests for middleware: test blocks unauthorized users, test allows users with permissions | | |

### GOAL-005: Testing, Documentation & Deployment

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| PR-SD-002 | Customer search < 100ms | | |
| SR-SD-001 | Customer-specific pricing access controls | | |
| SR-SD-002 | All modifications logged | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-086 | Write comprehensive unit tests for all models (Customer, SalesQuotation, PriceList): test factories, relationships, scopes, computed attributes | | |
| TASK-087 | Write comprehensive unit tests for all services (CreditCheckService, QuotationCalculationService, PricingService): test all public methods, test edge cases | | |
| TASK-088 | Write comprehensive unit tests for all actions: test validation, test business logic, test event dispatching; mock repository dependencies | | |
| TASK-089 | Write feature tests for complete workflows: customer creation → quotation creation → quotation sending → quotation acceptance; verify all database records created, all events dispatched | | |
| TASK-090 | Write integration tests: test customer credit check integration with backoffice module (mock), test pricing integration with inventory items | | |
| TASK-091 | Write performance test: seed 10,000 customers with various data, test customer search query performance (must be < 100ms per PR-SD-002); test with various filters; assert results accurate | | |
| TASK-092 | Write security tests: test customer-specific pricing access controls (SR-SD-001), test users can only access customers in their tenant, test pricing visibility restrictions | | |
| TASK-093 | Set up Pest configuration in `packages/sales/tests/Pest.php`; configure RefreshDatabase, tenant seeding, authentication helpers | | |
| TASK-094 | Achieve minimum 80% code coverage; run `./vendor/bin/pest --coverage`; add tests for uncovered code paths | | |
| TASK-095 | Create API documentation in `docs/api/sales-api.md`: document all endpoints with OpenAPI 3.0 spec, include request/response examples, document error codes, include authentication requirements | | |
| TASK-096 | Create user guide in `docs/guides/sales-user-guide.md`: customer management workflows, quotation creation best practices, pricing configuration guide, common troubleshooting | | |
| TASK-097 | Create migration guide in `docs/migrations/sales-setup.md`: installation steps, initial data setup (customers, price lists), configuration options, integration with other modules | | |
| TASK-098 | Update main README.md with sales module overview, installation instructions, quick start guide, link to detailed documentation | | |
| TASK-099 | Create CHANGELOG.md for sales package tracking all changes by version | | |
| TASK-100 | Validate all acceptance criteria from PRD: customer master data management working, quotation creation working, pricing management functional, credit limit checking operational, all security requirements met | | |
| TASK-101 | Conduct code review: verify PSR-12 compliance via Laravel Pint, verify strict types in all files, verify PHPDoc completeness, verify repository pattern usage throughout | | |
| TASK-102 | Run full test suite: `./vendor/bin/pest packages/sales/tests/`; verify all tests pass; fix any failures; ensure no flaky tests | | |
| TASK-103 | Deploy to staging environment; perform smoke tests on all API endpoints; verify database migrations successful; verify customer search performance meets requirements; verify pricing calculations accurate | | |

## 3. Alternatives

- **ALT-001**: Use simple customer-item pricing table instead of price lists - rejected due to lack of flexibility for volume discounts and date-based pricing
- **ALT-002**: Store pricing history in separate audit table - rejected as activity log provides sufficient audit trail (DR-SD-002)
- **ALT-003**: Calculate quotation totals on-the-fly instead of storing - rejected due to performance concerns and need for historical accuracy
- **ALT-004**: Use enum for payment terms instead of VARCHAR - accepted and implemented as PaymentTerms enum

## 4. Dependencies

### Mandatory Dependencies
- **DEP-001**: SUB01 (Multi-Tenancy) - Tenant model and tenant_id isolation
- **DEP-002**: SUB02 (Authentication & Authorization) - User model, roles, permissions  
- **DEP-003**: SUB03 (Audit Logging) - ActivityLoggerContract for tracking changes
- **DEP-004**: SUB14 (Inventory Management) - InventoryItem model for item references
- **DEP-005**: SUB06 (UOM) - Unit of measure for quotation lines

### Optional Dependencies
- **DEP-006**: SUB15 (Backoffice) - Credit limit checking (can be implemented later)
- **DEP-007**: SUB04 (Serial Numbering) - Auto-generate quotation numbers (fallback: manual)

### Package Dependencies
- **DEP-008**: lorisleiva/laravel-actions ^2.0 - Action pattern implementation
- **DEP-009**: spatie/laravel-activitylog ^4.0 - Activity logging trait
- **DEP-010**: brick/math ^0.12 - Precise decimal calculations

## 5. Files

### Models
- `packages/sales/src/Models/Customer.php` - Customer master data model
- `packages/sales/src/Models/SalesQuotation.php` - Sales quotation header model
- `packages/sales/src/Models/SalesQuotationLine.php` - Quotation line items model
- `packages/sales/src/Models/PriceList.php` - Price list master model
- `packages/sales/src/Models/PriceListItem.php` - Price list item details model

### Enums
- `packages/sales/src/Enums/PaymentTerms.php` - Payment terms enumeration
- `packages/sales/src/Enums/QuotationStatus.php` - Quotation status enumeration

### Repositories
- `packages/sales/src/Contracts/CustomerRepositoryContract.php` - Customer repository interface
- `packages/sales/src/Repositories/CustomerRepository.php` - Customer repository implementation
- `packages/sales/src/Contracts/QuotationRepositoryContract.php` - Quotation repository interface
- `packages/sales/src/Repositories/QuotationRepository.php` - Quotation repository implementation

### Services
- `packages/sales/src/Services/CreditCheckService.php` - Credit limit validation service
- `packages/sales/src/Services/QuotationCalculationService.php` - Quotation totals calculation
- `packages/sales/src/Contracts/PricingServiceContract.php` - Pricing service interface
- `packages/sales/src/Services/PricingService.php` - Pricing calculation and lookup
- `packages/sales/src/Services/BaseSalesService.php` - Base service with common methods

### Actions
- `packages/sales/src/Actions/CreateCustomerAction.php` - Create customer action
- `packages/sales/src/Actions/UpdateCustomerAction.php` - Update customer action
- `packages/sales/src/Actions/DeleteCustomerAction.php` - Delete customer action
- `packages/sales/src/Actions/CreateSalesQuotationAction.php` - Create quotation action
- `packages/sales/src/Actions/UpdateSalesQuotationAction.php` - Update quotation action
- `packages/sales/src/Actions/SendQuotationAction.php` - Send quotation to customer
- `packages/sales/src/Actions/AcceptQuotationAction.php` - Accept quotation action
- `packages/sales/src/Actions/RejectQuotationAction.php` - Reject quotation action
- `packages/sales/src/Actions/CreatePriceListAction.php` - Create price list action
- `packages/sales/src/Actions/AddPriceListItemAction.php` - Add item to price list
- `packages/sales/src/Actions/CalculatePriceAction.php` - Calculate effective price

### Controllers
- `packages/sales/src/Http/Controllers/CustomerController.php` - Customer API controller
- `packages/sales/src/Http/Controllers/QuotationController.php` - Quotation API controller
- `packages/sales/src/Http/Controllers/PriceListController.php` - Price list API controller
- `packages/sales/src/Http/Controllers/PricingController.php` - Pricing query controller

### Requests
- `packages/sales/src/Http/Requests/StoreCustomerRequest.php` - Customer creation validation
- `packages/sales/src/Http/Requests/UpdateCustomerRequest.php` - Customer update validation
- `packages/sales/src/Http/Requests/StoreQuotationRequest.php` - Quotation creation validation
- `packages/sales/src/Http/Requests/StorePriceListRequest.php` - Price list validation
- `packages/sales/src/Http/Requests/AddPriceListItemRequest.php` - Price list item validation

### Resources
- `packages/sales/src/Http/Resources/CustomerResource.php` - Customer JSON transformation
- `packages/sales/src/Http/Resources/SalesQuotationResource.php` - Quotation transformation
- `packages/sales/src/Http/Resources/PriceListResource.php` - Price list transformation
- `packages/sales/src/Http/Resources/PriceBreakdownResource.php` - Price breakdown

### Events
- `packages/sales/src/Events/CustomerCreatedEvent.php` - Customer created event
- `packages/sales/src/Events/CustomerUpdatedEvent.php` - Customer updated event
- `packages/sales/src/Events/QuotationCreatedEvent.php` - Quotation created event
- `packages/sales/src/Events/QuotationSentEvent.php` - Quotation sent event
- `packages/sales/src/Events/QuotationAcceptedEvent.php` - Quotation accepted event

### Observers & Policies
- `packages/sales/src/Observers/CustomerObserver.php` - Customer model observer
- `packages/sales/src/Observers/SalesQuotationObserver.php` - Quotation observer
- `packages/sales/src/Policies/CustomerPolicy.php` - Customer authorization policy
- `packages/sales/src/Policies/SalesQuotationPolicy.php` - Quotation policy
- `packages/sales/src/Policies/PriceListPolicy.php` - Price list policy

### Database
- `packages/sales/database/migrations/2025_01_01_000001_create_customers_table.php`
- `packages/sales/database/migrations/2025_01_01_000002_create_sales_quotations_table.php`
- `packages/sales/database/migrations/2025_01_01_000003_create_sales_quotation_lines_table.php`
- `packages/sales/database/migrations/2025_01_01_000004_create_price_lists_table.php`
- `packages/sales/database/migrations/2025_01_01_000005_create_price_list_items_table.php`
- `packages/sales/database/factories/CustomerFactory.php`
- `packages/sales/database/factories/SalesQuotationFactory.php`
- `packages/sales/database/seeders/SalesSeeder.php`

### Configuration & Routes
- `packages/sales/config/sales.php` - Sales module configuration
- `packages/sales/routes/api.php` - API route definitions
- `packages/sales/src/SalesServiceProvider.php` - Service provider
- `packages/sales/src/Middleware/ValidateSalesAccess.php` - Access control middleware

### Tests (Total: 103 tasks with testing components)
- `packages/sales/tests/Unit/Models/CustomerTest.php`
- `packages/sales/tests/Unit/Models/SalesQuotationTest.php`
- `packages/sales/tests/Unit/Services/CreditCheckServiceTest.php`
- `packages/sales/tests/Unit/Services/PricingServiceTest.php`
- `packages/sales/tests/Feature/Http/Controllers/CustomerControllerTest.php`
- `packages/sales/tests/Feature/Http/Controllers/QuotationControllerTest.php`
- `packages/sales/tests/Integration/CreditCheckIntegrationTest.php`
- `packages/sales/tests/Performance/CustomerSearchPerformanceTest.php`

### Documentation
- `packages/sales/README.md` - Package readme with installation and usage
- `docs/api/sales-api.md` - Complete API documentation
- `docs/guides/sales-user-guide.md` - End user guide
- `docs/migrations/sales-setup.md` - Migration and setup guide
- `packages/sales/CHANGELOG.md` - Version history

## 6. Testing

### Unit Tests (35 tests)
- **TEST-001**: Customer model relationships and computed attributes
- **TEST-002**: Customer credit limit calculations and status determination
- **TEST-003**: CreditCheckService credit availability logic
- **TEST-004**: CreditCheckService approval requirement logic
- **TEST-005**: SalesQuotation status transitions
- **TEST-006**: SalesQuotation expiry logic
- **TEST-007**: QuotationCalculationService line total calculation
- **TEST-008**: QuotationCalculationService discount application
- **TEST-009**: QuotationCalculationService total calculation with tax
- **TEST-010**: PriceList validity logic
- **TEST-011**: PricingService pricing hierarchy (customer > volume > default)
- **TEST-012**: PricingService volume discount calculation
- **TEST-013**: PricingService cache effectiveness
- **TEST-014**: All action classes: validation, business logic, event dispatching

### Feature Tests (45 tests)
- **TEST-015**: Customer CRUD operations via API
- **TEST-016**: Customer search with various filters
- **TEST-017**: Customer credit status endpoint
- **TEST-018**: Customer authorization enforcement
- **TEST-019**: Cannot delete customer with active orders
- **TEST-020**: Quotation lifecycle (create → send → accept)
- **TEST-021**: Quotation with multiple lines
- **TEST-022**: Cannot update non-draft quotation
- **TEST-023**: Quotation authorization checks
- **TEST-024**: Price list CRUD operations
- **TEST-025**: Add items to price list
- **TEST-026**: Get effective price for customer/item/quantity
- **TEST-027**: Price breakdown response structure
- **TEST-028**: Complete workflow: customer → quotation → acceptance

### Integration Tests (8 tests)
- **TEST-029**: Customer credit check with backoffice integration (mocked)
- **TEST-030**: Pricing integration with inventory items
- **TEST-031**: Quotation conversion to sales order (PLAN02)
- **TEST-032**: Customer with price list and volume discounts

### Performance Tests (2 tests)
- **TEST-033**: Customer search performance with 10,000 customers (< 100ms)
- **TEST-034**: Pricing cache effectiveness test

### Security Tests (5 tests)
- **TEST-035**: Customer-specific pricing access controls
- **TEST-036**: Tenant isolation for customers
- **TEST-037**: Tenant isolation for quotations
- **TEST-038**: Tenant isolation for price lists
- **TEST-039**: Activity logging for all modifications

### Acceptance Tests (5 tests)
- **TEST-040**: Customer master data management complete
- **TEST-041**: Sales quotation creation and management functional
- **TEST-042**: Pricing management with hierarchy working
- **TEST-043**: Credit limit checking operational
- **TEST-044**: All security requirements satisfied

**Total Test Coverage:** 100 tests (35 unit + 45 feature + 8 integration + 2 performance + 5 security + 5 acceptance)

## 7. Risks & Assumptions

### Risks
- **RISK-001**: Customer search performance may degrade with millions of customers - Mitigation: implement database indexes, caching, pagination
- **RISK-002**: Pricing calculations may become complex with multiple discount rules - Mitigation: clear hierarchy defined, comprehensive testing
- **RISK-003**: Credit limit enforcement depends on backoffice module availability - Mitigation: implement fallback logic, allow manual override
- **RISK-004**: Concurrent quotation modifications may cause data inconsistency - Mitigation: implement optimistic locking in PLAN02

### Assumptions
- **ASSUMPTION-001**: Inventory module (SUB14) provides InventoryItem model and is available
- **ASSUMPTION-002**: UOM module (SUB06) provides unit of measure data
- **ASSUMPTION-003**: Customers have single primary billing and shipping address (multi-address in future)
- **ASSUMPTION-004**: Price lists are maintained manually (auto-pricing rules in future)
- **ASSUMPTION-005**: Single currency per customer (multi-currency conversion in future)
- **ASSUMPTION-006**: Credit limits checked at order creation only (real-time monitoring in future)

## 8. KIV for Future Implementations

- **KIV-001**: Multi-currency support with exchange rate conversion
- **KIV-002**: Multiple billing/shipping addresses per customer
- **KIV-003**: Automated pricing rules based on cost-plus or competitor pricing
- **KIV-004**: Customer portal for self-service quotation viewing
- **KIV-005**: Email integration for sending quotations directly from system
- **KIV-006**: PDF generation for quotation printouts
- **KIV-007**: Quotation revision tracking (version history)
- **KIV-008**: Approval workflow for high-value quotations
- **KIV-009**: Integration with CRM for lead-to-customer conversion
- **KIV-010**: Real-time credit monitoring with alerts
- **KIV-011**: Customer segmentation and targeted pricing
- **KIV-012**: Seasonal pricing and promotional campaigns

## 9. Related PRD / Further Reading

- **Master PRD**: [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD**: [../prd/prd-01/PRD01-SUB17-SALES.md](../prd/prd-01/PRD01-SUB17-SALES.md)
- **Related Plans**: 
  - PRD01-SUB17-PLAN02 (Sales Order Management) - Order processing and approval
  - PRD01-SUB17-PLAN03 (Order Fulfillment) - Delivery and shipping
- **Architecture Documentation**: [../../architecture/](../../architecture/)
- **Coding Guidelines**: [../../CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
- **GitHub Copilot Instructions**: [../../.github/copilot-instructions.md](../../.github/copilot-instructions.md)
- **Laravel Actions Documentation**: https://laravelactions.com/
- **Laravel Scout Documentation**: https://laravel.com/docs/scout
