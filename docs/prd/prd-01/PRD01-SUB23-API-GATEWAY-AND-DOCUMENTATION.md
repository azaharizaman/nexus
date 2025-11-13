# PRD01-SUB23: API Gateway & Documentation

**Master PRD:** [../PRD01-MVP.md](../PRD01-MVP.md)  
**Feature Module Category:** Core Feature Modules - Infrastructure  
**Related Sub-PRDs:** SUB02 (Authentication), All transactional modules  
**Composer Package:** `azaharizaman/erp-api-gateway`  
**Version:** 1.0.0  
**Status:** Draft  
**Created:** November 11, 2025

---

## Executive Summary

The API Gateway & Documentation module provides unified RESTful and GraphQL APIs with versioning, interactive Swagger/OpenAPI documentation, API sandbox environment, rate limiting, and comprehensive monitoring for seamless third-party integrations.

### Purpose

This module solves the challenge of providing a consistent, well-documented, and secure API interface for external systems, mobile apps, and custom frontends while managing versioning, rate limiting, and performance optimization.

### Scope

**Included:**
- Unified RESTful API gateway with versioning support (v1, v2)
- GraphQL API for flexible data querying
- Interactive API documentation with Swagger/OpenAPI
- API sandbox environment for testing without affecting production
- Batch operations for bulk data updates
- Webhook management for event subscriptions
- API client SDKs for common languages (PHP, JavaScript, Python)

**Excluded:**
- Frontend web application (this is backend-only ERP)
- Mobile application development (handled by external teams)
- Custom integration development (handled by Integration Connectors module)

### Dependencies

**Mandatory Dependencies:**
- **SUB01 (Multi-Tenancy)** - Tenant isolation for API access
- **SUB02 (Authentication & Authorization)** - API authentication (OAuth 2.0, API keys)
- **SUB03 (Audit Logging)** - Track API requests and responses

**Optional Dependencies:**
- All transactional modules - API endpoint exposure

### Composer Package Information

- **Package Name:** `azaharizaman/erp-api-gateway`
- **Namespace:** `Nexus\Erp\ApiGateway`
- **Monorepo Location:** `/packages/api-gateway/`
- **Installation:** `composer require azaharizaman/erp-api-gateway` (post v1.0 release)

---

## Requirements

