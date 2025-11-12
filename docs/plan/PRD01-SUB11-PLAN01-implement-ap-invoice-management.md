---
plan: Implement Accounts Payable Invoice Management and Vendor Balance Tracking
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, accounts-payable, finance, vendor-management, invoice-tracking, gl-integration]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan establishes the Accounts Payable invoice management system with vendor invoice entry, automatic GL posting, and vendor balance tracking. It implements the core database schema for AP invoices with line items, creates models with status management, establishes the repository pattern, and integrates with General Ledger for automatic expense and AP liability posting. This plan delivers multi-line invoice entry, invoice lifecycle management (open/partial/paid/void), and the foundation for payment processing in PLAN02.

## 1. Requirements & Constraints

**Functional Requirements:**
- **FR-AP-001**: Support **vendor invoice entry** with multi-line item details and GL distribution
- **FR-AP-004**: Provide **aging reports** (30/60/90 days) for outstanding payables
- **FR-AP-005**: Support **vendor statements** reconciliation

**Business Rules:**
- **BR-AP-002**: **Posted invoices** cannot be edited; only reversed or adjusted
- **BR-AP-004**: Vendor balances MUST equal sum of **unpaid invoices - unapplied payments**

**Data Requirements:**
- **DR-AP-001**: Store AP invoice metadata: invoice_number, vendor_id, invoice_date, due_date, total_amount, paid_amount, status

**Integration Requirements:**
- **IR-AP-002**: Integrate with **General Ledger** for automatic AP and expense posting
- **IR-AP-003**: Integrate with **Vendor Master** for vendor validation and credit limits

**Performance Requirements:**
- **PR-AP-002**: Generate **aging report** for 10,000+ invoices in under 3 seconds

**Security Requirements:**
- **SR-AP-001**: Enforce **role-based access** for payment approval based on amount thresholds

**Scalability Requirements:**
- **SCR-AP-001**: Support **100,000+ invoices** per tenant per year with optimal indexing

**Architecture Requirements:**
- **ARCH-AP-001**: Use **database transactions** to ensure atomicity when applying payments to invoices

**Events:**
- **EV-AP-001**: Dispatch `APInvoiceCreatedEvent` when vendor invoice is created

**Constraints:**
- **CON-001**: Invoice numbers must be unique per tenant
- **CON-002**: Posted invoices cannot be edited (immutable after posting)
- **CON-003**: Invoice total must equal sum of line items
- **CON-004**: Due date must be >= invoice date
- **CON-005**: Line item GL accounts must be expense or asset accounts
- **CON-006**: Vendor must be active to receive new invoices

**Guidelines:**
- **GUD-001**: Use PSR-12 coding standards, strict types, repository pattern, and Laravel Actions
- **GUD-002**: Log all AP operations using Spatie Activity Log
- **GUD-003**: Use decimal precision (DECIMAL 20,4) for all financial amounts
- **GUD-004**: Calculate aging buckets dynamically based on current date vs due date
- **GUD-005**: Cache vendor balances for 5 minutes to improve performance

**Patterns:**
- **PAT-001**: Repository pattern with APInvoiceRepositoryContract
- **PAT-002**: Laravel Actions for CreateAPInvoiceAction, PostAPInvoiceAction
- **PAT-003**: Observer pattern for automatic GL posting on invoice save
- **PAT-004**: Strategy pattern for different invoice types (standard, credit memo, debit memo)

## 2. Implementation Steps

