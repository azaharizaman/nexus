---
plan: Implement Accounts Receivable Invoice Management and Customer Balance Tracking
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, accounts-receivable, finance, customer-management, invoice-tracking, gl-integration, sales-integration]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan establishes the Accounts Receivable invoice management system with customer invoice creation, automatic AR entry generation from sales orders, automatic GL posting, and customer balance tracking. It implements the core database schema for AR invoices with line items, creates models with status management, establishes the repository pattern, and integrates with General Ledger for automatic revenue and AR asset posting. This plan delivers multi-line invoice entry, invoice lifecycle management (open/partial/paid/overdue/void), aging analysis, and the foundation for receipt processing in PLAN02.

## 1. Requirements & Constraints

**Functional Requirements:**
- **FR-AR-001**: Support **customer invoice creation** with multi-line items and GL distribution
- **FR-AR-002**: Implement **auto-generate AR entries** from sales orders or delivery notes
- **FR-AR-004**: Provide **aging reports** (30/60/90 days) for outstanding receivables
- **FR-AR-005**: Support **credit memo** processing for customer refunds

**Business Rules:**
- **BR-AR-002**: **Posted invoices** cannot be edited; only reversed or adjusted via credit memo
- **BR-AR-004**: Customer balances MUST equal sum of **unpaid invoices - unapplied receipts**
- **BR-AR-005**: Overdue invoices MUST be flagged automatically based on **due_date vs current_date**

**Data Requirements:**
- **DR-AR-001**: Store AR invoice metadata: invoice_number, customer_id, invoice_date, due_date, total_amount, paid_amount, status

**Integration Requirements:**
- **IR-AR-002**: Integrate with **General Ledger** for automatic AR and revenue posting
- **IR-AR-003**: Integrate with **Customer Master** for customer validation and credit limits

**Performance Requirements:**
- **PR-AR-002**: Generate **aging report** for 10,000+ invoices in under 3 seconds

**Security Requirements:**
- **SR-AR-001**: Enforce **role-based access** for credit memo approval based on amount thresholds

**Scalability Requirements:**
- **SCR-AR-001**: Support **100,000+ invoices** per tenant per year with optimal indexing

**Architecture Requirements:**
- **ARCH-AR-001**: Use **database transactions** to ensure atomicity when applying receipts to invoices

**Events:**
- **EV-AR-001**: Dispatch `ARInvoiceCreatedEvent` when customer invoice is created

**Constraints:**
- **CON-001**: Invoice numbers must be unique per tenant
- **CON-002**: Posted invoices cannot be edited (immutable after posting)
- **CON-003**: Invoice total must equal sum of line items
- **CON-004**: Due date must be >= invoice date
- **CON-005**: Line item GL accounts must be revenue or asset accounts
- **CON-006**: Customer must be active to receive new invoices
- **CON-007**: Cannot exceed customer credit limit unless authorized

**Guidelines:**
- **GUD-001**: Use PSR-12 coding standards, strict types, repository pattern, and Laravel Actions
- **GUD-002**: Log all AR operations using Spatie Activity Log
- **GUD-003**: Use decimal precision (DECIMAL 20,4) for all financial amounts
- **GUD-004**: Calculate aging buckets dynamically based on current date vs due date
- **GUD-005**: Cache customer balances for 5 minutes to improve performance
- **GUD-006**: Auto-flag overdue invoices daily via scheduled command

**Patterns:**
- **PAT-001**: Repository pattern with ARInvoiceRepositoryContract
- **PAT-002**: Laravel Actions for CreateARInvoiceAction, PostARInvoiceAction, CreateARFromSalesOrderAction
- **PAT-003**: Observer pattern for automatic GL posting on invoice save
- **PAT-004**: Strategy pattern for different invoice types (standard, credit memo, debit memo)

## 2. Implementation Steps

