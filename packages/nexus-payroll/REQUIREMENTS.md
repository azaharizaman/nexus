nexus-payroll Package Requirements
Version: 1.0.0 Last Updated: November 15, 2025 Status: Architecture Design - Progressive Disclosure Model

Executive Summary
nexus-payroll is a progressive, headless payroll engine for PHP/Laravel that scales from a one-line monthly salary payment to a full statutory-compliant, multi-country payroll system without ever breaking existing code.
The Problem We Solve
Most payroll solutions force you to pick:
	•	Simple packages → only basic salary, collapse under statutory rules
	•	Enterprise payroll (SQL Payroll, Talenox, PayrollPanda, SAP) → expensive, bloated, locked-in UI
nexus-payroll solves both with atomic progressive disclosure:
	1	Level 1: Basic Salary (5 minutes) – Trait on Employee, one processPayroll() call, posts to nexus-accounting
	2	Level 2: Payslip Engine (1–3 days) – Database-driven components (allowances, deductions, overtime, claims, bonuses, tax, contributions)
	3	Level 3: Statutory Compliance (Production-ready) – Full Malaysia PCB/EPF/SOCSO/EIS/MTD/EA/CP39/E-Filing, retroactive recalculation, multi-frequency (monthly/semi-monthly/weekly), government API submissions
Core Philosophy
	1	Progressive Disclosure – Start simple, grow without refactoring
	2	100% Backwards Compatible – Level 1 code works forever after promotion
	3	Fully Headless – Pure API, no UI
	4	Framework-Agnostic Core – Zero Laravel in calculation engine
	5	Nexus ERP Native – Deep integration with nexus-hrm, nexus-accounting, nexus-workflow, nexus-tenancy, nexus-audit-log
	6	Statutory-First Design – Malaysia compliance is built-in and up-to-date by default
Why This Approach Wins
Mass Market (80%):
	•	Fastest payroll setup in Laravel ecosystem
	•	Zero-migration upgrade path
	•	Works instantly with nexus-hrm employees
Enterprise/Statutory (20%):
	•	Accurate PCB calculation with YA2025–2027 tables embedded
	•	Retroactive pay runs that automatically adjust past tax
	•	Full audit trail of every calculation
	•	Direct LHDN/EPF/KWSP/SOCSO API submission endpoints
	•	Multi-company, multi-currency, multi-frequency

Personas & User Stories
Personas
ID
Persona
Role
Primary Goal
P1
Small Business Developer
Laravel dev
“Pay 10 staff monthly with basic salary in one command”
P2
Growing Company Dev/HR
Backend + HR
“Handle allowances, overtime, claims, EPF/SOCSO accurately”
P3
Payroll Administrator
Finance/HR user
“Run payroll, generate payslips, submit EA/CP39 with zero errors”
P4
Compliance Manager
Finance/Legal
“Ensure 100% LHDN compliance and audit-ready records”
P5
Employee
Staff
“View my payslip, YTD figures, tax relief claims”
User Stories
Level 1: Basic Salary
ID
Persona
Story
Priority
US-001
P1
As a dev, I want to add HasPayroll trait and pay salary with one method call
High
US-002
P1
As a dev, I want automatic GL postings to nexus-accounting on pay run
High
Level 2: Payslip Engine
ID
Persona
Story
Priority
US-010
P2
As a payroll admin, I want recurring earnings/deductions (allowances, commissions, fixed deductions)
High
US-011
P2
As a payroll admin, I want variable items (overtime, claims, bonuses, unpaid leave) pulled from nexus-hrm
High
US-012
P2
As a payroll admin, I want payslip PDF generation with company branding
High
US-013
P3
As an employee, I want secure payslip portal/view API
High
Level 3: Statutory Compliance
| ID | Persona | Story | Priority | |–––––| | US-020 | P3 | As payroll admin, I want accurate PCB calculation with all reliefs (individual, spouse, children, etc.) | High | | US-021 | P3 | As payroll admin, I want auto-calculation of EPF, SOCSO, EIS, HRDF, Zakat | High | | US-022 | P3 | As payroll admin, I want retroactive payroll runs that adjust previous months tax correctly | High | | US-023 | P4 | As compliance manager, I want one-click generation of EA Form, CP39, Borang E, PCB Audit File | High | | US-024 | P3 | As payroll admin, I want direct submission endpoints for LHDN e-Filing, KWSP i-Akaun, SOCSO ASSIST | High | | US-025 | P3 | As payroll admin, I want multi-frequency payroll (monthly + semi-monthly + commission runs) | High |

