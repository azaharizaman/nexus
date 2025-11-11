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
├── packages/
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
| Component | Recommendation | Purpose |
|-----------|---------------|---------|
| **Primary RDBMS** | PostgreSQL (preferred) or MySQL | Transactional data with ACID compliance |
| **Cache Layer** | Redis or Memcached | Settings cache, session storage, rate limiting |
| **Search Engine** | Laravel Scout + Meilisearch/Algolia | Full-text search across entities |
| **Audit Storage** | MongoDB or PostgreSQL JSONB | High-volume, flexible log storage |

**Database Strategy:** Hybrid SQL + NoSQL approach (see Hybrid Database Strategy section below)

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
| Package | Purpose | Location |
|---------|---------|----------|
| **azaharizaman/erp-core** | Multi-tenancy, auth, audit logging | `packages/core/` |
| **azaharizaman/erp-uom** | Unit of Measure management | `packages/uom/` |
| **azaharizaman/erp-inventory** | Inventory and stock control | `packages/inventory/` |
| **azaharizaman/erp-backoffice** | Company/branch/department structure | `packages/backoffice/` |
| **azaharizaman/erp-serial-numbering** | Document numbering system | `packages/serial-numbering/` |
| **azaharizaman/huggingface-php** | AI/ML integration capabilities | External package |

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

### D.1. Core Modules (The Essential Backbone)

**Core modules** are always included in every installation and provide the foundational infrastructure for the ERP system. These modules cannot be disabled or removed as they form the operational backbone.

#### D.1.1. Mandatory Core Modules

| Core Module | Sub-PRD Reference | Purpose | Always Active |
|-------------|-------------------|---------|---------------|
| **Multi-Tenancy System** | PRD01-SUB01 | Tenant isolation, context management, impersonation | ✅ Yes |
| **Authentication & Authorization** | PRD01-SUB02 | User management, RBAC, API token authentication | ✅ Yes |
| **Audit Logging** | PRD01-SUB03 | Activity tracking, compliance, security monitoring | ✅ Yes |
| **Settings Management** | PRD01-SUB05 | System/tenant/module configuration storage | ✅ Yes |
| **API Gateway** | PRD01-SUB23 | Unified API entry point, rate limiting, documentation | ✅ Yes |
| **Notifications & Events** | PRD01-SUB22 | Event bus, notification delivery, webhook management | ✅ Yes |

**Core Module Characteristics:**
- **Non-removable:** Cannot be uninstalled or disabled
- **Zero Dependencies:** Do not depend on feature modules
- **Foundation Layer:** Provide services that feature modules depend on
- **Always Tested:** 100% test coverage required
- **Performance Critical:** Must meet sub-200ms response time targets

### D.2. Feature Modules (The Plug-ins)

**Feature modules** are optional, industry-specific, or business-function-specific modules that can be installed, enabled, or disabled independently based on organizational needs.

#### D.2.1. Available Feature Modules

**Requirement:** Each module must be completely independent and installable/removable without affecting the Core or other feature modules.

| Feature Module | Sub-PRD Reference | Industry Focus | Dependencies | Installation Status |
|----------------|-------------------|----------------|--------------|---------------------|
| **Serial Numbering** | PRD01-SUB04 | All industries | Core only | Optional |
| **Unit of Measure (UOM)** | PRD01-SUB06 | Manufacturing, Retail, Distribution | Core only | Optional |
| **Backoffice** | PRD01-SUB15 | All industries | Core only | Optional |
| **Chart of Accounts** | PRD01-SUB07 | Finance-focused organizations | Core, Backoffice | Optional |
| **General Ledger** | PRD01-SUB08 | Finance-focused organizations | Core, COA, Backoffice | Optional |
| **Journal Entries** | PRD01-SUB09 | Finance-focused organizations | Core, GL | Optional |
| **Banking** | PRD01-SUB10 | Organizations with banking operations | Core, GL | Optional |
| **Accounts Payable** | PRD01-SUB11 | Organizations with supplier management | Core, GL, Banking | Optional |
| **Accounts Receivable** | PRD01-SUB12 | Organizations with customer billing | Core, GL, Banking | Optional |
| **Human Capital Management** | PRD01-SUB13 | Organizations with employees | Core, Backoffice | Optional |
| **Inventory Management** | PRD01-SUB14 | Retail, Manufacturing, Distribution | Core, UOM, Backoffice | Optional |
| **Purchasing/SCM** | PRD01-SUB16 | Procurement-focused organizations | Core, Inventory, AP | Optional |
| **Sales & Distribution** | PRD01-SUB17 | Sales-focused organizations | Core, Inventory, AR | Optional |
| **Master Data Management** | PRD01-SUB18 | Large enterprises | Core, multiple modules | Optional |
| **Taxation** | PRD01-SUB19 | Tax-compliant organizations | Core, GL, AR, AP | Optional |
| **Financial Reporting** | PRD01-SUB20 | Compliance-focused organizations | Core, GL, AP, AR | Optional |
| **Workflow Engine** | PRD01-SUB21 | Process-driven organizations | Core only | Optional |
| **Localization** | PRD01-SUB25 | Multi-region organizations | Core only | Optional |
| **Integration Connectors** | PRD01-SUB24 | Organizations with external systems | Core, API Gateway | Optional |