### GOAL-001: Create Database Schema for Accounts Receivable System

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-AR-001, DR-AR-001, BR-AR-002, SCR-AR-001 | Implement ar_invoices, ar_invoice_lines, and customer_credit_limits tables with proper constraints, indexes, and audit fields | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `database/migrations/YYYY_MM_DD_HHMMSS_create_customers_table.php` with namespace. Add `declare(strict_types=1);`. Use anonymous migration class format: `return new class extends Migration` | | |
| TASK-002 | In up() method, create `customers` table with columns: `id` (BIGINT AUTO_INCREMENT PRIMARY KEY), `tenant_id` (UUID NOT NULL), `customer_code` (VARCHAR 50 NOT NULL), `customer_name` (VARCHAR 255 NOT NULL), `contact_person` (VARCHAR 255 NULL), `email` (VARCHAR 255 NULL), `phone` (VARCHAR 50 NULL), `billing_address` (TEXT NULL), `shipping_address` (TEXT NULL), `payment_terms` (VARCHAR 50 NULL - "Net 30", "Net 60", "COD"), `payment_terms_days` (INTEGER DEFAULT 30), `credit_limit` (DECIMAL 20,4 NULL), `currency_code` (VARCHAR 3 NOT NULL DEFAULT 'USD'), `tax_id` (VARCHAR 50 NULL), `is_active` (BOOLEAN DEFAULT TRUE), `gl_account_id` (BIGINT NOT NULL - default AR asset account), `metadata` (JSON NULL), timestamps, soft deletes | | |
| TASK-003 | Add indexes on customers: `INDEX idx_customers_tenant (tenant_id)`, `UNIQUE KEY uk_customers_code (tenant_id, customer_code)`, `INDEX idx_customers_active (is_active)`, `INDEX idx_customers_name (customer_name)` for search | | |
| TASK-004 | Add foreign keys on customers: `FOREIGN KEY fk_customers_tenant (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE`, `FOREIGN KEY fk_customers_gl (gl_account_id) REFERENCES accounts(id) ON DELETE RESTRICT` (prevent deletion of AR account) | | |
| TASK-005 | Create `ar_invoices` table with columns: `id` (BIGINT AUTO_INCREMENT PRIMARY KEY), `tenant_id` (UUID NOT NULL), `invoice_number` (VARCHAR 50 NOT NULL), `customer_id` (BIGINT NOT NULL), `sales_order_id` (BIGINT NULL - link to sales order), `invoice_date` (DATE NOT NULL), `due_date` (DATE NOT NULL), `reference` (VARCHAR 100 NULL - PO number or reference), `description` (TEXT NULL), `subtotal_amount` (DECIMAL 20,4 NOT NULL DEFAULT 0), `tax_amount` (DECIMAL 20,4 NOT NULL DEFAULT 0), `total_amount` (DECIMAL 20,4 NOT NULL), `paid_amount` (DECIMAL 20,4 NOT NULL DEFAULT 0), `status` (VARCHAR 20 NOT NULL DEFAULT 'draft' - draft/posted/partial/paid/overdue/void), `posted_at` (TIMESTAMP NULL), `posted_by` (BIGINT NULL), `gl_entry_id` (BIGINT NULL - link to GL entry), `notes` (TEXT NULL), timestamps, soft deletes | | |
| TASK-006 | Add indexes on ar_invoices: `INDEX idx_ar_inv_tenant (tenant_id)`, `UNIQUE KEY uk_ar_inv_number (tenant_id, invoice_number)`, `INDEX idx_ar_inv_customer (customer_id)`, `INDEX idx_ar_inv_status (status)`, `INDEX idx_ar_inv_date (invoice_date)`, `INDEX idx_ar_inv_due (due_date)` for aging reports, `INDEX idx_ar_inv_sales_order (sales_order_id)`, `INDEX idx_ar_inv_gl (gl_entry_id)` | | |
| TASK-007 | Add foreign keys on ar_invoices: `FOREIGN KEY fk_ar_inv_tenant (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE`, `FOREIGN KEY fk_ar_inv_customer (customer_id) REFERENCES customers(id) ON DELETE RESTRICT`, `FOREIGN KEY fk_ar_inv_sales_order (sales_order_id) REFERENCES sales_orders(id) ON DELETE SET NULL`, `FOREIGN KEY fk_ar_inv_posted_by (posted_by) REFERENCES users(id)`, `FOREIGN KEY fk_ar_inv_gl (gl_entry_id) REFERENCES gl_entries(id) ON DELETE SET NULL` | | |
| TASK-008 | Create `ar_invoice_lines` table with columns: `id` (BIGINT AUTO_INCREMENT PRIMARY KEY), `ar_invoice_id` (BIGINT NOT NULL), `line_number` (INTEGER NOT NULL), `product_id` (BIGINT NULL - optional product link), `description` (TEXT NOT NULL), `gl_account_id` (BIGINT NOT NULL - revenue account), `quantity` (DECIMAL 15,4 NOT NULL DEFAULT 1), `unit_price` (DECIMAL 20,4 NOT NULL), `amount` (DECIMAL 20,4 NOT NULL - quantity * unit_price), `tax_code` (VARCHAR 20 NULL), `tax_amount` (DECIMAL 20,4 NOT NULL DEFAULT 0), `cost_center` (VARCHAR 50 NULL - optional allocation), `project_id` (BIGINT NULL - optional project tracking), timestamps | | |
| TASK-009 | Add indexes on ar_invoice_lines: `INDEX idx_ar_lines_invoice (ar_invoice_id)`, `INDEX idx_ar_lines_gl (gl_account_id)`, `INDEX idx_ar_lines_product (product_id)`, `INDEX idx_ar_lines_project (project_id)` | | |
| TASK-010 | Add foreign keys on ar_invoice_lines: `FOREIGN KEY fk_ar_lines_invoice (ar_invoice_id) REFERENCES ar_invoices(id) ON DELETE CASCADE`, `FOREIGN KEY fk_ar_lines_gl (gl_account_id) REFERENCES accounts(id) ON DELETE RESTRICT`, `FOREIGN KEY fk_ar_lines_product (product_id) REFERENCES products(id) ON DELETE SET NULL` | | |
| TASK-011 | Create `customer_credit_limits` table with columns: `id` (BIGINT AUTO_INCREMENT PRIMARY KEY), `tenant_id` (UUID NOT NULL), `customer_id` (BIGINT NOT NULL), `credit_limit` (DECIMAL 20,4 NOT NULL), `current_balance` (DECIMAL 20,4 NOT NULL DEFAULT 0), `available_credit` (DECIMAL 20,4 GENERATED ALWAYS AS (credit_limit - current_balance) STORED), `payment_terms_days` (INTEGER NOT NULL DEFAULT 30), `is_on_hold` (BOOLEAN NOT NULL DEFAULT FALSE), `hold_reason` (TEXT NULL), timestamps | | |
| TASK-012 | Add indexes and constraints on customer_credit_limits: `UNIQUE KEY uk_credit_customer (tenant_id, customer_id)`, `INDEX idx_credit_tenant (tenant_id)`, `INDEX idx_credit_hold (is_on_hold)` | | |
| TASK-013 | In down() method, drop tables in reverse order: `Schema::dropIfExists('customer_credit_limits')`, then ar_invoice_lines, then ar_invoices, then customers | | |

