---
goal: Implement Comprehensive Audit Logging System with Blockchain Verification
version: 1.0
date_created: 2025-11-08
last_updated: 2025-11-08
owner: Core Domain Team
status: 'Planned'
tags: [infrastructure, core, audit, logging, blockchain, compliance, phase-1, mvp]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan establishes a comprehensive audit logging system for the Laravel ERP that captures all data modifications, user activities, and system events. The system integrates Spatie Laravel Activitylog for activity tracking and provides optional blockchain verification for critical transactions using the php-blockchain package. This infrastructure is essential for compliance, security auditing, and forensic analysis.

## 1. Requirements & Constraints

**Core Requirements:**
- **REQ-001**: Integrate Spatie Laravel Activitylog package for activity tracking
- **REQ-002**: Log all model create, update, and delete operations automatically
- **REQ-003**: Capture user identity, IP address, user agent, and timestamp for all activities
- **REQ-004**: Store old and new values for all changed attributes
- **REQ-005**: Support custom activity logging beyond model changes
- **REQ-006**: Implement immutable audit trail (no modification or deletion of audit records)
- **REQ-007**: Integrate php-blockchain package for cryptographic verification
- **REQ-008**: Generate and store blockchain hashes for critical transactions
- **REQ-009**: Provide verification endpoint to validate blockchain integrity
- **REQ-010**: Support querying audit logs by user, model, date range, and action
- **REQ-011**: Implement audit log export functionality (CSV, JSON)
- **REQ-012**: Create API endpoints for audit log access
- **REQ-013**: Implement CLI commands for audit operations
- **REQ-014**: Apply tenant isolation to audit logs

**Security Requirements:**
- **SEC-001**: Prevent modification or deletion of audit log entries
- **SEC-002**: Store sensitive data changes in encrypted format
- **SEC-003**: Restrict audit log access to authorized users only (Admin/Auditor roles)
- **SEC-004**: Log all authentication and authorization events
- **SEC-005**: Log permission and role changes with full details
- **SEC-006**: Implement tamper detection using blockchain verification

**Performance Constraints:**
- **CON-001**: Audit logging must not add more than 20ms overhead per operation
- **CON-002**: Async logging for non-critical operations using queues
- **CON-003**: Support minimum 1000 audit log writes per second
- **CON-004**: Efficient querying with proper indexing on activity_log table
- **CON-005**: Archive old audit logs (>1 year) to separate storage

**Compliance Guidelines:**
- **GUD-001**: Maintain audit trail for minimum 7 years
- **GUD-002**: Capture sufficient detail for forensic analysis
- **GUD-003**: Support GDPR data subject access requests
- **GUD-004**: Comply with SOC 2, ISO 27001 audit requirements
- **GUD-005**: Provide audit reports for compliance auditors

**Design Patterns:**
- **PAT-001**: Use Spatie LogsActivity trait for automatic model auditing
- **PAT-002**: Use observer pattern for capturing model events
- **PAT-003**: Apply repository pattern for audit log queries
- **PAT-004**: Use action pattern for complex audit operations
- **PAT-005**: Implement queue-based async logging for performance

## 2. Implementation Steps

### Implementation Phase 1: Spatie Activitylog Integration

- GOAL-001: Install and configure Spatie Laravel Activitylog package

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Install spatie/laravel-activitylog package via composer require | | |
| TASK-002 | Publish activitylog configuration: php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" | | |
| TASK-003 | Run activitylog migrations to create activity_log table | | |
| TASK-004 | Configure activitylog in config/activitylog.php: enable/disable logging, set database connection, configure cleanup | | |
| TASK-005 | Add tenant_id column to activity_log table for multi-tenant isolation | | |
| TASK-006 | Add indexes to activity_log table: (tenant_id, created_at), (subject_type, subject_id), (causer_type, causer_id), log_name | | |
| TASK-007 | Set default log name to 'default' and configure custom log names per domain | | |

### Implementation Phase 2: Model Activity Logging

