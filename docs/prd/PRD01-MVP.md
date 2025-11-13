# Product Requirements Document (PRD) for Laravel ERP, a true, Headless ERP System

## Section A: Executive Summary

## Section B: Goals and Strategic Vision (Refined)

### B.1. Product Vision

**Vision Statement:**
"To create the world's most flexible, API-first, open-source ERP backbone that empowers developers to build industry-specific business solutions without the constraints of traditional monolithic systems."

**Core Vision Pillars (The "What We Are"):**
* **100% Headless Architecture:** Complete separation of business logic (Laravel backend) from presentation, enabling unlimited frontend possibilities (React, Vue, Flutter, etc.).
* **Modular & Agnostic Design:** A core system that supports pluggable modules and features toggling, making it adaptable to any business size and industry vertical.
* **Developer-First & Open Source:** Clean APIs, comprehensive documentation, and a collaborative ecosystem built on the modern Laravel v12 stack.
* **AI-Native Readiness:** Designed for programmatic consumption, facilitating integration with AI agents, machine learning services, and business automation (via **azaharizaman/huggingface-php** package).

### B.2. Problem Statement and Market Opportunity

This section clearly defines the pain points and positions the Laravel ERP as the solution.

#### B.2.1. Problem Statement: Limitations of Current ERPs
Traditional ERP solutions create significant pain points for modern businesses and developers:
* **Vendor Lock-in & High TCO:** Expensive proprietary licensing, high implementation costs (6-12 months), and complex, costly customization.
* **Monolithic Rigidity:** Tightly coupled UI and business logic hinder innovation, prevent adoption of modern frontends, and limit scalability.
* **Poor Developer Experience:** Outdated technology stacks, complex APIs, and minimal, closed documentation severely limit system integration and extension.
* **Industry Inflexibility:** One-size-fits-all architectures fail to accommodate the unique, high-value workflows required by different industry verticals.

#### B.2.2. Market Opportunity and Unique Value Proposition (UVP)
The market is demanding a modern, API-centric alternative:
* **API Economy:** 83% of enterprises prioritize API-first strategies, creating a demand for a truly headless ERP core.
* **Open Source Growth:** 78% of enterprises use open source for core applications, confirming a willingness to adopt community-driven enterprise software.
* **Laravel/Modern Stack Appeal:** Leveraging the velocity and developer popularity of the Laravel ecosystem to solve enterprise problems.
* **UVP Summary (Why Us?):** We offer a **True Headless Design** with **Zero Frontend Constraints**, built on a **Modern Technology Stack** (Laravel v12, Sanctum, Reverb), optimized for **AI-Agent Integration**, all delivered via a flexible, **Open Source Modular Core**.

### B.3. Business Goals and Success Metrics (KPIs)

These metrics measure product health, community engagement, and market adoption.

#### B.3.1. Primary Success Metrics (Year 1: Foundation & MVP)
| Category | Metric | Target | Rationale |
| :--- | :--- | :--- | :--- |
| **Product Health** | API Test Coverage | $95\%$ | Ensures core business logic integrity and reliability. |
| **Community** | GitHub Stars | $5,000+$ | Quality indicator and measure of developer interest. |
| **Adoption** | Active Installations | $500+$ | Real-world usage and product maturity. |
| **Performance** | API Response Time | $<200\text{ms}$ average | Non-functional quality benchmark for $95\%$ of endpoints. |
| **Features** | MVP Module Completion | $100\%$ (SUB01-SUB17) | Achievement of the initial functional scope. |

#### B.3.2. Secondary Success Metrics (Year 2: Ecosystem & Scaling)
| Category | Metric | Target | Rationale |
| :--- | :--- | :--- | :--- |
| **Ecosystem** | Community Contributors | $100+$ | Measure of collaborative health and platform extensibility. |
| **Market Reach** | Industry Penetration | $5+$ verticals | Validation of the modular, industry-agnostic design. |
| **API Utilisation** | Monthly API Calls | $1\text{M}+$ (across all installs) | Measure of active system utilization and feature adoption. |
| **Efficiency** | Implementation Speed | $<4$ weeks (average time-to-production) | Validation of the developer-first experience. |

### B.4. Target Audience/Users

The PRD recognizes that the product is built **for developers** but ultimately **benefits business stakeholders**.

#### B.4.1. Primary Audience: Technical Implementers (The Builders)
* **Backend Developers:** PHP/Laravel specialists seeking pre-built, reliable business logic to accelerate application development.
* **System Integrators/Consultants:** Firms seeking an open-source, flexible, and rapidly implementable ERP core for client projects.
* **AI/Automation Engineers:** Professionals who rely on high-quality, documented, and event-driven APIs for building automation and AI-driven workflows.

#### B.4.2. Secondary Audience: Business Stakeholders (The Beneficiaries)
* **CTOs & Technical Leaders:** Executives focused on reducing vendor lock-in, lowering TCO, and future-proofing the technology stack.
* **Business Leaders (CFOs/Operations Directors):** Executives seeking highly flexible, modern systems that can be customized to their exact, industry-specific workflows.

---

## Section C: System Architecture & Design Principles

This section details what the system is and how it's architecturally designed, from repository structure to code-level patterns.

### C.1. Core Architectural Strategy: The Monorepo

To balance **developer experience (DX)** with **architectural purity**, the entire system MUST be developed within a **Monorepo** (monolithic repository). This single Git repository will contain the main headless application and all modular packages.

**Rationale:**

1. **Unified Developer Experience:** Solves the "many windows" problem. Developers can open one VS Code instance and work across all modules and the main application.
2. **Atomic Commits:** Allows for cross-package changes to be captured in a single, atomic Git commit, simplifying feature development and bug fixes.
3. **Simplified Versioning:** Fulfills the requirement for unified versioning. A single Git tag (e.g., `v1.2.0`) will apply to all packages simultaneously.
4. **No "Separation Event":** The code is always decoupled in its package structure. There is no risky, pre-release "separation" task.

**Repository Structure:**

```
laravel-erp-monorepo/
├── .git/
├── apps/
│   ├── headless-erp-app/          ← Main Laravel v12 application
│   │   ├── app/
│   │   ├── config/
│   │   ├── routes/
│   │   └── composer.json          ← Requires packages below
│   └── ... (future apps if needed)
├── packages/                       <- In this folder naming we use package to make it comply to composer package but in this document it is refered to as Feature Module
│   ├── core/                       ← Core functionality package
│   │   ├── src/
│   │   ├── tests/
│   │   └── composer.json
│   ├── accounting/                 ← Accounting module package
│   │   ├── src/
│   │   ├── tests/
│   │   └── composer.json
│   ├── inventory/                  ← Inventory module package
│   │   ├── src/
│   │   ├── tests/
│   │   └── composer.json
│   └── ... (20+ other module packages)
├── composer.json                   ← Root composer.json
└── README.md
```

**Composer Path Repositories:**

The main application's `composer.json` uses Composer's `"type": "path"` repository feature to locally require modules:

```json
{
    "name": "azaharizaman/headless-erp-app",
    "require": {
        "php": "^8.2",
        "laravel/framework": "^12.0",
        "azaharizaman/erp-core": "dev-main",
        "azaharizaman/erp-accounting": "dev-main",
        "azaharizaman/erp-inventory": "dev-main"
    },
    "repositories": [
        {
            "type": "path",
            "url": "../../packages/*"
        }
    ]
}
```

This tells Composer: *"When this app asks for `azaharizaman/erp-accounting`, don't look on the internet (Packagist). Look in the local `packages/` directory."*

### C.2. Development & Repository Structure Requirements

**Requirement C.2.1:** The repository MUST contain a root `packages/` directory to house all individual modules (e.g., `accounting`, `inventory`, `core`).

**Requirement C.2.2:** The repository MUST contain a root `apps/` directory to house the primary standalone headless Laravel application.

**Requirement C.2.3:** The main application MUST use Composer's `"type": "path"` repository feature to locally require the modules from the `packages/` directory during development.

**Requirement C.2.4:** Each package in `packages/` MUST have its own:
- `composer.json` with proper package naming (`azaharizaman/erp-{module}`)
- `src/` directory containing all package code
- `tests/` directory with PHPUnit/Pest tests
- `README.md` with package-specific documentation

### C.3. The "Package-First" Mandate (Within the Monorepo)
This strategy remains the key to modularity and enables independent distribution.

**Requirement C.3.1:** All core ERP functionalities (e.g., Core, Accounting, Inventory) MUST be developed as individual, version-controlled Composer packages living in the `packages/` directory.

**Requirement C.3.2:** The "default" standalone headless ERP product (in `apps/headless-erp-app`) will be a minimal Laravel v12 application that simply requires these packages as dependencies.

**Requirement C.3.3:** Any module MUST be independently installable in an external Laravel v12 application via Composer (once published to a repository like Packagist).

**Benefits:**
- Developers get the "feel" of a single bundled application during development
- Architectural purity is maintained through package boundaries
- Packages can be published independently to Packagist for community use
- Third-party developers can use individual modules without the full ERP

### C.4. High-Level System Summary (The ERP Core)
**Laravel ERP** is a **100% headless, API-first ERP core** built on **Laravel v12**, providing comprehensive business logic for enterprise resource planning without any coupled frontend interface. The system serves as a robust backend foundation that developers can integrate with any frontend technology (React, Vue, Flutter, Angular, mobile apps, or even AI agents) through well-documented RESTful APIs.

**Key Characteristics:**
- **Headless by Design:** Zero UI dependencies; all interactions occur through APIs
- **Business Logic Layer:** Complete ERP functionality accessible programmatically
- **Modern Architecture:** Built on Laravel v12 with event-driven, modular design
- **Multi-Tenant Native:** Tenant isolation enforced at the architecture level
- **AI-Ready:** Designed for consumption by AI agents and automation systems
- **Monorepo Structure:** Single repository with multiple packages for optimal DX

### C.5. Headless Architecture Rationale

**Design Philosophy:** The system deliberately has **no default frontend** to maximize flexibility and prevent vendor lock-in at the presentation layer.

**Core Architectural Requirement:**
> **All features, business logic, and data operations MUST be accessible exclusively via documented API endpoints.**

**Why Headless?**
1. **Frontend Freedom:** Organizations can choose any UI technology that fits their needs (web, mobile, desktop, voice, AI)
2. **Multi-Channel Delivery:** Same backend serves web dashboards, mobile apps, kiosks, and third-party integrations simultaneously
3. **Evolutionary UI:** Frontend can be modernized or replaced without touching business logic
4. **Developer Experience:** Clear separation of concerns allows frontend and backend teams to work independently
5. **AI-First Strategy:** APIs are the natural interface for AI agents and automation workflows

**What This Means:**
- ❌ No Blade templates or server-side rendering
- ❌ No bundled JavaScript frameworks (Vue/React components)
- ❌ No default admin panels or dashboards
- ✅ Complete RESTful API for all operations
- ✅ Comprehensive API documentation (OpenAPI/Swagger)
- ✅ WebSocket support for real-time updates
- ✅ Webhook system for event notifications

### C.6. Technical Stack (The "Tech Spec")

The system leverages modern, battle-tested technologies from the Laravel ecosystem and industry standards.

#### C.6.1. Core Framework and Language
| Component | Version | Purpose |
|-----------|---------|---------|
| **PHP** | ≥ 8.2 | Primary language with modern type system and performance |
| **Laravel Framework** | v12.x (latest stable) | Core framework providing routing, ORM, validation, and architectural patterns |
| **Composer** | Latest | Dependency management and package ecosystem |

#### C.6.2. Authentication & Authorization
| Component | Purpose | Implementation |
|-----------|---------|----------------|
| **Laravel Sanctum** | API token authentication and management | Token-based auth for SPA and mobile apps |
| **Spatie Permission** | Role-Based Access Control (RBAC) | Granular permission management and role hierarchy |

#### C.6.3. Real-Time & Event Infrastructure
| Component | Purpose | Use Cases |
|-----------|---------|-----------|
| **Laravel Reverb** | WebSocket server for real-time updates | Live dashboard updates, notifications, collaborative features |
| **Laravel Echo** | Client-side WebSocket abstraction | Real-time event broadcasting to connected clients |
| **Laravel Queue + Horizon** | Asynchronous job processing and monitoring | Background tasks, email, report generation, integrations |

#### C.6.4. Database & Data Layer
| Component | Specification | Purpose |
|-----------|---------------|---------|
| **Primary RDBMS** | PostgreSQL 13+ (REQUIRED) | ACID-compliant transactional data with JSONB support |
| **JSONB Storage** | PostgreSQL JSONB columns | Unstructured and flexible data storage (audit logs, settings, events) |
| **Cache Layer** | Redis or Memcached | Settings cache, session storage, rate limiting |
| **Search Engine** | Laravel Scout + Meilisearch/Algolia | Full-text search across entities |

**Database Strategy (MANDATORY):** PostgreSQL is the exclusive, required database platform for this project. All ACID-required transactional data is stored in PostgreSQL standard tables, while unstructured and flexible data is stored in PostgreSQL JSONB columns. This provides:
- ✅ Full ACID compliance for all transactional data
- ✅ Native JSON support for flexible data structures (audit logs, event payloads, settings)
- ✅ Single database platform reducing operational complexity
- ✅ Superior query capabilities and performance optimization
- ✅ No external dependencies for document storage

#### C.6.5. Development & Quality Tools
| Component | Purpose |
|-----------|---------|
| **Pest PHP** | Testing framework (unit, feature, integration tests) |
| **Laravel Pint** | Code style enforcement (PSR-12 + Laravel conventions) |
| **PHPStan** | Static analysis for type safety and bug detection |
| **Laravel Telescope** | Application debugging and monitoring (development) |
| **Laravel Pulse** | Performance monitoring (production) |

#### C.6.6. API & Integration
| Component | Purpose |
|-----------|---------|
| **OpenAPI/Swagger** | API documentation and specification |
| ~~**Laravel API Resources**~~ | ~~Consistent API response formatting~~ *(Note: Replaced by Spatie Laravel Data - see 3.9.3)* |
| **Fractal (optional)** | Advanced API transformation and pagination |

#### C.6.7. Business Packages (Custom/First-Party)

**Important Namespace Note:** All packages use the **Nexus** namespace (e.g., `Nexus\Erp\Core`) within code, while maintaining the **azaharizaman** vendor prefix in Composer package names for external distribution consistency (e.g., `azaharizaman/erp-core`).

| Package | Composer Name | PHP Namespace | Purpose | Location |
|---------|---------------|----------------|---------|----------|
| **Core Module** | `azaharizaman/erp-core` | `Nexus\Erp\Core` | Multi-tenancy, auth, audit logging | `packages/core/` |
| **UOM Module** | `azaharizaman/erp-uom` | `Nexus\Erp\Uom` | Unit of Measure management | `packages/uom/` |
| **Inventory Module** | `azaharizaman/erp-inventory` | `Nexus\Erp\Inventory` | Inventory and stock control | `packages/inventory/` |
| **Backoffice Module** | `azaharizaman/erp-backoffice` | `Nexus\Erp\Backoffice` | Company/branch/department structure | `packages/backoffice/` |
| **Serial Numbering Module** | `azaharizaman/erp-serial-numbering` | `Nexus\Erp\SerialNumbering` | Document numbering system | `packages/serial-numbering/` |
| **Settings Module** | `azaharizaman/erp-settings-management` | `Nexus\Erp\SettingsManagement` | Application settings and configuration | `packages/settings-management/` |
| **AI/ML Integration** | `azaharizaman/huggingface-php` | `Nexus\Huggingface` | AI/ML integration capabilities | External package |

### C.7. Open-Source Strategy

**Licensing Model:**

The Laravel ERP project will be released under the **MIT License**, providing maximum flexibility for commercial and non-commercial use.

**MIT License Key Provisions:**
- ✅ **Commercial Use:** Organizations can use, modify, and sell products built on this ERP
- ✅ **Modification:** Full freedom to customize and extend the codebase
- ✅ **Distribution:** Can redistribute original or modified versions
- ✅ **Private Use:** Can be used in closed-source, proprietary projects
- ⚠️ **Attribution Required:** Must retain copyright and license notices
- ⚠️ **No Warranty:** Software provided "as-is" without liability

**Why MIT?**
- Maximizes adoption by removing licensing barriers
- Compatible with commercial products and services
- Aligns with Laravel's own MIT licensing
- Encourages contribution from enterprise developers

**Contribution Guidelines:**

Detailed contribution guidelines are documented in `CONTRIBUTING.md` (to be created). Key principles:

1. **Code Quality Standards:**
   - All code must pass Laravel Pint formatting
   - Minimum 80% test coverage for new features
   - PHPStan level 5 compliance required
   - Follow PSR-12 and Laravel conventions (see `CODING_GUIDELINES.md`)

2. **Pull Request Process:**
   - Feature branches from `main` branch
   - Descriptive PR titles following conventional commits
   - Required: tests, documentation updates, changelog entry
   - Minimum 2 approvals from core maintainers

3. **Issue Reporting:**
   - Use GitHub Issues with provided templates
   - Security issues reported privately via email (security@[project-domain].com)
   - Feature requests require PRD/RFC discussion first

4. **Community Standards:**
   - Code of Conduct based on Contributor Covenant
   - Inclusive, respectful communication required
   - Zero tolerance for harassment or discrimination

5. **Governance:**
   - Core team maintains architectural direction
   - Major decisions discussed in RFC process
   - Community voting for non-breaking feature prioritization

**Documentation Standards:**
- All public APIs must have complete PHPDoc blocks
- OpenAPI/Swagger specs auto-generated from code
- Maintained docs at `docs/` directory (PRDs, Plans, Architecture)
- Example implementations in `examples/` directory

**Release Cadence:**
- **Major versions:** Yearly (breaking changes allowed)
- **Minor versions:** Quarterly (new features, backwards compatible)
- **Patch versions:** As needed (bug fixes, security updates)
- **Security updates:** Immediate release when critical

**Links:**
- Full Contribution Guide: `CONTRIBUTING.md`
- Code of Conduct: `CODE_OF_CONDUCT.md`
- Security Policy: `SECURITY.md`
- Coding Standards: `CODING_GUIDELINES.md`
- License: `LICENSE` (MIT)

### C.8. Foundational Principles: SOLID

All code within the packages and main application MUST adhere to the **SOLID principles** to ensure maintainability, testability, and long-term stability.

#### C.8.1. Single Responsibility Principle (SRP)

**Definition:** A class should have only one reason to change.

**Requirement:** Each class, service, or component MUST have a single, well-defined responsibility.

**Examples:**
- ✅ `TenantRepository` - Handles only tenant data access
- ✅ `InvoiceCalculator` - Handles only invoice calculations
- ❌ `UserManager` that handles authentication, authorization, and user profile updates (violates SRP)

#### C.8.2. Open/Closed Principle (OCP)

**Definition:** Software entities should be open for extension but closed for modification.

**Requirement:** Use interfaces, abstract classes, and dependency injection to allow behavior extension without modifying existing code.

**Examples:**
- ✅ Payment providers implement `PaymentGatewayContract` interface
- ✅ New payment methods added without changing existing code
- ❌ Adding `if/else` statements to handle new payment types in existing class

#### C.8.3. Liskov Substitution Principle (LSP)

**Definition:** Objects of a superclass should be replaceable with objects of a subclass without breaking the application.

**Requirement:** All implementations of an interface MUST be substitutable for each other.

**Examples:**
- ✅ Any `SearchServiceContract` implementation can replace another
- ✅ Switching from `ScoutSearchService` to `DatabaseSearchService` doesn't break code
- ❌ Subclass that throws exceptions not declared in parent interface

#### C.8.4. Interface Segregation Principle (ISP)

**Definition:** No client should be forced to depend on methods it does not use.

**Requirement:** Create focused, specific interfaces rather than large, general-purpose ones.