> **Note:** These requirements are derived from Master PRD Section F.2.3 - PRD01-SUB23 (API Gateway & Docs). For complete traceability and context, refer to the [Master PRD Requirements Table](../PRD01-MVP.md#f23-requirements-by-sub-prd).

### Functional Requirements (FR)

| Requirement ID | Description | Priority | Status |
|----------------|-------------|----------|--------|
| **FR-API-001** | Provide **unified RESTful API gateway** with versioning support (v1, v2) | High | Planned |
| **FR-API-002** | Support **GraphQL API** for flexible data querying | Medium | Planned |
| **FR-API-003** | Generate **interactive API documentation** with Swagger/OpenAPI | High | Planned |
| **FR-API-004** | Provide **API sandbox environment** for testing without affecting production | High | Planned |
| **FR-API-005** | Support **batch operations** for bulk data updates | Medium | Planned |
| **FR-API-006** | Implement **webhook management** for event subscriptions | Medium | Planned |
| **FR-API-007** | Provide **API client SDKs** for common languages (PHP, JavaScript, Python) | Medium | Planned |

### Business Rules (BR)

| Rule ID | Description | Status |
|---------|-------------|--------|
| **BR-API-001** | API versions must be **backward compatible** for at least 12 months | Planned |
| **BR-API-002** | **Deprecated endpoints** must show warnings 3 months before removal | Planned |

### Data Requirements (DR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **DR-API-001** | Log all **API requests** with response times and status codes | Planned |
| **DR-API-002** | Store **API usage metrics** for analytics and billing | Planned |

### Integration Requirements (IR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **IR-API-001** | Integrate with **all modules** via consistent API patterns | Planned |
| **IR-API-002** | Support **OAuth 2.0** and **API key authentication** | Planned |

### Security Requirements (SR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SR-API-001** | Implement **rate limiting** per API key with tiered plans | Planned |
| **SR-API-002** | **Authenticate and authorize** all API requests | Planned |
| **SR-API-003** | **Encrypt API keys** at rest and require HTTPS for all endpoints | Planned |

### Performance Requirements (PR)

| Requirement ID | Target | Status |
|----------------|--------|--------|
| **PR-API-001** | API gateway routing must add **< 10ms latency** | Planned |
| **PR-API-002** | Support **10,000+ API requests per second** with horizontal scaling | Planned |

### Scalability Requirements (SCR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SCR-API-001** | Scale API gateway **horizontally** with load balancing | Planned |

### Architecture Requirements (ARCH)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **ARCH-API-001** | Use **Laravel Sanctum** for API authentication | Planned |
| **ARCH-API-002** | Implement **API versioning** via URL path (/api/v1/, /api/v2/) | Planned |
| **ARCH-API-003** | Use **Redis** for rate limiting and API key validation caching | Planned |

### Event Requirements (EV)

| Event ID | Event Name | Trigger | Status |
|----------|------------|---------|--------|
| **EV-API-001** | `APIRequestEvent` | For monitoring and analytics | Planned |
| **EV-API-002** | `RateLimitExceededEvent` | When API quota is reached | Planned |

---

## Technical Specifications

### Database Schema

**API Keys Table:**

```sql
CREATE TABLE api_keys (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    key_name VARCHAR(255) NOT NULL,
    key_hash VARCHAR(255) NOT NULL,  -- Hashed API key
    key_prefix VARCHAR(20) NOT NULL,  -- First 8 chars for identification
    permissions JSONB NULL,  -- Scoped permissions
    rate_limit_tier VARCHAR(20) DEFAULT 'standard',  -- 'basic', 'standard', 'premium', 'unlimited'
    requests_per_minute INT DEFAULT 60,
    is_active BOOLEAN DEFAULT TRUE,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_by BIGINT NOT NULL REFERENCES users(id),
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_api_keys_tenant (tenant_id),
    INDEX idx_api_keys_hash (key_hash),
    INDEX idx_api_keys_prefix (key_prefix),
    INDEX idx_api_keys_active (is_active),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**API Request Log Table:**

```sql
CREATE TABLE api_request_log (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    api_key_id BIGINT NULL REFERENCES api_keys(id),
    user_id BIGINT NULL REFERENCES users(id),
    request_method VARCHAR(10) NOT NULL,  -- GET, POST, PUT, PATCH, DELETE
    request_path TEXT NOT NULL,
    request_query TEXT NULL,
    request_body TEXT NULL,
    response_status_code INT NOT NULL,
    response_time_ms INT NOT NULL,
    response_body TEXT NULL,
    ip_address VARCHAR(50) NULL,
    user_agent TEXT NULL,
    api_version VARCHAR(10) NULL,  -- v1, v2
    created_at TIMESTAMP NOT NULL,
    
    INDEX idx_api_request_log_tenant (tenant_id),
    INDEX idx_api_request_log_key (api_key_id),
    INDEX idx_api_request_log_user (user_id),
    INDEX idx_api_request_log_path (request_path),
    INDEX idx_api_request_log_status (response_status_code),
    INDEX idx_api_request_log_created (created_at),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**API Usage Metrics Table:**

```sql
CREATE TABLE api_usage_metrics (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    api_key_id BIGINT NULL REFERENCES api_keys(id),
    metric_date DATE NOT NULL,
    total_requests INT DEFAULT 0,
    successful_requests INT DEFAULT 0,
    failed_requests INT DEFAULT 0,
    rate_limited_requests INT DEFAULT 0,
    avg_response_time_ms INT DEFAULT 0,
    total_data_transferred_mb DECIMAL(15, 2) DEFAULT 0,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    UNIQUE (tenant_id, api_key_id, metric_date),
    INDEX idx_api_usage_metrics_tenant (tenant_id),
    INDEX idx_api_usage_metrics_key (api_key_id),
    INDEX idx_api_usage_metrics_date (metric_date),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**API Versions Table:**

```sql
CREATE TABLE api_versions (
    id BIGSERIAL PRIMARY KEY,
    version_number VARCHAR(10) NOT NULL UNIQUE,  -- v1, v2, v3
    is_current BOOLEAN DEFAULT FALSE,
    is_deprecated BOOLEAN DEFAULT FALSE,
    deprecation_date DATE NULL,
    sunset_date DATE NULL,
    release_notes TEXT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);
```

**Deprecated Endpoints Table:**

```sql
CREATE TABLE deprecated_endpoints (
    id BIGSERIAL PRIMARY KEY,
    api_version VARCHAR(10) NOT NULL,
    endpoint_path TEXT NOT NULL,
    http_method VARCHAR(10) NOT NULL,
    deprecated_at DATE NOT NULL,
    sunset_at DATE NOT NULL,
    replacement_endpoint TEXT NULL,
    deprecation_message TEXT NULL,
    created_at TIMESTAMP NOT NULL,
    
    UNIQUE (api_version, endpoint_path, http_method),
    INDEX idx_deprecated_endpoints_version (api_version),
    INDEX idx_deprecated_endpoints_sunset (sunset_at)
);
```

**Sandbox Environments Table:**

```sql
CREATE TABLE sandbox_environments (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    environment_name VARCHAR(255) NOT NULL,
    database_name VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    auto_reset BOOLEAN DEFAULT TRUE,
    reset_frequency VARCHAR(20) NULL,  -- 'hourly', 'daily', 'weekly'
    last_reset_at TIMESTAMP NULL,
    created_by BIGINT NOT NULL REFERENCES users(id),
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    UNIQUE (tenant_id, environment_name),
    INDEX idx_sandbox_environments_tenant (tenant_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

### API Endpoints

**API Key Management:**
- `GET /api/v1/gateway/api-keys` - List API keys
- `POST /api/v1/gateway/api-keys` - Create API key
- `PATCH /api/v1/gateway/api-keys/{id}` - Update API key
- `DELETE /api/v1/gateway/api-keys/{id}` - Revoke API key
- `POST /api/v1/gateway/api-keys/{id}/rotate` - Rotate API key

**API Documentation:**
- `GET /api/docs` - Swagger UI documentation
- `GET /api/docs/openapi.json` - OpenAPI specification (JSON)
- `GET /api/docs/openapi.yaml` - OpenAPI specification (YAML)

**API Versions:**
- `GET /api/versions` - List available API versions
- `GET /api/versions/current` - Get current version info
- `GET /api/versions/deprecated` - List deprecated endpoints

**Sandbox:**
- `GET /api/v1/sandbox/environments` - List sandbox environments
- `POST /api/v1/sandbox/environments` - Create sandbox
- `POST /api/v1/sandbox/environments/{id}/reset` - Reset sandbox data
- `DELETE /api/v1/sandbox/environments/{id}` - Delete sandbox

**Analytics:**
- `GET /api/v1/gateway/usage` - Get API usage metrics
- `GET /api/v1/gateway/requests` - Get request log
- `GET /api/v1/gateway/rate-limits` - Get current rate limit status

**Batch Operations:**
- `POST /api/v1/batch` - Execute batch operations
- `GET /api/v1/batch/{id}` - Get batch operation status

**GraphQL:**
- `POST /api/graphql` - GraphQL endpoint
- `GET /api/graphql/playground` - GraphQL playground UI

### Events

**Domain Events Emitted:**

```php
namespace Nexus\Erp\ApiGateway\Events;

class APIRequestEvent
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly int $statusCode,
        public readonly int $responseTimeMs,
        public readonly ?string $apiKeyId
    ) {}
}

class RateLimitExceededEvent
{
    public function __construct(
        public readonly string $apiKeyId,
        public readonly string $endpoint,
        public readonly int $requestCount,
        public readonly int $limit
    ) {}
}
```

### Event Listeners

**Events from Other Modules:**

This module doesn't typically listen to events from other modules, but provides APIs for all modules to expose their functionality.

---

## Implementation Plans

**Note:** Implementation plans follow the naming convention `PLAN{number}-implement-{component}.md`

| Plan File | Requirements Covered | Milestone | Status |
|-----------|---------------------|-----------|--------|
| PLAN23-implement-api-gateway.md | FR-API-001 to FR-API-007, BR-API-001 to BR-API-002 | MILESTONE 11 | Not Started |

**Implementation plan will be created separately using:** `.github/prompts/create-implementation-plan.prompt.md`

---

## Acceptance Criteria

### Functional Acceptance

- [ ] Unified RESTful API gateway with versioning functional
- [ ] GraphQL API for flexible querying working
- [ ] Interactive API documentation (Swagger) generated
- [ ] API sandbox environment operational
- [ ] Batch operations for bulk updates working
- [ ] Webhook management functional
- [ ] API client SDKs available for PHP, JavaScript, Python

### Technical Acceptance

- [ ] All API endpoints return correct responses per OpenAPI spec
- [ ] API gateway routing adds < 10ms latency (PR-API-001)
- [ ] System supports 10,000+ API requests per second (PR-API-002)
- [ ] Horizontal scaling with load balancing functional (SCR-API-001)
- [ ] Laravel Sanctum for authentication operational (ARCH-API-001)
- [ ] API versioning via URL path working (ARCH-API-002)
- [ ] Redis for rate limiting operational (ARCH-API-003)

### Security Acceptance

- [ ] Rate limiting per API key enforced (SR-API-001)
- [ ] All API requests authenticated and authorized (SR-API-002)
- [ ] API keys encrypted at rest, HTTPS required (SR-API-003)

### Integration Acceptance

- [ ] Integration with all modules via consistent patterns functional (IR-API-001)
- [ ] OAuth 2.0 and API key authentication working (IR-API-002)

---

## Testing Strategy

### Unit Tests

**Test Coverage Requirements:** Minimum 80% code coverage

**Key Test Areas:**
- API key generation and validation
- Rate limiting logic
- Request throttling
- API versioning routing
- Batch operation processing

**Example Tests:**
```php
test('rate limiting enforces request limits', function () {
    $apiKey = ApiKey::factory()->create(['requests_per_minute' => 10]);
    
    // Make 10 requests (should succeed)
    for ($i = 0; $i < 10; $i++) {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$apiKey->key}",
        ])->getJson('/api/v1/test');
        
        $response->assertOk();
    }
    
    // 11th request should be rate limited
    $response = $this->withHeaders([
        'Authorization' => "Bearer {$apiKey->key}",
    ])->getJson('/api/v1/test');
    
    $response->assertStatus(429);  // Too Many Requests
});

test('deprecated endpoints show warning', function () {
    DeprecatedEndpoint::create([
        'api_version' => 'v1',
        'endpoint_path' => '/api/v1/old-endpoint',
        'http_method' => 'GET',
        'deprecated_at' => now()->subMonths(2),
        'sunset_at' => now()->addMonth(),
        'replacement_endpoint' => '/api/v2/new-endpoint',
    ]);
    
    $response = $this->getJson('/api/v1/old-endpoint');
    
    expect($response->headers->get('Deprecation'))->not->toBeNull();
    expect($response->headers->get('Sunset'))->not->toBeNull();
});
```

### Feature Tests

**API Integration Tests:**
- Create and use API key
- Rate limit enforcement
- Batch operation execution
- GraphQL query execution
- Sandbox environment testing

**Example Tests:**
```php
test('can create and use API key', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    
    // Create API key
    $response = $this->actingAs($user)
        ->postJson('/api/v1/gateway/api-keys', [
            'key_name' => 'Test API Key',
            'rate_limit_tier' => 'standard',
        ]);
    
    $response->assertCreated();
    $apiKey = $response->json('data.api_key');
    
    // Use API key
    $response = $this->withHeaders([
        'Authorization' => "Bearer {$apiKey}",
    ])->getJson('/api/v1/test');
    
    $response->assertOk();
});
```

### Integration Tests

**Cross-Module Integration:**
- API exposes all transactional module endpoints
- Rate limiting doesn't impact normal operations
- API versioning doesn't break existing integrations

### Performance Tests

**Load Testing Scenarios:**
- API gateway routing: < 10ms latency (PR-API-001)
- 10,000+ API requests per second (PR-API-002)
- Horizontal scaling with multiple instances
- Rate limiting under high load

---

## Dependencies

### Feature Module Dependencies

**From Master PRD Section D.2.1:**

**Mandatory Dependencies:**
- **SUB01 (Multi-Tenancy)** - Tenant isolation for API access
- **SUB02 (Authentication & Authorization)** - API authentication
- **SUB03 (Audit Logging)** - Track API requests and responses

**Optional Dependencies:**
- All transactional modules - API endpoint exposure

### External Package Dependencies

```json
{
  "require": {
    "php": "^8.2",
    "laravel/framework": "^12.0",
    "azaharizaman/erp-core": "^1.0",
    "lorisleiva/laravel-actions": "^2.0",
    "darkaonline/l5-swagger": "^8.0",
    "rebing/graphql-laravel": "^9.0"
  },
  "require-dev": {
    "pestphp/pest": "^4.0"
  }
}
```

### Infrastructure Dependencies

- **Database:** PostgreSQL 14+ (for API logs and metrics)
- **Cache:** Redis 6+ (for rate limiting and API key validation)
- **Load Balancer:** nginx or HAProxy (for horizontal scaling)

---

## Feature Module Structure

### Directory Structure (in Monorepo)

```
packages/api-gateway/
├── src/
│   ├── Actions/
│   │   ├── CreateApiKeyAction.php
│   │   ├── ValidateApiKeyAction.php
│   │   └── ExecuteBatchOperationAction.php
│   ├── Contracts/
│   │   ├── ApiGatewayServiceContract.php
│   │   └── RateLimiterServiceContract.php
│   ├── Events/
│   │   ├── APIRequestEvent.php
│   │   └── RateLimitExceededEvent.php
│   ├── Listeners/
│   │   └── LogApiRequestListener.php
│   ├── Models/
│   │   ├── ApiKey.php
│   │   ├── ApiRequestLog.php
│   │   ├── ApiUsageMetric.php
│   │   ├── ApiVersion.php
│   │   ├── DeprecatedEndpoint.php
│   │   └── SandboxEnvironment.php
│   ├── Middleware/
│   │   ├── AuthenticateApiKey.php
│   │   ├── RateLimitApi.php
│   │   └── LogApiRequest.php
│   ├── Observers/
│   │   └── ApiKeyObserver.php
│   ├── Policies/
│   │   └── ApiKeyPolicy.php
│   ├── Repositories/
│   │   └── ApiKeyRepository.php
│   ├── Services/
│   │   ├── ApiGatewayService.php
│   │   ├── RateLimiterService.php
│   │   ├── ApiVersionService.php
│   │   └── BatchOperationService.php
│   ├── GraphQL/
│   │   ├── Queries/
│   │   ├── Mutations/
│   │   └── Types/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Requests/
│   │   └── Resources/
│   └── ApiGatewayServiceProvider.php
├── tests/
│   ├── Feature/
│   │   ├── ApiKeyManagementTest.php
│   │   ├── RateLimitingTest.php
│   │   └── BatchOperationsTest.php
│   └── Unit/
│       ├── ApiKeyValidationTest.php
│       └── RateLimiterTest.php
├── database/
│   ├── migrations/
│   │   ├── 2025_01_01_000001_create_api_keys_table.php
│   │   ├── 2025_01_01_000002_create_api_request_log_table.php
│   │   ├── 2025_01_01_000003_create_api_usage_metrics_table.php
│   │   ├── 2025_01_01_000004_create_api_versions_table.php
│   │   ├── 2025_01_01_000005_create_deprecated_endpoints_table.php
│   │   └── 2025_01_01_000006_create_sandbox_environments_table.php
│   └── factories/
│       └── ApiKeyFactory.php
├── routes/
│   ├── api.php
│   └── graphql.php
├── config/
│   ├── api-gateway.php
│   └── l5-swagger.php
├── docs/
│   └── openapi.yaml
├── composer.json
└── README.md
```

---

## Migration Path

This is a new module with no existing functionality to migrate from.

**Initial Setup:**
1. Install package via Composer
2. Publish migrations and run `php artisan migrate`
3. Configure rate limiting tiers
4. Generate OpenAPI documentation
5. Set up GraphQL schema
6. Create API client SDKs
7. Configure sandbox environments
8. Train developers on API usage

---

## Success Metrics

From Master PRD Section B.3:

**Adoption Metrics:**
- API key creation > 80% of tenants
- API usage growth > 50% month-over-month
- Developer satisfaction score > 4.5/5

**Performance Metrics:**
- API gateway routing latency < 10ms (PR-API-001)
- 10,000+ API requests per second (PR-API-002)

**Reliability Metrics:**
- API uptime > 99.9%
- API error rate < 0.1%

**Operational Metrics:**
- Average API response time < 200ms
- Time to first API integration < 1 hour

---

## Assumptions & Constraints

### Assumptions

1. All module endpoints follow RESTful conventions
2. External developers familiar with REST and GraphQL
3. API documentation kept up-to-date with code changes
4. HTTPS available for all API endpoints
5. Load balancer configured for horizontal scaling

### Constraints

1. API versions backward compatible for at least 12 months
2. Deprecated endpoints show warnings 3 months before removal
3. All API requests must be authenticated and authorized
4. API keys encrypted at rest, HTTPS required for all endpoints
5. API gateway routing must add < 10ms latency

---

## Monorepo Integration

### Development

- Lives in `/packages/api-gateway/` during development
- Main app uses Composer path repository to require locally:
  ```json
  {
    "repositories": [
      {
        "type": "path",
        "url": "./packages/api-gateway"
      }
    ],
    "require": {
      "azaharizaman/erp-api-gateway": "@dev"
    }
  }
  ```
- All changes committed to monorepo

### Release (v1.0)

- Tagged with monorepo version (e.g., v1.0.0)
- Published to Packagist as `azaharizaman/erp-api-gateway`
- Can be installed independently in external Laravel apps
- Semantic versioning: MAJOR.MINOR.PATCH

---

## References

- Master PRD: [../PRD01-MVP.md](../PRD01-MVP.md)
- Monorepo Strategy: [../PRD01-MVP.md#C.1](../PRD01-MVP.md#section-c1-core-architectural-strategy-the-monorepo)
- Feature Module Independence: [../PRD01-MVP.md#D.2.2](../PRD01-MVP.md#d22-feature-module-independence-requirements)
- Architecture Documentation: [../../architecture/](../../architecture/)
- Coding Guidelines: [../../CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
- GitHub Copilot Instructions: [../../.github/copilot-instructions.md](../../.github/copilot-instructions.md)

---

**Next Steps:**
1. Review and approve this Sub-PRD
2. Create implementation plan: `PLAN23-implement-api-gateway.md` in `/docs/plan/`
3. Break down into GitHub issues
4. Assign to MILESTONE 11 from Master PRD Section F.2.4
5. Set up feature module structure in `/packages/api-gateway/`
