---
plan: PRD01-SUB23-PLAN01 - API Gateway Foundation
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Laravel ERP Development Team
status: Planned
tags: [feature, api-gateway, routing, versioning, authentication, infrastructure]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan covers **API Gateway Foundation** for the API Gateway & Documentation module (PRD01-SUB23). It implements unified API routing, multi-version support (v1, v2, v3), request/response transformation, gateway authentication with token forwarding, and load balancing with service discovery.

## 1. Requirements & Constraints

**Requirements Addressed:**
- **FR-AG-001**: Provide unified RESTful API gateway with versioning support
- **FR-AG-002**: Support request routing to backend services
- **FR-AG-003**: Implement response aggregation for composite queries
- **FR-AG-004**: Multi-version API support (v1, v2, v3) via URL path
- **ARCH-AG-001**: Use Laravel Sanctum for API authentication
- **ARCH-AG-002**: Implement API versioning via URL path (/api/v1/, /api/v2/)
- **PR-AG-001**: API gateway routing must add < 10ms latency

**Security Constraints:**
- **SEC-001**: All API requests must be authenticated via Sanctum or API keys
- **SEC-002**: API keys must be encrypted at rest using Laravel encryption
- **SEC-003**: HTTPS required for all API endpoints in production

**Performance Constraints:**
- **CON-001**: Gateway routing latency must be < 10ms (99th percentile)
- **CON-002**: Support 10,000+ API requests per second with horizontal scaling
- **CON-003**: Response aggregation must complete within 500ms for up to 5 services

**Guidelines:**
- **GUD-001**: Use Laravel routing with route prefixes for versioning
- **GUD-002**: Implement middleware pipeline for request transformation
- **GUD-003**: Use Redis for caching service discovery and route mapping
- **GUD-004**: Follow RESTful conventions for all API endpoints

**Patterns:**
- **PAT-001**: Use Proxy pattern for routing requests to backend services
- **PAT-002**: Implement Chain of Responsibility for middleware pipeline
- **PAT-003**: Use Registry pattern for service discovery
- **PAT-004**: Apply Facade pattern for API response formatting

## 2. Implementation Steps

### GOAL-001: Setup API Gateway Package Structure

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-AG-001 | Package structure and database schema for API gateway | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create package directory at `packages/api-gateway/` with standard Laravel package structure | | |
| TASK-002 | Create composer.json with name `azaharizaman/erp-api-gateway`, namespace `Nexus\Erp\ApiGateway`, require laravel/framework ^12.0, laravel/sanctum ^4.0 | | |
| TASK-003 | Create service provider in `packages/api-gateway/src/ApiGatewayServiceProvider.php` registering routes, migrations, configs | | |
| TASK-004 | Create migration `xxxx_create_api_keys_table.php` with columns: id, tenant_id, key_name, key_hash, key_prefix, permissions (JSON), rate_limit_tier, requests_per_minute, is_active, last_used_at, expires_at, created_by, timestamps | | |
| TASK-005 | Create migration `xxxx_create_api_request_log_table.php` with columns: id, tenant_id, api_key_id, user_id, request_method, request_path, response_status_code, response_time_ms, ip_address, user_agent, api_version, created_at | | |
| TASK-006 | Create migration `xxxx_create_api_usage_metrics_table.php` with columns: id, tenant_id, api_key_id, metric_date, total_requests, successful_requests, failed_requests, avg_response_time_ms, timestamps | | |
| TASK-007 | Create migration `xxxx_create_api_versions_table.php` with columns: id, version_number, is_current, is_deprecated, deprecation_date, sunset_date, release_notes, timestamps | | |