#### D.2.2. Module Independence Requirements

To ensure true modularity and prevent coupling, each feature module MUST adhere to:

1. **Self-Contained Business Logic:**
   - All domain logic contained within module boundaries
   - No direct method calls to other feature modules
   - Database tables use module-specific prefixes (e.g., `inv_`, `sales_`, `hr_`)

2. **Event-Driven Communication:**
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

### D.3. Feature Toggling Mechanism

The system provides flexible feature toggling at multiple levels to accommodate different organizational sizes and industry requirements.

#### D.3.1. Toggling Levels

| Level | Scope | Use Case | Example |
|-------|-------|----------|---------|
| **System-Wide** | All tenants | Enable/disable modules globally | Disable Inventory module for SaaS offering |
| **Tenant-Level** | Single tenant | Enable/disable for specific tenant | Enable HR module only for Enterprise tier tenants |
| **User-Level** | Individual users | Feature flags for specific users | Beta feature access for selected users |
| **Environment** | Dev/Staging/Production | Test features before production | Enable experimental AI features in staging |

#### D.3.2. Toggle Implementation Strategy

**Technical Implementation:**

```php
// Example: Check if module is enabled for current tenant
if (Modules::isEnabled('inventory', tenant())) {
    // Inventory-specific logic
}

// Example: Feature flag for specific capability
if (Feature::active('ai-powered-categorization')) {
    // AI categorization logic
}
```

**Toggle Storage:**
- **System-wide toggles:** `system_settings` table
- **Tenant toggles:** `tenant_settings` table with module scope
- **User toggles:** `user_feature_flags` table
- **Cache layer:** Redis for sub-10ms toggle checks

**Toggle Management:**

1. **Admin API Endpoints:**
   - `POST /api/v1/admin/modules/{module}/enable` - Enable module for tenant
   - `POST /api/v1/admin/modules/{module}/disable` - Disable module for tenant
   - `GET /api/v1/admin/modules` - List all modules and their status

2. **Tenant Self-Service:**
   - Tenants can enable/disable modules within their subscription tier
   - Upgrade prompts for premium modules
   - Usage tracking per module for billing

3. **Graceful Degradation:**
   - API endpoints return `404 Not Found` for disabled modules
   - Clear error messages: `{"error": "Module 'inventory' is not enabled for this tenant"}`
   - Frontend can query `/api/v1/modules/enabled` to show/hide features

#### D.3.3. Module Lifecycle Management

**Installation Process:**
1. Check dependencies are met
2. Run module-specific migrations
3. Seed default data if needed
4. Register API routes and event listeners
5. Update OpenAPI documentation
6. Emit `ModuleEnabledEvent`

**Uninstallation Process:**
1. Check no dependent modules are active
2. Archive module data (optional, configurable)
3. Remove API routes
4. Clean up event listeners
5. Emit `ModuleDisabledEvent`