**Examples:**
- ✅ `Searchable` interface with only search-related methods
- ✅ `Auditable` interface with only audit-related methods
- ❌ `EntityContract` interface forcing all entities to implement search, audit, export, and import methods

#### C.8.5. Dependency Inversion Principle (DIP)

**Definition:** High-level modules should not depend on low-level modules. Both should depend on abstractions.

**Requirement:** All services MUST depend on contracts (interfaces), not concrete implementations.

**Examples:**
- ✅ `TenantManager` depends on `TenantRepositoryContract`, not `TenantRepository`
- ✅ Services injected via constructor receive interfaces
- ❌ Service directly instantiating concrete classes with `new` keyword

### C.9. Core Framework Principles (The "Laravel Way")

This defines how we leverage Laravel's core features to ensure consistency across all packages.

#### C.9.1. Dependency Injection (DI) and the Service Container

**Requirement C.9.1.1:** All services, repositories, and strategies MUST be resolved from the Laravel Service Container.

**Requirement C.9.1.2:** Avoid `new` keywords for services; use constructor or method injection. This is the primary way we achieve DIP (Dependency Inversion Principle).

**Requirement C.9.1.3:** PHP 8 Attributes (like `#[Singleton]`) SHOULD be used to manage bindings for cleaner service provider code.

**Example:**
```php
// ✅ CORRECT: Using constructor injection
class TenantManager
{
    public function __construct(
        private readonly TenantRepositoryContract $repository,
        private readonly ActivityLoggerContract $logger
    ) {}
}

// ✅ CORRECT: Using PHP 8 attributes for binding
#[Singleton(TenantRepositoryContract::class)]
class TenantRepository implements TenantRepositoryContract
{
    // Implementation
}

// ❌ INCORRECT: Direct instantiation
class TenantManager
{
    public function create(array $data): Tenant
    {
        $repository = new TenantRepository(); // Violates DIP
        return $repository->create($data);
    }
}
```

#### C.9.2. Judicious Use of Facades

**Requirement C.9.2.1:** Facades (e.g., `Log::info()`, `Cache::get()`) MAY be used within **Controllers**, **Action classes**, and **Laravel-specific classes** (like Middleware, Commands).

**Requirement C.9.2.2:** Facades MUST NOT be used inside **core business logic** (Services, Repositories). These classes must use constructor injection (e.g., `__construct(private LoggerInterface $logger)`) to remain framework-agnostic and highly testable.

**Rationale:** This separation ensures business logic can be tested without Laravel, moved to different frameworks if needed, and mocked easily in tests.

**Example:**
```php
// ✅ CORRECT: Facade in Controller
class TenantController extends Controller
{
    public function store(Request $request)
    {
        Log::info('Creating new tenant');
        return $this->action->run($request->validated());
    }
}

// ✅ CORRECT: Injection in Service
class TenantManager
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}
    
    public function create(array $data): Tenant
    {
        $this->logger->info('Creating tenant', $data);
        // ...
    }
}

// ❌ INCORRECT: Facade in Service
class TenantManager
{
    public function create(array $data): Tenant
    {
        Log::info('Creating tenant'); // Couples service to Laravel
        // ...
    }
}
```

#### C.9.3. Queues & Jobs for Long-Running Tasks

**Requirement C.9.3.1:** Any process that is **not instantaneous** or **relies on a third-party API** (e.g., sending an email, generating a report, calling an AI model) MUST be implemented as a **Queueable Job**.

**Rationale:** This ensures the headless API responds instantly (e.g., `{"message": "Report is being generated"}`) and maintains high throughput, a critical requirement for a headless system.

**Example:**
```php
// ✅ CORRECT: Long-running task as Job
class GenerateFinancialReportJob implements ShouldQueue
{
    public function handle(ReportGeneratorContract $generator): void
    {
        $report = $generator->generate($this->criteria);
        // Store and notify user
    }
}

// Controller dispatches job immediately
public function generateReport(Request $request): JsonResponse
{
    GenerateFinancialReportJob::dispatch($request->validated());
    
    return response()->json([
        'message' => 'Report generation started',
        'status' => 'processing'
    ], 202); // HTTP 202 Accepted
}

// ❌ INCORRECT: Blocking API call
public function generateReport(Request $request): JsonResponse
{
    $report = $this->generator->generate($request->validated()); // Blocks for 30s
    return response()->json($report);
}
```

### C.10. Mandated Application Patterns (The "How We Build")

These are the **primary patterns** for building new features. All developers MUST follow these patterns to ensure consistency across the monorepo.

#### C.10.1. Service-Repository Pattern

**Pattern Definition:** Separate data access (Repository) from business logic (Service).

**Requirement C.10.1.1:** All data access MUST go through Repository classes that implement repository contracts.

**Requirement C.10.1.2:** All business logic MUST reside in Service classes that depend on repository contracts.

**Requirement C.10.1.3:** Controllers MUST NOT directly access Repositories or Models. They must call Services or Actions.

**Example:**
```php
// Repository (Data Access Layer)
interface TenantRepositoryContract
{
    public function findById(int $id): ?Tenant;
    public function create(array $data): Tenant;
}

class TenantRepository implements TenantRepositoryContract
{
    public function create(array $data): Tenant
    {
        return Tenant::create($data);
    }
}

// Service (Business Logic Layer)
class TenantManager
{
    public function __construct(
        private readonly TenantRepositoryContract $repository,
        private readonly ActivityLoggerContract $logger
    ) {}
    
    public function create(array $data): Tenant
    {
        $tenant = $this->repository->create($data);
        $this->logger->log('Tenant created', $tenant);
        event(new TenantCreatedEvent($tenant));
        return $tenant;
    }
}
```

#### C.10.2. Action Class Pattern (The Command Pattern)

**Pattern Definition:** Encapsulate a single use case or business operation in a dedicated class.

**Requirement C.10.2.1:** To enforce "thin controllers," any **complex, single-use-case business logic** (e.g., "Create Invoice," "Register User," "Run EOM Report") MUST be encapsulated in a dedicated **Action Class**.

**Recommendation:** The **`lorisleiva/laravel-actions`** package is the preferred implementation, as it unifies controllers, jobs, and commands into a single, testable class.

**Rationale:** This is our implementation of the **Command Pattern**. It makes logic portable and reusable from controllers, console commands, and queued jobs.

**Example:**
```php
use Lorisleiva\Actions\Concerns\AsAction;

class CreateInvoiceAction
{
    use AsAction;
    
    public function handle(array $data): Invoice
    {
        // Complex business logic here
        $invoice = $this->invoiceRepository->create($data);
        $this->calculateTotals($invoice);
        $this->postToGeneralLedger($invoice);
        event(new InvoiceCreatedEvent($invoice));
        return $invoice;
    }
    
    // Automatically available as:
    // - Controller: CreateInvoiceAction::run($data)
    // - Job: CreateInvoiceAction::dispatch($data)
    // - Command: php artisan invoice:create
}
```

#### C.10.3. Data Transfer Object (DTO) Pattern (The Contract)

**Pattern Definition:** Use typed objects to transfer data between application layers instead of arrays.

**Requirement C.10.3.1:** This is the **primary pattern for data integrity**. DTOs MUST be used for all data moving between application layers, especially for **API request/response contracts**.

**Requirement C.10.3.2:** DTOs MUST be used **in place of "loose" arrays** for method parameters and return values.

**Recommendation:** The **`spatie/laravel-data`** package is the preferred implementation. It replaces the need for Form Requests (validation) and API Resources (transformation) with a single, type-safe class.

**Rationale:** This replaces the older Transformer (Presenter) Pattern and provides strict, self-documenting API contracts, which is essential for a headless system.

**Example:**
```php
use Spatie\LaravelData\Data;

// DTO with validation and transformation
class CreateTenantData extends Data
{
    public function __construct(
        public string $name,
        public string $domain,
        public TenantStatus $status,
        public ?array $configuration = null
    ) {}
    
    // Validation rules
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'unique:tenants'],
            'status' => ['required', 'string', Rule::in(TenantStatus::values())],
        ];
    }
}

// Usage in Controller
public function store(CreateTenantData $data): JsonResponse
{
    $tenant = CreateTenantAction::run($data);
    return TenantResource::make($tenant)->response();
}

// ❌ INCORRECT: Using arrays
public function store(Request $request): JsonResponse
{
    $data = $request->all(); // Loose array
    $tenant = $this->service->create($data);
    return response()->json($tenant);
}
```

#### C.10.4. Event-Driven Architecture (The Observer/Mediator Pattern)

**Pattern Definition:** Use domain events for loose coupling between modules.

**Requirement C.10.4.1:** All significant domain actions MUST emit events that other modules can subscribe to.

**Requirement C.10.4.2:** Cross-module communication MUST occur through events, never direct method calls.

**Rationale:** This is our implementation of the **Observer** and **Mediator** patterns, using Laravel's event bus as the central mediator. It enables module independence and extensibility.

**Example:**
```php
// Event in Inventory module
class StockAdjustedEvent
{
    public function __construct(
        public readonly InventoryItem $item,
        public readonly float $quantity,
        public readonly string $reason
    ) {}
}

// Listener in Accounting module (different package)
class UpdateInventoryValuationListener
{
    #[Listen(StockAdjustedEvent::class)]
    public function handle(StockAdjustedEvent $event): void
    {
        $this->accountingService->updateInventoryAccount(
            $event->item,
            $event->quantity
        );
    }
}
```

#### C.10.5. Pipeline Pattern (The Chain of Responsibility)

**Pattern Definition:** Pass data through a series of processing steps.

**Requirement C.10.5.1:** For any **multi-step, filterable process** (e.g., "Order Checkout," "Data Import Validation"), the **Pipeline Pattern** MUST be used.

**Rationale:** This is Laravel's implementation of the **Chain of Responsibility Pattern**. It allows modules to "tap into" a core process and add or modify steps without modifying the core code, which is critical for our modularity goal.

**Example:**
```php
// Order checkout pipeline
class ProcessCheckoutPipeline
{
    public function handle(Order $order): Order
    {
        return Pipeline::send($order)
            ->through([
                ValidateStockAvailability::class,
                ApplyDiscounts::class,
                CalculateTaxes::class,
                ReserveStock::class,
                ProcessPayment::class,
                GenerateInvoice::class,
            ])
            ->then(fn($order) => $order);
    }
}

// Modules can add steps without modifying core
class CustomModule extends ServiceProvider
{
    public function boot()
    {
        Pipeline::add(ProcessCheckoutPipeline::class, ApplyLoyaltyPoints::class);
    }
}
```

#### C.10.6. Factory Pattern

**Pattern Definition:** Encapsulate object creation logic, especially for conditional instantiation.

**Requirement C.10.6.1:** When creating a **complex object** or a **class based on runtime condition** (e.g., selecting a payment provider from a config key), a **Factory Class** (e.g., `PaymentProviderFactory`) MUST be used.

**Rationale:** This encapsulates complex object creation and respects the **Open/Closed Principle**.

**Example:**
```php
class PaymentGatewayFactory
{
    public function make(string $provider): PaymentGatewayContract
    {
        return match($provider) {
            'stripe' => app(StripeGateway::class),
            'paypal' => app(PayPalGateway::class),
            'local' => app(LocalBankTransfer::class),
            default => throw new InvalidArgumentException("Unknown provider: $provider")
        };
    }
}

// Usage
$gateway = $this->factory->make(config('payment.default_provider'));
$gateway->charge($amount);
```

### C.11. Patterns Not Globally Mandated

This section clarifies why certain complex patterns are **not mandated system-wide**, to prevent over-engineering.

#### C.11.1. CQRS (Command Query Responsibility Segregation)

**Stance:** CQRS is **NOT a system-wide mandate**.

**Rationale:** The complexity of maintaining separate read/write models is overkill for standard CRUD modules (like "Settings" or "User Management").

**Exception:** A module MAY use CQRS if it has:
- High-performance requirements
- High-contention scenarios
- Vastly different read/write needs

**Examples of Valid CQRS Use:**
- ✅ "Real-time Inventory" module with heavy read queries
- ✅ "Analytics" module with separate reporting database
- ❌ Standard CRUD modules (Settings, User profiles)

#### C.11.2. Decorator & Composite Patterns

**Stance:** These are **developer-discretion patterns**.

**Rationale:** These are excellent, classic GoF (Gang of Four) patterns for solving specific problems:
- **Composite:** Hierarchical structures (e.g., Bill of Materials, Organization charts)
- **Decorator:** Adding features to objects at runtime (e.g., Invoice decorators for taxes, discounts)

These are not foundational architectural principles but rather **tools** to be used by developers when appropriate. They do not need to be mandated in the PRD.

**When to Use:**
- Composite: Tree structures, nested hierarchies
- Decorator: Dynamic feature addition without subclassing

---

## Section D: Modular Design and Feature Toggling

This section defines the modular architecture that enables the system to cater to "every industry big and small."

**Important:** All modules in this system are **Feature Modules** - self-encapsulating units that will be released as separate Composer packages on Packagist when version 1.0 is released. Each Feature Module adheres to the monorepo concept outlined in Section C.1.

### D.1. Mandatory Feature Modules (The Essential Backbone)

**Mandatory Feature Modules** are always included in every installation and provide the foundational infrastructure for the ERP system. These modules cannot be disabled or removed as they form the operational backbone.

#### D.1.1. Mandatory Feature Modules List

| Feature Module | Sub-PRD Reference | Purpose | Always Active |
|-------------|-------------------|---------|---------------|
| **Multi-Tenancy System** | PRD01-SUB01 | Tenant isolation, context management, impersonation | ✅ Yes |
| **Authentication & Authorization** | PRD01-SUB02 | User management, RBAC, API token authentication | ✅ Yes |
| **Audit Logging** | PRD01-SUB03 | Activity tracking, compliance, security monitoring | ✅ Yes |
| **Settings Management** | PRD01-SUB05 | System/tenant/module configuration storage | ✅ Yes |
| **API Gateway** | PRD01-SUB23 | Unified API entry point, rate limiting, documentation | ✅ Yes |
| **Notifications & Events** | PRD01-SUB22 | Event bus, notification delivery, webhook management | ✅ Yes |

**Mandatory Feature Module Characteristics:**
- **Non-removable:** Cannot be uninstalled or disabled
- **Zero Dependencies:** Do not depend on optional feature modules
- **Foundation Layer:** Provide services that optional feature modules depend on
- **Always Tested:** 100% test coverage required
- **Performance Critical:** Must meet sub-200ms response time targets
- **Composer Packages:** Each released as `azaharizaman/erp-{module}` on Packagist

### D.2. Optional Feature Modules (The Plug-ins)

**Optional Feature Modules** are industry-specific or business-function-specific modules that can be installed, enabled, or disabled independently based on organizational needs.

#### D.2.1. Available Optional Feature Modules

**Requirement:** Each feature module must be completely independent and installable/removable without affecting mandatory feature modules or other optional feature modules.

| Feature Module | Sub-PRD References | Industry Focus | Key Capabilities | Installation Status |
|----------------|-------------------|----------------|------------------|---------------------|
| **Core Infrastructure** | SUB01, SUB02, SUB03, SUB05, SUB22, SUB23 | All industries (mandatory foundation) | Multi-tenancy isolation, authentication & authorization (Sanctum + RBAC), comprehensive audit logging, hierarchical settings management, event-driven notifications, and unified API gateway with documentation | Mandatory (Always Active) |
| **Finance & Accounting** | SUB07, SUB08, SUB09, SUB10, SUB11, SUB12, SUB19, SUB20 | Finance-focused organizations, enterprises requiring full accounting capabilities | Complete financial management including Chart of Accounts, General Ledger, Journal Entries, Banking reconciliation, AP/AR processing, taxation, and financial reporting | Optional |
| **Inventory & Warehouse Management** | SUB06, SUB14 | Manufacturing, Retail, Distribution, Logistics | Multi-warehouse inventory tracking with UOM management, stock movements, costing methods, and real-time stock level monitoring | Optional |
| **Supply Chain Management** | SUB16 | Procurement-focused organizations, manufacturers with supplier networks | Purchase order management with multi-level approvals, supplier evaluation, inventory receipt integration, and AP invoice matching | Optional |
| **Sales & Distribution** | SUB17 | Sales-focused organizations, e-commerce, wholesale/retail | Sales order processing with approval workflows, fulfillment tracking, inventory reservation, credit limit checks, and automated invoicing | Optional |
| **Human Resources & Payroll** | SUB13 | Organizations with employees, HR departments | Human capital management covering employee master data, organizational hierarchy, job information, and payroll integration | Optional |
| **Backoffice & Administration** | SUB15 | All industries | Core backoffice operations including company setup, fiscal year management, branch/department structure, currency configuration, and document templates | Optional |
| **Business Intelligence & Analytics** | SUB18, SUB20 | Large enterprises, data-driven organizations | Master data management with real-time reporting APIs, analytics offload, materialized views, ETL pipelines, and compliance reporting (SOX, ISO) | Optional |
| **Workflow & Automation** | SUB21 | Process-driven organizations, enterprises with complex approval chains | Configurable workflow engine with approval chains, escalation rules, parallel/sequential routing, and event-driven execution | Optional |
| **Document & Serial Number Management** | SUB04 | All industries | Automated document numbering system with configurable patterns, tenant-specific sequences, and collision-free ID generation | Optional |
| **Localization & Multi-Currency** | SUB25 | Multi-region organizations, international businesses | Internationalization support with multi-language interfaces, multi-currency transactions, exchange rate management, and regional formatting | Optional |
| **Integration & Connectivity** | SUB24 | Organizations with external systems, legacy systems | Pre-built integration connectors for third-party systems with event-driven synchronization and dynamic connector registration | Optional |

#### D.2.2. Feature Module Independence Requirements

To ensure true modularity and prevent coupling, each feature module MUST adhere to:

1. **Self-Contained Business Logic:**
   - All domain logic contained within feature module boundaries
   - No direct method calls to other optional feature modules
   - Database tables use module-specific prefixes (e.g., `inv_`, `sales_`, `hr_`)

2. **Event-Driven Communication:**
   - Cross-module communication ONLY via domain events
   - Example: `StockAdjustedEvent` emitted by Inventory, consumed by GL
   - No shared mutable state between feature modules

3. **Dependency Declaration:**
   - Explicit declaration of required feature modules in `composer.json`
   - System prevents enabling a feature module if dependencies are not active
   - Clean error messages for missing dependencies

4. **Database Isolation:**
   - Each feature module manages its own migrations
   - Foreign keys only to mandatory feature modules, never to other optional feature modules
   - Use soft references (IDs without FK constraints) for cross-module relationships

5. **API Namespace Isolation:**
   - Each feature module's APIs under `/api/v1/{module}/` namespace
   - Example: `/api/v1/inventory/items`, `/api/v1/sales/orders`
   - Feature module-specific API documentation

6. **Composer Package Structure:**
   - Each feature module is a separate Composer package: `azaharizaman/erp-{module}`
   - Lives in monorepo `packages/` directory during development
   - Published to Packagist as independent package for v1.0 release
   - Follows structure defined in Section C.1 (Monorepo strategy)
   - Cross-module communication ONLY via domain events
   - Example: `StockAdjustedEvent` emitted by Inventory, consumed by GL
   - No shared mutable state between modules

3. **Dependency Declaration:**
   - Explicit declaration of required modules in `module.json`
   - System prevents enabling a module if dependencies are not active
   - Clean error messages for missing dependencies

4. **Database Isolation:**
   - Each module manages its own migrations
   - Foreign keys only to Core tables, never to other feature modules
   - Use soft references (IDs without FK constraints) for cross-module relationships

5. **API Namespace Isolation:**
   - Each module's APIs under `/api/v1/{module}/` namespace
   - Example: `/api/v1/inventory/items`, `/api/v1/sales/orders`
   - Module-specific API documentation