### GOAL-002: Implement Multi-Version API Routing

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-AG-001, FR-AG-004 | Multi-version API support via URL path routing | | |
| ARCH-AG-002 | API versioning implementation | | |
| PR-AG-001 | Routing latency < 10ms | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-008 | Create route file `packages/api-gateway/routes/api-v1.php` with prefix `/api/v1` and middleware ['api', 'auth:sanctum', 'api.version:v1'] | | |
| TASK-009 | Create route file `packages/api-gateway/routes/api-v2.php` with prefix `/api/v2` and middleware ['api', 'auth:sanctum', 'api.version:v2'] | | |
| TASK-010 | Create API version middleware in `app/Http/Middleware/ApiVersionMiddleware.php` that sets request attribute 'api_version' from route parameter | | |
| TASK-011 | Create API version model in `packages/api-gateway/src/Models/ApiVersion.php` with casts for is_current, is_deprecated (boolean), deprecation_date, sunset_date (date) | | |
| TASK-012 | Create API version repository contract in `packages/api-gateway/src/Contracts/ApiVersionRepositoryContract.php` with methods: getCurrentVersion, getVersion, isDeprecated | | |
| TASK-013 | Implement API version repository in `packages/api-gateway/src/Repositories/ApiVersionRepository.php` with Redis caching for version lookups (TTL 3600s) | | |
| TASK-014 | Create route service provider in `packages/api-gateway/src/Providers/ApiRouteServiceProvider.php` that loads versioned route files based on configured versions | | |
| TASK-015 | Add route caching optimization using `php artisan route:cache` to reduce routing overhead to < 5ms | | |

### GOAL-003: Implement Gateway Authentication

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| ARCH-AG-001 | Laravel Sanctum API authentication | | |
| SEC-001, SEC-002 | Secure API key management | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-016 | Create API key model in `packages/api-gateway/src/Models/ApiKey.php` with fillable: tenant_id, key_name, key_hash, key_prefix, permissions, rate_limit_tier, requests_per_minute, expires_at, created_by | | |
| TASK-017 | Add model traits: BelongsToTenant, LogsActivity for audit logging | | |
| TASK-018 | Create API key repository contract in `packages/api-gateway/src/Contracts/ApiKeyRepositoryContract.php` with methods: findByHash, create, update, revoke, findActive | | |
| TASK-019 | Implement API key repository in `packages/api-gateway/src/Repositories/ApiKeyRepository.php` with query optimization using indexes on key_hash, tenant_id, is_active | | |
| TASK-020 | Create API key service in `packages/api-gateway/src/Services/ApiKeyService.php` with method `generateApiKey(): array` returning ['key' => plaintext, 'hash' => hashed, 'prefix' => first 8 chars] | | |
| TASK-021 | Implement key hashing using `Hash::make($key)` for storage and `Hash::check($key, $hash)` for verification | | |
| TASK-022 | Create API key authentication guard in `packages/api-gateway/src/Auth/ApiKeyGuard.php` that validates API keys from Authorization header `Bearer <key>` | | |
| TASK-023 | Register custom guard in `config/auth.php` as 'api-key' guard using ApiKeyGuard driver | | |
| TASK-024 | Create API key authentication middleware in `app/Http/Middleware/AuthenticateApiKey.php` that checks Authorization header and validates key against database | | |
| TASK-025 | Update ApiKey model last_used_at timestamp on successful authentication using observer | | |

### GOAL-004: Implement Request/Response Transformation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-AG-002 | Request routing with transformation | | |
| FR-AG-003 | Response aggregation for composite queries | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-026 | Create request transformer contract in `packages/api-gateway/src/Contracts/RequestTransformerContract.php` with method `transform(Request $request): Request` | | |
| TASK-027 | Implement base request transformer in `packages/api-gateway/src/Transformers/BaseRequestTransformer.php` adding tenant_id, user_id, api_version to request attributes | | |
| TASK-028 | Create response transformer contract in `packages/api-gateway/src/Contracts/ResponseTransformerContract.php` with method `transform($data, Request $request): array` | | |
| TASK-029 | Implement JSON:API response transformer in `packages/api-gateway/src/Transformers/JsonApiResponseTransformer.php` formatting responses with {data, meta, links} structure | | |
| TASK-030 | Create transformation middleware in `app/Http/Middleware/TransformRequestMiddleware.php` applying request transformations before controller | | |
| TASK-031 | Create transformation middleware in `app/Http/Middleware/TransformResponseMiddleware.php` applying response transformations after controller | | |
| TASK-032 | Create response aggregation service in `packages/api-gateway/src/Services/ResponseAggregationService.php` with method `aggregate(array $responses): array` merging multiple service responses | | |
| TASK-033 | Implement parallel request execution using GuzzleHttp concurrent requests for response aggregation with 500ms timeout | | |

