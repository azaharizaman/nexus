That's a great way to consolidate all the granular details into a final, actionable document. Here is the complete list of requirements for the **`Nexus\Sequencing`** package, incorporating all the discussions on atomicity, custom formatting, scope isolation, custom stepping, and reset limits.

---

## ⚙️ Nexus Sequencing: Consolidated Requirements

### 1. Functional Requirements (FR)

| ID | Requirement | Priority | Rationale / Source Discussion |
| :--- | :--- | :--- | :--- |
| **FR-CORE-001** | Provide a **framework-agnostic core** (`Nexus\Sequencing\Core`) containing all generation and counter logic. | **Critical** | Core purity; required for reliability. |
| **FR-CORE-002** | Implement **atomic number generation** using database-level locking (`SELECT FOR UPDATE`). | **Critical** | Guarantees no race conditions (US-006). |
| **FR-CORE-003** | Ensure generation is **transaction-safe** and rolls back the counter increment if the calling transaction fails. | **Critical** | ERP reliability and data integrity. |
| **FR-CORE-004** | Support built-in pattern variables (e.g., `{YEAR}`, `{MONTH}`, `{COUNTER}`) and custom context variables (e.g., `{DEPARTMENT}`). | **High** | Supports complex document ID formats. |
| **FR-CORE-005** | Implement the ability to **preview** the next number without consuming the counter. | High | Supports admin testing and UI preview (US-007). |
| **FR-CORE-006** | Implement logic for **Daily, Monthly, Yearly, and Never** counter resets. | **High** | Basic ERP requirement (US-005). |
| **FR-CORE-007** | Implement a **`ValidateSerialNumberService`** to check if a given number matches a pattern's Regex and inherent variable formats. | **High** | Supports bulk import validation and quick lookups. |
| **FR-CORE-008** | Sequence definition must allow configuring a **`step_size`** (defaulting to 1) for custom counter increments. | **High** | Supports reserving blocks of numbers. |
| **FR-CORE-009** | Sequence definition must support a **`reset_limit`** (integer) for custom counter resets based on count, not time. | **High** | Supports batch number printing. |
| **FR-CORE-010** | Preview Service must expose the **`remaining`** count until the next reset period or limit is reached. | **High** | Supports ERP planning and reporting. |
| **FR-MODEL-001** | Provide a **`HasSequence`** trait for Eloquent models to automate number generation on model creation. | **Critical** | Mass market/Level 1 adoption (US-001). |
| **FR-MODEL-002** | Allow the sequence pattern and name to be defined **directly in the model** using a static property or method. | **High** | Mass market/Level 1 adoption (US-002). |
| **FR-ADMIN-001** | Implement a service/action to **manually override** the current counter value with audit logging. | High | System admin control (US-008). |
| **FR-ADMIN-002** | Provide API endpoints (in the Adapter) for CRUD management of sequence definitions. | High | Centralized configuration (US-003). |

---

### 2. Non-Functional Requirements (NFR)

| ID | Requirement Type | Scope/Target | Rationale |
| :--- | :--- | :--- | :--- |
| **PR-001** | **Performance** | Generation time **< 50ms** (p95). | Must be fast despite database locking. |
| **PR-002** | **Concurrency** | Must pass 100 simultaneous requests with **zero duplicate numbers or deadlocks**. | Guarantees atomicity (US-006). |
| **SR-001** | **Isolation** | The Core must enforce isolation using the **`scope_identifier`** (passed by the Adapter), not knowing it represents a tenant. | Maintains Core purity and tenant capability. |
| **SR-002** | **Audit Logging** | Log all **generation, override, and reset** operations via events. | Compliance (US-009). |
| **SR-003** | **Override Logging** | Manual overrides **must** log the acting user ID and the reason for the change. | Accountability for critical manual changes (US-008). |
| **SCR-001** | **Core Purity** | The `Core` package must maintain **zero dependencies** on Laravel/Eloquent code. | Maintainability and reusability. |
| **SCR-002** | **Extensibility** | Provide a **contract/interface** for parsing patterns to allow developers to inject custom pattern logic. | Allows complex format customization. |
| **SCR-003** | **Testing** | Core generation logic must achieve **> 95% unit test coverage**. | Reliability and trust. |