- GOAL-002: Implement automatic activity logging for all domain models

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-008 | Add LogsActivity trait to all domain models in Core, Backoffice, Inventory, Sales, Purchasing domains | | |
| TASK-009 | Configure $logAttributes on each model to specify which attributes to log (use ['*'] for all) | | |
| TASK-010 | Set $logOnlyDirty = true on models to log only changed attributes | | |
| TASK-011 | Set $dontSubmitEmptyLogs = true to avoid empty log entries | | |
| TASK-012 | Implement getActivitylogOptions() method on models for fine-grained control | | |
| TASK-013 | Configure $logName per domain: 'core', 'backoffice', 'inventory', 'sales', 'purchasing' | | |
| TASK-014 | Test automatic logging on model create, update, delete operations | | |

### Implementation Phase 3: Custom Activity Logging

- GOAL-003: Implement custom activity logging for business operations

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-015 | Create ActivityLoggerService in app/Domains/Core/Services/ActivityLoggerService.php | | |
| TASK-016 | Implement log() method with parameters: description, subject, properties, logName | | |
| TASK-017 | Implement logAuthentication() method for login/logout events | | |
| TASK-018 | Implement logPermissionChange() method for role/permission modifications | | |
| TASK-019 | Implement logCriticalOperation() method for sensitive operations (with blockchain) | | |
| TASK-020 | Implement logExport() method for data export activities | | |
| TASK-021 | Add causer (user) and tenant context to all custom logs | | |
| TASK-022 | Create ActivityLoggerContract interface in app/Domains/Core/Contracts/ActivityLoggerContract.php | | |
| TASK-023 | Bind ActivityLoggerContract to ActivityLoggerService in service provider | | |

### Implementation Phase 4: Blockchain Integration Setup

- GOAL-004: Integrate php-blockchain package for cryptographic verification

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-024 | Install azaharizaman/php-blockchain:dev-main package via composer require | | |
| TASK-025 | Publish blockchain configuration if available | | |
| TASK-026 | Create blockchain_verifications table migration with columns: id, model_type, model_id, activity_log_id, hash, previous_hash, block_index, created_at | | |
| TASK-027 | Add indexes: (model_type, model_id), activity_log_id, hash | | |
| TASK-028 | Create BlockchainVerification model in app/Domains/Core/Models/BlockchainVerification.php | | |
| TASK-029 | Add relationships: activity(), subject() morphTo | | |
| TASK-030 | Implement BelongsToTenant trait on BlockchainVerification model | | |

### Implementation Phase 5: Blockchain Verification Service

- GOAL-005: Build blockchain verification service for critical operations

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-031 | Create BlockchainVerifierContract in app/Domains/Core/Contracts/BlockchainVerifierContract.php with methods: store(), verify(), getChain() | | |
| TASK-032 | Implement BlockchainVerifier service in app/Domains/Core/Services/BlockchainVerifier.php | | |
| TASK-033 | Implement store() method: calculate hash from model data, store BlockchainVerification record | | |
| TASK-034 | Use SHA-256 for hash calculation including: model data, timestamp, previous hash | | |
| TASK-035 | Implement verify() method: recalculate hash and compare with stored hash | | |
| TASK-036 | Implement verifyChain() method: validate entire blockchain for model type | | |
| TASK-037 | Implement getChain() method: retrieve blockchain history for a model | | |
| TASK-038 | Add error handling for blockchain verification failures | | |
| TASK-039 | Bind BlockchainVerifierContract to BlockchainVerifier in service provider | | |

### Implementation Phase 6: Critical Operation Identification

- GOAL-006: Identify and tag critical operations requiring blockchain verification

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-040 | Create CriticalOperation attribute in app/Domains/Core/Attributes/CriticalOperation.php | | |
| TASK-041 | Tag critical actions with #[CriticalOperation] attribute | | |
| TASK-042 | Critical operations: Tenant creation/archival, User creation/deletion, Role/permission changes | | |
| TASK-043 | Critical operations: Purchase order approval, Invoice posting, Payment processing | | |
| TASK-044 | Critical operations: Stock adjustments, Goods receipt posting, Sales order confirmation | | |
| TASK-045 | Create BlockchainObserver in app/Domains/Core/Observers/BlockchainObserver.php | | |
| TASK-046 | Implement created() method to store blockchain hash for critical models | | |
| TASK-047 | Implement updated() method to add new block to chain | | |
| TASK-048 | Register observers in EventServiceProvider for critical models | | |

