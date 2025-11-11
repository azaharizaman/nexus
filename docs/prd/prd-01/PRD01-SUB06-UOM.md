# PRD01-SUB06: Unit of Measure (UOM) Management System

**Master PRD:** [../PRD01-MVP.md](../PRD01-MVP.md)  
**Feature Module Category:** Mandatory Feature Modules - Core Infrastructure  
**Related Sub-PRDs:** PRD01-SUB01 (Multi-Tenancy), PRD01-SUB07 (Inventory Management)  
**Composer Package:** `azaharizaman/erp-uom-management` (Published: `azaharizaman/laravel-uom-management`)  
**Version:** 1.0.0  
**Status:** Draft  
**Created:** November 11, 2025

---

## Executive Summary

The Unit of Measure (UOM) Management System provides **unit of measure management with precision conversion factors, automatic unit conversion logic, and rounding accuracy controls**. This mandatory feature module is the foundation for all quantity-based operations in the ERP system (inventory, purchasing, sales, manufacturing) ensuring consistent, accurate measurement handling across different units.

### Purpose

The UOM Management System solves the critical problem of **measurement consistency and conversion accuracy** in a multi-unit business environment. It enables:

1. **Standardization:** Define base units and conversion factors for all measurement types
2. **Automatic Conversion:** Seamlessly convert between units (kg ↔ lb, m ↔ ft, L ↔ gal)
3. **Precision Control:** Handle decimal precision and rounding for accurate calculations
4. **Multi-Unit Operations:** Support complex scenarios (buying in kg, selling in lb)
5. **Industry-Specific Units:** Extensible for specialized units (barrels, BTU, etc.)

### Scope

**Included in this Feature Module:**

- ✅ Pre-defined UOM library (length, mass, volume, area, count)
- ✅ Custom UOM creation per tenant
- ✅ Conversion factor management (6+ decimal precision)
- ✅ Automatic conversion logic between compatible units
- ✅ Base unit designation for each category
- ✅ Rounding rules and precision settings
- ✅ UOM validation on transactions
- ✅ Unit compatibility checks
- ✅ Search and filtering capabilities
- ✅ Tenant-scoped custom units

**Excluded from this Feature Module:**

- ❌ Currency conversion (separate module)
- ❌ Temperature conversion formulas (future enhancement)
- ❌ Complex unit expressions (kg/m², BTU/hr) - future enhancement
- ❌ Historical conversion rate tracking (not needed for UOM)

### Dependencies

**Mandatory Dependencies:**
- Laravel Framework v12.x
- PHP ≥ 8.2
- `brick/math` package for precision arithmetic
- PRD01-SUB01 (Multi-Tenancy System)

**Feature Module Dependencies:**
- **Mandatory:** SUB01 (Multi-Tenancy)

**Note:** This package is already published as `azaharizaman/laravel-uom-management` and is a required dependency in the main ERP application.

### Composer Package Information

- **Published Package:** `azaharizaman/laravel-uom-management`
- **Internal Package Name:** `azaharizaman/erp-uom-management`
- **Namespace:** `Azaharizaman\Uom`
- **Monorepo Location:** `/packages/uom-management/`
- **Installation:** `composer require azaharizaman/laravel-uom-management`
## Requirements