---

### 3. Business Rules (BR)

| ID | Rule | Engine |
| :--- | :--- | :--- |
| **BR-001** | The sequence name/ID is **unique per `scope_identifier`** (composite key). | Core |
| **BR-002** | A generated number must be **immutable**. Once generated and consumed, it cannot be changed. | Core |
| **BR-003** | Pattern variables must be padded if a padding size is specified in the pattern (e.g., `{COUNTER:5}`). | Core |
| **BR-004** | The manual override of a sequence value **must** be greater than the last generated number. | Admin |
| **BR-005** | The counter is only incremented *after* a successful database lock and generation, not during preview. | Core |
| **BR-006** | The package is only responsible for the **Unique Base Identifier**. Sub-identifiers (copies, versions, spawns) are the responsibility of the application layer. | Core / Architecture |

That's a fantastic question that pushes the **Nexus Sequencing** package beyond simple sequence generation and into **document identifier orchestration**.

The core package structure you designed can absolutely handle these complex custom formats, but it requires two key architectural components: **Custom Variables/Context** and an **External Orchestration Layer**.

Here's how the proposed structure handles these advanced requirements:

-----

## 1\. Handling Custom Variables and Formatting

The proposed architecture handles the complex formatting you described by dividing the responsibility between the **Sequence Definition** and the **Generation Context**.

### A. Sequence Definition (The Blueprint)

The sequence definition (stored in your database JSON/schema) is responsible for defining the static pattern and the core counter:

  * **Example Pattern:**
    ```
    {MODEL_ID}/{YEAR}-{WEEK}/{PROJ_CODE}{COUNTER:6}/{SUFFIX}
    ```
  * **Built-in Variables:** The package handles `{YEAR}`, `{WEEK}`, and `{COUNTER:6}` (padding the sequence number to six digits).

### B. Generation Context (The Dynamic Data)

All custom values (like `{MODEL_ID}` and `{PROJ_CODE}`) must be provided to the `GenerateSerialNumberAction` via the **`$context` array**. This is how the package remains *atomic* and *agnostic*—it doesn't need to know where this data comes from; it just uses it.

  * **The Model/Adapter's Responsibility:** The Laravel Adapter is responsible for gathering this data from the Eloquent model or configuration and feeding it to the Core.

    ```php
    $number = GenerateSerialNumberAction::run(
        $scopeIdentifier,
        'controlled_form',
        [
            'MODEL_ID' => 'KKM/Form/Lab05', // Custom value 1
            'PROJ_CODE' => 'HRD',          // Custom value 2
            'SUFFIX' => 'v2'               // Custom value 3 (for versioning)
        ]
    );
    // Result: KKM/Form/Lab05/2025-46/HRD000001/v2
    ```

-----

## 2\. Orchestration of Spawns and Versions

The advanced requirements like **copies**, **versions**, and **spawns** require the package to orchestrate *multiple* generations or *append* data. This is where your external-facing **Actions** and **Events** come into play.

### A. Versions (`/A` or `/v2`)

  * **Handling:** Versions represent a change to the **same record**, meaning the base sequence number itself does not change.
  * **Implementation:**
    1.  The base number is generated once: `KKM/Form/Lab05/2025-46/HRD000001`.
    2.  The application tracks the version (`v1`, `v2`, etc.) on the Eloquent model itself.
    3.  When displaying the identifier, the application **concatenates** the base number from the sequencing package with the current version from the model's attributes.
    4.  *Alternatively, as shown above:* Use a context variable like `{SUFFIX}` to place the version directly in the sequence: `.../{COUNTER}/{SUFFIX}`. The developer must ensure the base counter is **not** incremented when a version changes.