### Implementation Phase 7: Audit Log Repository

- GOAL-007: Build repository for querying and retrieving audit logs

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-049 | Create AuditLogRepositoryContract in app/Domains/Core/Contracts/AuditLogRepositoryContract.php | | |
| TASK-050 | Define methods: findById(), getBySubject(), getByCauser(), getByDateRange(), getByLogName() | | |
| TASK-051 | Implement AuditLogRepository in app/Domains/Core/Repositories/AuditLogRepository.php | | |
| TASK-052 | Implement findById() returning single activity with subject and causer relationships | | |
| TASK-053 | Implement getBySubject() with pagination, accepting model type and ID | | |
| TASK-054 | Implement getByCauser() to get all activities by specific user | | |
| TASK-055 | Implement getByDateRange() with filtering by from/to dates | | |
| TASK-056 | Implement getByLogName() to filter by domain (core, inventory, sales, etc.) | | |
| TASK-057 | Add support for combined filters and sorting | | |
| TASK-058 | Apply tenant scope to all queries | | |

### Implementation Phase 8: Audit Log Actions

- GOAL-008: Create action classes for audit log operations

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-059 | Create GetAuditLogsAction in app/Domains/Core/Actions/Audit/GetAuditLogsAction.php | | |
| TASK-060 | Implement filtering by subject, causer, date range, log name with pagination | | |
| TASK-061 | Create ExportAuditLogsAction in app/Domains/Core/Actions/Audit/ExportAuditLogsAction.php | | |
| TASK-062 | Support export formats: CSV, JSON with configurable date range | | |
| TASK-063 | Generate export filename with timestamp: audit_log_YYYYMMDD_HHMMSS.csv | | |
| TASK-064 | Include all relevant fields: timestamp, user, action, model, old/new values | | |
| TASK-065 | Create VerifyBlockchainAction in app/Domains/Core/Actions/Audit/VerifyBlockchainAction.php | | |
| TASK-066 | Accept model type and ID, call BlockchainVerifier.verify() | | |
| TASK-067 | Return verification result with tamper status and hash details | | |
| TASK-068 | Implement queue-based async export for large datasets | | |

### Implementation Phase 9: API Endpoints

- GOAL-009: Build RESTful API endpoints for audit log access

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-069 | Create AuditLogController in app/Http/Controllers/Api/V1/AuditLogController.php | | |
| TASK-070 | Implement index() method with pagination, filtering, and sorting | | |
| TASK-071 | Support query parameters: subject_type, subject_id, causer_id, log_name, from_date, to_date | | |
| TASK-072 | Implement show() method returning single activity with full details | | |
| TASK-073 | Implement verify() method to verify blockchain hash for specific record | | |
| TASK-074 | Create AuditLogResource in app/Http/Resources/AuditLogResource.php | | |
| TASK-075 | Include fields: id, description, subject, causer, properties, log_name, created_at | | |
| TASK-076 | Include blockchain verification status if applicable | | |
| TASK-077 | Apply authorization: only users with 'view audit logs' permission can access | | |

### Implementation Phase 10: Audit Export Endpoint

- GOAL-010: Create endpoint for exporting audit logs

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-078 | Add export() method to AuditLogController | | |
| TASK-079 | Accept query parameters: format (csv/json), from_date, to_date, filters | | |
| TASK-080 | Call ExportAuditLogsAction with filters | | |
| TASK-081 | Return downloadable file for synchronous small exports | | |
| TASK-082 | Queue job for large exports and return job ID | | |
| TASK-083 | Implement status endpoint to check export job progress | | |
| TASK-084 | Send email notification when export is ready | | |
| TASK-085 | Store exported files in storage/app/exports with auto-cleanup after 24 hours | | |

