# PRD01-SUB04: Serial Numbering System

**Master PRD:** [../PRD01-MVP.md](../PRD01-MVP.md)  
**Feature Module Category:** Mandatory Feature Modules - Core Infrastructure  
**Related Sub-PRDs:** PRD01-SUB01 (Multi-Tenancy), PRD01-SUB05 (Settings Management)  
**Composer Package:** `azaharizaman/erp-serial-numbering`  
**Version:** 1.0.0  
**Status:** Draft  
**Created:** November 11, 2025

---

## Executive Summary

The Serial Numbering System provides **automated document numbering with configurable patterns, tenant-specific sequences, and collision-free ID generation**. This mandatory feature module ensures unique, sequential, human-readable identifiers for all business documents (invoices, purchase orders, receipts, etc.) with support for complex formatting rules, reset periods, and high-concurrency environments.

### Purpose

The Serial Numbering System solves the critical problem of **consistent document identification** across the ERP system. It enables:

1. **Unique Identifiers:** Guarantee collision-free document numbers even under high concurrency
2. **Business Formatting:** Support complex patterns (INV-2025-001, PO-DEPT-00123, etc.)
3. **Regulatory Compliance:** Meet requirements for sequential numbering (tax authorities)
4. **Multi-Tenant Isolation:** Separate number sequences per tenant
5. **Operational Control:** Support resets (yearly, monthly, daily) and custom prefixes

### Scope

**Included in this Feature Module:**

- ✅ Configurable serial number patterns with variables
- ✅ Tenant-scoped sequences
- ✅ Atomic number generation with race condition prevention
- ✅ Reset periods (yearly, monthly, daily, never)
- ✅ Multiple sequences per tenant (invoices, POs, etc.)
- ✅ Padding and formatting options
- ✅ Sequence preview and validation
- ✅ Rollback capability for failed transactions
- ✅ Audit trail for number generation

**Excluded from this Feature Module:**

- ❌ Barcode/QR code generation (separate module)
- ❌ External system synchronization (future enhancement)
- ❌ Number range reservation (future enhancement)

### Dependencies

**Mandatory Dependencies:**
- Laravel Framework v12.x
- PHP ≥ 8.2
- Database with transaction support
- PRD01-SUB01 (Multi-Tenancy System)

**Feature Module Dependencies:**
- **Mandatory:** SUB01 (Multi-Tenancy)
- **Optional:** SUB05 (Settings Management) for pattern storage

### Composer Package Information

- **Package Name:** `azaharizaman/erp-serial-numbering`
- **Namespace:** `Nexus\Erp\SerialNumbering`
- **Monorepo Location:** `/packages/serial-numbering/`
- **Installation:** `composer require azaharizaman/erp-serial-numbering` (post v1.0 release)

---

## Requirements## Requirements

