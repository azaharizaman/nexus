

## Functional Requirements

### Core Generation Requirements

| ID | Requirement | Status | Priority | Notes |
|----|-------------|--------|----------|-------|
| **FR-CORE-001** | Provide a **framework-agnostic core** (`Nexus\Sequencing\Core`) containing all generation and counter logic | 🔧 Needs Refactoring | Critical | Currently Laravel-integrated, needs separation |
| **FR-CORE-002** | Implement **atomic number generation** using database-level locking (`SELECT FOR UPDATE`) | ✅ Complete | Critical | Implemented in DatabaseSequenceRepository |
| **FR-CORE-003** | Ensure generation is **transaction-safe** and rolls back counter increment if calling transaction fails | ✅ Complete | Critical | DB::transaction wrapper in action |
| **FR-CORE-004** | Support built-in pattern variables (e.g., `{YEAR}`, `{MONTH}`, `{COUNTER}`) and custom context variables (e.g., `{DEPARTMENT}`) | ✅ Complete | High | PatternParserService supports 7 variables |
| **FR-CORE-005** | Implement the ability to **preview** the next number without consuming the counter | ✅ Complete | High | PreviewSerialNumberAction |
| **FR-CORE-006** | Implement logic for **Daily, Monthly, Yearly, and Never** counter resets | ✅ Complete | High | ResetPeriod enum + shouldReset() logic |
| **FR-CORE-007** | Implement a **ValidateSerialNumberService** to check if a given number matches a pattern's Regex and inherent variable formats | ❌ Not Implemented | High | Needed for bulk import validation |
| **FR-CORE-008** | Sequence definition must allow configuring a **step_size** (defaulting to 1) for custom counter increments | ❌ Not Implemented | High | Would support reserving blocks of numbers |
| **FR-CORE-009** | Sequence definition must support a **reset_limit** (integer) for custom counter resets based on count, not time | ❌ Not Implemented | High | Would support batch number printing |
| **FR-CORE-010** | Preview Service must expose the **remaining** count until the next reset period or limit is reached | ❌ Not Implemented | High | Needed for ERP planning and reporting |

### Model Integration Requirements

| ID | Requirement | Status | Priority | Notes |
|----|-------------|--------|----------|-------|
| **FR-MODEL-001** | Provide a **HasSequence** trait for Eloquent models to automate number generation on model creation | ❌ Not Implemented | Critical | Level 1 adoption pattern |
| **FR-MODEL-002** | Allow the sequence pattern and name to be defined **directly in the model** using a static property or method | ❌ Not Implemented | High | Simplifies configuration |

### Administration Requirements

| ID | Requirement | Status | Priority | Notes |
|----|-------------|--------|----------|-------|
| **FR-ADMIN-001** | Implement a service/action to **manually override** the current counter value with audit logging | ✅ Complete | High | OverrideSerialNumberAction |
| **FR-ADMIN-002** | Provide API endpoints (in the Adapter) for CRUD management of sequence definitions | ✅ Complete | High | SequenceController with 6 endpoints |

---

## Non-Functional Requirements

### Performance Requirements

| ID | Requirement | Target | Status | Notes |
|----|-------------|--------|--------|-------|
| **PR-001** | Generation time **< 50ms** (p95) | < 50ms | ✅ Met | Database locking is fast with proper indexing |
| **PR-002** | Must pass 100 simultaneous requests with **zero duplicate numbers or deadlocks** | 100 concurrent | ✅ Met | SELECT FOR UPDATE + unique constraints |

### Scope Isolation Requirements

| ID | Requirement | Status | Notes |
|----|-------------|--------|-------|
| **SR-001** | The Core must enforce isolation using the **scope_identifier** (passed by the Adapter), not knowing it represents a tenant | 🔧 Needs Refactoring | Currently uses `tenant_id` directly |
| **SR-002** | Log all **generation, override, and reset** operations via events | ✅ Complete | SerialNumberLog model + Events |
| **SR-003** | Manual overrides **must** log the acting user ID and the reason for the change | ✅ Complete | OverrideSerialNumberAction captures causer |

### Security & Code Requirements

| ID | Requirement | Status | Notes |
|----|-------------|--------|-------|
| **SCR-001** | The `Core` package must maintain **zero dependencies** on Laravel/Eloquent code | 🔧 Needs Refactoring | Currently mixed architecture |
| **SCR-002** | Provide a **contract/interface** for parsing patterns to allow developers to inject custom pattern logic | 🔄 Partial | PatternParserContract exists, needs injection mechanism |
| **SCR-003** | Core generation logic must achieve **> 95% unit test coverage** | ⏳ Pending | Only 2 unit tests currently |

---

## Business Rules

| ID | Rule | Engine | Status |
|----|------|--------|--------|
| **BR-001** | The sequence name/ID is **unique per scope_identifier** (composite key) | Core | ✅ Enforced via database unique constraint |
| **BR-002** | A generated number must be **immutable**. Once generated and consumed, it cannot be changed | Core | ✅ No delete/update logic in actions |
| **BR-003** | Pattern variables must be padded if a padding size is specified in the pattern (e.g., `{COUNTER:5}`) | Core | ✅ PatternParserService handles padding |
| **BR-004** | The manual override of a sequence value **must** be greater than the last generated number | Admin | ✅ Validation in OverrideSerialNumberAction |
| **BR-005** | The counter is only incremented *after* a successful database lock and generation, not during preview | Core | ✅ Preview action doesn't call lockAndIncrement |
| **BR-006** | The package is only responsible for the **Unique Base Identifier**. Sub-identifiers (copies, versions, spawns) are the responsibility of the application layer | Core / Architecture | ✅ Documented principle |

---