### Implementation Phase 11: CLI Commands

- GOAL-011: Create CLI commands for audit operations

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-086 | Create ExportAuditLogsCommand in app/Console/Commands/Audit/ExportAuditLogsCommand.php with signature erp:audit:export | | |
| TASK-087 | Add options: --from, --to, --format, --output, --tenant | | |
| TASK-088 | Call ExportAuditLogsAction and save to specified output path | | |
| TASK-089 | Display progress bar for large exports | | |
| TASK-090 | Create VerifyBlockchainCommand in app/Console/Commands/Audit/VerifyBlockchainCommand.php with signature erp:audit:verify {model} {id} | | |
| TASK-091 | Call VerifyBlockchainAction and display verification result | | |
| TASK-092 | Create CleanupAuditLogsCommand in app/Console/Commands/Audit/CleanupAuditLogsCommand.php with signature erp:audit:cleanup | | |
| TASK-093 | Add option --days to specify retention period (default 365 days) | | |
| TASK-094 | Archive old logs to separate table or storage before deletion | | |
| TASK-095 | Display count of archived/deleted records | | |
| TASK-096 | Register commands in app/Console/Kernel.php | | |
| TASK-097 | Schedule CleanupAuditLogsCommand to run monthly | | |

### Implementation Phase 12: Authentication Event Logging

- GOAL-012: Implement comprehensive authentication event logging

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-098 | Update LogAuthenticationAttemptListener to use ActivityLoggerService | | |
| TASK-099 | Log UserLoggedInEvent with IP address, user agent, timestamp | | |
| TASK-100 | Log UserLoggedOutEvent with session duration | | |
| TASK-101 | Log PasswordResetEvent with requester IP | | |
| TASK-102 | Log PasswordChangedEvent | | |
| TASK-103 | Log AccountLockedEvent with reason and trigger | | |
| TASK-104 | Log MFA enable/disable events | | |
| TASK-105 | Log failed login attempts with IP address | | |
| TASK-106 | Log token refresh events | | |
| TASK-107 | Tag all authentication logs with log_name: 'security' | | |

### Implementation Phase 13: Permission Change Logging

- GOAL-013: Log all role and permission modifications

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-108 | Create RolePermissionObserver in app/Domains/Core/Observers/RolePermissionObserver.php | | |
| TASK-109 | Listen for role created/updated/deleted events | | |
| TASK-110 | Listen for permission created/updated/deleted events | | |
| TASK-111 | Listen for role assigned/revoked from user events | | |
| TASK-112 | Listen for permission assigned/revoked from role events | | |
| TASK-113 | Log full details: old/new values, user making change, affected user/role | | |
| TASK-114 | Tag with log_name: 'security' for sensitive permission changes | | |
| TASK-115 | Register observer in EventServiceProvider | | |

### Implementation Phase 14: Tenant Isolation

- GOAL-014: Ensure audit logs respect tenant boundaries

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-116 | Add tenant_id to activity_log table via migration | | |
| TASK-117 | Modify Spatie's Activity model by extending in app/Domains/Core/Models/Activity.php | | |
| TASK-118 | Add BelongsToTenant trait to custom Activity model | | |
| TASK-119 | Configure activitylog.activity_model to use custom Activity model | | |
| TASK-120 | Test tenant isolation: user A cannot see tenant B's audit logs | | |
| TASK-121 | Apply tenant scope in AuditLogRepository queries | | |

### Implementation Phase 15: Audit Log Policies

- GOAL-015: Implement authorization policies for audit log access

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-122 | Create AuditLogPolicy in app/Domains/Core/Policies/AuditLogPolicy.php | | |
| TASK-123 | Implement viewAny() checking 'view audit logs' permission | | |
| TASK-124 | Implement view() checking 'view audit logs' permission and tenant isolation | | |
| TASK-125 | Implement export() checking 'export audit logs' permission (Admin/Auditor only) | | |
| TASK-126 | Implement verify() checking 'verify blockchain' permission | | |
| TASK-127 | Register AuditLogPolicy in AuthServiceProvider | | |
| TASK-128 | Apply policy checks in AuditLogController methods | | |