### GOAL-002: Create Customer and AR Invoice Models with Status Management

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-AR-001, BR-AR-002, BR-AR-005, CON-002, CON-003 | Implement Customer and ARInvoice Eloquent models with status enums, immutability enforcement, balance calculations, and overdue flagging | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-014 | Create `app/Domains/AccountsReceivable/Models/Customer.php` with namespace. Add `declare(strict_types=1);`. Import traits: `use BelongsToTenant, HasFactory, SoftDeletes, LogsActivity;` | | |
| TASK-015 | Define $fillable array: `['tenant_id', 'customer_code', 'customer_name', 'contact_person', 'email', 'phone', 'billing_address', 'shipping_address', 'payment_terms', 'payment_terms_days', 'credit_limit', 'currency_code', 'tax_id', 'is_active', 'gl_account_id', 'metadata']` | | |
| TASK-016 | Define $casts array: `['is_active' => 'boolean', 'credit_limit' => 'decimal:4', 'payment_terms_days' => 'integer', 'metadata' => 'array', 'deleted_at' => 'datetime']` | | |
| TASK-017 | Implement `getActivitylogOptions(): LogOptions` in Customer: `return LogOptions::defaults()->logOnly(['customer_code', 'customer_name', 'email', 'payment_terms', 'credit_limit', 'is_active'])->logOnlyDirty()->dontSubmitEmptyLogs();` | | |
| TASK-018 | Add relationships in Customer: `invoices()` hasMany ARInvoice, `receipts()` hasMany ARReceipt, `creditLimit()` hasOne CustomerCreditLimit with withDefault(), `glAccount()` belongsTo Account (gl_account_id) | | |
| TASK-019 | Implement `getCurrentBalanceAttribute(): float` computed attribute: `return $this->invoices()->whereIn('status', [ARInvoiceStatus::POSTED, ARInvoiceStatus::PARTIAL, ARInvoiceStatus::OVERDUE])->sum('total_amount') - $this->invoices()->sum('paid_amount');` (BR-AR-004) | | |
| TASK-020 | Implement `getAvailableCreditAttribute(): float` computed attribute: `if (!$this->credit_limit) return INF; return max(0, $this->credit_limit - $this->current_balance);` | | |
| TASK-021 | Create `app/Domains/AccountsReceivable/Models/ARInvoice.php` with namespace. Add `declare(strict_types=1);`. Import traits: `use BelongsToTenant, HasFactory, SoftDeletes, LogsActivity;` | | |
| TASK-022 | Define $fillable: `['tenant_id', 'invoice_number', 'customer_id', 'sales_order_id', 'invoice_date', 'due_date', 'reference', 'description', 'subtotal_amount', 'tax_amount', 'total_amount', 'paid_amount', 'status', 'posted_at', 'posted_by', 'gl_entry_id', 'notes']` | | |
| TASK-023 | Define $casts: `['invoice_date' => 'date', 'due_date' => 'date', 'subtotal_amount' => 'decimal:4', 'tax_amount' => 'decimal:4', 'total_amount' => 'decimal:4', 'paid_amount' => 'decimal:4', 'status' => ARInvoiceStatus::class, 'posted_at' => 'datetime', 'deleted_at' => 'datetime']` | | |
| TASK-024 | Create `app/Domains/AccountsReceivable/Enums/ARInvoiceStatus.php` as string-backed enum with cases: `DRAFT = 'draft'`, `POSTED = 'posted'`, `PARTIAL = 'partial'`, `PAID = 'paid'`, `OVERDUE = 'overdue'`, `VOID = 'void'`. Implement `label(): string`, `isPayable(): bool` (POSTED or PARTIAL or OVERDUE), `isFullyPaid(): bool` (PAID), `canBeEdited(): bool` (DRAFT only) | | |
| TASK-025 | Implement `getActivitylogOptions(): LogOptions` in ARInvoice: `return LogOptions::defaults()->logOnly(['invoice_number', 'customer_id', 'total_amount', 'paid_amount', 'status', 'posted_at'])->logOnlyDirty()->dontSubmitEmptyLogs();` | | |
| TASK-026 | Add relationships in ARInvoice: `customer()` belongsTo Customer, `salesOrder()` belongsTo SalesOrder with withDefault(), `lines()` hasMany ARInvoiceLine, `receipts()` belongsToMany ARReceipt through ar_receipt_applications, `glEntry()` belongsTo GLEntry with withDefault(), `poster()` belongsTo User (posted_by) with withDefault() | | |
| TASK-027 | Add scopes: `scopeDraft(Builder $query): Builder`, `scopePosted(Builder $query): Builder`, `scopeOverdue(Builder $query): Builder` returning `$query->where('status', ARInvoiceStatus::OVERDUE)`, `scopeByCustomer(Builder $query, int $customerId): Builder`, `scopeByDateRange(Builder $query, Carbon $from, Carbon $to): Builder` | | |
| TASK-028 | Implement `getOutstandingAmountAttribute(): float` computed attribute: `return max(0, bcsub((string)$this->total_amount, (string)$this->paid_amount, 4));` | | |
| TASK-029 | Implement `getDaysOverdueAttribute(): int` computed attribute: `if ($this->status !== ARInvoiceStatus::OVERDUE) return 0; return max(0, now()->diffInDays($this->due_date, false));` (BR-AR-005) | | |
| TASK-030 | Implement `isFullyPaid(): bool` method: `return bccomp((string)$this->paid_amount, (string)$this->total_amount, 4) === 0;` | | |
| TASK-031 | Implement `isOverdue(): bool` method: `if (in_array($this->status, [ARInvoiceStatus::PAID, ARInvoiceStatus::VOID])) return false; return $this->due_date->isPast() && !$this->isFullyPaid();` (BR-AR-005) | | |
| TASK-032 | Implement static boot to enforce immutability (BR-AR-002): `static::updating(function ($invoice) { if ($invoice->status !== ARInvoiceStatus::DRAFT && $invoice->isDirty(['customer_id', 'invoice_date', 'total_amount', 'lines'])) { throw new ImmutableInvoiceException('Posted invoices cannot be edited. Use credit memo to adjust.'); } });` | | |
| TASK-033 | Create `app/Domains/AccountsReceivable/Models/ARInvoiceLine.php` with namespace. Add `declare(strict_types=1);`. Use `HasFactory` trait | | |
| TASK-034 | Define $fillable: `['ar_invoice_id', 'line_number', 'product_id', 'description', 'gl_account_id', 'quantity', 'unit_price', 'amount', 'tax_code', 'tax_amount', 'cost_center', 'project_id']` | | |
| TASK-035 | Define $casts: `['quantity' => 'decimal:4', 'unit_price' => 'decimal:4', 'amount' => 'decimal:4', 'tax_amount' => 'decimal:4']` | | |
| TASK-036 | Add relationships in ARInvoiceLine: `invoice()` belongsTo ARInvoice, `product()` belongsTo Product with withDefault(), `glAccount()` belongsTo Account (gl_account_id) | | |
| TASK-037 | Implement static boot to calculate amount: `static::saving(function ($line) { $line->amount = bcmul((string)$line->quantity, (string)$line->unit_price, 4); });` | | |
| TASK-038 | Create `app/Domains/AccountsReceivable/Models/CustomerCreditLimit.php` with namespace. Define relationships: `customer()` belongsTo Customer. Add computed attributes: `getAvailableCreditAttribute()`, `isOverLimitAttribute()` | | |

