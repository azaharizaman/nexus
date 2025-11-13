# **NEXUS ERP SYSTEM ARCHITECTURE DOCUMENT**

## **Executive Summary and Document Context**

| Field | Description |
| :---- | :---- |
| **TL;DR (Too Long; Didn't Read)** | Nexus ERP is built as a collection of small, independent (atomic) Laravel packages. This ensures **Maximum Atomicity**, making the ERP infinitely scalable, extensible, and reusable. The **nexus/erp** composer package serves as a lean **Orchestrator** and **API Presentation Layer**, distributable via `composer require nexus/erp`. |
| **Purpose of this Document** | To serve as the single, authoritative architectural blueprint defining the boundaries, technical stack, naming conventions, communication protocols (Contracts & Events), and decomposition of all packages within the Nexus ERP project. |
| **Addressed Pain Points** | Monolithic architecture complexity, circular dependencies, difficulty in scaling individual components, and high cost/risk when modifying core business logic. |
| **Motivation** | To cut the 6-12 month slug of building a complex ERP by providing a reusable, atomic foundation, fulfilling the vision: "What if the future of Enterprise Software is not to be bought but to be built." |
| **Intended Audience** | The Architectural Review Board (ARB), Core Nexus ERP Developers, Package Developers, and System Integrators building on the Nexus platform. |
| **Project Structure** | **Monorepo Design:** Source code in `src/` with `Nexus\Erp` namespace, distributable as `nexus/erp` package. **Edward CLI Demo:** Terminal-only Laravel app in `apps/edward/` demonstrating Nexus ERP capabilities (tribute to JD Edwards terminal ERP systems). |

### **Table of Contents**

1. Introduction and Architectural Mandate  
2. Defining Architectural Boundaries  
3. Technical Stack  
4. Feature Toggling Architecture  
5. Security Model and Governance  
6. Deployment, CI/CD, and Operations Strategy  
7. Standardized Package Naming Conventions (Architectural Governance)  
8. The "Where Does This Go?" Decision Guide (Refactoring Checklist)  
9. Technical Refactoring Directives  
10. Architectural Decomposition: Atomic Package Brainstorm  
11. Conclusion and Future Outlook  
12. Internationalization and Localization (i18n/l10n) Architecture

## **1\. Introduction and Architectural Mandate**

The Nexus ERP project is built on the philosophy of **Maximum Atomicity**. Our goal is to ensure that the core business logic is encapsulated in small, highly focused, and reusable Laravel packages that can theoretically function *independently* of the main Nexus ERP application. Nexus ERP aims to cut the initial slug for any developer or organization that wants to build ERP but can’t afford the 6-12 months slug. The motto of Nexus ERP is :

” What if the future of Enterprise Software is not to be bought but to be built.”

This document serves as the guide for all new feature development and the ongoing refactoring effort. The primary objective is to define a clear architectural boundary between **Atomic Packages** and the **Nexus ERP Core**.

### **Core Goal**

**The Atomic Rule:** All business logic that governs a single, independent domain of the ERP (e.g., UOMs, Serial Numbers, Currencies) MUST reside in its own package. The Nexus ERP Core is responsible only for **Orchestration, Configuration, and API Presentation**.

## **2\. Defining Architectural Boundaries**

When developing a new feature or refactoring existing code, the first question must be: **"Is this core domain logic, or is this integration/presentation?"**

### **A. The Atomic Package (The "Service Layer")**

Packages are self-contained Laravel applications/services that are **headless** by design (no blade/frontend logic).

| Responsibility | Description | Example Code |
| :---- | :---- | :---- |
| **Domain Logic** | All models, migrations, business rules, calculations, and validators for a single domain. | UOM conversion algorithms, Serial Number voiding logic. |
| **Data Persistence** | Handling all CRUD operations for the package's specific models. | UomRepository, SerialTracker model. |
| **API Endpoints (Internal)** | Packages MUST define their own API routes, but these are for **internal use only** (e.g., for local testing or package-internal calls) and are not the public-facing ERP API. | GET /api/uom-management/uoms |
| **Isolation** | A package **MUST NOT** be aware of the existence of other atomic packages. | nexus-uom cannot directly call a class from nexus-accounting. |

How Packages Communicate:  
Packages must communicate via Contracts (Interfaces) and Events. If Package A needs to use Package B, Package A defines a PHP Interface (Contract) for the service it needs, and the Nexus ERP Core binds the concrete implementation from Package B to that interface.

### **B. Nexus ERP Core (The "Orchestrator")**

The **nexus/erp** package is the **API Presentation Layer** and the **Service Orchestrator**, distributable via composer. It is a true headless application that only communicates to the external world via API, GraphQL and WebSockets. It contains the bare minimum code necessary to make the system function as a unified ERP.

**Package Structure:**
- **Namespace:** `Nexus\Erp`
- **Location:** Root-level `src/` directory
- **Installation:** `composer require nexus/erp`
- **Auto-Discovery:** ErpServiceProvider for Laravel package auto-discovery

The main users are system integrators, developers or machine-to-machine services, rarely the end users as the "Head" part needs to be developed by other developers before it can be made public.

| Responsibility | Description | Example Code |
| :---- | :---- | :---- |
| **Public API Routes** | Defines the external, unified ERP API endpoints that consumers (frontends) will use. **Includes API Documentation (OpenAPI/Swagger).** | GET /api/v1/purchase-orders |
| **Orchestration** | Logic that requires coordinating data or actions across **two or more** atomic packages. | Creating a Purchase Order that requires models from nexus-accounting and serial numbers from nexus-sequencing. |
| **Service Container Binding** | ErpServiceProvider and configuration logic that binds the concrete package implementations to the contracts defined by other packages. | `Nexus\Erp\ErpServiceProvider` |
| **High-Level Configuration** | Configuration that affects the application as a whole (e.g., middleware stacking, global rate limiting, CORS). **Includes setup for Laravel first-party packages like Sanctum (API Auth) and Reverb/Echo (WebSockets).** | config/app.php overrides. |

**Edward CLI Demo:**
- **Purpose:** Terminal-only Laravel application demonstrating Nexus ERP capabilities
- **Location:** `apps/edward/` directory
- **Inspiration:** Tribute to JD Edwards terminal ERP systems (1970s-1990s)
- **Usage:** `cd apps/edward && php artisan edward:menu`
- **Features:** Interactive CLI interface with ASCII art banner, 7 module menus (Tenant, User, Inventory, Settings, Reports, Search, Audit)
- **Target Users:** Developers, system integrators, automated workflows, CI/CD pipelines

## **3\. Technical Stack**

This section outlines the foundational technologies, frameworks, and data platforms selected for the ERP system. The stack prioritizes performance, scalability, real-time capabilities, and developer experience, leveraging the robustness of the Laravel ecosystem.

### **3.1 Core Framework and Language**

| Component | Technology | Version / Requirement | Rationale |
| :---- | :---- | :---- | :---- |
| **Backend Framework** | Laravel Framework | Latest stable release | Provides a rich ecosystem, robust ORM (Eloquent), and powerful service container for rapid, modular development. |
| **Programming Language** | PHP | **8.3 and above only** | Strict typing, JIT performance enhancements, and modern syntax ensure high performance and code quality. |
| **Real-time Communication** | Laravel Reverb & Echo | First-party package | Provides WebSocket capabilities for real-time updates and push notifications across the application without external services (like Pusher). |

### **3.2 Data Layer and Persistence**

The data layer is designed for maximum consistency, flexibility, and performance, opting for a unified database platform.

#### **3.2.1 Primary Database Platform: PostgreSQL**

* **Exclusive Use:** All application persistence will be managed exclusively by **PostgreSQL**.  
* **Structured Data:** PostgreSQL provides robust **ACID** (Atomicity, Consistency, Isolation, Durability) guarantees, making it ideal for critical ERP data like transactions, inventory levels, and user management.  
* **Unstructured Data:** We will leverage PostgreSQL's native **JSONB** data type for storing unstructured data, such as flexible configurations, audit trails, and module-specific metadata. This approach brings the best of both worlds:  
  1. **ACIDity:** Unstructured data is still managed within the transactional guarantees of PostgreSQL.  
  2. **Flexibility:** It allows modules to store complex, evolving schemas without costly schema migrations.  
  3. **Performance:** JSONB supports efficient indexing and querying, allowing complex NoSQL-like operations directly within the SQL environment.

#### **3.2.2 Caching and Sessions**

* **Technology:** **Redis**  
* **Use Cases:** Dedicated solely for high-speed key-value storage, including caching application results (e.g., reports, complex queries) and managing user session state.

#### **3.2.3 Primary Key Strategy: UUIDs (Mandatory)**

All primary keys for transactional and master data models **MUST** use **Universally Unique Identifiers (UUIDs)** instead of traditional auto-incrementing integers.

| Requirement | Value | Rationale in ERP Context |
| :---- | :---- | :---- |
| **Primary Key Type** | **UUID (preferably ULID)** | Enables distributed ID generation and enhances security. |
| **Database Column** | uuid or binary(16) (for efficiency) | Standardized data type across all models. |

**Rationale for Mandating UUIDs:**

1. **Security & Headless API:** Prevents **Enumeration Attacks**. Since the ERP is headless, clients only interact via the API. Using UUIDs prevents attackers or malicious users from guessing the existence of other records or predicting the next record ID (e.g., by changing /invoices/123 to /invoices/124).  
2. **Distributed ID Generation (Atomicity):** Atomic Packages can generate their own unique IDs **before** persisting a model to the database. This is critical for supporting:  
   * **Offline Mode:** Allowing clients/packages to create records without immediate database connectivity.  
   * **Cross-Database Replication:** Simplifies merging data from independent systems without ID collisions.  
   * **Maximum Atomicity:** Reinforces the idea that each package is an independent service.  
3. **Database Performance Mitigation (ULIDs):** While random UUIDs can cause index fragmentation in databases, we will use **ULIDs** (Universally Unique Lexicographically Sortable Identifiers). ULIDs are a type of UUID that includes a timestamp component, making them **time-sortable** and therefore much more efficient for database indexing and retrieval (PostgreSQL B-tree indexes) than pure random UUIDs.

### **3.3 Search and Indexing**

* **Search Engine:** Meilisearch  
* **Integration:** Laravel Scout (acting as the Core driver/adapter)

#### **3.3.1 Centralized Search Architecture (ERP Core)**

To address the requirement of implementing search at the **ERP Core** level, we will employ an **Event-Driven Indexing** strategy to ensure packages remain decoupled from the specific search engine technology (Meilisearch).

**Recommended Architecture:**

1. **Packages Declare Searchable Data (Decoupling):**  
   * Packages (e.g., Sales, Inventory, HRM) do **not** interact with Laravel Scout or Meilisearch directly.  
   * When data is created, updated, or deleted, the package fires a specific, documented **Event** (e.g., InventoryItemUpdatedEvent). The payload of this event contains only the necessary data to be indexed (e.g., ID, name, description, tags).  
2. **ERP Core Search Listener:**  
   * The ERP Core houses a dedicated **Indexing Service Listener**.  
   * This Listener subscribes to all relevant package events. Upon receiving an event, it calls the **Core Search Service**.  
3. **Core Search Service (Abstraction Layer):**  
   * The ERP Core defines a SearchService interface (e.g., Core\\Contracts\\SearchService).  
   * The Core provides a concrete implementation, the **Scout/Meilisearch Adapter**, which handles the interaction with the Laravel Scout layer.  
   * This adapter transforms the generic event data into the format Meilisearch requires and pushes it to the relevant index.

Benefit:  
This design makes the packages search-engine-agnostic. If we ever need to switch from Meilisearch to another solution (like ElasticSearch), we only need to update the Scout/Meilisearch Adapter within the ERP Core, without touching any individual package code. The ERP Core serves as the single source of truth for how indexing and searching occur across the entire system.

### **3.4 Security Technologies**

* **API Authentication:** **Laravel Sanctum**. Used for simple, token-based authentication for single-page applications (SPAs) and issuing long-lived API tokens for machine-to-machine integrations (system integrators).  
* **Authorization Management:** The **nexus-identity-management** package will provide the core RBAC engine and persistence, utilizing Laravel's built-in Gate and Policy system.

## **5\. Security Model and Governance**

The security model is defined by three principles: **Authentication (AuthN), Authorization (AuthZ),** and **Data Isolation (Tenancy)**.

### **5.1 Authentication (AuthN)**

* **Mechanism:** Token-based authentication using **Laravel Sanctum**.  
* **Target Users:** System Integrators, Machine-to-Machine services, and the "Head" Frontend application.  
* **Implementation:** Handled by the **Nexus ERP Core** to unify the authentication layer for all internal package APIs.

### **5.2 Authorization (AuthZ) \- RBAC**

All authorization is managed by the **nexus-identity-management** package and is strictly **Tenant-Scoped**.

* **Role-Based Access Control (RBAC):** Users are assigned **Roles** (e.g., Administrator, Sales Clerk, Viewer). Roles are granted **Permissions** (e.g., view-purchase-orders, create-invoices).  
* **Enforcement:** Authorization checks utilize Laravel's Policies and Gates.  
  * **Controllers/API:** Check for the permission before executing the action ($user-\>can('create-invoices')).  
  * **Models/Eloquent:** Check permissions via Policies before performing CRUD operations (Gate::allows('update', $invoice)).  
* **Principle of Least Privilege:** All user roles will be designed to grant the minimum permissions required for their tasks.

### **5.3 Data Isolation (Tenancy)**

This is the most critical security layer for a SaaS application and is strictly enforced by the **nexus-tenancy-management** package.

* **Tenant ID Constraint:** Every transactional and master data model **MUST** include a tenant\_id foreign key.  
* **Global Scope:** The nexus-tenancy-management package **MUST** register a **Global Query Scope** that automatically adds a WHERE tenant\_id \= \<current\_tenant\_id\> clause to *every* relevant database query.  
* **Rationale:** This ensures that even if application logic fails or a developer forgets a check, the database layer physically prevents one tenant from viewing or modifying another tenant's data.

### **5.4 Encryption and Data Security (New Mandate)**

All data, both in motion and at rest, must be protected by industry-standard encryption protocols.

| Area | Requirement | Standard / Mechanism | Rationale |
| :---- | :---- | :---- | :---- |
| **Data in Transit** | **Mandatory API Encryption** for all external and internal API calls, including WebSockets. | **TLS 1.3** and strong cipher suites. | Prevents man-in-the-middle attacks and ensures confidentiality of data sent over the network. |
| **Data at Rest (PII/Secrets)** | Sensitive data fields **MUST** be encrypted before storage. | **Application-Level Encryption (AES-256-CBC/GCM)** using Laravel's built-in Encryptable attribute/cast. | Protects against database compromises, ensuring data is unintelligible without the application key. |
| **Data at Rest (Database/Disk)** | The entire PostgreSQL volume/disk **MUST** be encrypted. | **Transparent Data Encryption (TDE)** provided by the underlying cloud infrastructure (e.g., AWS KMS, Azure Key Vault). | Ensures physical security of the database server media. |
| **Secret Management** | **Mandatory use of a dedicated Secret Manager.** | **Cloud KMS** (Key Management Service) or equivalent vault service. **NEVER** store production secrets (e.g., API keys, database credentials) directly in .env files. | Centralizes key rotation and access control for highly sensitive credentials. |
| **Password Hashing** | All user passwords **MUST** use a modern, slow, and non-reversible hashing algorithm. | **Bcrypt or Argon2id** (Laravel default is preferred). | Industry best practice to protect against rainbow table attacks. |

## **6\. Deployment, CI/CD, and Operations Strategy**

The deployment strategy is designed to support the **atomic, modular architecture** while ensuring high availability, speed, and scalability.

### **6.1 Containerization and Infrastructure**

* **Containerization (Mandatory):** The Nexus ERP Core and all Atomic Packages **MUST** be deployed via **Docker containers**. This standardizes the runtime environment and dependencies (PHP 8.3, Composer packages) across all environments.  
* **Orchestration:** **Kubernetes (K8s)** is the required orchestration engine. K8s allows for:  
  * **Horizontal Scaling:** Easily scale out individual components (e.g., the Queue Worker pod for the Core, or the Search Listener service) based on traffic or job volume.  
  * **Self-Healing:** Automatic container restarts and health checks.  
* **Infrastructure:** We will maintain a **Cloud-Agnostic** design. The core deployment logic (Docker/K8s manifests) should be portable across major providers (AWS EKS, GCP GKE, Azure AKS).

### **6.2 Monorepo CI/CD Pipeline**

The CI/CD pipeline **MUST** be designed to manage the monorepo efficiently using a "Change Detection" approach.

| Stage | Process | Rationale |
| :---- | :---- | :---- |
| **Change Detection (Pre-Build)** | Pipeline detects which specific packages/directories (e.g., packages/nexus-uom) have changed since the last successful build using Git history. | Saves time and resources by only testing and building affected packages. |
| **Build Artifacts** | Build Docker images for the affected Atomic Packages and the Nexus ERP Core. | Creates immutable, versioned artifacts ready for deployment. |
| **Testing** | Run unit, feature, and integration tests, ensuring tests specific to *changed* packages are executed first. | Guarantees quality and validates inter-package Contracts. |
| **Staging Deployment** | Deploy new images to the Staging environment using K8s manifests. | Provides a safe environment for quality assurance (QA) and regression testing. |
| **Production Rollout** | Use a **Rolling Update** strategy in K8s to gradually replace old pods with new ones, minimizing downtime and allowing for immediate rollback if errors occur. | Ensures zero-downtime deployment for the SaaS offering. |

### **6.3 Monitoring and Observability**

* **Logging:** Centralized logging using the **nexus-audit-log** package and **ELK/Loki stack**. All logs (API requests, exceptions, domain events, and database changes) must be aggregated and searchable.  
* **Application Performance Monitoring (APM):** Integrate an APM tool (e.g., New Relic, Datadog) to track:  
  * **API Latency:** Track the execution time of Nexus ERP Core API endpoints.  
  * **Database Query Time:** Monitor the performance of queries generated by Atomic Packages.  
  * **Queue Processing Time:** Track job completion rates and worker efficiency.  
* **Alerting:** Define threshold-based alerts for core services (high error rates, low memory/CPU, or exceeding API latency SLOs).

## **7\. Standardized Package Naming Conventions (Architectural Governance)**

The package suffix dictates its primary responsibility, data volatility, and architectural role. Developers **MUST** adhere to this classification when creating or refactoring packages.

| Suffix | Architectural Role | Responsibility | Data Type / Volatility | Examples |
| :---- | :---- | :---- | :---- | :---- |
| **\-master** | **Master Data Definition** (The "What") | Defines, stores, and validates the static core reference data for a domain. | Low Volatility (Reference data), High Reusability. | nexus-item-master, nexus-employee-master |
| **\-management** | **Transactional State & Rules** (The "How") | Manages state changes, applies complex business rules, and persists transactional data within a domain. **Note:** As of Phase 8.2, core packages simplified names (nexus-uom, nexus-inventory, nexus-sequencing, nexus-settings, nexus-tenancy, nexus-backoffice). | High Volatility (Transactional records), State Persistence. | nexus-workflow-management, nexus-procurement-management |
| **\-interface** | **External Abstraction** (The "Boundary") | Provides a stable façade (Contract) for interacting with complex internal logic or external systems. Hides underlying implementation complexity. | Medium Volatility (Swappable implementation logic). | nexus-ledger-interface, nexus-payment-interface |
| **\-engine** | **Stateless Execution** (The "Calculation") | Executes pure, computational logic, algorithms, or rule-sets based on inputs, without maintaining persistence for the execution itself. | Stateless, High Performance/Computation. | nexus-workflow-engine, nexus-reporting-engine |

## **8\. The "Where Does This Go?" Decision Guide (Refactoring Checklist)**

Use this checklist for every new file or feature.

| \# | Question | Decision Path | Location |
| :---- | :---- | :---- | :---- |
| **1\.** | **Is this logic exclusively about a single domain (e.g., only UOMs, only Currencies, only User Permissions)?** | Yes, it is **atomic**. | **The Atomic Package** |
| **2\.** | **Does this logic need to call, reference, or be aware of a class or model from *another* Nexus atomic package?** | Yes, it requires cross-package knowledge (Coordination). | **Nexus ERP Core (Orchestration Layer)** |
| **3\.** | **Is this code a public-facing endpoint for a client application to consume (e.g., part of the v1 API)?** | Yes, this is **presentation**. | **Nexus ERP Core (Routes)** |
| **4\.** | **Is this an Interface/Contract that defines *how* a specific service should be consumed?** | Yes, this promotes decoupling. | **nexus/erp** (Contracts directory) - The orchestration layer manages all inter-package contracts. |
| **5\.** | **Is this code responsible for registering a package's service provider or setting up global environment variables?** | Yes, this is **bootstrapping**. | **Nexus ERP Core (Service Providers)** |
| **6\.** | **Is this code can be release as an individual composer package that make sense to be use in othe applications and with meaningful purpose to be use on its own?** | Yes, this is **Purposeful Composer Package**. | **The Atomic Package** |

### **Example Scenarios:**

| Scenario | Location | Rationale |
| :---- | :---- | :---- |
| **Calculating volume conversion (Liters to Gallons).** | nexus-uom | Pure, atomic domain logic. |
| **Defining the structure of the PurchaseOrder model.** | nexus-accounting | Atomic domain logic for the accounting module. |
| **The API endpoint POST /api/v1/po/create which takes a PurchaseOrder request, validates the UOMs using the UOM service, and requests a serial number for the PO from the Serialization service.** | **nexus/erp** (Core) | This orchestrates two different packages (accounting and sequencing). |
| **The logic for checking if a serial number has been voided.** | nexus-sequencing | Pure, atomic domain logic. |

## **9\. Technical Refactoring Directives**

### **A. Dependency Management**

**NEVER** directly reference another package's concrete class.

* **Bad:** new \\Nexus\\Uom\\Services\\UomConverter();  
* **Good:** app(\\Nexus\\Contracts\\UomConverterInterface::class)-\>convert(...)

The binding of UomConverterInterface to \\Nexus\\Uom\\Services\\UomConverter is handled centrally in the **nexus/erp** ErpServiceProvider.

### **B. Event-Driven Architecture (EDA)**

For reactive updates between packages, use Laravel Events to ensure packages remain unaware of their consumers.

| Action | Location | Mechanism |
| :---- | :---- | :---- |
| **Triggering an action** | **Atomic Package** | Dispatch a Domain Event (e.g., UomUpdated::dispatch($uom)). |
| **Reacting to an action** | **Nexus ERP Core** | Define a Listener in the Core's EventServiceProvider that executes cross-package orchestration logic. |

Example: When a Serial Number is voided in nexus-sequencing, it dispatches SerialNumberVoided. The nexus/erp Core listens for this event and then calls the updateStatus method on the relevant PurchaseOrder via the accounting service. The sequencing package never knew what consumed the event.

### **C. Laravel Version and Headless Focus**

1. **Strict Headless:** Ensure **no** Blade views, sessions, or typical frontend scaffolding exists in **any** atomic package or the Nexus ERP Core. Everything must be API-driven (JSON responses).  
2. **Laravel 12 Standard:** All package structures must adhere strictly to the Laravel 12 package development best practices.  
3. **Monorepo Tools:** We will continue to leverage the monorepo structure for parallel development and unified testing, but adherence to the atomic principles is the priority.



### **E. Foundational Principles: SOLID**

All code within the packages and main application **MUST** adhere to the **SOLID principles** to ensure maintainability, testability, and long-term stability.

#### **1\. Single Responsibility Principle (SRP)**

* **Definition:** A class should have only one reason to change.  
* **Requirement:** Each class, service, or component **MUST** have a single, well-defined responsibility.  
* **Example:** InvoiceCalculator only handles invoice calculations, not data persistence or notification logic.

#### **2\. Open/Closed Principle (OCP)**

* **Definition:** Software entities should be open for extension but closed for modification.  
* **Requirement:** Use interfaces, abstract classes, and dependency injection to allow behavior extension without modifying existing core code.  
* **Example:** Payment providers **MUST** implement a PaymentGatewayContract interface, allowing new providers to be added without changing the nexus-payment-interface core.

#### **3\. Liskov Substitution Principle (LSP)**

* **Definition:** Objects of a superclass should be replaceable with objects of a subclass without breaking the application.  
* **Requirement:** All implementations of an interface **MUST** be substitutable for each other.  
* **Example:** Any SearchServiceContract implementation (e.g., Scout or Database) can replace another without breaking the calling Service.

#### **4\. Interface Segregation Principle (ISP)**

* **Definition:** No client should be forced to depend on methods it does not use.  
* **Requirement:** Create focused, specific interfaces rather than large, general-purpose ones.  
* **Example:** Prefer a thin SearchableContract over a monolithic EntityContract that forces search, audit, and export methods onto every implementor.

#### **5\. Dependency Inversion Principle (DIP)**

* **Definition:** High-level modules should not depend on low-level modules. Both should depend on abstractions.  
* **Requirement:** All services **MUST** depend on contracts (interfaces), not concrete implementations. This is the foundation of the **Contracts-First** mandate.

### **F. Core Framework Principles (The "Laravel Way")**

This defines how we leverage Laravel's core features to ensure consistency across all packages, especially concerning dependency management.

#### **1\. Dependency Injection (DI) and the Service Container**

* **Requirement F.1.1:** All services, repositories, and strategies **MUST** be resolved from the Laravel Service Container.  
* **Requirement F.1.2:** Avoid **new keywords** for services; use constructor or method injection. This is the primary way we achieve DIP.  
* **Requirement F.1.3:** PHP 8 Attributes (like \#\[Singleton\]) **SHOULD** be used to manage bindings for cleaner service provider code where applicable.

#### **2\. Judicious Use of Facades**

* **Requirement F.2.1:** Facades (e.g., Log::info(), Cache::get()) **MAY** be used within **Controllers, Action classes, and Laravel-specific classes** (like Middleware, Commands).  
* **Requirement F.2.2:** Facades **MUST NOT** be used inside **core business logic** (Services, Repositories). These classes **MUST** use constructor injection (e.g., \_\_construct(private LoggerInterface $logger)) to remain framework-agnostic and highly testable.  
* **Rationale:** This separation ensures business logic remains independent of the framework wrapper and can be easily mocked in tests.

#### **3\. Queues & Jobs for Long-Running Tasks**

* **Requirement F.3.1:** Any process that is **not instantaneous** or **relies on a third-party API** (e.g., sending an email, generating a report, calling an AI model) **MUST** be implemented as a **Queueable Job**.  
* **Rationale:** This ensures the headless API responds instantly (HTTP 202 Accepted) and maintains high throughput, a critical requirement for a headless system.

### **G. Mandated Application Patterns (The "How We Build")**

These are the **primary patterns** for building new features. All developers **MUST** follow these patterns to ensure consistency across the monorepo.

#### **1\. Service-Repository Pattern**

* **Requirement G.1.1:** All data access **MUST** go through Repository classes that implement repository contracts.  
* **Requirement G.1.2:** All business logic **MUST** reside in Service classes that depend on repository contracts.  
* **Requirement G.1.3:** Controllers **MUST NOT** directly access Repositories or Models. They **MUST** call Services or Actions.

#### **2\. Action Class Pattern (The Command Pattern)**

* **Requirement G.2.1:** To enforce "thin controllers," any **complex, single-use-case business logic** (e.g., "Create Invoice," "Register User," "Run EOM Report") **MUST** be encapsulated in a dedicated **Action Class**.  
* **Recommendation:** Use the lorisleiva/laravel-actions package as the preferred implementation, as it unifies controllers, jobs, and commands into a single, testable class.  
* **Rationale:** This is our implementation of the **Command Pattern**, making logic portable and reusable from various entry points (API, Console, Queue).

#### **3\. Data Transfer Object (DTO) Pattern (The Contract)**

* **Requirement G.3.1:** This is the **primary pattern for data integrity**. DTOs **MUST** be used for all data moving between application layers, especially for **API request/response contracts** and the contracts for inter-package communication.  
* **Requirement G.3.2:** DTOs **MUST** be used in place of "loose" arrays for method parameters and return values.  
* **Recommendation:** Use the spatie/laravel-data package. This provides strict, self-documenting API contracts, which is essential for a headless system.

#### **4\. Event-Driven Architecture (The Observer/Mediator Pattern)**

* **Requirement G.4.1:** All significant domain actions **MUST** emit events that other modules can subscribe to.  
* **Requirement G.4.2:** **Cross-module communication MUST occur through events, never direct method calls.**  
* **Rationale:** This reinforces the **Maximum Atomicity** mandate by enabling module independence and decoupling (Observer/Mediator patterns).

#### **5\. Pipeline Pattern (The Chain of Responsibility)**

* **Requirement G.5.1:** For any **multi-step, filterable process** (e.g., "Order Checkout," "Data Import Validation"), the **Pipeline Pattern MUST be used.**  
* **Rationale:** This allows atomic packages to "tap into" a core process and add or modify steps without modifying the core Orchestrator code, promoting the **Open/Closed Principle**.

#### **6\. Factory Pattern**

* **Requirement G.6.1:** When creating a **complex object** or a **class based on runtime condition** (e.g., selecting a payment provider from a config key), a **Factory Class** **MUST** be used.  
* **Rationale:** This encapsulates complex object creation and respects the **Open/Closed Principle**.

### **H. Patterns Not Globally Mandated**

This section clarifies why certain complex patterns are **not mandated system-wide**, to prevent over-engineering.

#### **1\. CQRS (Command Query Responsibility Segregation)**

* **Stance:** CQRS is **NOT a system-wide mandate**.  
* **Rationale:** The complexity of maintaining separate read/write models is generally overkill for standard CRUD modules.  
* **Exception:** A module **MAY** use CQRS if it has high-performance requirements, high-contention scenarios, or vastly different read/write needs (e.g., a dedicated real-time analytics module).

#### **2\. Decorator & Composite Patterns**

* **Stance:** These are **developer-discretion patterns**.  
* **Rationale:** These are highly effective tools for solving specific problems (e.g., **Composite** for tree structures like Bill of Materials, **Decorator** for dynamically adding features like tax calculation to an invoice). They are not foundational architectural principles but tools to be used when appropriate.

## **10\. Architectural Decomposition: Atomic Package Brainstorm**

### **A. Architectural & Decoupling Core (The System Foundation)**

These packages manage the environment, security boundaries, and runtime isolation for the entire application. **Note:** Contract definitions (PHP Interfaces) for inter-package communication are managed by **nexus/erp** as the orchestration layer, not in a separate package.

| Package Name | Domain Responsibility |
| :---- | :---- |
| **nexus-tenancy** | **Multi-Tenancy:** Manages the definition, configuration, and runtime isolation of data and resources for separate tenants/companies. |
| **nexus-identity-management** | User, Role, and Permission management (Authentication, Authorization, RBAC). |
| **nexus-feature-toggling-management** | **Feature Flag Persistence:** Manages the definition, storage, and CRUD operations for feature flags at the system, tenant, and user levels. |

### **B. Fundamental Master Data & Management Core (The ERP Constants)**

These packages manage system constants and configuration that all other business modules depend on.

| Package Name | Domain Responsibility |
| :---- | :---- |
| **nexus-organization-master** | Defines the organizational structure, including Offices, Departments, Teams, Units, and Staffing Hierarchy. |
| **nexus-fiscal-calendar-master** | Defines static master data for Fiscal Years, Periods, and statutory holidays. |
| **nexus-sequencing** | Controls the generation, persistence, and validation of all document numbering sequences (e.g., PO-0001, Invoice-0002). |
| **nexus-tax-management** | Centralized tax codes, rates, rules, and jurisdiction handling. |
| **nexus-currency-management** | Currency codes, exchange rates, conversion rates and history. |
| **nexus-uom** | Unit of Measure (UOM) codes, conversion logic, precision, and packaging rules. |
| **nexus-reporting-engine** | Financial report generation, report templates, and data aggregation logic. |

### **C. Core Financial & Operational Packages (The Business Engine)**

These packages manage the primary domain models and transactional processes that define the business.

| Package Name | Domain Responsibility |
| :---- | :---- |
| **nexus-party-management** | Management of all "Parties" (Customers, Vendors, Employees, Contacts, Legal Entities). |
| **nexus-employee-master** | Core HR master data (Job Titles, Departments, Reporting Hierarchy, basic Employee Profile). **Relies on nexus-organization-master.** |
| **nexus-accounts-payable-management** | Manages the full lifecycle of Vendor Invoices, Payment Authorizations, and reconciliation against the Vendor Ledger. |
| **nexus-accounts-receivable-management** | Manages the full lifecycle of Customer Invoices, Receipts/Collections, and reconciliation against the Customer Ledger. |
| **nexus-cash-and-bank-management** | Manages all Bank Transaction Logs, Cash Accounts, Bank Reconciliation processes, and transactional fund transfers. |
| **nexus-ledger-interface** | Core double-entry accounting engine (General Ledger, Chart of Accounts, Journal Entries). |
| **nexus-payment-interface** | Bank account master, payment method processing, and transaction execution/reconciliation. |

### **D. Universal Supporting Packages (Good to Have for General ERP)**

These packages provide critical cross-cutting capabilities but are not strictly necessary for the initial launch of basic financial modules.

| Package Name | Domain Responsibility |
| :---- | :---- |
| **nexus-ai-automation-management** | **AI/ML Inference and Orchestration:** Provides standardized contracts and services for AI/ML inference (e.g., document classification, data extraction, demand prediction, sentiment analysis). Handles external API calls, model response normalization, and cost/usage tracking. |
| **nexus-audit-log** | **Cross-cutting Logging:** Provides standardized, transactional tracking of model changes (who, what, when, field changes). All packages will be configured to use this service. |
| **nexus-sequencing** | Unique serial number generation, versioning, status tracking, and voiding (as described in the prompt). |
| **nexus-workflow-engine** | **Execution Logic:** The pure, stateless computation of state transitions, rule evaluation, **approval logic**, and **escalation triggers.** |
| **nexus-workflow-management** | **State Persistence:** Tracking the status, history, users, and instance data of all running workflows (e.g., preventing duplicate workflows for a given document). |
| **nexus-document-management** | Secure storage, version control, and access control for attachments and documents. |
| **nexus-notification-service** | Centralized queueing and dispatching of notifications (Email, SMS, internal alerts, WebSockets). |
| **nexus-settings** | Key-value store for global application, tenant, or user-specific settings. |

### **E. Universal Commerce & Operations Packages (Strongly Recommended)**

These packages manage fundamental business transactions and resources (inventory, assets, time) that are critical for nearly every industry, even if the specific implementation details (e.g., manufacturing vs. trading) differ slightly in the Core Orchestrator.

| Package Name | Domain Responsibility | Applicable Industries |
| :---- | :---- | :---- |
| **nexus-purchase-order-management** | Purchase requests, purchase orders, vendor bill reconciliation. | All (General, Manufacturing, Maintenance, Fleet, Legal, Medical, Gov) |
| **nexus-sales-order-management** | Sales quotes, sales orders, invoicing, returns, and fulfillment coordination. | All (General, Manufacturing, Legal, Maintenance, Medical) |
| **nexus-inventory** | Stock levels, basic costing (FIFO/LIFO/Average), material movements, stocktake. | General, Manufacturing, Warehouse, Fleet (Spares/Fuel), Medical (Supplies) |
| **nexus-asset-management** | Tracking internal (or customer-owned) fixed assets, warranty, depreciation, and service history. | Manufacturing, Maintenance, Fleet, Government, Legal (IT/Equipment) |
| **nexus-time-activity-management** | Highly granular time entry tracking (e.g., for employee time or client billing), activity logs, and expense association. | General Service, Manufacturing (Labor), Legal, Maintenance, Medical |

### **F. Industry-Specific Packages**

These packages are necessary for the vertical functionality required by a specific industry. The original "General Service/Trading" modules have been absorbed into 5C. Separating these packages by industry ensures clarity and makes the ERP's extension points immediately obvious.

#### **1\. Manufacturing (Must-Have: 2, Good-to-Have: 2\)**

| Criticality | Package Name | Core Responsibility |
| :---- | :---- | :---- |
| Must Have | **nexus-bill-of-materials-management** | Product recipes, multi-level BOMs, component consumption tracking. |
| Must Have | **nexus-work-order-management** | Job tickets, production execution, actual vs. standard cost tracking. |
| Good to Have | **nexus-production-scheduling-management** | Capacity planning and sequencing of work orders across resources. |
| Good to Have | **nexus-quality-control-management** | Non-conformance reporting, inspection plans, test results tracking. |

#### **2\. Finance Service Provider (Must-Have: 2, Good-to-Have: 1\)**

| Criticality | Package Name | Core Responsibility |
| :---- | :---- | :---- |
| Must Have | **nexus-loan-management** | Loan application, disbursement, repayment schedules, and interest calculations. |
| Must Have | **nexus-customer-onboarding-management** | KYC (Know Your Customer) data collection, verification, and AML checks. |
| Good to Have | **nexus-compliance-reporting-engine** | Regulatory report generation (computation/output). |

#### **3\. Legal Service Provider (Must-Have: 1, Good-to-Have: 1\)**

| Criticality | Package Name | Core Responsibility |
| :---- | :---- | :---- |
| Must Have | **nexus-case-file-management** | Case status, matter documents, opposing parties, court dates. |
| Good to Have | **nexus-client-trust-accounting-management** | Managing funds held on behalf of clients (segregated accounting). |

#### **4\. Maintenance Service Provider (Must-Have: 1, Good-to-Have: 1\)**

| Criticality | Package Name | Core Responsibility |
| :---- | :---- | :---- |
| Must Have | **nexus-service-call-scheduling-management** | Dispatching technicians, tracking service history, job completion status. |
| Good to Have | **nexus-preventive-maintenance-management** | Defining and triggering planned maintenance schedules. |

#### **5\. Government/Public Service (Must-Have: 2, Good-to-Have: 1\)**

| Criticality | Package Name | Core Responsibility |
| :---- | :---- | :---- |
| Must Have | **nexus-budget-management** | Fund accounting, tracking expenditures against approved budgets. |
| Must Have | **nexus-policy-management** | Storing and referencing internal and external policy documents and rules. |
| Good to Have | **nexus-citizen-portal-integration** | Handling service requests and feedback from the public. |

#### **6\. Fleet Management Services (Must-Have: 2, Good-to-Have: 1\)**

| Criticality | Package Name | Core Responsibility |
| :---- | :---- | :---- |
| Must Have | **nexus-vehicle-telematics-management** | Ingesting and processing GPS, fuel usage, and sensor data from vehicles. |
| Must Have | **nexus-maintenance-scheduling-management** | Tracking vehicle repair history and scheduling planned servicing. |
| Good to Have | **nexus-route-optimization-engine** | Logic for optimizing delivery or service routes (pure computation). |

#### **7\. Warehouse Services (Must-Have: 2, Good-to-Have: 1\)**

| Criticality | Package Name | Core Responsibility |
| :---- | :---- | :---- |
| Must Have | **nexus-inventory-location-management** | Managing bins, aisles, zones, and tracking inventory by exact location (WMS). |
| Must Have | **nexus-fulfillment-management** | Managing and validating fulfillment processes (wave planning, picking lists). |
| Good to Have | **nexus-barcode-validation-engine** | Logic for validating inventory movements via scanned barcodes (pure computation). |

#### **8\. General Practitioner Medical (Must-Have: 2, Good-to-Have: 1\)**

| Criticality | Package Name | Core Responsibility |
| :---- | :---- | :---- |
| Must Have | **nexus-patient-record-management** | Storing structured medical history, diagnoses, and treatment plans (EMR). |
| Must Have | **nexus-appointment-scheduling-management** | Booking, managing, and notifying patients and practitioners of appointments. |
| Good to Have | **nexus-billing-claim-management** | Generating and tracking insurance claims and patient bills. |

#### **9\. General Service/Trading (Good-to-Have: 1\)**

| Criticality | Package Name | Core Responsibility |
| :---- | :---- | :---- |
| Good to Have | **nexus-commission-management** | Sales commission rules and payout calculations. |

## **11\. Conclusion and Future Outlook**

### **Assumptions**

1. **Strict Monorepo Adherence:** Development and testing will be contained within a single monorepo structure utilizing package discovery and management tools.  
2. **Contracts First:** All cross-package functionality will be implemented and consumed via PHP Contracts defined in **nexus/erp** (the orchestration layer) before any concrete implementation is written.  
3. **Laravel Ecosystem:** The nexus/erp Core will exclusively use Laravel's standard features and first-party packages (e.g., Sanctum, Reverb) for API and communication tooling.  
4. **Database Isolation (Tenancy):** The nexus-tenancy package will enforce logical data isolation for tenants, allowing for either single-database or multi-database tenancy configurations.

### **Risks & Mitigation Plan**

| Risk | Impact | Mitigation Plan |
| :---- | :---- | :---- |
| **Architectural Drift** | Packages start referencing concrete classes, leading to a "distributed monolith" and loss of atomicity. | **Layer 1: Static (Build-Time) Enforcement:** Implement **Static Analysis** (e.g., PHPStan/Rector rules) to detect cross-package concrete references before commit/build. |
| **Performance Overhead** | Excessive use of Events and Service Container resolution may introduce latency in high-volume transactions. | **Asynchronous Processing:** Critical updates and non-time-sensitive coordination must be moved to queues (Listeners) to decouple execution and ensure low latency for user-facing API calls. |
| **Dependency Hell** | Over-reliance on internal packages for minor features. | **Minimum Atomicity Threshold:** Enforce a rule that a package must contain at least 5 core models or 500 lines of unique business logic to justify its existence; otherwise, its logic belongs in the Core Orchestrator or an existing package. |

### **Extendability and Scalability**

* **Horizontal Scalability:** The Atomic Package design ensures that individual domains (e.g., nexus-inventory) can be conceptually isolated and even deployed as standalone microservices if volume demands it, simply by moving its database and API routes out of the nexus/erp Core Orchestrator's scope.  
* **Easy Extension:** New industry-specific packages (Section 10F) can be added by simply creating the new package and registering its Service Provider in the Nexus ERP Core, without modifying any existing business logic.

### **Future Innovation Path**

1. **AI Integration Expansion:** Fully leverage the **nexus-ai-automation-management** package to move beyond simple data extraction into advanced predictive services (e.g., automated cash flow forecasting, proactive maintenance scheduling).  
2. **Event Stream Processing:** Implement a dedicated package (e.g., nexus-event-streamer) for publishing key domain events (like PO Completed, Invoice Paid) to an external stream platform (Kafka/AWS Kinesis) for real-time analytics or integration with third-party tools.  
3. **Dynamic UI Generation:** Investigate creating a separate package that consumes the API routes and model schemas defined in the atomic packages to **dynamically generate frontend UIs**, further streamlining the effort required for system integrators to build their head application.

## **12\. Internationalization and Localization (i18n/l10n) Architecture**

To support a global user base, all language strings and regional data formatting **MUST** be decoupled from the core business logic.

### **12.1 Language Management (i18n)**

* **Source of Truth:** Language preference (Locale) is stored in the **nexus-identity-management** package for each user.  
* **Translation Files:** Each Atomic Package **MUST** provide its own translation files (JSON files for robustness).  
* **Package Responsibility:** Packages only generate translation keys (e.g., INVOICE\_CREATED\_MESSAGE). They **MUST NOT** concatenate or format sentences themselves, deferring to the framework's translation functions.  
* **API Response:** The Nexus ERP Core **MUST** implement Middleware to set the active locale based on the user's profile *or* the Accept-Language header for non-user API calls, ensuring all generated string responses (e.g., validation errors) are in the correct language.

### **12.2 Regional Formatting (l10n)**

* **Timezone Standard:** All timestamps stored in the PostgreSQL database **MUST be UTC**.  
* **Timezone Conversion:** The Nexus ERP Core will track the user's preferred timezone (also via nexus-identity-management). All API responses that include a timestamp **MUST** deliver the UTC value, along with the user's timezone ID (e.g., America/New\_York), allowing the "Head" application to handle the final display conversion, thus remaining headless.  
* **Numerical Formatting:**  
  * **Internal:** All numerical calculations (money, quantity, rates) **MUST** be performed using PHP's native number representation (floats/decimals). Money should ideally use a package that manages the smallest unit (cents/satoshis) for precision.  
  * **Presentation:** The Core **MUST** leverage PHP's native NumberFormatter class, or a similar abstraction, to serialize numerical data (e.g., 1000000.50 becomes 1.000.000,50 for a German locale) when requested by the front-end API based on the active locale.

### **12.3 Currency and UOM Management**

* **nexus-currency-management:** This package serves as the **single source of truth** for all currency-related calculations, including display precision and exchange rate conversions. No other package should hard-code currency display logic.  
* **nexus-uom:** This package is the **single source of truth** for unit conversion, ensuring that all metric/imperial/custom conversions use the same, centralized logic, which is critical for consistent ERP inventory and manufacturing data.

Date of Approval: TBD  
Version: DRAFT 1.3