### Implementation Phase 16: Routes Definition

- GOAL-016: Define audit log API routes

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-129 | Define routes in routes/api.php under /api/v1/audit prefix | | |
| TASK-130 | GET /api/v1/audit/activities - List audit logs with filters | | |
| TASK-131 | GET /api/v1/audit/activities/{id} - Get single audit log | | |
| TASK-132 | GET /api/v1/audit/export - Export audit logs | | |
| TASK-133 | GET /api/v1/audit/verify/{model}/{id} - Verify blockchain hash | | |
| TASK-134 | Apply auth:sanctum middleware to all audit routes | | |
| TASK-135 | Apply throttle middleware to prevent abuse | | |

### Implementation Phase 17: Testing

- GOAL-017: Create comprehensive test suite for audit logging

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-136 | Create AuditLogTest feature test in tests/Feature/Core/AuditLogTest.php | | |
| TASK-137 | Test model creation logs activity automatically | | |
| TASK-138 | Test model update logs only changed attributes | | |
| TASK-139 | Test model deletion logs activity | | |
| TASK-140 | Test custom activity logging via ActivityLoggerService | | |
| TASK-141 | Test GET /api/v1/audit/activities returns paginated logs | | |
| TASK-142 | Test filtering by subject_type, causer_id, date range | | |
| TASK-143 | Test audit log export generates correct file | | |
| TASK-144 | Test unauthorized user cannot access audit logs (403) | | |
| TASK-145 | Test tenant isolation: user cannot see other tenant's logs | | |
| TASK-146 | Create BlockchainVerificationTest unit test in tests/Unit/Core/BlockchainVerificationTest.php | | |
| TASK-147 | Test blockchain hash generation is deterministic | | |
| TASK-148 | Test blockchain verification detects tampering | | |
| TASK-149 | Test blockchain chain validation | | |
| TASK-150 | Create AuditLogRepositoryTest unit test in tests/Unit/Core/AuditLogRepositoryTest.php | | |
| TASK-151 | Test getBySubject() returns correct logs | | |
| TASK-152 | Test getByCauser() returns user's activities | | |
| TASK-153 | Test getByDateRange() filters correctly | | |

## 3. Alternatives

- **ALT-001**: Build custom audit logging from scratch - Rejected because Spatie Laravel Activitylog is battle-tested, feature-rich, and well-maintained. No need to reinvent the wheel.

- **ALT-002**: Use database triggers for audit logging - Rejected because it couples audit logic to database layer, makes it database-specific, and reduces flexibility. Application-level logging provides better control.

- **ALT-003**: Third-party blockchain service (e.g., AWS Quantum Ledger Database) - Considered but custom implementation with php-blockchain provides more control and avoids vendor lock-in. Can be migrated later if needed.

- **ALT-004**: Event sourcing architecture for complete audit trail - Considered but too complex for Phase 1. Current approach provides sufficient audit trail. Event sourcing can be added to specific domains in later phases.

- **ALT-005**: Store audit logs in separate database - Considered for performance but rejected for simplicity. Will implement table partitioning if performance becomes issue.

## 4. Dependencies

- **DEP-001**: Laravel 12.x framework installed and configured
- **DEP-002**: PHP 8.2+ for attributes and typed properties
- **DEP-003**: spatie/laravel-activitylog package (install via composer)
- **DEP-004**: azaharizaman/php-blockchain package (install via composer)
- **DEP-005**: User authentication system must be implemented (PRD-02)
- **DEP-006**: Tenant system must be implemented (PRD-01)
- **DEP-007**: Queue driver configured (Redis/Database) for async operations
- **DEP-008**: Storage configured for audit log exports
- **DEP-009**: Email configuration for export notifications

## 5. Files