### GOAL-003: Implement AR Invoice Repository and Aging Service

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| PAT-001, PR-AR-002, BR-AR-004, GUD-004 | Create repository contracts and AR aging service with optimized queries for balance calculation and aging bucket analysis | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-039 | Create `app/Domains/AccountsReceivable/Contracts/ARInvoiceRepositoryContract.php` with namespace. Add `declare(strict_types=1);`. Define interface methods: `findById(int $id): ?ARInvoice`, `findByNumber(string $number, ?string $tenantId = null): ?ARInvoice`, `getOverdueInvoices(?string $tenantId = null): Collection`, `getByCustomer(int $customerId): Collection`, `getByDateRange(Carbon $from, Carbon $to, ?string $tenantId = null): Collection`, `getAgingReport(?string $tenantId = null): array`, `paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator`, `create(array $data): ARInvoice`, `update(ARInvoice $invoice, array $data): ARInvoice` | | |
| TASK-040 | Create `app/Domains/AccountsReceivable/Repositories/DatabaseARInvoiceRepository.php` implementing ARInvoiceRepositoryContract. Add `declare(strict_types=1);`. Constructor has no dependencies | | |
| TASK-041 | Implement `getOverdueInvoices()` with eager loading: `return ARInvoice::with(['customer', 'lines'])->overdue()->where('tenant_id', $tenantId ?? tenant_id())->orderBy('due_date')->get();`. For collections tracking | | |
| TASK-042 | Implement `getAgingReport()` with optimized query (PR-AR-002): Calculate aging buckets using DB query: `SELECT CASE WHEN DATEDIFF(CURRENT_DATE, due_date) <= 0 THEN 'current' WHEN DATEDIFF(CURRENT_DATE, due_date) BETWEEN 1 AND 30 THEN '1-30' ... END as bucket, SUM(total_amount - paid_amount) as outstanding FROM ar_invoices WHERE status IN ('posted', 'partial', 'overdue') GROUP BY bucket`. Return array with keys: `['current', '1_30', '31_60', '61_90', 'over_90', 'total']` (GUD-004) | | |
| TASK-043 | Implement `paginate()` with filters: Support filters: `status` (string), `customer_id` (int), `overdue` (bool), `from_date`, `to_date`, `search` (invoice_number/reference). Build query with conditional filters and eager load relationships | | |
| TASK-044 | Create `app/Domains/AccountsReceivable/Contracts/CustomerRepositoryContract.php` with methods: `findById(int $id): ?Customer`, `findByCode(string $code, ?string $tenantId = null): ?Customer`, `getCustomerBalance(int $customerId): float`, `checkCreditLimit(int $customerId, float $amount): bool`, `paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator`, `create(array $data): Customer`, `update(Customer $customer, array $data): Customer` | | |
| TASK-045 | Create `app/Domains/AccountsReceivable/Repositories/DatabaseCustomerRepository.php` implementing CustomerRepositoryContract. Implement all methods with proper eager loading | | |
| TASK-046 | Implement `getCustomerBalance()` with cached result (GUD-005): `return Cache::remember("customer_balance:{$customerId}", 300, function() use ($customerId) { return ARInvoice::where('customer_id', $customerId)->whereIn('status', [ARInvoiceStatus::POSTED, ARInvoiceStatus::PARTIAL, ARInvoiceStatus::OVERDUE])->sum(DB::raw('total_amount - paid_amount')); });`. 5 minute cache | | |
| TASK-047 | Implement `checkCreditLimit()`: `$customer = $this->findById($customerId); if (!$customer->credit_limit) return true; $currentBalance = $this->getCustomerBalance($customerId); return ($currentBalance + $amount) <= $customer->credit_limit;` (CON-007) | | |
| TASK-048 | Create `app/Domains/AccountsReceivable/Services/ARReportService.php` with constructor: `public function __construct(private readonly ARInvoiceRepositoryContract $invoiceRepo) {}` | | |
| TASK-049 | Implement `generateAgingReport(?int $customerId = null): array` method: Call `$this->invoiceRepo->getAgingReport()` and format result with aging bucket labels, amounts, and percentages. If $customerId provided, filter by customer. Return structured array with buckets and totals | | |
| TASK-050 | Implement `generateCustomerStatement(int $customerId, Carbon $from, Carbon $to): array` method: Retrieve all invoices and receipts for customer in date range. Calculate running balance showing: opening balance, invoices (charges), receipts (payments), closing balance. Return array with transaction history and balance summary | | |
| TASK-051 | Bind contracts in AppServiceProvider: `$this->app->bind(ARInvoiceRepositoryContract::class, DatabaseARInvoiceRepository::class); $this->app->bind(CustomerRepositoryContract::class, DatabaseCustomerRepository::class);` | | |