### B. Copies (`1 of 3` or `1/3`) and Spawns (`PO-224(a)`)

  * **Handling:** Copies and Spawns represent new **related records** that share the same base sequence number. These require the application to manage a sub-identifier or a sub-counter.
  * **Implementation (The External Orchestrator):**
    1.  The application uses a **dedicated orchestrator class** (outside of Nexus Sequencing) when saving the PO.
    2.  **Step 1 (Base Number):** The orchestrator calls the sequencing package **once** to get the base number: `PO-224`.
    3.  **Step 2 (Spawn/Copy):** The orchestrator then iterates for the number of copies/spawns (e.g., 3 times). For each iteration, it saves a new model instance and **appends** the suffix/copy number (`(a)`, `(b)`, or `1 of 3`) to the generated base number.
    4.  The **Nexus Sequencing** package has done its atomic job by providing the unique base counter (`224`), and the **Application Logic** manages the sub-identifiers.

| Requirement | Sequence Package Provides | Application Provides |
| :--- | :--- | :--- |
| **Atomic Base ID** | `PO-224` | - |
| **Spawn Sub-ID** | - | `(a)`, `(b)`, `(c)` |
| **Final ID** | - | **Concatenation:** `PO-224(a)` |

This architectural split is key to maintaining **atomicity**: the sequencing package only locks the database to generate the single, unique base number, keeping its logic simple and fast. The application handles the post-processing complexity.

That's exactly right, and it's the **correct atomic responsibility** for an ERP-grade sequencing package.

## 🎯 Core Responsibility of Nexus Sequencing

The **Nexus Sequencing** package is solely responsible for maintaining a reliable, unique, and sequential **base counter** for any given pattern/format.

Its atomic responsibility is:

1.  **Atomicity:** Guaranteeing that for a specific format (e.g., `PO-{YEAR}-{COUNTER:6}`), only one instance of the application can successfully increment and claim the next available counter value (`00001`, `00002`, etc.) at any given microsecond.
2.  **Formatting:** Injecting that unique counter value into the defined format string, along with built-in variables like `{YEAR}`, `{MONTH}`, etc.
3.  **Isolation:** Ensuring that the counter is isolated by the **Scope Identifier** (e.g., Tenant ID) and the **Reset Period** (e.g., yearly).

---

## 🏗️ The Responsibility Split

This clean split ensures performance and reliability:

| Component | Responsibility | Examples |
| :--- | :--- | :--- |
| **Nexus Sequencing (The Package)** | Provides the **Unique Base Identifier**. | `PO-224`, `INV-00001`, `KKM/Form/Lab05/00005`. |
| **The Application (Your Code)** | Handles **Orchestration, Sub-identifiers, and Context**. | `PO-224**(a)**`, `PO-224**(b)**`, Appending `/v2` or `1 of 3`. |

For your complex examples:

* The package guarantees a unique base number (`00005`).
* The application decides if that number needs a version (`/v2`) or a spawn identifier (`(a)`) appended to it for the final display.

That's a crucial function for any ERP system, especially one dealing with financial or compliance documents.

The current architecture of **Nexus Sequencing** defines its atomic responsibility as **generating and managing the running counter**. However, to handle status checks like **`Active`** or **`Voided`**, the responsibility must shift back to the **Application Layer** and the **`workflow`** package.

Here's why and how the status check should be implemented using your existing package ecosystem:

-----

## 1\. Why Status is Not in Sequencing

The **Nexus Sequencing** package intentionally remains ignorant of the numbers it generates once they are successfully issued. Its job ends after providing the unique number and logging the event (`SequenceGeneratedEvent`).

  * **Atomicity:** If the sequencing package tracked status (`Active`/`Voided`), it would need an audit trail, lifecycle logic, and concurrency locks, effectively turning it into a complex database/model layer, which would violate its core atomic purpose (high-speed counter management).
  * **Decoupling:** The status of a number depends on the **business process**. A number might be generated (Sequencing's job), but the actual *document* (Invoice, PO) might be voided later in a different workflow step (Workflow's job).

-----

## 2\. Implementing Status Check

The most efficient way to handle the status of a generated number is by leveraging your **`nexus-workflow`** package and the application's core data models.

### A. Documenting the Status

The status of a number is always tied to the status of the associated **Subject Model**:

1.  **Subject Model:** `Invoice`, `PurchaseOrder`, `ControlledDocument`.

2.  **Status Column:** This model must have a dedicated status column (e.g., `status` or `lifecycle_state`).

