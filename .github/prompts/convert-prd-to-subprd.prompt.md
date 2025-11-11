# Convert PRD to Sub-PRD Prompt

**Purpose:** Extract a specific module from a Master PRD and create a focused Sub-PRD  
**Version:** 1.0.0  
**Date:** November 10, 2025

---

## Prompt for AI Agent

```
I need you to create a Sub-PRD by extracting feature module-specific requirements from a Master PRD.

**Master PRD:** [Specify file path, e.g., /docs/prd/PRD01-MVP.md]
**Target Feature Module:** [Specify the Sub-PRD to generate, e.g., SUB01 for Multitenancy, SUB07 for Chart of Accounts, SUB14 for Inventory Management]
**Sub-PRD ID:** [Auto-derived from target, e.g., PRD01-SUB01, PRD01-SUB07]

**Important:** All modules in this system are **Feature Modules** - self-encapsulating units that will be released as separate Composer packages (`azaharizaman/erp-{module}`) on Packagist. Each Feature Module adheres to the monorepo concept outlined in Master PRD Section C.1.

Please follow these steps:

1. **Read the Master PRD** to understand the complete context and dependencies

2. **Extract Requirements** - Identify and extract ALL requirements related to the target Sub-PRD from Section F.2.3 of Master PRD:
   - Functional requirements (FR-*)
   - Business rules (BR-*)
   - Data requirements (DR-*)
   - Integration requirements (IR-*)
   - Performance requirements (PR-*)
   - Security requirements (SR-*)
   - Scalability requirements (SCR-*)
   - Compliance requirements (CR-*)
   - Architecture requirements (ARCH-*)
   - Event requirements (EV-*)

3. **Create Sub-PRD File** with naming convention from Section F.2.1 of Master PRD
   - Example: `PRD01-SUB01-MULTITENANCY.md`, `PRD01-SUB06-UOM.md` (use UPPERCASE as shown in F.2.1)
   - Save to: `/docs/prd/prd-01/`

4. **Include in Sub-PRD:**
   ```markdown
   # PRD{number}-SUB{subnumber}: {Feature Module Name from F.2.1}

   **Master PRD:** [Link to master PRD file, e.g., ../PRD01-MVP.md]
   **Feature Module Category:** [Category this belongs to from Master PRD Section D.2.1, e.g., "Mandatory Feature Modules" or "Optional Feature Modules - Finance & Accounting"]
   **Related Sub-PRDs:** [List other Sub-PRDs in same category]
   **Composer Package:** `azaharizaman/erp-{module}` (e.g., `azaharizaman/erp-multitenancy`)
   **Version:** 1.0.0
   **Status:** Draft
   **Created:** [Date]

   ---

   ## Executive Summary

   [Extract feature module description from Master PRD Section F.2.1]

   ### Purpose
   [What business problem does this feature module solve?]

   ### Scope
   [What is included and excluded from this feature module]

   ### Dependencies
   [List prerequisite feature modules from Master PRD Section D.2.1]

   ### Composer Package Information
   - **Package Name:** `azaharizaman/erp-{module}`
   - **Namespace:** `Azaharizaman\Erp\{Module}`
   - **Monorepo Location:** `/packages/{module}/`
   - **Installation:** `composer require azaharizaman/erp-{module}` (post v1.0 release)

   ## Requirements

   ### Functional Requirements (FR)
   [Extract all FR-{MODULE}-* items from Master PRD Section F.2.3]

   | Requirement ID | Description | Priority | Status |
   |----------------|-------------|----------|--------|
   | FR-{MODULE}-001 | ... | High | Planned |

   ### Business Rules (BR)
   [Extract all BR-{MODULE}-* items]

   | Rule ID | Description | Status |
   |---------|-------------|--------|
   | BR-{MODULE}-001 | ... | Planned |

   ### Data Requirements (DR)
   [Extract all DR-{MODULE}-* items]

   | Requirement ID | Description | Status |
   |----------------|-------------|--------|
   | DR-{MODULE}-001 | ... | Planned |

   ### Performance Requirements (PR)
   [Extract all PR-{MODULE}-* items]

   | Requirement ID | Target | Status |
   |----------------|--------|--------|
   | PR-{MODULE}-001 | < 200ms | Planned |

   ### Security Requirements (SR)
   [Extract all SR-{MODULE}-* items]

   | Requirement ID | Description | Status |
   |----------------|-------------|--------|
   | SR-{MODULE}-001 | ... | Planned |

   ### Integration Requirements (IR)
   [Extract all IR-{MODULE}-* items]

   | Requirement ID | Integration Point | Status |
   |----------------|------------------|--------|
   | IR-{MODULE}-001 | ... | Planned |

   ### Architecture Requirements (ARCH)
   [Extract all ARCH-{MODULE}-* items]

   | Requirement ID | Description | Status |
   |----------------|-------------|--------|
   | ARCH-{MODULE}-001 | ... | Planned |

   ## Technical Specifications

   ### Database Schema
   [Define tables, columns, indexes, relationships]

   ### API Endpoints
   [Define RESTful endpoints following /api/v1/{module}/ pattern]

   ### Events
   [Define domain events that this module emits]

   ### Event Listeners
   [Define what events from other modules this module listens to]

   ## Implementation Plans

   **Note:** Implementation plans follow the naming convention `PLAN{number}-{action}-{component}.md`

   | Plan File | Requirements Covered | Milestone | Status |
   |-----------|---------------------|-----------|--------|
   | PLAN{number}-implement-{module}.md | FR-{MODULE}-001, FR-{MODULE}-002, ... | MILESTONE {X} | Not Started |

   **Implementation plan will be created separately using:** `.github/prompts/create-implementation-plan.prompt.md`

   ## Acceptance Criteria

   [Define what "done" means for this module]

   - [ ] All functional requirements implemented
   - [ ] All tests passing (unit, feature, integration)
   - [ ] API documentation complete (OpenAPI/Swagger)
   - [ ] Performance benchmarks met
   - [ ] Security requirements validated
   - [ ] Code review completed
   - [ ] Migration scripts tested

   ## Testing Strategy

   ### Unit Tests
   [Specify unit test requirements]

   ### Feature Tests
   [Specify feature test requirements]

   ### Integration Tests
   [Specify integration test requirements with other modules]

   ### Performance Tests
   [Specify performance test requirements]

   ## Dependencies

   ### Feature Module Dependencies
   [From Master PRD Section D.2.1 - list required feature modules]
   
   - **Mandatory Dependencies:** [e.g., Core Infrastructure (SUB01, SUB02, SUB03, SUB05)]
   - **Optional Dependencies:** [e.g., UOM Management (SUB06) if applicable]

   ### External Package Dependencies
   [List required Composer packages beyond Laravel framework]

   ### Infrastructure Dependencies
   [List required services: database type, Redis, queue system, etc.]

   ## Feature Module Structure

   ### Directory Structure (in Monorepo)
   ```
   packages/{module}/
   ├── src/
   │   ├── Actions/
   │   ├── Contracts/
   │   ├── Events/
   │   ├── Listeners/
   │   ├── Models/
   │   ├── Repositories/
   │   ├── Services/
   │   └── {Module}ServiceProvider.php
   ├── tests/
   │   ├── Feature/
   │   └── Unit/
   ├── database/
   │   ├── migrations/
   │   └── factories/
   ├── composer.json
   └── README.md
   ```

   ## Migration Path

   [If this feature module replaces existing functionality, describe migration approach]

   ## Success Metrics

   [Define measurable success criteria from Master PRD Section B.3]

   ## Assumptions & Constraints

   ### Assumptions
   [List assumptions specific to this feature module]

   ### Constraints
   [List constraints specific to this feature module]

   ## Monorepo Integration

   ### Development
   - Lives in `/packages/{module}/` during development
   - Main app uses Composer path repository to require locally
   - All changes committed to monorepo

   ### Release (v1.0)
   - Tagged with monorepo version (e.g., v1.0.0)
   - Published to Packagist as `azaharizaman/erp-{module}`
   - Can be installed independently in external Laravel apps

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
   2. Create implementation plan: `PLAN{number}-implement-{component}.md` in `/docs/plan/`
   3. Break down into GitHub issues
   4. Assign to milestone from Master PRD Section F.2.4
   5. Set up feature module structure in `/packages/{module}/`
   ```

5. **Maintain Traceability:**
   - Link back to master PRD with relative path (e.g., `../PRD01-MVP.md`)
   - Reference implementation plan location (`/docs/plan/PLAN{number}-implement-{component}.md`)
   - Cross-reference related sub-PRDs from same feature module
   - Preserve all requirement IDs from master PRD Section F.2.3

6. **Verify Against Master PRD:**
   - Check Section F.2.1 for correct Sub-PRD filename (use UPPERCASE)
   - Check Section F.2.3 for all requirements with matching module code
   - Check Section D.2.1 for feature module grouping and dependencies
   - Check Section F.2.4 for milestone assignment

7. **Create Directory Structure (if needed):**
   ```bash
   mkdir -p /docs/prd/prd-01/
   ```

Please create the Sub-PRD now and confirm when complete.
```