**Upgrade Process:**
- Module versions tracked independently
- Rolling updates without downtime
- Database migrations per module version
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
| **SUB01** | MULTITENANCY | PRD01-SUB01-MULTITENANCY.md |
| **SUB02** | AUTHENTICATION | PRD01-SUB02-AUTHENTICATION.md |
| **SUB03** | AUDIT LOGGING | PRD01-SUB03-AUDIT-LOGGING.md |
| **SUB04** | SERIAL NUMBERING | PRD01-SUB04-SERIAL-NUMBERING.md |
| **SUB05** | SETTINGS MANAGEMENT | PRD01-SUB05-SETTINGS-MANAGEMENT.md |
| **SUB06** | UNIT OF MEASURE (UOM) | PRD01-SUB06-UOM.md |
| **SUB07** | CHART OF ACCOUNTS (COA) | PRD01-SUB07-CHART-OF-ACCOUNTS.md |
| **SUB08** | GENERAL LEDGER (GL) | PRD01-SUB08-GENERAL-LEDGER.md |
| **SUB09** | JOURNAL ENTRIES (JE) | PRD01-SUB09-JOURNAL-ENTRIES.md |
| **SUB10** | BANKING | PRD01-SUB10-BANKING.md |
| **SUB11** | ACCOUNTS PAYABLE (AP) | PRD01-SUB11-ACCOUNTS-PAYABLE.md |
| **SUB12** | ACCOUNTS RECEIVABLE (AR) | PRD01-SUB12-ACCOUNTS-RECEIVABLE.md |
| **SUB13** | HUMAN CAPITAL MGMT (HCM) | PRD01-SUB13-HCM.md |
| **SUB14** | INVENTORY MANAGEMENT | PRD01-SUB14-INVENTORY-MANAGEMENT.md |
| **SUB15** | BACKOFFICE | PRD01-SUB15-BACKOFFICE.md |
| **SUB16** | PURCHASING | PRD01-SUB16-PURCHASING.md |
| **SUB17** | SALES | PRD01-SUB17-SALES.md |
| **SUB18** | MASTER DATA MGMT (MDM) | PRD01-SUB18-MASTER-DATA-MANAGEMENT.md |
| **SUB19** | TAXATION | PRD01-SUB19-TAXATION.md |
| **SUB20** | FINANCIAL REPORTING (FR) | PRD01-SUB20-FINANCIAL-REPORTING.md |
| **SUB21** | WORKFLOW ENGINE (WF) | PRD01-SUB21-WORKFLOW-ENGINE.md |
| **SUB22** | NOTIFICATIONS & EVENTS | PRD01-SUB22-NOTIFICATIONS-EVENTS.md |
| **SUB23** | API GATEWAY & DOCS | PRD01-SUB23-API-GATEWAY-AND-DOCUMENTATION.md |
| **SUB24** | INTEGRATION CONNECTORS | PRD01-SUB24-INTEGRATION-CONNECTORS.md |
| **SUB25** | LOCALIZATION | PRD01-SUB25-LOCALIZATION.md |

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

This section details the specific requirements for each module, now organized in a traceabile table structure.

### **PRD01-SUB01: MULTITENANCY**

SQL Database \- Core

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| FR-MT-001 | Implement a **Tenant Model** to represent isolated entities. | FR | Planned | TBD | N/A |
| FR-MT-002 | Ensure strict **Tenant Data Isolation** by scoping all database and cache operations per tenant. | FR | Planned | TBD | N/A |
| FR-MT-003 | Establish a **Tenant Context middleware** to resolve and inject active tenant context. | FR | Planned | TBD | N/A |
| FR-MT-006 | Allow **Tenant Impersonation** for administrative support under strict auditing control. | FR | Planned | TBD | N/A |
| SR-MT-001 | Prevent **cross-tenant data exposure**. | SR | Planned | TBD | N/A |
| SR-MT-003 | **Encrypt tenant-specific configurations** and secrets. | SR | Planned | TBD | N/A |
| PR-MT-001 | System must maintain sub-**100ms** average response time for tenant resolution and context loading. | PR | Planned | TBD | N/A |
| SCR-MT-001 | Multitenancy layer must **scale horizontally** to support thousands of concurrent tenants. | SCR | Planned | TBD | N/A |

### **PRD01-SUB02: AUTHENTICATION**

SQL Database \- Core

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| FR-AA-002 | Implement API Authentication using **token-based** access control (Laravel Sanctum). | FR | Planned | TBD | N/A |
| FR-AA-003 | Develop a **Role-Based Access Control (RBAC)** system. | FR | Planned | TBD | N/A |
| FR-AA-006 | Enforce **Password Security** through salted hashing using Argon2 or bcrypt. | FR | Planned | TBD | N/A |
| FR-AA-008 | Enable **Account Lockout** after repeated failed login attempts. | FR | Planned | TBD | N/A |
| SR-AA-001 | Ensure **Tenant-Scoped Authentication**. | SR | Planned | TBD | N/A |
| SR-AA-003 | Enforce **API Rate Limiting** on authentication endpoints. | SR | Planned | TBD | N/A |
| PR-AA-001 | Login and token validation operations must complete under **300ms** on average. | PR | Planned | TBD | N/A |

### **PRD01-SUB03: AUDIT LOGGING**

