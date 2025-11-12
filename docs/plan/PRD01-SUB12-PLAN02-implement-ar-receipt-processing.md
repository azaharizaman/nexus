---
plan: Implement Accounts Receivable Receipt Processing and Payment Application
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, accounts-receivable, payment-processing, cash-application, banking-integration, collections]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan delivers the receipt processing system for Accounts Receivable with payment recording, intelligent receipt application to multiple invoices, unapplied receipt tracking, and banking integration for deposit reconciliation. It implements receipt entry with multiple payment methods, automatic and manual cash application, payment allocation logic with partial payments, and integration with the Banking module for deposit tracking. This plan completes the AR system by automating the customer payment workflow that reduces manual payment application time by 75%.

## 1. Requirements & Constraints

**Functional Requirements:**
- **FR-AR-002**: Implement **receipt processing** with payment method tracking
- **FR-AR-003**: Support **receipt application** to multiple invoices with partial payments

**Business Rules:**
- **BR-AR-001**: Receipt amounts MUST **not exceed the invoice outstanding balance**
- **BR-AR-003**: Receipts MUST reference at least one **valid customer invoice**
- **BR-AR-004**: Customer balances MUST equal sum of **unpaid invoices - unapplied receipts**

**Data Requirements:**
- **DR-AR-002**: Store AR receipt metadata: receipt_number, receipt_date, customer_id, bank_account_id, payment_method, total_amount
- **DR-AR-003**: Store receipt applications: ar_receipt_id, ar_invoice_id, amount_applied

**Integration Requirements:**
- **IR-AR-001**: Integrate with **Banking module** for payment reconciliation and deposit tracking

**Performance Requirements:**
- **PR-AR-001**: Generate and post receipts under **2 seconds** per transaction

**Security Requirements:**
- **SR-AR-001**: Enforce **role-based access** for credit memo approval based on amount thresholds

**Architecture Requirements:**
- **ARCH-AR-001**: Use **database transactions** to ensure atomicity when applying receipts to invoices

**Events:**
- **EV-AR-002**: Dispatch `ARReceiptProcessedEvent` when payment receipt is processed
- **EV-AR-003**: Dispatch `ARReceiptAppliedEvent` when receipt is applied to invoice
- **EV-AR-004**: Dispatch `ARInvoiceFullyPaidEvent` when invoice is fully paid

**Constraints:**
- **CON-008**: Receipt numbers must be unique per tenant
- **CON-009**: Receipt date must be >= invoice date for all applied invoices
- **CON-010**: Total receipt application cannot exceed receipt amount
- **CON-011**: Cannot unapply receipt from paid invoice without approval
- **CON-012**: Receipt method must match bank account capability (cash, check, wire, card)

**Guidelines:**
- **GUD-007**: Use PSR-12 coding standards, strict types, repository pattern, and Laravel Actions
- **GUD-008**: Log all receipt operations using Spatie Activity Log
- **GUD-009**: Process receipt applications with pessimistic locking to prevent race conditions
- **GUD-010**: Generate receipt notifications asynchronously
- **GUD-011**: Automatically match receipts to invoices when reference number provided

**Patterns:**
- **PAT-005**: Repository pattern with ARReceiptRepositoryContract
- **PAT-006**: Strategy pattern for different payment methods (cash, check, wire, card)
- **PAT-007**: Laravel Actions for ProcessReceiptAction, ApplyReceiptAction
- **PAT-008**: Queue jobs for bank reconciliation matching

## 2. Implementation Steps