### GOAL-004: Implement Invoice Creation Actions with GL Integration

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-AR-001, FR-AR-002, IR-AR-002, CON-003, CON-005, PAT-002 | Create actions for manual invoice creation and auto-generation from sales orders with GL posting to debit AR asset and credit revenue accounts | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-052 | Create `app/Domains/AccountsReceivable/Actions/CreateARInvoiceAction.php` with namespace. Add `declare(strict_types=1);`. Use AsAction trait. Constructor: `public function __construct(private readonly ARInvoiceRepositoryContract $invoiceRepo, private readonly CustomerRepositoryContract $customerRepo, private readonly ActivityLoggerContract $activityLogger) {}` | | |
| TASK-053 | Implement `handle(array $data): ARInvoice` method in CreateARInvoiceAction. Step 1: Validate customer exists and active (CON-006): `$customer = $this->customerRepo->findById($data['customer_id']); if (!$customer || !$customer->is_active) { throw new InactiveCustomerException('Customer is not active'); }` | | |
| TASK-054 | Step 2: Validate invoice number unique: `if ($this->invoiceRepo->findByNumber($data['invoice_number'], tenant_id())) { throw new DuplicateInvoiceNumberException("Invoice number {$data['invoice_number']} already exists"); }` (CON-001) | | |
| TASK-055 | Step 3: Validate due date >= invoice date (CON-004): `if (Carbon::parse($data['due_date'])->lt(Carbon::parse($data['invoice_date']))) { throw new InvalidDueDateException('Due date must be >= invoice date'); }` | | |
| TASK-056 | Step 4: Validate line items GL accounts (CON-005): `foreach ($data['lines'] as $line) { $account = Account::find($line['gl_account_id']); if (!$account || !in_array($account->account_type, ['revenue', 'asset'])) { throw new InvalidGLAccountException("Account {$line['gl_account_id']} must be revenue or asset"); } }` | | |
| TASK-057 | Step 5: Calculate totals and validate (CON-003): `$calculatedSubtotal = array_reduce($data['lines'], fn($sum, $line) => bcadd($sum, bcmul((string)$line['quantity'], (string)$line['unit_price'], 4), 4), '0'); $calculatedTotal = bcadd($calculatedSubtotal, (string)($data['tax_amount'] ?? 0), 4); if (bccomp($calculatedTotal, (string)$data['total_amount'], 4) !== 0) { throw new InvoiceTotalMismatchException("Calculated total {$calculatedTotal} does not match provided {$data['total_amount']}"); }` | | |
| TASK-058 | Step 6: Check credit limit (CON-007): `if (!$this->customerRepo->checkCreditLimit($data['customer_id'], $data['total_amount'])) { if (!($data['override_credit_limit'] ?? false)) { throw new CreditLimitExceededException('Customer credit limit exceeded'); } $this->activityLogger->log('Credit limit overridden for invoice creation', $customer, auth()->user()); }` | | |
| TASK-059 | Step 7: Create invoice with lines in transaction: `DB::transaction(function() use ($data) { $invoiceData = Arr::except($data, ['lines']); $invoiceData['tenant_id'] = tenant_id(); $invoiceData['status'] = ARInvoiceStatus::DRAFT; $invoice = $this->invoiceRepo->create($invoiceData); foreach ($data['lines'] as $lineData) { $invoice->lines()->create($lineData); } $this->activityLogger->log("AR Invoice created: {$invoice->invoice_number}", $invoice, auth()->user()); event(new ARInvoiceCreatedEvent($invoice)); return $invoice->fresh(['lines', 'customer']); });` | | |
| TASK-060 | Create `app/Domains/AccountsReceivable/Actions/CreateARFromSalesOrderAction.php` with AsAction trait (FR-AR-002). Constructor: `public function __construct(private readonly ARInvoiceRepositoryContract $invoiceRepo, private readonly ActivityLoggerContract $activityLogger) {}` | | |
| TASK-061 | Implement `handle(SalesOrder $salesOrder): ARInvoice` in CreateARFromSalesOrderAction. Validate sales order status: `if ($salesOrder->status !== SalesOrderStatus::APPROVED && $salesOrder->status !== SalesOrderStatus::SHIPPED) { throw new InvalidSalesOrderStatusException('Sales order must be approved or shipped'); }` | | |
| TASK-062 | Build invoice data from sales order: `$invoiceData = ['invoice_number' => $this->generateInvoiceNumber(), 'customer_id' => $salesOrder->customer_id, 'sales_order_id' => $salesOrder->id, 'invoice_date' => now()->toDateString(), 'due_date' => now()->addDays($salesOrder->customer->payment_terms_days)->toDateString(), 'reference' => $salesOrder->order_number, 'subtotal_amount' => $salesOrder->subtotal_amount, 'tax_amount' => $salesOrder->tax_amount, 'total_amount' => $salesOrder->total_amount, 'lines' => $salesOrder->lines->map(fn($line) => ['line_number' => $line->line_number, 'product_id' => $line->product_id, 'description' => $line->description, 'gl_account_id' => $line->product->revenue_account_id, 'quantity' => $line->quantity, 'unit_price' => $line->unit_price, 'tax_code' => $line->tax_code])->toArray()];` | | |
| TASK-063 | Create invoice using CreateARInvoiceAction: `$invoice = CreateARInvoiceAction::run($invoiceData); $this->activityLogger->log("AR Invoice auto-generated from sales order {$salesOrder->order_number}", $invoice, auth()->user()); return $invoice;` | | |
| TASK-064 | Create `app/Domains/AccountsReceivable/Actions/PostARInvoiceAction.php` with AsAction trait. Constructor: `public function __construct(private readonly ARInvoiceRepositoryContract $invoiceRepo, private readonly GLEntryService $glService, private readonly ActivityLoggerContract $activityLogger) {}` | | |
| TASK-065 | Implement `handle(ARInvoice $invoice): ARInvoice` in PostARInvoiceAction. Validate invoice is draft: `if ($invoice->status !== ARInvoiceStatus::DRAFT) { throw new InvalidStatusException('Only draft invoices can be posted'); }` | | |
| TASK-066 | Create GL entry in transaction (IR-AR-002): `DB::transaction(function() use ($invoice) { $glEntryLines = []; foreach ($invoice->lines as $line) { $glEntryLines[] = ['account_id' => $line->gl_account_id, 'debit_amount' => 0, 'credit_amount' => $line->amount, 'description' => "AR Invoice {$invoice->invoice_number} - Line {$line->line_number}"]; } $glEntryLines[] = ['account_id' => $invoice->customer->gl_account_id, 'debit_amount' => $invoice->total_amount, 'credit_amount' => 0, 'description' => "AR Invoice {$invoice->invoice_number}"]; $glEntry = $this->glService->createEntry(['entry_date' => $invoice->invoice_date, 'description' => "AR Invoice {$invoice->invoice_number} - {$invoice->customer->customer_name}", 'reference' => $invoice->invoice_number, 'lines' => $glEntryLines]); $invoice->update(['status' => ARInvoiceStatus::POSTED, 'posted_at' => now(), 'posted_by' => auth()->id(), 'gl_entry_id' => $glEntry->id]); $this->activityLogger->log("AR Invoice posted", $invoice, auth()->user()); return $invoice->fresh(['glEntry']); });`. Debit AR asset, credit revenue | | |
| TASK-067 | Create events: `app/Domains/AccountsReceivable/Events/ARInvoiceCreatedEvent.php` with properties: `public readonly ARInvoice $invoice`. Similarly create `ARInvoicePostedEvent.php` | | |
| TASK-068 | Create exceptions: `app/Domains/AccountsReceivable/Exceptions/InactiveCustomerException.php`, `DuplicateInvoiceNumberException.php`, `InvalidDueDateException.php`, `InvalidGLAccountException.php`, `InvoiceTotalMismatchException.php`, `CreditLimitExceededException.php`, `InvalidSalesOrderStatusException.php`, `ImmutableInvoiceException.php`, `InvalidStatusException.php` | | |

