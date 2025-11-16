

## Personas & User Stories

### Personas

| ID | Persona | Role | Primary Goal |
|-----|---------|------|--------------|
| **P1** | Service Manager | Dispatch center lead | "Efficiently schedule and dispatch technicians to maximize utilization and customer satisfaction" |
| **P2** | Dispatcher | Operations coordinator | "Assign technicians to jobs based on skills, location, and priority" |
| **P3** | Field Technician | Mobile workforce | "Receive clear job instructions, complete work efficiently, and document service accurately" |
| **P4** | Customer | Service recipient | "Request service, track technician arrival, and receive timely service completion reports" |
| **P5** | Service Coordinator | Customer service team | "Manage customer service contracts, track SLA compliance, and handle service requests" |
| **P6** | Maintenance Planner | Preventive maintenance team | "Schedule recurring maintenance activities and ensure asset upkeep" |

### User Stories

#### Level 1: Basic Field Service (Essential MVP)

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| **US-001** | P1 | As a service manager, I want to create work orders specifying service location, work type, and priority | **High** |
| **US-002** | P2 | As a dispatcher, I want to assign work orders to available technicians based on skills and location | **High** |
| **US-003** | P3 | As a field technician, I want to view my assigned jobs for the day on my mobile device | **High** |
| **US-004** | P3 | As a field technician, I want to start a job, capture time spent, and upload before/after photos | **High** |
| **US-005** | P3 | As a field technician, I want to record parts/materials used during service | **High** |
| **US-006** | P3 | As a field technician, I want to capture customer signature upon job completion | **High** |
| **US-007** | P3 | As a field technician, I want the system to auto-generate a service report (PDF) for customer | **High** |
| **US-008** | P4 | As a customer, I want to receive a service completion report via email with photos and technician notes | **High** |
| **US-009** | P1 | As a service manager, I want to view work order status (new, scheduled, in progress, completed, verified) | **High** |

#### Level 2: Advanced Field Service (Scheduling & Quality)

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| **US-010** | P2 | As a dispatcher, I want to view a calendar/map showing all technicians and their assignments | **High** |
| **US-011** | P2 | As a dispatcher, I want route optimization to minimize travel time between jobs | **High** |
| **US-012** | P2 | As a dispatcher, I want to reassign jobs when technicians call in sick or jobs take longer than expected | **High** |
| **US-013** | P3 | As a field technician, I want to fill out job-specific checklists (safety inspection, quality checks) | **High** |
| **US-014** | P3 | As a field technician, I want the app to capture my GPS location when I start/end a job | **High** |
| **US-015** | P6 | As a maintenance planner, I want to define preventive maintenance schedules (monthly/quarterly/yearly) | **High** |
| **US-016** | P6 | As a maintenance planner, I want the system to auto-generate PM work orders based on schedule | **High** |
| **US-017** | P5 | As a service coordinator, I want to manage customer service contracts with SLA terms | **High** |
| **US-018** | P5 | As a service coordinator, I want to track SLA compliance (response time, resolution time) | **High** |
| **US-019** | P1 | As a service manager, I want to link work orders to customer assets/equipment for service history | **High** |

#### Level 3: Enterprise Field Service (Contracts, Analytics & Automation)

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| **US-020** | P1 | As a service manager, I want to auto-assign work orders based on technician skills, proximity, and availability | **High** |
| **US-021** | P1 | As a service manager, I want to track technician productivity (jobs completed, avg time per job) | **High** |
| **US-022** | P1 | As a service manager, I want to analyze first-time fix rate and identify recurring issues | **High** |
| **US-023** | P4 | As a customer, I want to submit service requests via a customer portal | Medium |
| **US-024** | P4 | As a customer, I want to track technician en-route status in real-time | Medium |
| **US-025** | P5 | As a service coordinator, I want to receive SLA breach alerts before deadlines expire | **High** |
| **US-026** | P1 | As a service manager, I want to integrate with IoT devices for predictive maintenance alerts | Medium |
| **US-027** | P1 | As a service manager, I want to generate billing automatically based on service hours and parts used | **High** |