### GOAL-001: Create Receipt and Receipt Application Database Schema

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-AR-002, DR-AR-002, DR-AR-003, ARCH-AR-001 | Implement ar_receipts and ar_receipt_applications tables with proper constraints and atomicity support | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `database/migrations/YYYY_MM_DD_HHMMSS_create_ar_receipts_table.php` with namespace. Add `declare(strict_types=1);`. Use anonymous migration class format: `return new class extends Migration` | | |
| TASK-002 | In up() method, create `ar_receipts` table with columns: `id` (BIGINT AUTO_INCREMENT PRIMARY KEY), `tenant_id` (UUID NOT NULL), `receipt_number` (VARCHAR 50 NOT NULL), `customer_id` (BIGINT NOT NULL), `receipt_date` (DATE NOT NULL), `bank_account_id` (BIGINT NOT NULL - deposit bank account), `payment_method` (VARCHAR 20 NOT NULL - 'cash', 'check', 'wire', 'card'), `total_amount` (DECIMAL 20,4 NOT NULL), `applied_amount` (DECIMAL 20,4 NOT NULL DEFAULT 0), `unapplied_amount` (DECIMAL 20,4 NOT NULL DEFAULT 0 - computed: total - applied), `status` (VARCHAR 20 NOT NULL DEFAULT 'pending' - pending/applied/deposited/void), `check_number` (VARCHAR 50 NULL - for check payments), `wire_reference` (VARCHAR 100 NULL - for wire transfers), `card_last4` (VARCHAR 4 NULL - for card payments), `gl_entry_id` (BIGINT NULL - link to GL receipt entry), `bank_transaction_id` (BIGINT NULL - link to bank transaction for reconciliation), `reference` (VARCHAR 255 NULL - customer reference/invoice number), `notes` (TEXT NULL), timestamps, soft deletes | | |
| TASK-003 | Add indexes on ar_receipts: `INDEX idx_ar_rec_tenant (tenant_id)`, `UNIQUE KEY uk_ar_rec_number (tenant_id, receipt_number)`, `INDEX idx_ar_rec_customer (customer_id)`, `INDEX idx_ar_rec_status (status)`, `INDEX idx_ar_rec_date (receipt_date)`, `INDEX idx_ar_rec_bank (bank_account_id)`, `INDEX idx_ar_rec_bank_tx (bank_transaction_id)`, `INDEX idx_ar_rec_reference (reference)` for auto-matching | | |
| TASK-004 | Add foreign keys on ar_receipts: `FOREIGN KEY fk_ar_rec_tenant (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE`, `FOREIGN KEY fk_ar_rec_customer (customer_id) REFERENCES customers(id) ON DELETE RESTRICT`, `FOREIGN KEY fk_ar_rec_bank (bank_account_id) REFERENCES bank_accounts(id) ON DELETE RESTRICT`, `FOREIGN KEY fk_ar_rec_gl (gl_entry_id) REFERENCES gl_entries(id) ON DELETE SET NULL`, `FOREIGN KEY fk_ar_rec_bank_tx (bank_transaction_id) REFERENCES bank_transactions(id) ON DELETE SET NULL` | | |
| TASK-005 | Create `ar_receipt_applications` table with columns: `id` (BIGINT AUTO_INCREMENT PRIMARY KEY), `ar_receipt_id` (BIGINT NOT NULL), `ar_invoice_id` (BIGINT NOT NULL), `amount_applied` (DECIMAL 20,4 NOT NULL), `applied_at` (TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP), `applied_by` (BIGINT NOT NULL), `notes` (TEXT NULL), timestamps | | |
| TASK-006 | Add indexes on ar_receipt_applications: `INDEX idx_ar_app_receipt (ar_receipt_id)`, `INDEX idx_ar_app_invoice (ar_invoice_id)`, `INDEX idx_ar_app_date (applied_at)`. Add unique constraint to prevent duplicate applications: `UNIQUE KEY uk_ar_app_receipt_invoice (ar_receipt_id, ar_invoice_id)` | | |
| TASK-007 | Add foreign keys on ar_receipt_applications: `FOREIGN KEY fk_ar_app_receipt (ar_receipt_id) REFERENCES ar_receipts(id) ON DELETE CASCADE`, `FOREIGN KEY fk_ar_app_invoice (ar_invoice_id) REFERENCES ar_invoices(id) ON DELETE CASCADE`, `FOREIGN KEY fk_ar_app_applied_by (applied_by) REFERENCES users(id)` | | |
| TASK-008 | In down() method, drop tables in reverse order: `Schema::dropIfExists('ar_receipt_applications')`, then ar_receipts | | |

### GOAL-002: Create Receipt Models with Status and Payment Method Management

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-AR-002, FR-AR-003, CON-010 | Implement ARReceipt and ARReceiptApplication models with validation and relationship definitions | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-009 | Create `app/Domains/AccountsReceivable/Models/ARReceipt.php` with namespace. Add `declare(strict_types=1);`. Import traits: `use BelongsToTenant, HasFactory, SoftDeletes, LogsActivity;` | | |
| TASK-010 | Define $fillable array: `['tenant_id', 'receipt_number', 'customer_id', 'receipt_date', 'bank_account_id', 'payment_method', 'total_amount', 'applied_amount', 'unapplied_amount', 'status', 'check_number', 'wire_reference', 'card_last4', 'gl_entry_id', 'bank_transaction_id', 'reference', 'notes']` | | |
| TASK-011 | Define $casts array: `['receipt_date' => 'date', 'total_amount' => 'decimal:4', 'applied_amount' => 'decimal:4', 'unapplied_amount' => 'decimal:4', 'status' => ARReceiptStatus::class, 'payment_method' => PaymentMethod::class, 'deleted_at' => 'datetime']` | | |
| TASK-012 | Create `app/Domains/AccountsReceivable/Enums/ARReceiptStatus.php` as string-backed enum with cases: `PENDING = 'pending'`, `APPLIED = 'applied'`, `DEPOSITED = 'deposited'`, `VOID = 'void'`. Implement `label(): string`, `canApply(): bool` (PENDING or APPLIED), `isComplete(): bool` (DEPOSITED or VOID) | | |
| TASK-013 | Create `app/Domains/AccountsReceivable/Enums/PaymentMethod.php` as string-backed enum with cases: `CASH = 'cash'`, `CHECK = 'check'`, `WIRE = 'wire'`, `CARD = 'card'`. Implement `label(): string`, `requiresCheckNumber(): bool` (CHECK only), `isElectronic(): bool` (WIRE, CARD), `requiresBankReconciliation(): bool` (all except CASH) | | |
| TASK-014 | Implement `getActivitylogOptions(): LogOptions` in ARReceipt: `return LogOptions::defaults()->logOnly(['receipt_number', 'customer_id', 'receipt_date', 'total_amount', 'status', 'payment_method'])->logOnlyDirty()->dontSubmitEmptyLogs();` | | |
| TASK-015 | Add relationships in ARReceipt: `customer()` belongsTo Customer, `bankAccount()` belongsTo BankAccount, `applications()` hasMany ARReceiptApplication, `invoices()` hasManyThrough ARInvoice via applications, `glEntry()` belongsTo GLEntry with withDefault(), `bankTransaction()` belongsTo BankTransaction with withDefault() | | |
| TASK-016 | Add scopes: `scopePending(Builder $query): Builder`, `scopeApplied(Builder $query): Builder`, `scopeDeposited(Builder $query): Builder`, `scopeByCustomer(Builder $query, int $customerId): Builder`, `scopeByDateRange(Builder $query, Carbon $from, Carbon $to): Builder`, `scopeUnapplied(Builder $query): Builder` returning receipts with unapplied_amount > 0 | | |
| TASK-017 | Implement `getUnappliedAmountAttribute(): float` computed attribute: Calculate from applications: `$applied = $this->applications->sum('amount_applied'); return bcsub((string)$this->total_amount, (string)$applied, 4);`. Real-time calculation | | |
| TASK-018 | Implement `canApplyToInvoice(ARInvoice $invoice): bool` method: `return $this->status->canApply() && $this->customer_id === $invoice->customer_id && $this->unapplied_amount > 0 && $invoice->status->isPayable();`. Validates compatibility (FR-AR-003) | | |
| TASK-019 | Implement static boot to calculate unapplied amount: `static::saving(function ($receipt) { if ($receipt->isDirty('applied_amount')) { $receipt->unapplied_amount = bcsub((string)$receipt->total_amount, (string)$receipt->applied_amount, 4); } });` | | |
| TASK-020 | Create `app/Domains/AccountsReceivable/Models/ARReceiptApplication.php` with namespace. Add `declare(strict_types=1);`. Use `HasFactory` trait | | |
| TASK-021 | Define $fillable: `['ar_receipt_id', 'ar_invoice_id', 'amount_applied', 'applied_at', 'applied_by', 'notes']`. Define $casts: `['amount_applied' => 'decimal:4', 'applied_at' => 'datetime']` | | |
| TASK-022 | Add relationships in ARReceiptApplication: `receipt()` belongsTo ARReceipt, `invoice()` belongsTo ARInvoice, `applier()` belongsTo User (applied_by) with withDefault() | | |
| TASK-023 | Implement validation in static boot: `static::creating(function ($application) { $receipt = $application->receipt; $invoice = $application->invoice; if ($receipt->customer_id !== $invoice->customer_id) { throw new CustomerMismatchException('Receipt and invoice must belong to same customer'); } if (bccomp((string)$application->amount_applied, (string)$invoice->outstanding_amount, 4) > 0) { throw new OverpaymentException("Application {$application->amount_applied} exceeds invoice outstanding {$invoice->outstanding_amount}"); } });` (BR-AR-001) | | |