### GOAL-005: Implement Scheduled Command for Overdue Invoice Flagging

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| BR-AR-005, GUD-006 | Create scheduled console command to automatically flag overdue invoices daily and dispatch notifications | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-069 | Create `app/Console/Commands/FlagOverdueInvoicesCommand.php` with namespace. Add `declare(strict_types=1);`. Extend Command class. Set protected properties: `$signature = 'ar:flag-overdue-invoices'`, `$description = 'Flag overdue AR invoices and dispatch notifications'` | | |
| TASK-070 | In handle() method, query posted and partial invoices past due date: `$overdueInvoices = ARInvoice::whereIn('status', [ARInvoiceStatus::POSTED, ARInvoiceStatus::PARTIAL])->where('due_date', '<', now()->toDateString())->whereColumn('paid_amount', '<', 'total_amount')->get();` | | |
| TASK-071 | Update each overdue invoice status: `DB::transaction(function() use ($overdueInvoices) { $count = 0; foreach ($overdueInvoices as $invoice) { if ($invoice->status !== ARInvoiceStatus::OVERDUE) { $invoice->update(['status' => ARInvoiceStatus::OVERDUE]); event(new InvoiceOverdueEvent($invoice)); $count++; } } $this->info("Flagged {$count} invoices as overdue"); activity()->log("Flagged {$count} invoices as overdue via scheduled command"); });` | | |
| TASK-072 | Register command in Kernel with daily schedule: In `app/Console/Kernel.php`, add to schedule() method: `$schedule->command('ar:flag-overdue-invoices')->daily()->at('01:00');`. Runs daily at 1 AM | | |
| TASK-073 | Create `app/Domains/AccountsReceivable/Events/InvoiceOverdueEvent.php` with properties: `public readonly ARInvoice $invoice`, `public readonly int $daysOverdue` | | |
| TASK-074 | Create listener `app/Domains/AccountsReceivable/Listeners/SendOverdueNotificationListener.php` to send notification to customer: `public function handle(InvoiceOverdueEvent $event): void { Notification::send($event->invoice->customer, new InvoiceOverdueNotification($event->invoice, $event->daysOverdue)); }` | | |

## 3. Alternatives

- **ALT-001**: Store aging buckets as database columns updated by trigger - **Rejected** because calculated dynamically is more flexible and accurate
- **ALT-002**: Allow posting invoices with zero amount - **Rejected** because violates business logic (no value transaction)
- **ALT-003**: Auto-post invoices immediately on creation - **Rejected** because draft review required before GL impact
- **ALT-004**: Store customer balance as denormalized field - **Deferred** to performance optimization phase, currently calculated with 5-min cache
- **ALT-005**: Hard delete invoices instead of soft delete - **Rejected** because audit trail and referential integrity required

## 4. Dependencies

**Package Dependencies:**
- **DEP-001**: `laravel/framework` ^12.0
- **DEP-002**: `spatie/laravel-activitylog` ^4.0 (audit logging)
- **DEP-003**: `lorisleiva/laravel-actions` ^2.0 (action pattern)
- **DEP-004**: `brick/math` ^0.12 (decimal precision)