MongoDB/PostgreSQL JSONB \- High Volume Logs

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| FR-AL-001 | Capture **Activity Logs** for all CRUD operations with actor, timestamp, and context. | FR | Planned | TBD | N/A |
| FR-AL-004 | Attach **Data Context (before/after states)** for high-value transactional records. | FR | Planned | TBD | N/A |
| FR-AL-005 | Provide **Audit Export** capability. | FR | Planned | TBD | N/A |
| SR-AL-001 | Enforce **Tenant Isolation** on all log queries. | SR | Planned | TBD | N/A |
| SR-AL-002 | Optionally support **Log Immutability** through append-only storage. | SR | Planned | TBD | N/A |
| PR-AL-001 | Logging operations should not add more than **10% overhead** to request processing. | PR | Planned | TBD | N/A |
| ARCH-AL-001 | Use **document store (MongoDB) or JSONB** for flexible, append-only log schema. | ARCH | Planned | TBD | N/A |

### **PRD01-SUB04: SERIAL NUMBERING**

SQL Database \- Transactional

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| FR-SN-001 | Allow **Configurable Serial Number Patterns**. | FR | Planned | TBD | N/A |
| FR-SN-003 | Support **Reset Periods** (daily, monthly, yearly, or manual). | FR | Planned | TBD | N/A |
| SR-SN-001 | Prevent **Race Conditions** in concurrent serial generation through atomic locking. | SR | Planned | TBD | N/A |
| PR-SN-001 | Serial generation should complete under **50ms** with zero duplication. | PR | Planned | TBD | N/A |

### **PRD01-SUB05: SETTINGS MANAGEMENT**

SQL Database \+ Redis Cache Layer

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| FR-SM-001 | Organize Settings in a **Hierarchical Structure** (system, tenant, and module levels). | FR | Planned | TBD | N/A |
| FR-SM-004 | Provide **Default Values** for unset configuration parameters. | FR | Planned | TBD | N/A |
| SR-SM-001 | **Encrypt sensitive setting values** such as credentials or API keys. | SR | Planned | TBD | N/A |
| PR-SM-001 | **Cache frequently accessed settings** to reduce database queries. | PR | Planned | TBD | N/A |
| EV-SM-001 | **Emit Events** when settings are created, updated, or deleted. | EV | Planned | TBD | N/A |
| ARCH-SM-001 | Use **Redis/Memcached for caching** tenant configs for sub-100ms access. | ARCH | Planned | TBD | N/A |

### **PRD01-SUB06: UOM**

SQL Database \- Transactional

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| FR-UOM-003 | Manage **Conversion Factors** between units with precision up to six decimal places. | FR | Planned | TBD | N/A |
| FR-UOM-005 | Provide **Automatic Conversion** logic when transactions involve different UOMs. | FR | Planned | TBD | N/A |
| PR-UOM-001 | Maintain **rounding accuracy** and prevent cumulative conversion errors. | PR | Planned | TBD | N/A |

### **PRD01-SUB07: CHART OF ACCOUNTS**

SQL Database \- ACID Required

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| FR-COA-001 | Maintain a **hierarchical chart of accounts**. | FR | Planned | TBD | N/A |
| FR-COA-002 | Allow tagging accounts by type, category, and reporting group. | FR | Planned | TBD | N/A |
| BR-COA-001 | Prevent **deletion of accounts** that have associated transactions. | BR | Planned | TBD | N/A |
| PR-COA-001 | Loading and filtering of COA should complete within **200ms**. | PR | Planned | TBD | N/A |

### **PRD01-SUB08: GENERAL LEDGER**

SQL Database \- ACID Critical

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| FR-GL-001 | **Automatically post entries** from all submodules. | FR | Planned | TBD | N/A |
| FR-GL-002 | Support **multi-currency** transactions. | FR | Planned | TBD | N/A |
| BR-GL-001 | Ensure all journal entries are **balanced (debit \= credit)**. | BR | Planned | TBD | N/A |
| DR-GL-001 | Store **aggregated monthly balances** for high-performance reporting. | DR | Planned | TBD | N/A |
| PR-GL-001 | Posting 1000 journal entries should complete under **1 second**. | PR | Planned | TBD | N/A |
| ARCH-GL-001 | **ACID compliance** non-negotiable for financial data integrity. | ARCH | Planned | TBD | N/A |

### **PRD01-SUB09: JOURNAL ENTRIES**