### GOAL-003: Implement Receipt Repository and Application Service

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| PAT-005, PR-AR-001, ARCH-AR-001, GUD-011 | Create repository contracts and receipt application service with atomic transaction support, auto-matching, and optimized queries | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-024 | Create `app/Domains/AccountsReceivable/Contracts/ARReceiptRepositoryContract.php` with namespace. Add `declare(strict_types=1);`. Define interface methods: `findById(int $id): ?ARReceipt`, `findByNumber(string $number, ?string $tenantId = null): ?ARReceipt`, `getUnapplied(?string $tenantId = null): Collection`, `getForCustomer(int $customerId): Collection`, `getByDateRange(Carbon $from, Carbon $to, ?string $tenantId = null): Collection`, `findByReference(string $reference, int $customerId): ?ARReceipt`, `paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator`, `create(array $data): ARReceipt`, `update(ARReceipt $receipt, array $data): ARReceipt` | | |
| TASK-025 | Create `app/Domains/AccountsReceivable/Repositories/DatabaseARReceiptRepository.php` implementing ARReceiptRepositoryContract. Add `declare(strict_types=1);`. Constructor has no dependencies | | |
| TASK-026 | Implement `getUnapplied()` with eager loading: `return ARReceipt::with(['customer', 'bankAccount'])->unapplied()->where('tenant_id', $tenantId ?? tenant_id())->orderBy('receipt_date')->get();`. For cash application queue | | |
| TASK-027 | Implement `findByReference()` for auto-matching (GUD-011): `return ARReceipt::where('customer_id', $customerId)->where('reference', $reference)->whereIn('status', [ARReceiptStatus::PENDING, ARReceiptStatus::APPLIED])->first();`. Match by customer PO or invoice number | | |
| TASK-028 | Implement `paginate()` with filters: Support filters: `status` (string), `customer_id` (int), `payment_method` (string), `unapplied` (bool), `from_date`, `to_date`, `search` (receipt_number/check_number/reference). Build query with conditional filters and eager load relationships | | |
| TASK-029 | Create `app/Domains/AccountsReceivable/Services/ReceiptApplicationService.php` with constructor: `public function __construct(private readonly ARInvoiceRepositoryContract $invoiceRepo, private readonly ActivityLoggerContract $activityLogger) {}` | | |
| TASK-030 | Implement `applyReceiptToInvoice(ARReceipt $receipt, ARInvoice $invoice, float $amount, ?string $notes = null): ARReceiptApplication` method in ReceiptApplicationService. Step 1: Validate receipt can apply: `if (!$receipt->canApplyToInvoice($invoice)) { throw new InvalidApplicationException('Receipt cannot be applied to this invoice'); }` | | |
| TASK-031 | Step 2: Validate amount does not exceed limits (CON-010): `if (bccomp((string)$amount, (string)$receipt->unapplied_amount, 4) > 0) { throw new InsufficientReceiptException("Amount {$amount} exceeds unapplied {$receipt->unapplied_amount}"); } if (bccomp((string)$amount, (string)$invoice->outstanding_amount, 4) > 0) { throw new OverpaymentException("Amount {$amount} exceeds invoice outstanding {$invoice->outstanding_amount}"); }` | | |
| TASK-032 | Step 3: Lock records and create application atomically (ARCH-AR-001, GUD-009): `DB::transaction(function() use ($receipt, $invoice, $amount, $notes) { $receipt->lockForUpdate(); $invoice->lockForUpdate(); $application = ARReceiptApplication::create(['ar_receipt_id' => $receipt->id, 'ar_invoice_id' => $invoice->id, 'amount_applied' => $amount, 'applied_at' => now(), 'applied_by' => auth()->id(), 'notes' => $notes]); $receipt->increment('applied_amount', $amount); $invoice->increment('paid_amount', $amount); $this->updateInvoiceStatus($invoice); event(new ARReceiptAppliedEvent($application)); if ($invoice->isFullyPaid()) { event(new ARInvoiceFullyPaidEvent($invoice)); } $this->activityLogger->log("Receipt {$receipt->receipt_number} applied to invoice {$invoice->invoice_number}: {$amount}", $receipt, auth()->user()); return $application; });` | | |
| TASK-033 | Implement `updateInvoiceStatus(ARInvoice $invoice): void` private method: `$invoice->status = match(true) { $invoice->isFullyPaid() => ARInvoiceStatus::PAID, $invoice->paid_amount > 0 => ARInvoiceStatus::PARTIAL, default => $invoice->status }; $invoice->save();`. Updates status based on payment | | |
| TASK-034 | Implement `unapplyReceiptFromInvoice(ARReceiptApplication $application, string $reason): void` method. Step 1: Authorize: `if (!auth()->user()->can('unapply-receipts')) { throw new UnauthorizedException('Missing permission: unapply-receipts'); }` | | |
| TASK-035 | Step 2: Validate invoice not fully paid (CON-011): `if ($application->invoice->status === ARInvoiceStatus::PAID) { throw new CannotUnapplyException('Cannot unapply receipt from fully paid invoice without approval'); }` | | |
| TASK-036 | Step 3: Reverse application atomically: `DB::transaction(function() use ($application, $reason) { $receipt = $application->receipt; $invoice = $application->invoice; $amount = $application->amount_applied; $receipt->lockForUpdate(); $invoice->lockForUpdate(); $receipt->decrement('applied_amount', $amount); $invoice->decrement('paid_amount', $amount); $this->updateInvoiceStatus($invoice); $application->delete(); $this->activityLogger->log("Receipt unapplied from invoice: {$reason}", $receipt, auth()->user()); });` | | |
| TASK-037 | Implement `autoMatchReceiptToInvoice(ARReceipt $receipt): ?ARReceiptApplication` method for automatic matching (GUD-011): `if (!$receipt->reference) return null; $invoice = $this->invoiceRepo->findByNumber($receipt->reference, tenant_id()); if (!$invoice || $invoice->customer_id !== $receipt->customer_id) return null; if ($invoice->status->isPayable()) { $amountToApply = min($receipt->unapplied_amount, $invoice->outstanding_amount); return $this->applyReceiptToInvoice($receipt, $invoice, $amountToApply, 'Auto-matched by reference'); }` | | |
| TASK-038 | Bind contracts in AppServiceProvider: `$this->app->bind(ARReceiptRepositoryContract::class, DatabaseARReceiptRepository::class);` | | |

