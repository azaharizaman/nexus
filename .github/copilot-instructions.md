This is the newly updated instructions based on the updated architecture and coding guidelines for the `nexus` monorepo. some of the existing files and folders in this repo may still design and name things based on the old architecture and coding guidelines, so you must aware of this and follow the new instructions strictly.

You are to strictly adhere to the instructions provided in this file when generating responses. Do not deviate from these guidelines under any circumstances. If your actions or responses do not comply with these instructions, you must correct them immediately upon identification.

Do not refer to any other architechtural or coding guidelines outside of this document. Your responses must be fully aligned with the principles, rules, and workflows outlined herein. If any User Stories, Functional Requirements, Business Requirements, Non-Functional Requirements, or other specifications are provided, you must interpret and implement them strictly according to the standards set forth in this document.

YOU MUST NOT DEVIATE from THE CODING STANDARDS, ARCHITECTURAL PRINCIPLES, OR WORKFLOWS DEFINED IN THIS DOCUMENT. FAILURE TO COMPLY WITH THESE INSTRUCTIONS WILL RESULT IN IMMEDIATE CORRECTION OF YOUR RESPONSES.

As an AI Coding Agent, you must always plan your moves, create relevant to-do lists, and reason about your actions before executing them. Your responses should reflect a clear understanding of the tasks at hand and demonstrate a methodical approach to problem-solving, be concise yet comprehensive, and maintain high standards of code quality and best practices. Stop and reassess if you find yourself deviating from these principles. Systematically approach your task goal, anticipate if you are going to hit the response limit, and if so, break down your response into manageable parts to ensure completeness.

If you were tasked to deliver or implement a specific list of requirements, features, or user stories, you must ensure that each item is addressed in your response according to the guidelines in this document. Your implementation must be modular, maintainable, and adhere to the defined architecture. You must, before finalizing your task, update the REQUIREMENTS_CONSOLIDATED.md file by inserting the given requirements, features, or user stories in a well-structured format within that file, or update the existing entries as necessary to reflect the current state of the project. The table format for the REQUIREMENTS_CONSOLIDATED.md file is as follows:

