.
├── apps
│   └── edward
│       ├── app
│       │   ├── Actions
│       │   │   ├── Auth
│       │   │   │   ├── LoginAction.php
│       │   │   │   ├── LogoutAction.php
│       │   │   │   ├── RegisterUserAction.php
│       │   │   │   ├── RequestPasswordResetAction.php
│       │   │   │   └── ResetPasswordAction.php
│       │   │   ├── Permission
│       │   │   │   ├── AssignRoleToUserAction.php
│       │   │   │   ├── CreateRoleAction.php
│       │   │   │   └── RevokeRoleFromUserAction.php
│       │   │   ├── UnitOfMeasure
│       │   │   │   ├── ConvertQuantityAction.php
│       │   │   │   ├── GetCompatibleUomsAction.php
│       │   │   │   └── ValidateUomCompatibilityAction.php
│       │   │   └── User
│       │   │       └── SuspendUserAction.php
│       │   ├── Console
│       │   │   └── Commands
│       │   │       ├── EdwardMenuCommand.php
│       │   │       ├── SequencingDemoCommand.php
│       │   │       ├── Tenant
│       │   │       │   ├── CreateTenantCommand.php
│       │   │       │   └── ListTenantsCommand.php
│       │   │       ├── Tenant.backup
│       │   │       │   ├── CreateTenantCommand.php
│       │   │       │   └── ListTenantsCommand.php
│       │   │       ├── TestTenantWorkflowCommand.php
│       │   │       ├── TestUserTaskCommand.php
│       │   │       └── WorkflowManagementCommand.php
│       │   ├── Enums
│       │   │   └── UomCategory.php
│       │   ├── Events
│       │   │   ├── Auth
│       │   │   │   ├── LoginFailedEvent.php
│       │   │   │   ├── PasswordResetEvent.php
│       │   │   │   ├── PasswordResetRequestedEvent.php
│       │   │   │   ├── UserLoggedInEvent.php
│       │   │   │   ├── UserLoggedOutEvent.php
│       │   │   │   ├── UserRegisteredEvent.php
│       │   │   │   └── UserSuspendedEvent.php
│       │   │   └── Permission
│       │   │       ├── RoleAssignedEvent.php
│       │   │       ├── RoleCreatedEvent.php
│       │   │       └── RoleRevokedEvent.php
│       │   ├── Exceptions
│       │   │   ├── AccountLockedException.php
│       │   │   └── UnitOfMeasure
│       │   │       ├── IncompatibleUomException.php
│       │   │       ├── InvalidQuantityException.php
│       │   │       ├── UomConversionException.php
│       │   │       └── UomNotFoundException.php
│       │   ├── Listeners
│       │   │   └── Auth
│       │   │       ├── LogAuthenticationFailureListener.php
│       │   │       └── LogAuthenticationSuccessListener.php
│       │   ├── Models
│       │   │   ├── Employee.php
│       │   │   ├── Invoice.php
│       │   │   ├── PurchaseOrder.php
│       │   │   ├── Uom.php
│       │   │   └── User.php
│       │   ├── Policies
│       │   │   ├── RolePolicy.php
│       │   │   └── UserPolicy.php
│       │   ├── Providers
│       │   │   ├── AppServiceProvider.php
│       │   │   ├── AuthServiceProvider.php
│       │   │   ├── EventServiceProvider.php
│       │   │   ├── LoggingServiceProvider.php
│       │   │   ├── PermissionServiceProvider.php
│       │   │   └── SearchServiceProvider.php
│       │   ├── Repositories
│       │   │   ├── DatabaseUomRepository.php
│       │   │   └── UserRepository.php
│       │   ├── Services
│       │   │   └── UnitOfMeasure
│       │   │       └── UomConversionService.php
│       │   └── Support
│       │       ├── Contracts
│       │       │   ├── ActivityLoggerContract.php
│       │       │   ├── PermissionServiceContract.php
│       │       │   ├── SearchServiceContract.php
│       │       │   └── TokenServiceContract.php
│       │       ├── Helpers
│       │       │   └── tenant.php
│       │       ├── Services
│       │       │   ├── Auth
│       │       │   │   └── SanctumTokenService.php
│       │       │   ├── Logging
│       │       │   │   └── SpatieActivityLogger.php
│       │       │   ├── Permission
│       │       │   │   └── SpatiePermissionService.php
│       │       │   └── Search
│       │       │       └── ScoutSearchService.php
│       │       └── Traits
│       │           ├── HasActivityLogging.php
│       │           ├── HasPermissions.php
│       │           ├── HasTokens.php
│       │           └── IsSearchable.php
│       ├── artisan
│       ├── bootstrap
│       │   ├── app.php
│       │   ├── cache
│       │   └── providers.php
│       ├── composer.json
│       ├── config
│       │   ├── app.php
│       │   ├── authentication.php
│       │   ├── auth.php
│       │   ├── cache.php
│       │   ├── database.php
│       │   ├── filesystems.php
│       │   ├── logging.php
│       │   ├── mail.php
│       │   ├── packages.php
│       │   ├── permission.php
│       │   ├── queue.php
│       │   ├── sanctum.php
│       │   ├── scout.php
│       │   ├── services.php
│       │   └── session.php
│       ├── database
│       │   ├── factories
│       │   │   ├── TenantFactory.php
│       │   │   ├── UomFactory.php
│       │   │   └── UserFactory.php
│       │   ├── migrations
│       │   │   ├── 0001_01_01_000000_create_tenants_table.php
│       │   │   ├── 0001_01_01_000001_create_cache_table.php
│       │   │   ├── 0001_01_01_000002_create_jobs_table.php
│       │   │   ├── 0001_01_01_000003_create_users_table.php
│       │   │   ├── 2025_01_01_000006_create_uoms_table.php
│       │   │   ├── 2025_01_15_000001_create_purchase_orders_table.php
│       │   │   ├── 2025_11_09_023712_create_activity_log_table.php
│       │   │   ├── 2025_11_09_023713_add_event_column_to_activity_log_table.php
│       │   │   ├── 2025_11_09_023714_add_batch_uuid_column_to_activity_log_table.php
│       │   │   ├── 2025_11_09_072906_create_personal_access_tokens_table.php
│       │   │   └── 2025_11_10_111307_create_permission_tables.php
│       │   └── seeders
│       │       ├── DatabaseSeeder.php
│       │       ├── RolePermissionSeeder.php
│       │       └── UomSeeder.php
│       ├── edward-screen.png
│       ├── IMPLEMENTATION_SUMMARY_CONVERSION_ENGINE.md
│       ├── phpunit.xml
│       ├── public
│       ├── README.md
│       ├── routes
│       │   └── console.php
│       ├── src
│       │   └── Enums
│       │       └── UserStatus.php
│       ├── storage
│       │   ├── app
│       │   │   ├── private
│       │   │   └── public
│       │   ├── framework
│       │   │   ├── cache
│       │   │   │   └── data
│       │   │   ├── sessions
│       │   │   ├── testing
│       │   │   └── views
│       │   └── logs
│       └── tests
│           ├── Feature
│           │   ├── Admin
│           │   │   └── UserManagementApiTest.php
│           │   ├── Auth
│           │   │   ├── AuthenticationApiTest.php
│           │   │   ├── SanctumAuthenticationTest.php
│           │   │   ├── SanctumTokenAbilitiesTest.php
│           │   │   └── SpatiePermissionIntegrationTest.php
│           │   ├── Console
│           │   │   └── Commands
│           │   │       └── Tenant
│           │   │           ├── CreateTenantCommandTest.php
│           │   │           └── ListTenantsCommandTest.php
│           │   ├── Core
│           │   │   └── TenantTest.php
│           │   ├── Domains
│           │   │   └── Core
│           │   │       ├── Actions
│           │   │       │   └── TenantActionsIntegrationTest.php
│           │   │       ├── Listeners
│           │   │       │   └── TenantLifecycleListenersIntegrationTest.php
│           │   │       ├── Middleware
│           │   │       │   └── IdentifyTenantTest.php
│           │   │       ├── Services
│           │   │       │   └── TenantManagerFeatureTest.php
│           │   │       └── TenantFeatureTest.php
│           │   ├── ExampleTest.php
│           │   ├── Http
│           │   │   └── Controllers
│           │   │       └── Api
│           │   │           └── V1
│           │   │               └── TenantControllerTest.php
│           │   ├── Permission
│           │   │   └── RbacActionsTest.php
│           │   ├── Support
│           │   │   ├── ActivityLoggerContractTest.php
│           │   │   ├── SearchServiceContractTest.php
│           │   │   └── TokenServiceContractTest.php
│           │   └── UnitOfMeasure
│           │       ├── UomConversionTest.php
│           │       └── UomSeederTest.php
│           ├── Integration
│           │   └── UnitOfMeasure
│           │       └── UomIntegrationTest.php
│           ├── Pest.php
│           ├── TestCase.php
│           └── Unit
│               ├── Actions
│               │   └── Auth
│               │       └── LoginActionTest.php
│               ├── Core
│               │   ├── TenantManagerTest.php
│               │   └── TenantScopeTest.php
│               ├── Domains
│               │   └── Core
│               │       ├── Actions
│               │       │   ├── ArchiveTenantActionTest.php
│               │       │   ├── CreateTenantActionTest.php
│               │       │   └── UpdateTenantActionTest.php
│               │       ├── Enums
│               │       │   ├── TenantStatusTest.php
│               │       │   └── UserStatusTest.php
│               │       ├── Listeners
│               │       │   └── InitializeTenantDataListenerTest.php
│               │       ├── Models
│               │       │   ├── TenantTest.php
│               │       │   └── UserTest.php
│               │       ├── Policies
│               │       │   └── TenantPolicyTest.php
│               │       ├── Scopes
│               │       │   └── TenantScopeTest.php
│               │       ├── Services
│               │       │   └── TenantManagerTest.php
│               │       └── Traits
│               │           └── BelongsToTenantTest.php
│               ├── ExampleTest.php
│               ├── ScoutIntegrationTest.php
│               ├── Support
│               │   └── Helpers
│               │       └── TenantHelperTest.php
│               └── UnitOfMeasure
│                   ├── UomConversionServiceTest.php
│                   └── UomTest.php
├── ATOMICITY-REPURPOSE.md
├── boost.json
├── CODING_GUIDELINES.md
├── composer.json
├── composer.lock
├── config
│   └── nexus.php
├── database
│   └── migrations
│       └── 2025_11_14_000001_add_workflow_state_to_tenants_table.php
├── docs
│   ├── api
│   │   └── backoffice-api.md
│   ├── PHASE3_HTTP_API_INTEGRATION_SUMMARY.md
│   ├── plan
│   │   ├── PRD01-SUB01-PLAN01-implement-multitenancy-core.md
│   │   ├── PRD01-SUB01-PLAN02-implement-multitenancy-api.md
│   │   ├── PRD01-SUB02-PLAN01-implement-authentication-core.md
│   │   ├── PRD01-SUB02-PLAN02-implement-rbac-user-management.md
│   │   ├── PRD01-SUB03-PLAN01-implement-audit-logging.md
│   │   ├── PRD01-SUB04-PLAN01-implement-serial-numbering.md
│   │   ├── PRD01-SUB05-PLAN01-implement-settings-management.md
│   │   ├── PRD01-SUB06-PLAN01-implement-uom-foundation.md
│   │   ├── PRD01-SUB06-PLAN02-implement-uom-conversion.md
│   │   ├── PRD01-SUB06-PLAN03-implement-uom-api-integration.md
│   │   ├── PRD01-SUB07-PLAN01-implement-coa-foundation.md
│   │   ├── PRD01-SUB07-PLAN02-implement-coa-templates-actions.md
│   │   ├── PRD01-SUB07-PLAN03-implement-coa-api.md
│   │   ├── PRD01-SUB08-PLAN01-implement-gl-core-posting.md
│   │   ├── PRD01-SUB08-PLAN02-implement-gl-multi-currency.md
│   │   ├── PRD01-SUB08-PLAN03-implement-gl-fiscal-periods.md
│   │   ├── PRD01-SUB09-PLAN01-implement-journal-entries-core.md
│   │   ├── PRD01-SUB09-PLAN02-implement-journal-approval-gl-integration.md
│   │   ├── PRD01-SUB10-PLAN01-implement-banking-foundation.md
│   │   ├── PRD01-SUB10-PLAN02-implement-reconciliation-engine.md
│   │   ├── PRD01-SUB11-PLAN01-implement-ap-invoice-management.md
│   │   ├── PRD01-SUB11-PLAN02-implement-payment-processing.md
│   │   ├── PRD01-SUB12-PLAN01-implement-ar-invoice-management.md
│   │   ├── PRD01-SUB12-PLAN02-implement-ar-receipt-processing.md
│   │   ├── PRD01-SUB13-PLAN01-implement-employee-management-foundation.md
│   │   ├── PRD01-SUB13-PLAN02-implement-document-leave-management.md
│   │   ├── PRD01-SUB14-PLAN01-implement-inventory-foundation.md
│   │   ├── PRD01-SUB14-PLAN02-implement-stock-movements.md
│   │   ├── PRD01-SUB14-PLAN03-implement-inventory-valuation-reporting.md
│   │   ├── PRD01-SUB15-PLAN01-implement-organizational-foundation.md
│   │   ├── PRD01-SUB15-PLAN02-implement-fiscal-period-management.md
│   │   ├── PRD01-SUB15-PLAN03-implement-workflows-document-numbering.md
│   │   ├── PRD01-SUB16-PLAN01-implement-vendor-requisition-management.md
│   │   ├── PRD01-SUB16-PLAN02-implement-purchase-order-approval.md
│   │   ├── PRD01-SUB16-PLAN03-implement-goods-receipt-matching.md
│   │   ├── PRD01-SUB16-PLAN04-implement-vendor-performance-reporting.md
│   │   ├── PRD01-SUB17-PLAN01-implement-customer-quotation-management.md
│   │   ├── PRD01-SUB17-PLAN02-implement-sales-order-management.md
│   │   ├── PRD01-SUB17-PLAN03-implement-order-fulfillment-delivery.md
│   │   ├── PRD01-SUB18-PLAN01-implement-mdm-entity-foundation.md
│   │   ├── PRD01-SUB18-PLAN02-implement-data-quality-validation.md
│   │   ├── PRD01-SUB18-PLAN03-implement-duplicate-detection-merging.md
│   │   ├── PRD01-SUB18-PLAN04-implement-data-stewardship-bulk-operations.md
│   │   ├── PRD01-SUB19-PLAN01-implement-tax-master-data-foundation.md
│   │   ├── PRD01-SUB19-PLAN02-implement-tax-calculation-engine.md
│   │   ├── PRD01-SUB19-PLAN03-implement-tax-period-filing-management.md
│   │   ├── PRD01-SUB19-PLAN04-implement-tax-integration-reconciliation.md
│   │   ├── PRD01-SUB20-PLAN01-implement-financial-reporting-foundation.md
│   │   ├── PRD01-SUB20-PLAN02-implement-financial-reporting-api-comparison.md
│   │   ├── PRD01-SUB20-PLAN03-implement-custom-reports-dashboards.md
│   │   ├── PRD01-SUB20-PLAN04-implement-management-reports-bi-integration.md
│   │   ├── PRD01-SUB21-PLAN01-implement-workflow-engine-foundation.md
│   │   ├── PRD01-SUB21-PLAN02-implement-conditional-routing-delegation.md
│   │   ├── PRD01-SUB21-PLAN03-implement-escalation-workflow-inbox.md
│   │   ├── PRD01-SUB21-PLAN04-implement-workflow-designer-monitoring.md
│   │   ├── PRD01-SUB22-PLAN01-implement-notification-infrastructure-templates.md
│   │   ├── PRD01-SUB22-PLAN02-implement-multichannel-delivery-retry.md
│   │   ├── PRD01-SUB22-PLAN03-implement-event-subscriptions-preferences.md
│   │   ├── PRD01-SUB22-PLAN04-implement-realtime-streaming-webhooks.md
│   │   ├── PRD01-SUB23-PLAN01-implement-api-gateway-foundation.md
│   │   ├── PRD01-SUB23-PLAN02-implement-api-documentation-and-sandbox.md
│   │   ├── PRD01-SUB23-PLAN03-implement-rate-limiting-and-analytics.md
│   │   ├── PRD01-SUB23-PLAN04-implement-batch-operations-webhooks-sdks.md
│   │   ├── PRD01-SUB24-PLAN01-implement-connector-framework.md
│   │   ├── PRD01-SUB24-PLAN02-implement-sync-field-mapping.md
│   │   ├── PRD01-SUB24-PLAN03-implement-sync-conflict-resolution.md
│   │   ├── PRD01-SUB24-PLAN04-implement-webhooks-monitoring.md
│   │   ├── PRD01-SUB25-PLAN01-implement-language-translation.md
│   │   ├── PRD01-SUB25-PLAN02-implement-currency-exchange-rates.md
│   │   └── PRD01-SUB25-PLAN03-implement-formatting-tax-rules-api.md
│   ├── prd
│   │   ├── CONSOLIDATED-REQUIREMENTS.md
│   │   ├── PACKAGE-REQUIREMENTS-INDEX.md
│   │   ├── prd-01
│   │   │   ├── PRD01-SUB01-MULTITENANCY.md
│   │   │   ├── PRD01-SUB02-AUTHENTICATION.md
│   │   │   ├── PRD01-SUB03-AUDIT-LOGGING.md
│   │   │   ├── PRD01-SUB04-SERIAL-NUMBERING.md
│   │   │   ├── PRD01-SUB05-SETTINGS-MANAGEMENT.md
│   │   │   ├── PRD01-SUB06-UOM.md
│   │   │   ├── PRD01-SUB07-CHART-OF-ACCOUNTS.md
│   │   │   ├── PRD01-SUB08-GENERAL-LEDGER.md
│   │   │   ├── PRD01-SUB09-JOURNAL-ENTRIES.md
│   │   │   ├── PRD01-SUB10-BANKING.md
│   │   │   ├── PRD01-SUB11-ACCOUNTS-PAYABLE.md
│   │   │   ├── PRD01-SUB12-ACCOUNTS-RECEIVABLE.md
│   │   │   ├── PRD01-SUB13-HCM.md
│   │   │   ├── PRD01-SUB14-INVENTORY-MANAGEMENT.md
│   │   │   ├── PRD01-SUB15-BACKOFFICE.md
│   │   │   ├── PRD01-SUB16-PURCHASING.md
│   │   │   ├── PRD01-SUB17-SALES.md
│   │   │   ├── PRD01-SUB18-MASTER-DATA-MANAGEMENT.md
│   │   │   ├── PRD01-SUB19-TAXATION.md
│   │   │   ├── PRD01-SUB20-FINANCIAL-REPORTING.md
│   │   │   ├── PRD01-SUB21-WORKFLOW-ENGINE.md
│   │   │   ├── PRD01-SUB22-NOTIFICATIONS-EVENTS.md
│   │   │   ├── PRD01-SUB23-API-GATEWAY-AND-DOCUMENTATION.md
│   │   │   ├── PRD01-SUB24-INTEGRATION-CONNECTORS.md
│   │   │   └── PRD01-SUB25-LOCALIZATION.md
│   │   └── PRD01-MVP.md
│   └── SYSTEM ARCHITECHTURAL DOCUMENT.md
├── Makefile
├── package.json
├── packages
│   ├── nexus-accounting
│   │   └── docs
│   │       └── REQUIREMENTS.md
│   ├── nexus-analytics
│   │   └── REQUIREMENTS.md
│   ├── nexus-audit-log
│   │   ├── composer.json
│   │   ├── config
│   │   │   └── audit-logging.php
│   │   ├── database
│   │   │   └── migrations
│   │   │       └── 2025_11_12_000001_enhance_activity_log_table_for_audit.php
│   │   ├── IMPLEMENTATION_SUMMARY.md
│   │   ├── README.md
│   │   ├── src
│   │   │   ├── AuditLoggingServiceProvider.php
│   │   │   ├── Contracts
│   │   │   │   ├── AuditLogRepositoryContract.php
│   │   │   │   ├── LogExporterContract.php
│   │   │   │   └── LogFormatterContract.php
│   │   │   ├── Events
│   │   │   │   ├── ActivityLoggedEvent.php
│   │   │   │   └── LogRetentionExpiredEvent.php
│   │   │   ├── Jobs
│   │   │   │   └── LogActivityJob.php
│   │   │   ├── Listeners
│   │   │   │   └── NotifyHighValueActivityListener.php
│   │   │   ├── Models
│   │   │   │   └── AuditLog.php
│   │   │   ├── Observers
│   │   │   │   └── AuditObserver.php
│   │   │   ├── Repositories
│   │   │   │   └── DatabaseAuditLogRepository.php
│   │   │   ├── Services
│   │   │   │   ├── LogExporterService.php
│   │   │   │   └── LogFormatterService.php
│   │   │   └── Traits
│   │   │       ├── Auditable.php
│   │   │       └── LogsSystemActivity.php
│   │   ├── SUMMARY.md
│   │   ├── TESTING.md
│   │   └── tests
│   │       ├── bootstrap.php
│   │       ├── Feature
│   │       │   ├── AuditLogApiTest.php
│   │       │   └── IndependentTestabilityTest.php
│   │       ├── Pest.php
│   │       ├── TestCase.php
│   │       └── Unit
│   │           ├── AuditableTraitTest.php
│   │           └── LogFormatterServiceTest.php
│   ├── nexus-backoffice
│   │   ├── CHANGELOG.md
│   │   ├── composer.json
│   │   ├── config
│   │   │   └── backoffice.php
│   │   ├── CONTRIBUTING.md
│   │   ├── database
│   │   │   ├── factories
│   │   │   │   ├── CompanyFactory.php
│   │   │   │   ├── DepartmentFactory.php
│   │   │   │   ├── OfficeFactory.php
│   │   │   │   ├── OfficeTypeFactory.php
│   │   │   │   ├── PositionFactory.php
│   │   │   │   ├── StaffFactory.php
│   │   │   │   ├── StaffTransferFactory.php
│   │   │   │   ├── UnitFactory.php
│   │   │   │   └── UnitGroupFactory.php
│   │   │   └── migrations
│   │   │       ├── 2025_01_01_000001_create_backoffice_companies_table.php
│   │   │       ├── 2025_01_01_000002_create_backoffice_office_types_table.php
│   │   │       ├── 2025_01_01_000003_create_backoffice_offices_table.php
│   │   │       ├── 2025_01_01_000004_create_backoffice_departments_table.php
│   │   │       ├── 2025_01_01_000005_create_backoffice_unit_groups_table.php
│   │   │       ├── 2025_01_01_000006_create_backoffice_positions_table.php
│   │   │       ├── 2025_01_01_000007_create_backoffice_units_table.php
│   │   │       ├── 2025_01_01_000008_create_backoffice_staff_table.php
│   │   │       ├── 2025_01_01_000009_create_backoffice_office_office_type_table.php
│   │   │       ├── 2025_01_01_000010_create_backoffice_staff_unit_table.php
│   │   │       └── 2025_01_01_000011_create_backoffice_staff_transfers_table.php
│   │   ├── docs
│   │   │   ├── configuration.md
│   │   │   ├── examples.md
│   │   │   ├── factories.md
│   │   │   ├── installation.md
│   │   │   ├── models.md
│   │   │   ├── organizational-chart.md
│   │   │   ├── positions.md
│   │   │   ├── README.md
│   │   │   ├── REQUIREMENTS.md
│   │   │   ├── resignation.md
│   │   │   └── staff-transfers.md
│   │   ├── LICENSE.md
│   │   ├── PACKAGE_STRUCTURE.md
│   │   ├── phpstan.neon
│   │   ├── phpunit.xml
│   │   ├── README.md
│   │   ├── REFACTORING-COMPLETION.md
│   │   ├── REFACTORING.md
│   │   ├── run-tests.sh
│   │   ├── src
│   │   │   ├── BackofficeServiceProvider.php
│   │   │   ├── Casts
│   │   │   │   ├── FullName.php
│   │   │   │   └── HierarchyPath.php
│   │   │   ├── Contracts
│   │   │   │   ├── AuditContract.php
│   │   │   │   ├── NotificationContract.php
│   │   │   │   └── UserProviderContract.php
│   │   │   ├── Enums
│   │   │   │   ├── OfficeTypeStatus.php
│   │   │   │   ├── PositionType.php
│   │   │   │   ├── StaffStatus.php
│   │   │   │   └── StaffTransferStatus.php
│   │   │   ├── Exceptions
│   │   │   │   ├── CircularReferenceException.php
│   │   │   │   ├── InvalidAssignmentException.php
│   │   │   │   ├── InvalidResignationException.php
│   │   │   │   └── InvalidTransferException.php
│   │   │   ├── Helpers
│   │   │   │   ├── OrganizationalChart.php
│   │   │   │   └── StaffTransferHelper.php
│   │   │   ├── Models
│   │   │   │   ├── Company.php
│   │   │   │   ├── Department.php
│   │   │   │   ├── Office.php
│   │   │   │   ├── OfficeType.php
│   │   │   │   ├── Position.php
│   │   │   │   ├── Staff.php
│   │   │   │   ├── StaffTransfer.php
│   │   │   │   ├── UnitGroup.php
│   │   │   │   └── Unit.php
│   │   │   ├── Observers
│   │   │   │   ├── CompanyObserver.php
│   │   │   │   ├── DepartmentObserver.php
│   │   │   │   ├── OfficeObserver.php
│   │   │   │   ├── StaffObserver.php
│   │   │   │   └── StaffTransferObserver.php
│   │   │   ├── Policies
│   │   │   │   ├── CompanyPolicy.php
│   │   │   │   ├── DepartmentPolicy.php
│   │   │   │   ├── OfficePolicy.php
│   │   │   │   ├── PositionPolicy.php
│   │   │   │   ├── StaffPolicy.php
│   │   │   │   └── StaffTransferPolicy.php
│   │   │   └── Traits
│   │   │       └── HasHierarchy.php
│   │   ├── TESTING.md
│   │   └── tests
│   │       ├── Feature
│   │       │   ├── CompanyTest.php
│   │       │   ├── DepartmentTest.php
│   │       │   ├── OfficeTest.php
│   │       │   ├── OrganizationalChartTest.php
│   │       │   ├── PositionTest.php
│   │       │   ├── ProcessResignationsCommandTest.php
│   │       │   ├── StaffResignationTest.php
│   │       │   ├── StaffTest.php
│   │       │   ├── StaffTransferFeatureTest.php
│   │       │   └── StaffTransferTest.php
│   │       ├── TestCase.php
│   │       └── Unit
│   │           ├── CompanyObserverTest.php
│   │           ├── EnumsTest.php
│   │           ├── FullNameCastTest.php
│   │           ├── HasHierarchyTraitTest.php
│   │           ├── HierarchyPathCastTest.php
│   │           └── StaffTransferPolicyTest.php
│   ├── nexus-crm
│   │   └── REQUIREMENTS.md
│   ├── 
│   │   └── REQUIREMENTS.md
│   ├── nexus-hrm
│   │   └── docs
│   │       └── REQUIREMENTS.md
│   ├── nexus-inventory
│   │   ├── composer.json
│   │   ├── config
│   │   │   └── inventory-management.php
│   │   ├── database
│   │   │   ├── factories
│   │   │   │   ├── ItemFactory.php
│   │   │   │   ├── LocationFactory.php
│   │   │   │   ├── StockFactory.php
│   │   │   │   ├── StockMovementFactory.php
│   │   │   │   └── Transactions
│   │   │   │       ├── OpeningBalanceFactory.php
│   │   │   │       ├── StockAdjustmentFactory.php
│   │   │   │       ├── StockInFactory.php
│   │   │   │       ├── StockOutFactory.php
│   │   │   │       └── StockTransferFactory.php
│   │   │   └── migrations
│   │   │       ├── 2025_11_02_000001_create_items_table.php
│   │   │       ├── 2025_11_02_000002_create_locations_table.php
│   │   │       ├── 2025_11_02_000003_create_stocks_table.php
│   │   │       ├── 2025_11_02_000004_create_stock_movements_table.php
│   │   │       ├── 2025_11_02_000005_create_opening_balances_table.php
│   │   │       ├── 2025_11_02_000006_create_stock_ins_table.php
│   │   │       ├── 2025_11_02_000007_create_stock_outs_table.php
│   │   │       ├── 2025_11_02_000008_create_stock_transfers_table.php
│   │   │       └── 2025_11_02_000009_create_stock_adjustments_table.php
│   │   ├── docs
│   │   │   └── progress-checklist.md
│   │   ├── PRD.md
│   │   ├── README.md
│   │   └── src
│   │       ├── Concerns
│   │       │   ├── IsItem.php
│   │       │   └── IsLocation.php
│   │       ├── Contracts
│   │       │   ├── Item.php
│   │       │   └── Location.php
│   │       ├── Exceptions
│   │       │   └── InsufficientStockException.php
│   │       ├── Facades
│   │       │   └── Inventory.php
│   │       ├── InventoryServiceProvider.php
│   │       ├── Models
│   │       │   ├── Item.php
│   │       │   ├── Location.php
│   │       │   ├── StockMovement.php
│   │       │   ├── Stock.php
│   │       │   └── Transactions
│   │       │       ├── BaseTransaction.php
│   │       │       ├── OpeningBalance.php
│   │       │       ├── StockAdjustment.php
│   │       │       ├── StockIn.php
│   │       │       ├── StockOut.php
│   │       │       └── StockTransfer.php
│   │       └── Services
│   │           └── InventoryService.php
│   ├── nexus-manufacturing
│   │   ├── composer.json
│   │   ├── config
│   │   │   └── manufacturing.php
│   │   ├── database
│   │   │   └── migrations
│   │   │       ├── 2025_01_01_000001_create_manufacturing_products_table.php
│   │   │       ├── 2025_01_01_000002_create_manufacturing_bill_of_materials_table.php
│   │   │       ├── 2025_01_01_000003_create_manufacturing_bom_items_table.php
│   │   │       ├── 2025_01_01_000004_create_manufacturing_work_centers_table.php
│   │   │       ├── 2025_01_01_000005_create_manufacturing_routings_table.php
│   │   │       ├── 2025_01_01_000006_create_manufacturing_routing_operations_table.php
│   │   │       ├── 2025_01_01_000007_create_manufacturing_work_orders_table.php
│   │   │       ├── 2025_01_01_000008_create_manufacturing_material_allocations_table.php
│   │   │       ├── 2025_01_01_000009_create_manufacturing_production_reports_table.php
│   │   │       ├── 2025_01_01_000010_create_manufacturing_operation_logs_table.php
│   │   │       ├── 2025_01_01_000011_create_manufacturing_inspection_plans_table.php
│   │   │       ├── 2025_01_01_000012_create_manufacturing_inspection_characteristics_table.php
│   │   │       ├── 2025_01_01_000013_create_manufacturing_quality_inspections_table.php
│   │   │       ├── 2025_01_01_000014_create_manufacturing_inspection_measurements_table.php
│   │   │       ├── 2025_01_01_000015_create_manufacturing_batch_genealogy_table.php
│   │   │       ├── 2025_01_01_000016_create_manufacturing_batch_genealogy_materials_table.php
│   │   │       └── 2025_01_01_000017_create_manufacturing_production_costing_table.php
│   │   ├── FIXES_APPLIED.md
│   │   ├── IMPLEMENTATION_COMPLETE.md
│   │   ├── README.md
│   │   ├── REQUIREMENTS.md
│   │   ├── src
│   │   │   ├── Contracts
│   │   │   │   ├── BillOfMaterialRepositoryContract.php
│   │   │   │   ├── BOMExplosionServiceContract.php
│   │   │   │   ├── MaterialManagementServiceContract.php
│   │   │   │   ├── ProductionCostingServiceContract.php
│   │   │   │   ├── ProductionExecutionServiceContract.php
│   │   │   │   ├── ProductionReportRepositoryContract.php
│   │   │   │   ├── QualityInspectionRepositoryContract.php
│   │   │   │   ├── QualityManagementServiceContract.php
│   │   │   │   ├── TraceabilityServiceContract.php
│   │   │   │   ├── WorkOrderPlanningServiceContract.php
│   │   │   │   └── WorkOrderRepositoryContract.php
│   │   │   ├── Enums
│   │   │   │   ├── BOMStatus.php
│   │   │   │   ├── DispositionType.php
│   │   │   │   ├── InspectionResult.php
│   │   │   │   ├── ProductType.php
│   │   │   │   └── WorkOrderStatus.php
│   │   │   ├── Events
│   │   │   │   ├── MaterialConsumed.php
│   │   │   │   ├── ProductionReported.php
│   │   │   │   ├── WorkOrderCompleted.php
│   │   │   │   └── WorkOrderCreated.php
│   │   │   ├── ManufacturingServiceProvider.php
│   │   │   ├── Models
│   │   │   │   ├── BatchGenealogy.php
│   │   │   │   ├── BillOfMaterial.php
│   │   │   │   ├── BOMItem.php
│   │   │   │   ├── InspectionCharacteristic.php
│   │   │   │   ├── InspectionMeasurement.php
│   │   │   │   ├── InspectionPlan.php
│   │   │   │   ├── MaterialAllocation.php
│   │   │   │   ├── OperationLog.php
│   │   │   │   ├── ProductionCosting.php
│   │   │   │   ├── ProductionReport.php
│   │   │   │   ├── Product.php
│   │   │   │   ├── QualityInspection.php
│   │   │   │   ├── RoutingOperation.php
│   │   │   │   ├── Routing.php
│   │   │   │   ├── WorkCenter.php
│   │   │   │   └── WorkOrder.php
│   │   │   ├── Repositories
│   │   │   │   ├── BillOfMaterialRepository.php
│   │   │   │   ├── ProductionReportRepository.php
│   │   │   │   ├── QualityInspectionRepository.php
│   │   │   │   └── WorkOrderRepository.php
│   │   │   ├── Services
│   │   │   │   ├── BOMExplosionService.php
│   │   │   │   ├── MaterialManagementService.php
│   │   │   │   ├── ProductionCostingService.php
│   │   │   │   ├── ProductionExecutionService.php
│   │   │   │   ├── QualityManagementService.php
│   │   │   │   ├── TraceabilityService.php
│   │   │   │   └── WorkOrderPlanningService.php
│   │   │   └── Workflows
│   │   │       └── WorkOrderWorkflow.php
│   │   └── tests
│   │       ├── Feature
│   │       │   ├── ProductionCostingTest.php
│   │       │   └── WorkOrderLifecycleTest.php
│   │       ├── Pest.php
│   │       ├── TestCase.php
│   │       └── Unit
│   │           ├── BOMBusinessRulesTest.php
│   │           ├── BOMExplosionServiceTest.php
│   │           ├── QualityBusinessRulesTest.php
│   │           └── WorkOrderBusinessRulesTest.php
│   ├── nexus-marketing
│   │   ├── README.md
│   │   └── REQUIREMENTS.md
│   ├── nexus-payroll
│   │   └── docs
│   │       └── REQUIREMENTS.md
│   ├── nexus-procurement
│   │   └── REQUIREMENTS.md
│   ├── nexus-project-management
│   │   └── REQUIREMENTS.md
│   ├── nexus-scm
│   │   ├── README.md
│   │   ├── REQUIREMENTS.legacy.md
│   │   └── REQUIREMENTS.md
│   ├── nexus-sequencing
│   │   ├── ADR-001-CORE-ADAPTER-SEPARATION.md
│   │   ├── composer.json
│   │   ├── config
│   │   │   └── serial-numbering.php
│   │   ├── database
│   │   │   ├── migrations
│   │   │   │   ├── 2025_11_12_000001_create_serial_number_sequences_table.php
│   │   │   │   ├── 2025_11_12_000002_create_serial_number_logs_table.php
│   │   │   │   └── 2025_11_14_000001_add_step_size_reset_limit_to_sequences.php
│   │   │   └── seeders
│   │   │       └── DefaultSequenceSeeder.php
│   │   ├── IMPLEMENTATION_SUMMARY.md
│   │   ├── PHASE_2.1_COMPLETION_REPORT.md
│   │   ├── PHASE_2.2_COMPLETION_REPORT.md
│   │   ├── phpstan-core-purity.neon
│   │   ├── QUICK_START.md
│   │   ├── README.md
│   │   ├── rector-core.php
│   │   ├── REQUIREMENTS.md
│   │   ├── REQUIREMENTS_REFACTORING.md
│   │   ├── routes
│   │   │   └── api.php
│   │   ├── scripts
│   │   │   └── verify-core.php
│   │   ├── src
│   │   │   ├── Actions
│   │   │   │   ├── GenerateSerialNumberAction.php
│   │   │   │   ├── ManagePatternTemplatesAction.php
│   │   │   │   ├── OverrideSerialNumberAction.php
│   │   │   │   ├── PreviewSerialNumberAction.php
│   │   │   │   ├── RegisterCustomVariableAction.php
│   │   │   │   ├── ResetSequenceAction.php
│   │   │   │   └── ValidatePatternAction.php
│   │   │   ├── Adapters
│   │   │   │   └── Laravel
│   │   │   │       └── EloquentCounterRepository.php
│   │   │   ├── Contracts
│   │   │   │   ├── PatternParserContract.php
│   │   │   │   └── SequenceRepositoryContract.php
│   │   │   ├── Core
│   │   │   │   ├── Contracts
│   │   │   │   │   ├── ConditionalProcessorInterface.php
│   │   │   │   │   ├── CounterRepositoryInterface.php
│   │   │   │   │   ├── CustomVariableInterface.php
│   │   │   │   │   ├── PatternEvaluatorInterface.php
│   │   │   │   │   ├── PatternTemplateInterface.php
│   │   │   │   │   ├── ResetStrategyInterface.php
│   │   │   │   │   ├── ValidationResult.php
│   │   │   │   │   └── VariableRegistryInterface.php
│   │   │   │   ├── Engine
│   │   │   │   │   ├── BasicConditionalProcessor.php
│   │   │   │   │   ├── RegexPatternEvaluator.php
│   │   │   │   │   ├── TemplateRegistry.php
│   │   │   │   │   └── VariableRegistry.php
│   │   │   │   ├── Services
│   │   │   │   │   ├── DefaultResetStrategy.php
│   │   │   │   │   ├── GenerationService.php
│   │   │   │   │   └── ValidationService.php
│   │   │   │   ├── Templates
│   │   │   │   │   ├── AbstractPatternTemplate.php
│   │   │   │   │   ├── Financial
│   │   │   │   │   │   ├── InvoiceTemplate.php
│   │   │   │   │   │   └── QuoteTemplate.php
│   │   │   │   │   ├── HR
│   │   │   │   │   │   └── EmployeeIdTemplate.php
│   │   │   │   │   ├── Inventory
│   │   │   │   │   │   └── StockTransferTemplate.php
│   │   │   │   │   └── Procurement
│   │   │   │   │       └── PurchaseOrderTemplate.php
│   │   │   │   ├── ValueObjects
│   │   │   │   │   ├── CounterState.php
│   │   │   │   │   ├── GeneratedNumber.php
│   │   │   │   │   ├── GenerationContext.php
│   │   │   │   │   ├── PatternTemplate.php
│   │   │   │   │   ├── ResetPeriod.php
│   │   │   │   │   └── SequenceConfig.php
│   │   │   │   └── Variables
│   │   │   │       ├── CustomerTierVariable.php
│   │   │   │       ├── DepartmentVariable.php
│   │   │   │       └── ProjectCodeVariable.php
│   │   │   ├── Enums
│   │   │   │   └── ResetPeriod.php
│   │   │   ├── Events
│   │   │   │   ├── SequenceGeneratedEvent.php
│   │   │   │   ├── SequenceOverriddenEvent.php
│   │   │   │   └── SequenceResetEvent.php
│   │   │   ├── Examples
│   │   │   │   ├── EnhancedPreviewExample.php
│   │   │   │   └── ExampleModels.php
│   │   │   ├── Exceptions
│   │   │   │   ├── DuplicateNumberException.php
│   │   │   │   ├── InvalidPatternException.php
│   │   │   │   └── SequenceNotFoundException.php
│   │   │   ├── Http
│   │   │   │   ├── Controllers
│   │   │   │   │   └── SequenceController.php
│   │   │   │   ├── Middleware
│   │   │   │   │   └── InjectTenantContext.php
│   │   │   │   ├── Requests
│   │   │   │   │   ├── CreateSequenceRequest.php
│   │   │   │   │   └── UpdateSequenceRequest.php
│   │   │   │   └── Resources
│   │   │   │       ├── SequenceResource.php
│   │   │   │       └── SerialNumberLogResource.php
│   │   │   ├── Models
│   │   │   │   ├── Sequence.php
│   │   │   │   └── SerialNumberLog.php
│   │   │   ├── Policies
│   │   │   │   └── SequencePolicy.php
│   │   │   ├── Repositories
│   │   │   │   └── DatabaseSequenceRepository.php
│   │   │   ├── SequencingServiceProvider.php
│   │   │   ├── Services
│   │   │   │   └── PatternParserService.php
│   │   │   └── Traits
│   │   │       └── HasSequence.php
│   │   └── tests
│   │       ├── Integration
│   │       │   └── ActionIntegrationTest.php
│   │       ├── Pest.php
│   │       ├── TestCase.php
│   │       └── Unit
│   │           ├── Actions
│   │           │   └── PreviewSerialNumberActionTest.php
│   │           ├── Core
│   │           │   ├── Services
│   │           │   │   ├── GenerationServiceTest.php
│   │           │   │   └── ValidationServiceTest.php
│   │           │   └── ValueObjects
│   │           │       ├── CounterStateTest.php
│   │           │       ├── PatternTemplateTest.php
│   │           │       └── SequenceConfigTest.php
│   │           ├── PatternParserServiceTest.php
│   │           ├── SequenceModelTest.php
│   │           └── Traits
│   │               └── HasSequenceTest.php
│   ├── nexus-settings
│   │   ├── composer.json
│   │   ├── config
│   │   │   └── settings-management.php
│   │   ├── database
│   │   │   ├── migrations
│   │   │   │   ├── 2025_11_12_000001_create_settings_table.php
│   │   │   │   └── 2025_11_12_000002_create_settings_history_table.php
│   │   │   └── seeders
│   │   │       └── DefaultSettingsSeeder.php
│   │   ├── IMPLEMENTATION_SUMMARY.md
│   │   ├── README.md
│   │   ├── routes
│   │   │   └── api.php
│   │   ├── src
│   │   │   ├── Console
│   │   │   │   └── Commands
│   │   │   │       └── WarmSettingsCacheCommand.php
│   │   │   ├── Contracts
│   │   │   │   ├── SettingsRepositoryContract.php
│   │   │   │   └── SettingsServiceContract.php
│   │   │   ├── Events
│   │   │   │   ├── CacheInvalidatedEvent.php
│   │   │   │   ├── SettingCreatedEvent.php
│   │   │   │   └── SettingUpdatedEvent.php
│   │   │   ├── Facades
│   │   │   │   └── Settings.php
│   │   │   ├── Http
│   │   │   │   ├── Controllers
│   │   │   │   │   └── SettingsController.php
│   │   │   │   ├── Requests
│   │   │   │   │   ├── BulkUpdateSettingsRequest.php
│   │   │   │   │   ├── CreateSettingRequest.php
│   │   │   │   │   ├── ImportSettingsRequest.php
│   │   │   │   │   └── UpdateSettingRequest.php
│   │   │   │   └── Resources
│   │   │   │       └── SettingResource.php
│   │   │   ├── Models
│   │   │   │   ├── SettingHistory.php
│   │   │   │   └── Setting.php
│   │   │   ├── Policies
│   │   │   │   └── SettingPolicy.php
│   │   │   ├── Repositories
│   │   │   │   └── DatabaseSettingsRepository.php
│   │   │   ├── Services
│   │   │   │   └── SettingsService.php
│   │   │   └── SettingsServiceProvider.php
│   │   └── tests
│   │       ├── Feature
│   │       │   ├── SettingsApiTest.php
│   │       │   └── SettingsHierarchyTest.php
│   │       ├── Pest.php
│   │       ├── README.md
│   │       ├── TestCase.php
│   │       └── Unit
│   │           └── SettingsServiceTest.php
│   ├── nexus-tenancy
│   │   ├── composer.json
│   │   ├── config
│   │   │   └── tenancy.php
│   │   ├── database
│   │   │   └── migrations
│   │   │       └── 0001_01_01_000000_create_tenants_table.php
│   │   ├── README.md
│   │   └── src
│   │       ├── Actions
│   │       │   ├── ActivateTenantAction.php
│   │       │   ├── ArchiveTenantAction.php
│   │       │   ├── CreateTenantAction.php
│   │       │   ├── DeleteTenantAction.php
│   │       │   ├── EndImpersonationAction.php
│   │       │   ├── StartImpersonationAction.php
│   │       │   ├── SuspendTenantAction.php
│   │       │   └── UpdateTenantAction.php
│   │       ├── Contracts
│   │       │   ├── TenantManagerContract.php
│   │       │   └── TenantRepositoryContract.php
│   │       ├── Enums
│   │       │   └── TenantStatus.php
│   │       ├── Events
│   │       │   ├── TenantActivatedEvent.php
│   │       │   ├── TenantArchivedEvent.php
│   │       │   ├── TenantCreatedEvent.php
│   │       │   ├── TenantDeletedEvent.php
│   │       │   ├── TenantImpersonationEndedEvent.php
│   │       │   ├── TenantImpersonationStartedEvent.php
│   │       │   ├── TenantSuspendedEvent.php
│   │       │   └── TenantUpdatedEvent.php
│   │       ├── Http
│   │       │   ├── Controllers
│   │       │   │   └── TenantController.php
│   │       │   ├── Middleware
│   │       │   │   ├── EnsureTenantActive.php
│   │       │   │   └── IdentifyTenant.php
│   │       │   ├── Requests
│   │       │   │   ├── StoreTenantRequest.php
│   │       │   │   └── UpdateTenantRequest.php
│   │       │   └── Resources
│   │       │       └── TenantResource.php
│   │       ├── Listeners
│   │       │   └── InitializeTenantDataListener.php
│   │       ├── Models
│   │       │   └── Tenant.php
│   │       ├── Policies
│   │       │   └── TenantPolicy.php
│   │       ├── Repositories
│   │       │   └── TenantRepository.php
│   │       ├── Scopes
│   │       │   └── TenantScope.php
│   │       ├── Services
│   │       │   ├── ImpersonationService.php
│   │       │   └── TenantManager.php
│   │       ├── TenancyServiceProvider.php
│   │       └── Traits
│   │           └── BelongsToTenant.php
│   ├── nexus-uom
│   │   ├── CHANGELOG.md
│   │   ├── composer.json
│   │   ├── config
│   │   │   └── uom.php
│   │   ├── coverage.xml
│   │   ├── database
│   │   │   ├── factories
│   │   │   │   ├── UomAliasFactory.php
│   │   │   │   ├── UomCompoundComponentFactory.php
│   │   │   │   ├── UomCompoundUnitFactory.php
│   │   │   │   ├── UomConversionFactory.php
│   │   │   │   ├── UomConversionLogFactory.php
│   │   │   │   ├── UomCustomConversionFactory.php
│   │   │   │   ├── UomCustomUnitFactory.php
│   │   │   │   ├── UomItemFactory.php
│   │   │   │   ├── UomItemPackagingFactory.php
│   │   │   │   ├── UomPackagingFactory.php
│   │   │   │   ├── UomTypeFactory.php
│   │   │   │   ├── UomUnitFactory.php
│   │   │   │   └── UomUnitGroupFactory.php
│   │   │   ├── migrations
│   │   │   │   └── create_uom_tables.php
│   │   │   └── seeders
│   │   │       └── UomDatabaseSeeder.php
│   │   ├── docs
│   │   │   ├── contributing.md
│   │   │   ├── packagist-release-notes.md
│   │   │   ├── progress-checklist.md
│   │   │   ├── setup-and-usage.md
│   │   │   └── upgrade-guide.md
│   │   ├── phpunit.xml
│   │   ├── PRD.md
│   │   ├── README.md
│   │   ├── src
│   │   │   ├── Console
│   │   │   │   └── Commands
│   │   │   │       ├── UomConvertCommand.php
│   │   │   │       ├── UomListUnitsCommand.php
│   │   │   │       └── UomSeedCommand.php
│   │   │   ├── Contracts
│   │   │   │   ├── AliasResolver.php
│   │   │   │   ├── CompoundUnitConverter.php
│   │   │   │   ├── CustomUnitRegistrar.php
│   │   │   │   ├── PackagingCalculator.php
│   │   │   │   └── UnitConverter.php
│   │   │   ├── Exceptions
│   │   │   │   └── ConversionException.php
│   │   │   ├── Models
│   │   │   │   ├── UomAlias.php
│   │   │   │   ├── UomCompoundComponent.php
│   │   │   │   ├── UomCompoundUnit.php
│   │   │   │   ├── UomConversionLog.php
│   │   │   │   ├── UomConversion.php
│   │   │   │   ├── UomCustomConversion.php
│   │   │   │   ├── UomCustomUnit.php
│   │   │   │   ├── UomItemPackaging.php
│   │   │   │   ├── UomItem.php
│   │   │   │   ├── UomPackaging.php
│   │   │   │   ├── UomType.php
│   │   │   │   ├── UomUnitGroup.php
│   │   │   │   └── UomUnit.php
│   │   │   ├── Services
│   │   │   │   ├── DefaultAliasResolver.php
│   │   │   │   ├── DefaultCompoundUnitConverter.php
│   │   │   │   ├── DefaultCustomUnitRegistrar.php
│   │   │   │   ├── DefaultPackagingCalculator.php
│   │   │   │   └── DefaultUnitConverter.php
│   │   │   ├── Support
│   │   │   │   └── UnitConversion.php
│   │   │   └── UomServiceProvider.php
│   │   └── tests
│   │       ├── Feature
│   │       │   ├── Console
│   │       │   │   ├── UomConvertCommandTest.php
│   │       │   │   ├── UomListUnitsCommandTest.php
│   │       │   │   └── UomSeedCommandTest.php
│   │       │   ├── ConversionFlowTest.php
│   │       │   └── CustomUnitRegistrationTest.php
│   │       ├── TestCase.php
│   │       └── Unit
│   │           ├── Exceptions
│   │           │   └── ConversionExceptionTest.php
│   │           ├── MigrationsTest.php
│   │           ├── Models
│   │           │   ├── UomModelRelationsTest.php
│   │           │   └── UomUnitTest.php
│   │           ├── ServiceProviderTest.php
│   │           ├── Services
│   │           │   ├── DefaultAliasResolverTest.php
│   │           │   ├── DefaultCompoundUnitConverterTest.php
│   │           │   ├── DefaultCustomUnitRegistrarTest.php
│   │           │   ├── DefaultPackagingCalculatorTest.php
│   │           │   └── DefaultUnitConverterTest.php
│   │           └── Support
│   │               └── UnitConversionTest.php
│   └── nexus-workflow
│       ├── composer.json
│       ├── config
│       │   └── workflow.php
│       ├── database
│       │   └── migrations
│       │       ├── 2025_11_14_000002_create_workflow_definitions_table.php
│       │       ├── 2025_11_14_000003_create_workflow_instances_table.php
│       │       ├── 2025_11_14_000004_create_workflow_transitions_table.php
│       │       ├── 2025_11_14_000005_create_user_tasks_table.php
│       │       ├── 2025_11_14_000006_create_approver_groups_table.php
│       │       └── 2025_11_14_000007_create_approver_group_members_table.php
│       ├── docs
│       │   └── REQUIREMENTS-V3.md
│       ├── PHASE_1_COMPLETION.md
│       ├── PHASE_2_PLAN.md
│       ├── README.md
│       ├── src
│       │   ├── Adapters
│       │   │   └── Laravel
│       │   │       ├── Services
│       │   │       │   └── WorkflowManager.php
│       │   │       └── Traits
│       │   │           └── HasWorkflow.php
│       │   ├── Console
│       │   │   └── Commands
│       │   │       ├── WorkflowActivateCommand.php
│       │   │       ├── WorkflowDeactivateCommand.php
│       │   │       ├── WorkflowExportCommand.php
│       │   │       ├── WorkflowImportCommand.php
│       │   │       ├── WorkflowListCommand.php
│       │   │       └── WorkflowShowCommand.php
│       │   ├── Contracts
│       │   │   └── ApprovalStrategyContract.php
│       │   ├── Core
│       │   │   ├── Contracts
│       │   │   │   └── WorkflowEngineContract.php
│       │   │   ├── DTOs
│       │   │   │   ├── TransitionResult.php
│       │   │   │   ├── WorkflowDefinition.php
│       │   │   │   └── WorkflowInstance.php
│       │   │   └── Services
│       │   │       └── StateTransitionService.php
│       │   ├── Engines
│       │   │   └── DatabaseWorkflowEngine.php
│       │   ├── Factories
│       │   │   └── ApprovalStrategyFactory.php
│       │   ├── Models
│       │   │   ├── ApproverGroupMember.php
│       │   │   ├── ApproverGroup.php
│       │   │   ├── UserTask.php
│       │   │   ├── WorkflowDefinition.php
│       │   │   ├── WorkflowInstance.php
│       │   │   └── WorkflowTransition.php
│       │   ├── Services
│       │   │   ├── ApproverGroupService.php
│       │   │   ├── UserTaskService.php
│       │   │   └── WorkflowDefinitionService.php
│       │   ├── Strategies
│       │   │   ├── AnyApprovalStrategy.php
│       │   │   ├── ParallelApprovalStrategy.php
│       │   │   ├── QuorumApprovalStrategy.php
│       │   │   ├── SequentialApprovalStrategy.php
│       │   │   └── WeightedApprovalStrategy.php
│       │   ├── Traits
│       │   │   └── HasDatabaseWorkflow.php
│       │   └── WorkflowServiceProvider.php
│       └── tests
│           ├── Feature
│           │   ├── ApproverGroupServiceTest.php
│           │   ├── Level1StateMachineTest.php
│           │   ├── Phase2IntegrationTest.php
│           │   ├── TenantWorkflowIntegrationTest.php
│           │   ├── UserTaskServiceTest.php
│           │   └── WorkflowDefinitionServiceTest.php
│           ├── Pest.php
│           ├── Support
│           │   └── Post.php
│           ├── TestCase.php
│           └── Unit
│               └── StateTransitionTest.php
├── phpunit.xml
├── README.md
├── REQUIREMENTS.md
├── routes
│   ├── api-backoffice.php
│   ├── audit-log.php
│   └── console.php
├── src
│   ├── Actions
│   │   ├── Action.php
│   │   ├── AuditLog
│   │   │   ├── ExportAuditLogsAction.php
│   │   │   ├── GetAuditLogStatisticsAction.php
│   │   │   ├── PurgeExpiredAuditLogsAction.php
│   │   │   ├── SearchAuditLogsAction.php
│   │   │   └── ShowAuditLogAction.php
│   │   ├── Auth
│   │   │   ├── LoginAction.php
│   │   │   ├── LogoutAction.php
│   │   │   ├── RegisterUserAction.php
│   │   │   ├── RequestPasswordResetAction.php
│   │   │   └── ResetPasswordAction.php
│   │   ├── Backoffice
│   │   │   ├── CreateCompanyAction.php
│   │   │   ├── CreateDepartmentAction.php
│   │   │   ├── CreateOfficeAction.php
│   │   │   ├── CreateStaffAction.php
│   │   │   ├── ExportOrganizationalDataAction.php
│   │   │   ├── GenerateOrganizationalChartAction.php
│   │   │   ├── GenerateTransferStatisticsAction.php
│   │   │   ├── ProcessResignationsAction.php
│   │   │   ├── ProcessStaffTransfersAction.php
│   │   │   ├── TransferStaffAction.php
│   │   │   └── UpdateCompanyHierarchyAction.php
│   │   ├── Permission
│   │   │   ├── AssignRoleToUserAction.php
│   │   │   ├── CreateRoleAction.php
│   │   │   └── RevokeRoleFromUserAction.php
│   │   ├── UnitOfMeasure
│   │   │   ├── ConvertQuantityAction.php
│   │   │   ├── GetCompatibleUomsAction.php
│   │   │   └── ValidateUomCompatibilityAction.php
│   │   └── User
│   │       └── SuspendUserAction.php
│   ├── Console
│   │   └── Commands
│   │       ├── Backoffice
│   │       │   ├── CreateOfficeTypesCommand.php
│   │       │   ├── InstallBackofficeCommand.php
│   │       │   ├── ProcessResignationsCommand.php
│   │       │   └── ProcessStaffTransfersCommand.php
│   │       └── Tenant
│   │           ├── CreateTenantCommand.php
│   │           └── ListTenantsCommand.php
│   ├── Enums
│   │   └── UomCategory.php
│   ├── ErpServiceProvider.php
│   ├── Events
│   │   ├── Auth
│   │   │   ├── LoginFailedEvent.php
│   │   │   ├── PasswordResetEvent.php
│   │   │   ├── PasswordResetRequestedEvent.php
│   │   │   ├── UserLoggedInEvent.php
│   │   │   ├── UserLoggedOutEvent.php
│   │   │   ├── UserRegisteredEvent.php
│   │   │   └── UserSuspendedEvent.php
│   │   └── Permission
│   │       ├── RoleAssignedEvent.php
│   │       ├── RoleCreatedEvent.php
│   │       └── RoleRevokedEvent.php
│   ├── Exceptions
│   │   ├── AccountLockedException.php
│   │   └── UnitOfMeasure
│   │       ├── IncompatibleUomException.php
│   │       ├── InvalidQuantityException.php
│   │       ├── UomConversionException.php
│   │       └── UomNotFoundException.php
│   ├── Http
│   │   ├── Controllers
│   │   │   ├── Api
│   │   │   │   ├── Backoffice
│   │   │   │   │   ├── CompanyController.php
│   │   │   │   │   ├── DepartmentController.php
│   │   │   │   │   ├── OfficeController.php
│   │   │   │   │   └── StaffController.php
│   │   │   │   └── V1
│   │   │   │       ├── Admin
│   │   │   │       │   └── UserManagementController.php
│   │   │   │       ├── AuthController.php
│   │   │   │       └── TenantController.php
│   │   │   └── Controller.php
│   │   ├── Middleware
│   │   │   ├── Api
│   │   │   │   ├── ApiResponseMiddleware.php
│   │   │   │   └── CompanyContextMiddleware.php
│   │   │   ├── EnsureAccountNotLocked.php
│   │   │   └── ValidateSanctumToken.php
│   │   ├── Requests
│   │   │   ├── Api
│   │   │   │   └── Backoffice
│   │   │   │       ├── StaffTransferRequest.php
│   │   │   │       ├── StoreCompanyRequest.php
│   │   │   │       ├── StoreStaffRequest.php
│   │   │   │       └── UpdateCompanyRequest.php
│   │   │   ├── Auth
│   │   │   │   ├── ForgotPasswordRequest.php
│   │   │   │   ├── LoginRequest.php
│   │   │   │   ├── RegisterRequest.php
│   │   │   │   └── ResetPasswordRequest.php
│   │   │   ├── StoreTenantRequest.php
│   │   │   └── UpdateTenantRequest.php
│   │   └── Resources
│   │       ├── Api
│   │       │   └── Backoffice
│   │       │       ├── CompanyResource.php
│   │       │       ├── DepartmentResource.php
│   │       │       ├── OfficeResource.php
│   │       │       ├── StaffResource.php
│   │       │       └── StaffTransferResource.php
│   │       ├── Auth
│   │       │   ├── TokenResource.php
│   │       │   └── UserResource.php
│   │       └── TenantResource.php
│   ├── Listeners
│   │   └── Auth
│   │       ├── LogAuthenticationFailureListener.php
│   │       └── LogAuthenticationSuccessListener.php
│   ├── Models
│   │   ├── Tenant.php
│   │   ├── Uom.php
│   │   └── User.php
│   ├── Policies
│   │   ├── RolePolicy.php
│   │   └── UserPolicy.php
│   ├── Providers
│   │   ├── AppServiceProvider.php
│   │   ├── AuthServiceProvider.php
│   │   ├── BackofficeServiceProvider.php
│   │   ├── EventServiceProvider.php
│   │   ├── LoggingServiceProvider.php
│   │   ├── PermissionServiceProvider.php
│   │   └── SearchServiceProvider.php
│   ├── README-AUTHENTICATION.md
│   ├── Repositories
│   │   ├── DatabaseUomRepository.php
│   │   └── UserRepository.php
│   ├── Services
│   │   └── UnitOfMeasure
│   │       └── UomConversionService.php
│   ├── Settings
│   │   └── docs
│   │       └── REQUIREMENTS.md
│   └── Support
│       ├── Contracts
│       │   ├── ActivityLoggerContract.php
│       │   ├── PermissionServiceContract.php
│       │   ├── RepositoryContract.php
│       │   ├── SearchServiceContract.php
│       │   ├── TenantManagerContract.php
│       │   ├── TenantRepositoryContract.php
│       │   ├── TokenServiceContract.php
│       │   ├── UomRepositoryContract.php
│       │   └── UserRepositoryContract.php
│       ├── Helpers
│       │   └── tenant.php
│       ├── Services
│       │   ├── Auth
│       │   │   └── SanctumTokenService.php
│       │   ├── Logging
│       │   │   ├── SpatieActivityLoggerAdapter.php
│       │   │   └── SpatieActivityLogger.php
│       │   ├── Permission
│       │   │   └── SpatiePermissionService.php
│       │   └── Search
│       │       └── ScoutSearchService.php
│       └── Traits
│           ├── HasActivityLogging.php
│           ├── HasPermissions.php
│           ├── HasTokens.php
│           └── IsSearchable.php
├── tests
│   ├── Pest.php
│   ├── README.md
│   ├── run-tests.sh
│   ├── TestCase.php
│   └── Unit
│       └── Actions
│           └── Backoffice
│               ├── CreateCompanyActionTest.php
│               ├── CreateStaffActionTest.php
│               ├── ExportOrganizationalDataActionTest.php
│               └── ProcessStaffTransfersActionTest.php
├── tree.md
└── vite.config.js

383 directories, 951 filescd /workspaces/nexus-erp
git add packages/nexus-manufacturing/
git commit -m "fix(manufacturing): Fix namespace imports and method signatures"
git push origin feature/nexus-manufacturing-implementation
