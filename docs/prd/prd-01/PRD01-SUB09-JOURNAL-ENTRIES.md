# PRD01-SUB09: Journal Entries System

**Master PRD:** [../PRD01-MVP.md](../PRD01-MVP.md)  
**Feature Module Category:** Mandatory Feature Modules - Finance & Accounting  
**Related Sub-PRDs:** PRD01-SUB07 (Chart of Accounts), PRD01-SUB08 (General Ledger)  
**Composer Package:** `azaharizaman/erp-journal-entries`  
**Version:** 1.0.0  
**Status:** Draft  
**Created:** November 11, 2025

---

## Executive Summary

The Journal Entries System provides **manual and automated journal entry management with recurring journals, reversing entries, templates, and approval workflows**. This module enables accountants to manually record adjustments, accruals, reclassifications, and other entries that don't originate from transactional submodules.

### Purpose

The Journal Entries System solves the critical problem of **manual accounting adjustments** that are essential for accurate financial statements. It enables:

1. **Manual Adjustments:** Record period-end adjustments, corrections, and reclassifications
2. **Recurring Journals:** Automate monthly/quarterly recurring entries (rent, depreciation)
3. **Reversing Entries:** Automatically reverse accruals in next period
4. **Templates:** Save commonly used journal patterns for quick entry
5. **Approval Workflow:** Multi-level approval before posting to GL
6. **Audit Trail:** Complete history of manual adjustments

### Scope

**Included in this Feature Module:**

- ✅ Manual journal entry creation and editing
- ✅ Multi-line journal entries
- ✅ Recurring journal schedules
- ✅ Reversing entry automation
- ✅ Journal entry templates
- ✅ Approval workflow (draft → pending → approved → posted)
- ✅ Batch journal entry creation
- ✅ Journal entry search and filtering
- ✅ Integration with General Ledger
- ✅ Authorization controls

**Excluded from this Feature Module:**

- ❌ System-generated entries (handled by submodules)
- ❌ Financial statement generation (handled by SUB20)

### Dependencies

**Mandatory Dependencies:**
- Laravel Framework v12.x
- PHP ≥ 8.2
- PostgreSQL
- PRD01-SUB01 (Multi-Tenancy System)
- PRD01-SUB07 (Chart of Accounts)
- PRD01-SUB08 (General Ledger)

### Composer Package Information

- **Package Name:** `azaharizaman/erp-journal-entries`
- **Namespace:** `Azaharizaman\Erp\JournalEntries`
- **Monorepo Location:** `/packages/journal-entries/`
- **Installation:** `composer require azaharizaman/erp-journal-entries` (post v1.0 release)

---

## Requirements

## Requirements

