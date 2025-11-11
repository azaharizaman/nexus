# PRD01-SUB25: Localization

**Master PRD:** [../PRD01-MVP.md](../PRD01-MVP.md)  
**Feature Module Category:** Optional Feature Modules - Global Features  
**Related Sub-PRDs:** SUB01 (Multi-Tenancy), SUB12 (Accounting), SUB17 (Sales), SUB19 (Taxation)  
**Composer Package:** `azaharizaman/erp-localization`  
**Version:** 1.0.0  
**Status:** Draft  
**Created:** November 11, 2025

---

## Executive Summary

The Localization module provides multi-language user interfaces, multi-currency transactions, exchange rate management, and country-specific formatting for numbers, dates, and times, enabling global business operations with right-to-left (RTL) language support.

### Purpose

This module solves the challenge of supporting global operations by providing comprehensive localization features including multi-language UI with runtime switching, multi-currency transactions with automatic conversion, exchange rate management, and country-specific formatting for dates, numbers, and tax rules.

### Scope

**Included:**
- Multi-language user interfaces with runtime language switching
- Multi-currency transactions with automatic conversion
- Exchange rate management with historical rate tracking
- Date and time format localization per user preferences
- Number formatting based on locale (decimal separators, grouping)
- Right-to-left (RTL) languages support (Arabic, Hebrew)
- Translation management with fallback to default language
- Country-specific tax and compliance rules

**Excluded:**
- Automatic machine translation (requires manual translation input)
- Cultural adaptation beyond UI text (images, colors, etc.)
- Legal compliance verification (handled by implementation partners)

### Dependencies

**Mandatory Dependencies:**
- **SUB01 (Multi-Tenancy)** - Tenant-specific localization preferences
- **SUB02 (Authentication & Authorization)** - User language preferences
- **SUB12 (Accounting)** - Multi-currency financial transactions

**Optional Dependencies:**
- **SUB17 (Sales)** - Currency conversion for pricing
- **SUB19 (Taxation)** - Country-specific tax rules
- All transactional modules - Localized UI and formatting

### Composer Package Information

- **Package Name:** `azaharizaman/erp-localization`
- **Namespace:** `Azaharizaman\Erp\Localization`
- **Monorepo Location:** `/packages/localization/`
- **Installation:** `composer require azaharizaman/erp-localization` (post v1.0 release)

---

## Requirements

