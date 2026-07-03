# Development Roadmap

Status: Baseline finalized

---

## Phase 0: Documentation Foundation

Deliverables:

- Master Development Blueprint.
- Project constitution.
- Architecture documentation.
- Database design documentation.
- API standards.
- UI and UX standards.
- Module and workflow baseline documents.

Exit criteria:

- Developers can start scaffolding without guessing core direction.

---

## Phase 1: Application Foundation

Deliverables:

- Slim Framework 4 setup.
- Configuration loading.
- PDO database connection.
- Migration tool.
- Standard API response helpers.
- Error middleware.
- Authentication foundation.
- RBAC foundation.
- Hotel scoping checks.
- Audit logging foundation.

Exit criteria:

- A protected sample endpoint can prove authentication, permission, hotel scope,
  standardized response, and audit logging.

---

## Phase 2: Hotel, User, and Room Core

Deliverables:

- Hotel groups.
- Hotels.
- Users.
- Roles and permissions.
- Room types.
- Rooms.
- Room status management.
- Basic room pricing.

Exit criteria:

- A hotel manager can configure rooms for one hotel without seeing another
  hotel's data.

---

## Phase 3: Guest, Reservation, Check-in, Checkout

Deliverables:

- Guest records.
- Identity document capture.
- Reservation creation.
- Availability checking.
- Advance payments.
- Check-in.
- Checkout.
- Room shift.
- Stay folio.

Exit criteria:

- The system supports booking to checkout for a real hotel stay.

---

## Phase 4: Accounts and Messaging

Deliverables:

- Payment methods.
- Split payments.
- Refund records.
- GST-ready invoice records.
- Daily collection report.
- MSG91 queue.
- SMS and WhatsApp logs.

Exit criteria:

- A stay can be settled and guest notifications can be queued and tracked.

---

## Phase 5: Aadhaar Verification

Deliverables:

- sandbox.co.in integration wrapper.
- Aadhaar verification workflow.
- Masked Aadhaar storage.
- Verification logs.
- Retry and failure handling.

Exit criteria:

- Reception can verify Aadhaar when available and proceed with manual capture
  when the provider is unavailable.

---

## Phase 6: Housekeeping

Deliverables:

- Cleaning tasks.
- Maintenance requests.
- Laundry records.
- Room readiness flow.
- Housekeeping dashboard.

Exit criteria:

- Checkout creates cleaning work and completed cleaning returns a room to
  available status.

---

## Phase 7: Inventory and Purchase

Deliverables:

- Vendors.
- Inventory items.
- Units.
- Purchase orders.
- Goods receipts.
- Batches and expiry.
- Stock ledger.
- Costing methods.
- Minimum stock alerts.
- Stock adjustment.
- Stock audit.

Exit criteria:

- Purchased goods increase stock through a traceable ledger, and adjustments are
  audited.

---

## Phase 8: Kitchen and Recipe Costing

Deliverables:

- Kitchen items.
- Recipes.
- Recipe costing.
- Room service orders.
- Meal plans.
- Inventory deduction.
- Kitchen cost reports.

Exit criteria:

- A room service order can deduct recipe ingredients and post charges to a stay.

---

## Phase 9: Employees

Deliverables:

- Employee profiles.
- Departments.
- Shifts.
- Attendance.
- Documents.
- Salary details.
- Emergency contacts.

Exit criteria:

- Hotel staff records and attendance can be managed by authorized users.

---

## Phase 10: Reporting and Release Hardening

Deliverables:

- Occupancy report.
- Revenue report.
- Check-in report.
- Checkout report.
- Inventory report.
- Purchase report.
- Vendor report.
- Daily collection report.
- GST report.
- PWA polish.
- Security review.
- Backup and restore documentation.

Exit criteria:

- HMS is ready for pilot deployment at one or more hotels.