### GOAL-001: Create Database Schema for Accounts Payable System

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-AP-001, DR-AP-001, BR-AP-002, SCR-AP-001 | Implement ap_invoices, ap_invoice_lines, and vendors tables with proper constraints, indexes, and audit fields | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `database/migrations/YYYY_MM_DD_HHMMSS_create_vendors_table.php` with namespace. Add `declare(strict_types=1);`. Use anonymous migration class format: `return new class extends Migration` | | |
| TASK-002 | In up() method, create `vendors` table with columns: `id` (BIGINT AUTO_INCREMENT PRIMARY KEY), `tenant_id` (UUID NOT NULL), `vendor_code` (VARCHAR 50 NOT NULL), `vendor_name` (VARCHAR 255 NOT NULL), `contact_person` (VARCHAR 255 NULL), `email` (VARCHAR 255 NULL), `phone` (VARCHAR 50 NULL), `address` (TEXT NULL), `payment_terms` (VARCHAR 50 NULL - "Net 30", "Net 60", "2/10 Net 30"), `payment_terms_days` (INTEGER DEFAULT 30), `credit_limit` (DECIMAL 20,4 NULL), `currency_code` (VARCHAR 3 NOT NULL DEFAULT 'USD'), `tax_id` (VARCHAR 50 NULL), `is_active` (BOOLEAN DEFAULT TRUE), `gl_account_id` (BIGINT NOT NULL - default AP liability account), `metadata` (JSON NULL), timestamps, soft deletes | | |
| TASK-003 | Add indexes on vendors: `INDEX idx_vendors_tenant (tenant_id)`, `UNIQUE KEY uk_vendors_code (tenant_id, vendor_code)`, `INDEX idx_vendors_active (is_active)`, `INDEX idx_vendors_name (vendor_name)` for search | | |
| TASK-004 | Add foreign keys on vendors: `FOREIGN KEY fk_vendors_tenant (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE`, `FOREIGN KEY fk_vendors_gl (gl_account_id) REFERENCES accounts(id) ON DELETE RESTRICT` (prevent deletion of AP account) | | |
| TASK-005 | Create `ap_invoices` table with columns: `id` (BIGINT AUTO_INCREMENT PRIMARY KEY), `tenant_id` (UUID NOT NULL), `invoice_number` (VARCHAR 50 NOT NULL), `vendor_id` (BIGINT NOT NULL), `invoice_date` (DATE NOT NULL), `due_date` (DATE NOT NULL), `po_number` (VARCHAR 50 NULL - purchase order reference), `description` (TEXT NULL), `subtotal_amount` (DECIMAL 20,4 NOT NULL DEFAULT 0), `tax_amount` (DECIMAL 20,4 NOT NULL DEFAULT 0), `total_amount` (DECIMAL 20,4 NOT NULL), `paid_amount` (DECIMAL 20,4 NOT NULL DEFAULT 0), `status` (VARCHAR 20 NOT NULL DEFAULT 'draft' - draft/posted/partial/paid/void), `posted_at` (TIMESTAMP NULL), `posted_by` (BIGINT NULL), `gl_entry_id` (BIGINT NULL - link to GL entry), `notes` (TEXT NULL), timestamps, soft deletes | | |
| TASK-006 | Add indexes on ap_invoices: `INDEX idx_ap_inv_tenant (tenant_id)`, `UNIQUE KEY uk_ap_inv_number (tenant_id, invoice_number)`, `INDEX idx_ap_inv_vendor (vendor_id)`, `INDEX idx_ap_inv_status (status)`, `INDEX idx_ap_inv_date (invoice_date)`, `INDEX idx_ap_inv_due (due_date)` for aging reports, `INDEX idx_ap_inv_gl (gl_entry_id)` | | |
| TASK-007 | Add foreign keys on ap_invoices: `FOREIGN KEY fk_ap_inv_tenant (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE`, `FOREIGN KEY fk_ap_inv_vendor (vendor_id) REFERENCES vendors(id) ON DELETE RESTRICT`, `FOREIGN KEY fk_ap_inv_posted_by (posted_by) REFERENCES users(id)`, `FOREIGN KEY fk_ap_inv_gl (gl_entry_id) REFERENCES gl_entries(id) ON DELETE SET NULL` | | |
| TASK-008 | Create `ap_invoice_lines` table with columns: `id` (BIGINT AUTO_INCREMENT PRIMARY KEY), `ap_invoice_id` (BIGINT NOT NULL), `line_number` (INTEGER NOT NULL), `description` (TEXT NOT NULL), `gl_account_id` (BIGINT NOT NULL - expense or asset account), `quantity` (DECIMAL 15,4 NOT NULL DEFAULT 1), `unit_price` (DECIMAL 20,4 NOT NULL), `amount` (DECIMAL 20,4 NOT NULL - quantity * unit_price), `tax_code` (VARCHAR 20 NULL), `tax_amount` (DECIMAL 20,4 NOT NULL DEFAULT 0), `cost_center` (VARCHAR 50 NULL - optional allocation), `project_id` (BIGINT NULL - optional project tracking), timestamps | | |
| TASK-009 | Add indexes on ap_invoice_lines: `INDEX idx_ap_lines_invoice (ap_invoice_id)`, `INDEX idx_ap_lines_gl (gl_account_id)`, `INDEX idx_ap_lines_project (project_id)` | | |
| TASK-010 | Add foreign keys on ap_invoice_lines: `FOREIGN KEY fk_ap_lines_invoice (ap_invoice_id) REFERENCES ap_invoices(id) ON DELETE CASCADE`, `FOREIGN KEY fk_ap_lines_gl (gl_account_id) REFERENCES accounts(id) ON DELETE RESTRICT` | | |
| TASK-011 | In down() method, drop tables in reverse order: `Schema::dropIfExists('ap_invoice_lines')`, then ap_invoices, then vendors | | |