> **Note:** These requirements are derived from Master PRD Section F.2.3 - PRD01-SUB25 (Localization). For complete traceability and context, refer to the [Master PRD Requirements Table](../PRD01-MVP.md#f23-requirements-by-sub-prd).

### Functional Requirements (FR)

| Requirement ID | Description | Priority | Status |
|----------------|-------------|----------|--------|
| **FR-LOC-001** | Support **multi-language user interfaces** with runtime language switching | High | Planned |
| **FR-LOC-002** | Support **multi-currency transactions** with automatic conversion | High | Planned |
| **FR-LOC-003** | Provide **exchange rate management** with historical rate tracking | High | Planned |
| **FR-LOC-004** | Support **date and time format localization** per user preferences | High | Planned |
| **FR-LOC-005** | Support **number formatting** based on locale (decimal separators, grouping) | High | Planned |
| **FR-LOC-006** | Support **right-to-left (RTL) languages** (Arabic, Hebrew) | Medium | Planned |
| **FR-LOC-007** | Provide **translation management** with fallback to default language | High | Planned |
| **FR-LOC-008** | Support **country-specific tax and compliance rules** | High | Planned |

### Business Rules (BR)

| Rule ID | Description | Status |
|---------|-------------|--------|
| **BR-LOC-001** | Users can **switch language** anytime without re-authentication | Planned |
| **BR-LOC-002** | Currency conversions use **exchange rates effective at transaction date** | Planned |
| **BR-LOC-003** | Missing translations **fallback to default language** (English) | Planned |

### Data Requirements (DR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **DR-LOC-001** | Store **translation files** in JSON format per language | Planned |
| **DR-LOC-002** | Maintain **complete exchange rate history** for auditing | Planned |
| **DR-LOC-003** | Store **user language preferences** at user and tenant levels | Planned |

### Integration Requirements (IR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **IR-LOC-001** | Integrate with **all transactional modules** for localized UI and formatting | Planned |
| **IR-LOC-002** | Support **external exchange rate API** for automatic rate updates | Planned |

### Security Requirements (SR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SR-LOC-001** | **Encrypt sensitive translations** (e.g., legal terms, compliance text) | Planned |
| **SR-LOC-002** | Log all **exchange rate changes** for audit trail | Planned |

### Performance Requirements (PR)

| Requirement ID | Target | Status |
|----------------|--------|--------|
| **PR-LOC-001** | Language switching must complete within **200ms** | Planned |
| **PR-LOC-002** | Currency conversion calculations must complete within **10ms** | Planned |

### Scalability Requirements (SCR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SCR-LOC-001** | Support **50+ languages** and **150+ currencies** | Planned |

### Architecture Requirements (ARCH)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **ARCH-LOC-001** | Use **Redis caching** for translations and exchange rates | Planned |
| **ARCH-LOC-002** | Use **Laravel localization framework** for translation management | Planned |
| **ARCH-LOC-003** | Store translations in **JSON format** for easy editing | Planned |

### Event Requirements (EV)

| Event ID | Event Name | Trigger | Status |
|----------|------------|---------|--------|
| **EV-LOC-001** | `LanguageSwitchedEvent` | When user changes language | Planned |
| **EV-LOC-002** | `ExchangeRateUpdatedEvent` | When exchange rate is updated | Planned |
| **EV-LOC-003** | `TranslationMissingEvent` | When translation key not found | Planned |

---

## Technical Specifications

### Database Schema

**Languages Table:**

```sql
CREATE TABLE languages (
    id BIGSERIAL PRIMARY KEY,
    language_code VARCHAR(10) NOT NULL UNIQUE,  -- ISO 639-1 (e.g., 'en', 'es', 'ar')
    language_name VARCHAR(100) NOT NULL,
    native_name VARCHAR(100) NOT NULL,  -- Language name in its own script
    is_rtl BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_languages_code (language_code),
    INDEX idx_languages_active (is_active)
);
```

**Currencies Table:**

```sql
CREATE TABLE currencies (
    id BIGSERIAL PRIMARY KEY,
    currency_code VARCHAR(3) NOT NULL UNIQUE,  -- ISO 4217 (e.g., 'USD', 'EUR')
    currency_name VARCHAR(100) NOT NULL,
    currency_symbol VARCHAR(10) NOT NULL,
    decimal_places INT DEFAULT 2,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_currencies_code (currency_code),
    INDEX idx_currencies_active (is_active)
);
```

**Exchange Rates Table:**

```sql
CREATE TABLE exchange_rates (
    id BIGSERIAL PRIMARY KEY,
    from_currency_code VARCHAR(3) NOT NULL REFERENCES currencies(currency_code),
    to_currency_code VARCHAR(3) NOT NULL REFERENCES currencies(currency_code),
    rate DECIMAL(20, 10) NOT NULL,
    effective_date DATE NOT NULL,
    source VARCHAR(50) NOT NULL,  -- 'manual', 'api', 'central_bank'
    created_by BIGINT NULL REFERENCES users(id),
    created_at TIMESTAMP NOT NULL,
    
    UNIQUE (from_currency_code, to_currency_code, effective_date),
    INDEX idx_exchange_rates_currencies (from_currency_code, to_currency_code),
    INDEX idx_exchange_rates_date (effective_date)
);
```

**Tenant Localization Settings Table:**

```sql
CREATE TABLE tenant_localization_settings (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    default_language_code VARCHAR(10) NOT NULL REFERENCES languages(language_code),
    default_currency_code VARCHAR(3) NOT NULL REFERENCES currencies(currency_code),
    timezone VARCHAR(50) NOT NULL DEFAULT 'UTC',
    date_format VARCHAR(50) DEFAULT 'Y-m-d',
    time_format VARCHAR(50) DEFAULT 'H:i:s',
    first_day_of_week INT DEFAULT 0,  -- 0 = Sunday, 1 = Monday
    number_format JSONB NOT NULL DEFAULT '{"decimal_separator": ".", "thousands_separator": ","}',
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    UNIQUE (tenant_id),
    INDEX idx_tenant_localization_tenant (tenant_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**User Localization Preferences Table:**

```sql
CREATE TABLE user_localization_preferences (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    language_code VARCHAR(10) NOT NULL REFERENCES languages(language_code),
    currency_code VARCHAR(3) NULL REFERENCES currencies(currency_code),
    timezone VARCHAR(50) NULL,
    date_format VARCHAR(50) NULL,
    time_format VARCHAR(50) NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    UNIQUE (user_id),
    INDEX idx_user_localization_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Translation Keys Table:**

```sql
CREATE TABLE translation_keys (
    id BIGSERIAL PRIMARY KEY,
    translation_key VARCHAR(255) NOT NULL UNIQUE,
    module VARCHAR(100) NOT NULL,  -- Module name (e.g., 'sales', 'purchasing')
    context TEXT NULL,  -- Context for translators
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_translation_keys_module (module)
);
```

**Translations Table:**

```sql
CREATE TABLE translations (
    id BIGSERIAL PRIMARY KEY,
    translation_key_id BIGINT NOT NULL REFERENCES translation_keys(id) ON DELETE CASCADE,
    language_code VARCHAR(10) NOT NULL REFERENCES languages(language_code),
    translated_text TEXT NOT NULL,
    is_approved BOOLEAN DEFAULT FALSE,
    translated_by BIGINT NULL REFERENCES users(id),
    approved_by BIGINT NULL REFERENCES users(id),
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    UNIQUE (translation_key_id, language_code),
    INDEX idx_translations_key (translation_key_id),
    INDEX idx_translations_language (language_code),
    INDEX idx_translations_approved (is_approved)
);
```

**Country Tax Rules Table:**

```sql
CREATE TABLE country_tax_rules (
    id BIGSERIAL PRIMARY KEY,
    country_code VARCHAR(2) NOT NULL,  -- ISO 3166-1 alpha-2
    tax_type VARCHAR(50) NOT NULL,  -- 'vat', 'gst', 'sales_tax'
    standard_rate DECIMAL(5, 2) NOT NULL,
    reduced_rates JSONB NULL,  -- Array of reduced rates with descriptions
    compliance_rules JSONB NULL,
    effective_from DATE NOT NULL,
    effective_to DATE NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    INDEX idx_country_tax_rules_country (country_code),
    INDEX idx_country_tax_rules_dates (effective_from, effective_to)
);
```

### API Endpoints

All endpoints follow the RESTful pattern under `/api/v1/localization/`:

**Languages:**
- `GET /api/v1/localization/languages` - List available languages
- `GET /api/v1/localization/languages/{code}` - Get language details
- `POST /api/v1/localization/languages/{code}/activate` - Activate language
- `POST /api/v1/localization/languages/{code}/deactivate` - Deactivate language

**Currencies:**
- `GET /api/v1/localization/currencies` - List available currencies
- `GET /api/v1/localization/currencies/{code}` - Get currency details
- `POST /api/v1/localization/currencies/{code}/activate` - Activate currency

**Exchange Rates:**
- `GET /api/v1/localization/exchange-rates` - List exchange rates
- `GET /api/v1/localization/exchange-rates/{from}/{to}` - Get specific rate
- `POST /api/v1/localization/exchange-rates` - Create/update exchange rate
- `GET /api/v1/localization/exchange-rates/history` - Get historical rates
- `POST /api/v1/localization/exchange-rates/sync` - Sync from external API

**Conversions:**
- `POST /api/v1/localization/convert` - Convert amount between currencies
- `POST /api/v1/localization/convert-batch` - Batch currency conversion

**Tenant Settings:**
- `GET /api/v1/localization/tenant/settings` - Get tenant localization settings
- `PATCH /api/v1/localization/tenant/settings` - Update tenant settings

**User Preferences:**
- `GET /api/v1/localization/user/preferences` - Get user preferences
- `PATCH /api/v1/localization/user/preferences` - Update user preferences
- `POST /api/v1/localization/user/switch-language` - Switch language

**Translations:**
- `GET /api/v1/localization/translations` - List translation keys
- `GET /api/v1/localization/translations/{language}` - Get translations for language
- `POST /api/v1/localization/translations` - Add translation
- `PATCH /api/v1/localization/translations/{id}` - Update translation
- `POST /api/v1/localization/translations/{id}/approve` - Approve translation

**Tax Rules:**
- `GET /api/v1/localization/tax-rules/{country}` - Get country-specific tax rules

### Events

**Domain Events Emitted:**

```php
namespace Azaharizaman\Erp\Localization\Events;

class LanguageSwitchedEvent
{
    public function __construct(
        public readonly User $user,
        public readonly string $previousLanguage,
        public readonly string $newLanguage
    ) {}
}

class ExchangeRateUpdatedEvent
{
    public function __construct(
        public readonly string $fromCurrency,
        public readonly string $toCurrency,
        public readonly float $rate,
        public readonly \Carbon\Carbon $effectiveDate
    ) {}
}

class TranslationMissingEvent
{
    public function __construct(
        public readonly string $translationKey,
        public readonly string $languageCode,
        public readonly string $module
    ) {}
}
```

### Event Listeners

**Events from Other Modules:**

This module listens to user login events to apply language preferences:
- `UserLoggedInEvent` (SUB02) - Apply user's language preference

---

## Implementation Plans

**Note:** Implementation plans follow the naming convention `PLAN{number}-implement-{component}.md`

| Plan File | Requirements Covered | Milestone | Status |
|-----------|---------------------|-----------|--------|
| PLAN25-implement-localization.md | FR-LOC-001 to FR-LOC-008, BR-LOC-001 to BR-LOC-003 | MILESTONE 12 | Not Started |

**Implementation plan will be created separately using:** `.github/prompts/create-implementation-plan.prompt.md`

---

## Acceptance Criteria

### Functional Acceptance

- [ ] Multi-language user interfaces with runtime switching functional
- [ ] Multi-currency transactions with automatic conversion operational
- [ ] Exchange rate management with historical tracking working
- [ ] Date and time format localization functional
- [ ] Number formatting based on locale operational
- [ ] RTL language support (Arabic, Hebrew) working
- [ ] Translation management with fallback functional
- [ ] Country-specific tax and compliance rules operational

### Technical Acceptance

- [ ] All API endpoints return correct responses per OpenAPI spec
- [ ] Language switching completes within 200ms (PR-LOC-001)
- [ ] Currency conversion completes within 10ms (PR-LOC-002)
- [ ] System supports 50+ languages and 150+ currencies (SCR-LOC-001)
- [ ] Redis caching for translations and exchange rates functional (ARCH-LOC-001)
- [ ] Laravel localization framework integrated (ARCH-LOC-002)
- [ ] Translations stored in JSON format (ARCH-LOC-003)

### Security Acceptance

- [ ] Sensitive translations encrypted (SR-LOC-001)
- [ ] All exchange rate changes logged (SR-LOC-002)

### Integration Acceptance

- [ ] Integration with all transactional modules functional (IR-LOC-001)
- [ ] External exchange rate API integration working (IR-LOC-002)

---

## Testing Strategy

### Unit Tests

**Test Coverage Requirements:** Minimum 80% code coverage

**Key Test Areas:**
- Currency conversion calculations
- Number formatting for different locales
- Date formatting for different locales
- Translation fallback logic
- RTL text direction detection

**Example Tests:**
```php
test('currency conversion uses correct exchange rate', function () {
    $rate = ExchangeRate::factory()->create([
        'from_currency_code' => 'USD',
        'to_currency_code' => 'EUR',
        'rate' => 0.85,
        'effective_date' => today(),
    ]);
    
    $result = ConvertCurrencyAction::run(100, 'USD', 'EUR', today());
    
    expect($result)->toBe(85.0);
});

test('translation falls back to default language when missing', function () {
    $key = 'sales.order.status';
    
    // No French translation exists
    $result = TranslationService::get($key, 'fr');
    
    // Should return English (default) translation
    expect($result)->toBe('Order Status');
});

test('number formats correctly for different locales', function () {
    $number = 1234567.89;
    
    // US format
    $us = FormatNumberAction::run($number, 'en_US');
    expect($us)->toBe('1,234,567.89');
    
    // German format
    $de = FormatNumberAction::run($number, 'de_DE');
    expect($de)->toBe('1.234.567,89');
});
```

### Feature Tests

**API Integration Tests:**
- Switch user language preference
- Convert currency via API
- Update exchange rates
- Get translations for language

**Example Tests:**
```php
test('user can switch language preference', function () {
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)
        ->postJson('/api/v1/localization/user/switch-language', [
            'language_code' => 'es',
        ]);
    
    $response->assertOk();
    
    expect($user->fresh()->localizationPreferences->language_code)->toBe('es');
});

test('can convert currency via API', function () {
    ExchangeRate::factory()->create([
        'from_currency_code' => 'USD',
        'to_currency_code' => 'EUR',
        'rate' => 0.85,
        'effective_date' => today(),
    ]);
    
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)
        ->postJson('/api/v1/localization/convert', [
            'amount' => 100,
            'from_currency' => 'USD',
            'to_currency' => 'EUR',
        ]);
    
    $response->assertOk();
    expect($response->json('converted_amount'))->toBe(85.0);
});
```

### Integration Tests

**Cross-Module Integration:**
- Sales order shows prices in user's preferred currency
- Invoice displays amounts in correct currency with proper formatting
- Tax calculations use country-specific rules
- Date/time formats applied across all modules

### Performance Tests

**Load Testing Scenarios:**
- Language switching within 200ms (PR-LOC-001)
- Currency conversion within 10ms (PR-LOC-002)
- 50+ languages loading time
- 150+ currencies support
- Translation caching effectiveness

---

## Dependencies

### Feature Module Dependencies

**From Master PRD Section D.2.1:**

**Mandatory Dependencies:**
- **SUB01 (Multi-Tenancy)** - Tenant-specific localization preferences
- **SUB02 (Authentication & Authorization)** - User language preferences
- **SUB12 (Accounting)** - Multi-currency financial transactions

**Optional Dependencies:**
- **SUB17 (Sales)** - Currency conversion for pricing
- **SUB19 (Taxation)** - Country-specific tax rules
- All transactional modules - Localized UI and formatting

### External Package Dependencies

```json
{
  "require": {
    "php": "^8.2",
    "laravel/framework": "^12.0",
    "azaharizaman/erp-core": "^1.0",
    "lorisleiva/laravel-actions": "^2.0",
    "nesbot/carbon": "^3.0"
  },
  "require-dev": {
    "pestphp/pest": "^4.0"
  }
}
```

### Infrastructure Dependencies

- **Database:** PostgreSQL 14+ (for exchange rates, translations, settings)
- **Cache:** Redis 6+ (for translation and exchange rate caching)
- **External APIs:** Exchange rate providers (e.g., ECB, Open Exchange Rates)

---

## Feature Module Structure

### Directory Structure (in Monorepo)

```
packages/localization/
├── src/
│   ├── Actions/
│   │   ├── ConvertCurrencyAction.php
│   │   ├── SwitchLanguageAction.php
│   │   ├── FormatNumberAction.php
│   │   ├── FormatDateAction.php
│   │   └── UpdateExchangeRateAction.php
│   ├── Contracts/
│   │   ├── LocalizationServiceContract.php
│   │   └── ExchangeRateProviderContract.php
│   ├── Events/
│   │   ├── LanguageSwitchedEvent.php
│   │   ├── ExchangeRateUpdatedEvent.php
│   │   └── TranslationMissingEvent.php
│   ├── Listeners/
│   │   └── ApplyUserLanguagePreferenceListener.php
│   ├── Models/
│   │   ├── Language.php
│   │   ├── Currency.php
│   │   ├── ExchangeRate.php
│   │   ├── TenantLocalizationSettings.php
│   │   ├── UserLocalizationPreferences.php
│   │   ├── TranslationKey.php
│   │   ├── Translation.php
│   │   └── CountryTaxRule.php
│   ├── Observers/
│   │   └── ExchangeRateObserver.php
│   ├── Policies/
│   │   └── TranslationPolicy.php
│   ├── Repositories/
│   │   ├── LocalizationRepository.php
│   │   └── ExchangeRateRepository.php
│   ├── Services/
│   │   ├── LocalizationService.php
│   │   ├── CurrencyService.php
│   │   ├── TranslationService.php
│   │   ├── FormattingService.php
│   │   └── ExchangeRateProviders/
│   │       ├── ECBExchangeRateProvider.php
│   │       └── OpenExchangeRatesProvider.php
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Requests/
│   │   └── Resources/
│   ├── Console/
│   │   └── Commands/
│   │       └── SyncExchangeRatesCommand.php
│   └── LocalizationServiceProvider.php
├── tests/
│   ├── Feature/
│   │   ├── LanguageSwitchingTest.php
│   │   ├── CurrencyConversionTest.php
│   │   └── TranslationManagementTest.php
│   └── Unit/
│       ├── CurrencyConversionTest.php
│       ├── NumberFormattingTest.php
│       └── DateFormattingTest.php
├── database/
│   ├── migrations/
│   │   ├── 2025_01_01_000001_create_languages_table.php
│   │   ├── 2025_01_01_000002_create_currencies_table.php
│   │   ├── 2025_01_01_000003_create_exchange_rates_table.php
│   │   ├── 2025_01_01_000004_create_tenant_localization_settings_table.php
│   │   ├── 2025_01_01_000005_create_user_localization_preferences_table.php
│   │   ├── 2025_01_01_000006_create_translation_keys_table.php
│   │   ├── 2025_01_01_000007_create_translations_table.php
│   │   └── 2025_01_01_000008_create_country_tax_rules_table.php
│   ├── seeders/
│   │   ├── LanguageSeeder.php
│   │   ├── CurrencySeeder.php
│   │   └── CountryTaxRuleSeeder.php
│   └── factories/
│       └── ExchangeRateFactory.php
├── lang/
│   ├── en/
│   │   └── messages.json
│   ├── es/
│   │   └── messages.json
│   └── ar/
│       └── messages.json
├── routes/
│   └── api.php
├── config/
│   └── localization.php
├── composer.json
└── README.md
```

---

## Migration Path

This is a new module with no existing functionality to migrate from.

**Initial Setup:**
1. Install package via Composer
2. Publish migrations and run `php artisan migrate`
3. Run seeders for languages, currencies, and country tax rules
4. Set default tenant localization settings
5. Configure exchange rate API integration
6. Import base translations for supported languages
7. Set up cron job for automatic exchange rate sync

---

## Success Metrics

From Master PRD Section B.3:

**Adoption Metrics:**
- Multi-language usage > 30% of tenants
- Multi-currency transactions > 20% of tenants
- RTL language adoption > 10% in applicable regions

**Performance Metrics:**
- Language switching within 200ms (PR-LOC-001)
- Currency conversion within 10ms (PR-LOC-002)

**Accuracy Metrics:**
- Exchange rate accuracy > 99.9%
- Translation completeness > 95% for supported languages

**Operational Metrics:**
- Exchange rate update frequency: Daily
- Translation approval time < 48 hours

---

## Assumptions & Constraints

### Assumptions

1. Users prefer working in their native language when available
2. Exchange rate APIs provide reliable, timely data
3. Translation quality verified by native speakers
4. Country tax rules updated by compliance team
5. Date/time/number formats follow locale standards

### Constraints

1. Users can switch language anytime without re-authentication
2. Currency conversions use exchange rates effective at transaction date
3. Missing translations fallback to default language (English)
4. Language switching completes within 200ms
5. Currency conversion completes within 10ms
6. Support 50+ languages and 150+ currencies

---

## Monorepo Integration

### Development

- Lives in `/packages/localization/` during development
- Main app uses Composer path repository to require locally:
  ```json
  {
    "repositories": [
      {
        "type": "path",
        "url": "./packages/localization"
      }
    ],
    "require": {
      "azaharizaman/erp-localization": "@dev"
    }
  }
  ```
- All changes committed to monorepo

### Release (v1.0)

- Tagged with monorepo version (e.g., v1.0.0)
- Published to Packagist as `azaharizaman/erp-localization`
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
2. Create implementation plan: `PLAN25-implement-localization.md` in `/docs/plan/`
3. Break down into GitHub issues
4. Assign to MILESTONE 12 from Master PRD Section F.2.4
5. Set up feature module structure in `/packages/localization/`

---

🎉 **PROJECT COMPLETE!** 🎉

**All 13 Sub-PRDs Created:**
- SUB13-SUB17: Business Modules (HCM, Inventory, Backoffice, Purchasing, Sales)
- SUB18-SUB21: Supporting Modules (MDM, Taxation, Financial Reporting, Workflow)
- SUB22-SUB25: Integration & Global Modules (Notifications, API Gateway, Integration Connectors, Localization)

**Total Documentation:** ~93,000 lines across 13 Sub-PRD files

**Ready for:** Implementation plan creation using `.github/prompts/create-implementation-plan.prompt.md`