### GOAL-004: Implement Receipt Processing Action with GL Integration

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-AR-002, PR-AR-001, PAT-007, IR-AR-001 | Create receipt processing action with GL integration for cash receipts and bank deposit tracking | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-039 | Create `app/Domains/AccountsReceivable/Actions/CreateARReceiptAction.php` with namespace. Add `declare(strict_types=1);`. Use AsAction trait. Constructor: `public function __construct(private readonly ARReceiptRepositoryContract $receiptRepo, private readonly CustomerRepositoryContract $customerRepo, private readonly ActivityLoggerContract $activityLogger) {}` | | |
| TASK-040 | Implement `handle(array $data): ARReceipt` method in CreateARReceiptAction. Step 1: Validate customer exists: `$customer = $this->customerRepo->findById($data['customer_id']); if (!$customer) { throw new CustomerNotFoundException(); }` | | |
| TASK-041 | Step 2: Validate receipt number unique: `if ($this->receiptRepo->findByNumber($data['receipt_number'], tenant_id())) { throw new DuplicateReceiptNumberException(); }` (CON-008) | | |
| TASK-042 | Step 3: Validate bank account: `$bankAccount = BankAccount::find($data['bank_account_id']); if (!$bankAccount || !$bankAccount->is_active) { throw new InvalidBankAccountException('Bank account not found or inactive'); }` | | |
| TASK-043 | Step 4: Validate payment method compatibility (CON-012): `$paymentMethod = PaymentMethod::from($data['payment_method']); if ($paymentMethod->requiresCheckNumber() && empty($data['check_number'])) { throw new MissingCheckNumberException('Check number required for check payments'); }` | | |
| TASK-044 | Step 5: Create receipt: `$receiptData = array_merge($data, ['tenant_id' => tenant_id(), 'status' => ARReceiptStatus::PENDING, 'applied_amount' => 0, 'unapplied_amount' => $data['total_amount']]); $receipt = $this->receiptRepo->create($receiptData); $this->activityLogger->log("Receipt created: {$receipt->receipt_number}", $receipt, auth()->user()); return $receipt;` | | |
| TASK-045 | Create `app/Domains/AccountsReceivable/Actions/ProcessARReceiptAction.php` with AsAction trait (PR-AR-001). Constructor: `public function __construct(private readonly ARReceiptRepositoryContract $receiptRepo, private readonly ReceiptApplicationService $applicationService, private readonly GLEntryService $glService, private readonly ActivityLoggerContract $activityLogger) {}` | | |
| TASK-046 | Implement `handle(ARReceipt $receipt, array $applications): ARReceipt` in ProcessARReceiptAction. Validate receipt in correct status: `if (!$receipt->status->canApply()) { throw new InvalidStatusException('Receipt cannot be processed in current status'); }`. Validate applications provided (BR-AR-003): `if (empty($applications)) { throw new NoApplicationsException('Receipt must be applied to at least one invoice'); }` | | |
| TASK-047 | Process applications in transaction (ARCH-AR-001): `DB::transaction(function() use ($receipt, $applications) { $totalApplied = '0'; foreach ($applications as $app) { $invoice = ARInvoice::findOrFail($app['invoice_id']); $this->applicationService->applyReceiptToInvoice($receipt, $invoice, $app['amount'], $app['notes'] ?? null); $totalApplied = bcadd($totalApplied, (string)$app['amount'], 4); } if (bccomp($totalApplied, (string)$receipt->total_amount, 4) !== 0) { throw new ApplicationMismatchException("Applied {$totalApplied} does not match receipt {$receipt->total_amount}"); } $this->createGLEntry($receipt); $receipt->update(['status' => ARReceiptStatus::APPLIED]); event(new ARReceiptProcessedEvent($receipt)); $this->activityLogger->log("Receipt processed: {$receipt->receipt_number}", $receipt, auth()->user()); return $receipt->fresh(['applications', 'invoices']); });` | | |
| TASK-048 | Implement `createGLEntry(ARReceipt $receipt): void` private method for IR-AR-001: `$glEntryLines = [['account_id' => $receipt->bankAccount->gl_account_id, 'debit_amount' => $receipt->total_amount, 'credit_amount' => 0, 'description' => "Receipt {$receipt->receipt_number}"], ['account_id' => $receipt->customer->gl_account_id, 'debit_amount' => 0, 'credit_amount' => $receipt->total_amount, 'description' => "Payment received {$receipt->receipt_number}"]]; $glEntry = $this->glService->createEntry(['entry_date' => $receipt->receipt_date, 'description' => "AR Receipt {$receipt->receipt_number} - {$receipt->customer->customer_name}", 'reference' => $receipt->receipt_number, 'lines' => $glEntryLines]); $receipt->update(['gl_entry_id' => $glEntry->id]);`. Debit cash/bank, credit AR asset | | |
| TASK-049 | Create `app/Domains/AccountsReceivable/Actions/AutoApplyReceiptAction.php` with AsAction trait for automatic cash application (GUD-011). Constructor: `public function __construct(private readonly ARReceiptRepositoryContract $receiptRepo, private readonly ARInvoiceRepositoryContract $invoiceRepo, private readonly ReceiptApplicationService $applicationService) {}` | | |
| TASK-050 | Implement `handle(ARReceipt $receipt): array` in AutoApplyReceiptAction. Step 1: Try reference-based matching: `$application = $this->applicationService->autoMatchReceiptToInvoice($receipt); if ($application) { return ['method' => 'reference_match', 'applications' => [$application]]; }` | | |
| TASK-051 | Step 2: Apply to oldest invoices if no reference match: `$invoices = $this->invoiceRepo->getByCustomer($receipt->customer_id)->where('status', ARInvoiceStatus::POSTED)->sortBy('due_date'); $applications = []; $remainingAmount = $receipt->unapplied_amount; foreach ($invoices as $invoice) { if ($remainingAmount <= 0) break; $amountToApply = min($remainingAmount, $invoice->outstanding_amount); $applications[] = $this->applicationService->applyReceiptToInvoice($receipt, $invoice, $amountToApply, 'Auto-applied to oldest invoice'); $remainingAmount = bcsub((string)$remainingAmount, (string)$amountToApply, 4); } return ['method' => 'oldest_first', 'applications' => $applications];` | | |
| TASK-052 | Create events: `app/Domains/AccountsReceivable/Events/ARReceiptProcessedEvent.php` with properties: `public readonly ARReceipt $receipt`. Similarly create `ARReceiptAppliedEvent.php` with `public readonly ARReceiptApplication $application`, and `ARInvoiceFullyPaidEvent.php` with `public readonly ARInvoice $invoice` (shared with PLAN01) | | |
| TASK-053 | Create exceptions: `app/Domains/AccountsReceivable/Exceptions/InvalidApplicationException.php`, `InsufficientReceiptException.php`, `OverpaymentException.php`, `CannotUnapplyException.php`, `CustomerMismatchException.php`, `DuplicateReceiptNumberException.php`, `InvalidBankAccountException.php`, `MissingCheckNumberException.php`, `NoApplicationsException.php`, `ApplicationMismatchException.php`, `CustomerNotFoundException.php` | | |