### GOAL-002: Create Vendor and AP Invoice Models with Status Management

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-AP-001, BR-AP-002, CON-002, CON-003 | Implement Vendor and APInvoice Eloquent models with status enums, immutability enforcement, and balance calculations | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-012 | Create `app/Domains/AccountsPayable/Models/Vendor.php` with namespace. Add `declare(strict_types=1);`. Import traits: `use BelongsToTenant, HasFactory, SoftDeletes, LogsActivity;` | | |
| TASK-013 | Define $fillable array: `['tenant_id', 'vendor_code', 'vendor_name', 'contact_person', 'email', 'phone', 'address', 'payment_terms', 'payment_terms_days', 'credit_limit', 'currency_code', 'tax_id', 'is_active', 'gl_account_id', 'metadata']` | | |
| TASK-014 | Define $casts array: `['payment_terms_days' => 'integer', 'credit_limit' => 'decimal:4', 'is_active' => 'boolean', 'metadata' => 'array', 'deleted_at' => 'datetime']` | | |
| TASK-015 | Implement `getActivitylogOptions(): LogOptions` for audit trail: `return LogOptions::defaults()->logOnly(['vendor_name', 'email', 'payment_terms', 'credit_limit', 'is_active'])->logOnlyDirty()->dontSubmitEmptyLogs();` | | |
| TASK-016 | Add relationships: `glAccount()` belongsTo Account with withDefault(), `invoices()` hasMany APInvoice ordered by invoice_date desc, `tenant()` belongsTo with withDefault() | | |
| TASK-017 | Add scopes: `scopeActive(Builder $query): Builder` returning `$query->where('is_active', true)`, `scopeByCurrency(Builder $query, string $currency): Builder`, `scopeWithBalance(Builder $query): Builder` adding withSum(['invoices' => 'outstanding_amount']) | | |
| TASK-018 | Implement `getOutstandingBalanceAttribute(): float` computed attribute: `return $this->invoices()->whereIn('status', [APInvoiceStatus::POSTED, APInvoiceStatus::PARTIAL])->sum(DB::raw('total_amount - paid_amount'));`. Calculates unpaid invoice total | | |
| TASK-019 | Implement `isOverCreditLimit(): bool` method: `if ($this->credit_limit === null) { return false; } return $this->outstanding_balance > $this->credit_limit;`. Validates credit limit | | |
| TASK-020 | Implement `canReceiveInvoices(): bool` method: `return $this->is_active && !$this->trashed();`. Validates vendor ready for new invoices (CON-006) | | |
| TASK-021 | Create `app/Domains/AccountsPayable/Models/APInvoice.php` with namespace. Add `declare(strict_types=1);`. Use `BelongsToTenant, HasFactory, SoftDeletes, LogsActivity` traits | | |
| TASK-022 | Define $fillable: `['tenant_id', 'invoice_number', 'vendor_id', 'invoice_date', 'due_date', 'po_number', 'description', 'subtotal_amount', 'tax_amount', 'total_amount', 'paid_amount', 'status', 'posted_at', 'posted_by', 'gl_entry_id', 'notes']`. Define $casts: `['invoice_date' => 'date', 'due_date' => 'date', 'subtotal_amount' => 'decimal:4', 'tax_amount' => 'decimal:4', 'total_amount' => 'decimal:4', 'paid_amount' => 'decimal:4', 'status' => APInvoiceStatus::class, 'posted_at' => 'datetime', 'deleted_at' => 'datetime']` | | |
| TASK-023 | Create `app/Domains/AccountsPayable/Enums/APInvoiceStatus.php` as string-backed enum with cases: `DRAFT = 'draft'`, `POSTED = 'posted'`, `PARTIAL = 'partial'`, `PAID = 'paid'`, `VOID = 'void'`. Implement `label(): string` for display, `isEditable(): bool` (only DRAFT), `isPayable(): bool` (POSTED or PARTIAL), `isComplete(): bool` (PAID or VOID) | | |
| TASK-024 | Add relationships in APInvoice: `vendor()` belongsTo Vendor, `lines()` hasMany APInvoiceLine ordered by line_number, `glEntry()` belongsTo GLEntry with withDefault(), `poster()` belongsTo User (posted_by) with withDefault(), `payments()` hasManyThrough APPayment via APPaymentApplication (PLAN02) | | |
| TASK-025 | Add scopes: `scopeDraft(Builder $query): Builder`, `scopePosted(Builder $query): Builder`, `scopeOutstanding(Builder $query): Builder` (POSTED or PARTIAL status), `scopeOverdue(Builder $query): Builder` returning `$query->outstanding()->where('due_date', '<', now())`, `scopeByVendor(Builder $query, int $vendorId): Builder`, `scopeByDateRange(Builder $query, Carbon $from, Carbon $to): Builder` | | |
| TASK-026 | Implement `getOutstandingAmountAttribute(): float` computed attribute: `return $this->total_amount - $this->paid_amount;`. Used for payment application | | |
| TASK-027 | Implement `getDaysOutstandingAttribute(): int` computed attribute: `return $this->invoice_date->diffInDays(now());`. Days since invoice date | | |
| TASK-028 | Implement `getDaysOverdueAttribute(): int` computed attribute: `return max(0, $this->due_date->diffInDays(now(), false));`. Days past due (0 if not overdue) | | |
| TASK-029 | Implement `getAgingBucketAttribute(): string` computed attribute for reporting: `$days = $this->days_overdue; return match(true) { $days === 0 => 'Current', $days <= 30 => '1-30', $days <= 60 => '31-60', $days <= 90 => '61-90', default => 'Over 90' };` | | |
| TASK-030 | Implement `isFullyPaid(): bool` method: `return bccomp((string)$this->paid_amount, (string)$this->total_amount, 4) === 0;`. Decimal comparison | | |
| TASK-031 | Implement static boot to enforce immutability (CON-002): `static::updating(function ($invoice) { if ($invoice->isDirty() && $invoice->getOriginal('status') !== APInvoiceStatus::DRAFT->value) { $dirtyFields = array_keys($invoice->getDirty()); $allowedFields = ['paid_amount', 'status']; $invalidChanges = array_diff($dirtyFields, $allowedFields); if (!empty($invalidChanges)) { throw new InvoiceImmutableException('Cannot edit posted invoice. Fields: ' . implode(', ', $invalidChanges)); } } });` | | |
| TASK-032 | Create `app/Domains/AccountsPayable/Models/APInvoiceLine.php` with namespace. Add `declare(strict_types=1);`. Use `HasFactory` trait | | |
| TASK-033 | Define $fillable: `['ap_invoice_id', 'line_number', 'description', 'gl_account_id', 'quantity', 'unit_price', 'amount', 'tax_code', 'tax_amount', 'cost_center', 'project_id']`. Define $casts: `['line_number' => 'integer', 'quantity' => 'decimal:4', 'unit_price' => 'decimal:4', 'amount' => 'decimal:4', 'tax_amount' => 'decimal:4']` | | |
| TASK-034 | Add relationships: `invoice()` belongsTo APInvoice, `glAccount()` belongsTo Account, `project()` belongsTo Project (if exists) with withDefault() | | |
| TASK-035 | Implement static boot to calculate amount automatically: `static::saving(function ($line) { $line->amount = bcmul((string)$line->quantity, (string)$line->unit_price, 4); });`. Ensures quantity * unit_price consistency | | |