### D.3. Feature Module Toggling Mechanism

The system provides flexible feature module toggling at multiple levels to accommodate different organizational sizes and industry requirements.

**Note:** Only **optional feature modules** can be toggled. Mandatory feature modules (Section D.1) are always active.

#### D.3.1. Toggling Levels

| Level | Scope | Use Case | Example |
|-------|-------|----------|---------|
| **System-Wide** | All tenants | Enable/disable feature modules globally | Disable Inventory feature module for SaaS offering |
| **Tenant-Level** | Single tenant | Enable/disable for specific tenant | Enable HR feature module only for Enterprise tier tenants |
| **User-Level** | Individual users | Feature flags for specific capabilities | Beta feature access for selected users |
| **Environment** | Dev/Staging/Production | Test features before production | Enable experimental AI features in staging |

#### D.3.2. Toggle Implementation Strategy

**Technical Implementation:**

```php
// Example: Check if feature module is enabled for current tenant
if (FeatureModule::isEnabled('inventory', tenant())) {
    // Inventory-specific logic
}

// Example: Feature flag for specific capability within a module
if (Feature::active('ai-powered-categorization')) {
    // AI categorization logic
}
```

**Toggle Storage:**
- **System-wide toggles:** `system_settings` table
- **Tenant toggles:** `tenant_feature_modules` table
- **User toggles:** `user_feature_flags` table
- **Cache layer:** Redis for sub-10ms toggle checks

**Toggle Management:**

1. **Admin API Endpoints:**
   - `POST /api/v1/admin/feature-modules/{module}/enable` - Enable feature module for tenant
   - `POST /api/v1/admin/feature-modules/{module}/disable` - Disable feature module for tenant
   - `GET /api/v1/admin/feature-modules` - List all feature modules and their status

2. **Tenant Self-Service:**
   - Tenants can enable/disable feature modules within their subscription tier
   - Upgrade prompts for premium feature modules
   - Usage tracking per feature module for billing

3. **Graceful Degradation:**
   - API endpoints return `404 Not Found` for disabled feature modules
   - Clear error messages: `{"error": "Feature module 'inventory' is not enabled for this tenant"}`
   - Frontend can query `/api/v1/feature-modules/enabled` to show/hide features

#### D.3.3. Feature Module Lifecycle Management

**Installation Process:**
1. Check dependencies are met (required feature modules are active)
2. Run feature module-specific migrations
3. Seed default data if needed
4. Register API routes and event listeners
5. Update OpenAPI documentation
6. Emit `FeatureModuleEnabledEvent`

**Uninstallation Process:**
1. Check no dependent feature modules are active
2. Archive feature module data (optional, configurable)
3. Remove API routes
4. Clean up event listeners
5. Emit `FeatureModuleDisabledEvent`

**Upgrade Process:**
- Feature module versions tracked independently
- Rolling updates without downtime
- Database migrations per feature module version
- Backwards compatibility maintained within major version

---

## Section E: AI and Extensibility Features

This section focuses on the system's unique AI-native capabilities and extensibility architecture that differentiates Laravel ERP from traditional solutions.

### E.1. AI-Ready Requirements

The system is designed with "AI Hooks" throughout the architecture, allowing AI capabilities to be easily integrated, extended, or replaced without modifying core business logic.

#### E.1.1. The Role of `azaharizaman/huggingface-php`

**Package Purpose:**
The `azaharizaman/huggingface-php` package serves as the primary bridge between the Laravel ERP backend and Hugging Face's extensive AI/ML model ecosystem.

**Core Capabilities:**
- **Model Inference:** Execute predictions using pre-trained models
- **Text Classification:** Categorize documents, transactions, customer queries
- **Named Entity Recognition (NER):** Extract entities from unstructured text (invoices, emails)
- **Sentiment Analysis:** Analyze customer feedback, support tickets
- **Text Generation:** Generate descriptions, summaries, reports
- **Translation:** Multi-language support for localization

**Integration Points:**

| Use Case | Module | AI Model Type | Business Value |
|----------|--------|---------------|----------------|
| **Automated GL Account Suggestion** | General Ledger | Text Classification | Suggests GL accounts based on transaction description |
| **Invoice Data Extraction** | Accounts Payable | Named Entity Recognition | Extracts vendor, amounts, dates from scanned invoices |
| **Customer Inquiry Routing** | CRM/Support | Text Classification | Routes support tickets to appropriate departments |
| **Inventory Demand Forecasting** | Inventory | Time Series Prediction | Predicts stock requirements based on historical data |
| **Smart Document Categorization** | Document Management | Multi-label Classification | Auto-tags and categorizes uploaded documents |
| **Anomaly Detection** | Audit Logging | Outlier Detection | Flags unusual transactions or user behavior |

#### E.1.2. AI Hooks Architecture

**Requirement:** Define clear AI Hooks within the core framework to allow AI features to be easily swapped or extended.

**Hook Pattern Implementation:**

```php
// Example: AI Hook for transaction categorization
interface TransactionCategorizerContract
{
    public function categorize(Transaction $transaction): Category;
    public function suggest(string $description): array;
}

// Default implementation (rule-based)
class RuleBasedCategorizer implements TransactionCategorizerContract
{
    public function categorize(Transaction $transaction): Category
    {
        // Traditional rule-based logic
    }
}

// AI-powered implementation
class HuggingFaceCategorizerAdapter implements TransactionCategorizerContract
{
    public function categorize(Transaction $transaction): Category
    {
        // Use azaharizaman/huggingface-php for inference
        $result = $this->huggingFace->classify($transaction->description);
        return Category::find($result['category_id']);
    }
}
```

**AI Hook Registration:**
- Contracts defined in `app/Support/Contracts/AI/`
- Default implementations in `app/Support/Services/AI/Default/`
- AI implementations in `app/Support/Services/AI/HuggingFace/`
- Service provider bindings allow runtime swapping

#### E.1.3. AI Feature Toggles

AI features are independently toggleable to support:
- **Gradual Rollout:** Test AI features with subset of tenants
- **Cost Management:** AI inference can be expensive; enable per subscription tier
- **Fallback Strategy:** Gracefully degrade to rule-based logic if AI service unavailable
- **Performance Monitoring:** Compare AI vs. rule-based performance

**AI Toggle Configuration:**
```php
// config/ai.php
return [
    'enabled' => env('AI_ENABLED', false),
    'provider' => env('AI_PROVIDER', 'huggingface'), // huggingface, openai, local
    'fallback' => 'rules', // Fallback to rule-based if AI fails
    'features' => [
        'transaction_categorization' => env('AI_CATEGORIZATION', false),
        'invoice_extraction' => env('AI_INVOICE_EXTRACTION', false),
        'demand_forecasting' => env('AI_DEMAND_FORECAST', false),
    ],
];
```

#### E.1.4. AI Training Data Pipeline

**Data Collection:**
- User corrections to AI suggestions are logged
- Feedback loop improves model accuracy over time
- Privacy-preserving: No PII sent to external AI services

**Model Fine-Tuning:**
- Export anonymized training data via admin API
- Fine-tune models on tenant-specific or industry-specific data
- Deploy custom models per tenant (enterprise tier feature)

### E.2. API Design and Documentation

The API is the primary interface for all system interactions and must be comprehensive, versioned, and impeccably documented.

#### E.2.1. OpenAPI (Swagger) Specification

**Requirement:** OpenAPI (Swagger) specification for every API endpoint.

**Implementation Strategy:**

1. **Auto-Generation from Code:**
   - Use `darkaonline/l5-swagger` package
   - PHPDoc annotations on controllers automatically generate OpenAPI specs
   - Example:
   ```php
   /**
    * @OA\Get(
    *     path="/api/v1/inventory/items",
    *     summary="List inventory items",
    *     tags={"Inventory"},
    *     @OA\Parameter(name="page", in="query", required=false),
    *     @OA\Response(response=200, description="Successful operation")
    * )
    */
   public function index(Request $request): JsonResponse
   ```

2. **Specification Storage:**
   - Generated spec stored at `/storage/api-docs/api-docs.json`
   - Accessible via `/api/documentation` endpoint
   - Interactive Swagger UI at `/api/docs` (dev/staging only)

3. **Documentation Requirements:**
   - All endpoints must have:
     - Summary and description
     - Request parameters (query, path, body)
     - Response schemas with examples
     - Authentication requirements
     - Error response codes

4. **Validation:**
   - CI/CD pipeline validates OpenAPI spec on every PR
   - Breaks build if endpoints lack documentation
   - Spectral linting rules enforce API design consistency

#### E.2.2. API Versioning Strategy

**Requirement:** Define API versioning strategy (e.g., `/api/v1/`).

**Versioning Approach: URL Path Versioning**

**Structure:**
```
/api/v1/{module}/{resource}
/api/v2/{module}/{resource}
```

**Examples:**
- `/api/v1/inventory/items`
- `/api/v1/sales/orders`
- `/api/v2/inventory/items` (future breaking changes)

**Versioning Rules:**

1. **Backwards Compatibility:**
   - Within same major version (v1), all changes must be backwards compatible
   - Adding new fields: ✅ Allowed
   - Removing fields: ❌ Breaking change (requires new major version)
   - Changing field types: ❌ Breaking change
   - Adding new endpoints: ✅ Allowed

2. **Deprecation Process:**
   - Deprecated endpoints remain functional for minimum 12 months
   - `Deprecated` header returned: `Deprecated: true; sunset="2026-12-31"`
   - Documentation clearly marks deprecated endpoints
   - Migration guide provided for each breaking change

3. **Version Support:**
   - **Current version (v1):** Fully supported, active development
   - **Previous version (v0):** Security fixes only, 12-month sunset period
   - **Older versions:** Unsupported, removed from production

4. **Header-Based Version Override (Optional):**
   ```
   Accept: application/vnd.laravel-erp.v2+json
   ```
   - Allows clients to specify version in header
   - URL version takes precedence

**Version Negotiation:**
- Default to latest stable version if no version specified
- Return `400 Bad Request` for unsupported versions
- Clear error message with supported versions list

#### E.2.3. API Response Standards

**Standardized Response Structure:**

```json
{
  "success": true,
  "data": { /* resource data */ },
  "meta": {
    "version": "v1",
    "timestamp": "2025-11-11T12:00:00Z",
    "request_id": "req_abc123"
  },
  "pagination": {
    "current_page": 1,
    "total_pages": 10,
    "per_page": 15,
    "total_items": 150
  }
}
```

**Error Response Structure:**

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "name": ["The name field is required."]
    }
  },
  "meta": {
    "version": "v1",
    "timestamp": "2025-11-11T12:00:00Z",
    "request_id": "req_xyz789"
  }
}
```

**HTTP Status Codes:**
- `200 OK` - Successful GET, PUT, PATCH
- `201 Created` - Successful POST
- `204 No Content` - Successful DELETE
- `400 Bad Request` - Validation error
- `401 Unauthorized` - Authentication required
- `403 Forbidden` - Authorization failed
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Business logic error
- `429 Too Many Requests` - Rate limit exceeded
- `500 Internal Server Error` - Server error

### E.3. Webhook and Eventing

**Detail the strategy for emitting business events that external systems can subscribe to using Reverb/Echo.**

#### E.3.1. Event Architecture

**Three-Tier Event System:**

1. **Internal Domain Events** (PHP Classes)
   - Fired within Laravel application
   - Example: `TenantCreatedEvent`, `InvoicePostedEvent`
   - Handled by Laravel event listeners

2. **WebSocket Events** (Real-Time Push)
   - Broadcast via Laravel Reverb to connected clients
   - Example: Dashboard notifications, live updates
   - Consumed by frontend applications using Laravel Echo

3. **Webhook Events** (HTTP Callbacks)
   - HTTP POST to external URLs when events occur
   - Example: Notify external accounting system when invoice created
   - Reliable delivery with retry mechanism

#### E.3.2. Webhook Implementation

**Webhook Registration:**

```json
POST /api/v1/webhooks
{
  "url": "https://external-system.com/webhooks/erp-events",
  "events": ["invoice.created", "payment.received", "order.shipped"],
  "secret": "webhook_secret_key_for_signature",
  "active": true
}
```

**Webhook Delivery:**

1. **Event Payload Structure:**
```json
{
  "event": "invoice.created",
  "timestamp": "2025-11-11T12:00:00Z",
  "tenant_id": "tenant_abc",
  "data": {
    "invoice_id": 12345,
    "customer": "Acme Corp",
    "amount": 1500.00,
    "currency": "USD"
  },
  "signature": "sha256=abc123..." // HMAC signature for verification
}
```

2. **Delivery Guarantees:**
   - Retry logic: 3 attempts with exponential backoff (1s, 5s, 25s)
   - Dead letter queue for failed webhooks after max retries
   - Webhook delivery logs stored for 30 days
   - Admin dashboard shows delivery success/failure rates

3. **Security:**
   - HMAC-SHA256 signature in `X-Webhook-Signature` header
   - External systems verify signature using shared secret
   - HTTPS required for all webhook URLs
   - IP whitelist support (optional)

#### E.3.3. Real-Time Event Broadcasting (Reverb/Echo)

**Broadcast Channels:**

1. **Private Tenant Channels:**
   ```
   tenant.{tenant_id}
   ```
   - Broadcasts tenant-specific events
   - Requires authentication
   - Example: Notifications, status updates

2. **Private User Channels:**
   ```
   user.{user_id}
   ```
   - User-specific notifications
   - Personal dashboard updates

3. **Presence Channels:**
   ```
   presence.document.{document_id}
   ```
   - Shows who's currently viewing/editing a document
   - Collaborative features

**Frontend Consumption:**

```javascript
// Using Laravel Echo
Echo.private(`tenant.${tenantId}`)
    .listen('InvoiceCreated', (event) => {
        console.log('New invoice created:', event.invoice);
        // Update UI
    });