```markdown
| Package/App (Namespace) | Requirement # | Description (concise and understandable by developer) | Implemented in (Class / File / Method) | Status (status of the delivery or implementation for this requirement) | Notes (to support the status or linkbacks to other Requirements #) | Date (when this row item was recently updated) |
| --- | --- | --- | --- | --- | --- | --- |
| `Nexus\Crm` | `BR-001` | Users cannot self-assign leads (configurable) | `packages/Crm/src/Core/AssignmentStrategyResolver.php` | Completed | Level 2 only; enforced by assignment resolver. | 2025-11-16 |
| `Nexus\Crm` | `BR-002` | All state changes must be ACID-compliant | `packages/Crm/src/Core/PipelineEngine.php` | Completed | All state changes must be ACID-compliant. | 2025-11-16 |
| `Nexus\Crm` | `BR-003` | Stale leads auto-escalate after configured timeout | `packages/Crm/src/Core/EscalationService.php` | Completed | Stale leads auto-escalate after configured timeout. | 2025-11-16 |
| `Nexus\Crm` | `BR-004` | Compensation activities execute in reverse order | `packages/Crm/src/Core/PipelineEngine.php` | Partially Completed | Compensation activities execute in reverse order. | 2025-11-16 |
| `Nexus\Crm` | `BR-005` | Delegation chain maximum depth: 3 levels | `packages/Crm/src/Core/AssignmentStrategyResolver.php` | Completed | Delegation chain maximum depth: 3 levels. | 2025-11-16 |
| `Nexus\Crm` | `BR-006` | Level 1 code remains compatible after Level 2/3 upgrades | `packages/Crm/src/Traits/HasCrm.php` | Completed | Level 1 code remains compatible after Level 2/3 upgrades. | 2025-11-16 |
| `Nexus\Crm` | `BR-007` | One CRM instance per subject model | `packages/Crm/src/Core/CrmManager.php` | Completed | One CRM instance per subject model. | 2025-11-16 |
| `Nexus\Crm` | `BR-008` | Parallel branches must all complete before proceeding | `packages/Crm/src/Core/PipelineEngine.php` | Completed | Parallel branches must all complete before proceeding. | 2025-11-16 |
| `Nexus\Crm` | `BR-009` | Assignment checks delegation chain first | `packages/Crm/src/Core/AssignmentStrategyResolver.php` | Completed | Assignment checks delegation chain first. | 2025-11-16 |
| `Nexus\Crm` | N/A | Unit & Feature tests added for ProjectManagement & API flows | `packages/ProjectManagement/tests/Unit/*` and `apps/Atomy/tests/Feature/*` | Completed | Unit tests for managers and feature tests for controllers and repos added. Coverage: target 90%+, run tests to verify. | 2025-11-16 |

```
Note on the above format: DO NOT add in table seperator or formatting outside of the table itself. Only add rows as needed within the table structure shown.

# Update on Atomic Package Structure and Naming Conventions

The naming convention for files and directories may differ based on the current repository structure, but the principles and guidelines outlined herein must always be followed annd you should update the naming accordingly.

To guide you with the naming convention and Namespaces, here is some match of old and new:
- Old: Nexus\Erp       New: Nexus\Atomy
- Old: folder_name: packages\nexus-crm    New: folder_name: packages\Crm
- Old: folder_name: packages\nexus-audit-logger    New: folder_name: packages\AuditLogger

All atomic packages folders in packages must have .gitignore and properly configured composer.json files.

Remember, DO NOT include any view files or templates in any of the packages. All UI-related code must reside solely within the applications in the apps directory that is nt within the current scope of this repository or project


-----

# NEXUS\_ARCHITECTURE.md: Nexus Monorepo Architectural Guidelines & Rules

This document outlines the architecture of the `nexus` monorepo. Its purpose is to enforce a clean, scalable, and decoupled component structure. Adhering to these rules is mandatory for all development.

**The Core Philosophy: "Logic in Packages, Implementation in Applications."**

Our architecture is built on one primary concept: **Decoupling**.

  * **`📦 packages/`** contain pure, framework-agnostic business logic. They are the "engines."
  * **`🚀 apps/`** are the runnable applications. They are the "cars" that use the engines.

**Tech Stack:**

  * PHP 8.3+
  * Laravel 12.x (for applications only)
  * Composer for dependency management
  * PSR-4 Autoloading
  * PHPUnit for testing
  * PostgreSQL for data storage (in applications)

-----

## 1\. 🌲 Proposed Monorepo Structure

This visual map illustrates the physical layout of the monorepo. The **`Nexus\Tenant`** package is expanded to serve as the template for all other atomic packages.

```md
nexus/
├── .gitignore
├── composer.json               # Root monorepo workspace configuration (defines 'path' repositories)
├── NEXUS_ARCHITECTURE.md       # (This document)
├── README.md
│
├── 📦 packages/                  # Atomic, publishable PHP packages
│   ├── Hrm/                      # Nexus\Hrm
│   │   ├── composer.json
│   │   └── src/
│   ├── Inventory/                # Nexus\Inventory (Requires 'nexus/uom')
│   │   ├── composer.json
│   │   └── src/
│   ├── Tenant/                   # Nexus\Tenant (The Expanded Template)
│   │   ├── composer.json         # Defines 'nexus/tenant', autoloading
│   │   ├── README.md             # Package-specific documentation
│   │   ├── LICENSE               # Package licensing file
│   │   └── src/                  # The source code root
│   │       ├── Contracts/        # REQUIRED: Interfaces defining persistence needs and data structures
│   │       │   ├── TenantInterface.php         # Data structure contract (What a Tenant IS)
│   │       │   └── TenantRepositoryInterface.php # Persistence contract (How to SAVE/FIND a Tenant)
│   │       ├── Exceptions/       # REQUIRED: Domain-specific exceptions
│   │       │   └── TenantNotFoundException.php
│   │       ├── Services/         # REQUIRED: Core business logic (The "Engine")
│   │       │   └── TenantManager.php           # e.g., createNewTenant(data), switchTenant(id)
│   │       └── NexusTenantServiceProvider.php  # OPTIONAL: Laravel integration point
│   ├── Uom/                      # Nexus\Uom
│   │   ├── composer.json
│   │   └── src/
│   └── Workflow/                 # Nexus\Workflow
│       ├── composer.json
│       └── src/
│
└── 🚀 apps/                      # Deployable applications
    ├── Atomy/                    # Nexus\Atomy (Headless Laravel Orchestrator)
    │   ├── .env.example
    │   ├── artisan
    │   ├── composer.json         # Requires all 'nexus/*' packages
    │   ├── /app
    │   │   ├── /Console
    │   │   ├── /Http/Controllers/Api/
    │   │   ├── /Models           # Eloquent Models (implements package Contracts)
    │   │   └── /Repositories     # Concrete Repository implementations
    │   ├── /config/features.php
    │   ├── /database/migrations/ # ALL migrations for the ERP
    │   └── /routes/api.php
    └── Edward/                   # Edward (Terminal Client)
        ├── .env.example
        ├── artisan
        ├── composer.json
        ├── /app/Console/Commands/
        └── /app/Http/Clients/AtomyApiClient.php

```



-----

## 2\. 📦 The "Packages" Directory (The Logic)

This is the most strictly controlled part of the monorepo. All code in this directory must follow these rules.

> **The Golden Rule:** A package must **never** depend on an application. Applications **always** depend on packages. `Nexus\Tenant` can *never* know what `Nexus\Atomy` is.

### Rules of Atomicity

1.  **Must Be Framework-Agnostic:**

      * Packages must be "pure PHP" or, at most, depend on framework-agnostic libraries (e.g., `psr/log`).
      * **DO NOT** use Laravel-specific classes like `Illuminate\Database\Eloquent\Model`, `Illuminate\Http\Request`, or `Illuminate\Support\Facades\Route`.
      * A light dependency on `illuminate/support` (for Collections, Contracts, etc.) is acceptable if needed, but it should be avoided if possible.

2.  **Must NOT Have Persistence (The "Contract-Driven" Pattern):**

      * Packages **must not** contain database migrations.
      * Packages **must not** contain Eloquent Models or any concrete database logic.
      * Instead, a package *defines its need for persistence* by providing **interfaces (Contracts)** (e.g., `Nexus\Uom\Contracts\UomRepositoryInterface`).

3.  **Must Define Explicit Dependencies:**

      * If a package requires another atomic package, it must be explicitly defined in its own `packages/MyPackage/composer.json`.
      * **Example:** `packages/Inventory/composer.json` must contain `"require": { "nexus/uom": "^1.0" }`.

4.  **Must Be Publishable:**

      * Every package must be a complete, self-contained unit that *could* be published to Packagist at any time. It must have its own `composer.json`, `LICENSE`, and `README.md`.

-----

## 3\. 🚀 The "Apps" Directory (The Implementation)

Applications are the consumers of the packages. They provide the "glue" that connects the logic, the database, and the user.

### Nexus\\Atomy (The Headless Orchestrator)

`Atomy` is the central "headless" ERP backend. It assembles the atomic packages into a single, cohesive application.

  * **Its Job is to Implement Contracts:** `Atomy` contains the *concrete implementations* of the interfaces defined in the packages (e.g., `app/Repositories/DbUomRepository.php`).
  * **Its Job is to Provide Persistence:** `Atomy` is where all **`database/migrations`** and **Eloquent `app/Models`** live. It defines the schema that fulfills the needs of the packages.
  * **Its Job is to Orchestrate Logic:** `Atomy` creates new, higher-level services by combining one or more atomic packages (e.g., `StaffLeaveApprovalWorkflow` using `Nexus\Workflow` and `Nexus\Hrm`).
  * **Its Job is to Be Headless:** All functionality must be exposed via **API/GraphQL**. The `resources/views` directory must remain empty.

### Edward (The Terminal Client)

`Edward` is a TUI (Terminal User Interface) client. It is a consumer of `Atomy`.

  * **Golden Rule:** `Edward` **must never** access the `Atomy` database directly. It **must never** `require` any of the atomic packages (like `nexus/tenant`).
  * **It is Fully Decoupled:** Treat `Edward` as if it were a React frontend or a native mobile app. Its *only* connection to the system is the API provided by `Atomy`.
  * **It is API-Driven:** All its functionality is built on top of an API client (e.g., `app/Http/Clients/AtomyApiClient.php`) that consumes `Atomy`'s endpoints.
  * **Its UI is the Console:** The entire user interface is built using Laravel Artisan commands (e.g., `php artisan edward:dashboard`).

-----

## 4\. 🗺️ Developer Workflow: How to Implement an Atomy Feature

When given a user story for `Atomy`, follow this decision-making process.

**User Story Example:** "As a staff member, I want to view the current stock level of a product in kilograms."

1.  **Question 1: Is the *core logic* missing?**

      * *Analysis:* The core logic for stock management (`StockManager`) and UoM conversion (`UomConverter`) already exists in **`packages/Inventory`** and **`packages/Uom`**. No new atomic package code needed.

2.  **Question 2: How is this logic *stored*?**

      * *Analysis:* We need the `Product` and `Unit` models (which implement the package interfaces). These must exist in `Atomy`.
      * *Action:* Verify that `apps/Atomy/database/migrations/` has the tables and `apps/Atomy/app/Models/` has the corresponding Eloquent models (`Product.php`, `Unit.php`) and Repositories that bind the contracts.

3.  **Question 3: How is this logic *orchestrated*?**

      * *Analysis:* No complex orchestration is needed here; the service call is direct.
      * *Action:* Define a simple service or use the `StockManager` directly in a controller.

4.  **Question 4: How is this logic *exposed*?**

      * *Action:* Go to `apps/Atomy`.
      * Add a new endpoint in `routes/api.php`:
        `Route::get('/v1/inventory/products/{sku}/stock', [InventoryController::class, 'getStock']);`
      * The `InventoryController` will inject the `StockManager` and return the result via JSON.

5.  **Question 5: How does the *user access* this?**

      * *Action:* Go to `apps/Edward`.
      * Add a `getStockLevel` method to `app/Http/Clients/AtomyApiClient.php` to call the new endpoint.
      * Create a new command `app/Console/Commands/ViewStockCommand.php` that uses the client and formats the green text output.

-----

## 5\. 🏗️ Developer Workflow: How to Create a New Atomic Package

When a new business domain is required (e.g., `Nexus\Crm` or `Nexus\AuditLogger`), follow these steps, using the structure of `Nexus\Tenant` as a reference.

1.  **Create Directory:** Create the `packages/AuditLogger` folder.
2.  **Init Composer:** `cd packages/AuditLogger` and run `composer init`.
      * Set the name to `nexus/audit-logger`.
      * Define the PSR-4 autoloader: `"Nexus\\AuditLogger\\": "src/"`.
3.  **Define Contracts:** Define all persistence and model needs as interfaces in `packages/AuditLogger/src/Contracts/`.
      * *Example:* `AuditLogEntryInterface.php`, `AuditLogRepositoryInterface.php`.
4.  **Update Monorepo Root:** Go to the root `nexus/` directory and add your new package to the `repositories` path array.
5.  **Install in Atomy:** `cd apps/Atomy` and run `composer require nexus/audit-logger:"*@dev"`.
6.  **Implement in Atomy:** Go back to `apps/Atomy` and create the necessary migrations, models (`App\Models\AuditLog`), and repositories (`DbAuditLogRepository`) that implement the contracts from `Nexus\AuditLogger`.
7.  **Bind Implementation:** Bind the interface to the concrete implementation in `apps/Atomy/app/Providers/AppServiceProvider.php`.

-----

## 6\. 📜 Strictly Mandated Coding Prinsiples and Design Philosopy

1.  **Single Responsibility Principle (SRP):** Each class or module must have one, and only one, reason to change. This ensures high cohesion and low coupling.
2.  **Dependency Inversion Principle (DIP):** High-level modules should not depend on low-level modules. Both should depend on abstractions (e.g., interfaces). This promotes decoupling and testability.
3.  **Interface Segregation Principle (ISP):** No client should be forced to depend on methods it does not use. Create specific interfaces rather than large, general-purpose ones.
4.  **Open/Closed Principle (OCP):** Software entities (classes, modules, functions, etc.) should be open for extension but closed for modification. This encourages the use of polymorphism and composition over inheritance.
5.  **Code Readability and Maintainability:** Write clean, well-documented code. Use meaningful names, consistent formatting, and include comments where necessary to explain complex logic.
6.  **Automated Testing:** All packages and applications must include comprehensive unit and integration tests. Use PHPUnit or a similar framework to ensure code quality and prevent regressions.
7.  **Inversion of Control (IoC) and Dependency Injection (DI):** Utilize IoC containers to manage class dependencies. This enhances modularity and makes testing easier by allowing for mock implementations.
8.  **Liskov Substitution Principle (LSP):** Subtypes must be substitutable for their base types without altering the correctness of the program. This ensures that derived classes extend the base class behavior without changing its expected functionality.
9.  **YAGNI (You Aren't Gonna Need It):** Avoid adding functionality until it is necessary. This prevents over-engineering and keeps the codebase lean.
10. **No Hard-Coding of Values:** Avoid hard-coding configuration values, URLs, or other constants directly in the code. Use configuration files or environment variables instead to enhance flexibility and adaptability across different environments.
11. **SOLID Principles:** Adhere to the SOLID principles of object-oriented design to create a robust and maintainable codebase.
12. **Clean Architecture:** Follow the principles of Clean Architecture to ensure a clear separation of concerns, making the system easier to understand, maintain, and extend over time.
13. **Factory Pattern for Object Creation:** Use the Factory Pattern to encapsulate the instantiation logic of complex objects. This promotes code reuse and simplifies object creation, especially when dealing with multiple implementations of an interface. Each model or service that requires complex setup should have a corresponding factory class responsible for its creation.

____

## 7\. 📚 Testability and Test Coverage
1.  **Unit Tests for Packages:** Each atomic package must include unit tests that cover all public methods and critical paths. Use mocking to isolate dependencies and ensure tests are focused on the unit being tested. Only PHPUnit is to be used for testing.
2.  **Integration Tests for Applications:** Applications must include integration tests that verify the interaction between different packages and components. These tests should cover end-to-end scenarios to ensure the system works as expected. PHPUnit should be used for integration testing as well.
3.  **Code Coverage Requirements:** Aim for a minimum of 90% code coverage for both packages and applications. Use tools like PHPUnit's built-in coverage reporting to monitor and maintain coverage levels.

-----

## 8\. 📦 Composer and Dependency Management
1.  **Versioning:** Follow Semantic Versioning (SemVer) for all atomic packages. Update the version number in `composer.json` according to the nature of the changes (major, minor, patch).
2.  **Dependency Constraints:** Use strict version constraints in `composer.json` to avoid unexpected breaking changes from dependencies. Prefer using caret (`^`) or tilde (`~`) operators to specify compatible versions.
3.  **Autoloading:** Ensure all packages use PSR-4 autoloading standards. Define the autoload section in `composer.json` appropriately to map namespaces to directory structures.
4.  **Composer Scripts:** Utilize Composer scripts for common tasks such as running tests, generating documentation, or performing code quality checks. This standardizes workflows and improves developer efficiency.

____

## 9\. 📄 Documentation Standards

1.  **Comprehensive README:** Each atomic package must include a `README.md` file that provides an overview of the package, installation instructions, usage examples, and contribution guidelines.
2.  **Inline Documentation:** Use PHPDoc comments to document all classes, methods, and properties. This enhances code readability and provides useful information for developers using IDEs that support PHPDoc.
3.  **Changelog:** Maintain a `CHANGELOG.md` file for each package to document all notable changes, enhancements, and bug fixes in each version. Follow the "Keep a Changelog" format for consistency.

-----