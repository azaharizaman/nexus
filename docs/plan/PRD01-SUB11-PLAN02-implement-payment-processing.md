---
plan: Implement Accounts Payable Payment Processing with Batch Payments and Application
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, accounts-payable, payment-processing, batch-payments, banking-integration, approval-workflow]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan delivers the payment processing system for Accounts Payable with batch payment capability, intelligent payment application to multiple invoices, dual authorization for large payments, and banking integration for automated disbursements. It implements payment scheduling, partial payment allocation, unapplied payment tracking, and integration with the Banking module for check printing and electronic payments. This plan completes the AP system by automating the vendor payment workflow that reduces manual payment processing time by 80%.

## 1. Requirements & Constraints

**Functional Requirements:**
- **FR-AP-002**: Implement **payment processing** with batch payment capability
- **FR-AP-003**: Support **payment application** to multiple invoices with partial payments

**Business Rules:**
- **BR-AP-001**: Payment amounts must **not exceed the invoice outstanding balance**
- **BR-AP-003**: Payments MUST reference at least one **valid vendor invoice**
- **BR-AP-004**: Vendor balances MUST equal sum of **unpaid invoices - unapplied payments**

**Data Requirements:**
- **DR-AP-002**: Store AP payment metadata: payment_number, payment_date, bank_account_id, total_amount, status
- **DR-AP-003**: Store payment applications: ap_payment_id, ap_invoice_id, amount_applied

**Integration Requirements:**
- **IR-AP-001**: Integrate with **Banking module** for automated disbursements and bank reconciliation

**Performance Requirements:**
- **PR-AP-001**: Process **batch payments (1000 invoices)** in under 5 seconds using queue jobs

**Security Requirements:**
- **SR-AP-001**: Enforce **role-based access** for payment approval based on amount thresholds
- **SR-AP-002**: Require **dual authorization** for payments exceeding configured limit

**Architecture Requirements:**
- **ARCH-AP-001**: Use **database transactions** to ensure atomicity when applying payments to invoices

**Events:**
- **EV-AP-002**: Dispatch `APPaymentProcessedEvent` when payment is processed
- **EV-AP-003**: Dispatch `APPaymentAppliedEvent` when payment is applied to invoice
- **EV-AP-004**: Dispatch `APInvoiceFullyPaidEvent` when invoice is fully paid

**Constraints:**
- **CON-007**: Payment numbers must be unique per tenant
- **CON-008**: Payment date must be >= invoice date for all applied invoices
- **CON-009**: Total payment application cannot exceed payment amount
- **CON-010**: Cannot unapply payment from paid invoice without approval
- **CON-011**: Payments require approval before disbursement if above threshold
- **CON-012**: Payment method must match bank account capability (check, wire, ACH)

**Guidelines:**
- **GUD-006**: Use PSR-12 coding standards, strict types, repository pattern, and Laravel Actions
- **GUD-007**: Log all payment operations using Spatie Activity Log
- **GUD-008**: Process batch payments in chunks of 100 to prevent memory issues
- **GUD-009**: Use pessimistic locking during payment application to prevent race conditions
- **GUD-010**: Generate payment approval notifications asynchronously

**Patterns:**
- **PAT-005**: Repository pattern with APPaymentRepositoryContract
- **PAT-006**: Strategy pattern for different payment methods (check, wire, ACH, EFT)
- **PAT-007**: Laravel Actions for ProcessPaymentAction, ApplyPaymentAction
- **PAT-008**: Queue jobs for batch payment processing and approval notifications

## 2. Implementation Steps