SQL Database \- ACID Required

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| FR-JE-002 | Support **recurring journals, reversing entries, and templates**. | FR | Planned | TBD | N/A |
| BR-JE-001 | Only **authorized users** may post journals to the general ledger. | BR | Planned | TBD | N/A |
| PR-JE-001 | Approval and posting workflow must complete within **2 seconds** per entry. | PR | Planned | TBD | N/A |

### **PRD01-SUB10: BANKING**

SQL Database \- ACID Required

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| FR-BR-002 | Support **automated matching** of bank statements with AR/AP entries. | FR | Planned | TBD | N/A |
| SR-BR-001 | **Secure bank credentials** with encryption and access control. | SR | Planned | TBD | N/A |
| PR-BR-001 | Reconciliation engine should handle **10k+ transactions in under 5 seconds**. | PR | Planned | TBD | N/A |

### **PRD01-SUB11: ACCOUNTS PAYABLE**

SQL Database \- ACID Required

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| FR-AP-002 | **Auto-generate AP entries** from approved purchase orders. | FR | Planned | TBD | N/A |
| BR-AP-001 | Payment amounts must **not exceed the invoice total**. | BR | Planned | TBD | N/A |
| IR-AP-001 | Integrate with **banking module** for automated disbursements. | IR | Planned | TBD | N/A |
| PR-AP-001 | Process batch payments (1000 invoices) in under **5 seconds**. | PR | Planned | TBD | N/A |

### **PRD01-SUB12: ACCOUNTS RECEIVABLE**

SQL Database \- ACID Required

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| FR-AR-002 | **Auto-generate AR entries** from sales orders or delivery notes. | FR | Planned | TBD | N/A |
| IR-AR-001 | Integrate with **banking module** for reconciliation. | IR | Planned | TBD | N/A |
| PR-AR-001 | Generate and post receipts under **2 seconds** per transaction. | PR | Planned | TBD | N/A |

### **PRD01-SUB13: HCM**

SQL Database \- Transactional

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| FR-HCM-001 | Maintain employee master data including personal, job, and **payroll information**. | FR | Planned | TBD | N/A |
| BR-HCM-001 | Disallow **deletion of employee records** with existing payroll or leave history. | BR | Planned | TBD | N/A |
| PR-HCM-001 | Employee record retrieval must complete under **200ms**. | PR | Planned | TBD | N/A |

### **PRD01-SUB14: INVENTORY MANAGEMENT**

SQL Database \- Transactional

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| FR-INV-002 | Track stock quantities across **multiple warehouses and locations**. | FR | Planned | TBD | N/A |
| FR-INV-003 | Support stock movements including transfers, adjustments, and **costing**. | FR | Planned | TBD | N/A |
| BR-INV-001 | **Negative stock not allowed** unless explicitly enabled. | BR | Planned | TBD | N/A |
| DR-INV-001 | Maintain **stock ledger** for all movements for audit purposes. | DR | Planned | TBD | N/A |
| PR-INV-001 | Query current stock levels in under **100ms** for \<10k items. | PR | Planned | TBD | N/A |

### **PRD01-SUB15: BACKOFFICE**

SQL Database \- Transactional

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| FR-BO-002 | Manage **fiscal year creation, closing, and reopening**. | FR | Planned | TBD | N/A |
| FR-BO-003 | Provide lookup APIs for currencies, taxes, and document templates. | FR | Planned | TBD | N/A |
| BR-BO-001 | Only **one active fiscal year per tenant** is allowed at a time. | BR | Planned | TBD | N/A |
| SR-BO-001 | Only "**System Admin**" may modify fiscal year or company info. | SR | Planned | TBD | N/A |

### **PRD01-SUB16: PURCHASING**

SQL Database \- Transactional

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| FR-PO-001 | Support **multi-level purchase approval** and supplier evaluation. | FR | Planned | TBD | N/A |
| BR-PO-001 | POs above threshold require multi-level approval. | BR | Planned | TBD | N/A |
| IR-PO-001 | Integrate with **AP for invoice matching**. | IR | Planned | TBD | N/A |
| IR-SCM-001 | Integrate with **inventory module** for stock receipt updates. | IR | Planned | TBD | N/A |
| PR-SCM-001 | Approve and process a PO under **1 second**. | PR | Planned | TBD | N/A |

### **PRD01-SUB17: SALES**