**Internal Dependencies:**
- **DEP-011**: PRD01-SUB07 (Chart of Accounts) - GL account validation
- **DEP-012**: PRD01-SUB08 (General Ledger) - GL entry creation
- **DEP-013**: PRD01-SUB17 (Sales) - Sales order integration
- **DEP-014**: PRD01-SUB01 (Multi-Tenancy) - Tenant isolation

**Infrastructure:**
- **DEP-015**: Cache driver (Redis/Memcached) for customer balance caching
- **DEP-016**: Scheduler (cron) for daily overdue invoice flagging

## 5. Files

**Migrations:**
- `database/migrations/YYYY_MM_DD_HHMMSS_create_customers_table.php` - Customer master
- `database/migrations/YYYY_MM_DD_HHMMSS_create_ar_invoices_table.php` - AR invoices
- `database/migrations/YYYY_MM_DD_HHMMSS_create_ar_invoice_lines_table.php` - Invoice lines
- `database/migrations/YYYY_MM_DD_HHMMSS_create_customer_credit_limits_table.php` - Credit limits

**Models:**
- `app/Domains/AccountsReceivable/Models/Customer.php` - Customer master
- `app/Domains/AccountsReceivable/Models/ARInvoice.php` - AR invoice header
- `app/Domains/AccountsReceivable/Models/ARInvoiceLine.php` - Invoice line items
- `app/Domains/AccountsReceivable/Models/CustomerCreditLimit.php` - Credit limits

**Enums:**
- `app/Domains/AccountsReceivable/Enums/ARInvoiceStatus.php` - Invoice lifecycle

**Contracts:**
- `app/Domains/AccountsReceivable/Contracts/ARInvoiceRepositoryContract.php` - Invoice repository
- `app/Domains/AccountsReceivable/Contracts/CustomerRepositoryContract.php` - Customer repository

**Repositories:**
- `app/Domains/AccountsReceivable/Repositories/DatabaseARInvoiceRepository.php` - Invoice repo
- `app/Domains/AccountsReceivable/Repositories/DatabaseCustomerRepository.php` - Customer repo

**Services:**
- `app/Domains/AccountsReceivable/Services/ARReportService.php` - Aging and statements

**Actions:**
- `app/Domains/AccountsReceivable/Actions/CreateARInvoiceAction.php` - Manual invoice creation
- `app/Domains/AccountsReceivable/Actions/CreateARFromSalesOrderAction.php` - Auto-generation
- `app/Domains/AccountsReceivable/Actions/PostARInvoiceAction.php` - Post to GL

**Events:**
- `app/Domains/AccountsReceivable/Events/ARInvoiceCreatedEvent.php` - Invoice created
- `app/Domains/AccountsReceivable/Events/ARInvoicePostedEvent.php` - Invoice posted
- `app/Domains/AccountsReceivable/Events/InvoiceOverdueEvent.php` - Invoice overdue

**Listeners:**
- `app/Domains/AccountsReceivable/Listeners/SendOverdueNotificationListener.php` - Overdue notification

**Console Commands:**
- `app/Console/Commands/FlagOverdueInvoicesCommand.php` - Daily overdue flagging

**Exceptions:**
- `app/Domains/AccountsReceivable/Exceptions/InactiveCustomerException.php` - Inactive customer
- `app/Domains/AccountsReceivable/Exceptions/DuplicateInvoiceNumberException.php` - Duplicate number
- `app/Domains/AccountsReceivable/Exceptions/InvalidDueDateException.php` - Invalid due date
- `app/Domains/AccountsReceivable/Exceptions/InvalidGLAccountException.php` - Invalid GL account
- `app/Domains/AccountsReceivable/Exceptions/InvoiceTotalMismatchException.php` - Total mismatch
- `app/Domains/AccountsReceivable/Exceptions/CreditLimitExceededException.php` - Credit exceeded
- `app/Domains/AccountsReceivable/Exceptions/InvalidSalesOrderStatusException.php` - Invalid SO status
- `app/Domains/AccountsReceivable/Exceptions/ImmutableInvoiceException.php` - Immutable invoice
- `app/Domains/AccountsReceivable/Exceptions/InvalidStatusException.php` - Invalid status

**Service Provider (updated):**
- `app/Providers/AppServiceProvider.php` - Repository bindings

## 6. Testing

**Unit Tests (20 tests):**
- **TEST-001**: `test_invoice_status_enum_has_all_cases` - Verify 6 status cases
- **TEST-002**: `test_invoice_calculates_outstanding_amount` - Test getOutstandingAmountAttribute()
- **TEST-003**: `test_invoice_determines_overdue_status` - Test isOverdue()
- **TEST-004**: `test_invoice_validates_fully_paid` - Test isFullyPaid()
- **TEST-005**: `test_customer_calculates_current_balance` - Test getCurrentBalanceAttribute()
- **TEST-006**: `test_customer_calculates_available_credit` - Test getAvailableCreditAttribute()
- **TEST-007**: `test_invoice_line_calculates_amount` - Test auto-calculation
- **TEST-008**: `test_invoice_prevents_editing_when_posted` - Test immutability (BR-AR-002)
- **TEST-009**: `test_repository_gets_overdue_invoices` - Test getOverdueInvoices()
- **TEST-010**: `test_repository_filters_by_customer` - Test getByCustomer()
- **TEST-011**: `test_aging_report_calculates_buckets_correctly` - Test GUD-004
- **TEST-012**: `test_customer_balance_cached_for_5_minutes` - Test GUD-005
- **TEST-013**: `test_credit_limit_check_works` - Test checkCreditLimit()
- **TEST-014**: `test_invoice_factory_generates_valid_data` - Test factory
- **TEST-015**: `test_invoice_scope_overdue_works` - Test scopeOverdue()
- **TEST-016**: `test_days_overdue_calculated_correctly` - Test getDaysOverdueAttribute()
- **TEST-017**: `test_customer_statement_running_balance` - Test statement calculation
- **TEST-018**: `test_invoice_number_uniqueness_within_tenant` - Test CON-001
- **TEST-019**: `test_due_date_validation` - Test CON-004
- **TEST-020**: `test_invoice_total_validation` - Test CON-003