### GOAL-001: Create Payment and Payment Application Database Schema

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-AP-002, DR-AP-002, DR-AP-003, ARCH-AP-001 | Implement ap_payments, ap_payment_applications, and ap_payment_approvals tables with proper constraints and atomicity support | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `database/migrations/YYYY_MM_DD_HHMMSS_create_ap_payments_table.php` with namespace. Add `declare(strict_types=1);`. Use anonymous migration class format: `return new class extends Migration` | | |
| TASK-002 | In up() method, create `ap_payments` table with columns: `id` (BIGINT AUTO_INCREMENT PRIMARY KEY), `tenant_id` (UUID NOT NULL), `payment_number` (VARCHAR 50 NOT NULL), `vendor_id` (BIGINT NOT NULL), `payment_date` (DATE NOT NULL), `bank_account_id` (BIGINT NOT NULL - source bank account), `payment_method` (VARCHAR 20 NOT NULL - 'check', 'wire', 'ach', 'eft'), `total_amount` (DECIMAL 20,4 NOT NULL), `applied_amount` (DECIMAL 20,4 NOT NULL DEFAULT 0), `unapplied_amount` (DECIMAL 20,4 NOT NULL DEFAULT 0 - computed: total - applied), `status` (VARCHAR 20 NOT NULL DEFAULT 'draft' - draft/pending_approval/approved/processed/void), `check_number` (VARCHAR 50 NULL - for check payments), `wire_reference` (VARCHAR 100 NULL - for wire transfers), `gl_entry_id` (BIGINT NULL - link to GL disbursement entry), `notes` (TEXT NULL), `approved_by` (BIGINT NULL), `approved_at` (TIMESTAMP NULL), timestamps, soft deletes | | |
| TASK-003 | Add indexes on ap_payments: `INDEX idx_ap_pay_tenant (tenant_id)`, `UNIQUE KEY uk_ap_pay_number (tenant_id, payment_number)`, `INDEX idx_ap_pay_vendor (vendor_id)`, `INDEX idx_ap_pay_status (status)`, `INDEX idx_ap_pay_date (payment_date)`, `INDEX idx_ap_pay_bank (bank_account_id)`, `INDEX idx_ap_pay_gl (gl_entry_id)` | | |
| TASK-004 | Add foreign keys on ap_payments: `FOREIGN KEY fk_ap_pay_tenant (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE`, `FOREIGN KEY fk_ap_pay_vendor (vendor_id) REFERENCES vendors(id) ON DELETE RESTRICT`, `FOREIGN KEY fk_ap_pay_bank (bank_account_id) REFERENCES bank_accounts(id) ON DELETE RESTRICT`, `FOREIGN KEY fk_ap_pay_gl (gl_entry_id) REFERENCES gl_entries(id) ON DELETE SET NULL`, `FOREIGN KEY fk_ap_pay_approved_by (approved_by) REFERENCES users(id)` | | |
| TASK-005 | Create `ap_payment_applications` table with columns: `id` (BIGINT AUTO_INCREMENT PRIMARY KEY), `ap_payment_id` (BIGINT NOT NULL), `ap_invoice_id` (BIGINT NOT NULL), `amount_applied` (DECIMAL 20,4 NOT NULL), `applied_at` (TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP), `applied_by` (BIGINT NOT NULL), `notes` (TEXT NULL), timestamps | | |
| TASK-006 | Add indexes on ap_payment_applications: `INDEX idx_ap_app_payment (ap_payment_id)`, `INDEX idx_ap_app_invoice (ap_invoice_id)`, `INDEX idx_ap_app_date (applied_at)`. Add unique constraint to prevent duplicate applications: `UNIQUE KEY uk_ap_app_payment_invoice (ap_payment_id, ap_invoice_id)` | | |
| TASK-007 | Add foreign keys on ap_payment_applications: `FOREIGN KEY fk_ap_app_payment (ap_payment_id) REFERENCES ap_payments(id) ON DELETE CASCADE`, `FOREIGN KEY fk_ap_app_invoice (ap_invoice_id) REFERENCES ap_invoices(id) ON DELETE CASCADE`, `FOREIGN KEY fk_ap_app_applied_by (applied_by) REFERENCES users(id)` | | |
| TASK-008 | Create `ap_payment_approvals` table for dual authorization (SR-AP-002) with columns: `id` (BIGINT AUTO_INCREMENT PRIMARY KEY), `ap_payment_id` (BIGINT NOT NULL), `approval_level` (INTEGER NOT NULL DEFAULT 1), `required_role` (VARCHAR 50 NOT NULL - 'ap_manager', 'finance_director'), `approved_by` (BIGINT NULL), `approved_at` (TIMESTAMP NULL), `status` (VARCHAR 20 NOT NULL DEFAULT 'pending' - pending/approved/rejected), `notes` (TEXT NULL), timestamps | | |
| TASK-009 | Add indexes on ap_payment_approvals: `INDEX idx_ap_approval_payment (ap_payment_id)`, `INDEX idx_ap_approval_status (status)`, `INDEX idx_ap_approval_level (approval_level)` | | |
| TASK-010 | Add foreign keys on ap_payment_approvals: `FOREIGN KEY fk_ap_approval_payment (ap_payment_id) REFERENCES ap_payments(id) ON DELETE CASCADE`, `FOREIGN KEY fk_ap_approval_approved_by (approved_by) REFERENCES users(id)` | | |
| TASK-011 | In down() method, drop tables in reverse order: `Schema::dropIfExists('ap_payment_approvals')`, then ap_payment_applications, then ap_payments | | |