SQL Database \- Transactional

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| FR-SD-002 | Support order approval, **fulfillment**, and invoicing workflows. | FR | Planned | TBD | N/A |
| BR-SD-001 | Orders cannot exceed **customer credit limit**. | BR | Planned | TBD | N/A |
| IR-SD-001 | Integrate with **inventory for stock reservation** and issue. | IR | Planned | TBD | N/A |
| PR-SD-001 | Process an order (including stock allocation) in **\<2 seconds** for \<100 items. | PR | Planned | TBD | N/A |

### **PRD01-SUB18: MASTER DATA MANAGEMENT**

SQL Database \+ Analytics Layer

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| FR-REP-001 | Provide **real-time reporting API** for all transactional modules. | FR | Planned | TBD | N/A |
| PR-REP-001 | Dashboard queries must return **\<3 seconds** for datasets \<10k rows. | PR | Planned | TBD | N/A |
| ARCH-MDM-001 | Use **PostgreSQL Materialized Views or ClickHouse** for analytics offload. | ARCH | Planned | TBD | N/A |

### **PRD01-SUB19: TAXATION**

SQL Database \- Transactional

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| FR-BI-001 | Aggregate data from all modules for trend and historical analysis. | FR | Planned | TBD | N/A |
| FR-BI-002 | Provide ETL pipelines for structured and unstructured data sources. | FR | Planned | TBD | N/A |
| SCR-BI-001 | Support **horizontal scaling** for large datasets. | SCR | Planned | TBD | N/A |

### **PRD01-SUB20: FINANCIAL REPORTING**

SQL Database \+ Data Warehouse

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| FR-COMP-001 | Support configurable **compliance policies** (SOX, ISO, etc.). | FR | Planned | TBD | N/A |
| CR-COMP-001 | Maintain **complete audit trails** of financial and user operations. | CR | Planned | TBD | N/A |
| ARCH-FR-001 | Use **PostgreSQL JSONB/Materialized Views or dedicated Data Warehouse**. | ARCH | Planned | TBD | N/A |

### **PRD01-SUB21: WORKFLOW ENGINE**

SQL Database \+ Redis Queue

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| FR-NW-002 | Support configurable **approval and escalation workflows**. | FR | Planned | TBD | N/A |
| IR-NW-001 | Integrate with all transactional modules via **event bus**. | IR | Planned | TBD | N/A |
| ARCH-WF-001 | Workflow definitions in **SQL**; execution via **Redis Queue** for async processing. | ARCH | Planned | TBD | N/A |

### **PRD01-SUB22: NOTIFICATIONS & EVENTS**

Redis Queue \+ Laravel Horizon

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| FR-NW-001 | Deliver event-driven notifications via **email, webhooks, and system feeds**. | FR | Planned | TBD | N/A |
| PR-NW-001 | Deliver notifications within **3 seconds** of triggering event. | PR | Planned | TBD | N/A |
| ARCH-NE-001 | Use **Redis (Laravel Queue/Horizon)** for asynchronous message processing. | ARCH | Planned | TBD | N/A |

### **PRD01-SUB23: API GATEWAY & DOCS**

SQL Database \- Core

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| FR-INT-001 | Provide **unified API gateway** for external system integrations. | FR | Planned | TBD | N/A |
| SR-INT-001 | **Authenticate and throttle** all integrations securely. | SR | Planned | TBD | N/A |
| SCR-INT-001 | Allow **dynamic addition of new connectors** without downtime. | SCR | Planned | TBD | N/A |

### **PRD01-SUB24: INTEGRATION CONNECTORS**

SQL Database \+ Event Streams

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| FR-DOC-001 | Manage document uploads, **versioning**, and linking to transactions. | FR | Planned | TBD | N/A |
| CR-DOC-001 | Comply with regional data privacy and **retention laws**. | CR | Planned | TBD | N/A |
| ARCH-IC-001 | Connector configs in **SQL**; event-driven sync via **Redis/Kafka**. | ARCH | Planned | TBD | N/A |

### **PRD01-SUB25: LOCALIZATION**

SQL Database \+ Redis Cache

| Requirement Codes | Description | Classification | Progress | Date | Issue \# |
| :---- | :---- | :---- | :---- | :---- | :---- |
| FR-LOC-001 | Support **multi-language user interfaces**. | FR | Planned | TBD | N/A |
| FR-LOC-002 | Support **multi-currency transactions** and exchange rate management. | FR | Planned | TBD | N/A |
| BR-LOC-001 | Currency exchange rates must be **updated daily**. | BR | Planned | TBD | N/A |
| PR-LOC-001 | Language switching should complete within **200ms**. | PR | Planned | TBD | N/A |

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