### GOAL-005: Implement Banking Integration for Deposit Reconciliation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| IR-AR-001, CON-012, GUD-010 | Create integration with Banking module for deposit tracking, receipt reconciliation, and bank transaction matching | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-054 | Create `app/Domains/AccountsReceivable/Actions/MatchReceiptToBankTransactionAction.php` with AsAction trait. Constructor: `public function __construct(private readonly ARReceiptRepositoryContract $receiptRepo, private readonly ActivityLoggerContract $activityLogger) {}` | | |
| TASK-055 | Implement `handle(ARReceipt $receipt, int $bankTransactionId): ARReceipt` in MatchReceiptToBankTransactionAction. Step 1: Validate receipt is applied: `if ($receipt->status !== ARReceiptStatus::APPLIED) { throw new InvalidStatusException('Only applied receipts can be matched to bank transactions'); }` | | |
| TASK-056 | Step 2: Validate bank transaction exists: `$bankTransaction = BankTransaction::find($bankTransactionId); if (!$bankTransaction) { throw new BankTransactionNotFoundException(); } if ($bankTransaction->bank_account_id !== $receipt->bank_account_id) { throw new BankAccountMismatchException('Bank transaction must be for same bank account'); }` | | |
| TASK-057 | Step 3: Update receipt with bank transaction link: `$receipt->update(['bank_transaction_id' => $bankTransactionId, 'status' => ARReceiptStatus::DEPOSITED]); $this->activityLogger->log("Receipt matched to bank transaction {$bankTransaction->reference}", $receipt, auth()->user()); return $receipt->fresh(['bankTransaction']);` | | |
| TASK-058 | Create `app/Domains/AccountsReceivable/Listeners/CreateDepositFromReceiptListener.php` listening to `ARReceiptProcessedEvent`. In handle() method: `if ($event->receipt->payment_method->requiresBankReconciliation()) { dispatch(new CreateBankDepositJob($event->receipt)); }`. Queue job for bank deposit creation | | |
| TASK-059 | Create `app/Jobs/CreateBankDepositJob.php` implementing ShouldQueue. In handle(): `$receipt = ARReceipt::find($this->receiptId); BankTransaction::create(['tenant_id' => tenant_id(), 'bank_account_id' => $receipt->bank_account_id, 'transaction_date' => $receipt->receipt_date, 'transaction_type' => 'deposit', 'amount' => $receipt->total_amount, 'description' => "AR Receipt {$receipt->receipt_number}", 'reference' => $receipt->receipt_number, 'status' => 'pending']); activity()->log("Bank deposit created for receipt {$receipt->receipt_number}");`. Create pending deposit for reconciliation | | |
| TASK-060 | Create `app/Domains/AccountsReceivable/Services/ReceiptReconciliationService.php` with constructor: `public function __construct(private readonly ARReceiptRepositoryContract $receiptRepo) {}` | | |
| TASK-061 | Implement `matchUnreconciled(): array` method: Query unreconciled receipts (status APPLIED, bank_transaction_id NULL) and pending bank transactions. Match by: 1) Date range (±3 days), 2) Amount match (exact), 3) Bank account match. Return array of matches: `['receipt_id' => $receiptId, 'bank_transaction_id' => $txId, 'confidence' => 'high'|'medium'|'low']` | | |
| TASK-062 | Implement `getUnreconciledReceipts(): Collection` method: `return $this->receiptRepo->query()->where('status', ARReceiptStatus::APPLIED)->whereNull('bank_transaction_id')->whereIn('payment_method', [PaymentMethod::CHECK, PaymentMethod::WIRE, PaymentMethod::CARD])->orderBy('receipt_date')->get();`. For reconciliation queue | | |