### GOAL-002: Create Payment Models with Status and Approval Management

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-AP-002, FR-AP-003, SR-AP-002, CON-009 | Implement APPayment, APPaymentApplication, and APPaymentApproval models with validation and relationship definitions | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-012 | Create `app/Domains/AccountsPayable/Models/APPayment.php` with namespace. Add `declare(strict_types=1);`. Import traits: `use BelongsToTenant, HasFactory, SoftDeletes, LogsActivity;` | | |
| TASK-013 | Define $fillable array: `['tenant_id', 'payment_number', 'vendor_id', 'payment_date', 'bank_account_id', 'payment_method', 'total_amount', 'applied_amount', 'unapplied_amount', 'status', 'check_number', 'wire_reference', 'gl_entry_id', 'notes', 'approved_by', 'approved_at']` | | |
| TASK-014 | Define $casts array: `['payment_date' => 'date', 'total_amount' => 'decimal:4', 'applied_amount' => 'decimal:4', 'unapplied_amount' => 'decimal:4', 'status' => APPaymentStatus::class, 'payment_method' => PaymentMethod::class, 'approved_at' => 'datetime', 'deleted_at' => 'datetime']` | | |
| TASK-015 | Create `app/Domains/AccountsPayable/Enums/APPaymentStatus.php` as string-backed enum with cases: `DRAFT = 'draft'`, `PENDING_APPROVAL = 'pending_approval'`, `APPROVED = 'approved'`, `PROCESSED = 'processed'`, `VOID = 'void'`. Implement `label(): string`, `canApply(): bool` (DRAFT or APPROVED), `requiresApproval(): bool` (PENDING_APPROVAL), `isComplete(): bool` (PROCESSED or VOID) | | |
| TASK-016 | Create `app/Domains/AccountsPayable/Enums/PaymentMethod.php` as string-backed enum with cases: `CHECK = 'check'`, `WIRE = 'wire'`, `ACH = 'ach'`, `EFT = 'eft'`. Implement `label(): string`, `requiresCheckNumber(): bool` (CHECK only), `isElectronic(): bool` (WIRE, ACH, EFT) | | |
| TASK-017 | Implement `getActivitylogOptions(): LogOptions` in APPayment: `return LogOptions::defaults()->logOnly(['payment_number', 'vendor_id', 'payment_date', 'total_amount', 'status', 'approved_by'])->logOnlyDirty()->dontSubmitEmptyLogs();` | | |
| TASK-018 | Add relationships in APPayment: `vendor()` belongsTo Vendor, `bankAccount()` belongsTo BankAccount, `applications()` hasMany APPaymentApplication, `invoices()` hasManyThrough APInvoice via applications, `glEntry()` belongsTo GLEntry with withDefault(), `approver()` belongsTo User (approved_by) with withDefault(), `approvals()` hasMany APPaymentApproval | | |
| TASK-019 | Add scopes: `scopeDraft(Builder $query): Builder`, `scopePendingApproval(Builder $query): Builder`, `scopeApproved(Builder $query): Builder`, `scopeProcessed(Builder $query): Builder`, `scopeByVendor(Builder $query, int $vendorId): Builder`, `scopeByDateRange(Builder $query, Carbon $from, Carbon $to): Builder` | | |
| TASK-020 | Implement `getUnappliedAmountAttribute(): float` computed attribute: Calculate from applications: `$applied = $this->applications->sum('amount_applied'); return $this->total_amount - $applied;`. Real-time calculation | | |
| TASK-021 | Implement `canApplyToInvoice(APInvoice $invoice): bool` method: `return $this->status->canApply() && $this->vendor_id === $invoice->vendor_id && $this->unapplied_amount > 0 && $invoice->status->isPayable();`. Validates compatibility | | |
| TASK-022 | Implement `requiresApproval(): bool` method for SR-AP-001: `$threshold = config('ap.payment_approval_threshold', 10000); return $this->total_amount >= $threshold;`. Configurable threshold | | |
| TASK-023 | Implement static boot to calculate unapplied amount: `static::saving(function ($payment) { if ($payment->isDirty('applied_amount')) { $payment->unapplied_amount = bcsub((string)$payment->total_amount, (string)$payment->applied_amount, 4); } });` | | |
| TASK-024 | Create `app/Domains/AccountsPayable/Models/APPaymentApplication.php` with namespace. Add `declare(strict_types=1);`. Use `HasFactory` trait | | |
| TASK-025 | Define $fillable: `['ap_payment_id', 'ap_invoice_id', 'amount_applied', 'applied_at', 'applied_by', 'notes']`. Define $casts: `['amount_applied' => 'decimal:4', 'applied_at' => 'datetime']` | | |
| TASK-026 | Add relationships in APPaymentApplication: `payment()` belongsTo APPayment, `invoice()` belongsTo APInvoice, `applier()` belongsTo User (applied_by) with withDefault() | | |
| TASK-027 | Implement validation in static boot: `static::creating(function ($application) { $payment = $application->payment; $invoice = $application->invoice; if ($payment->vendor_id !== $invoice->vendor_id) { throw new VendorMismatchException('Payment and invoice must belong to same vendor'); } if (bccomp((string)$application->amount_applied, (string)$invoice->outstanding_amount, 4) > 0) { throw new OverpaymentException("Application {$application->amount_applied} exceeds invoice outstanding {$invoice->outstanding_amount}"); } });` (BR-AP-001) | | |
| TASK-028 | Create `app/Domains/AccountsPayable/Models/APPaymentApproval.php` with namespace. Define relationships: `payment()` belongsTo APPayment, `approver()` belongsTo User (approved_by) | | |
| TASK-029 | Add scope in APPaymentApproval: `scopePending(Builder $query): Builder` returning `$query->where('status', 'pending')` | | |