```

#### E.3.4. Available Business Events

**Core Events:**
- `tenant.created`, `tenant.updated`, `tenant.suspended`
- `user.created`, `user.login`, `user.logout`
- `role.assigned`, `permission.granted`

**Financial Events:**
- `invoice.created`, `invoice.posted`, `invoice.paid`, `invoice.voided`
- `payment.received`, `payment.applied`, `payment.refunded`
- `journal.posted`, `journal.reversed`

**Inventory Events:**
- `stock.adjusted`, `stock.transferred`, `stock.received`
- `item.created`, `item.updated`, `item.deleted`

**Sales Events:**
- `order.created`, `order.confirmed`, `order.shipped`, `order.delivered`
- `quotation.sent`, `quotation.accepted`, `quotation.expired`

**Purchasing Events:**
- `po.created`, `po.approved`, `po.received`, `po.closed`
- `grn.created`, `grn.posted`

**Event Catalog:**
- Complete event catalog documented at `/docs/events.md`
- Each event includes: name, payload schema, frequency, reliability requirements

---
## Section F: TECHNICAL ARCHITECTURE DECISIONS

### F.1 Hybrid Database Strategy (SQL + NoSQL)

**Core Principle:** Use RDBMS (MySQL/PostgreSQL) for transactional integrity, NoSQL for high-volume auxiliary data.

**✅ SQL Database (Primary - Laravel Native)**
- **Modules:** SUB04-SUB17 (Serial Numbering, UOM, Accounting, COA, GL, JE, Banking, AP, AR, HCM, Inventory, Backoffice, Purchasing, Sales)
- **Rationale:** 
  - ACID compliance required for financial transactions (BR-GL-001: debit = credit)
  - Relational integrity for Invoice → Line Items → Inventory/GL Account
  - Complex tenant isolation enforcement (SR-MT-001)
  - Foreign key relationships and SQL joins for complex queries

**✅ NoSQL/Cache Layer (Specialized)**

| Module | Technology | Purpose | Performance Target |
|--------|-----------|---------|-------------------|
| **SUB03: Audit Logging** | MongoDB or PostgreSQL JSONB | High-volume, append-only, context-rich logs (FR-AL-001, FR-AL-004) | <10% overhead (PR-AL-001) |
| **SUB05: Settings Management** | Redis/Memcached (Laravel Cache) | Tenant config caching for sub-100ms access (PR-SM-001) | <100ms retrieval |
| **SUB22: Notifications/Events** | Redis (Laravel Queue/Horizon) | Asynchronous message queue (IR-NW-001, PR-NW-001) | <3s delivery |
| **SUB18/SUB20: Reporting/Analytics** | PostgreSQL JSONB/Materialized Views or ClickHouse | Aggregated analytics offloaded from transactional DB (PR-REP-001) | <3s for <10k rows |

**Key Benefits:**
1. **Data Integrity:** Financial data maintains ACID properties
2. **Scalability:** High-volume logs and events don't impact transactional performance
3. **Flexibility:** Document stores handle dynamic audit context without schema changes
4. **Performance:** Caching and async processing protect API response times

### F.2 **Core Component: PRD01-MVP Module Decomposition and Requirements Summary**

This section outlines the decomposition of the **PRD01-MVP** into core Sub-PRD modules, their corresponding implementation plans, detailed requirements categorized by type (Functional, System, Performance, etc.), and the associated release milestones.

#### F.2.1 **PRD to Sub-PRD Mapping**

The MVP is structured around 25 distinct modules, each detailed in its own Sub-PRD file.

| ID | Module Description | Sub-PRD File |
| :---- | :---- | :---- |
| **SUB01** | Tenant isolation infrastructure enabling secure data segregation across organizations with configurable settings and middleware-based context resolution | PRD01-SUB01-MULTITENANCY.md |
| **SUB02** | Stateless API authentication using Laravel Sanctum with personal access tokens, role-based permissions, and security controls | PRD01-SUB02-AUTHENTICATION.md |
| **SUB03** | Comprehensive activity logging system tracking all critical operations with event-based recording and searchable audit trails | PRD01-SUB03-AUDIT-LOGGING.md |
| **SUB04** | Automated document numbering system with configurable patterns, tenant-specific sequences, and collision-free ID generation | PRD01-SUB04-SERIAL-NUMBERING.md |
| **SUB05** | Hierarchical configuration management supporting system-level, tenant-level, and module-level settings with encryption and caching | PRD01-SUB05-SETTINGS-MANAGEMENT.md |
| **SUB06** | Unit of Measure management with precision conversion factors, automatic unit conversion logic, and rounding accuracy controls | PRD01-SUB06-UOM.md |
| **SUB07** | Hierarchical chart of accounts structure with account types, categories, and reporting groups for financial classification | PRD01-SUB07-CHART-OF-ACCOUNTS.md |
| **SUB08** | Core general ledger system with automatic posting from submodules, multi-currency support, and balanced entry enforcement | PRD01-SUB08-GENERAL-LEDGER.md |
| **SUB09** | Manual and automated journal entry management with recurring journals, reversing entries, templates, and approval workflows | PRD01-SUB09-JOURNAL-ENTRIES.md |
| **SUB10** | Bank account management with automated statement reconciliation, transaction matching, and secure credential handling | PRD01-SUB10-BANKING.md |
| **SUB11** | Accounts payable processing with automatic AP entry generation from purchase orders, payment processing, and vendor management | PRD01-SUB11-ACCOUNTS-PAYABLE.md |
| **SUB12** | Accounts receivable management with automatic AR entry generation from sales orders, receipt processing, and customer credit control | PRD01-SUB12-ACCOUNTS-RECEIVABLE.md |
| **SUB13** | Human capital management covering employee master data, job information, organizational hierarchy, and payroll integration | PRD01-SUB13-HCM.md |
| **SUB14** | Multi-warehouse inventory tracking with stock movements, costing methods, negative stock controls, and comprehensive stock ledgers | PRD01-SUB14-INVENTORY-MANAGEMENT.md |
| **SUB15** | Core backoffice operations including company setup, fiscal year management, currency configuration, and document templates | PRD01-SUB15-BACKOFFICE.md |
| **SUB16** | Purchase order management with multi-level approvals, supplier evaluation, inventory receipt integration, and AP invoice matching | PRD01-SUB16-PURCHASING.md |
| **SUB17** | Sales order processing with approval workflows, fulfillment tracking, inventory reservation, credit limit checks, and invoicing | PRD01-SUB17-SALES.md |
| **SUB18** | Master data management providing real-time reporting APIs, analytics offload, and materialized views for performance optimization | PRD01-SUB18-MASTER-DATA-MANAGEMENT.md |
| **SUB19** | Tax calculation engine supporting multiple tax jurisdictions, configurable tax rules, automatic tax computation, and compliance reporting | PRD01-SUB19-TAXATION.md |
| **SUB20** | Financial reporting infrastructure with data aggregation, ETL pipelines, compliance policies (SOX, ISO), and data warehouse integration | PRD01-SUB20-FINANCIAL-REPORTING.md |
| **SUB21** | Configurable workflow engine with approval chains, escalation rules, parallel/sequential routing, and event-driven execution | PRD01-SUB21-WORKFLOW-ENGINE.md |
| **SUB22** | Event-driven notification system delivering alerts via email, webhooks, and system feeds with asynchronous queue processing | PRD01-SUB22-NOTIFICATIONS-EVENTS.md |
| **SUB23** | Unified API gateway for external integrations with authentication, rate limiting, versioning, and auto-generated API documentation | PRD01-SUB23-API-GATEWAY-AND-DOCUMENTATION.md |
| **SUB24** | Pre-built integration connectors for third-party systems with event-driven synchronization and dynamic connector registration | PRD01-SUB24-INTEGRATION-CONNECTORS.md |
| **SUB25** | Internationalization support with multi-language interfaces, multi-currency transactions, exchange rate management, and regional formatting | PRD01-SUB25-LOCALIZATION.md |

#### F.2.2 **Sub-PRD to Implementation Plan Mapping**

Each Sub-PRD is associated with a single, dedicated implementation plan.

| Sub-PRD File | Implementation Plan File |
| :---- | :---- |
| PRD01-SUB01-MULTITENANCY.md | PRD01-SUB01-PLAN01-IMPLEMENT-MULTITENANCY.md |
| PRD01-SUB02-AUTHENTICATION.md | PRD01-SUB02-PLAN01-IMPLEMENT-AUTHENTICATION.md |
| PRD01-SUB03-AUDIT-LOGGING.md | PRD01-SUB03-PLAN01-IMPLEMENT-AUDIT-LOGGING.md |
| PRD01-SUB04-SERIAL-NUMBERING.md | PRD01-SUB04-PLAN01-IMPLEMENT-SERIAL-NUMBERING.md |
| PRD01-SUB05-SETTINGS-MANAGEMENT.md | PRD01-SUB05-PLAN01-IMPLEMENT-SETTINGS-MANAGEMENT.md |
| PRD01-SUB06-UOM.md | PRD01-SUB06-PLAN01-IMPLEMENT-UOM.md |
| PRD01-SUB07-CHART-OF-ACCOUNTS.md | PRD01-SUB07-PLAN01-IMPLEMENT-CHART-OF-ACCOUNTS.md |
| PRD01-SUB08-GENERAL-LEDGER.md | PRD01-SUB08-PLAN01-IMPLEMENT-GENERAL-LEDGER.md |
| PRD01-SUB09-JOURNAL-ENTRIES.md | PRD01-SUB09-PLAN01-IMPLEMENT-JOURNAL-ENTRIES.md |
| PRD01-SUB10-BANKING.md | PRD01-SUB10-PLAN01-IMPLEMENT-BANKING.md |
| PRD01-SUB11-ACCOUNTS-PAYABLE.md | PRD01-SUB11-PLAN01-IMPLEMENT-ACCOUNTS-PAYABLE.md |
| PRD01-SUB12-ACCOUNTS-RECEIVABLE.md | PRD01-SUB12-PLAN01-IMPLEMENT-ACCOUNTS-RECEIVABLE.md |
| PRD01-SUB13-HCM.md | PRD01-SUB13-PLAN01-IMPLEMENT-HCM.md |
| PRD01-SUB14-INVENTORY-MANAGEMENT.md | PRD01-SUB14-PLAN01-IMPLEMENT-INVENTORY-MANAGEMENT.md |
| PRD01-SUB15-BACKOFFICE.md | PRD01-SUB15-PLAN01-IMPLEMENT-BACKOFFICE.md |
| PRD01-SUB16-PURCHASING.md | PRD01-SUB16-PLAN01-IMPLEMENT-PURCHASING.md |
| PRD01-SUB17-SALES.md | PRD01-SUB17-PLAN01-IMPLEMENT-SALES.md |
| PRD01-SUB18-MASTER-DATA-MANAGEMENT.md | PRD01-SUB18-PLAN01-IMPLEMENT-MDM.md |
| PRD01-SUB19-TAXATION.md | PRD01-SUB19-PLAN01-IMPLEMENT-TAXATION.md |
| PRD01-SUB20-FINANCIAL-REPORTING.md | PRD01-SUB20-PLAN01-IMPLEMENT-FINANCIAL-REPORTING.md |
| PRD01-SUB21-WORKFLOW-ENGINE.md | PRD01-SUB21-PLAN01-IMPLEMENT-WORKFLOW-ENGINE.md |
| PRD01-SUB22-NOTIFICATIONS-EVENTS.md | PRD01-SUB22-PLAN01-IMPLEMENT-NOTIFICATIONS-EVENTS.md |
| PRD01-SUB23-API-GATEWAY-AND-DOCUMENTATION.md | PRD01-SUB23-PLAN01-IMPLEMENT-API-GATEWAY.md |
| PRD01-SUB24-INTEGRATION-CONNECTORS.md | PRD01-SUB24-PLAN01-IMPLEMENT-INTEGRATION-CONNECTORS.md |
| PRD01-SUB25-LOCALIZATION.md | PRD01-SUB25-PLAN01-IMPLEMENT-LOCALIZATION.md |

#### F.2.3 **Sub-PRD to Detailed Requirements Mapping (Table Format)**

This section details the specific requirements for each module, now organized in a traceable table structure.

##### F.2.3.1 **Requirement Category Definitions**

To ensure comprehensive coverage and traceability, all requirements are classified into the following standardized categories:

| Category | Code Prefix | Definition | Purpose | Examples |
|----------|-------------|------------|---------|----------|
| **Functional Requirements** | FR-* | Define **what** the system must do - specific behaviors, features, and capabilities | Captures user-facing features and business operations | User login, generate invoice, export report |
| **Business Rules** | BR-* | Define **constraints and policies** that govern business operations | Enforces business logic and operational policies | Credit limit checks, approval thresholds, one active fiscal year |
| **Data Requirements** | DR-* | Define **data structure, storage, retention, and quality** needs | Ensures data integrity and availability | Audit trail storage, aggregated balances, stock ledger |
| **Integration Requirements** | IR-* | Define **how modules interact** with each other or external systems | Ensures seamless data flow between components | AP-Banking integration, Inventory-Sales sync, webhook delivery |
| **Performance Requirements** | PR-* | Define **speed, throughput, and resource** constraints | Guarantees acceptable system responsiveness | API response < 200ms, batch processing speed, concurrent user support |
| **Security Requirements** | SR-* | Define **protection mechanisms** for data and system access | Ensures confidentiality, integrity, and availability | Encryption, authentication, tenant isolation, access control |
| **Scalability Requirements** | SCR-* | Define **growth capacity** for users, data, and load | Ensures system can handle increasing demands | Horizontal scaling, multi-region support, 1000+ concurrent tenants |
| **Compliance Requirements** | CR-* | Define **regulatory and legal** obligations | Ensures adherence to laws and standards | GDPR, SOX, ISO 27001, audit retention periods |
| **Architecture Requirements** | ARCH-* | Define **technical implementation** constraints and patterns | Guides technology choices and system design | Database choice (PostgreSQL), ACID compliance, microservices pattern |
| **Event Requirements** | EV-* | Define **domain events** that must be emitted for system integration | Enables event-driven architecture and loose coupling | SettingUpdatedEvent, InvoicePostedEvent, StockAdjustedEvent |

**Usage Guidelines:**

- **Every requirement MUST** have a unique code (e.g., `FR-MT-001`, `BR-AP-002`)
- **Module-specific codes** follow pattern: `{Category}-{ModuleCode}-{Number}` (e.g., `FR-INV-003` for Inventory functional requirement #3)
- **Cross-cutting codes** use general prefixes: `{Category}-{Number}` (e.g., `SR-001` for system-wide security requirement)
- **Related requirements** should reference each other (e.g., "See FR-GL-001 for automatic posting")
- **Traceability** maintained through Sub-PRD and Implementation Plan references

### **PRD01-SUB01: MULTITENANCY**

SQL Database \- Core

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| **FR-MT-001** | Implement a **Tenant Model** with UUID primary keys to represent isolated organizational entities | FR | Planned | TBD | N/A |
| **FR-MT-002** | Ensure strict **Tenant Data Isolation** by scoping all database queries and cache operations per tenant | FR | Planned | TBD | N/A |
| **FR-MT-003** | Establish a **Tenant Context middleware** to resolve and inject active tenant context into requests | FR | Planned | TBD | N/A |
| **FR-MT-004** | Provide **Tenant CRUD APIs** (create, read, update, archive) with full validation | FR | Planned | TBD | N/A |
| **FR-MT-005** | Support **Tenant status management** (active, suspended, archived) with status transitions | FR | Planned | TBD | N/A |
| **FR-MT-006** | Allow **Tenant Impersonation** for administrative support under strict auditing control | FR | Planned | TBD | N/A |
| **FR-MT-007** | Implement **Tenant-aware global scopes** on all Eloquent models for automatic data isolation | FR | Planned | TBD | N/A |
| **BR-MT-001** | A tenant cannot be deleted if it has **active users or transactional data** | BR | Planned | TBD | N/A |
| **BR-MT-002** | Tenant domain names must be **unique across the system** | BR | Planned | TBD | N/A |
| **BR-MT-003** | Suspended tenants can **view data but not modify** anything | BR | Planned | TBD | N/A |
| **DR-MT-001** | Store tenant **configuration as encrypted JSON** for flexibility | DR | Planned | TBD | N/A |
| **DR-MT-002** | Maintain **soft deletes** for tenant records for recovery and audit purposes | DR | Planned | TBD | N/A |
| **SR-MT-001** | Prevent **cross-tenant data exposure** through database-level isolation and query scoping | SR | Planned | TBD | N/A |
| **SR-MT-002** | All API endpoints must **authenticate tenant context** before processing requests | SR | Planned | TBD | N/A |
| **SR-MT-003** | **Encrypt tenant-specific configurations** and secrets using Laravel's encryption | SR | Planned | TBD | N/A |
| **SR-MT-004** | Log all **tenant impersonation events** with full actor and context details | SR | Planned | TBD | N/A |
| **PR-MT-001** | System must maintain sub-**100ms** average response time for tenant resolution and context loading | PR | Planned | TBD | N/A |
| **PR-MT-002** | Tenant context resolution must **not add more than 5ms overhead** to request processing | PR | Planned | TBD | N/A |
| **SCR-MT-001** | Multitenancy layer must **scale horizontally** to support 1000+ concurrent tenants | SCR | Planned | TBD | N/A |
| **SCR-MT-002** | Support **tenant-level database sharding** for future massive scale (10k+ tenants) | SCR | Planned | TBD | N/A |
| **ARCH-MT-001** | Use **UUID for tenant IDs** to prevent enumeration attacks and enable distributed generation | ARCH | Planned | TBD | N/A |
| **ARCH-MT-002** | Implement **shared-schema multi-tenancy** with tenant_id foreign key pattern | ARCH | Planned | TBD | N/A |
| **EV-MT-001** | Emit **TenantCreatedEvent** when new tenant is created | EV | Planned | TBD | N/A |
| **EV-MT-002** | Emit **TenantStatusChangedEvent** when tenant status is updated | EV | Planned | TBD | N/A |
| **EV-MT-003** | Emit **TenantImpersonationStartedEvent** when admin impersonates tenant | EV | Planned | TBD | N/A |

### **PRD01-SUB02: AUTHENTICATION**

SQL Database \- Core

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| **FR-AA-001** | Implement **User Registration** with email verification and password strength validation | FR | Planned | TBD | N/A |
| **FR-AA-002** | Implement API Authentication using **token-based** access control (Laravel Sanctum) | FR | Planned | TBD | N/A |
| **FR-AA-003** | Develop a **Role-Based Access Control (RBAC)** system with hierarchical roles | FR | Planned | TBD | N/A |
| **FR-AA-004** | Support **Permission management** with granular permissions per module and action | FR | Planned | TBD | N/A |
| **FR-AA-005** | Implement **Token refresh** mechanism for long-lived sessions | FR | Planned | TBD | N/A |
| **FR-AA-006** | Enforce **Password Security** through salted hashing using Argon2 or bcrypt | FR | Planned | TBD | N/A |
| **FR-AA-007** | Provide **Password reset** via secure email token with expiration | FR | Planned | TBD | N/A |
| **FR-AA-008** | Enable **Account Lockout** after repeated failed login attempts (5 attempts / 30 minutes) | FR | Planned | TBD | N/A |
| **FR-AA-009** | Support **Multi-Factor Authentication (MFA)** using TOTP (Time-based One-Time Password) | FR | Planned | TBD | N/A |
| **FR-AA-010** | Implement **Session management** with concurrent session limits per user | FR | Planned | TBD | N/A |
| **BR-AA-001** | Users must have at least **one role** assigned to access the system | BR | Planned | TBD | N/A |
| **BR-AA-002** | Password must be at least **12 characters** with mixed case, numbers, and symbols | BR | Planned | TBD | N/A |
| **BR-AA-003** | Tokens expire after **24 hours** of inactivity, requiring re-authentication | BR | Planned | TBD | N/A |
| **BR-AA-004** | Locked accounts require **admin intervention or time-based auto-unlock** after 1 hour | BR | Planned | TBD | N/A |
| **DR-AA-001** | Store **password history** (last 5 passwords) to prevent reuse | DR | Planned | TBD | N/A |
| **DR-AA-002** | Log all **authentication attempts** (success and failure) with IP and user agent | DR | Planned | TBD | N/A |
| **SR-AA-001** | Ensure **Tenant-Scoped Authentication** - users belong to exactly one tenant | SR | Planned | TBD | N/A |
| **SR-AA-002** | Use **HTTPS only** for all authentication endpoints (enforce in production) | SR | Planned | TBD | N/A |
| **SR-AA-003** | Enforce **API Rate Limiting** on authentication endpoints (10 attempts/minute per IP) | SR | Planned | TBD | N/A |
| **SR-AA-004** | Implement **CSRF protection** for all state-changing operations | SR | Planned | TBD | N/A |
| **SR-AA-005** | Store API tokens using **bcrypt hashing** in database | SR | Planned | TBD | N/A |
| **PR-AA-001** | Login and token validation operations must complete under **300ms** on average | PR | Planned | TBD | N/A |
| **PR-AA-002** | Permission check must complete under **10ms** using cached role-permission mappings | PR | Planned | TBD | N/A |
| **SCR-AA-001** | Support **100,000+ concurrent authenticated users** across all tenants | SCR | Planned | TBD | N/A |
| **ARCH-AA-001** | Use **Laravel Sanctum** for API token management | ARCH | Planned | TBD | N/A |
| **ARCH-AA-002** | Use **Spatie Permission** package for RBAC implementation | ARCH | Planned | TBD | N/A |
| **ARCH-AA-003** | Cache permissions in **Redis** with 1-hour TTL for performance | ARCH | Planned | TBD | N/A |
| **EV-AA-001** | Emit **UserRegisteredEvent** when new user account is created | EV | Planned | TBD | N/A |
| **EV-AA-002** | Emit **UserLoggedInEvent** on successful authentication | EV | Planned | TBD | N/A |
| **EV-AA-003** | Emit **AccountLockedEvent** when account is locked due to failed attempts | EV | Planned | TBD | N/A |
| **EV-AA-004** | Emit **PermissionsChangedEvent** when user roles or permissions are modified | EV | Planned | TBD | N/A |

### **PRD01-SUB03: AUDIT LOGGING**

MongoDB/PostgreSQL JSONB \- High Volume Logs

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| **FR-AL-001** | Capture **Activity Logs** for all CRUD operations with actor, timestamp, IP address, and context | FR | Planned | TBD | N/A |
| **FR-AL-002** | Support **Structured log queries** with filtering by user, date range, entity type, and action | FR | Planned | TBD | N/A |
| **FR-AL-003** | Implement **Automatic log enrichment** with request ID, session ID, and tenant context | FR | Planned | TBD | N/A |
| **FR-AL-004** | Attach **Data Context (before/after states)** for high-value transactional records | FR | Planned | TBD | N/A |
| **FR-AL-005** | Provide **Audit Export** capability in JSON, CSV, and PDF formats | FR | Planned | TBD | N/A |
| **FR-AL-006** | Support **Log retention policies** with automatic archival and deletion | FR | Planned | TBD | N/A |
| **BR-AL-001** | Logs are **immutable** - cannot be edited or deleted by standard users | BR | Planned | TBD | N/A |
| **BR-AL-002** | Financial transaction logs must be retained for **7 years** (compliance requirement) | BR | Planned | TBD | N/A |
| **BR-AL-003** | System administrators can view logs but **not modify audit data** | BR | Planned | TBD | N/A |
| **DR-AL-001** | Store logs with **full request/response payloads** for API calls (configurable) | DR | Planned | TBD | N/A |
| **DR-AL-002** | Partition logs by **month** for efficient querying and archival | DR | Planned | TBD | N/A |
| **DR-AL-003** | Index logs by **tenant_id, user_id, created_at, entity_type** for fast retrieval | DR | Planned | TBD | N/A |
| **SR-AL-001** | Enforce **Tenant Isolation** on all log queries - users cannot see other tenants' logs | SR | Planned | TBD | N/A |
| **SR-AL-002** | Optionally support **Log Immutability** through append-only storage (PostgreSQL or MongoDB) | SR | Planned | TBD | N/A |
| **SR-AL-003** | Mask **sensitive data** (passwords, tokens, credit cards) in logs automatically | SR | Planned | TBD | N/A |
| **PR-AL-001** | Logging operations should not add more than **10% overhead** to request processing | PR | Planned | TBD | N/A |
| **PR-AL-002** | Log queries must return results in under **2 seconds** for 1 million records | PR | Planned | TBD | N/A |
| **SCR-AL-001** | Support **100 million+ log entries** per tenant with acceptable performance | SCR | Planned | TBD | N/A |
| **CR-AL-001** | Meet **SOX compliance** requirements for financial audit trails | CR | Planned | TBD | N/A |
| **CR-AL-002** | Support **GDPR right to erasure** for personal data in logs (anonymization) | CR | Planned | TBD | N/A |
| **ARCH-AL-001** | Use **document store (MongoDB) or JSONB (PostgreSQL)** for flexible, append-only log schema | ARCH | Planned | TBD | N/A |
| **ARCH-AL-002** | Implement **async logging** using Laravel queues to prevent blocking requests | ARCH | Planned | TBD | N/A |
| **ARCH-AL-003** | Use **Spatie ActivityLog** package as foundation with custom extensions | ARCH | Planned | TBD | N/A |
| **EV-AL-001** | Emit **LogEntryCreatedEvent** when new audit log is written (for real-time monitoring) | EV | Planned | TBD | N/A |

### **PRD01-SUB04: SERIAL NUMBERING**

SQL Database \- Transactional

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| **FR-SN-001** | Allow **configurable serial number patterns** with placeholders (prefix, suffix, date formats, counters) | FR | Planned | TBD | N/A |
| **FR-SN-002** | Support **multiple number series** per tenant (invoices, POs, receipts, etc.) | FR | Planned | TBD | N/A |
| **FR-SN-003** | Support **reset periods** (daily, monthly, yearly, fiscal year, or manual) | FR | Planned | TBD | N/A |
| **FR-SN-004** | Provide **preview functionality** to see generated number before committing | FR | Planned | TBD | N/A |
| **FR-SN-005** | Support **tenant-specific customization** of number formats | FR | Planned | TBD | N/A |
| **FR-SN-006** | Allow **manual number entry** with collision detection and validation | FR | Planned | TBD | N/A |
| **FR-SN-007** | Provide **number series templates** for common document types | FR | Planned | TBD | N/A |
| **BR-SN-001** | Generated numbers must be **unique within series and tenant** | BR | Planned | TBD | N/A |
| **BR-SN-002** | Number series cannot be deleted if **documents exist** using that series | BR | Planned | TBD | N/A |
| **BR-SN-003** | Counter reset must **not create duplicate numbers** with existing records | BR | Planned | TBD | N/A |
| **BR-SN-004** | Pattern changes apply **only to new numbers**, not existing documents | BR | Planned | TBD | N/A |
| **DR-SN-001** | Store **current counter value** with atomic increment mechanism | DR | Planned | TBD | N/A |
| **DR-SN-002** | Maintain **number generation history** for audit purposes | DR | Planned | TBD | N/A |
| **DR-SN-003** | Store **pattern configuration** with validation rules per series | DR | Planned | TBD | N/A |
| **IR-SN-001** | Integrate with **all transactional modules** (AP, AR, Sales, Purchasing, Inventory) | IR | Planned | TBD | N/A |
| **SR-SN-001** | Prevent **race conditions** in concurrent serial generation through atomic database locking | SR | Planned | TBD | N/A |
| **SR-SN-002** | Ensure **tenant isolation** - no cross-tenant number conflicts | SR | Planned | TBD | N/A |
| **PR-SN-001** | Serial generation should complete under **50ms** with zero duplication | PR | Planned | TBD | N/A |
| **PR-SN-002** | Support **1000+ concurrent number generation requests** without conflicts | PR | Planned | TBD | N/A |
| **SCR-SN-001** | Support **millions of generated numbers** per series per tenant | SCR | Planned | TBD | N/A |
| **ARCH-SN-001** | Use **database-level atomic operations** (SELECT FOR UPDATE) for counter increment | ARCH | Planned | TBD | N/A |
| **ARCH-SN-002** | Implement **pessimistic locking** to guarantee uniqueness | ARCH | Planned | TBD | N/A |
| **EV-SN-001** | Emit **SerialNumberGeneratedEvent** when new number is generated | EV | Planned | TBD | N/A |
| **EV-SN-002** | Emit **SerialNumberSeriesCreatedEvent** when new series is configured | EV | Planned | TBD | N/A |
| **EV-SN-003** | Emit **SerialNumberSeriesResetEvent** when counter is reset | EV | Planned | TBD | N/A |

### **PRD01-SUB05: SETTINGS MANAGEMENT**

SQL Database \+ Redis Cache Layer

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| **FR-SM-001** | Organize Settings in a **hierarchical structure** (system, tenant, and module levels) | FR | Planned | TBD | N/A |
| **FR-SM-002** | Support **dynamic setting types** (string, integer, boolean, JSON, encrypted) | FR | Planned | TBD | N/A |
| **FR-SM-003** | Provide **setting validation** with custom validation rules per setting | FR | Planned | TBD | N/A |
| **FR-SM-004** | Provide **default values** for unset configuration parameters | FR | Planned | TBD | N/A |
| **FR-SM-005** | Support **setting inheritance** (tenant inherits from system, module from tenant) | FR | Planned | TBD | N/A |
| **FR-SM-006** | Implement **setting versioning** to track changes over time | FR | Planned | TBD | N/A |
| **FR-SM-007** | Provide **setting import/export** in JSON format for backup and migration | FR | Planned | TBD | N/A |
| **FR-SM-008** | Support **setting groups** for organized management (General, Email, API, etc.) | FR | Planned | TBD | N/A |
| **BR-SM-001** | System-level settings can only be modified by **super administrators** | BR | Planned | TBD | N/A |
| **BR-SM-002** | Tenant settings **override system defaults** when specified | BR | Planned | TBD | N/A |
| **BR-SM-003** | Required settings must have **non-null values** before system operation | BR | Planned | TBD | N/A |
| **BR-SM-004** | Setting changes requiring **service restart** must be flagged and notified | BR | Planned | TBD | N/A |
| **DR-SM-001** | Store settings as **key-value pairs** with metadata (type, scope, group) | DR | Planned | TBD | N/A |
| **DR-SM-002** | Maintain **setting change history** with user, timestamp, old/new values | DR | Planned | TBD | N/A |
| **DR-SM-003** | Support **JSON storage** for complex configuration objects | DR | Planned | TBD | N/A |
| **IR-SM-001** | Provide **setting API** for modules to retrieve configuration at runtime | IR | Planned | TBD | N/A |
| **IR-SM-002** | Integrate with **all modules** for module-specific configuration | IR | Planned | TBD | N/A |
| **SR-SM-001** | **Encrypt sensitive setting values** such as credentials, API keys, and tokens | SR | Planned | TBD | N/A |
| **SR-SM-002** | Implement **access control** - users can only view/edit authorized settings | SR | Planned | TBD | N/A |
| **SR-SM-003** | Mask sensitive values in **audit logs and API responses** | SR | Planned | TBD | N/A |
| **PR-SM-001** | **Cache frequently accessed settings** in Redis to reduce database queries | PR | Planned | TBD | N/A |
| **PR-SM-002** | Setting retrieval must complete in **< 10ms** for cached values | PR | Planned | TBD | N/A |
| **PR-SM-003** | Setting updates must propagate to cache within **100ms** | PR | Planned | TBD | N/A |
| **SCR-SM-001** | Support **10,000+ settings** per tenant without performance degradation | SCR | Planned | TBD | N/A |
| **ARCH-SM-001** | Use **Redis/Memcached for caching** tenant configs for sub-100ms access | ARCH | Planned | TBD | N/A |
| **ARCH-SM-002** | Implement **cache invalidation** on setting updates using Laravel events | ARCH | Planned | TBD | N/A |
| **ARCH-SM-003** | Store encrypted settings using **Laravel's encryption** with application key | ARCH | Planned | TBD | N/A |
| **EV-SM-001** | Emit **SettingCreatedEvent** when new setting is defined | EV | Planned | TBD | N/A |
| **EV-SM-002** | Emit **SettingUpdatedEvent** when setting value is modified | EV | Planned | TBD | N/A |
| **EV-SM-003** | Emit **SettingDeletedEvent** when setting is removed | EV | Planned | TBD | N/A |
| **EV-SM-004** | Emit **SettingCacheInvalidatedEvent** when cache needs refresh | EV | Planned | TBD | N/A |

### **PRD01-SUB06: UOM (UNIT OF MEASURE)**

SQL Database \- Transactional

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| **FR-UOM-001** | Manage **UOM master data** with name, abbreviation, and category (length, weight, volume, etc.) | FR | Planned | TBD | N/A |
| **FR-UOM-002** | Support **UOM categories** for grouping related units (metric, imperial, custom) | FR | Planned | TBD | N/A |
| **FR-UOM-003** | Manage **conversion factors** between units with precision up to six decimal places | FR | Planned | TBD | N/A |
| **FR-UOM-004** | Support **bi-directional conversions** (kg to lbs and lbs to kg) | FR | Planned | TBD | N/A |
| **FR-UOM-005** | Provide **automatic conversion** logic when transactions involve different UOMs | FR | Planned | TBD | N/A |
| **FR-UOM-006** | Support **base UOM** concept per category with all conversions relative to base | FR | Planned | TBD | N/A |
| **FR-UOM-007** | Provide **UOM templates** for common industries (retail, manufacturing, logistics) | FR | Planned | TBD | N/A |
| **FR-UOM-008** | Support **compound UOMs** (e.g., kg/m³ for density, km/hr for speed) | FR | Planned | TBD | N/A |
| **BR-UOM-001** | UOM codes must be **unique within tenant** | BR | Planned | TBD | N/A |
| **BR-UOM-002** | Base UOM conversion factor is always **1.0** (by definition) | BR | Planned | TBD | N/A |
| **BR-UOM-003** | UOMs cannot be deleted if **used in inventory or transactions** | BR | Planned | TBD | N/A |
| **BR-UOM-004** | Conversion factors must be **reciprocal** (if A→B is 2.5, then B→A is 0.4) | BR | Planned | TBD | N/A |
| **DR-UOM-001** | Store **conversion matrix** for all UOM pairs within same category | DR | Planned | TBD | N/A |
| **DR-UOM-002** | Maintain **conversion precision** settings per UOM category | DR | Planned | TBD | N/A |
| **DR-UOM-003** | Store **historical conversion factors** if they change over time | DR | Planned | TBD | N/A |
| **IR-UOM-001** | Integrate with **Inventory module** for stock quantity conversions | IR | Planned | TBD | N/A |
| **IR-UOM-002** | Integrate with **Sales module** for order quantity conversions | IR | Planned | TBD | N/A |
| **IR-UOM-003** | Integrate with **Purchasing module** for PO quantity conversions | IR | Planned | TBD | N/A |
| **PR-UOM-001** | Maintain **rounding accuracy** and prevent cumulative conversion errors | PR | Planned | TBD | N/A |
| **PR-UOM-002** | UOM conversion must complete in **< 5ms** per operation | PR | Planned | TBD | N/A |
| **PR-UOM-003** | Support **10,000+ conversions per second** for batch operations | PR | Planned | TBD | N/A |
| **SCR-UOM-001** | Support **1000+ custom UOMs** per tenant | SCR | Planned | TBD | N/A |
| **ARCH-UOM-001** | Use **high-precision decimal types** (DECIMAL(20,6)) for conversion factors | ARCH | Planned | TBD | N/A |
| **ARCH-UOM-002** | Implement **conversion caching** for frequently used UOM pairs | ARCH | Planned | TBD | N/A |
| **ARCH-UOM-003** | Use **brick/math** library for precise decimal arithmetic | ARCH | Planned | TBD | N/A |
| **EV-UOM-001** | Emit **UOMCreatedEvent** when new unit of measure is defined | EV | Planned | TBD | N/A |
| **EV-UOM-002** | Emit **UOMConversionUpdatedEvent** when conversion factor changes | EV | Planned | TBD | N/A |
| **EV-UOM-003** | Emit **UOMCategoryCreatedEvent** when new UOM category is defined | EV | Planned | TBD | N/A |

### **PRD01-SUB07: CHART OF ACCOUNTS**

SQL Database \- ACID Required

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| **FR-COA-001** | Maintain a **hierarchical chart of accounts** with unlimited depth using nested set model | FR | Planned | TBD | N/A |
| **FR-COA-002** | Allow tagging accounts by **type** (Asset, Liability, Equity, Revenue, Expense), **category**, and **reporting group** | FR | Planned | TBD | N/A |
| **FR-COA-003** | Support **account activation/deactivation** without deletion | FR | Planned | TBD | N/A |
| **FR-COA-004** | Provide **COA templates** for different industries (manufacturing, retail, services) | FR | Planned | TBD | N/A |
| **FR-COA-005** | Support **parent-child account relationships** with automatic code generation | FR | Planned | TBD | N/A |
| **FR-COA-006** | Allow **bulk import/export** of COA structure in Excel/CSV format | FR | Planned | TBD | N/A |
| **BR-COA-001** | Prevent **deletion of accounts** that have associated GL transactions | BR | Planned | TBD | N/A |
| **BR-COA-002** | Account codes must be **unique within tenant** | BR | Planned | TBD | N/A |
| **BR-COA-003** | Parent accounts cannot have **direct postings** - only leaf accounts accept transactions | BR | Planned | TBD | N/A |
| **DR-COA-001** | Store **account metadata** including description, notes, and custom fields | DR | Planned | TBD | N/A |
| **DR-COA-002** | Maintain **audit trail** of all COA structure changes | DR | Planned | TBD | N/A |
| **IR-COA-001** | Integrate with **General Ledger** for transaction posting validation | IR | Planned | TBD | N/A |
| **PR-COA-001** | Loading and filtering of COA should complete within **200ms** for 10k accounts | PR | Planned | TBD | N/A |
| **PR-COA-002** | Tree traversal operations must complete in **O(1)** time using nested set model | PR | Planned | TBD | N/A |
| **SCR-COA-001** | Support **50,000+ accounts** per tenant without performance degradation | SCR | Planned | TBD | N/A |
| **ARCH-COA-001** | Use **kalnoy/nestedset** package for hierarchical data management | ARCH | Planned | TBD | N/A |
| **EV-COA-001** | Emit **AccountCreatedEvent** when new account is added to COA | EV | Planned | TBD | N/A |
| **EV-COA-002** | Emit **AccountUpdatedEvent** when account details are modified | EV | Planned | TBD | N/A |
| **EV-COA-003** | Emit **AccountDeactivatedEvent** when account is deactivated | EV | Planned | TBD | N/A |

### **PRD01-SUB08: GENERAL LEDGER**

SQL Database \- ACID Critical

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| **FR-GL-001** | **Automatically post entries** from all submodules (AP, AR, Inventory, Payroll) to GL | FR | Planned | TBD | N/A |
| **FR-GL-002** | Support **multi-currency** transactions with real-time exchange rate application | FR | Planned | TBD | N/A |
| **FR-GL-003** | Provide **trial balance** generation with drill-down to source transactions | FR | Planned | TBD | N/A |
| **FR-GL-004** | Support **GL entry reversal** with automatic offsetting entries | FR | Planned | TBD | N/A |
| **FR-GL-005** | Implement **batch posting** for high-volume transaction processing | FR | Planned | TBD | N/A |
| **FR-GL-006** | Generate **account ledger** with running balance for any date range | FR | Planned | TBD | N/A |
| **BR-GL-001** | Ensure all journal entries are **balanced (debit = credit)** before posting | BR | Planned | TBD | N/A |
| **BR-GL-002** | Posted GL entries are **immutable** - can only be reversed, not edited | BR | Planned | TBD | N/A |
| **BR-GL-003** | Only **active accounts** can receive new postings | BR | Planned | TBD | N/A |
| **BR-GL-004** | GL entries must reference **valid accounting periods** that are open | BR | Planned | TBD | N/A |
| **DR-GL-001** | Store **aggregated monthly balances** for high-performance reporting and trial balance | DR | Planned | TBD | N/A |
| **DR-GL-002** | Maintain **source document references** for all GL entries (traceability) | DR | Planned | TBD | N/A |
| **DR-GL-003** | Store **exchange rates** at time of transaction for multi-currency support | DR | Planned | TBD | N/A |
| **IR-GL-001** | Integrate with **all transactional modules** (AP, AR, Inventory, Payroll, Banking) via events | IR | Planned | TBD | N/A |
| **IR-GL-002** | Provide **GL posting API** for third-party integrations | IR | Planned | TBD | N/A |
| **PR-GL-001** | Posting 1000 journal entries should complete under **1 second** | PR | Planned | TBD | N/A |
| **PR-GL-002** | Trial balance generation must complete in **< 2 seconds** for 100k transactions | PR | Planned | TBD | N/A |
| **PR-GL-003** | Account ledger query must return results in **< 500ms** for 50k entries | PR | Planned | TBD | N/A |
| **SCR-GL-001** | Support **millions of GL entries** per tenant per year | SCR | Planned | TBD | N/A |
| **CR-GL-001** | Meet **SOX compliance** for financial statement accuracy and auditability | CR | Planned | TBD | N/A |
| **ARCH-GL-001** | **ACID compliance** non-negotiable for financial data integrity | ARCH | Planned | TBD | N/A |
| **ARCH-GL-002** | Use **PostgreSQL row-level locking** for concurrent entry posting | ARCH | Planned | TBD | N/A |
| **ARCH-GL-003** | Implement **double-entry bookkeeping** with debit/credit validation | ARCH | Planned | TBD | N/A |
| **EV-GL-001** | Emit **GLEntryCreatedEvent** when new GL entry is created | EV | Planned | TBD | N/A |
| **EV-GL-002** | Emit **GLEntryPostedEvent** when entry is posted to ledger | EV | Planned | TBD | N/A |
| **EV-GL-003** | Emit **GLEntryReversedEvent** when entry is reversed | EV | Planned | TBD | N/A |
| **EV-GL-004** | Emit **AccountBalanceUpdatedEvent** when account balance changes | EV | Planned | TBD | N/A |

### **PRD01-SUB09: JOURNAL ENTRIES**

SQL Database \- ACID Required

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| **FR-JE-001** | Support **manual journal entry** creation with multi-line debit/credit distribution | FR | Planned | TBD | N/A |
| **FR-JE-002** | Support **recurring journals** (monthly, quarterly, annually) with automatic generation | FR | Planned | TBD | N/A |
| **FR-JE-003** | Support **reversing entries** that auto-reverse on specified date | FR | Planned | TBD | N/A |
| **FR-JE-004** | Provide **journal entry templates** for common adjustments | FR | Planned | TBD | N/A |
| **FR-JE-005** | Implement **approval workflow** (draft → pending → approved → posted) | FR | Planned | TBD | N/A |
| **FR-JE-006** | Support **attachments** (PDFs, images) to journal entries | FR | Planned | TBD | N/A |
| **BR-JE-001** | Only **authorized users** may post journals to the general ledger | BR | Planned | TBD | N/A |
| **BR-JE-002** | Journal entries must be **balanced** before submission for approval | BR | Planned | TBD | N/A |
| **BR-JE-003** | Approved entries cannot be **edited** - only reversed | BR | Planned | TBD | N/A |
| **DR-JE-001** | Store **approval history** with approver, timestamp, and comments | DR | Planned | TBD | N/A |
| **DR-JE-002** | Maintain **journal entry templates** library for reuse | DR | Planned | TBD | N/A |
| **IR-JE-001** | Integrate with **General Ledger** for automatic posting upon approval | IR | Planned | TBD | N/A |
| **PR-JE-001** | Approval and posting workflow must complete within **2 seconds** per entry | PR | Planned | TBD | N/A |
| **PR-JE-002** | Recurring journal generation must process **1000 entries in < 10 seconds** | PR | Planned | TBD | N/A |
| **EV-JE-001** | Emit **JournalEntryCreatedEvent** when new journal entry is created | EV | Planned | TBD | N/A |
| **EV-JE-002** | Emit **JournalEntrySubmittedEvent** when submitted for approval | EV | Planned | TBD | N/A |
| **EV-JE-003** | Emit **JournalEntryApprovedEvent** when approved | EV | Planned | TBD | N/A |
| **EV-JE-004** | Emit **JournalEntryPostedEvent** when posted to GL | EV | Planned | TBD | N/A |

### **PRD01-SUB10: BANKING**

SQL Database \- ACID Required

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| **FR-BR-001** | Manage **multiple bank accounts** per tenant with account details and balances | FR | Planned | TBD | N/A |
| **FR-BR-002** | Support **automated matching** of bank statements with AR/AP entries using fuzzy logic | FR | Planned | TBD | N/A |
| **FR-BR-003** | Provide **manual statement upload** with CSV/Excel parsing | FR | Planned | TBD | N/A |
| **FR-BR-004** | Generate **bank reconciliation reports** showing matched and unmatched items | FR | Planned | TBD | N/A |
| **FR-BR-005** | Support **manual matching** for exceptions that automated system cannot resolve | FR | Planned | TBD | N/A |
| **FR-BR-006** | Track **bank statement import history** with processing status | FR | Planned | TBD | N/A |
| **BR-BR-001** | Bank accounts must have **unique account numbers** within tenant | BR | Planned | TBD | N/A |
| **BR-BR-002** | Reconciliation cannot close until **all items are matched or explained** | BR | Planned | TBD | N/A |
| **DR-BR-001** | Store **bank transaction history** with match status and references | DR | Planned | TBD | N/A |
| **DR-BR-002** | Maintain **reconciliation audit trail** showing who reconciled and when | DR | Planned | TBD | N/A |
| **IR-BR-001** | Integrate with **AP module** for payment matching | IR | Planned | TBD | N/A |
| **IR-BR-002** | Integrate with **AR module** for receipt matching | IR | Planned | TBD | N/A |
| **IR-BR-003** | Integrate with **GL** for bank balance verification | IR | Planned | TBD | N/A |
| **SR-BR-001** | **Secure bank credentials** with encryption and access control (vault storage) | SR | Planned | TBD | N/A |
| **SR-BR-002** | Restrict bank account **modification to authorized users only** | SR | Planned | TBD | N/A |
| **PR-BR-001** | Reconciliation engine should handle **10k+ transactions in under 5 seconds** | PR | Planned | TBD | N/A |
| **PR-BR-002** | Statement parsing must process **1000 rows in < 2 seconds** | PR | Planned | TBD | N/A |
| **ARCH-BR-001** | Use **fuzzy string matching** algorithm for intelligent transaction matching | ARCH | Planned | TBD | N/A |
| **EV-BR-001** | Emit **BankStatementUploadedEvent** when statement is imported | EV | Planned | TBD | N/A |
| **EV-BR-002** | Emit **BankTransactionMatchedEvent** when transaction is successfully matched | EV | Planned | TBD | N/A |
| **EV-BR-003** | Emit **ReconciliationCompletedEvent** when reconciliation is finalized | EV | Planned | TBD | N/A |

### **PRD01-SUB11: ACCOUNTS PAYABLE**

SQL Database \- ACID Required

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| **FR-AP-001** | Support **AP invoice entry** with line items, taxes, and terms | FR | Planned | TBD | N/A |
| **FR-AP-002** | **Auto-generate AP entries** from approved purchase orders with 3-way matching | FR | Planned | TBD | N/A |
| **FR-AP-003** | Implement **payment scheduling** with due date tracking and reminders | FR | Planned | TBD | N/A |
| **FR-AP-004** | Support **batch payment processing** for multiple invoices | FR | Planned | TBD | N/A |
| **FR-AP-005** | Generate **vendor aging reports** (current, 30, 60, 90+ days) | FR | Planned | TBD | N/A |
| **FR-AP-006** | Support **partial payments** and payment application to multiple invoices | FR | Planned | TBD | N/A |
| **FR-AP-007** | Provide **vendor statement reconciliation** | FR | Planned | TBD | N/A |
| **BR-AP-001** | Payment amounts must **not exceed the invoice total** (allow early payment discounts) | BR | Planned | TBD | N/A |
| **BR-AP-002** | Invoices must reference **valid purchase orders** for 3-way matching | BR | Planned | TBD | N/A |
| **BR-AP-003** | Duplicate invoices (same vendor + invoice number) must be **prevented** | BR | Planned | TBD | N/A |
| **DR-AP-001** | Store **complete payment history** per invoice with application details | DR | Planned | TBD | N/A |
| **DR-AP-002** | Maintain **vendor master data** with payment terms and contact information | DR | Planned | TBD | N/A |
| **IR-AP-001** | Integrate with **banking module** for automated disbursements and reconciliation | IR | Planned | TBD | N/A |
| **IR-AP-002** | Integrate with **GL** for automatic AP/expense posting | IR | Planned | TBD | N/A |
| **IR-AP-003** | Integrate with **Purchasing** for PO-to-invoice matching | IR | Planned | TBD | N/A |
| **PR-AP-001** | Process batch payments (1000 invoices) in under **5 seconds** | PR | Planned | TBD | N/A |
| **PR-AP-002** | 3-way matching must complete in **< 1 second** per invoice | PR | Planned | TBD | N/A |
| **SCR-AP-001** | Support **100k+ outstanding invoices** per tenant | SCR | Planned | TBD | N/A |
| **EV-AP-001** | Emit **APInvoiceCreatedEvent** when new invoice is entered | EV | Planned | TBD | N/A |
| **EV-AP-002** | Emit **APInvoiceApprovedEvent** after 3-way matching approval | EV | Planned | TBD | N/A |
| **EV-AP-003** | Emit **PaymentProcessedEvent** when payment is executed | EV | Planned | TBD | N/A |
| **EV-AP-004** | Emit **VendorBalanceUpdatedEvent** when vendor balance changes | EV | Planned | TBD | N/A |

### **PRD01-SUB12: ACCOUNTS RECEIVABLE**

SQL Database \- ACID Required

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| **FR-AR-001** | Support **AR invoice entry** with line items, taxes, and payment terms | FR | Planned | TBD | N/A |
| **FR-AR-002** | **Auto-generate AR entries** from sales orders or delivery notes with validation | FR | Planned | TBD | N/A |
| **FR-AR-003** | Implement **customer payment recording** with multiple payment methods | FR | Planned | TBD | N/A |
| **FR-AR-004** | Support **payment application** to multiple invoices with allocation rules | FR | Planned | TBD | N/A |
| **FR-AR-005** | Generate **customer aging reports** (current, 30, 60, 90+ days overdue) | FR | Planned | TBD | N/A |
| **FR-AR-006** | Implement **credit limit monitoring** with alerts for approaching limits | FR | Planned | TBD | N/A |
| **FR-AR-007** | Support **customer statement generation** for outstanding balances | FR | Planned | TBD | N/A |
| **FR-AR-008** | Provide **automated dunning** (overdue payment reminders) | FR | Planned | TBD | N/A |
| **BR-AR-001** | Invoices cannot be created for customers **exceeding credit limits** without override | BR | Planned | TBD | N/A |
| **BR-AR-002** | Payment amounts cannot exceed **invoice total** (allow overpayments as credit) | BR | Planned | TBD | N/A |
| **BR-AR-003** | Duplicate invoices (same customer + invoice number) must be **prevented** | BR | Planned | TBD | N/A |
| **DR-AR-001** | Store **complete payment history** per customer with application details | DR | Planned | TBD | N/A |
| **DR-AR-002** | Maintain **customer master data** with credit limits, payment terms, and contacts | DR | Planned | TBD | N/A |
| **DR-AR-003** | Track **customer credit memos** and adjustments separately | DR | Planned | TBD | N/A |
| **IR-AR-001** | Integrate with **banking module** for payment reconciliation and matching | IR | Planned | TBD | N/A |
| **IR-AR-002** | Integrate with **GL** for automatic AR/revenue posting | IR | Planned | TBD | N/A |
| **IR-AR-003** | Integrate with **Sales** for order-to-invoice flow | IR | Planned | TBD | N/A |
| **PR-AR-001** | Generate and post receipts under **2 seconds** per transaction | PR | Planned | TBD | N/A |
| **PR-AR-002** | Customer aging report must generate in **< 3 seconds** for 50k invoices | PR | Planned | TBD | N/A |
| **SCR-AR-001** | Support **500k+ outstanding invoices** per tenant | SCR | Planned | TBD | N/A |
| **EV-AR-001** | Emit **ARInvoiceCreatedEvent** when new invoice is generated | EV | Planned | TBD | N/A |
| **EV-AR-002** | Emit **PaymentReceivedEvent** when customer payment is recorded | EV | Planned | TBD | N/A |
| **EV-AR-003** | Emit **PaymentAppliedEvent** when payment is applied to invoices | EV | Planned | TBD | N/A |
| **EV-AR-004** | Emit **CreditLimitExceededEvent** when customer approaches/exceeds limit | EV | Planned | TBD | N/A |
| **EV-AR-005** | Emit **InvoiceOverdueEvent** when invoice becomes overdue | EV | Planned | TBD | N/A |

### **PRD01-SUB13: HCM (HUMAN CAPITAL MANAGEMENT)**

SQL Database \- Transactional

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| **FR-HCM-001** | Maintain employee master data including personal, job, and **payroll information** | FR | Planned | TBD | N/A |
| **FR-HCM-002** | Support **organizational hierarchy** with reporting relationships and department structure | FR | Planned | TBD | N/A |
| **FR-HCM-003** | Manage **employee lifecycle** (hire, transfer, promotion, termination) with workflow | FR | Planned | TBD | N/A |
| **FR-HCM-004** | Track **employment history** including position changes, salary adjustments, and transfers | FR | Planned | TBD | N/A |
| **FR-HCM-005** | Support **multiple employment types** (full-time, part-time, contract, intern) | FR | Planned | TBD | N/A |
| **FR-HCM-006** | Manage **employee documents** (contracts, certifications, IDs) with expiry tracking | FR | Planned | TBD | N/A |
| **FR-HCM-007** | Track **time and attendance** with integration to payroll | FR | Planned | TBD | N/A |
| **FR-HCM-008** | Support **leave management** (annual, sick, unpaid) with balance tracking | FR | Planned | TBD | N/A |
| **FR-HCM-009** | Manage **employee benefits** (health insurance, retirement, allowances) | FR | Planned | TBD | N/A |
| **FR-HCM-010** | Provide **employee self-service portal** for data updates and leave requests | FR | Planned | TBD | N/A |
| **BR-HCM-001** | Disallow **deletion of employee records** with existing payroll or leave history | BR | Planned | TBD | N/A |
| **BR-HCM-002** | Employee IDs must be **unique within tenant** | BR | Planned | TBD | N/A |
| **BR-HCM-003** | Terminated employees cannot have **active leave or payroll** processing | BR | Planned | TBD | N/A |
| **BR-HCM-004** | Manager cannot approve their **own leave requests** | BR | Planned | TBD | N/A |
| **DR-HCM-001** | Store **sensitive personal data** (SSN, ID numbers) with encryption | DR | Planned | TBD | N/A |
| **DR-HCM-002** | Maintain **complete audit trail** of all employee data changes | DR | Planned | TBD | N/A |
| **DR-HCM-003** | Store **document metadata** with expiry dates and renewal reminders | DR | Planned | TBD | N/A |
| **IR-HCM-001** | Integrate with **Payroll module** for salary and deduction processing | IR | Planned | TBD | N/A |
| **IR-HCM-002** | Integrate with **Backoffice** for department and position management | IR | Planned | TBD | N/A |
| **IR-HCM-003** | Integrate with **Authentication** for employee user account management | IR | Planned | TBD | N/A |
| **SR-HCM-001** | Implement **role-based access** to employee personal information | SR | Planned | TBD | N/A |
| **SR-HCM-002** | **Encrypt sensitive fields** (salary, SSN, bank account) at rest | SR | Planned | TBD | N/A |
| **SR-HCM-003** | Log all **access to employee records** for compliance auditing | SR | Planned | TBD | N/A |
| **PR-HCM-001** | Employee record retrieval must complete under **200ms** | PR | Planned | TBD | N/A |
| **PR-HCM-002** | Organizational hierarchy query must return in **< 100ms** for 10k employees | PR | Planned | TBD | N/A |
| **SCR-HCM-001** | Support **100,000+ employee records** per tenant | SCR | Planned | TBD | N/A |
| **CR-HCM-001** | Comply with **GDPR** for employee personal data protection | CR | Planned | TBD | N/A |
| **CR-HCM-002** | Support **right to erasure** for terminated employees after retention period | CR | Planned | TBD | N/A |
| **ARCH-HCM-001** | Use **soft deletes** for employee records to maintain referential integrity | ARCH | Planned | TBD | N/A |
| **EV-HCM-001** | Emit **EmployeeHiredEvent** when new employee is onboarded | EV | Planned | TBD | N/A |
| **EV-HCM-002** | Emit **EmployeeTerminatedEvent** when employment ends | EV | Planned | TBD | N/A |
| **EV-HCM-003** | Emit **EmployeeTransferredEvent** when employee changes department/position | EV | Planned | TBD | N/A |
| **EV-HCM-004** | Emit **DocumentExpiringEvent** when employee document approaches expiry | EV | Planned | TBD | N/A |

### **PRD01-SUB14: INVENTORY MANAGEMENT**

SQL Database \- Transactional

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| **FR-INV-001** | Maintain **item master data** including SKU, description, UOM, and category | FR | Planned | TBD | N/A |
| **FR-INV-002** | Support **multiple warehouses** per tenant with location hierarchy (warehouse \> zone \> bin) | FR | Planned | TBD | N/A |
| **FR-INV-003** | Track **real-time inventory balances** by warehouse with available/reserved/on-hand quantities | FR | Planned | TBD | N/A |
| **FR-INV-004** | Process **stock movements** (receipt, issue, transfer, adjustment) with audit trail | FR | Planned | TBD | N/A |
| **FR-INV-005** | Support **batch/lot tracking** with expiry dates for perishable items | FR | Planned | TBD | N/A |
| **FR-INV-006** | Implement **serial number tracking** for high-value or regulated items | FR | Planned | TBD | N/A |
| **FR-INV-007** | Calculate **inventory valuation** using FIFO, LIFO, or weighted average methods | FR | Planned | TBD | N/A |
| **FR-INV-008** | Support **reorder point** and **reorder quantity** for automated replenishment alerts | FR | Planned | TBD | N/A |
| **FR-INV-009** | Perform **cycle counting** and **physical inventory** with variance reconciliation | FR | Planned | TBD | N/A |
| **FR-INV-010** | Track **inventory aging** reports for slow-moving and obsolete stock analysis | FR | Planned | TBD | N/A |
| **BR-INV-001** | Stock **cannot go negative** without explicit override permission | BR | Planned | TBD | N/A |
| **BR-INV-002** | All inventory movements must have **approved source documents** (PO, SO, adjustment form) | BR | Planned | TBD | N/A |
| **BR-INV-003** | Items with **active stock balances** cannot be deleted | BR | Planned | TBD | N/A |
| **BR-INV-004** | Inter-warehouse transfers must have **matching issue and receipt** transactions | BR | Planned | TBD | N/A |
| **DR-INV-001** | Store **transaction history** for all stock movements with timestamp and user | DR | Planned | TBD | N/A |
| **DR-INV-002** | Maintain **inventory snapshots** for monthly closing and financial reporting | DR | Planned | TBD | N/A |
| **DR-INV-003** | Record **costing data** (purchase price, landed cost, standard cost) per item | DR | Planned | TBD | N/A |
| **IR-INV-001** | Integrate with **UOM Management** for unit conversions in stock transactions | IR | Planned | TBD | N/A |
| **IR-INV-002** | Integrate with **Purchasing** for goods receipt and PO fulfillment | IR | Planned | TBD | N/A |
| **IR-INV-003** | Integrate with **Sales** for order fulfillment and stock reservation | IR | Planned | TBD | N/A |
| **IR-INV-004** | Integrate with **General Ledger** for inventory valuation posting | IR | Planned | TBD | N/A |
| **SR-INV-001** | Implement **optimistic locking** to prevent concurrent stock update conflicts | SR | Planned | TBD | N/A |
| **SR-INV-002** | Enforce **warehouse-level access control** to restrict stock visibility | SR | Planned | TBD | N/A |
| **PR-INV-001** | Stock balance queries must complete in **< 50ms** for single item | PR | Planned | TBD | N/A |
| **PR-INV-002** | Support **1,000+ concurrent stock movements** without deadlocks | PR | Planned | TBD | N/A |
| **PR-INV-003** | Inventory valuation calculation for month-end must complete in **< 5 seconds** for 100k items | PR | Planned | TBD | N/A |
| **SCR-INV-001** | Support **1 million+ items** per tenant with efficient indexing | SCR | Planned | TBD | N/A |
| **SCR-INV-002** | Handle **10 million+ transactions** per tenant per year | SCR | Planned | TBD | N/A |
| **ARCH-INV-001** | Use **database transactions** with row-level locking for stock updates | ARCH | Planned | TBD | N/A |
| **ARCH-INV-002** | Implement **event sourcing** for critical stock movements to ensure auditability | ARCH | Planned | TBD | N/A |
| **ARCH-INV-003** | Use **Redis caching** for frequently accessed stock balances | ARCH | Planned | TBD | N/A |
| **EV-INV-001** | Emit **StockReceivedEvent** when goods are received into warehouse | EV | Planned | TBD | N/A |
| **EV-INV-002** | Emit **StockIssuedEvent** when items are issued for sales or production | EV | Planned | TBD | N/A |
| **EV-INV-003** | Emit **StockAdjustedEvent** when inventory adjustments are posted | EV | Planned | TBD | N/A |
| **EV-INV-004** | Emit **LowStockAlertEvent** when item reaches reorder point | EV | Planned | TBD | N/A |

### **PRD01-SUB15: BACKOFFICE**

SQL Database \- Transactional

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| **FR-BO-001** | Manage **organizational structure** (companies, branches, departments, cost centers) | FR | Planned | TBD | N/A |
| **FR-BO-002** | Support **fiscal year management** including creation, closing, and reopening | FR | Planned | TBD | N/A |
| **FR-BO-003** | Define **accounting periods** with open/closed status per module | FR | Planned | TBD | N/A |
| **FR-BO-004** | Maintain **company master data** (registration, tax IDs, addresses, bank accounts) | FR | Planned | TBD | N/A |
| **FR-BO-005** | Manage **branch/office hierarchy** with multi-level relationships | FR | Planned | TBD | N/A |
| **FR-BO-006** | Support **department and cost center** hierarchy for expense allocation | FR | Planned | TBD | N/A |
| **FR-BO-007** | Define **approval workflows** with multi-level approvers and delegation rules | FR | Planned | TBD | N/A |
| **FR-BO-008** | Manage **document numbering sequences** per entity (company, branch, department) | FR | Planned | TBD | N/A |
| **BR-BO-001** | Only **system administrators** can create or modify fiscal years | BR | Planned | TBD | N/A |
| **BR-BO-002** | Closed accounting periods **cannot accept new transactions** without reopening | BR | Planned | TBD | N/A |
| **BR-BO-003** | **Fiscal year end date** must be after start date and cannot overlap existing years | BR | Planned | TBD | N/A |
| **BR-BO-004** | Organizational entities with **active transactions** cannot be deleted | BR | Planned | TBD | N/A |
| **DR-BO-001** | Store **complete hierarchy path** for efficient organizational queries | DR | Planned | TBD | N/A |
| **DR-BO-002** | Maintain **period lock history** for compliance and audit trail | DR | Planned | TBD | N/A |
| **DR-BO-003** | Record **approval workflow history** with timestamps and approver details | DR | Planned | TBD | N/A |
| **IR-BO-001** | Integrate with **all transactional modules** for period validation | IR | Planned | TBD | N/A |
| **IR-BO-002** | Provide **organizational hierarchy API** for authorization and reporting | IR | Planned | TBD | N/A |
| **IR-BO-003** | Integrate with **HCM** for employee-department assignments | IR | Planned | TBD | N/A |
| **SR-BO-001** | Implement **role-based access** to fiscal year and period management | SR | Planned | TBD | N/A |
| **SR-BO-002** | Log all **administrative actions** (fiscal year closing, period locking) | SR | Planned | TBD | N/A |
| **PR-BO-001** | Organizational hierarchy queries must complete in **< 100ms** for 1000+ entities | PR | Planned | TBD | N/A |
| **PR-BO-002** | Period validation check must complete in **< 10ms** | PR | Planned | TBD | N/A |
| **SCR-BO-001** | Support **10,000+ organizational entities** per tenant | SCR | Planned | TBD | N/A |
| **ARCH-BO-001** | Use **nested set model** or **closure table** for efficient hierarchy queries | ARCH | Planned | TBD | N/A |
| **ARCH-BO-002** | Cache **current period status** in Redis for fast validation | ARCH | Planned | TBD | N/A |
| **EV-BO-001** | Emit **FiscalYearClosedEvent** when fiscal year is closed | EV | Planned | TBD | N/A |
| **EV-BO-002** | Emit **PeriodLockedEvent** when accounting period is locked | EV | Planned | TBD | N/A |
| **EV-BO-003** | Emit **OrganizationUpdatedEvent** when organizational structure changes | EV | Planned | TBD | N/A |
| FR-BO-003 | Provide lookup APIs for currencies, taxes, and document templates. | FR | Planned | TBD | N/A |
| BR-BO-001 | Only **one active fiscal year per tenant** is allowed at a time. | BR | Planned | TBD | N/A |
| SR-BO-001 | Only "**System Admin**" may modify fiscal year or company info. | SR | Planned | TBD | N/A |

### **PRD01-SUB16: PURCHASING**

SQL Database \- Transactional

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| **FR-PO-001** | Support **purchase requisition** workflow with approvals before PO creation | FR | Planned | TBD | N/A |
| **FR-PO-002** | Create **purchase orders** with line items, pricing, delivery dates, and terms | FR | Planned | TBD | N/A |
| **FR-PO-003** | Manage **vendor master data** including contact info, payment terms, and ratings | FR | Planned | TBD | N/A |
| **FR-PO-004** | Support **multi-level purchase approval** based on amount thresholds and authority matrix | FR | Planned | TBD | N/A |
| **FR-PO-005** | Process **goods receipt** against PO with quantity and quality verification | FR | Planned | TBD | N/A |
| **FR-PO-006** | Support **partial deliveries** and **back orders** with fulfillment tracking | FR | Planned | TBD | N/A |
| **FR-PO-007** | Implement **three-way matching** (PO, goods receipt, vendor invoice) for AP processing | FR | Planned | TBD | N/A |
| **FR-PO-008** | Track **vendor performance metrics** (on-time delivery, quality, pricing) | FR | Planned | TBD | N/A |
| **BR-PO-001** | POs above **approval threshold** require multi-level authorization | BR | Planned | TBD | N/A |
| **BR-PO-002** | **Closed POs** cannot be modified; require change order or cancellation | BR | Planned | TBD | N/A |
| **BR-PO-003** | Goods receipt quantity **cannot exceed PO quantity** without override | BR | Planned | TBD | N/A |
| **BR-PO-004** | Vendors with **active POs** cannot be deleted | BR | Planned | TBD | N/A |
| **DR-PO-001** | Store **complete PO history** including revisions and approvals | DR | Planned | TBD | N/A |
| **DR-PO-002** | Maintain **goods receipt records** with quality inspection results | DR | Planned | TBD | N/A |
| **DR-PO-003** | Record **vendor evaluation data** for performance analysis | DR | Planned | TBD | N/A |
| **IR-PO-001** | Integrate with **Accounts Payable** for 3-way invoice matching | IR | Planned | TBD | N/A |
| **IR-PO-002** | Integrate with **Inventory** for automatic stock updates on goods receipt | IR | Planned | TBD | N/A |
| **IR-PO-003** | Integrate with **Backoffice** for approval workflow enforcement | IR | Planned | TBD | N/A |
| **SR-PO-001** | Implement **authorization matrix** for purchase approval limits | SR | Planned | TBD | N/A |
| **SR-PO-002** | Log all **PO modifications** with user and timestamp | SR | Planned | TBD | N/A |
| **PR-PO-001** | PO creation and approval must complete in **< 1 second** | PR | Planned | TBD | N/A |
| **PR-PO-002** | Vendor search must return results in **< 100ms** | PR | Planned | TBD | N/A |
| **SCR-PO-001** | Support **100,000+ POs** per tenant per year | SCR | Planned | TBD | N/A |
| **ARCH-PO-001** | Use **workflow engine** for flexible approval routing | ARCH | Planned | TBD | N/A |
| **EV-PO-001** | Emit **PurchaseOrderCreatedEvent** when PO is created | EV | Planned | TBD | N/A |
| **EV-PO-002** | Emit **PurchaseOrderApprovedEvent** when PO receives final approval | EV | Planned | TBD | N/A |
| **EV-PO-003** | Emit **GoodsReceivedEvent** when goods are received against PO | EV | Planned | TBD | N/A |
| **EV-PO-004** | Emit **VendorRatingUpdatedEvent** when vendor performance is evaluated | EV | Planned | TBD | N/A |

### **PRD01-SUB17: SALES**

SQL Database \- Transactional

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| **FR-SD-001** | Support **sales quotation** creation with pricing, discounts, and validity periods | FR | Planned | TBD | N/A |
| **FR-SD-002** | Convert quotations to **sales orders** with approval workflow | FR | Planned | TBD | N/A |
| **FR-SD-003** | Manage **customer master data** including billing/shipping addresses and credit terms | FR | Planned | TBD | N/A |
| **FR-SD-004** | Support **order fulfillment** with picking, packing, and shipping documentation | FR | Planned | TBD | N/A |
| **FR-SD-005** | Implement **pricing management** with customer-specific pricing, volume discounts, and promotions | FR | Planned | TBD | N/A |
| **FR-SD-006** | Track **sales order status** (draft, confirmed, partial, fulfilled, invoiced, closed) | FR | Planned | TBD | N/A |
| **FR-SD-007** | Support **back orders** for out-of-stock items with automatic fulfillment on restock | FR | Planned | TBD | N/A |
| **FR-SD-008** | Generate **delivery notes** and **packing lists** for shipments | FR | Planned | TBD | N/A |
| **BR-SD-001** | Orders **cannot exceed customer credit limit** without management override | BR | Planned | TBD | N/A |
| **BR-SD-002** | **Confirmed orders** cannot be modified; require change order or cancellation | BR | Planned | TBD | N/A |
| **BR-SD-003** | Fulfilled quantity **cannot exceed ordered quantity** without authorization | BR | Planned | TBD | N/A |
| **BR-SD-004** | Customers with **active orders** cannot be deleted | BR | Planned | TBD | N/A |
| **DR-SD-001** | Store **complete order history** including revisions and approvals | DR | Planned | TBD | N/A |
| **DR-SD-002** | Maintain **pricing history** for audit and analysis | DR | Planned | TBD | N/A |
| **DR-SD-003** | Record **fulfillment details** with serial/lot numbers for traceability | DR | Planned | TBD | N/A |
| **IR-SD-001** | Integrate with **Inventory** for stock reservation and fulfillment | IR | Planned | TBD | N/A |
| **IR-SD-002** | Integrate with **Accounts Receivable** for automatic invoice generation | IR | Planned | TBD | N/A |
| **IR-SD-003** | Integrate with **Backoffice** for customer credit limit checking | IR | Planned | TBD | N/A |
| **SR-SD-001** | Implement **customer-specific pricing** access controls | SR | Planned | TBD | N/A |
| **SR-SD-002** | Log all **order modifications** with user and timestamp | SR | Planned | TBD | N/A |
| **PR-SD-001** | Order processing (including stock allocation) must complete in **< 2 seconds** for orders with < 100 items | PR | Planned | TBD | N/A |
| **PR-SD-002** | Customer search must return results in **< 100ms** | PR | Planned | TBD | N/A |
| **SCR-SD-001** | Support **500,000+ sales orders** per tenant per year | SCR | Planned | TBD | N/A |
| **ARCH-SD-001** | Use **optimistic locking** for concurrent order modifications | ARCH | Planned | TBD | N/A |
| **EV-SD-001** | Emit **SalesOrderCreatedEvent** when order is created | EV | Planned | TBD | N/A |
| **EV-SD-002** | Emit **SalesOrderConfirmedEvent** when order receives approval | EV | Planned | TBD | N/A |
| **EV-SD-003** | Emit **OrderFulfilledEvent** when shipment is completed | EV | Planned | TBD | N/A |
| **EV-SD-004** | Emit **CustomerCreditLimitExceededEvent** when order exceeds credit limit | EV | Planned | TBD | N/A |

### **PRD01-SUB18: MASTER DATA MANAGEMENT**

SQL Database \+ Analytics Layer

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| **FR-MDM-001** | Provide **centralized master data** repository for customers, vendors, items, and employees | FR | Planned | TBD | N/A |
| **FR-MDM-002** | Support **data quality rules** with validation, deduplication, and enrichment | FR | Planned | TBD | N/A |
| **FR-MDM-003** | Implement **master data versioning** with effective-dated changes | FR | Planned | TBD | N/A |
| **FR-MDM-004** | Provide **data lineage tracking** showing source and transformations | FR | Planned | TBD | N/A |
| **FR-MDM-005** | Support **bulk import/export** with validation and error handling | FR | Planned | TBD | N/A |
| **FR-MDM-006** | Implement **data matching algorithms** to detect duplicates across systems | FR | Planned | TBD | N/A |
| **FR-MDM-007** | Provide **golden record** creation from multiple source systems | FR | Planned | TBD | N/A |
| **FR-MDM-008** | Support **data stewardship workflows** for master data approval | FR | Planned | TBD | N/A |
| **BR-MDM-001** | Master data **cannot be deleted** if referenced by transactional data | BR | Planned | TBD | N/A |
| **BR-MDM-002** | Duplicate records must be **merged, not overwritten** to preserve history | BR | Planned | TBD | N/A |
| **BR-MDM-003** | **Data quality score** must exceed threshold before publishing to consuming systems | BR | Planned | TBD | N/A |
| **DR-MDM-001** | Store **complete change history** for all master data with before/after snapshots | DR | Planned | TBD | N/A |
| **DR-MDM-002** | Maintain **data quality metrics** (completeness, accuracy, timeliness) | DR | Planned | TBD | N/A |
| **DR-MDM-003** | Record **data source mappings** for multi-system integration | DR | Planned | TBD | N/A |
| **IR-MDM-001** | Integrate with **all transactional modules** as master data provider | IR | Planned | TBD | N/A |
| **IR-MDM-002** | Provide **MDM API** for external system synchronization | IR | Planned | TBD | N/A |
| **IR-MDM-003** | Support **bi-directional sync** with external CRM and ERP systems | IR | Planned | TBD | N/A |
| **SR-MDM-001** | Implement **role-based access** to master data by entity type and sensitivity | SR | Planned | TBD | N/A |
| **SR-MDM-002** | **Encrypt sensitive master data** (customer PII, vendor bank details) | SR | Planned | TBD | N/A |
| **PR-MDM-001** | Master data queries must complete in **< 100ms** for single record | PR | Planned | TBD | N/A |
| **PR-MDM-002** | Bulk import must process **10,000+ records in < 60 seconds** | PR | Planned | TBD | N/A |
| **PR-MDM-003** | Real-time reporting API must return in **< 3 seconds** for datasets with < 10k rows | PR | Planned | TBD | N/A |
| **SCR-MDM-001** | Support **10 million+ master records** per tenant | SCR | Planned | TBD | N/A |
| **CR-MDM-001** | Comply with **GDPR** for customer and employee PII management | CR | Planned | TBD | N/A |
| **ARCH-MDM-001** | Use **PostgreSQL Materialized Views** or **ClickHouse** for analytics offload | ARCH | Planned | TBD | N/A |
| **ARCH-MDM-002** | Implement **CDC (Change Data Capture)** for real-time data synchronization | ARCH | Planned | TBD | N/A |
| **EV-MDM-001** | Emit **MasterDataCreatedEvent** when new master record is created | EV | Planned | TBD | N/A |
| **EV-MDM-002** | Emit **MasterDataUpdatedEvent** when master record is modified | EV | Planned | TBD | N/A |
| **EV-MDM-003** | Emit **DuplicateDetectedEvent** when potential duplicate is identified | EV | Planned | TBD | N/A |

### **PRD01-SUB19: TAXATION**

SQL Database \- Transactional

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| **FR-TAX-001** | Maintain **tax authority master data** (tax offices, jurisdictions, rates) | FR | Planned | TBD | N/A |
| **FR-TAX-002** | Support **multiple tax types** (VAT, GST, sales tax, withholding tax, excise duty) | FR | Planned | TBD | N/A |
| **FR-TAX-003** | Calculate **automatic tax** on transactions based on jurisdiction and item category | FR | Planned | TBD | N/A |
| **FR-TAX-004** | Support **tax exemptions** and **special tax rates** for specific customers or items | FR | Planned | TBD | N/A |
| **FR-TAX-005** | Generate **tax reports** (VAT return, GST summary, withholding tax report) | FR | Planned | TBD | N/A |
| **FR-TAX-006** | Support **reverse charge mechanism** for cross-border transactions | FR | Planned | TBD | N/A |
| **FR-TAX-007** | Track **tax periods** with filing deadlines and compliance status | FR | Planned | TBD | N/A |
| **FR-TAX-008** | Provide **tax reconciliation** between GL and tax reports | FR | Planned | TBD | N/A |
| **BR-TAX-001** | Tax rates must have **effective date ranges** and cannot overlap | BR | Planned | TBD | N/A |
| **BR-TAX-002** | **Negative tax amounts** not allowed without explicit reversal document | BR | Planned | TBD | N/A |
| **BR-TAX-003** | Tax configuration changes **cannot be backdated** after period closing | BR | Planned | TBD | N/A |
| **DR-TAX-001** | Store **complete tax calculation details** for each transaction line | DR | Planned | TBD | N/A |
| **DR-TAX-002** | Maintain **tax rate history** with effective dates for audit | DR | Planned | TBD | N/A |
| **DR-TAX-003** | Record **tax filing submissions** with acknowledgment receipts | DR | Planned | TBD | N/A |
| **IR-TAX-001** | Integrate with **General Ledger** for tax posting to control accounts | IR | Planned | TBD | N/A |
| **IR-TAX-002** | Integrate with **AP/AR** for tax calculation on invoices | IR | Planned | TBD | N/A |
| **IR-TAX-003** | Integrate with **Sales/Purchasing** for automatic tax determination | IR | Planned | TBD | N/A |
| **SR-TAX-001** | Implement **audit trail** for all tax configuration changes | SR | Planned | TBD | N/A |
| **SR-TAX-002** | Restrict tax rate modifications to **authorized tax administrators** | SR | Planned | TBD | N/A |
| **PR-TAX-001** | Tax calculation must complete in **< 50ms** per transaction | PR | Planned | TBD | N/A |
| **PR-TAX-002** | Tax report generation must complete in **< 10 seconds** for monthly period | PR | Planned | TBD | N/A |
| **SCR-TAX-001** | Support **multiple tax jurisdictions** (federal, state, county, city) | SCR | Planned | TBD | N/A |
| **CR-TAX-001** | Comply with **local tax regulations** for each supported jurisdiction | CR | Planned | TBD | N/A |
| **CR-TAX-002** | Support **e-filing formats** for tax authorities (XML, EDI) | CR | Planned | TBD | N/A |
| **ARCH-TAX-001** | Use **tax calculation engine** with rule-based configuration | ARCH | Planned | TBD | N/A |
| **ARCH-TAX-002** | Cache **frequently used tax rates** in Redis for performance | ARCH | Planned | TBD | N/A |
| **EV-TAX-001** | Emit **TaxCalculatedEvent** when tax is computed on transaction | EV | Planned | TBD | N/A |
| **EV-TAX-002** | Emit **TaxPeriodClosedEvent** when tax period is finalized | EV | Planned | TBD | N/A |
| **EV-TAX-003** | Emit **TaxFilingSubmittedEvent** when tax return is filed | EV | Planned | TBD | N/A |

### **PRD01-SUB20: FINANCIAL REPORTING**

SQL Database \+ Data Warehouse

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| **FR-FR-001** | Generate **standard financial statements** (Balance Sheet, P&L, Cash Flow Statement) | FR | Planned | TBD | N/A |
| **FR-FR-002** | Support **multi-period comparative reports** with variance analysis | FR | Planned | TBD | N/A |
| **FR-FR-003** | Provide **drill-down capability** from summary to transaction detail | FR | Planned | TBD | N/A |
| **FR-FR-004** | Support **custom report builder** with drag-and-drop field selection | FR | Planned | TBD | N/A |
| **FR-FR-005** | Generate **management reports** (departmental P&L, cost center analysis) | FR | Planned | TBD | N/A |
| **FR-FR-006** | Support **report scheduling** with email delivery and export formats (PDF, Excel, CSV) | FR | Planned | TBD | N/A |
| **FR-FR-007** | Provide **real-time dashboards** with KPIs and trend charts | FR | Planned | TBD | N/A |
| **FR-FR-008** | Support **consolidation reporting** for multi-company groups | FR | Planned | TBD | N/A |
| **BR-FR-001** | Reports can only be generated for **closed accounting periods** or current period | BR | Planned | TBD | N/A |
| **BR-FR-002** | **Compliance reports** (SOX, IFRS) require audit trail and version control | BR | Planned | TBD | N/A |
| **BR-FR-003** | Financial statements must **balance** (assets = liabilities + equity) | BR | Planned | TBD | N/A |
| **DR-FR-001** | Store **report definitions** with field mappings and formulas | DR | Planned | TBD | N/A |
| **DR-FR-002** | Maintain **report execution history** with parameters and generated snapshots | DR | Planned | TBD | N/A |
| **DR-FR-003** | Cache **aggregated financial data** for faster report generation | DR | Planned | TBD | N/A |
| **IR-FR-001** | Integrate with **General Ledger** as primary data source | IR | Planned | TBD | N/A |
| **IR-FR-002** | Integrate with **all transactional modules** for operational reports | IR | Planned | TBD | N/A |
| **IR-FR-003** | Provide **BI tool integration** (Power BI, Tableau, Looker) via APIs | IR | Planned | TBD | N/A |
| **SR-FR-001** | Implement **role-based access** to financial reports by sensitivity level | SR | Planned | TBD | N/A |
| **SR-FR-002** | **Watermark** and **log access** to confidential financial reports | SR | Planned | TBD | N/A |
| **PR-FR-001** | Dashboard queries must return in **< 3 seconds** for datasets with < 10k rows | PR | Planned | TBD | N/A |
| **PR-FR-002** | Financial statement generation must complete in **< 5 seconds** for monthly period | PR | Planned | TBD | N/A |
| **SCR-FR-001** | Support **10+ years of historical data** for trend analysis | SCR | Planned | TBD | N/A |
| **CR-FR-001** | Comply with **GAAP/IFRS** reporting standards | CR | Planned | TBD | N/A |
| **CR-FR-002** | Support **SOX compliance** with complete audit trails | CR | Planned | TBD | N/A |
| **ARCH-FR-001** | Use **PostgreSQL Materialized Views** or **dedicated Data Warehouse** (ClickHouse) | ARCH | Planned | TBD | N/A |
| **ARCH-FR-002** | Implement **OLAP cubes** for multi-dimensional analysis | ARCH | Planned | TBD | N/A |
| **ARCH-FR-003** | Use **incremental aggregation** to optimize report performance | ARCH | Planned | TBD | N/A |
| **EV-FR-001** | Emit **ReportGeneratedEvent** when report is created | EV | Planned | TBD | N/A |
| **EV-FR-002** | Emit **DashboardRefreshedEvent** when real-time dashboard updates | EV | Planned | TBD | N/A |

### **PRD01-SUB21: WORKFLOW ENGINE**

SQL Database \+ Redis Queue

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| **FR-WF-001** | Support **visual workflow designer** for creating approval chains | FR | Planned | TBD | N/A |
| **FR-WF-002** | Implement **multi-level approval routing** with parallel and sequential flows | FR | Planned | TBD | N/A |
| **FR-WF-003** | Support **conditional routing** based on transaction amount, type, or custom rules | FR | Planned | TBD | N/A |
| **FR-WF-004** | Provide **escalation rules** for overdue approvals with deadline enforcement | FR | Planned | TBD | N/A |
| **FR-WF-005** | Support **delegation** of approval authority with time-bound assignments | FR | Planned | TBD | N/A |
| **FR-WF-006** | Track **workflow status** with real-time progress visualization | FR | Planned | TBD | N/A |
| **FR-WF-007** | Support **workflow templates** for common approval patterns | FR | Planned | TBD | N/A |
| **FR-WF-008** | Provide **workflow inbox** for pending approvals with filtering and sorting | FR | Planned | TBD | N/A |
| **BR-WF-001** | Workflow cannot be **modified** after activation; require versioning | BR | Planned | TBD | N/A |
| **BR-WF-002** | Approvers cannot approve their **own submissions** | BR | Planned | TBD | N/A |
| **BR-WF-003** | **Rejected items** return to originator; cannot proceed to next stage | BR | Planned | TBD | N/A |
| **DR-WF-001** | Store **complete workflow execution history** with timestamps and approver comments | DR | Planned | TBD | N/A |
| **DR-WF-002** | Maintain **workflow definitions** with versioning for audit trail | DR | Planned | TBD | N/A |
| **DR-WF-003** | Record **delegation history** with effective dates | DR | Planned | TBD | N/A |
| **IR-WF-001** | Integrate with **all transactional modules** via event bus | IR | Planned | TBD | N/A |
| **IR-WF-002** | Integrate with **Notifications** for approval alerts | IR | Planned | TBD | N/A |
| **IR-WF-003** | Provide **workflow API** for external system integration | IR | Planned | TBD | N/A |
| **SR-WF-001** | Implement **authorization checks** to ensure approvers have required permissions | SR | Planned | TBD | N/A |
| **SR-WF-002** | Log all **workflow actions** (approve, reject, delegate) with user details | SR | Planned | TBD | N/A |
| **PR-WF-001** | Workflow routing decision must complete in **< 100ms** | PR | Planned | TBD | N/A |
| **PR-WF-002** | Support **1,000+ concurrent workflow instances** without performance degradation | PR | Planned | TBD | N/A |
| **SCR-WF-001** | Support **100+ active workflow definitions** per tenant | SCR | Planned | TBD | N/A |
| **ARCH-WF-001** | Store workflow definitions in **SQL** with JSON configuration | ARCH | Planned | TBD | N/A |
| **ARCH-WF-002** | Use **Redis Queue** for asynchronous workflow execution | ARCH | Planned | TBD | N/A |
| **ARCH-WF-003** | Implement **state machine pattern** for workflow execution | ARCH | Planned | TBD | N/A |
| **EV-WF-001** | Emit **WorkflowStartedEvent** when workflow instance is initiated | EV | Planned | TBD | N/A |
| **EV-WF-002** | Emit **WorkflowApprovedEvent** when approval step is completed | EV | Planned | TBD | N/A |
| **EV-WF-003** | Emit **WorkflowRejectedEvent** when workflow is rejected | EV | Planned | TBD | N/A |
| **EV-WF-004** | Emit **WorkflowEscalatedEvent** when approval deadline is exceeded | EV | Planned | TBD | N/A |

### **PRD01-SUB22: NOTIFICATIONS & EVENTS**

Redis Queue \+ Laravel Horizon

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| **FR-NE-001** | Support **multi-channel notifications** (email, SMS, push, in-app, webhook) | FR | Planned | TBD | N/A |
| **FR-NE-002** | Provide **notification templates** with variable substitution | FR | Planned | TBD | N/A |
| **FR-NE-003** | Support **event subscriptions** with user-configurable preferences | FR | Planned | TBD | N/A |
| **FR-NE-004** | Implement **notification scheduling** with delivery time optimization | FR | Planned | TBD | N/A |
| **FR-NE-005** | Track **notification delivery status** (sent, delivered, failed, bounced) | FR | Planned | TBD | N/A |
| **FR-NE-006** | Support **notification grouping** and **digest mode** to reduce noise | FR | Planned | TBD | N/A |
| **FR-NE-007** | Provide **notification history** with read/unread status tracking | FR | Planned | TBD | N/A |
| **FR-NE-008** | Support **real-time event streaming** via WebSockets for live updates | FR | Planned | TBD | N/A |
| **BR-NE-001** | Users must **opt-in** for non-critical notifications | BR | Planned | TBD | N/A |
| **BR-NE-002** | **Critical alerts** (security, compliance) cannot be disabled by users | BR | Planned | TBD | N/A |
| **BR-NE-003** | Failed notifications must **retry** with exponential backoff up to 3 attempts | BR | Planned | TBD | N/A |
| **DR-NE-001** | Store **notification logs** for audit and troubleshooting | DR | Planned | TBD | N/A |
| **DR-NE-002** | Maintain **user preferences** for notification channels and frequency | DR | Planned | TBD | N/A |
| **DR-NE-003** | Record **webhook delivery attempts** with response codes | DR | Planned | TBD | N/A |
| **IR-NE-001** | Integrate with **all modules** via event-driven architecture | IR | Planned | TBD | N/A |
| **IR-NE-002** | Support **external notification services** (SendGrid, Twilio, Firebase) | IR | Planned | TBD | N/A |
| **IR-NE-003** | Provide **webhook endpoints** for third-party system notifications | IR | Planned | TBD | N/A |
| **SR-NE-001** | Implement **rate limiting** to prevent notification spam | SR | Planned | TBD | N/A |
| **SR-NE-002** | **Validate webhook signatures** to ensure authenticity | SR | Planned | TBD | N/A |
| **PR-NE-001** | Notifications must be queued and delivered within **3 seconds** of triggering event | PR | Planned | TBD | N/A |
| **PR-NE-002** | Support **10,000+ notifications per minute** during peak load | PR | Planned | TBD | N/A |
| **SCR-NE-001** | Scale to **1 million+ notifications per day** per tenant | SCR | Planned | TBD | N/A |
| **ARCH-NE-001** | Use **Redis with Laravel Queue/Horizon** for asynchronous message processing | ARCH | Planned | TBD | N/A |
| **ARCH-NE-002** | Implement **pub/sub pattern** for real-time event broadcasting | ARCH | Planned | TBD | N/A |
| **ARCH-NE-003** | Use **Laravel Echo** with WebSockets for real-time notifications | ARCH | Planned | TBD | N/A |
| **EV-NE-001** | Emit **NotificationSentEvent** when notification is dispatched | EV | Planned | TBD | N/A |
| **EV-NE-002** | Emit **NotificationFailedEvent** when delivery fails after retries | EV | Planned | TBD | N/A |
| **EV-NE-003** | Emit **WebhookDeliveredEvent** when webhook successfully delivers | EV | Planned | TBD | N/A |

### **PRD01-SUB23: API GATEWAY & DOCS**

SQL Database \- Core

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| **FR-API-001** | Provide **unified RESTful API gateway** with versioning support (v1, v2) | FR | Planned | TBD | N/A |
| **FR-API-002** | Support **GraphQL API** for flexible data querying | FR | Planned | TBD | N/A |
| **FR-API-003** | Generate **interactive API documentation** with Swagger/OpenAPI | FR | Planned | TBD | N/A |
| **FR-API-004** | Provide **API sandbox environment** for testing without affecting production | FR | Planned | TBD | N/A |
| **FR-API-005** | Support **batch operations** for bulk data updates | FR | Planned | TBD | N/A |
| **FR-API-006** | Implement **webhook management** for event subscriptions | FR | Planned | TBD | N/A |
| **FR-API-007** | Provide **API client SDKs** for common languages (PHP, JavaScript, Python) | FR | Planned | TBD | N/A |
| **BR-API-001** | API versions must be **backward compatible** for at least 12 months | BR | Planned | TBD | N/A |
| **BR-API-002** | **Deprecated endpoints** must show warnings 3 months before removal | BR | Planned | TBD | N/A |
| **DR-API-001** | Log all **API requests** with response times and status codes | DR | Planned | TBD | N/A |
| **DR-API-002** | Store **API usage metrics** for analytics and billing | DR | Planned | TBD | N/A |
| **IR-API-001** | Integrate with **all modules** via consistent API patterns | IR | Planned | TBD | N/A |
| **IR-API-002** | Support **OAuth 2.0** and **API key authentication** | IR | Planned | TBD | N/A |
| **SR-API-001** | Implement **rate limiting** per API key with tiered plans | SR | Planned | TBD | N/A |
| **SR-API-002** | **Authenticate and authorize** all API requests | SR | Planned | TBD | N/A |
| **SR-API-003** | **Encrypt API keys** at rest and require HTTPS for all endpoints | SR | Planned | TBD | N/A |
| **PR-API-001** | API gateway routing must add **< 10ms latency** | PR | Planned | TBD | N/A |
| **PR-API-002** | Support **10,000+ API requests per second** with horizontal scaling | PR | Planned | TBD | N/A |
| **SCR-API-001** | Scale API gateway **horizontally** with load balancing | SCR | Planned | TBD | N/A |
| **ARCH-API-001** | Use **Laravel Sanctum** for API authentication | ARCH | Planned | TBD | N/A |
| **ARCH-API-002** | Implement **API versioning** via URL path (/api/v1/, /api/v2/) | ARCH | Planned | TBD | N/A |
| **ARCH-API-003** | Use **Redis** for rate limiting and API key validation caching | ARCH | Planned | TBD | N/A |
| **EV-API-001** | Emit **APIRequestEvent** for monitoring and analytics | EV | Planned | TBD | N/A |
| **EV-API-002** | Emit **RateLimitExceededEvent** when API quota is reached | EV | Planned | TBD | N/A |

### **PRD01-SUB24: INTEGRATION CONNECTORS**

SQL Database \+ Event Streams

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| **FR-IC-001** | Support **pre-built connectors** for common ERP/CRM systems (SAP, Salesforce, QuickBooks) | FR | Planned | TBD | N/A |
| **FR-IC-002** | Provide **connector framework** for custom integration development | FR | Planned | TBD | N/A |
| **FR-IC-003** | Support **bi-directional data synchronization** with conflict resolution | FR | Planned | TBD | N/A |
| **FR-IC-004** | Implement **field mapping configuration** with transformation rules | FR | Planned | TBD | N/A |
| **FR-IC-005** | Support **scheduled sync** and **real-time event-driven** integration | FR | Planned | TBD | N/A |
| **FR-IC-006** | Provide **integration monitoring dashboard** with sync status and error logs | FR | Planned | TBD | N/A |
| **FR-IC-007** | Support **data validation** before and after sync operations | FR | Planned | TBD | N/A |
| **FR-IC-008** | Implement **retry logic** with exponential backoff for failed syncs | FR | Planned | TBD | N/A |
| **BR-IC-001** | Connector configuration changes require **approval** in production | BR | Planned | TBD | N/A |
| **BR-IC-002** | Failed sync attempts must **alert administrators** after 3 retries | BR | Planned | TBD | N/A |
| **BR-IC-003** | Connectors cannot be **deleted** with active sync schedules | BR | Planned | TBD | N/A |
| **DR-IC-001** | Store **complete sync history** with before/after data snapshots | DR | Planned | TBD | N/A |
| **DR-IC-002** | Maintain **field mapping configurations** with versioning | DR | Planned | TBD | N/A |
| **DR-IC-003** | Log all **integration errors** with detailed diagnostic information | DR | Planned | TBD | N/A |
| **IR-IC-001** | Integrate with **all transactional modules** for data extraction | IR | Planned | TBD | N/A |
| **IR-IC-002** | Support **REST, SOAP, and GraphQL** protocols for external systems | IR | Planned | TBD | N/A |
| **IR-IC-003** | Provide **webhook receivers** for inbound data from external systems | IR | Planned | TBD | N/A |
| **SR-IC-001** | **Encrypt credentials** for external systems at rest | SR | Planned | TBD | N/A |
| **SR-IC-002** | Implement **OAuth 2.0** for secure third-party authentication | SR | Planned | TBD | N/A |
| **SR-IC-003** | Log all **data transfers** for compliance auditing | SR | Planned | TBD | N/A |
| **PR-IC-001** | Sync operations must complete within **30 seconds** for 1000 records | PR | Planned | TBD | N/A |
| **PR-IC-002** | Support **parallel sync jobs** for multiple connectors | PR | Planned | TBD | N/A |
| **SCR-IC-001** | Support **50+ active connectors** per tenant | SCR | Planned | TBD | N/A |
| **CR-IC-001** | Comply with **data privacy laws** (GDPR, CCPA) for cross-system data transfer | CR | Planned | TBD | N/A |
| **ARCH-IC-001** | Store connector configurations in **SQL** with encrypted credentials | ARCH | Planned | TBD | N/A |
| **ARCH-IC-002** | Use **Redis/Kafka** for event-driven real-time synchronization | ARCH | Planned | TBD | N/A |
| **ARCH-IC-003** | Implement **idempotency** for safe retry of failed operations | ARCH | Planned | TBD | N/A |
| **EV-IC-001** | Emit **SyncStartedEvent** when integration sync begins | EV | Planned | TBD | N/A |
| **EV-IC-002** | Emit **SyncCompletedEvent** when sync finishes successfully | EV | Planned | TBD | N/A |
| **EV-IC-003** | Emit **SyncFailedEvent** when sync encounters unrecoverable error | EV | Planned | TBD | N/A |

### **PRD01-SUB25: LOCALIZATION**

SQL Database \+ Redis Cache

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| **FR-LOC-001** | Support **multi-language user interfaces** with runtime language switching | FR | Planned | TBD | N/A |
| **FR-LOC-002** | Support **multi-currency transactions** with automatic conversion | FR | Planned | TBD | N/A |
| **FR-LOC-003** | Implement **exchange rate management** with historical rate tracking | FR | Planned | TBD | N/A |
| **FR-LOC-004** | Support **date and time format localization** per user preferences | FR | Planned | TBD | N/A |
| **FR-LOC-005** | Provide **number formatting** based on locale (decimal separators, grouping) | FR | Planned | TBD | N/A |
| **FR-LOC-006** | Support **right-to-left (RTL)** languages (Arabic, Hebrew) | FR | Planned | TBD | N/A |
| **FR-LOC-007** | Implement **translation management** with fallback to default language | FR | Planned | TBD | N/A |
| **FR-LOC-008** | Support **country-specific tax and compliance rules** | FR | Planned | TBD | N/A |
| **BR-LOC-001** | Currency exchange rates must be **updated daily** from reliable sources | BR | Planned | TBD | N/A |
| **BR-LOC-002** | **Base currency** cannot be changed after transactions are posted | BR | Planned | TBD | N/A |
| **BR-LOC-003** | Translation keys without translations must **fallback to English** | BR | Planned | TBD | N/A |
| **DR-LOC-001** | Store **translation strings** in database for dynamic updates | DR | Planned | TBD | N/A |
| **DR-LOC-002** | Maintain **exchange rate history** for revaluation and audit | DR | Planned | TBD | N/A |
| **DR-LOC-003** | Record **user language preferences** per account | DR | Planned | TBD | N/A |
| **IR-LOC-001** | Integrate with **external currency APIs** (ECB, Fixer.io) for rate updates | IR | Planned | TBD | N/A |
| **IR-LOC-002** | Provide **localization API** for custom frontend applications | IR | Planned | TBD | N/A |
| **SR-LOC-001** | Implement **role-based access** to exchange rate management | SR | Planned | TBD | N/A |
| **SR-LOC-002** | Log all **exchange rate changes** with user and timestamp | SR | Planned | TBD | N/A |
| **PR-LOC-001** | Language switching must complete within **200ms** | PR | Planned | TBD | N/A |
| **PR-LOC-002** | Currency conversion must complete in **< 10ms** per transaction | PR | Planned | TBD | N/A |
| **SCR-LOC-001** | Support **50+ languages** and **150+ currencies** | SCR | Planned | TBD | N/A |
| **ARCH-LOC-001** | Use **Redis caching** for translation strings and exchange rates | ARCH | Planned | TBD | N/A |
| **ARCH-LOC-002** | Implement **Laravel localization** framework for string management | ARCH | Planned | TBD | N/A |
| **ARCH-LOC-003** | Store translations in **JSON format** for easy import/export | ARCH | Planned | TBD | N/A |
| **EV-LOC-001** | Emit **ExchangeRateUpdatedEvent** when rates are refreshed | EV | Planned | TBD | N/A |
| **EV-LOC-002** | Emit **LanguageChangedEvent** when user switches language | EV | Planned | TBD | N/A |
| **EV-LOC-003** | Emit **TranslationUpdatedEvent** when translation strings are modified | EV | Planned | TBD | N/A |

#### F.2.4 **Milestone to Plan Mapping (Timeline)**

The development roadmap is structured into 12 distinct milestones.

| Milestone | Due Date | Associated Implementation Plans |
| :---- | :---- | :---- |
| **MILESTONE 1** | Nov 30, 2025 | PRD01-SUB01-PLAN01-IMPLEMENT-MULTITENANCY.md, PRD01-SUB02-PLAN01-IMPLEMENT-AUTHENTICATION.md (Part 1\) |
| **MILESTONE 2** | Dec 15, 2025 | PRD01-SUB02-PLAN01-IMPLEMENT-AUTHENTICATION.md (Part 2 & 3), PRD01-SUB03-PLAN01-IMPLEMENT-AUDIT-LOGGING.md |
| **MILESTONE 3** | Dec 31, 2025 | PRD01-SUB04-PLAN01-IMPLEMENT-SERIAL-NUMBERING.md, PRD01-SUB05-PLAN01-IMPLEMENT-SETTINGS-MANAGEMENT.md, PRD01-SUB06-PLAN01-IMPLEMENT-UOM.md, PRD01-SUB15-PLAN01-IMPLEMENT-BACKOFFICE.md |
| **MILESTONE 4** | Jan 15, 2026 | PRD01-SUB07-PLAN01-IMPLEMENT-CHART-OF-ACCOUNTS.md, PRD01-SUB08-PLAN01-IMPLEMENT-GENERAL-LEDGER.md |
| **MILESTONE 5** | Jan 31, 2026 | PRD01-SUB09-PLAN01-IMPLEMENT-JOURNAL-ENTRIES.md, PRD01-SUB10-PLAN01-IMPLEMENT-BANKING.md |
| **MILESTONE 6** | Feb 21, 2026 | PRD01-SUB11-PLAN01-IMPLEMENT-ACCOUNTS-PAYABLE.md, PRD01-SUB12-PLAN01-IMPLEMENT-ACCOUNTS-RECEIVABLE.md |
| **MILESTONE 7** | Mar 14, 2026 | PRD01-SUB13-PLAN01-IMPLEMENT-HCM.md, PRD01-SUB14-PLAN01-IMPLEMENT-INVENTORY-MANAGEMENT.md |
| **MILESTONE 8** | Mar 31, 2026 | PRD01-SUB16-PLAN01-IMPLEMENT-PURCHASING.md, PRD01-SUB17-PLAN01-IMPLEMENT-SALES.md |
| **MILESTONE 9** | Apr 30, 2026 | PRD01-SUB19-PLAN01-IMPLEMENT-TAXATION.md, PRD01-SUB20-PLAN01-IMPLEMENT-FINANCIAL-REPORTING.md |
| **MILESTONE 10** | May 31, 2026 | PRD01-SUB21-PLAN01-IMPLEMENT-WORKFLOW-ENGINE.md, PRD01-SUB22-PLAN01-IMPLEMENT-NOTIFICATIONS-EVENTS.md |
| **MILESTONE 11** | Jun 30, 2026 | PRD01-SUB23-PLAN01-IMPLEMENT-API-GATEWAY.md, PRD01-SUB24-PLAN01-IMPLEMENT-INTEGRATION-CONNECTORS.md |
| **MILESTONE 12** | Jul 31, 2026 | PRD01-SUB18-PLAN01-IMPLEMENT-MDM.md, PRD01-SUB25-PLAN01-IMPLEMENT-LOCALIZATION.md |