## 3. Alternatives

- **ALT-006**: Allow receipt application to exceed invoice amount (create credit) - **Rejected** because violates BR-AR-001, creates reconciliation issues
- **ALT-007**: Automatic receipt application always (oldest invoice first) - **Deferred** to configurable option, manual control preferred initially
- **ALT-008**: Store check images/scanned documents - **Deferred** to future enhancement (document management module)
- **ALT-009**: Accept partial receipts (amount less than invoice) - **Accepted** and implemented as core feature (FR-AR-003)
- **ALT-010**: Real-time bank feed integration for auto-matching - **Deferred** to future enhancement, batch reconciliation sufficient for MVP

## 4. Dependencies

**Package Dependencies:**
- **DEP-001**: `laravel/framework` ^12.0
- **DEP-002**: `spatie/laravel-activitylog` ^4.0 (audit logging)
- **DEP-003**: `lorisleiva/laravel-actions` ^2.0 (action pattern)
- **DEP-004**: `brick/math` ^0.12 (decimal precision)

**Internal Dependencies:**
- **DEP-017**: PRD01-SUB12-PLAN01 (AR Invoice Management) - MUST be completed first
- **DEP-018**: PRD01-SUB10 (Banking Module) - For bank account and transaction integration
- **DEP-019**: PRD01-SUB08 (General Ledger) - For GL receipt posting
- **DEP-020**: PRD01-SUB02 (Authentication & Authorization) - For user authorization

**Infrastructure:**
- **DEP-021**: Queue worker for bank deposit creation jobs
- **DEP-022**: Notification system (email) for receipt confirmations (optional)

## 5. Files

**Migrations:**
- `database/migrations/YYYY_MM_DD_HHMMSS_create_ar_receipts_table.php` - Receipts
- `database/migrations/YYYY_MM_DD_HHMMSS_create_ar_receipt_applications_table.php` - Applications