> **Note:** These requirements are derived from Master PRD Section F.2.3 - PRD01-SUB09 (Journal Entries). For complete traceability and context, refer to the [Master PRD Requirements Table](../PRD01-MVP.md#f23-requirements-by-sub-prd).

### Functional Requirements (FR)

| Requirement ID | Description | Priority | Status |
|----------------|-------------|----------|--------|
| **FR-JE-001** | Support **manual journal entry creation** with multi-line debit/credit entries and attachments | High | Planned |
| **FR-JE-002** | Support **recurring journals, reversing entries, and templates** for common accounting patterns | High | Planned |
| **FR-JE-003** | Implement **approval workflow** with configurable authorization rules before posting to GL | High | Planned |
| **FR-JE-004** | Provide **journal entry inquiry** with search, filter, and drill-down capabilities | Medium | Planned |

### Business Rules (BR)

| Rule ID | Description | Status |
|---------|-------------|--------|
| **BR-JE-001** | Only **authorized users** with proper permissions may post journals to the general ledger | Planned |
| **BR-JE-002** | Journal entries MUST be **balanced (debit = credit)** before approval | Planned |
| **BR-JE-003** | **Approved journals** cannot be edited, only reversed with offsetting entry | Planned |
| **BR-JE-004** | Recurring journals **auto-generate on schedule** but require approval before posting | Planned |

### Data Requirements (DR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **DR-JE-001** | Store journal entry metadata: entry_number, entry_date, description, status, created_by, approved_by | Planned |
| **DR-JE-002** | Store journal entry lines: account_id, debit_amount, credit_amount, description, reference | Planned |

### Integration Requirements (IR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **IR-JE-001** | Integrate with **General Ledger** for automatic posting after approval | Planned |
| **IR-JE-002** | Integrate with **Chart of Accounts** for account validation during entry | Planned |

### Performance Requirements (PR)

| Requirement ID | Target | Status |
|----------------|--------|--------|
| **PR-JE-001** | Approval and posting workflow must complete within **2 seconds** per entry | Planned |
| **PR-JE-002** | Support **batch approval** of 100+ journal entries in under 5 seconds | Planned |

### Security Requirements (SR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SR-JE-001** | Enforce **role-based access** for journal entry creation, approval, and posting | Planned |

### Architecture Requirements (ARCH)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **ARCH-JE-001** | Use **database transactions** to ensure atomicity when posting multiple lines to GL | Planned |

### Event Requirements (EV)

| Event ID | Event Name | Trigger | Status |
|----------|------------|---------|--------|
| **EV-JE-001** | `JournalEntryCreatedEvent` | When new manual journal entry is created | Planned |
| **EV-JE-002** | `JournalEntryApprovedEvent` | When journal entry is approved by authorized user | Planned |
| **EV-JE-003** | `JournalEntryPostedEvent` | When journal entry is posted to General Ledger | Planned |

---

## Technical Specifications

### Database Schema

**Journal Entries Table:**

```sql
CREATE TABLE journal_entries (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    entry_number VARCHAR(50) NOT NULL,
    entry_date DATE NOT NULL,
    description TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft', -- 'draft', 'pending_approval', 'approved', 'posted', 'rejected'
    is_recurring BOOLEAN NOT NULL DEFAULT FALSE,
    is_reversing BOOLEAN NOT NULL DEFAULT FALSE,
    reverse_date DATE NULL,
    template_id BIGINT NULL REFERENCES journal_templates(id),
    created_by BIGINT NOT NULL,
    approved_by BIGINT NULL,
    approved_at TIMESTAMP NULL,
    posted_to_gl_at TIMESTAMP NULL,
    gl_entry_id BIGINT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    UNIQUE (tenant_id, entry_number),
    INDEX idx_je_tenant (tenant_id),
    INDEX idx_je_date (entry_date),
    INDEX idx_je_status (status),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Journal Entry Lines Table:**

```sql
CREATE TABLE journal_entry_lines (
    id BIGSERIAL PRIMARY KEY,
    journal_entry_id BIGINT NOT NULL REFERENCES journal_entries(id) ON DELETE CASCADE,
    line_number INT NOT NULL,
    account_id BIGINT NOT NULL REFERENCES accounts(id),
    description TEXT NULL,
    debit_amount DECIMAL(20, 4) NOT NULL DEFAULT 0,
    credit_amount DECIMAL(20, 4) NOT NULL DEFAULT 0,
    cost_center VARCHAR(50) NULL,
    project_code VARCHAR(50) NULL,
    
    INDEX idx_je_lines_entry (journal_entry_id),
    INDEX idx_je_lines_account (account_id)
);
```

**Journal Templates Table:**

```sql
CREATE TABLE journal_templates (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_by BIGINT NOT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_je_templates_tenant (tenant_id)
);
```

**Recurring Journal Schedules Table:**

```sql
CREATE TABLE recurring_journal_schedules (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    journal_template_id BIGINT NOT NULL REFERENCES journal_templates(id),
    name VARCHAR(255) NOT NULL,
    frequency VARCHAR(20) NOT NULL, -- 'monthly', 'quarterly', 'annually'
    start_date DATE NOT NULL,
    end_date DATE NULL,
    next_generation_date DATE NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_recurring_tenant (tenant_id),
    INDEX idx_recurring_next_date (next_generation_date)
);
```

### API Endpoints

| Method | Endpoint | Purpose | Auth Required |
|--------|----------|---------|---------------|
| GET | `/api/v1/journal-entries` | List journal entries | Yes - Accounting |
| POST | `/api/v1/journal-entries` | Create journal entry | Yes - Accounting |
| GET | `/api/v1/journal-entries/{id}` | Get entry details | Yes - Accounting |
| PATCH | `/api/v1/journal-entries/{id}` | Update draft entry | Yes - Accounting |
| DELETE | `/api/v1/journal-entries/{id}` | Delete draft entry | Yes - Accounting |
| POST | `/api/v1/journal-entries/{id}/submit` | Submit for approval | Yes - Accounting |
| POST | `/api/v1/journal-entries/{id}/approve` | Approve entry | Yes - Accounting Manager |
| POST | `/api/v1/journal-entries/{id}/reject` | Reject entry | Yes - Accounting Manager |
| POST | `/api/v1/journal-entries/{id}/post` | Post to GL | Yes - Accounting Manager |
| POST | `/api/v1/journal-entries/{id}/reverse` | Create reversal | Yes - Accounting Manager |
| GET | `/api/v1/journal-templates` | List templates | Yes |
| POST | `/api/v1/journal-templates` | Create template | Yes - Accounting |
| GET | `/api/v1/recurring-journals` | List recurring schedules | Yes |
| POST | `/api/v1/recurring-journals` | Create recurring schedule | Yes - Accounting Manager |

### Events

**Domain Events Emitted:**

| Event Class | When Fired | Payload |
|-------------|-----------|---------|
| `JournalEntryCreatedEvent` | After JE created | `JournalEntry $entry` |
| `JournalEntrySubmittedEvent` | After submission | `JournalEntry $entry` |
| `JournalEntryApprovedEvent` | After approval | `JournalEntry $entry` |
| `JournalEntryPostedEvent` | After posting to GL | `JournalEntry $entry` |
| `RecurringJournalGeneratedEvent` | Auto-generated from schedule | `JournalEntry $entry` |

---

## Implementation Plans

| Plan File | Requirements Covered | Milestone | Status |
|-----------|---------------------|-----------|--------|
| PLAN09-implement-journal-entries.md | FR-JE-002, BR-JE-001, PR-JE-001 | MILESTONE 2 | Not Started |

---

## Acceptance Criteria

- [ ] Manual journal entry creation working
- [ ] Multi-level approval workflow functional
- [ ] Recurring journals auto-generate
- [ ] Reversing entries auto-create
- [ ] Templates save and load correctly
- [ ] Balance validation enforced
- [ ] Posting to GL successful
- [ ] Authorization controls working
- [ ] < 2s approval/posting time
- [ ] 100% test coverage

---

## Testing Strategy

```php
test('creates journal entry with balanced lines', function () {
    $entry = JournalEntry::factory()->create();
    $entry->lines()->createMany([
        ['account_id' => 1, 'debit_amount' => 5000],
        ['account_id' => 2, 'credit_amount' => 5000],
    ]);
    
    expect($entry->isBalanced())->toBeTrue();
});

test('prevents posting unbalanced entry', function () {
    $entry = JournalEntry::factory()->create();
    $entry->lines()->createMany([
        ['account_id' => 1, 'debit_amount' => 5000],
        ['account_id' => 2, 'credit_amount' => 4000],
    ]);
    
    expect(fn() => $entry->post())
        ->toThrow(UnbalancedEntryException::class);
});
```

---

## Dependencies

### Feature Module Dependencies

- **Mandatory:** SUB01, SUB07, SUB08

---

## Monorepo Integration

- Development: `/packages/journal-entries/`
- Published as: `azaharizaman/erp-journal-entries`

---

**Document Status:** Draft - Pending Review  
**Last Updated:** November 11, 2025