### GOAL-003: Implement Repository Pattern for AP Entities

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| PAT-001, PR-AP-002, SCR-AP-001 | Create repository contracts and implementations for efficient AP queries with eager loading, pagination, and aging calculations | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-036 | Create `app/Domains/AccountsPayable/Contracts/VendorRepositoryContract.php` with namespace. Add `declare(strict_types=1);`. Define interface methods: `findById(int $id): ?Vendor`, `findByCode(string $code, ?string $tenantId = null): ?Vendor`, `getActive(?string $tenantId = null): Collection`, `getWithBalance(?string $tenantId = null): Collection`, `paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator`, `create(array $data): Vendor`, `update(Vendor $vendor, array $data): Vendor`, `delete(Vendor $vendor): bool` | | |
| TASK-037 | Create `app/Domains/AccountsPayable/Repositories/DatabaseVendorRepository.php` implementing VendorRepositoryContract. Add `declare(strict_types=1);`. Constructor has no dependencies | | |
| TASK-038 | Implement `getWithBalance()` with eager loading: `return Vendor::with(['glAccount'])->withSum(['invoices as outstanding_balance' => fn($q) => $q->outstanding()], DB::raw('total_amount - paid_amount'))->where('tenant_id', $tenantId ?? tenant_id())->get();`. Efficiently loads balances | | |
| TASK-039 | Implement `paginate()` with filters: Support filters: `is_active` (bool), `currency` (string), `search` (string for vendor_name/vendor_code), `over_limit` (bool - credit limit exceeded). Build query with conditional filters | | |
| TASK-040 | Create `app/Domains/AccountsPayable/Contracts/APInvoiceRepositoryContract.php` with methods: `findById(int $id): ?APInvoice`, `findByNumber(string $number, ?string $tenantId = null): ?APInvoice`, `getOutstanding(?string $tenantId = null): Collection`, `getOverdue(?string $tenantId = null): Collection`, `getByVendor(int $vendorId): Collection`, `getByDateRange(Carbon $from, Carbon $to, ?string $tenantId = null): Collection`, `paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator`, `create(array $data): APInvoice`, `update(APInvoice $invoice, array $data): APInvoice`, `getAgingReport(?string $tenantId = null): array` | | |
| TASK-041 | Create `app/Domains/AccountsPayable/Repositories/DatabaseAPInvoiceRepository.php` implementing contract. Implement methods with proper eager loading: always load `with(['vendor', 'lines.glAccount'])` to prevent N+1 | | |
| TASK-042 | Implement `getOutstanding()`: `return APInvoice::with(['vendor', 'lines'])->outstanding()->where('tenant_id', $tenantId ?? tenant_id())->orderBy('due_date')->get();`. Used for payment queue | | |
| TASK-043 | Implement `getAgingReport()` for PR-AP-002: `$invoices = APInvoice::with('vendor')->outstanding()->where('tenant_id', $tenantId ?? tenant_id())->get(); $buckets = ['Current' => 0, '1-30' => 0, '31-60' => 0, '61-90' => 0, 'Over 90' => 0]; foreach ($invoices as $invoice) { $bucket = $invoice->aging_bucket; $buckets[$bucket] += $invoice->outstanding_amount; } return ['buckets' => $buckets, 'total_outstanding' => array_sum($buckets), 'invoice_count' => $invoices->count(), 'by_vendor' => $invoices->groupBy('vendor_id')->map(fn($group) => ['vendor_name' => $group->first()->vendor->vendor_name, 'outstanding' => $group->sum('outstanding_amount')])];`. Efficient aging calculation | | |
| TASK-044 | Implement `paginate()` with filters: Support filters: `status` (string), `vendor_id` (int), `overdue` (bool), `aging_bucket` (string), `from_date`, `to_date`, `search` (invoice_number/po_number). Use query builder with conditional filters | | |
| TASK-045 | Bind contracts to implementations in `app/Providers/AppServiceProvider.php` register() method: `$this->app->bind(VendorRepositoryContract::class, DatabaseVendorRepository::class); $this->app->bind(APInvoiceRepositoryContract::class, DatabaseAPInvoiceRepository::class);` | | |