**Feature Tests (18 tests):**
- **TEST-021**: `test_create_invoice_action_validates_customer` - Test customer validation
- **TEST-022**: `test_create_invoice_validates_gl_accounts` - Test CON-005
- **TEST-023**: `test_create_invoice_checks_credit_limit` - Test CON-007
- **TEST-024**: `test_create_invoice_creates_with_lines` - Test multi-line creation
- **TEST-025**: `test_post_invoice_creates_gl_entry` - Test IR-AR-002
- **TEST-026**: `test_post_invoice_validates_draft_status` - Test status check
- **TEST-027**: `test_auto_generate_from_sales_order` - Test FR-AR-002
- **TEST-028**: `test_cannot_edit_posted_invoice` - Test BR-AR-002
- **TEST-029**: `test_activity_log_records_invoice_operations` - Test LogsActivity
- **TEST-030**: `test_invoice_dispatches_created_event` - Test ARInvoiceCreatedEvent
- **TEST-031**: `test_scheduled_command_flags_overdue_invoices` - Test BR-AR-005
- **TEST-032**: `test_overdue_event_dispatched` - Test InvoiceOverdueEvent
- **TEST-033**: `test_aging_report_generation` - Test FR-AR-004
- **TEST-034**: `test_customer_statement_generation` - Test statement report
- **TEST-035**: `test_credit_limit_override_logged` - Test audit trail
- **TEST-036**: `test_invoice_line_immutability` - Test line editing restrictions
- **TEST-037**: `test_soft_delete_preserves_invoice` - Test soft deletes
- **TEST-038**: `test_cache_invalidation_on_payment` - Test cache clearing

**Integration Tests (8 tests):**
- **TEST-039**: `test_invoice_creation_atomic_transaction` - Test ARCH-AR-001
- **TEST-040**: `test_gl_entry_balances_after_posting` - Test debit = credit
- **TEST-041**: `test_customer_balance_reflects_invoices` - Test BR-AR-004
- **TEST-042**: `test_eager_loading_prevents_n_plus_one` - Test query count
- **TEST-043**: `test_repository_binding_resolves_correctly` - Test service container
- **TEST-044**: `test_invoice_lifecycle_draft_to_paid` - Test full workflow
- **TEST-045**: `test_sales_order_integration` - Test SO to invoice flow
- **TEST-046**: `test_invoice_amount_uses_bcmath_precision` - Test 4 decimal precision

**Performance Tests (2 tests):**
- **TEST-047**: `test_aging_report_10k_invoices_under_3_seconds` - Test PR-AR-002
- **TEST-048**: `test_customer_balance_cached_performance` - Test caching benefit

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: Credit limit checks bypassed via manual override - **Mitigation**: Log all overrides with user authentication, require approval
- **RISK-002**: Aging report slow with large datasets - **Mitigation**: Use database-level bucket calculation, add indexes on due_date
- **RISK-003**: Race condition updating customer balance - **Mitigation**: Use database transactions and locking
- **RISK-004**: GL entry creation fails mid-posting causing inconsistency - **Mitigation**: Database transaction wrapping all posting operations
- **RISK-005**: Overdue flagging command timeout with millions of invoices - **Mitigation**: Process in chunks, add index on (status, due_date)

**Assumptions:**
- **ASSUMPTION-001**: Most customers pay on time, <20% overdue rate expected
- **ASSUMPTION-002**: Sales orders exist before AR invoice generation
- **ASSUMPTION-003**: Credit limits configured for most customers
- **ASSUMPTION-004**: Payment terms standardized (Net 30, Net 60)
- **ASSUMPTION-005**: GL accounts (AR asset, revenue) pre-configured in COA

## 8. KIV for future implementations

- **KIV-001**: Implement recurring invoice generation (subscriptions)
- **KIV-002**: Add dunning process for collections (automated reminders)
- **KIV-003**: Implement early payment discount capture (2/10 Net 30)
- **KIV-004**: Add customer portal for self-service invoice viewing
- **KIV-005**: Implement invoice dispute workflow
- **KIV-006**: Add customer payment prediction using ML
- **KIV-007**: Implement automated payment allocation (cash application)
- **KIV-008**: Add multi-currency support with exchange rate tracking

## 9. Related PRD / Further Reading

- **Master PRD:** [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD:** [../prd/prd-01/PRD01-SUB12-ACCOUNTS-RECEIVABLE.md](../prd/prd-01/PRD01-SUB12-ACCOUNTS-RECEIVABLE.md)
- **Related Sub-PRDs:**
  - PRD01-SUB08 (General Ledger) - GL posting integration
  - PRD01-SUB17 (Sales) - Sales order to invoice integration
  - PRD01-SUB10 (Banking) - Receipt and deposit integration
- **Related Plans:**
  - PRD01-SUB12-PLAN02 (Receipt Processing) - Next plan for payment handling
- **External Documentation:**
  - Aging Report Best Practices: https://www.accountingtools.com/articles/accounts-receivable-aging-report
  - Credit Management: https://www.investopedia.com/terms/c/credit-management.asp
  - Revenue Recognition: https://www.fasb.org/topic/606
