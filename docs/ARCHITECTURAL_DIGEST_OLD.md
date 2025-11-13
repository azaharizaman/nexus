# Nexus ERP: Refactoring and Architectural Guidance

**Version:** DRAFT 1.0  
**Status:** Under Review  
**Date of Approval:** TBD

---

## Executive Summary and Document Context

| **Field** | **Description** |
|-----------|-----------------|
| **TL;DR** | Nexus ERP is being refactored from a monolithic application into a collection of small, independent (atomic) Laravel packages. This ensures **Maximum Atomicity**, making the ERP infinitely scalable, extensible, and reusable. The main Nexus ERP application will only serve as a lean Orchestrator and API Presentation Layer. |
| **Purpose** | To serve as the single, authoritative architectural blueprint defining the boundaries, naming conventions, communication protocols (Contracts & Events), and decomposition of all packages within the Nexus ERP project. |
| **Pain Points Addressed** | Monolithic architecture complexity, circular dependencies, difficulty in scaling individual components, and high cost/risk when modifying core business logic. |
| **Motivation** | To cut the 6-12 month slug of building a complex ERP by providing a reusable, atomic foundation, fulfilling the vision: *"What if the future of Enterprise Software is not to be bought but to be built."* |
| **Intended Audience** | The Architectural Review Board (ARB), Core Nexus ERP Developers, Package Developers, and System Integrators building on the Nexus platform. |

---

## Table of Contents