### GOAL-005: Implement API Request Logging and Monitoring

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| DR-AG-001 | Log all API requests with response times | | |
| DR-AG-002 | Store API usage metrics for analytics | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-034 | Create API request log model in `packages/api-gateway/src/Models/ApiRequestLog.php` with fillable: tenant_id, api_key_id, user_id, request_method, request_path, response_status_code, response_time_ms, ip_address, user_agent, api_version | | |
| TASK-035 | Create API usage metrics model in `packages/api-gateway/src/Models/ApiUsageMetrics.php` with fillable: tenant_id, api_key_id, metric_date, total_requests, successful_requests, failed_requests, avg_response_time_ms | | |
| TASK-036 | Create API logging middleware in `app/Http/Middleware/ApiRequestLoggingMiddleware.php` capturing request start time, logging after response with calculated duration | | |
| TASK-037 | Implement async logging using queued job `packages/api-gateway/src/Jobs/LogApiRequestJob.php` to avoid blocking main request thread | | |
| TASK-038 | Create API metrics aggregation command in `packages/api-gateway/src/Console/AggregateApiMetricsCommand.php` that runs daily aggregating previous day's request logs | | |
| TASK-039 | Add command to Laravel scheduler with `$schedule->command('erp:api:aggregate-metrics')->daily()` | | |
| TASK-040 | Create API usage service in `packages/api-gateway/src/Services/ApiUsageService.php` with methods: getUsageByKey, getUsageByTenant, getUsageByDate | | |
| TASK-041 | Implement log retention policy with automatic cleanup command deleting logs older than 90 days | | |

## 3. Alternatives

- **ALT-001**: Use Kong or Tyk as external API gateway - Rejected due to added infrastructure complexity and desire for native Laravel integration
- **ALT-002**: Implement API versioning via headers (Accept: application/vnd.api+json;version=1) - Rejected for URL-based versioning clarity
- **ALT-003**: Store API request logs in Elasticsearch - Deferred to future enhancement; current PostgreSQL sufficient for MVP
- **ALT-004**: Use Laravel Passport instead of Sanctum - Rejected as Sanctum provides simpler token-based auth suitable for API gateway

## 4. Dependencies

- **DEP-001**: Laravel Sanctum package for API authentication
- **DEP-002**: Redis server for caching route mappings and API key validation
- **DEP-003**: GuzzleHttp client for proxying requests to backend services
- **DEP-004**: Laravel Queue with Redis driver for async request logging
- **DEP-005**: Multi-tenancy infrastructure from PRD01-SUB01
- **DEP-006**: Authentication module from PRD01-SUB02

## 5. Files