---

## Quality Checklist

Before considering a Sub-PRD complete, verify:

- [ ] Correct filename from Master PRD Section F.2.1 (use UPPERCASE, e.g., `PRD01-SUB01-MULTITENANCY.md`)
- [ ] All relevant requirements extracted from Master PRD Section F.2.3
- [ ] Requirement IDs match Master PRD format (FR-{MODULE}-*, BR-{MODULE}-*, etc.)
- [ ] Feature module identified from Master PRD Section D.2.1
- [ ] Dependencies listed from Master PRD Section D.2.1
- [ ] Milestone referenced from Master PRD Section F.2.4
- [ ] No conflicting requirements
- [ ] Clear acceptance criteria defined
- [ ] Link to master PRD included (relative path: `../PRD01-MVP.md`)
- [ ] Saved to `/docs/prd/prd-01/` directory
- [ ] Technical specifications included (database, API, events)
- [ ] Testing strategy specified
- [ ] Implementation plan reference using correct naming: `PLAN{number}-implement-{component}.md`

---

## After Creating Sub-PRD

### 1. Review
- Product Manager reviews for completeness
- Stakeholders approve requirements
- Technical team validates feasibility

### 2. Create Implementation Plan
Use the prompt: `.github/prompts/create-implementation-plan.prompt.md`

```
Create an implementation plan for the following Sub-PRD:

**Sub-PRD:** /docs/prd/prd-01/PRD01-SUB01-MULTITENANCY.md
**Plan Filename:** PLAN01-implement-multitenancy.md
**Save to:** /docs/plan/

Follow the template in .github/prompts/create-implementation-plan.prompt.md
```

**Note:** Implementation plan naming follows: `PLAN{number}-{action}-{component}.md`
- Example: `PLAN01-implement-multitenancy.md`, `PLAN06-implement-uom.md`
- Not: `PRD01-SUB01-PLAN01-IMPLEMENT-MULTITENANCY.md` (old format)

### 3. Break into GitHub Issues
Use the prompt: `.github/prompts/create-issue-from-implementation-plan.prompt.md`

### 4. Assign to Milestone
Add to appropriate milestone in ROADMAP.md

---


## Related Prompts

- [create-implementation-plan.prompt.md](./create-implementation-plan.prompt.md) - Create PLAN from Sub-PRD
- [create-issue-from-implementation-plan.prompt.md](./create-issue-from-implementation-plan.prompt.md) - Create GitHub issues from PLAN

---

**Version:** 1.0.0  
**Maintained By:** Laravel ERP Development Team  
**Last Updated:** November 10, 2025
