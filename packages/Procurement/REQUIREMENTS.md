

## Personas & User Stories

### Personas

| ID | Persona | Role | Primary Goal |
|-----|---------|------|--------------|
| **P1** | Requester | Employee needing goods/services | "Submit a purchase requisition for office supplies and track approval status" |
| **P2** | Department Manager | Budget owner | "Approve requisitions within my budget authority and monitor departmental spend" |
| **P3** | Procurement Officer | Buyer/sourcing specialist | "Convert approved requisitions to RFQs, evaluate quotes, issue purchase orders efficiently" |
| **P4** | Warehouse Staff | Receiving clerk | "Record goods receipt accurately and match against purchase orders" |
| **P5** | Accounts Payable Clerk | Finance team | "Match vendor invoices against PO and GRN, authorize payment only when 3-way match succeeds" |
| **P6** | CFO/Finance Director | Executive oversight | "Enforce approval limits, monitor procurement spend, ensure compliance with purchasing policies" |
| **P7** | Vendor | External supplier | "View POs issued to me, submit quotes for RFQs, track payment status" |

### User Stories

#### Level 1: Basic Procurement (Simple PO Flow)

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| **US-001** | P1 | As a requester, I want to create a purchase requisition for items I need, specifying quantity, description, and estimated cost | **High** |
| **US-002** | P2 | As a department manager, I want to approve or reject requisitions from my team members with comments | **High** |
| **US-003** | P3 | As a procurement officer, I want to convert an approved requisition into a purchase order, selecting a vendor and negotiating final price | **High** |
| **US-004** | P3 | As a procurement officer, I want to create purchase orders directly (without requisition) for regular/recurring purchases | **High** |
| **US-005** | P4 | As warehouse staff, I want to record goods receipt against a PO, noting actual quantity received and any discrepancies | **High** |
| **US-006** | P5 | As AP clerk, I want to match a vendor invoice against the PO and GRN (3-way match) before authorizing payment | **High** |
| **US-007** | P1 | As a requester, I want to view the status of my requisitions (pending, approved, converted to PO, delivered) | Medium |

#### Level 2: Advanced Procurement (RFQ & Vendor Management)

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| **US-010** | P3 | As a procurement officer, I want to create an RFQ (Request for Quotation) for a requisition and invite multiple vendors to quote | **High** |
| **US-011** | P7 | As a vendor, I want to receive RFQ invitations via email and submit my quote through a vendor portal | **High** |
| **US-012** | P3 | As a procurement officer, I want to compare quotes side-by-side (price, delivery time, payment terms) and select the best vendor | **High** |
| **US-013** | P3 | As a procurement officer, I want to maintain a vendor master with contact details, payment terms, tax IDs, and performance ratings | **High** |
| **US-014** | P3 | As a procurement officer, I want to track vendor performance (on-time delivery, quality, pricing) to inform future sourcing decisions | Medium |
| **US-015** | P3 | As a procurement officer, I want to create blanket POs for recurring purchases with release schedules | Medium |
| **US-016** | P5 | As AP clerk, I want the system to enforce 3-way match tolerance rules (e.g., allow 5% quantity variance, reject if >10%) | **High** |

#### Level 3: Enterprise Procurement (Complex Workflows & Compliance)

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| **US-020** | P6 | As CFO, I want to define approval matrices where requisitions >$10K require director approval, >$50K require CFO approval | **High** |
| **US-021** | P3 | As a procurement officer, I want to conduct formal tender evaluations with weighted scoring (price 60%, quality 25%, delivery 15%) | Medium |
| **US-022** | P3 | As a procurement officer, I want to manage procurement contracts with renewal dates, terms, and compliance tracking | Medium |
| **US-023** | P6 | As CFO, I want to enforce separation of duties (requester ≠ approver ≠ receiver) automatically | **High** |
| **US-024** | P3 | As a procurement officer, I want to track import duties, customs clearance, and landed cost for international purchases | Medium |
| **US-025** | P6 | As CFO, I want to monitor procurement spend by category, department, vendor, and time period with drill-down analytics | Medium |
| **US-026** | P7 | As a vendor, I want to view my purchase orders, track payment status, and submit invoices through a vendor portal | Low |

---

## Functional Requirements