---

## Functional Requirements

### FR-L1: Level 1 - Basic Field Service (Essential MVP)

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-L1-001** | Create work order | **High** | • Work order number (auto-generated via nexus-sequencing)<br>• Customer and service location<br>• Work category (maintenance, installation, inspection, cleaning, repair, emergency)<br>• Priority level (low, normal, high, urgent)<br>• Description of work required<br>• Status (new, scheduled, in_progress, completed, verified, closed)<br>• Optional: link to asset/equipment |
| **FR-L1-002** | Technician assignment | **High** | • Assign work order to technician<br>• Reassign to different technician<br>• Notify technician via mobile app/email<br>• Track assignment history (audit trail) |
| **FR-L1-003** | Technician daily schedule | **High** | • Mobile app view of assigned jobs<br>• Sortable by priority, scheduled time<br>• Show customer address on map<br>• Navigation integration (Google Maps, Waze) |
| **FR-L1-004** | Mobile job execution | **High** | • Start job (capture start time, GPS location)<br>• Upload before photos<br>• Add work notes/findings<br>• Upload after photos<br>• End job (capture end time)<br>• Calculate labor hours automatically |
| **FR-L1-005** | Parts/materials consumption | **High** | • Search inventory for parts<br>• Add part to work order (part number, quantity)<br>• Track van inventory vs warehouse stock<br>• Auto-deduct from inventory on job completion<br>• Flag out-of-stock parts |
| **FR-L1-006** | Customer signature capture | **High** | • Digital signature pad on mobile device<br>• Capture customer name and date<br>• Store signature image with work order<br>• Optional: capture customer feedback/rating |
| **FR-L1-007** | Auto-generate service report | **High** | • PDF report generation<br>• Includes: customer info, work performed, time spent, parts used, photos, signature<br>• Branded template (company logo, colors)<br>• Email to customer automatically<br>• Store in document management system |
| **FR-L1-008** | Work order status tracking | **High** | • Dashboard view of all work orders<br>• Filter by status, technician, date range<br>• Visual status indicators (color-coded)<br>• Quick actions: schedule, assign, complete |

### FR-L2: Level 2 - Advanced Field Service (Scheduling & Quality)

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-L2-001** | Scheduling calendar view | **High** | • Daily/weekly/monthly calendar<br>• Drag-and-drop job assignment<br>• Technician availability view<br>• Color-coded by job priority<br>• Conflict detection (overlapping appointments) |
| **FR-L2-002** | Map-based dispatch view | **High** | • Interactive map showing all jobs and technicians<br>• Real-time technician location (GPS tracking)<br>• Visual route lines<br>• Distance/travel time calculations<br>• Cluster jobs by geographic area |
| **FR-L2-003** | Route optimization | **High** | • Calculate optimal job sequence for technician<br>• Minimize total travel distance/time<br>• Consider job priority and time windows<br>• Integration with routing APIs (Google Maps Directions API)<br>• Suggest job reassignment to reduce travel |
| **FR-L2-004** | Dynamic job reassignment | **High** | • Reassign job in real-time<br>• Notify both technicians (old and new)<br>• Update schedule automatically<br>• Capture reassignment reason<br>• Track reassignment metrics |
| **FR-L2-005** | Job-specific checklists | **High** | • Define checklist templates (HVAC inspection, safety check)<br>• Attach checklist to work order by job type<br>• Technician fills checklist on mobile (checkboxes, text, photos)<br>• Pass/fail criteria per checklist item<br>• Auto-fail job if critical items fail |
| **FR-L2-006** | GPS location tracking | **High** | • Capture GPS coordinates on job start/end<br>• Store location with work order<br>• Display job location on map<br>• Calculate distance traveled<br>• Geofencing: auto-start job when technician arrives at location |
| **FR-L2-007** | Preventive maintenance planning | **High** | • Define PM schedules (time-based: monthly/quarterly/yearly)<br>• Define PM schedules (meter-based: every 1000 hours)<br>• Link PM schedule to asset/equipment<br>• Auto-generate PM work orders based on schedule<br>• PM checklist templates |
| **FR-L2-008** | Asset/equipment management | **High** | • Asset master (asset ID, description, location, model, serial)<br>• Link work orders to assets<br>• Asset service history (all past jobs)<br>• Asset condition tracking<br>• Maintenance schedule by asset |
| **FR-L2-009** | Service contract management | **High** | • Customer service contract (contract number, start/end dates)<br>• SLA terms (response time: 4 hours, resolution time: 24 hours)<br>• Contract coverage (assets covered, services included)<br>• Contract status (active, expired, renewed)<br>• Link work orders to contracts |
| **FR-L2-010** | SLA compliance tracking | **High** | • SLA timer on work orders (response time, resolution time)<br>• Visual indicators (on track, at risk, breached)<br>• SLA breach alerts (email, SMS)<br>• SLA compliance dashboard<br>• Historical SLA metrics (% on-time) |
| **FR-L2-011** | Technician skills matrix | **High** | • Define skills (electrical, plumbing, HVAC, inspection)<br>• Assign skills to technicians<br>• Certification tracking (expiry dates, renewal alerts)<br>• Auto-assign jobs based on required skills<br>• Skills gap analysis |