1. [Introduction and Architectural Mandate](#1-introduction-and-architectural-mandate)
2. [Defining Architectural Boundaries](#2-defining-architectural-boundaries)
3. [Standardized Package Naming Conventions](#3-standardized-package-naming-conventions-architectural-governance)
4. [The "Where Does This Go?" Decision Guide](#4-the-where-does-this-go-decision-guide-refactoring-checklist)
5. [Technical Refactoring Directives](#5-technical-refactoring-directives)
6. [Architectural Decomposition: Atomic Package Brainstorm](#6-architectural-decomposition-atomic-package-brainstorm)
7. [Conclusion and Future Outlook](#7-conclusion-and-future-outlook)

1. Introduction and Architectural Mandate

The Nexus ERP project is built on the philosophy of Maximum Atomicity. Our goal is to ensure that the core business logic is encapsulated in small, highly focused, and reusable Laravel packages that can theoretically function independently of the main Nexus ERP application. Nexus ERP aims to cut the initial slug for any developer or organization that wants to build ERP but can’t afford the 6-12 months slug. The motto of Nexus ERP is :

” What if the future of Enterprise Software is not to be bought but to be built.”

This document serves as the guide for all new feature development and the ongoing refactoring effort. The primary objective is to define a clear architectural boundary between Atomic Packages and the Nexus ERP Core.

Core Goal

The Atomic Rule: All business logic that governs a single, independent domain of the ERP (e.g., UOMs, Serial Numbers, Currencies) MUST reside in its own package. The Nexus ERP Core is responsible only for Orchestration, Configuration, and API Presentation.

2. Defining Architectural Boundaries

When developing a new feature or refactoring existing code, the first question must be: "Is this core domain logic, or is this integration/presentation?"

A. The Atomic Package (The "Service Layer")

Packages are self-contained Laravel applications/services that are headless by design (no blade/frontend logic).

Responsibility

Description

Example Code

Domain Logic

All models, migrations, business rules, calculations, and validators for a single domain.

UOM conversion algorithms, Serial Number voiding logic.

Data Persistence

Handling all CRUD operations for the package's specific models.

UomRepository, SerialTracker model.

API Endpoints (Internal)

Packages MUST define their own API routes, but these are for internal use only (e.g., for local testing or package-internal calls) and are not the public-facing ERP API.

GET /api/uom-management/uoms

Isolation

A package MUST NOT be aware of the existence of other atomic packages.

nexus-uom-management cannot directly call a class from nexus-accounting.

How Packages Communicate:
Packages must communicate via Contracts (Interfaces) and Events. If Package A needs to use Package B, Package A defines a PHP Interface (Contract) for the service it needs, and the Nexus ERP Core binds the concrete implementation from Package B to that interface.

B. Nexus ERP Core (The "Orchestrator")

The main Nexus ERP application is the API Presentation Layer and the Service Orchestrator. It is itself a true headless applications that only communicate to the external world via API, GraphQL and WebSockets. It contains the bare minimum code necessary to make the system function as a unified ERP to the users of this ERP. The main users are system integrators, developes or machine to machines, rarely the end users as the “Head” part needs to be developed by other developer before it can be made public.

Responsibility

Description

Example Code

Public API Routes

Defines the external, unified ERP API endpoints that consumers (frontends) will use. Includes API Documentation (OpenAPI/Swagger).

GET /api/v1/purchase-orders

Orchestration

Logic that requires coordinating data or actions across two or more atomic packages.

Creating a Purchase Order that requires models from nexus-accounting and serial numbers from nexus-model-serialization.

Service Container Binding

The entire AppServiceProvider and configuration logic that binds the concrete package implementations to the contracts defined by other packages.

\App\Providers\AppServiceProvider logic.

High-Level Configuration

Configuration that affects the application as a whole (e.g., middleware stacking, global rate limiting, CORS). Includes setup for Laravel first-party packages like Sanctum (API Auth) and Reverb/Echo (WebSockets).

config/app.php overrides.

3. Standardized Package Naming Conventions (Architectural Governance)

The package suffix dictates its primary responsibility, data volatility, and architectural role. Developers MUST adhere to this classification when creating or refactoring packages.

Suffix

Architectural Role

Responsibility

Data Type / Volatility

Examples

-master

Master Data Definition (The "What")

Defines, stores, and validates the static core reference data for a domain.

Low Volatility (Reference data), High Reusability.

nexus-item-master, nexus-employee-master

-management

Transactional State & Rules (The "How")

Manages state changes, applies complex business rules, and persists transactional data within a domain.

High Volatility (Transactional records), State Persistence.

nexus-uom-management, nexus-inventory-management, nexus-workflow-management

-interface

External Abstraction (The "Boundary")

Provides a stable façade (Contract) for interacting with complex internal logic or external systems. Hides underlying implementation complexity.

Medium Volatility (Swappable implementation logic).

nexus-ledger-interface, nexus-payment-interface

-engine

Stateless Execution (The "Calculation")

Executes pure, computational logic, algorithms, or rule-sets based on inputs, without maintaining persistence for the execution itself.

Stateless, High Performance/Computation.

nexus-workflow-engine, nexus-reporting-engine

4. The "Where Does This Go?" Decision Guide (Refactoring Checklist)

Use this checklist for every new file or feature.

#

Question

Decision Path

Location

1.

Is this logic exclusively about a single domain (e.g., only UOMs, only Currencies, only User Permissions)?

Yes, it is atomic.

The Atomic Package

2.

Does this logic need to call, reference, or be aware of a class or model from another Nexus atomic package?

Yes, it requires cross-package knowledge (Coordination).

Nexus ERP Core (Orchestration Layer)

3.

Is this code a public-facing endpoint for a client application to consume (e.g., part of the v1 API)?

Yes, this is presentation.

Nexus ERP Core (Routes)

4.

Is this an Interface/Contract that defines how a specific service should be consumed?

Yes, this promotes decoupling.

The Atomic Package that consumes the service, OR a very thin nexus-contracts package.

5.

Is this code responsible for registering a package's service provider or setting up global environment variables?

Yes, this is bootstrapping.

Nexus ERP Core (Service Providers)

Example Scenarios:

Scenario

Location

Rationale

Calculating volume conversion (Liters to Gallons).

nexus-uom-management

Pure, atomic domain logic.

Defining the structure of the PurchaseOrder model.

nexus-accounting

Atomic domain logic for the accounting module.

The API endpoint POST /api/v1/po/create which takes a PurchaseOrder request, validates the UOMs using the UOM service, and requests a serial number for the PO from the Serialization service.

Nexus ERP Core

This orchestrates two different packages (accounting and serialization).

The logic for checking if a serial number has been voided.

nexus-model-serialization

Pure, atomic domain logic.

5. Technical Refactoring Directives

A. Dependency Management

NEVER directly reference another package's concrete class.

Bad: new \Nexus\UomManagement\Services\UomConverter();

Good: app(\Nexus\Contracts\UomConverterInterface::class)->convert(...)

The binding of UomConverterInterface to \Nexus\UomManagement\Services\UomConverter is handled centrally in the Nexus ERP Core's service providers.

B. Event-Driven Architecture (EDA)

For reactive updates between packages, use Laravel Events to ensure packages remain unaware of their consumers.

Action

Location

Mechanism

Triggering an action

Atomic Package

Dispatch a Domain Event (e.g., UomUpdated::dispatch($uom)).

Reacting to an action

Nexus ERP Core

Define a Listener in the Core's EventServiceProvider that executes cross-package orchestration logic.

Example: When a Serial Number is voided in nexus-model-serialization, it dispatches SerialNumberVoided. The Nexus ERP Core listens for this event and then calls the updateStatus method on the relevant PurchaseOrder via the accounting service. The serialization package never knew what consumed the event.

C. Laravel Version and Headless Focus

Strict Headless: Ensure no Blade views, sessions, or typical frontend scaffolding exists in any atomic package or the Nexus ERP Core. Everything must be API-driven (JSON responses).

Laravel 12 Standard: All package structures must adhere strictly to the Laravel 12 package development best practices.

Monorepo Tools: We will continue to leverage the monorepo structure for parallel development and unified testing, but adherence to the atomic principles is the priority.

6. Architectural Decomposition: Atomic Package Brainstorm

A. Architectural & Decoupling Core (The System Foundation)

These packages manage the environment, security boundaries, and communication structure for the entire application.

Package Name

Domain Responsibility

nexus-contracts

Decoupling Layer: Holds all PHP Interfaces (Contracts) used for inter-package communication, ensuring no package references another's concrete classes.

nexus-tenancy-management

Multi-Tenancy: Manages the definition, configuration, and runtime isolation of data and resources for separate tenants/companies.

nexus-identity-management

User, Role, and Permission management (Authentication, Authorization, RBAC).

B. Fundamental Master Data & Management Core (The ERP Constants)

These packages manage system constants and configuration that all other business modules depend on.

Package Name

Domain Responsibility

nexus-organization-master

Defines the organizational structure, including Offices, Departments, Teams, Units, and Staffing Hierarchy.

nexus-fiscal-calendar-master

Defines static master data for Fiscal Years, Periods, and statutory holidays.

nexus-sequencing-management

Controls the generation, persistence, and validation of all document numbering sequences (e.g., PO-0001, Invoice-0002).

nexus-tax-management

Centralized tax codes, rates, rules, and jurisdiction handling.

nexus-currency-management

Currency codes, exchange rates, conversion rates and history.

nexus-uom-management

Unit of Measure (UOM) codes, conversion logic, precision, and packaging rules.

nexus-reporting-engine

Financial report generation, report templates, and data aggregation logic.

C. Core Financial & Operational Packages (The Business Engine)

These packages manage the primary domain models and transactional processes that define the business.

Package Name

Domain Responsibility

nexus-party-management

Management of all "Parties" (Customers, Vendors, Employees, Contacts, Legal Entities).

nexus-employee-master

Core HR master data (Job Titles, Departments, Reporting Hierarchy, basic Employee Profile). Relies on nexus-organization-master.

nexus-item-master

Single source of truth for all Products, Services, Price Lists, and Catalogs.

nexus-accounts-payable-management

Manages the full lifecycle of Vendor Invoices, Payment Authorizations, and reconciliation against the Vendor Ledger.

nexus-accounts-receivable-management

Manages the full lifecycle of Customer Invoices, Receipts/Collections, and reconciliation against the Customer Ledger.

nexus-cash-and-bank-management

Manages all Bank Transaction Logs, Cash Accounts, Bank Reconciliation processes, and transactional fund transfers.

nexus-ledger-interface

Core double-entry accounting engine (General Ledger, Chart of Accounts, Journal Entries).

nexus-payment-interface

Bank account master, payment method processing, and transaction execution/reconciliation.

D. Universal Supporting Packages (Good to Have for General ERP)

These packages provide critical cross-cutting capabilities but are not strictly necessary for the initial launch of basic financial modules.

Package Name

Domain Responsibility

nexus-ai-automation-management

AI/ML Inference and Orchestration: Provides standardized contracts and services for AI/ML inference (e.g., document classification, data extraction, demand prediction, sentiment analysis). Handles external API calls, model response normalization, and cost/usage tracking.

nexus-audit-log

Cross-cutting Logging: Provides standardized, transactional tracking of model changes (who, what, when, field changes). All packages will be configured to use this service.

nexus-model-serialization

Unique serial number generation, versioning, status tracking, and voiding (as described in the prompt).

nexus-workflow-engine

Execution Logic: The pure, stateless computation of state transitions, rule evaluation, approval logic, and escalation triggers.

nexus-workflow-management

State Persistence: Tracking the status, history, users, and instance data of all running workflows (e.g., preventing duplicate workflows for a given document).

nexus-document-management

Secure storage, version control, and access control for attachments and documents.

nexus-notification-service

Centralized queueing and dispatching of notifications (Email, SMS, internal alerts, WebSockets).

nexus-settings-management

Key-value store for global application, tenant, or user-specific settings.

E. Universal Commerce & Operations Packages (Strongly Recommended)

These packages manage fundamental business transactions and resources (inventory, assets, time) that are critical for nearly every industry, even if the specific implementation details (e.g., manufacturing vs. trading) differ slightly in the Core Orchestrator.

Package Name

Domain Responsibility

Applicable Industries

nexus-purchase-order-management

Purchase requests, purchase orders, vendor bill reconciliation.

All (General, Manufacturing, Maintenance, Fleet, Legal, Medical, Gov)

nexus-sales-order-management

Sales quotes, sales orders, invoicing, returns, and fulfillment coordination.

All (General, Manufacturing, Legal, Maintenance, Medical)

nexus-inventory-management

Stock levels, basic costing (FIFO/LIFO/Average), material movements, stocktake.

General, Manufacturing, Warehouse, Fleet (Spares/Fuel), Medical (Supplies)

nexus-asset-management

Tracking internal (or customer-owned) fixed assets, warranty, depreciation, and service history.

Manufacturing, Maintenance, Fleet, Government, Legal (IT/Equipment)

nexus-time-activity-management

Highly granular time entry tracking (e.g., for employee time or client billing), activity logs, and expense association.

General Service, Manufacturing (Labor), Legal, Maintenance, Medical

F. Industry-Specific Packages

These packages are necessary for the vertical functionality required by a specific industry. The original "General Service/Trading" modules has been absorbed into 5C.

| Industry | Criticality | Package Name | Core Responsibility |
| ----- | ----- | ----- |
| 1. Manufacturing | Must Have | nexus-bill-of-materials-management | Product recipes, multi-level BOMs, component consumption tracking. |
|  | Must Have | nexus-work-order-management | Job tickets, production execution, actual vs. standard cost tracking. |
|  | Good to Have | nexus-production-scheduling-management | Capacity planning and sequencing of work orders across resources. |
|  | Good to Have | nexus-quality-control-management | Non-conformance reporting, inspection plans, test results tracking. |
| 2. Finance Service Provider | Must Have | nexus-loan-management | Loan application, disbursement, repayment schedules, and interest calculations. |
|  | Must Have | nexus-customer-onboarding-management | KYC (Know Your Customer) data collection, verification, and AML checks. |
|  | Good to Have | nexus-compliance-reporting-engine | Regulatory report generation (computation/output). |
| 3. Legal Service Provider | Must Have | nexus-case-file-management | Case status, matter documents, opposing parties, court dates. |
|  | Good to Have | nexus-client-trust-accounting-management | Managing funds held on behalf of clients (segregated accounting). |
| 4. Maintenance Service Provider | Must Have | nexus-service-call-scheduling-management | Dispatching technicians, tracking service history, job completion status. |
|  | Good to Have | nexus-preventive-maintenance-management | Defining and triggering planned maintenance schedules. |
| 5. Government-Public Service | Must Have | nexus-budget-management | Fund accounting, tracking expenditures against approved budgets. |
|  | Must Have | nexus-policy-management | Storing and referencing internal and external policy documents and rules. |
|  | Good to Have | nexus-citizen-portal-integration | Handling service requests and feedback from the public. |
| 6. Fleet Management Services | Must Have | nexus-vehicle-telematics-management | Ingesting and processing GPS, fuel usage, and sensor data from vehicles. |
|  | Must Have | nexus-maintenance-scheduling-management | Tracking vehicle repair history and scheduling planned servicing. |
|  | Good to Have | nexus-route-optimization-engine | Logic for optimizing delivery or service routes (pure computation). |
| 7. Warehouse Services | Must Have | nexus-inventory-location-management | Managing bins, aisles, zones, and tracking inventory by exact location (WMS). |
|  | Must Have | nexus-fulfillment-management | Managing and validating fulfillment processes (wave planning, picking lists). |
|  | Good to Have | nexus-barcode-validation-engine | Logic for validating inventory movements via scanned barcodes (pure computation). |
| 8. General Practitioner Medical | Must Have | nexus-patient-record-management | Storing structured medical history, diagnoses, and treatment plans (EMR). |
|  | Must Have | nexus-appointment-scheduling-management | Booking, managing, and notifying patients and practitioners of appointments. |
|  | Good to Have | nexus-billing-claim-management | Generating and tracking insurance claims and patient bills. |
| 9. General Service/Trading | Good to Have | nexus-commission-management | Sales commission rules and payout calculations. |

7. Conclusion and Future Outlook

Assumptions

Strict Monorepo Adherence: Development and testing will be contained within a single monorepo structure utilizing package discovery and management tools.

Contracts First: All cross-package functionality will be implemented and consumed via PHP Contracts (nexus-contracts) before any concrete implementation is written.

Laravel Ecosystem: The Nexus ERP Core will exclusively use Laravel's standard features and first-party packages (e.g., Sanctum, Reverb) for API and communication tooling.

Database Isolation (Tenancy): The nexus-tenancy-management package will enforce logical data isolation for tenants, allowing for either single-database or multi-database tenancy configurations.

Risks & Mitigation Plan

Risk

Impact

Mitigation Plan

Architectural Drift

Packages start referencing concrete classes, leading to a "distributed monolith" and loss of atomicity.

Mandatory Code Reviews: All pull requests involving cross-package communication must be blocked unless Contracts and Events are utilized. Automated static analysis tools will check for forbidden dependencies.

Performance Overhead

Excessive use of Events and Service Container resolution may introduce latency in high-volume transactions.

Asynchronous Processing: Critical updates and non-time-sensitive coordination must be moved to queues (Listeners) to decouple execution and ensure low latency for user-facing API calls.

Dependency Hell

Over-reliance on internal packages for minor features.

Minimum Atomicity Threshold: Enforce a rule that a package must contain at least 5 core models or 500 lines of unique business logic to justify its existence; otherwise, its logic belongs in the Core Orchestrator or an existing package.

Extendability and Scalability

Horizontal Scalability: The Atomic Package design ensures that individual domains (e.g., nexus-inventory-management) can be conceptually isolated and even deployed as standalone microservices if volume demands it, simply by moving its database and API routes out of the Core Orchestrator's scope.

Easy Extension: New industry-specific packages (Section 6F) can be added by simply creating the new package and registering its Service Provider in the Nexus ERP Core, without modifying any existing business logic.

Future Innovation Path

AI Integration Expansion: Fully leverage the nexus-ai-automation-management package to move beyond simple data extraction into advanced predictive services (e.g., automated cash flow forecasting, proactive maintenance scheduling).

Event Stream Processing: Implement a dedicated package (e.g., nexus-event-streamer) for publishing key domain events (like PO Completed, Invoice Paid) to an external stream platform (Kafka/AWS Kinesis) for real-time analytics or integration with third-party tools.

Dynamic UI Generation: Investigate creating a separate package that consumes the API routes and model schemas defined in the atomic packages to dynamically generate frontend UIs, further streamlining the effort required for system integrators to build their head application.

Date of Approval: TBD
Version: DRAFT 1.0