Functional Requirements
FR-L1: Level 1 - Basic Salary
ID
Requirement
Priority
Acceptance Criteria
FR-L1-001
HasPayroll trait on Employee model
High
One-line pay run, posts salary expense + liability
FR-L1-002
Basic pay run command (php artisan payroll:run)
High
Processes all active employees for selected period
FR-L2: Level 2 - Payslip Engine
ID
Requirement
Priority
Acceptance Criteria
FR-L2-001
Recurring payroll components (fixed allowance, deduction, overtime rate)
High
Stored per employee or global
FR-L2-002
Variable payroll items (claims, bonuses, commission, unpaid leave)
High
Pull from nexus-hrm approved records
FR-L2-003
YTD tracking for all components
High
Accurate cumulative figures
FR-L2-004
Payslip generation (PDF + JSON) with customizable template
High
Branding, multi-language support
FR-L2-005
Pay run locking & rollback
High
Prevent duplicate runs, full rollback if failed
FR-L2-006
Integration with nexus-workflow for claims/OT approval
High
Only approved items included
FR-L3: Level 3 - Statutory Compliance
ID
Requirement
Priority
Acceptance Criteria
FR-L3-001
Malaysia PCB calculation engine (YA2025–2027 tables embedded)
High
Handles all relief types, additional remuneration, retro pay
FR-L3-002
EPF, SOCSO, EIS, HRDF, Zakat auto-calculation
High
Age/category-based rates, ceilings, employer/employee share
FR-L3-003
Retroactive recalculation with tax adjustment
High
Adjust past payslips and generate CP159/CP22A
FR-L3-004
Statutory report generation: EA Form, E Form, CP39, CP8D, Borang TP1/TP3 etc.
High
Ready for submission format
FR-L3-005
Direct API submission endpoints (LHDN e-Filing, KWSP, SOCSO)
High
OAuth-ready, returns submission status
FR-L3-006
Multi-frequency payroll runs (monthly, semi-monthly, weekly, bonus)
High
Separate runs, correct proration
FR-L3-007
Multi-currency & foreign worker levy (FWL) handling
High
Auto-conversion + levy calculation
FR-L3-008
Tax relief claims module (medical, education, lifestyle)
High
Employee self-declare or HR input
FR-EXT: Extensibility
ID
Requirement
Priority
Acceptance Criteria
FR-EXT-001
Plugin interface for custom tax engines (SG, UAE, ID)
High
Implement TaxCalculatorContract
FR-EXT-002
Custom payroll component types
High
Earnings, deductions, employer contributions
FR-EXT-003
Custom statutory report formats
High
Return streamable file via strategy

Non-Functional Requirements
Performance
ID
Requirement
Target
PR-001
Pay run 5,000 employees
< 15 seconds
PR-002
Payslip PDF generation (single)
< 2 seconds
PR-003
Retro recalc 12 months, 1,000 emp
< 30 seconds
Security & Compliance
ID
Requirement
Scope
SR-001
All payroll data encrypted at rest
Mandatory
SR-002
Immutable payslip records
Audit log
SR-003
Role-based access (payroll:run, view)
Gates
SR-004
Statutory table updates via composer
YA changes
Reliability
ID
Requirement
Notes
REL-001
Pay runs atomic & transactional
Full rollback on failure
REL-002
Exact PCB rounding as per LHDN
Zero sen discrepancy

Data Requirements (Key Tables)
Table
Purpose
Key Fields
payroll_runs
Pay run header
id, period, frequency, status, locked_at
payroll_items
All earnings/deductions per run
run_id, employee_id, type, amount, ytd
payroll_statutory_rates
Embedded YA tables & rates
year, type, brackets, rates
payroll_reliefs
Employee tax relief claims
employee_id, year, type, amount
payroll_payslips
Generated payslip records
run_id, employee_id, pdf_path, hash
payroll_submissions
Track government submissions
type (EA/CP39), year/month, status, file

Dependencies
Required
	•	PHP ≥ 8.2
	•	nexus-hrm ≥ 1.0
	•	nexus-accounting
	•	nexus-workflow
	•	nexus-tenancy
	•	nexus-audit-log
	•	nexus-settings
Optional
	•	nexus-analytics (payroll analytics)
	•	queue driver (for large runs)

Done. nexus-payroll requirements document delivered. Ready for your review or next package.