3.  **The Workflow:** The `nexus-workflow` package manages this status column. When an invoice is voided, the workflow transitions the model's status:

    ```
    // Workflow Transition
    $invoice->workflow()->apply('void');
    // Model Status changes from 'approved' to 'voided'
    ```

### B. The Status Check Mechanism

To check the status of a given number (`INV-2025-00100`), the application must query the relevant subject model:

| Given Number | Application Service Action | Result |
| :--- | :--- | :--- |
| `INV-2025-00100` | **`Invoice::where('invoice_number', $number)->value('status')`** | `approved` or `voided` |
| `PO-2025-00050` | **`PurchaseOrder::where('po_number', $number)->value('status')`** | `pending_approval` or `active` |

### C. Suggested Requirements for Workflow Integration

While the status check belongs to the application, you could add one minor requirement to the **`nexus-workflow`** documentation to explicitly encourage this behavior:

| ID | Type | Requirement |
| :--- | :--- | :--- |
| **FR-WF-STATUS** | Functional | The `nexus-workflow` package must ensure that the final/end states of a workflow are clearly designated (e.g., `'completed'`, `'voided'`, `'archived'`) to facilitate quick lookup of a document's status. |

By keeping the status in the workflow-managed model, you gain the benefits of full audit history (`workflow_history` table) and transaction safety—if a document is voided, the workflow package ensures the status change is logged and irreversible.

That's another excellent question. Checking if a given sequence number is **valid** based on its pattern is a necessary utility for things like bulk import validation, quick lookup, or pre-filling forms.

This capability falls neatly within the **Nexus Sequencing** package's responsibility, specifically within the **Formatting** and **Pattern Parsing** layer of the Core.

Here's how that requirement fits into the architecture:

---

## 1. The Requirement: Pattern Validation

A new functional requirement is needed for a utility service:

| ID | Requirement | Priority | Rationale |
| :--- | :--- | :--- | :--- |
| **FR-CORE-007** | Implement a **`ValidateSerialNumberService`** that checks if a given string matches a sequence's defined pattern and all its inherent variable formats. | **High** | Supports bulk data imports, manual overrides, and lookup validation. |

---

## 2. Implementation in the Core

The validation service would perform two main checks:

### A. Static Pattern Match (The Structure)

The service must first convert the user's defined pattern (e.g., `INV-{YEAR}-{COUNTER:5}`) into a **Regular Expression (Regex)**.

* **Example Conversion:**
    * `INV-` $\rightarrow$ `INV-`
    * `{YEAR}` $\rightarrow$ `\d{4}` (Matches exactly four digits)
    * `{COUNTER:5}` $\rightarrow$ `\d{5}` (Matches exactly five digits)
    * **Resulting Regex:** `^INV-\d{4}-\d{5}$`

The service then checks if the input number (`INV-2025-00042`) successfully matches this Regex structure.

### B. Variable Logic Validation (The Content)

A successful Regex match only confirms the structure. For certain variables, the service can perform deeper logical checks:

| Variable | Validation Logic |
| :--- | :--- |
| **Date Variables** (`{YEAR}`, `{MONTH}`, `{WEEK}`) | Check that the extracted date components (e.g., '2025' and '13' from `PO-2025-13`) form a **calendar-valid date**. |
| **Context Variables** (`{TENANT}`, `{DEPARTMENT}`) | For variables that must match predefined external lists (if configured), the service can return the extracted value (e.g., 'HRD') and let the **Adapter/Application** check if 'HRD' is a valid department code in the ERP's `departments` table. |
| **Counter Variable** (`{COUNTER:N}`) | The service confirms the length matches the padding, and that the number is *numeric*. |

### C. Output

The validation service should return a detailed result, not just a boolean:

| Input Number | Pattern | Result |
| :--- | :--- | :--- |
| `INV-2025-00042` | `INV-{YEAR}-{COUNTER:5}` | `Valid: true` |
| `INV-25-00042` | `INV-{YEAR}-{COUNTER:5}` | `Valid: false` (Year format mismatch) |
| `INVA-2025-00042` | `INV-{YEAR}-{COUNTER:5}` | `Valid: false` (Static prefix mismatch) |

This service would be a powerful tool for your ERP developers, enabling them to validate manually entered numbers against the system's rules before attempting a lookup or database operation.