### GOAL-004: Implement Invoice Creation Action with GL Integration

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-AP-001, IR-AP-002, CON-003, CON-005, EV-AP-001 | Create action to create AP invoices with line items, validation, automatic GL posting, and event dispatching | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-046 | Create `app/Domains/AccountsPayable/Actions/CreateAPInvoiceAction.php` with namespace. Add `declare(strict_types=1);`. Use AsAction trait. Constructor: `public function __construct(private readonly APInvoiceRepositoryContract $invoiceRepo, private readonly VendorRepositoryContract $vendorRepo, private readonly ActivityLoggerContract $activityLogger) {}` | | |
| TASK-047 | Implement `handle(array $data): APInvoice` method. Step 1: Validate vendor exists and active: `$vendor = $this->vendorRepo->findById($data['vendor_id']); if (!$vendor || !$vendor->canReceiveInvoices()) { throw new InactiveVendorException('Vendor is not active or not found'); }` | | |
| TASK-048 | Step 2: Validate invoice number unique: `if ($this->invoiceRepo->findByNumber($data['invoice_number'], tenant_id())) { throw new DuplicateInvoiceNumberException('Invoice number already exists'); }` | | |
| TASK-049 | Step 3: Validate dates: `if (Carbon::parse($data['due_date'])->lt(Carbon::parse($data['invoice_date']))) { throw new InvalidDateException('Due date must be >= invoice date'); }` (CON-004) | | |
| TASK-050 | Step 4: Validate line items present: `if (empty($data['lines']) || count($data['lines']) === 0) { throw new MissingLineItemsException('Invoice must have at least one line item'); }` | | |
| TASK-051 | Step 5: Validate and process line items: `$totalAmount = '0'; $totalTax = '0'; foreach ($data['lines'] as $index => $line) { $glAccount = Account::find($line['gl_account_id']); if (!$glAccount || !in_array($glAccount->account_type, [AccountType::EXPENSE, AccountType::ASSET])) { throw new InvalidGLAccountException("Line {$index}: GL account must be expense or asset type"); } $lineAmount = bcmul((string)$line['quantity'], (string)$line['unit_price'], 4); $totalAmount = bcadd($totalAmount, $lineAmount, 4); $totalTax = bcadd($totalTax, (string)($line['tax_amount'] ?? 0), 4); }`. Validates CON-005 and calculates totals | | |
| TASK-052 | Step 6: Validate total matches: `$calculatedTotal = bcadd($totalAmount, $totalTax, 4); if (bccomp($calculatedTotal, (string)$data['total_amount'], 4) !== 0) { throw new InvoiceTotalMismatchException("Invoice total {$data['total_amount']} does not match calculated total {$calculatedTotal}"); }` (CON-003) | | |
| TASK-053 | Step 7: Create invoice and lines in transaction: `DB::transaction(function() use ($data, $totalAmount, $totalTax) { $invoiceData = array_merge($data, ['tenant_id' => tenant_id(), 'status' => APInvoiceStatus::DRAFT, 'subtotal_amount' => $totalAmount, 'tax_amount' => $totalTax, 'paid_amount' => 0]); $invoice = $this->invoiceRepo->create($invoiceData); foreach ($data['lines'] as $index => $line) { $invoice->lines()->create(array_merge($line, ['line_number' => $index + 1])); } $this->activityLogger->log("AP Invoice created: {$invoice->invoice_number}", $invoice, auth()->user()); event(new APInvoiceCreatedEvent($invoice)); return $invoice->fresh(['lines', 'vendor']); });` | | |
| TASK-054 | Create `app/Domains/AccountsPayable/Actions/PostAPInvoiceAction.php` with AsAction trait. Constructor: `public function __construct(private readonly APInvoiceRepositoryContract $invoiceRepo, private readonly GLEntryService $glService, private readonly ActivityLoggerContract $activityLogger) {}` | | |
| TASK-055 | Implement `handle(APInvoice $invoice): APInvoice` in PostAPInvoiceAction. Step 1: Validate invoice in draft: `if ($invoice->status !== APInvoiceStatus::DRAFT) { throw new InvalidStatusException('Only draft invoices can be posted'); }` | | |
| TASK-056 | Step 2: Authorize posting: `if (!auth()->user()->can('post-ap-invoices')) { throw new UnauthorizedException('Missing permission: post-ap-invoices'); }` | | |
| TASK-057 | Step 3: Create GL entry with debit to expenses and credit to AP: `$glEntryLines = []; foreach ($invoice->lines as $line) { $glEntryLines[] = ['account_id' => $line->gl_account_id, 'debit_amount' => $line->amount + $line->tax_amount, 'credit_amount' => 0, 'description' => $line->description]; } $glEntryLines[] = ['account_id' => $invoice->vendor->gl_account_id, 'debit_amount' => 0, 'credit_amount' => $invoice->total_amount, 'description' => "AP Invoice {$invoice->invoice_number}"];`. Debit expenses, credit AP liability | | |
| TASK-058 | Step 4: Post GL entry: `DB::transaction(function() use ($invoice, $glEntryLines) { $glEntry = $this->glService->createEntry(['entry_date' => $invoice->invoice_date, 'description' => "AP Invoice {$invoice->invoice_number} - {$invoice->vendor->vendor_name}", 'reference' => $invoice->invoice_number, 'lines' => $glEntryLines]); $invoice->update(['status' => APInvoiceStatus::POSTED, 'posted_at' => now(), 'posted_by' => auth()->id(), 'gl_entry_id' => $glEntry->id]); $this->activityLogger->log("AP Invoice posted to GL", $invoice, auth()->user()); return $invoice->fresh(); });` | | |
| TASK-059 | Create `app/Domains/AccountsPayable/Events/APInvoiceCreatedEvent.php` with namespace. Constructor: `public function __construct(public readonly APInvoice $invoice) {}` | | |
| TASK-060 | Create exceptions: `app/Domains/AccountsPayable/Exceptions/InactiveVendorException.php`, `DuplicateInvoiceNumberException.php`, `InvalidDateException.php`, `MissingLineItemsException.php`, `InvalidGLAccountException.php`, `InvoiceTotalMismatchException.php`, `InvoiceImmutableException.php`, `InvalidStatusException.php`. All extend base Exception | | |