### FR-L3: Level 3 - Enterprise Field Service (Contracts, Analytics & Automation)

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-L3-001** | Auto-assign algorithm | **High** | • Evaluate technician skills, availability, proximity<br>• Consider job priority and SLA<br>• Consider travel time from current location<br>• Load balancing (distribute jobs evenly)<br>• Override auto-assignment if needed |
| **FR-L3-002** | Technician productivity analytics | **High** | • Jobs completed per day/week/month<br>• Average time per job<br>• Utilization rate (working hours / total hours)<br>• Overtime hours<br>• Jobs per technician comparison |
| **FR-L3-003** | First-time fix rate analysis | **High** | • Track jobs requiring follow-up visits<br>• Calculate first-time fix rate (% jobs completed on first visit)<br>• Identify recurring issues by asset/customer<br>• Root cause analysis workflow |
| **FR-L3-004** | Customer portal | Medium | • Customers submit service requests<br>• Track open jobs (status, assigned technician)<br>• View service history<br>• Download service reports/invoices<br>• Rate technician/service quality |
| **FR-L3-005** | Real-time technician tracking | Medium | • GPS tracking during working hours<br>• Show technician location on customer portal<br>• "Technician en route" notifications<br>• ETA calculations<br>• Privacy controls (GPS only during job hours) |
| **FR-L3-006** | SLA breach prevention | **High** | • Predictive alerts (30 minutes before SLA breach)<br>• Escalation workflow (notify manager, auto-reassign)<br>• SLA buffer time configuration<br>• Historical SLA performance reports |
| **FR-L3-007** | IoT integration for predictive maintenance | Medium | • Integrate with IoT sensors (temperature, vibration, pressure)<br>• Receive alerts when thresholds exceeded<br>• Auto-create work orders from IoT alerts<br>• Link IoT data to asset service history<br>• Trend analysis (predict failures before they occur) |
| **FR-L3-008** | Auto-billing integration | **High** | • Calculate labor cost (hours × billing rate)<br>• Add parts cost (from inventory)<br>• Add travel charges (distance × mileage rate)<br>• Auto-generate draft invoice after job completion<br>• Send invoice to nexus-accounting for posting |
| **FR-L3-009** | Webhook notifications | **High** | • Job created → notify customer<br>• Technician assigned → notify customer<br>• Technician en route → notify customer<br>• Job completed → notify customer<br>• Service report ready → notify customer |
| **FR-L3-010** | Advanced capacity planning | Medium | • Forecast future workload (historical trends)<br>• Identify technician capacity gaps<br>• Recommend hiring/outsourcing<br>• What-if analysis (add/remove technicians) |