### GOAL-003: Implement Payment Repository and Application Service

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| PAT-005, PR-AP-001, ARCH-AP-001 | Create repository contracts and payment application service with atomic transaction support and optimized queries | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-030 | Create `app/Domains/AccountsPayable/Contracts/APPaymentRepositoryContract.php` with namespace. Add `declare(strict_types=1);`. Define interface methods: `findById(int $id): ?APPayment`, `findByNumber(string $number, ?string $tenantId = null): ?APPayment`, `getPendingApproval(?string $tenantId = null): Collection`, `getForVendor(int $vendorId): Collection`, `getByDateRange(Carbon $from, Carbon $to, ?string $tenantId = null): Collection`, `paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator`, `create(array $data): APPayment`, `update(APPayment $payment, array $data): APPayment` | | |
| TASK-031 | Create `app/Domains/AccountsPayable/Repositories/DatabaseAPPaymentRepository.php` implementing APPaymentRepositoryContract. Add `declare(strict_types=1);`. Constructor has no dependencies | | |
| TASK-032 | Implement `getPendingApproval()` with eager loading: `return APPayment::with(['vendor', 'bankAccount', 'approvals'])->pendingApproval()->where('tenant_id', $tenantId ?? tenant_id())->orderBy('payment_date')->get();`. For approval queue | | |
| TASK-033 | Implement `paginate()` with filters: Support filters: `status` (string), `vendor_id` (int), `payment_method` (string), `pending_approval` (bool), `from_date`, `to_date`, `search` (payment_number/check_number). Build query with conditional filters and eager load relationships | | |
| TASK-034 | Create `app/Domains/AccountsPayable/Services/PaymentApplicationService.php` with constructor: `public function __construct(private readonly APInvoiceRepositoryContract $invoiceRepo, private readonly ActivityLoggerContract $activityLogger) {}` | | |
| TASK-035 | Implement `applyPaymentToInvoice(APPayment $payment, APInvoice $invoice, float $amount, ?string $notes = null): APPaymentApplication` method in PaymentApplicationService. Step 1: Validate payment can apply: `if (!$payment->canApplyToInvoice($invoice)) { throw new InvalidApplicationException('Payment cannot be applied to this invoice'); }` | | |
| TASK-036 | Step 2: Validate amount does not exceed limits (CON-009): `if (bccomp((string)$amount, (string)$payment->unapplied_amount, 4) > 0) { throw new InsufficientPaymentException("Amount {$amount} exceeds unapplied {$payment->unapplied_amount}"); } if (bccomp((string)$amount, (string)$invoice->outstanding_amount, 4) > 0) { throw new OverpaymentException("Amount {$amount} exceeds invoice outstanding {$invoice->outstanding_amount}"); }` | | |
| TASK-037 | Step 3: Lock records and create application atomically (ARCH-AP-001, GUD-009): `DB::transaction(function() use ($payment, $invoice, $amount, $notes) { $payment->lockForUpdate(); $invoice->lockForUpdate(); $application = APPaymentApplication::create(['ap_payment_id' => $payment->id, 'ap_invoice_id' => $invoice->id, 'amount_applied' => $amount, 'applied_at' => now(), 'applied_by' => auth()->id(), 'notes' => $notes]); $payment->increment('applied_amount', $amount); $invoice->increment('paid_amount', $amount); $this->updateInvoiceStatus($invoice); event(new APPaymentAppliedEvent($application)); if ($invoice->isFullyPaid()) { event(new APInvoiceFullyPaidEvent($invoice)); } $this->activityLogger->log("Payment {$payment->payment_number} applied to invoice {$invoice->invoice_number}: {$amount}", $payment, auth()->user()); return $application; });` | | |
| TASK-038 | Implement `updateInvoiceStatus(APInvoice $invoice): void` private method: `$invoice->status = match(true) { $invoice->isFullyPaid() => APInvoiceStatus::PAID, $invoice->paid_amount > 0 => APInvoiceStatus::PARTIAL, default => $invoice->status }; $invoice->save();`. Updates status based on payment | | |
| TASK-039 | Implement `unapplyPaymentFromInvoice(APPaymentApplication $application, string $reason): void` method. Step 1: Authorize: `if (!auth()->user()->can('unapply-payments')) { throw new UnauthorizedException('Missing permission: unapply-payments'); }` | | |
| TASK-040 | Step 2: Validate invoice not fully paid (CON-010): `if ($application->invoice->status === APInvoiceStatus::PAID) { throw new CannotUnapplyException('Cannot unapply payment from fully paid invoice without approval'); }` | | |
| TASK-041 | Step 3: Reverse application atomically: `DB::transaction(function() use ($application, $reason) { $payment = $application->payment; $invoice = $application->invoice; $amount = $application->amount_applied; $payment->lockForUpdate(); $invoice->lockForUpdate(); $payment->decrement('applied_amount', $amount); $invoice->decrement('paid_amount', $amount); $this->updateInvoiceStatus($invoice); $application->delete(); $this->activityLogger->log("Payment unapplied from invoice: {$reason}", $payment, auth()->user()); });` | | |
| TASK-042 | Bind contracts in AppServiceProvider: `$this->app->bind(APPaymentRepositoryContract::class, DatabaseAPPaymentRepository::class);` | | |