### GOAL-005: Implement Aging Report and Vendor Statement Reconciliation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-AP-004, FR-AP-005, PR-AP-002 | Create aging report generation and vendor statement reconciliation features with optimized queries | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-061 | Create `app/Domains/AccountsPayable/Services/APReportService.php` with constructor: `public function __construct(private readonly APInvoiceRepositoryContract $invoiceRepo, private readonly VendorRepositoryContract $vendorRepo) {}` | | |
| TASK-062 | Implement `generateAgingReport(?string $tenantId = null, ?int $vendorId = null): array` method. Get aging data from repository: `$agingData = $this->invoiceRepo->getAgingReport($tenantId);`. If vendor filter, filter by vendor: `if ($vendorId) { $vendor = $this->vendorRepo->findById($vendorId); $agingData['by_vendor'] = $agingData['by_vendor']->where('vendor_id', $vendorId); $agingData['total_outstanding'] = $agingData['by_vendor']->sum('outstanding'); }` | | |
| TASK-063 | Add aging summary with percentages: `$summary = ['buckets' => $agingData['buckets'], 'total' => $agingData['total_outstanding'], 'percentages' => [], 'invoice_count' => $agingData['invoice_count'], 'generated_at' => now()->toIso8601String()]; foreach ($agingData['buckets'] as $bucket => $amount) { $summary['percentages'][$bucket] = $agingData['total_outstanding'] > 0 ? round(($amount / $agingData['total_outstanding']) * 100, 2) : 0; } return $summary;` | | |
| TASK-064 | Implement `generateVendorStatement(Vendor $vendor, Carbon $from, Carbon $to): array` method for FR-AP-005: Get all invoices in period: `$invoices = $this->invoiceRepo->getByVendor($vendor->id)->whereBetween('invoice_date', [$from, $to]);`. Get all payments (will be in PLAN02): `$payments = $vendor->payments()->whereBetween('payment_date', [$from, $to])->get();` | | |
| TASK-065 | Build transaction list combining invoices and payments: `$transactions = collect(); foreach ($invoices as $invoice) { $transactions->push(['date' => $invoice->invoice_date, 'type' => 'invoice', 'reference' => $invoice->invoice_number, 'description' => $invoice->description, 'amount' => $invoice->total_amount, 'balance_impact' => $invoice->total_amount]); } foreach ($payments as $payment) { $transactions->push(['date' => $payment->payment_date, 'type' => 'payment', 'reference' => $payment->payment_number, 'description' => "Payment", 'amount' => $payment->total_amount, 'balance_impact' => -$payment->total_amount]); } $transactions = $transactions->sortBy('date');` | | |
| TASK-066 | Calculate running balance: `$beginningBalance = $vendor->invoices()->where('invoice_date', '<', $from)->sum(DB::raw('total_amount - paid_amount')); $runningBalance = $beginningBalance; foreach ($transactions as &$txn) { $runningBalance += $txn['balance_impact']; $txn['running_balance'] = $runningBalance; }` | | |
| TASK-067 | Return statement: `return ['vendor' => ['id' => $vendor->id, 'name' => $vendor->vendor_name, 'code' => $vendor->vendor_code], 'period' => ['from' => $from, 'to' => $to], 'beginning_balance' => $beginningBalance, 'ending_balance' => $runningBalance, 'total_invoices' => $invoices->sum('total_amount'), 'total_payments' => $payments->sum('total_amount'), 'transactions' => $transactions];` | | |
| TASK-068 | Create `app/Domains/AccountsPayable/Actions/GenerateAPAgingReportAction.php` with AsAction trait. Implement `asJob(): bool` returning true for queue processing. Constructor: `public function __construct(private readonly APReportService $reportService) {}` | | |
| TASK-069 | Implement `handle(?int $vendorId = null): array` in GenerateAPAgingReportAction: `return $this->reportService->generateAgingReport(tenant_id(), $vendorId);`. Can be dispatched as job: `GenerateAPAgingReportAction::dispatch($vendorId);` | | |
| TASK-070 | Create `app/Domains/AccountsPayable/Actions/GenerateVendorStatementAction.php` with AsAction trait. Implement handle method that calls `$this->reportService->generateVendorStatement($vendor, $from, $to);` | | |

## 3. Alternatives

- **ALT-001**: Store vendor balance as cached column instead of calculating - **Rejected** because real-time accuracy critical for AP, risk of cache staleness
- **ALT-002**: Allow editing posted invoices - **Rejected** because violates accounting best practices (BR-AP-002), use adjustment invoices instead
- **ALT-003**: Use NoSQL for invoice storage (high volume) - **Rejected** because relational integrity critical for financial data, complex joins needed
- **ALT-004**: Separate tables for different invoice types (standard, credit memo, debit memo) - **Rejected** because adds complexity, single table with type enum more maintainable
- **ALT-005**: Store aging buckets as cached fields - **Rejected** because aging is time-based (changes daily), calculate on demand for accuracy

## 4. Dependencies

**Package Dependencies:**
- **DEP-001**: `laravel/framework` ^12.0
- **DEP-002**: `spatie/laravel-activitylog` ^4.0 (audit logging)
- **DEP-003**: `lorisleiva/laravel-actions` ^2.0 (action pattern)
- **DEP-004**: `brick/math` ^0.12 (decimal precision)