---

## Non-Functional Requirements

### Performance Requirements

| ID | Requirement | Target | Notes |
|----|-------------|--------|-------|
| **PR-001** | Mobile app startup time | < 3 seconds | Including authentication and data sync |
| **PR-002** | Work order list loading (100 jobs) | < 1 second | With filtering and sorting |
| **PR-003** | Service report generation (with photos) | < 5 seconds | PDF generation with 10 photos |
| **PR-004** | Route optimization (20 jobs, 5 technicians) | < 10 seconds | Using Google Maps Directions API |
| **PR-005** | Auto-assignment algorithm | < 5 seconds | For single work order |
| **PR-006** | Offline mobile capability | Full functionality | Sync when connection restored |

### Security Requirements

| ID | Requirement | Scope |
|----|-------------|-------|
| **SR-001** | Tenant data isolation | All field service data MUST be tenant-scoped (via nexus-tenancy) |
| **SR-002** | Role-based access control | Enforce permissions: create-work-order, assign-technician, view-customer-data, manage-contracts |
| **SR-003** | Mobile app authentication | API token-based auth via Laravel Sanctum |
| **SR-004** | Customer signature security | Encrypted storage, tamper-proof (hashed with timestamp) |
| **SR-005** | GPS data privacy | Technician GPS data captured only during working hours, with consent |
| **SR-006** | Service report integrity | Completed service reports are immutable (audit trail) |
| **SR-007** | Customer portal access control | Customers see only their own work orders and service history |

### Reliability Requirements

| ID | Requirement | Notes |
|----|-------------|-------|
| **REL-001** | Mobile app offline mode | Technicians can work without internet, sync later |
| **REL-002** | Data sync conflict resolution | Last-write-wins with conflict logging |
| **REL-003** | Service report generation resilience | Retry failed PDF generation automatically |
| **REL-004** | GPS tracking fault tolerance | Continue operation if GPS unavailable |
| **REL-005** | Notification delivery guarantee | Queue notifications, retry failed deliveries |

### Usability Requirements

| ID | Requirement | Notes |
|----|-------------|-------|
| **US-001** | Mobile app simplicity | Technicians complete jobs with < 10 taps |
| **US-002** | Photo upload size limit | Max 5MB per photo, auto-compress if needed |
| **US-003** | Signature capture responsiveness | No lag when drawing signature |
| **US-004** | Offline indicator | Clear visual indicator when app is offline |
| **US-005** | Customer portal ease of use | Submit service request in < 2 minutes |

---


## Business Rules

| ID | Rule | Level |
|----|------|-------|
| **BR-001** | Work order must have a customer and service location | All levels |
| **BR-002** | Cannot assign work order to technician without required skills | Level 2+ |
| **BR-003** | Cannot start work order without assignment to technician | All levels |
| **BR-004** | Work order can only be completed if all critical checklist items pass | Level 2+ |
| **BR-005** | Parts consumption auto-deducts from technician van stock first, then warehouse | All levels |
| **BR-006** | Service report can only be generated after work order is completed | All levels |
| **BR-007** | Customer signature is required before work order can be marked verified | All levels |
| **BR-008** | SLA deadlines calculated from service contract terms | Level 2+ |
| **BR-009** | SLA breach triggers escalation workflow (notify manager, auto-reassign) | Level 3 |
| **BR-010** | Preventive maintenance work orders auto-generated 7 days before due date | Level 2+ |
| **BR-011** | Cannot schedule technician beyond their daily capacity (8 hours default) | Level 2+ |
| **BR-012** | GPS location capture required when starting/ending job | Level 2+ |
| **BR-013** | Asset must have maintenance schedule if covered by service contract | Level 2+ |
| **BR-014** | Expired service contracts prevent new work order creation (unless emergency) | Level 2+ |
| **BR-015** | Route optimization respects job time windows (scheduled start/end times) | Level 2+ |

---