### GOAL-004: Implement Payment Processing Action with Batch Support

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-AP-002, PR-AP-001, PAT-007, PAT-008, GUD-008 | Create payment processing action with batch capability, queue job support, and GL integration for disbursements | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-043 | Create `app/Domains/AccountsPayable/Actions/CreateAPPaymentAction.php` with namespace. Add `declare(strict_types=1);`. Use AsAction trait. Constructor: `public function __construct(private readonly APPaymentRepositoryContract $paymentRepo, private readonly VendorRepositoryContract $vendorRepo, private readonly ActivityLoggerContract $activityLogger) {}` | | |
| TASK-044 | Implement `handle(array $data): APPayment` method in CreateAPPaymentAction. Step 1: Validate vendor exists: `$vendor = $this->vendorRepo->findById($data['vendor_id']); if (!$vendor) { throw new VendorNotFoundException(); }` | | |
| TASK-045 | Step 2: Validate payment number unique: `if ($this->paymentRepo->findByNumber($data['payment_number'], tenant_id())) { throw new DuplicatePaymentNumberException(); }` | | |
| TASK-046 | Step 3: Validate bank account: `$bankAccount = BankAccount::find($data['bank_account_id']); if (!$bankAccount || !$bankAccount->is_active) { throw new InvalidBankAccountException('Bank account not found or inactive'); }` | | |
| TASK-047 | Step 4: Validate payment method compatibility (CON-012): `$paymentMethod = PaymentMethod::from($data['payment_method']); if ($paymentMethod->requiresCheckNumber() && empty($data['check_number'])) { throw new MissingCheckNumberException('Check number required for check payments'); }` | | |
| TASK-048 | Step 5: Create payment: `$paymentData = array_merge($data, ['tenant_id' => tenant_id(), 'status' => APPaymentStatus::DRAFT, 'applied_amount' => 0, 'unapplied_amount' => $data['total_amount']]); $payment = $this->paymentRepo->create($paymentData); $this->activityLogger->log("Payment created: {$payment->payment_number}", $payment, auth()->user()); return $payment;` | | |
| TASK-049 | Create `app/Domains/AccountsPayable/Actions/ProcessAPPaymentAction.php` with AsAction trait. Constructor: `public function __construct(private readonly APPaymentRepositoryContract $paymentRepo, private readonly PaymentApplicationService $applicationService, private readonly GLEntryService $glService, private readonly ActivityLoggerContract $activityLogger) {}` | | |
| TASK-050 | Implement `handle(APPayment $payment, array $applications): APPayment` in ProcessAPPaymentAction. Validate payment in correct status: `if (!$payment->status->canApply()) { throw new InvalidStatusException('Payment cannot be processed in current status'); }`. Validate applications provided (BR-AP-003): `if (empty($applications)) { throw new NoApplicationsException('Payment must be applied to at least one invoice'); }` | | |
| TASK-051 | Process applications in transaction: `DB::transaction(function() use ($payment, $applications) { $totalApplied = '0'; foreach ($applications as $app) { $invoice = APInvoice::findOrFail($app['invoice_id']); $this->applicationService->applyPaymentToInvoice($payment, $invoice, $app['amount'], $app['notes'] ?? null); $totalApplied = bcadd($totalApplied, (string)$app['amount'], 4); } if (bccomp($totalApplied, (string)$payment->total_amount, 4) !== 0) { throw new ApplicationMismatchException("Applied {$totalApplied} does not match payment {$payment->total_amount}"); } $this->createGLEntry($payment); $payment->update(['status' => APPaymentStatus::PROCESSED]); event(new APPaymentProcessedEvent($payment)); $this->activityLogger->log("Payment processed: {$payment->payment_number}", $payment, auth()->user()); return $payment->fresh(['applications', 'invoices']); });` | | |
| TASK-052 | Implement `createGLEntry(APPayment $payment): void` private method for IR-AP-001: `$glEntryLines = [['account_id' => $payment->vendor->gl_account_id, 'debit_amount' => $payment->total_amount, 'credit_amount' => 0, 'description' => "Payment {$payment->payment_number}"], ['account_id' => $payment->bankAccount->gl_account_id, 'debit_amount' => 0, 'credit_amount' => $payment->total_amount, 'description' => "Disbursement {$payment->payment_number}"]]; $glEntry = $this->glService->createEntry(['entry_date' => $payment->payment_date, 'description' => "AP Payment {$payment->payment_number} - {$payment->vendor->vendor_name}", 'reference' => $payment->payment_number, 'lines' => $glEntryLines]); $payment->update(['gl_entry_id' => $glEntry->id]);`. Debit AP liability, credit cash | | |
| TASK-053 | Create `app/Domains/AccountsPayable/Actions/ProcessBatchPaymentsAction.php` with AsAction trait. Implement `asJob(): bool` returning true for queue processing (PR-AP-001). Constructor: `public function __construct(private readonly APInvoiceRepositoryContract $invoiceRepo, private readonly ProcessAPPaymentAction $processPaymentAction) {}` | | |
| TASK-054 | Implement `handle(Vendor $vendor, Carbon $paymentDate, ?float $maxAmount = null): array` in ProcessBatchPaymentsAction. Get outstanding invoices: `$invoices = $this->invoiceRepo->getByVendor($vendor->id)->where('status', APInvoiceStatus::POSTED)->where('due_date', '<=', $paymentDate)->sortBy('due_date');` | | |
| TASK-055 | Build batch payment with chunk processing (GUD-008): `$batches = []; $currentBatch = []; $currentTotal = '0'; foreach ($invoices->chunk(100) as $chunk) { foreach ($chunk as $invoice) { if ($maxAmount && bccomp(bcadd($currentTotal, (string)$invoice->outstanding_amount, 4), (string)$maxAmount, 4) > 0) { break 2; } $currentBatch[] = ['invoice_id' => $invoice->id, 'amount' => $invoice->outstanding_amount]; $currentTotal = bcadd($currentTotal, (string)$invoice->outstanding_amount, 4); if (count($currentBatch) >= 100) { $batches[] = ['invoices' => $currentBatch, 'total' => $currentTotal]; $currentBatch = []; $currentTotal = '0'; } } } if (!empty($currentBatch)) { $batches[] = ['invoices' => $currentBatch, 'total' => $currentTotal]; }` | | |
| TASK-056 | Process each batch: `$payments = []; foreach ($batches as $batch) { $payment = APPayment::create([...]); $this->processPaymentAction->handle($payment, $batch['invoices']); $payments[] = $payment; } return ['payments' => $payments, 'total_invoices' => array_sum(array_map(fn($b) => count($b['invoices']), $batches)), 'total_amount' => array_sum(array_column($batches, 'total'))];` | | |
| TASK-057 | Create events: `app/Domains/AccountsPayable/Events/APPaymentProcessedEvent.php` with properties: `public readonly APPayment $payment`. Similarly create `APPaymentAppliedEvent.php` with `public readonly APPaymentApplication $application`, and `APInvoiceFullyPaidEvent.php` with `public readonly APInvoice $invoice` | | |
| TASK-058 | Create exceptions: `app/Domains/AccountsPayable/Exceptions/InvalidApplicationException.php`, `InsufficientPaymentException.php`, `OverpaymentException.php`, `CannotUnapplyException.php`, `VendorMismatchException.php`, `DuplicatePaymentNumberException.php`, `InvalidBankAccountException.php`, `MissingCheckNumberException.php`, `NoApplicationsException.php`, `ApplicationMismatchException.php` | | |