**Models:**
- `app/Domains/AccountsReceivable/Models/ARReceipt.php` - Receipt header
- `app/Domains/AccountsReceivable/Models/ARReceiptApplication.php` - Receipt-invoice link

**Enums:**
- `app/Domains/AccountsReceivable/Enums/ARReceiptStatus.php` - Receipt lifecycle
- `app/Domains/AccountsReceivable/Enums/PaymentMethod.php` - Payment types

**Contracts:**
- `app/Domains/AccountsReceivable/Contracts/ARReceiptRepositoryContract.php` - Receipt repository

**Repositories:**
- `app/Domains/AccountsReceivable/Repositories/DatabaseARReceiptRepository.php` - Receipt repo

**Services:**
- `app/Domains/AccountsReceivable/Services/ReceiptApplicationService.php` - Application logic
- `app/Domains/AccountsReceivable/Services/ReceiptReconciliationService.php` - Bank reconciliation

**Actions:**
- `app/Domains/AccountsReceivable/Actions/CreateARReceiptAction.php` - Create receipt
- `app/Domains/AccountsReceivable/Actions/ProcessARReceiptAction.php` - Process receipt
- `app/Domains/AccountsReceivable/Actions/AutoApplyReceiptAction.php` - Auto cash application
- `app/Domains/AccountsReceivable/Actions/MatchReceiptToBankTransactionAction.php` - Bank matching

**Events:**
- `app/Domains/AccountsReceivable/Events/ARReceiptProcessedEvent.php` - Receipt processed
- `app/Domains/AccountsReceivable/Events/ARReceiptAppliedEvent.php` - Receipt applied
- `app/Domains/AccountsReceivable/Events/ARInvoiceFullyPaidEvent.php` - Invoice paid (shared)

**Listeners:**
- `app/Domains/AccountsReceivable/Listeners/CreateDepositFromReceiptListener.php` - Bank deposit

**Jobs:**
- `app/Jobs/CreateBankDepositJob.php` - Async deposit creation

**Exceptions:**
- `app/Domains/AccountsReceivable/Exceptions/InvalidApplicationException.php` - Invalid application
- `app/Domains/AccountsReceivable/Exceptions/InsufficientReceiptException.php` - Insufficient amount
- `app/Domains/AccountsReceivable/Exceptions/OverpaymentException.php` - Overpayment
- `app/Domains/AccountsReceivable/Exceptions/CannotUnapplyException.php` - Cannot unapply
- `app/Domains/AccountsReceivable/Exceptions/CustomerMismatchException.php` - Customer mismatch
- `app/Domains/AccountsReceivable/Exceptions/DuplicateReceiptNumberException.php` - Duplicate number
- `app/Domains/AccountsReceivable/Exceptions/InvalidBankAccountException.php` - Invalid bank
- `app/Domains/AccountsReceivable/Exceptions/MissingCheckNumberException.php` - Missing check #
- `app/Domains/AccountsReceivable/Exceptions/NoApplicationsException.php` - No applications
- `app/Domains/AccountsReceivable/Exceptions/ApplicationMismatchException.php` - Application mismatch
- `app/Domains/AccountsReceivable/Exceptions/CustomerNotFoundException.php` - Customer not found
- `app/Domains/AccountsReceivable/Exceptions/BankTransactionNotFoundException.php` - Bank tx not found
- `app/Domains/AccountsReceivable/Exceptions/BankAccountMismatchException.php` - Bank account mismatch

**Service Provider (updated):**
- `app/Providers/AppServiceProvider.php` - Repository bindings

## 6. Testing

**Unit Tests (15 tests):**
- **TEST-001**: `test_receipt_status_enum_has_all_cases` - Verify 4 status cases
- **TEST-002**: `test_payment_method_enum_identifies_check_requirement` - Test requiresCheckNumber()
- **TEST-003**: `test_receipt_calculates_unapplied_amount` - Test getUnappliedAmountAttribute()
- **TEST-004**: `test_receipt_validates_can_apply_to_invoice` - Test canApplyToInvoice()
- **TEST-005**: `test_receipt_application_validates_customer_match` - Test CustomerMismatchException
- **TEST-006**: `test_receipt_application_prevents_overpayment` - Test BR-AR-001
- **TEST-007**: `test_receipt_application_updates_invoice_status` - Test status change
- **TEST-008**: `test_repository_gets_unapplied_receipts` - Test getUnapplied()
- **TEST-009**: `test_repository_filters_by_customer` - Test getForCustomer()
- **TEST-010**: `test_auto_match_by_reference` - Test reference matching (GUD-011)
- **TEST-011**: `test_oldest_first_application_logic` - Test auto-apply oldest
- **TEST-012**: `test_receipt_factory_generates_valid_data` - Test factory
- **TEST-013**: `test_receipt_scope_unapplied_works` - Test scopeUnapplied()
- **TEST-014**: `test_unapplied_amount_calculated_from_applications` - Test real-time calculation
- **TEST-015**: `test_payment_method_electronic_identification` - Test isElectronic()