### FR-L1: Level 1 - Basic Procurement (Essential MVP)

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-L1-001** | Create purchase requisition with line items | **High** | • Multi-line item entry (item description, quantity, unit price estimate, GL account)<br>• Auto-save draft functionality<br>• Attach supporting documents (quotes, specifications)<br>• Auto-populate requester details |
| **FR-L1-002** | Requisition approval workflow | **High** | • Route to department manager based on requester's department<br>• Approval/rejection with mandatory comments<br>• Email notifications to requester and approver<br>• Track approval history with timestamps |
| **FR-L1-003** | Convert requisition to purchase order | **High** | • Select vendor from vendor master<br>• Copy requisition line items to PO<br>• Adjust quantities/prices during conversion<br>• Auto-generate PO number via nexus-sequencing<br>• Calculate taxes based on vendor jurisdiction |
| **FR-L1-004** | Direct purchase order creation | **High** | • Create PO without requisition (for regular purchases)<br>• Vendor selection with auto-populate payment terms<br>• Line item entry with GL account allocation<br>• Tax calculation based on tax codes<br>• PO approval workflow (if amount exceeds threshold) |
| **FR-L1-005** | Goods receipt note (GRN) creation | **High** | • Select pending POs for receiving<br>• Record actual quantity received per line item<br>• Note discrepancies (over/under delivery, damaged goods)<br>• Attach delivery note and inspection photos<br>• Partial receipts (receive PO in multiple shipments)<br>• Auto-update inventory levels |
| **FR-L1-006** | 3-way matching (PO-GRN-Invoice) | **High** | • Upload/scan vendor invoice<br>• Auto-match invoice to PO by PO number or vendor reference<br>• Compare invoice amount vs PO amount vs GRN quantity<br>• Flag discrepancies (price variance, quantity variance)<br>• Authorize payment if match succeeds within tolerance<br>• Route to AP manager if discrepancies exceed tolerance |
| **FR-L1-007** | Purchase requisition status tracking | Medium | • Real-time status updates (draft, pending approval, approved, converted, closed)<br>• Notification on status changes<br>• Audit trail of all state transitions |

### FR-L2: Level 2 - Advanced Procurement (Vendor & RFQ Management)

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-L2-001** | Vendor master management | **High** | • Vendor profile: name, address, contact details, tax ID, bank account<br>• Payment terms configuration (Net 30, 2/10 Net 30, COD)<br>• Tax status and withholding tax rules<br>• Currency preference and exchange rate handling<br>• Vendor category classification (goods vs services)<br>• Active/inactive status |
| **FR-L2-002** | Request for Quotation (RFQ) creation | **High** | • Create RFQ from approved requisition<br>• Define quote submission deadline<br>• Invite multiple vendors (3-5 typical)<br>• Specify evaluation criteria (price, delivery, payment terms)<br>• Attach specification documents |
| **FR-L2-003** | Vendor quote submission | **High** | • Vendors receive RFQ invitation via email with secure link<br>• Vendor portal for quote submission (line-by-line pricing)<br>• Support for alternate offers and comments<br>• Upload supporting documents (certificates, samples)<br>• Track quote submission status (pending, submitted, withdrawn) |
| **FR-L2-004** | RFQ evaluation and comparison | **High** | • Side-by-side quote comparison table<br>• Sort by price, delivery time, total cost<br>• Flag non-compliant quotes (missing items, late submission)<br>• Add evaluation notes per vendor<br>• Select winning vendor and auto-convert to PO |
| **FR-L2-005** | Vendor performance tracking | Medium | • Automatic metrics: on-time delivery rate, quality acceptance rate, price competitiveness<br>• Manual ratings: communication, responsiveness, flexibility<br>• Performance dashboard per vendor<br>• Use metrics in vendor selection recommendations |
| **FR-L2-006** | Blanket purchase orders | Medium | • Create blanket PO with total value limit and validity period<br>• Release mechanism: create release POs against blanket PO<br>• Track utilization (released value vs committed value)<br>• Auto-notify when 80% utilized or near expiry |
| **FR-L2-007** | 3-way match tolerance rules | **High** | • Configurable tolerance settings: price variance (%), quantity variance (%), total value variance (amount)<br>• Auto-approve if within tolerance<br>• Auto-route to supervisor if exceeds tolerance but below escalation threshold<br>• Auto-reject if exceeds escalation threshold (requires CFO approval) |

### FR-L3: Level 3 - Enterprise Procurement (Complex Workflows & Analytics)

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-L3-001** | Dynamic approval matrix | **High** | • Define approval rules based on amount thresholds, GL accounts, departments<br>• Multi-level approvals (Manager → Director → CFO)<br>• Parallel approvals (Finance AND Operations must both approve)<br>• Approval escalation if not actioned within SLA<br>• Configurable without code changes |
| **FR-L3-002** | Formal tender evaluation | Medium | • Define weighted evaluation criteria (price 60%, quality 20%, delivery 10%, sustainability 10%)<br>• Score each vendor against criteria<br>• Automatic weighted score calculation<br>• Tender evaluation committee (multiple reviewers)<br>• Generate tender evaluation report |
| **FR-L3-003** | Contract management | Medium | • Link POs to procurement contracts<br>• Track contract terms, renewal dates, value limits<br>• Auto-alert on contract expiry (90 days, 30 days)<br>• Contract compliance tracking (did we use the contracted vendor?)<br>• Contract amendment history |
| **FR-L3-004** | Separation of duties enforcement | **High** | • Requester cannot approve their own requisition<br>• PO creator cannot be the GRN receiver<br>• GRN receiver cannot approve the invoice payment<br>• System enforces these rules automatically<br>• Audit flag if violation attempted |
| **FR-L3-005** | Import/customs tracking | Medium | • Capture customs declaration details on PO<br>• Track import duties, freight, insurance (landed cost)<br>• Record customs clearance dates and documents<br>• Allocate landed cost to inventory items |
| **FR-L3-006** | Procurement analytics dashboard | Medium | • Spend by category, vendor, department, time period<br>• Trend analysis (spend growth, vendor concentration)<br>• Compliance metrics (% of POs with requisition, average approval time)<br>• Savings tracking (RFQ savings, contract compliance savings)<br>• Drill-down to transaction details |
| **FR-L3-007** | Vendor self-service portal | Low | • Vendor login with secure authentication<br>• View POs issued to vendor<br>• Submit invoices electronically<br>• Track payment status<br>• Download remittance advice<br>• Update vendor profile details |