### GOAL-005: Implement Dual Authorization Workflow for Large Payments

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| SR-AP-001, SR-AP-002, CON-011, GUD-010 | Create approval workflow with configurable thresholds, multi-level approvals, and notification system | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-059 | Create config file `config/ap.php` with approval settings: `return ['payment_approval_threshold' => env('AP_PAYMENT_APPROVAL_THRESHOLD', 10000), 'dual_authorization_threshold' => env('AP_DUAL_AUTH_THRESHOLD', 50000), 'approval_levels' => [['threshold' => 10000, 'role' => 'ap_manager'], ['threshold' => 50000, 'role' => 'finance_director']], 'auto_approve_below' => env('AP_AUTO_APPROVE_THRESHOLD', 1000)];` | | |
| TASK-060 | Create `app/Domains/AccountsPayable/Actions/SubmitPaymentForApprovalAction.php` with AsAction trait. Constructor: `public function __construct(private readonly APPaymentRepositoryContract $paymentRepo, private readonly ActivityLoggerContract $activityLogger) {}` | | |
| TASK-061 | Implement `handle(APPayment $payment): APPayment` in SubmitPaymentForApprovalAction. Step 1: Validate payment status: `if ($payment->status !== APPaymentStatus::DRAFT) { throw new InvalidStatusException('Only draft payments can be submitted'); }` | | |
| TASK-062 | Step 2: Determine required approval levels based on amount: `$levels = collect(config('ap.approval_levels'))->filter(fn($level) => $payment->total_amount >= $level['threshold'])->sortBy('threshold');` | | |
| TASK-063 | Step 3: Create approval records: `DB::transaction(function() use ($payment, $levels) { foreach ($levels as $index => $level) { APPaymentApproval::create(['ap_payment_id' => $payment->id, 'approval_level' => $index + 1, 'required_role' => $level['role'], 'status' => 'pending']); } $payment->update(['status' => APPaymentStatus::PENDING_APPROVAL]); $this->activityLogger->log("Payment submitted for approval: {$levels->count()} level(s)", $payment, auth()->user()); dispatch(new NotifyPaymentApproversJob($payment)); return $payment->fresh(['approvals']); });` | | |
| TASK-064 | Create `app/Domains/AccountsPayable/Actions/ApprovePaymentAction.php` with AsAction trait. Constructor: `public function __construct(private readonly APPaymentRepositoryContract $paymentRepo, private readonly ActivityLoggerContract $activityLogger) {}` | | |
| TASK-065 | Implement `handle(APPayment $payment, ?string $notes = null): APPayment` in ApprovePaymentAction. Step 1: Validate payment pending approval: `if ($payment->status !== APPaymentStatus::PENDING_APPROVAL) { throw new InvalidStatusException('Payment not pending approval'); }` | | |
| TASK-066 | Step 2: Find pending approval for user's role: `$userRoles = auth()->user()->roles->pluck('name'); $pendingApproval = $payment->approvals()->pending()->whereIn('required_role', $userRoles)->orderBy('approval_level')->first(); if (!$pendingApproval) { throw new NoApprovalRequiredException('No pending approval for your role'); }` | | |
| TASK-067 | Step 3: Approve and check if all levels complete: `DB::transaction(function() use ($payment, $pendingApproval, $notes) { $pendingApproval->update(['approved_by' => auth()->id(), 'approved_at' => now(), 'status' => 'approved', 'notes' => $notes]); $remainingApprovals = $payment->approvals()->pending()->count(); if ($remainingApprovals === 0) { $payment->update(['status' => APPaymentStatus::APPROVED, 'approved_by' => auth()->id(), 'approved_at' => now()]); $this->activityLogger->log("Payment fully approved", $payment, auth()->user()); } else { $this->activityLogger->log("Payment approved at level {$pendingApproval->approval_level}", $payment, auth()->user()); dispatch(new NotifyNextApproverJob($payment)); } return $payment->fresh(['approvals']); });` | | |
| TASK-068 | Create `app/Domains/AccountsPayable/Actions/RejectPaymentAction.php` with similar structure. Implement rejection logic that updates approval status to 'rejected' and payment status to DRAFT: `$pendingApproval->update(['status' => 'rejected', 'notes' => $reason]); $payment->update(['status' => APPaymentStatus::DRAFT]); dispatch(new NotifyPaymentRejectedJob($payment, $reason));` | | |
| TASK-069 | Create `app/Jobs/NotifyPaymentApproversJob.php` implementing ShouldQueue. In handle(): Get all approvers for pending approvals, send notifications via email/Slack: `$payment->approvals()->pending()->each(function($approval) { $users = User::role($approval->required_role)->get(); Notification::send($users, new PaymentApprovalRequiredNotification($approval)); });` | | |
| TASK-070 | Create `app/Jobs/NotifyNextApproverJob.php` implementing ShouldQueue. Send notification to next level approver: `$nextApproval = $payment->approvals()->pending()->orderBy('approval_level')->first(); $users = User::role($nextApproval->required_role)->get(); Notification::send($users, new PaymentApprovalRequiredNotification($nextApproval));` | | |
| TASK-071 | Create `app/Jobs/NotifyPaymentRejectedJob.php` implementing ShouldQueue. Notify payment creator of rejection: `$creator = $payment->creator; $creator->notify(new PaymentRejectedNotification($payment, $reason));` | | |

## 3. Alternatives

- **ALT-006**: Allow payment application to exceed invoice amount (create credit) - **Rejected** because violates BR-AP-001, creates reconciliation issues
- **ALT-007**: Automatic payment application (oldest invoice first) - **Deferred** to future enhancement, manual control preferred initially
- **ALT-008**: Single approval level only - **Rejected** because SR-AP-002 requires dual authorization for large amounts
- **ALT-009**: Pre-create batch payments for all vendors weekly - **Rejected** because cash flow timing critical, on-demand better
- **ALT-010**: Store check images/scanned documents - **Deferred** to future enhancement (document management module)

## 4. Dependencies

**Package Dependencies:**
- **DEP-001**: `laravel/framework` ^12.0
- **DEP-002**: `spatie/laravel-activitylog` ^4.0 (audit logging)
- **DEP-003**: `lorisleiva/laravel-actions` ^2.0 (action pattern)
- **DEP-004**: `brick/math` ^0.12 (decimal precision)

**Internal Dependencies:**
- **DEP-012**: PRD01-SUB11-PLAN01 (AP Invoice Management) - MUST be completed first
- **DEP-013**: PRD01-SUB10 (Banking Module) - For bank account integration
- **DEP-014**: PRD01-SUB08 (General Ledger) - For GL disbursement posting
- **DEP-015**: PRD01-SUB02 (Authentication & Authorization) - For role-based approvals