**New Files to Create:**
- **FILE-001**: database/migrations/YYYY_MM_DD_HHMMSS_add_tenant_id_to_activity_log_table.php - Add tenant isolation
- **FILE-002**: database/migrations/YYYY_MM_DD_HHMMSS_create_blockchain_verifications_table.php - Blockchain table
- **FILE-003**: app/Domains/Core/Models/Activity.php - Extended Activity model with tenant support
- **FILE-004**: app/Domains/Core/Models/BlockchainVerification.php - Blockchain verification model
- **FILE-005**: app/Domains/Core/Contracts/ActivityLoggerContract.php - Activity logger interface
- **FILE-006**: app/Domains/Core/Services/ActivityLoggerService.php - Custom activity logging service
- **FILE-007**: app/Domains/Core/Contracts/BlockchainVerifierContract.php - Blockchain verifier interface
- **FILE-008**: app/Domains/Core/Services/BlockchainVerifier.php - Blockchain verification service
- **FILE-009**: app/Domains/Core/Attributes/CriticalOperation.php - Critical operation attribute
- **FILE-010**: app/Domains/Core/Observers/BlockchainObserver.php - Blockchain observer
- **FILE-011**: app/Domains/Core/Observers/RolePermissionObserver.php - Role/permission observer
- **FILE-012**: app/Domains/Core/Contracts/AuditLogRepositoryContract.php - Audit log repository interface
- **FILE-013**: app/Domains/Core/Repositories/AuditLogRepository.php - Audit log repository
- **FILE-014**: app/Domains/Core/Actions/Audit/GetAuditLogsAction.php - Get audit logs action
- **FILE-015**: app/Domains/Core/Actions/Audit/ExportAuditLogsAction.php - Export action
- **FILE-016**: app/Domains/Core/Actions/Audit/VerifyBlockchainAction.php - Verify blockchain action
- **FILE-017**: app/Http/Controllers/Api/V1/AuditLogController.php - Audit log API controller
- **FILE-018**: app/Http/Resources/AuditLogResource.php - Audit log API resource
- **FILE-019**: app/Console/Commands/Audit/ExportAuditLogsCommand.php - Export CLI command
- **FILE-020**: app/Console/Commands/Audit/VerifyBlockchainCommand.php - Verify CLI command
- **FILE-021**: app/Console/Commands/Audit/CleanupAuditLogsCommand.php - Cleanup CLI command
- **FILE-022**: app/Domains/Core/Policies/AuditLogPolicy.php - Audit log authorization policy
- **FILE-023**: app/Domains/Core/Jobs/ExportAuditLogsJob.php - Async export job

**Files to Modify:**
- **FILE-024**: config/activitylog.php - Configure activity logging
- **FILE-025**: app/Providers/EventServiceProvider.php - Register observers
- **FILE-026**: app/Providers/AuthServiceProvider.php - Register AuditLogPolicy
- **FILE-027**: app/Console/Kernel.php - Register and schedule commands
- **FILE-028**: routes/api.php - Define audit log routes
- **FILE-029**: All domain models - Add LogsActivity trait

**Test Files:**
- **FILE-030**: tests/Feature/Core/AuditLogTest.php - Feature tests
- **FILE-031**: tests/Unit/Core/BlockchainVerificationTest.php - Blockchain unit tests
- **FILE-032**: tests/Unit/Core/AuditLogRepositoryTest.php - Repository unit tests
- **FILE-033**: tests/Unit/Core/ActivityLoggerServiceTest.php - Service unit tests

## 6. Testing

**Unit Tests:**
- **TEST-001**: Test ActivityLoggerService log() method creates activity record
- **TEST-002**: Test ActivityLoggerService includes causer and tenant context
- **TEST-003**: Test BlockchainVerifier generates deterministic hashes
- **TEST-004**: Test BlockchainVerifier verify() detects tampering
- **TEST-005**: Test BlockchainVerifier verifyChain() validates entire chain
- **TEST-006**: Test AuditLogRepository getBySubject() returns correct logs
- **TEST-007**: Test AuditLogRepository getByCauser() filters by user
- **TEST-008**: Test AuditLogRepository getByDateRange() filters correctly
- **TEST-009**: Test ExportAuditLogsAction generates CSV correctly
- **TEST-010**: Test ExportAuditLogsAction generates JSON correctly