**Feature Tests (18 tests):**
- **TEST-016**: `test_create_receipt_action_validates_customer` - Test customer validation
- **TEST-017**: `test_create_receipt_validates_bank_account` - Test bank validation
- **TEST-018**: `test_create_receipt_requires_check_number_for_checks` - Test CON-012
- **TEST-019**: `test_process_receipt_applies_to_multiple_invoices` - Test FR-AR-003
- **TEST-020**: `test_process_receipt_creates_gl_entry` - Test IR-AR-001
- **TEST-021**: `test_process_receipt_validates_total_matches` - Test CON-010
- **TEST-022**: `test_process_receipt_dispatches_events` - Test ARReceiptProcessedEvent
- **TEST-023**: `test_apply_receipt_validates_amount_limits` - Test BR-AR-001
- **TEST-024**: `test_apply_receipt_updates_invoice_status` - Test PARTIAL/PAID status
- **TEST-025**: `test_apply_receipt_dispatches_fully_paid_event` - Test ARInvoiceFullyPaidEvent
- **TEST-026**: `test_unapply_receipt_requires_permission` - Test authorization
- **TEST-027**: `test_cannot_unapply_from_paid_invoice` - Test CON-011
- **TEST-028**: `test_auto_apply_receipt_by_reference` - Test GUD-011
- **TEST-029**: `test_auto_apply_receipt_oldest_first` - Test fallback logic
- **TEST-030**: `test_match_receipt_to_bank_transaction` - Test bank matching
- **TEST-031**: `test_create_bank_deposit_from_receipt` - Test IR-AR-001
- **TEST-032**: `test_activity_log_records_receipt_operations` - Test LogsActivity
- **TEST-033**: `test_receipt_reconciliation_matching` - Test auto-reconciliation

**Integration Tests (10 tests):**
- **TEST-034**: `test_receipt_application_atomic_transaction` - Test ARCH-AR-001
- **TEST-035**: `test_pessimistic_locking_prevents_race_condition` - Test GUD-009
- **TEST-036**: `test_gl_entry_balances_after_receipt` - Test debit = credit
- **TEST-037**: `test_customer_balance_reflects_receipts` - Test BR-AR-004
- **TEST-038**: `test_eager_loading_prevents_n_plus_one` - Test query count
- **TEST-039**: `test_repository_binding_resolves_correctly` - Test service container
- **TEST-040**: `test_receipt_lifecycle_pending_to_deposited` - Test full workflow
- **TEST-041**: `test_receipt_integrates_with_banking` - Test banking integration
- **TEST-042**: `test_bank_deposit_job_queued` - Test async processing
- **TEST-043**: `test_receipt_amount_uses_bcmath_precision` - Test 4 decimal precision

**Performance Tests (2 tests):**
- **TEST-044**: `test_receipt_processing_under_2_seconds` - Test PR-AR-001
- **TEST-045**: `test_receipt_application_concurrent_no_deadlock` - Test locking performance

## 7. Risks & Assumptions

**Risks:**
- **RISK-006**: Concurrent receipt application causes double payment - **Mitigation**: Use pessimistic locking (lockForUpdate), unique constraint on receipt-invoice pair
- **RISK-007**: Bank reconciliation timeout on large datasets - **Mitigation**: Process in batches, index on bank_account_id and transaction_date
- **RISK-008**: Auto-matching creates incorrect applications - **Mitigation**: Require manual approval for auto-matched receipts, log all matches
- **RISK-009**: GL entry creation fails mid-receipt causing inconsistency - **Mitigation**: Database transaction wrapping all receipt operations
- **RISK-010**: Check number reused across tenants - **Mitigation**: Unique constraint on (tenant_id, bank_account_id, check_number)

**Assumptions:**
- **ASSUMPTION-006**: Most receipts apply to single invoice, partial payments are exception
- **ASSUMPTION-007**: Bank reconciliation turnaround time < 3 days for most receipts
- **ASSUMPTION-008**: Payment methods distributed: 40% check, 40% wire, 15% card, 5% cash
- **ASSUMPTION-009**: Reference-based auto-matching works for 60%+ receipts
- **ASSUMPTION-010**: Electronic payments (wire, card) clear same-day, checks next business day

## 8. KIV for future implementations

- **KIV-009**: Implement recurring receipt generation (subscription payments)
- **KIV-010**: Add receipt scheduling/future-dated receipts
- **KIV-011**: Implement lock box processing (batch receipt import from bank files)
- **KIV-012**: Add card payment gateway integration (Stripe, PayPal)
- **KIV-013**: Implement receipt reversal workflow
- **KIV-014**: Add customer payment portal (self-service)
- **KIV-015**: Implement early payment discount application
- **KIV-016**: Add payment forecasting and cash flow projection
- **KIV-017**: Implement receipt printing/email templates
- **KIV-018**: Add receipt confirmation tracking (email read receipts)

## 9. Related PRD / Further Reading

- **Master PRD:** [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD:** [../prd/prd-01/PRD01-SUB12-ACCOUNTS-RECEIVABLE.md](../prd/prd-01/PRD01-SUB12-ACCOUNTS-RECEIVABLE.md)
- **Related Sub-PRDs:**
  - PRD01-SUB08 (General Ledger) - GL receipt posting
  - PRD01-SUB10 (Banking) - Bank account and deposit integration
  - PRD01-SUB02 (Authentication & Authorization) - User authorization
- **Related Plans:**
  - PRD01-SUB12-PLAN01 (AR Invoice Management) - Prerequisites
- **External Documentation:**
  - Cash Application Best Practices: https://www.accountingtools.com/articles/cash-application-best-practices
  - Bank Reconciliation: https://www.investopedia.com/terms/b/bank-reconciliation-statement.asp
  - Lockbox Processing: https://www.treasurytoday.com/topics/cash-management/lockbox-services