**Infrastructure:**
- **DEP-016**: Queue worker for batch payment processing (PR-AP-001)
- **DEP-017**: Notification system (email/Slack) for approval workflows
- **DEP-018**: Redis or Memcached for approval state caching (optional)

## 5. Files

**Migrations:**
- `database/migrations/YYYY_MM_DD_HHMMSS_create_ap_payments_table.php` - Payments
- `database/migrations/YYYY_MM_DD_HHMMSS_create_ap_payment_applications_table.php` - Applications
- `database/migrations/YYYY_MM_DD_HHMMSS_create_ap_payment_approvals_table.php` - Approvals

**Models:**
- `app/Domains/AccountsPayable/Models/APPayment.php` - Payment header
- `app/Domains/AccountsPayable/Models/APPaymentApplication.php` - Payment-invoice link
- `app/Domains/AccountsPayable/Models/APPaymentApproval.php` - Approval workflow

**Enums:**
- `app/Domains/AccountsPayable/Enums/APPaymentStatus.php` - Payment lifecycle
- `app/Domains/AccountsPayable/Enums/PaymentMethod.php` - Payment types

**Contracts:**
- `app/Domains/AccountsPayable/Contracts/APPaymentRepositoryContract.php` - Payment repository

**Repositories:**
- `app/Domains/AccountsPayable/Repositories/DatabaseAPPaymentRepository.php` - Payment repo

**Services:**
- `app/Domains/AccountsPayable/Services/PaymentApplicationService.php` - Application logic

**Actions:**
- `app/Domains/AccountsPayable/Actions/CreateAPPaymentAction.php` - Create payment
- `app/Domains/AccountsPayable/Actions/ProcessAPPaymentAction.php` - Process payment
- `app/Domains/AccountsPayable/Actions/ProcessBatchPaymentsAction.php` - Batch processing
- `app/Domains/AccountsPayable/Actions/SubmitPaymentForApprovalAction.php` - Submit approval
- `app/Domains/AccountsPayable/Actions/ApprovePaymentAction.php` - Approve payment
- `app/Domains/AccountsPayable/Actions/RejectPaymentAction.php` - Reject payment

**Events:**
- `app/Domains/AccountsPayable/Events/APPaymentProcessedEvent.php` - Payment processed
- `app/Domains/AccountsPayable/Events/APPaymentAppliedEvent.php` - Payment applied
- `app/Domains/AccountsPayable/Events/APInvoiceFullyPaidEvent.php` - Invoice paid

**Jobs:**
- `app/Jobs/NotifyPaymentApproversJob.php` - Approval notifications
- `app/Jobs/NotifyNextApproverJob.php` - Next level notification
- `app/Jobs/NotifyPaymentRejectedJob.php` - Rejection notification

**Exceptions:**
- `app/Domains/AccountsPayable/Exceptions/InvalidApplicationException.php` - Invalid application
- `app/Domains/AccountsPayable/Exceptions/InsufficientPaymentException.php` - Insufficient amount
- `app/Domains/AccountsPayable/Exceptions/OverpaymentException.php` - Overpayment
- `app/Domains/AccountsPayable/Exceptions/CannotUnapplyException.php` - Cannot unapply
- `app/Domains/AccountsPayable/Exceptions/VendorMismatchException.php` - Vendor mismatch
- `app/Domains/AccountsPayable/Exceptions/DuplicatePaymentNumberException.php` - Duplicate number
- `app/Domains/AccountsPayable/Exceptions/InvalidBankAccountException.php` - Invalid bank
- `app/Domains/AccountsPayable/Exceptions/MissingCheckNumberException.php` - Missing check #
- `app/Domains/AccountsPayable/Exceptions/NoApplicationsException.php` - No applications
- `app/Domains/AccountsPayable/Exceptions/ApplicationMismatchException.php` - Application mismatch

**Configuration:**
- `config/ap.php` - AP module configuration (approval thresholds)

**Service Provider (updated):**
- `app/Providers/AppServiceProvider.php` - Repository bindings

## 6. Testing

**Unit Tests (15 tests):**
- **TEST-001**: `test_payment_status_enum_has_all_cases` - Verify 5 status cases
- **TEST-002**: `test_payment_method_enum_identifies_check_requirement` - Test requiresCheckNumber()
- **TEST-003**: `test_payment_calculates_unapplied_amount` - Test getUnappliedAmountAttribute()
- **TEST-004**: `test_payment_validates_can_apply_to_invoice` - Test canApplyToInvoice()
- **TEST-005**: `test_payment_determines_approval_requirement` - Test requiresApproval()
- **TEST-006**: `test_payment_application_validates_vendor_match` - Test VendorMismatchException
- **TEST-007**: `test_payment_application_prevents_overpayment` - Test BR-AP-001
- **TEST-008**: `test_payment_application_updates_invoice_status` - Test status change
- **TEST-009**: `test_repository_gets_pending_approval_payments` - Test getPendingApproval()
- **TEST-010**: `test_repository_filters_by_vendor` - Test getForVendor()
- **TEST-011**: `test_batch_payment_chunks_correctly` - Test chunk processing (GUD-008)
- **TEST-012**: `test_approval_workflow_requires_multiple_levels` - Test SR-AP-002
- **TEST-013**: `test_payment_factory_generates_valid_data` - Test factory
- **TEST-014**: `test_payment_scope_pending_approval_works` - Test scopePendingApproval()
- **TEST-015**: `test_unapplied_amount_calculated_from_applications` - Test real-time calculation