**Internal Dependencies:**
- **DEP-005**: PRD01-SUB01 (Multi-Tenancy System) - MUST be implemented first
- **DEP-006**: PRD01-SUB03 (Audit Logging System)
- **DEP-007**: PRD01-SUB07 (Chart of Accounts) - For GL account validation
- **DEP-008**: PRD01-SUB08 (General Ledger) - For automatic GL posting

**Infrastructure:**
- **DEP-009**: PostgreSQL 14+ OR MySQL 8.0+ with DECIMAL precision support
- **DEP-010**: Queue worker for async report generation (optional)
- **DEP-011**: Cache driver (Redis/Memcached) for vendor balance caching

## 5. Files

**Migrations:**
- `database/migrations/YYYY_MM_DD_HHMMSS_create_vendors_table.php` - Vendor master
- `database/migrations/YYYY_MM_DD_HHMMSS_create_ap_invoices_table.php` - AP invoices
- `database/migrations/YYYY_MM_DD_HHMMSS_create_ap_invoice_lines_table.php` - Invoice lines

**Models:**
- `app/Domains/AccountsPayable/Models/Vendor.php` - Vendor master
- `app/Domains/AccountsPayable/Models/APInvoice.php` - AP invoice header
- `app/Domains/AccountsPayable/Models/APInvoiceLine.php` - Invoice line items

**Enums:**
- `app/Domains/AccountsPayable/Enums/APInvoiceStatus.php` - Invoice lifecycle

**Contracts:**
- `app/Domains/AccountsPayable/Contracts/VendorRepositoryContract.php` - Vendor repository
- `app/Domains/AccountsPayable/Contracts/APInvoiceRepositoryContract.php` - Invoice repository

**Repositories:**
- `app/Domains/AccountsPayable/Repositories/DatabaseVendorRepository.php` - Vendor repo
- `app/Domains/AccountsPayable/Repositories/DatabaseAPInvoiceRepository.php` - Invoice repo

**Services:**
- `app/Domains/AccountsPayable/Services/APReportService.php` - Report generation

**Actions:**
- `app/Domains/AccountsPayable/Actions/CreateAPInvoiceAction.php` - Create invoice
- `app/Domains/AccountsPayable/Actions/PostAPInvoiceAction.php` - Post to GL
- `app/Domains/AccountsPayable/Actions/GenerateAPAgingReportAction.php` - Aging report
- `app/Domains/AccountsPayable/Actions/GenerateVendorStatementAction.php` - Vendor statement

**Events:**
- `app/Domains/AccountsPayable/Events/APInvoiceCreatedEvent.php` - Invoice created

**Exceptions:**
- `app/Domains/AccountsPayable/Exceptions/InactiveVendorException.php` - Vendor inactive
- `app/Domains/AccountsPayable/Exceptions/DuplicateInvoiceNumberException.php` - Duplicate number
- `app/Domains/AccountsPayable/Exceptions/InvalidDateException.php` - Date validation
- `app/Domains/AccountsPayable/Exceptions/MissingLineItemsException.php` - No lines
- `app/Domains/AccountsPayable/Exceptions/InvalidGLAccountException.php` - Invalid GL account
- `app/Domains/AccountsPayable/Exceptions/InvoiceTotalMismatchException.php` - Total mismatch
- `app/Domains/AccountsPayable/Exceptions/InvoiceImmutableException.php` - Edit posted invoice
- `app/Domains/AccountsPayable/Exceptions/InvalidStatusException.php` - Invalid status

**Service Provider (updated):**
- `app/Providers/AppServiceProvider.php` - Repository bindings

## 6. Testing

**Unit Tests (18 tests):**
- **TEST-001**: `test_vendor_calculates_outstanding_balance_correctly` - Test getOutstandingBalanceAttribute()
- **TEST-002**: `test_vendor_detects_credit_limit_exceeded` - Test isOverCreditLimit()
- **TEST-003**: `test_vendor_validates_active_status` - Test canReceiveInvoices()
- **TEST-004**: `test_ap_invoice_status_enum_has_all_cases` - Verify 5 status cases
- **TEST-005**: `test_ap_invoice_calculates_outstanding_amount` - Test getOutstandingAmountAttribute()
- **TEST-006**: `test_ap_invoice_calculates_days_overdue` - Test getDaysOverdueAttribute()
- **TEST-007**: `test_ap_invoice_assigns_aging_bucket_correctly` - Test getAgingBucketAttribute()
- **TEST-008**: `test_ap_invoice_detects_fully_paid` - Test isFullyPaid()
- **TEST-009**: `test_ap_invoice_line_calculates_amount_automatically` - Test boot calculation
- **TEST-010**: `test_repository_finds_invoice_by_number` - Test findByNumber()
- **TEST-011**: `test_repository_gets_outstanding_invoices` - Test getOutstanding()
- **TEST-012**: `test_repository_gets_overdue_invoices` - Test getOverdue()
- **TEST-013**: `test_repository_generates_aging_report` - Test getAgingReport()
- **TEST-014**: `test_aging_report_groups_by_buckets` - Test bucket calculation
- **TEST-015**: `test_vendor_statement_calculates_running_balance` - Test statement generation
- **TEST-016**: `test_vendor_factory_generates_valid_data` - Test factory
- **TEST-017**: `test_ap_invoice_scope_outstanding_works` - Test scopeOutstanding()
- **TEST-018**: `test_ap_invoice_scope_overdue_works` - Test scopeOverdue()