- **packages/api-gateway/composer.json**: Package definition
- **packages/api-gateway/src/ApiGatewayServiceProvider.php**: Main service provider
- **packages/api-gateway/src/Providers/ApiRouteServiceProvider.php**: Route provider for version management
- **packages/api-gateway/routes/api-v1.php**: Version 1 API routes
- **packages/api-gateway/routes/api-v2.php**: Version 2 API routes
- **database/migrations/xxxx_create_api_keys_table.php**: API keys table
- **database/migrations/xxxx_create_api_request_log_table.php**: Request logging table
- **database/migrations/xxxx_create_api_usage_metrics_table.php**: Usage metrics table
- **database/migrations/xxxx_create_api_versions_table.php**: API versions table
- **packages/api-gateway/src/Models/ApiKey.php**: API key model
- **packages/api-gateway/src/Models/ApiRequestLog.php**: Request log model
- **packages/api-gateway/src/Models/ApiUsageMetrics.php**: Usage metrics model
- **packages/api-gateway/src/Models/ApiVersion.php**: API version model
- **packages/api-gateway/src/Contracts/ApiKeyRepositoryContract.php**: API key repository interface
- **packages/api-gateway/src/Contracts/ApiVersionRepositoryContract.php**: API version repository interface
- **packages/api-gateway/src/Contracts/RequestTransformerContract.php**: Request transformer interface
- **packages/api-gateway/src/Contracts/ResponseTransformerContract.php**: Response transformer interface
- **packages/api-gateway/src/Repositories/ApiKeyRepository.php**: API key repository
- **packages/api-gateway/src/Repositories/ApiVersionRepository.php**: API version repository
- **packages/api-gateway/src/Services/ApiKeyService.php**: API key generation service
- **packages/api-gateway/src/Services/ResponseAggregationService.php**: Response aggregation service
- **packages/api-gateway/src/Services/ApiUsageService.php**: API usage analytics service
- **packages/api-gateway/src/Transformers/BaseRequestTransformer.php**: Base request transformer
- **packages/api-gateway/src/Transformers/JsonApiResponseTransformer.php**: JSON:API response transformer
- **packages/api-gateway/src/Auth/ApiKeyGuard.php**: Custom authentication guard
- **packages/api-gateway/src/Jobs/LogApiRequestJob.php**: Async request logging job
- **packages/api-gateway/src/Console/AggregateApiMetricsCommand.php**: Metrics aggregation command
- **app/Http/Middleware/ApiVersionMiddleware.php**: API version detection middleware
- **app/Http/Middleware/AuthenticateApiKey.php**: API key authentication middleware
- **app/Http/Middleware/TransformRequestMiddleware.php**: Request transformation middleware
- **app/Http/Middleware/TransformResponseMiddleware.php**: Response transformation middleware
- **app/Http/Middleware/ApiRequestLoggingMiddleware.php**: Request logging middleware

## 6. Testing

- **TEST-001**: Feature test for API v1 route registration and routing
- **TEST-002**: Feature test for API v2 route registration with version middleware
- **TEST-003**: Feature test for API key generation creating unique keys with hash and prefix
- **TEST-004**: Feature test for API key authentication validating Bearer token
- **TEST-005**: Feature test for API key expiration preventing authentication with expired keys
- **TEST-006**: Feature test for request transformation adding tenant_id and user_id attributes
- **TEST-007**: Feature test for response transformation formatting as JSON:API structure
- **TEST-008**: Feature test for API request logging capturing method, path, status, response time
- **TEST-009**: Feature test for API usage metrics aggregation calculating daily totals
- **TEST-010**: Feature test for response aggregation merging multiple service responses
- **TEST-011**: Unit test for API key hashing using Hash::make and Hash::check
- **TEST-012**: Unit test for API version repository caching with Redis
- **TEST-013**: Performance test for routing latency measuring < 10ms overhead
- **TEST-014**: Performance test for throughput handling 10,000+ requests per second
- **TEST-015**: Security test for API key encryption at rest

## 7. Risks & Assumptions

- **RISK-001**: High request volume may overwhelm logging system - Mitigated by async queue-based logging
- **RISK-002**: Gateway becomes single point of failure - Mitigated by horizontal scaling and load balancing
- **RISK-003**: Response aggregation may timeout for slow backend services - Mitigated by 500ms timeout and circuit breaker pattern

- **ASSUMPTION-001**: Redis server available for caching and queue driver
- **ASSUMPTION-002**: Backend services respond within 500ms for aggregation
- **ASSUMPTION-003**: API clients can handle JSON:API response format

## 8. KIV for Future Implementations

- **KIV-001**: Implement GraphQL gateway for flexible queries (covered in PLAN02)
- **KIV-002**: Add API request/response caching layer for frequent queries
- **KIV-003**: Implement service mesh integration (Istio, Linkerd)
- **KIV-004**: Add API gateway clustering for high availability
- **KIV-005**: Implement request deduplication to prevent duplicate processing
- **KIV-006**: Add support for gRPC protocol alongside REST

## 9. Related PRD / Further Reading

- **[PRD01-SUB23: API Gateway & Documentation](../prd/prd-01/PRD01-SUB23-API-GATEWAY-AND-DOCUMENTATION.md)** - Master Sub-PRD
- **[Laravel Sanctum Documentation](https://laravel.com/docs/sanctum)** - API authentication
- **[JSON:API Specification](https://jsonapi.org/)** - Response format standard
- **[API Gateway Pattern](https://microservices.io/patterns/apigateway.html)** - Architecture pattern reference