---

## Non-Functional Requirements

### Performance Requirements

| ID | Requirement | Target | Notes |
|----|-------------|--------|-------|
| **PR-001** | Requisition creation and save | < 2 seconds | Including draft auto-save |
| **PR-002** | PO generation from requisition | < 3 seconds | Including tax calculation |
| **PR-003** | 3-way match processing | < 5 seconds | For PO with up to 50 line items |
| **PR-004** | Vendor quote comparison loading | < 2 seconds | For RFQ with 5 vendors, 20 items |
| **PR-005** | Procurement analytics dashboard | < 10 seconds | For 12-month data across 1000+ transactions |

### Security Requirements

| ID | Requirement | Scope |
|----|-------------|-------|
| **SR-001** | Tenant data isolation | All procurement data MUST be tenant-scoped (via nexus-tenancy) |
| **SR-002** | Role-based access control | Enforce permissions: create-requisition, approve-requisition, create-po, approve-po, create-grn, approve-payment |
| **SR-003** | Vendor data encryption | Sensitive vendor data (bank account, tax ID) MUST be encrypted at rest |
| **SR-004** | Audit trail completeness | ALL create/update/delete operations MUST be logged via nexus-audit-log |
| **SR-005** | Separation of duties | System MUST enforce SOD rules (requester ≠ approver ≠ receiver) |
| **SR-006** | Document access control | Attachments (quotes, invoices, contracts) MUST be access-controlled by role |

### Reliability Requirements

| ID | Requirement | Notes |
|----|-------------|-------|
| **REL-001** | All financial transactions MUST be ACID-compliant | Wrapped in database transactions |
| **REL-002** | 3-way match MUST prevent payment authorization if discrepancies exceed tolerance | Hard constraint, no bypass |
| **REL-003** | Approval workflows MUST be resumable after system failure | Use nexus-workflow persistence |
| **REL-004** | Concurrency control for PO approval | Prevent duplicate approvals via optimistic locking |

### Compliance Requirements

| ID | Requirement | Jurisdiction |
|----|-------------|--------------|
| **COMP-001** | Tax calculation MUST support GST/VAT/Sales Tax | Multi-jurisdiction support via nexus-tax-management |
| **COMP-002** | Withholding tax on vendor payments | Malaysia: 2-10% WHT based on vendor type |
| **COMP-003** | Import duty calculation | Track duty rates, tariff codes, customs declarations |
| **COMP-004** | Procurement approval limits | Configurable by organization (e.g., $5K → Manager, $50K → Director) |
| **COMP-005** | Contract compliance reporting | Track PO value vs contract value, flag non-compliant purchases |

---

## Business Rules

| ID | Rule | Level |
|----|------|-------|
| **BR-001** | A requisition MUST have at least one line item | All levels |
| **BR-002** | Requisition total estimate MUST equal sum of line item estimates | All levels |
| **BR-003** | Approved requisitions cannot be edited (only cancelled) | All levels |
| **BR-004** | A purchase order MUST reference an approved requisition OR be explicitly marked as direct PO | All levels |
| **BR-005** | PO total amount MUST NOT exceed requisition approved amount by more than 10% without re-approval | Level 2 |
| **BR-006** | GRN quantity cannot exceed PO quantity for any line item | All levels |
| **BR-007** | 3-way match tolerance rules are configurable per tenant | Level 2 |
| **BR-008** | Payment authorization requires successful 3-way match OR manual override by authorized user | All levels |
| **BR-009** | Requester cannot approve their own requisition | Level 3 (SOD) |
| **BR-010** | PO creator cannot create GRN for the same PO | Level 3 (SOD) |
| **BR-011** | GRN creator cannot authorize payment for the same PO | Level 3 (SOD) |
| **BR-012** | Blanket PO releases cannot exceed blanket PO total committed value | Level 2 |
| **BR-013** | Vendor quote must be submitted before RFQ deadline to be considered valid | Level 2 |
| **BR-014** | Tax calculation based on vendor jurisdiction and tax codes from nexus-tax-management | All levels |
| **BR-015** | All procurement amounts must be in tenant's base currency OR converted at transaction date exchange rate | All levels |

---