**Feature Tests (15 tests):**
- **TEST-019**: `test_create_ap_invoice_action_validates_vendor` - Test vendor validation
- **TEST-020**: `test_create_ap_invoice_validates_dates` - Test date validation (CON-004)
- **TEST-021**: `test_create_ap_invoice_validates_line_items` - Test line item presence
- **TEST-022**: `test_create_ap_invoice_validates_total_matches` - Test CON-003
- **TEST-023**: `test_create_ap_invoice_validates_gl_accounts` - Test CON-005
- **TEST-024**: `test_create_ap_invoice_dispatches_event` - Test APInvoiceCreatedEvent
- **TEST-025**: `test_post_invoice_creates_gl_entry` - Test GL integration
- **TEST-026**: `test_post_invoice_only_allows_draft` - Test status validation
- **TEST-027**: `test_cannot_edit_posted_invoice` - Test BR-AP-002 immutability
- **TEST-028**: `test_generate_aging_report_action` - Test aging report
- **TEST-029**: `test_generate_vendor_statement_action` - Test statement generation
- **TEST-030**: `test_activity_log_records_ap_operations` - Test LogsActivity
- **TEST-031**: `test_unique_invoice_number_per_tenant` - Test CON-001
- **TEST-032**: `test_tenant_scoping_isolates_invoices` - Test BelongsToTenant trait
- **TEST-033**: `test_vendor_balance_matches_unpaid_invoices` - Test BR-AP-004

**Integration Tests (8 tests):**
- **TEST-034**: `test_invoice_creation_with_lines_atomic` - Test DB transaction
- **TEST-035**: `test_gl_entry_balances_after_posting` - Test debit = credit
- **TEST-036**: `test_eager_loading_prevents_n_plus_one` - Test query count
- **TEST-037**: `test_repository_binding_resolves_correctly` - Test service container
- **TEST-038**: `test_invoice_total_uses_bcmath_precision` - Test 4 decimal precision
- **TEST-039**: `test_aging_report_integrates_with_gl` - Test GL integration
- **TEST-040**: `test_vendor_statement_includes_payments` - Test payment integration (PLAN02)
- **TEST-041**: `test_invoice_lifecycle_draft_to_paid` - Test full workflow

**Performance Tests (3 tests):**
- **TEST-042**: `test_aging_report_10k_invoices_under_3_seconds` - Test PR-AP-002
- **TEST-043**: `test_paginate_100k_invoices_under_100ms` - Test SCR-AP-001
- **TEST-044**: `test_vendor_balance_calculation_cached` - Test caching performance

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: Vendor balance calculation slow for vendors with many invoices - **Mitigation**: Cache balances for 5 minutes, use database indexes on status and paid_amount
- **RISK-002**: Aging report slow with 100k+ invoices - **Mitigation**: Use optimized query with grouping, dispatch as background job for large datasets
- **RISK-003**: Concurrent invoice creation causes duplicate numbers - **Mitigation**: Unique constraint on (tenant_id, invoice_number), wrap in transaction
- **RISK-004**: GL posting fails mid-transaction leaving incomplete data - **Mitigation**: Use database transaction wrapping invoice update and GL entry creation
- **RISK-005**: Immutability enforcement bypassed via direct DB update - **Mitigation**: Database triggers (future), rely on application-level enforcement and audit logging

**Assumptions:**
- **ASSUMPTION-001**: Most vendors have Net 30 payment terms (can be configured)
- **ASSUMPTION-002**: Invoices typically have 1-10 line items, rarely exceed 50 lines
- **ASSUMPTION-003**: AP staff review aging reports weekly, not real-time
- **ASSUMPTION-004**: Credit limits are soft limits (warning only, not blocking)
- **ASSUMPTION-005**: GL account structure already configured with expense and AP liability accounts

## 8. KIV for future implementations

- **KIV-001**: Implement three-way matching (PO + Receipt + Invoice)
- **KIV-002**: Add invoice approval workflow (multi-level approvals)
- **KIV-003**: Implement early payment discount tracking (2/10 Net 30)
- **KIV-004**: Add vendor performance metrics (on-time payment rate)
- **KIV-005**: Implement recurring invoice templates
- **KIV-006**: Add OCR integration for invoice scanning/data extraction
- **KIV-007**: Implement invoice dispute management workflow
- **KIV-008**: Add predictive analytics for cash flow forecasting

## 9. Related PRD / Further Reading

- **Master PRD:** [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD:** [../prd/prd-01/PRD01-SUB11-ACCOUNTS-PAYABLE.md](../prd/prd-01/PRD01-SUB11-ACCOUNTS-PAYABLE.md)
- **Related Sub-PRDs:**
  - PRD01-SUB07 (Chart of Accounts) - GL account validation
  - PRD01-SUB08 (General Ledger) - GL posting integration
  - PRD01-SUB10 (Banking) - Payment disbursement (PLAN02)
  - PRD01-SUB16 (Purchasing) - PO integration (future)
- **Related Plans:**
  - PRD01-SUB11-PLAN02 (Payment Processing and Application) - Next phase
- **External Documentation:**
  - Accounts Payable Best Practices: https://www.investopedia.com/terms/a/accountspayable.asp
  - AP Aging Reports: https://www.accountingtools.com/articles/accounts-payable-aging-report
  - Three-Way Matching: https://www.procurify.com/blog/three-way-matching/