> **Note:** These requirements are derived from Master PRD Section F.2.3 - PRD01-SUB06 (Unit of Measure). For complete traceability and context, refer to the [Master PRD Requirements Table](../PRD01-MVP.md#f23-requirements-by-sub-prd).

### Functional Requirements (FR)

| Requirement ID | Description | Priority | Status |
|----------------|-------------|----------|--------|
| **FR-UOM-001** | Define **System UOMs** (meter, kilogram, liter, piece) seeded at installation | High | Planned |
| **FR-UOM-002** | Support **Tenant Custom UOMs** for industry-specific needs | High | Planned |
| **FR-UOM-003** | Store **Conversion Factors** with at least **6 decimal precision** for accurate unit conversions | High | Planned |
| **FR-UOM-004** | Support **UOM Categories** (length, mass, volume, area, count, time) | High | Planned |
| **FR-UOM-005** | Provide **Automatic Conversion** logic to translate quantities between compatible units | High | Planned |
| **FR-UOM-006** | Support **UOM activation/deactivation** without deletion | Medium | Planned |

### Business Rules (BR)

| Rule ID | Description | Status |
|---------|-------------|--------|
| **BR-UOM-001** | Each UOM category MUST have one **designated base unit** | Planned |
| **BR-UOM-002** | Conversion factors MUST be **stored relative to base unit** | Planned |
| **BR-UOM-003** | Only **compatible units within same category** can be converted | Planned |
| **BR-UOM-004** | **System UOMs cannot be deleted**, only deactivated | Planned |
| **BR-UOM-005** | **Tenant custom UOMs can be deleted** if not in use | Planned |

### Data Requirements (DR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **DR-UOM-001** | UOM MUST store: **code, name, category, base_unit, conversion_factor, precision, is_active** | Planned |
| **DR-UOM-002** | Conversion factors MUST use **DECIMAL(20,10)** or equivalent for precision | Planned |
| **DR-UOM-003** | System UOMs MUST be **seeded on installation** | Planned |

### Integration Requirements (IR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **IR-UOM-001** | Provide UOM conversion API for **all modules** (Inventory, Sales, Purchasing) | Planned |
| **IR-UOM-002** | Integrate with **azaharizaman/laravel-uom-management** package | Planned |

### Performance Requirements (PR)

| Requirement ID | Target | Status |
|----------------|--------|--------|
| **PR-UOM-001** | Unit conversions MUST maintain **rounding accuracy within 0.0001% tolerance** | Planned |
| **PR-UOM-002** | Conversion calculations MUST complete in **< 5ms** | Planned |

### Security Requirements (SR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SR-UOM-001** | Enforce **tenant isolation** on custom UOMs | Planned |
| **SR-UOM-002** | Prevent **deletion of UOMs in use** by inventory or transactions | Planned |

### Scalability Requirements (SCR)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SCR-UOM-001** | Support **1000+ custom UOMs** per tenant with efficient lookup | Planned |

### Architecture Requirements (ARCH)

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **ARCH-UOM-001** | Use **azaharizaman/laravel-uom-management** package as foundation | Planned |
| **ARCH-UOM-002** | Implement **precision-safe decimal math** using brick/math package | Planned |

### Event Requirements (EV)

| Event ID | Event Name | Trigger | Status |
|----------|------------|---------|--------|
| **EV-UOM-001** | `UOMCreatedEvent` | When new UOM is created | Planned |
| **EV-UOM-002** | `UOMDeactivatedEvent` | When UOM is deactivated | Planned |
| **EV-UOM-003** | `ConversionPerformedEvent` | When unit conversion is executed | Planned |

---

## Technical Specifications

### Database Schema

**Units of Measure Table:**

```sql
CREATE TABLE uoms (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NULL, -- NULL for system UOMs
    code VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL, -- 'length', 'mass', 'volume', 'area', 'count', 'time'
    base_unit VARCHAR(20) NOT NULL, -- Reference to base unit code in category
    conversion_factor DECIMAL(20, 10) NOT NULL DEFAULT 1.0,
    precision INT NOT NULL DEFAULT 2,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    is_system BOOLEAN NOT NULL DEFAULT FALSE,
    metadata JSONB NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    UNIQUE (tenant_id, code),
    INDEX idx_uoms_tenant (tenant_id),
    INDEX idx_uoms_category (category),
    INDEX idx_uoms_active (is_active)
);
```

**Standard UOM Categories and Base Units:**

| Category | Base Unit | Common Units |
|----------|-----------|--------------|
| **Length** | meter (m) | mm, cm, m, km, inch, foot, yard, mile |
| **Mass** | kilogram (kg) | mg, g, kg, ton, ounce, pound, ton (imperial) |
| **Volume** | liter (L) | mL, L, m³, fl oz, cup, pint, quart, gallon |
| **Area** | square meter (m²) | mm², cm², m², km², sq inch, sq foot, acre |
| **Count** | piece (pc) | pc, dozen, gross, box, case, pallet |
| **Time** | second (s) | s, min, hour, day, week, month, year |

**Example Conversion Factors (relative to base unit):**

```
Length (base: meter):
- millimeter (mm): 0.001
- centimeter (cm): 0.01
- meter (m): 1.0
- kilometer (km): 1000.0
- inch (in): 0.0254
- foot (ft): 0.3048
- yard (yd): 0.9144
- mile (mi): 1609.344

Mass (base: kilogram):
- milligram (mg): 0.000001
- gram (g): 0.001
- kilogram (kg): 1.0
- metric ton (t): 1000.0
- ounce (oz): 0.0283495
- pound (lb): 0.453592
- ton (imperial): 1016.047

Volume (base: liter):
- milliliter (mL): 0.001
- liter (L): 1.0
- cubic meter (m³): 1000.0
- fluid ounce (fl oz): 0.0295735
- gallon (gal): 3.78541
```

### API Endpoints

All endpoints follow `/api/v1/uoms` pattern:

| Method | Endpoint | Purpose | Auth Required |
|--------|----------|---------|---------------|
| GET | `/api/v1/uoms` | List all UOMs (system + tenant) | Yes |
| GET | `/api/v1/uoms/{code}` | Get specific UOM details | Yes |
| POST | `/api/v1/uoms` | Create custom UOM | Yes - Admin |
| PATCH | `/api/v1/uoms/{code}` | Update custom UOM | Yes - Admin |
| DELETE | `/api/v1/uoms/{code}` | Delete custom UOM (if unused) | Yes - Admin |
| GET | `/api/v1/uoms/category/{category}` | List UOMs by category | Yes |
| POST | `/api/v1/uoms/convert` | Convert quantity between units | Yes |
| GET | `/api/v1/uoms/compatible/{code}` | Get compatible units for conversion | Yes |

**Query Parameters:**

- `category` - Filter by category
- `is_active` - Filter by active status
- `search` - Search by code or name

**Request/Response Examples:**

**List UOMs:**
```json
// GET /api/v1/uoms?category=mass&is_active=true

// Response 200 OK
{
    "data": [
        {
            "code": "kg",
            "name": "Kilogram",
            "category": "mass",
            "base_unit": "kg",
            "conversion_factor": "1.0000000000",
            "precision": 3,
            "is_active": true,
            "is_system": true
        },
        {
            "code": "lb",
            "name": "Pound",
            "category": "mass",
            "base_unit": "kg",
            "conversion_factor": "0.4535920000",
            "precision": 3,
            "is_active": true,
            "is_system": true
        },
        {
            "code": "g",
            "name": "Gram",
            "category": "mass",
            "base_unit": "kg",
            "conversion_factor": "0.0010000000",
            "precision": 3,
            "is_active": true,
            "is_system": true
        }
    ]
}
```

**Create Custom UOM:**
```json
// POST /api/v1/uoms
{
    "code": "sack",
    "name": "Sack (50kg)",
    "category": "mass",
    "base_unit": "kg",
    "conversion_factor": 50.0,
    "precision": 2
}

// Response 201 Created
{
    "data": {
        "code": "sack",
        "name": "Sack (50kg)",
        "category": "mass",
        "base_unit": "kg",
        "conversion_factor": "50.0000000000",
        "precision": 2,
        "is_active": true,
        "is_system": false,
        "created_at": "2025-11-11T10:00:00Z"
    }
}
```

**Convert Units:**
```json
// POST /api/v1/uoms/convert
{
    "quantity": 10,
    "from_unit": "kg",
    "to_unit": "lb"
}

// Response 200 OK
{
    "data": {
        "original_quantity": 10.0,
        "original_unit": "kg",
        "converted_quantity": 22.046,
        "converted_unit": "lb",
        "precision": 3
    }
}
```

**Get Compatible Units:**
```json
// GET /api/v1/uoms/compatible/kg

// Response 200 OK
{
    "data": {
        "base_unit": "kg",
        "category": "mass",
        "compatible_units": [
            {"code": "mg", "name": "Milligram"},
            {"code": "g", "name": "Gram"},
            {"code": "kg", "name": "Kilogram"},
            {"code": "t", "name": "Metric Ton"},
            {"code": "oz", "name": "Ounce"},
            {"code": "lb", "name": "Pound"}
        ]
    }
}
```

### Service API

**Facade Usage:**
```php
use Azaharizaman\Uom\Facades\UomConverter;

// Convert quantity between units
$pounds = UomConverter::convert(10, 'kg', 'lb'); // 22.046

// Convert with precision control
$gallons = UomConverter::convert(10, 'L', 'gal', precision: 4); // 2.6417

// Check if units are compatible
$compatible = UomConverter::areCompatible('kg', 'lb'); // true
$compatible = UomConverter::areCompatible('kg', 'L'); // false

// Get base unit for category
$baseUnit = UomConverter::getBaseUnit('mass'); // 'kg'

// Normalize to base unit
$kilograms = UomConverter::toBaseUnit(22.046, 'lb'); // 10.0

// Create custom UOM
UomConverter::createUom([
    'code' => 'box',
    'name' => 'Box (12 pieces)',
    'category' => 'count',
    'base_unit' => 'pc',
    'conversion_factor' => 12.0
]);
```

### Events

**Domain Events Emitted by this Feature Module:**

| Event Class | When Fired | Payload |
|-------------|-----------|---------|
| `UomCreatedEvent` | After custom UOM created | `Uom $uom` |
| `UomUpdatedEvent` | After UOM updated | `Uom $uom, array $changes` |
| `UomDeletedEvent` | After UOM deleted | `Uom $uom` |
| `UomConversionPerformedEvent` | After unit conversion | `float $quantity, string $fromUnit, string $toUnit, float $result` |

---

## Implementation Plans

| Plan File | Requirements Covered | Milestone | Status |
|-----------|---------------------|-----------|--------|
| PLAN06-implement-uom-management.md | FR-UOM-003, FR-UOM-005, PR-UOM-001 | MILESTONE 1 | Not Started |

---

## Acceptance Criteria

- [ ] All standard UOM categories seeded
- [ ] Conversion factors accurate to 6+ decimals
- [ ] Automatic conversion working between compatible units
- [ ] Precision control functional
- [ ] Tenant custom UOMs supported
- [ ] Compatibility checks enforced
- [ ] Rounding accuracy within 0.0001% tolerance
- [ ] API endpoints functional
- [ ] Search and filtering working
- [ ] 100% test coverage

---

## Testing Strategy

### Unit Tests

```php
test('converts between mass units accurately', function () {
    $pounds = UomConverter::convert(10, 'kg', 'lb');
    
    expect($pounds)->toBeCloseTo(22.046, precision: 3);
});

test('maintains precision in conversions', function () {
    $meters = UomConverter::convert(39.3701, 'in', 'm');
    
    expect($meters)->toBeCloseTo(1.0, precision: 4);
});

test('prevents conversion between incompatible units', function () {
    UomConverter::convert(10, 'kg', 'L');
})->throws(IncompatibleUnitsException::class);

test('normalizes to base unit correctly', function () {
    $baseValue = UomConverter::toBaseUnit(2.20462, 'lb');
    
    expect($baseValue)->toBeCloseTo(1.0, precision: 5);
});
```

### Feature Tests

```php
test('can create custom tenant UOM', function () {
    $response = $this->actingAs($admin)
        ->postJson('/api/v1/uoms', [
            'code' => 'crate',
            'name' => 'Crate (24 bottles)',
            'category' => 'count',
            'base_unit' => 'pc',
            'conversion_factor' => 24.0
        ]);
    
    $response->assertCreated();
    expect($response->json('data.code'))->toBe('crate');
});

test('conversion API returns accurate results', function () {
    $response = $this->actingAs($user)
        ->postJson('/api/v1/uoms/convert', [
            'quantity' => 100,
            'from_unit' => 'cm',
            'to_unit' => 'm'
        ]);
    
    $response->assertOk();
    expect($response->json('data.converted_quantity'))->toBe(1.0);
});
```

---

## Dependencies

### Feature Module Dependencies

- **Mandatory:** SUB01 (Multi-Tenancy)

### External Package Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| `brick/math` | ^0.12 | High-precision decimal arithmetic |

---

## Success Metrics

| Metric | Target |
|--------|--------|
| Conversion Accuracy | 0.0001% tolerance |
| Conversion Performance | < 5ms |
| Precision Support | 10+ decimal places |

---

## Monorepo Integration

- Development: `/packages/uom-management/`
- Published as: `azaharizaman/laravel-uom-management`
- Repository: https://github.com/azaharizaman/laravel-uom-management

---

## References

- Master PRD: [../PRD01-MVP.md](../PRD01-MVP.md)
- Package Repository: https://github.com/azaharizaman/laravel-uom-management
- Brick Math Documentation: https://github.com/brick/math

---

**Document Status:** Draft - Pending Review  
**Last Updated:** November 11, 2025