> **Note:** These requirements are derived from Master PRD Section F.2.3 - PRD01-SUB04 (Serial Numbering). For complete traceability and context, refer to the [Master PRD Requirements Table](../PRD01-MVP.md#f23-requirements-by-sub-prd).

### Functional Requirements (FR)

| Requirement ID | Description | Priority | Status |
|----------------|-------------|----------|--------|
| **FR-SN-001** | Support **Configurable Serial Number Patterns** with variables: {YEAR}, {MONTH}, {DAY}, {COUNTER}, {PREFIX}, {TENANT}, {DEPARTMENT} | High | Planned |
| **FR-SN-002** | Provide **centralized sequence management** API for all modules requiring auto-numbering | High | Planned |
| **FR-SN-003** | Provide **Reset Periods** (daily, monthly, yearly, never) for counter sequences | High | Planned |
| **FR-SN-004** | Support **manual number override** for exceptional cases with audit logging | Medium | Planned |
| **FR-SN-005** | Provide **sequence preview** to show next generated number without consuming it | Medium | Planned |

### Business Rules (BR)

| Rule ID | Description | Status |
|---------|-------------|--------|
| **BR-SN-001** | Serial numbers MUST be **unique within their sequence and tenant scope** | Planned |
| **BR-SN-002** | Counter MUST be **zero-padded to configured width** (e.g., 001, 0001) | Planned |
| **BR-SN-003** | Number generation MUST be **atomic and transaction-safe** | Planned |
| **BR-SN-004** | Failed transactions MUST **NOT consume sequence numbers** | Planned |
| **BR-SN-005** | Pattern variables MUST be **evaluated at generation time**, not configuration time | Planned |

### Data Requirements (DR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **DR-SN-001** | Sequence configuration MUST store: tenant_id, sequence_name, pattern, reset_period, padding, current_value | Planned |
| **DR-SN-002** | Generated numbers MUST be **logged with: timestamp, tenant_id, sequence_name, generated_number, causer_id** | Planned |
| **DR-SN-003** | Sequence state MUST support **optimistic locking** for concurrent updates | Planned |

### Integration Requirements (IR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **IR-SN-001** | Provide sequence generation API for **all modules** (invoices, POs, items, etc.) | Planned |
| **IR-SN-002** | Integrate with **SUB03 (Audit Logging)** for sequence generation tracking | Planned |

### Performance Requirements (PR)

| Requirement ID | Target | Status |
|----------------|--------|--------|
| **PR-SN-001** | Number generation MUST complete in **< 50ms** for 95th percentile | Planned |
| **PR-SN-002** | System MUST support **100 concurrent number generations** without collisions | Planned |

### Security Requirements (SR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SR-SN-001** | Enforce **Race Condition Prevention** using database-level atomic locking (SELECT FOR UPDATE) | Planned |
| **SR-SN-002** | **Tenant isolation** MUST prevent cross-tenant sequence access | Planned |
| **SR-SN-003** | Manual overrides MUST require **admin permission** and be fully audited | Planned |

### Scalability Requirements (SCR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SCR-SN-001** | Support **1000+ sequences** per tenant with sub-10ms lookup time | Planned |

### Architecture Requirements (ARCH)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **ARCH-SN-001** | Use **database row-level locking** (SELECT FOR UPDATE) for atomic counter increment | Planned |
| **ARCH-SN-002** | Implement **transaction-safe number generation** with automatic rollback on failure | Planned |

### Event Requirements (EV)

| Event ID | Event Name | Trigger | Status |
|----------|------------|---------|--------|
| **EV-SN-001** | `SequenceGeneratedEvent` | When new serial number is generated | Planned |
| **EV-SN-002** | `SequenceResetEvent` | When sequence counter is reset | Planned |
| **EV-SN-003** | `SequenceOverriddenEvent` | When manual override is used | Planned |

---

## Technical Specifications

### Database Schema

**Sequence Configurations Table:**

```sql
CREATE TABLE serial_number_sequences (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    sequence_name VARCHAR(100) NOT NULL,
    pattern VARCHAR(255) NOT NULL,
    reset_period VARCHAR(20) NOT NULL, -- 'never', 'daily', 'monthly', 'yearly'
    padding INT NOT NULL DEFAULT 4,
    current_value BIGINT NOT NULL DEFAULT 0,
    last_reset_at TIMESTAMP NULL,
    last_generated_at TIMESTAMP NULL,
    metadata JSONB NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    UNIQUE (tenant_id, sequence_name),
    INDEX idx_sequences_tenant (tenant_id)
);
```

**Generated Numbers Log Table:**

```sql
CREATE TABLE serial_number_logs (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    sequence_id BIGINT NOT NULL REFERENCES serial_number_sequences(id),
    generated_number VARCHAR(255) NOT NULL,
    counter_value BIGINT NOT NULL,
    causer_type VARCHAR(255) NULL,
    causer_id BIGINT NULL,
    metadata JSONB NULL,
    created_at TIMESTAMP NOT NULL,
    
    INDEX idx_sn_logs_tenant (tenant_id),
    INDEX idx_sn_logs_number (generated_number),
    INDEX idx_sn_logs_created (created_at)
);
```

**Pattern Variables:**

| Variable | Description | Example |
|----------|-------------|---------|
| `{YEAR}` | 4-digit year | 2025 |
| `{YEAR:2}` | 2-digit year | 25 |
| `{MONTH}` | 2-digit month | 11 |
| `{DAY}` | 2-digit day | 09 |
| `{COUNTER}` | Auto-increment number (padded) | 00001 |
| `{PREFIX}` | Custom prefix from config | INV |
| `{TENANT}` | Tenant code | ACME |
| `{DEPARTMENT}` | Department code | SALES |

**Example Patterns:**
- Invoice: `INV-{YEAR}-{COUNTER:5}` → `INV-2025-00001`
- PO: `PO-{YEAR:2}{MONTH}-{COUNTER:4}` → `PO-2511-0001`
- Receipt: `{TENANT}-RCP-{YEAR}-{MONTH}-{DAY}-{COUNTER:3}` → `ACME-RCP-2025-11-09-001`

### API Endpoints

All endpoints follow `/api/v1/serial-numbers` pattern:

| Method | Endpoint | Purpose | Auth Required |
|--------|----------|---------|---------------|
| GET | `/api/v1/serial-numbers/sequences` | List all sequences for tenant | Yes - Admin |
| POST | `/api/v1/serial-numbers/sequences` | Create new sequence | Yes - Admin |
| GET | `/api/v1/serial-numbers/sequences/{id}` | Get sequence details | Yes - Admin |
| PATCH | `/api/v1/serial-numbers/sequences/{id}` | Update sequence configuration | Yes - Admin |
| DELETE | `/api/v1/serial-numbers/sequences/{id}` | Delete sequence (if unused) | Yes - Admin |
| POST | `/api/v1/serial-numbers/sequences/{id}/preview` | Preview next number | Yes - Admin |
| POST | `/api/v1/serial-numbers/sequences/{id}/reset` | Manually reset sequence | Yes - Admin |
| POST | `/api/v1/serial-numbers/generate` | Generate next number | Yes |
| GET | `/api/v1/serial-numbers/logs` | Get generation logs | Yes - Admin |

**Request/Response Examples:**

**Create Sequence:**
```json
// POST /api/v1/serial-numbers/sequences
{
    "sequence_name": "invoice",
    "pattern": "INV-{YEAR}-{COUNTER:5}",
    "reset_period": "yearly",
    "padding": 5,
    "metadata": {
        "description": "Invoice numbering sequence"
    }
}

// Response 201 Created
{
    "data": {
        "id": 1,
        "tenant_id": "uuid-here",
        "sequence_name": "invoice",
        "pattern": "INV-{YEAR}-{COUNTER:5}",
        "reset_period": "yearly",
        "padding": 5,
        "current_value": 0,
        "last_reset_at": null,
        "created_at": "2025-11-11T10:00:00Z"
    }
}
```

**Generate Number:**
```json
// POST /api/v1/serial-numbers/generate
{
    "sequence_name": "invoice",
    "variables": {
        "TENANT": "ACME",
        "DEPARTMENT": "SALES"
    }
}

// Response 200 OK
{
    "data": {
        "generated_number": "INV-2025-00001",
        "sequence_id": 1,
        "counter_value": 1,
        "generated_at": "2025-11-11T10:00:00Z"
    }
}
```

**Preview Next Number:**
```json
// POST /api/v1/serial-numbers/sequences/1/preview
{}

// Response 200 OK
{
    "data": {
        "next_number": "INV-2025-00002",
        "current_value": 1
    }
}
```

### Events

**Domain Events Emitted by this Feature Module:**

| Event Class | When Fired | Payload |
|-------------|-----------|---------|
| `SequenceCreatedEvent` | After sequence created | `SerialNumberSequence $sequence` |
| `SequenceResetEvent` | When sequence reset | `SerialNumberSequence $sequence, string $reason` |
| `NumberGeneratedEvent` | After number generated | `string $number, SerialNumberSequence $sequence` |

---

## Implementation Plans

| Plan File | Requirements Covered | Milestone | Status |
|-----------|---------------------|-----------|--------|
| PLAN04-implement-serial-numbering.md | FR-SN-001, FR-SN-003, SR-SN-001, PR-SN-001 | MILESTONE 1 | Not Started |

---

## Acceptance Criteria

- [ ] Configurable patterns with all variables working
- [ ] Reset periods functional (daily, monthly, yearly)
- [ ] Atomic number generation (no collisions)
- [ ] Race condition tests pass (100 concurrent requests)
- [ ] Number generation < 50ms
- [ ] Tenant isolation enforced
- [ ] Preview functionality accurate
- [ ] Audit logs capture all generations
- [ ] 100% test coverage

---

## Testing Strategy

### Unit Tests

```php
test('generates unique sequential numbers', function () {
    $sequence = SerialNumberSequence::factory()->create([
        'pattern' => 'INV-{YEAR}-{COUNTER:5}',
        'reset_period' => 'never'
    ]);
    
    $number1 = SerialNumberGenerator::generate($sequence);
    $number2 = SerialNumberGenerator::generate($sequence);
    
    expect($number1)->toBe('INV-2025-00001');
    expect($number2)->toBe('INV-2025-00002');
});

test('resets counter on yearly reset period', function () {
    $sequence = SerialNumberSequence::factory()->create([
        'pattern' => 'INV-{YEAR}-{COUNTER:3}',
        'reset_period' => 'yearly',
        'current_value' => 150,
        'last_reset_at' => now()->subYear()
    ]);
    
    $number = SerialNumberGenerator::generate($sequence);
    
    expect($number)->toBe('INV-2025-001');
    expect($sequence->fresh()->current_value)->toBe(1);
});
```

### Concurrency Tests

```php
test('prevents race conditions with concurrent generations', function () {
    $sequence = SerialNumberSequence::factory()->create();
    
    $numbers = collect();
    
    // Simulate 100 concurrent requests
    parallel(function () use ($sequence, $numbers) {
        $number = SerialNumberGenerator::generate($sequence);
        $numbers->push($number);
    }, 100);
    
    // All numbers should be unique
    expect($numbers->unique()->count())->toBe(100);
});
```

---

## Dependencies

### Feature Module Dependencies

- **Mandatory:** SUB01 (Multi-Tenancy)
- **Optional:** SUB05 (Settings Management)

### External Package Dependencies

None - implemented using Laravel primitives

---

## Success Metrics

| Metric | Target |
|--------|--------|
| Generation Performance | < 50ms (95th percentile) |
| Collision Rate | 0% |
| Concurrent Generations | 100+ simultaneous |

---

## Monorepo Integration

- Development: `/packages/serial-numbering/`
- Published as: `azaharizaman/erp-serial-numbering`

---

## References

- Master PRD: [../PRD01-MVP.md](../PRD01-MVP.md)
- Package Repository: https://github.com/azaharizaman/laravel-serial-numbering

---

**Document Status:** Draft - Pending Review  
**Last Updated:** November 11, 2025