**Feature Tests:**
- **TEST-011**: Test model creation automatically logs activity
- **TEST-012**: Test model update logs only changed attributes
- **TEST-013**: Test model deletion logs activity
- **TEST-014**: Test custom activity logging via service
- **TEST-015**: Test GET /api/v1/audit/activities returns paginated logs
- **TEST-016**: Test filtering audit logs by subject type
- **TEST-017**: Test filtering audit logs by causer
- **TEST-018**: Test filtering audit logs by date range
- **TEST-019**: Test GET /api/v1/audit/activities/{id} returns single log
- **TEST-020**: Test GET /api/v1/audit/export exports logs
- **TEST-021**: Test GET /api/v1/audit/verify verifies blockchain hash
- **TEST-022**: Test unauthorized user cannot access audit logs (403)
- **TEST-023**: Test tenant isolation in audit logs
- **TEST-024**: Test authentication events are logged
- **TEST-025**: Test permission changes are logged
- **TEST-026**: Test CLI command php artisan erp:audit:export works
- **TEST-027**: Test CLI command php artisan erp:audit:verify works

**Integration Tests:**
- **TEST-028**: Test blockchain verification workflow end-to-end
- **TEST-029**: Test audit log export for large datasets
- **TEST-030**: Test audit log cleanup archives old records
- **TEST-031**: Test critical operations generate blockchain entries

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: Audit log table growth impacting performance - Mitigation: Implement table partitioning, regular archival, proper indexing
- **RISK-002**: Blockchain verification performance overhead - Mitigation: Async blockchain storage, only for critical operations
- **RISK-003**: Storage costs for long-term audit retention - Mitigation: Archive to cheaper storage (S3 Glacier), implement compression
- **RISK-004**: Blockchain chain validation failure - Mitigation: Regular validation jobs, alerting, backup verification methods
- **RISK-005**: Privacy concerns with detailed logging - Mitigation: Encrypt sensitive data, support GDPR deletion requests
- **RISK-006**: Query performance on large activity_log table - Mitigation: Proper indexing, table partitioning, read replicas

**Assumptions:**
- **ASSUMPTION-001**: 7-year retention period is sufficient for compliance
- **ASSUMPTION-002**: Database can handle audit log volume (estimated 1M records/month per tenant)
- **ASSUMPTION-003**: Blockchain verification is only needed for critical operations (< 5% of total operations)
- **ASSUMPTION-004**: Async logging is acceptable for non-critical operations
- **ASSUMPTION-005**: CSV and JSON export formats are sufficient
- **ASSUMPTION-006**: Monthly cleanup job is adequate for archive management
- **ASSUMPTION-007**: Activity log modifications are never legitimate (immutability requirement)

## 8. Related Specifications / Further Reading

- [PHASE-1-MVP.md](../docs/prd/PHASE-1-MVP.md) - Overall Phase 1 requirements
- [PRD-01-infrastructure-multitenancy-1.md](./PRD-01-infrastructure-multitenancy-1.md) - Multi-tenancy system
- [PRD-02-infrastructure-auth-1.md](./PRD-02-infrastructure-auth-1.md) - Authentication system
- [Spatie Laravel Activitylog Documentation](https://spatie.be/docs/laravel-activitylog)
- [PHP Blockchain Package](https://github.com/azaharizaman/php-blockchain)
- [Blockchain for Audit Trails](https://www.sciencedirect.com/science/article/pii/S2096720921000336)
- [SOC 2 Audit Trail Requirements](https://www.vanta.com/resources/soc-2-audit-logs)
- [MODULE-DEVELOPMENT.md](../docs/prd/MODULE-DEVELOPMENT.md) - Module development guidelines