**Feature Tests (18 tests):**
- **TEST-016**: `test_create_payment_action_validates_vendor` - Test vendor validation
- **TEST-017**: `test_create_payment_validates_bank_account` - Test bank validation
- **TEST-018**: `test_create_payment_requires_check_number_for_checks` - Test CON-012
- **TEST-019**: `test_process_payment_applies_to_multiple_invoices` - Test FR-AP-003
- **TEST-020**: `test_process_payment_creates_gl_entry` - Test IR-AP-001
- **TEST-021**: `test_process_payment_validates_total_matches` - Test CON-009
- **TEST-022**: `test_process_payment_dispatches_events` - Test APPaymentProcessedEvent
- **TEST-023**: `test_apply_payment_validates_amount_limits` - Test BR-AP-001
- **TEST-024**: `test_apply_payment_updates_invoice_status` - Test PARTIAL/PAID status
- **TEST-025**: `test_apply_payment_dispatches_fully_paid_event` - Test APInvoiceFullyPaidEvent
- **TEST-026**: `test_unapply_payment_requires_permission` - Test authorization
- **TEST-027**: `test_cannot_unapply_from_paid_invoice` - Test CON-010
- **TEST-028**: `test_batch_payment_processes_1000_invoices` - Test PR-AP-001
- **TEST-029**: `test_submit_for_approval_creates_approval_records` - Test approval creation
- **TEST-030**: `test_approve_payment_requires_role` - Test SR-AP-001
- **TEST-031**: `test_dual_authorization_for_large_payments` - Test SR-AP-002
- **TEST-032**: `test_activity_log_records_payment_operations` - Test LogsActivity
- **TEST-033**: `test_payment_approval_notifications_dispatched` - Test GUD-010

**Integration Tests (10 tests):**
- **TEST-034**: `test_payment_application_atomic_transaction` - Test ARCH-AP-001
- **TEST-035**: `test_pessimistic_locking_prevents_race_condition` - Test GUD-009
- **TEST-036**: `test_gl_entry_balances_after_payment` - Test debit = credit
- **TEST-037**: `test_vendor_balance_reflects_payments` - Test BR-AP-004
- **TEST-038**: `test_eager_loading_prevents_n_plus_one` - Test query count
- **TEST-039**: `test_repository_binding_resolves_correctly` - Test service container
- **TEST-040**: `test_payment_lifecycle_draft_to_processed` - Test full workflow
- **TEST-041**: `test_batch_payment_integrates_with_banking` - Test banking integration
- **TEST-042**: `test_approval_workflow_multi_level` - Test complete approval flow
- **TEST-043**: `test_payment_amount_uses_bcmath_precision` - Test 4 decimal precision

**Performance Tests (2 tests):**
- **TEST-044**: `test_batch_payment_1000_invoices_under_5_seconds` - Test PR-AP-001
- **TEST-045**: `test_payment_application_concurrent_no_deadlock` - Test locking performance

## 7. Risks & Assumptions

**Risks:**
- **RISK-006**: Concurrent payment application causes double payment - **Mitigation**: Use pessimistic locking (lockForUpdate), unique constraint on payment-invoice pair
- **RISK-007**: Batch payment processing timeout on large datasets - **Mitigation**: Process in chunks of 100 (GUD-008), dispatch as queue job
- **RISK-008**: Approval workflow bypassed via direct status update - **Mitigation**: Enforce workflow in actions, database triggers (future), audit all status changes
- **RISK-009**: GL entry creation fails mid-payment causing inconsistency - **Mitigation**: Database transaction wrapping all payment operations
- **RISK-010**: Check number reused across tenants - **Mitigation**: Unique constraint on (tenant_id, bank_account_id, check_number)

**Assumptions:**
- **ASSUMPTION-006**: Most payments apply to single invoice, batch payments are exception
- **ASSUMPTION-007**: Approval turnaround time is < 24 hours for normal payments
- **ASSUMPTION-008**: Payment methods distributed: 60% check, 30% ACH, 10% wire
- **ASSUMPTION-009**: Dual authorization threshold ($50k) adequate for most organizations
- **ASSUMPTION-010**: Electronic payments processed same-day, checks next business day

## 8. KIV for future implementations

- **KIV-009**: Implement automatic payment application (oldest invoice first)
- **KIV-010**: Add payment scheduling/recurring payments
- **KIV-011**: Implement positive pay file generation for check fraud prevention
- **KIV-012**: Add ACH file generation (NACHA format)
- **KIV-013**: Implement payment reversal workflow
- **KIV-014**: Add vendor payment portal (self-service)
- **KIV-015**: Implement early payment discount capture
- **KIV-016**: Add payment forecasting and cash flow projection
- **KIV-017**: Implement check printing integration
- **KIV-018**: Add payment confirmation tracking (check cleared, wire confirmed)

## 9. Related PRD / Further Reading

- **Master PRD:** [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD:** [../prd/prd-01/PRD01-SUB11-ACCOUNTS-PAYABLE.md](../prd/prd-01/PRD01-SUB11-ACCOUNTS-PAYABLE.md)
- **Related Sub-PRDs:**
  - PRD01-SUB08 (General Ledger) - GL disbursement posting
  - PRD01-SUB10 (Banking) - Bank account and disbursement integration
  - PRD01-SUB02 (Authentication & Authorization) - Role-based approvals
- **Related Plans:**
  - PRD01-SUB11-PLAN01 (AP Invoice Management) - Prerequisites
- **External Documentation:**
  - Payment Processing Best Practices: https://www.accountingtools.com/articles/payment-processing-best-practices
  - Dual Authorization Controls: https://www.aicpa.org/resources/article/dual-authorization
  - ACH NACHA File Format: https://www.nacha.org/content/ach-file-specifications
  - Positive Pay: https://www.investopedia.com/terms/p/positive-pay.asp
