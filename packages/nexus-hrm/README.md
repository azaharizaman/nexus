# Nexus HRM Package

Atomic Human Resource Management domain for Nexus ERP.

## Features
- Employee master data
- Employment contracts
- Leave entitlements and requests
- Attendance tracking (clock-in/out, break tracking, overtime calculation)
- Performance management (review cycles, templates, 360-degree feedback, analytics)
- Disciplinary case management (investigation, resolution, follow-up tracking)
- Training program management (enrollment, completion, certification tracking)
- Monthly attendance summaries and reports
- Factories for all core models
- Independent migrations and testability

## Factories
Located in `database/factories/`:
- `EmployeeFactory`
- `EmploymentContractFactory`
- `LeaveEntitlementFactory`
- `LeaveRequestFactory`
- `AttendanceRecordFactory`
- `PerformanceCycleFactory`
- `PerformanceTemplateFactory`
- `PerformanceReviewFactory`
- `DisciplinaryCaseFactory`
- `TrainingFactory`
- `TrainingEnrollmentFactory`

Use these for seeding, testing, and generating realistic data in unit/feature tests.

## Running Tests
Tests use Pest and Orchestra Testbench with in-memory SQLite. Migrations are auto-run per test.

To run all HRM package tests:

```bash
cd /home/conrad/Dev/azaharizaman/nexus-erp
./vendor/bin/pest -c packages/nexus-hrm/phpunit.xml --colors=always
```

## Requirements
- Laravel 12+
- PHP 8.3+
- No cross-package dependencies; all integration via contracts/events

## Architecture
- Follows Maximum Atomicity: no direct dependencies on other packages
- All orchestration and integration handled in ERP core

---
For requirements and architecture, see:
- `docs/SYSTEM ARCHITECHTURAL DOCUMENT.md`
- `docs/PHASE3_HTTP_API_INTEGRATION_SUMMARY.md`
