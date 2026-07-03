# Hotel Hospitality Management System

# Master Development Blueprint

Version: 0.1  
Status: Draft baseline  
Audience: Owners, developers, AI agents, QA, and future maintainers

---

## 1. Purpose

This document is the single authoritative development blueprint for the
Hotel Hospitality Management System (HMS).

It replaces a traditional SRS for this project and defines the product vision,
architecture, modules, workflows, database direction, API conventions, UI
standards, permissions, integrations, validation rules, error handling, coding
standards, development phases, and future expansion boundaries.

All future documentation should either:

- Extend this blueprint.
- Reference this blueprint.
- Record a deliberate change to this blueprint.

If there is a conflict between another document and this blueprint, this
blueprint wins unless a newer approved decision record explicitly updates it.

---

## 2. Product Vision

HMS is a web-based hospitality management platform for owners of multiple small
to medium hotels.

The system manages day-to-day hotel operations from reservation to checkout,
including rooms, guests, identity verification, payments, housekeeping, kitchen
service, inventory, purchases, employees, accounting, reporting, and audit logs.

The product must feel simple to hotel staff while remaining modular and
enterprise-quality internally.

### 2.1 Target Users

Primary users:

- Hotel owners managing one or more hotels.
- Group managers overseeing multiple hotels.
- Hotel managers.
- Reception staff.
- Kitchen staff.
- Store and inventory staff.
- Housekeeping staff.
- Accountants.

Most users are expected to be non-technical. The software must reduce training
time and avoid technical terminology in daily workflows.

### 2.2 Golden Rule

The software is built for hotel staff, not software professionals.

If a feature can be simplified without sacrificing correctness, security, audit
quality, or legal compliance, it should be simplified.

### 2.3 Product Boundaries

Included:

- Multi-hotel hotel management.
- Room and reservation management.
- Guest management and identity records.
- Aadhaar verification through sandbox.co.in.
- SMS and WhatsApp through MSG91.
- Kitchen-only room service and meal plan support.
- Inventory, purchases, vendors, batches, expiry, costing, and stock audits.
- Housekeeping, laundry, and maintenance.
- Employee records and attendance.
- GST-ready accounts and payments.
- Reports and audit logs.
- Responsive PWA.

Excluded:

- Public restaurant operations.
- Table management.
- Point-of-sale restaurant billing.
- Cloud-specific architecture.
- Hosting-provider backup automation.

---

## 3. Technology Decisions

### 3.1 Backend

- Language: PHP 8.3 or newer.
- Framework: Slim Framework 4.
- Database: MySQL 8 or newer.
- Database access: PDO.
- ORM: Not used.
- Architecture style: Service + Repository.
- API style: API-first JSON backend.
- Authentication: Token-based authentication.
- Authorization: RBAC with permission checks.

### 3.2 Frontend

- Responsive web application.
- Progressive Web App.
- Must support desktop, laptop, Android, iPhone, and tablet.
- UI should prioritize large touch targets and simple workflows.

Frontend implementation is an SPA. The backend must remain API-first so the
interface can evolve independently.

### 3.3 Hosting

- Initial target: Shared hosting.
- Future target: VPS migration.
- Avoid cloud-provider lock-in.
- Avoid architecture that requires workers unavailable on shared hosting unless
  there is a fallback.

### 3.4 Queue Strategy

Messaging and deferred tasks should use queue-based processing.

Because shared hosting is the first deployment target, the queue implementation
must support a simple database-backed queue and cron-based worker execution.

Future VPS deployments may replace this with a dedicated queue worker.

---

## 4. Core Architectural Principles

### 4.1 Modularity

Each business area must be treated as a module with clear boundaries.

Examples:

- Hotels
- Users and permissions
- Rooms
- Guests
- Reservations
- Check-in and checkout
- Kitchen
- Inventory
- Purchases
- Housekeeping
- Employees
- Accounts
- Reports
- Integrations
- Audit logs

### 4.2 Layering

The backend should follow this flow:

HTTP route -> controller/action -> request validation -> service -> repository
-> database

Responsibilities:

- Routes map HTTP requests to handlers.
- Controllers parse request context and return responses.
- Validators enforce input rules.
- Services contain business logic.
- Repositories contain SQL and persistence logic.
- Entities or DTOs carry structured data.
- Policies enforce permissions and ownership boundaries.

### 4.3 Hotel Scoping

Every operational record must belong to a hotel unless it is deliberately global.

Examples of hotel-scoped records:

- Rooms
- Room types
- Reservations
- Stays
- Guests linked to stays
- Kitchen orders
- Inventory items
- Purchases
- Goods receipts
- Housekeeping tasks
- Employee assignments
- Payments
- Reports

Examples of global or owner-level records:

- Owner account.
- Hotel group.
- Global roles.
- System configuration.
- Master permissions.

Every query that returns hotel data must enforce hotel scope.

### 4.4 Auditability

Important changes must be auditable.

Audit logs must record:

- Entity type.
- Entity ID.
- Action.
- Old value.
- New value.
- User ID.
- Hotel ID when applicable.
- Timestamp.
- IP address.
- User agent when available.

### 4.5 Security First

Security decisions must be practical for small hotels but not casual.

Required practices:

- Password hashing using modern PHP password APIs.
- Token authentication protections for the SPA, including short-lived tokens,
  safe token handling, logout invalidation where practical, and HTTPS in
  production.
- Permission checks at backend boundaries.
- Server-side validation for all input.
- Parameterized SQL through PDO.
- Mask sensitive identity data where possible.
- Never log full Aadhaar numbers.
- Restrict file uploads by type, size, and storage path.
- Record important financial and identity changes in audit logs.

---

## 5. User Roles and Permissions

### 5.1 Default Roles

The system starts with these roles:

- Super Admin
- Group Manager
- Hotel Manager
- Reception
- Kitchen Manager
- Store Manager
- Housekeeping
- Accountant

Roles are templates. Actual access must be permission-based so owners can adjust
access by hotel and staff responsibility.

### 5.2 Permission Model

Use RBAC with named permissions.

Permission format:

`module.action`

Examples:

- `rooms.view`
- `rooms.manage`
- `reservations.create`
- `reservations.cancel`
- `checkin.perform`
- `checkout.perform`
- `guests.verify_identity`
- `payments.collect`
- `inventory.view`
- `inventory.adjust`
- `purchases.approve`
- `reports.view_financial`
- `settings.manage`

### 5.3 Hotel Access

A user may have access to:

- One hotel.
- Multiple selected hotels.
- All hotels owned by the group.
- The full system, for Super Admin only.

Permissions must be evaluated together with hotel access.

---

## 6. Core Modules

### 6.1 Hotel and Group Management

Purpose:

Manage hotel groups, hotels, owner accounts, branches, addresses, tax details,
and hotel-level configuration.

Key features:

- Unlimited hotels.
- One owner can manage multiple hotels.
- Hotel-specific room inventory.
- Hotel-specific kitchen inventory.
- Hotel-specific employees.
- Hotel-specific reports.
- Future inter-hotel stock transfer support.

Key records:

- Hotel group.
- Hotel.
- Hotel settings.
- Hotel tax settings.
- Hotel billing settings.

### 6.2 Users, Roles, and Permissions

Purpose:

Manage login access, staff roles, permission templates, and hotel assignment.

Key features:

- User accounts.
- Role assignment.
- Permission overrides.
- Active/inactive status.
- Password reset.
- Login audit.

### 6.3 Room Management

Purpose:

Manage room inventory, room types, pricing, availability, status, and movement.

Key features:

- Unlimited room types.
- Room numbers and floors.
- Base pricing.
- Seasonal pricing.
- Holiday pricing.
- Weekend pricing.
- Extra bed support.
- Multiple guests per room.
- Room shifting.
- Room status lifecycle.

Room statuses:

- Available
- Reserved
- Occupied
- Cleaning
- Maintenance
- Blocked

### 6.4 Guest Management

Purpose:

Maintain complete guest history and identity records.

Supported identity documents:

- Aadhaar
- Passport
- Driving License
- Voter ID

Key features:

- Guest profile.
- Guest contact details.
- Identity document records.
- Masked Aadhaar storage.
- Aadhaar verification response.
- Aadhaar verification timestamp.
- Optional guest photo.
- Stay history.
- Payment history.
- Notes and flags.

### 6.5 Reservation Management

Purpose:

Manage booking creation, confirmation, advance payments, cancellation, and
availability.

Supported booking sources:

- Walk-in
- Phone
- Other, with source details.

Key features:

- Advance bookings.
- Multiple rooms per reservation.
- Multiple guests per room.
- Partial payments.
- Configurable cancellation policy.
- Reservation status tracking.
- Auto availability updates.

Suggested reservation statuses:

- Draft
- Confirmed
- Checked In
- Completed
- Cancelled
- No Show

### 6.6 Check-in and Checkout

Purpose:

Manage the guest stay lifecycle.

Key features:

- Early check-in.
- Late checkout.
- Overstay calculation.
- Automatic room status updates.
- Guest movement tracking.
- Room shifting.
- Extra bed charges.
- Stay folio.
- Final bill settlement.

Guest movement tracking is a critical system requirement.

The system must be able to answer:

- Which guest stayed in which room?
- During what dates and times?
- Under which reservation?
- Who performed the check-in, room shift, or checkout?
- What payments were collected?

### 6.7 Kitchen

Purpose:

Support hotel kitchen operations for guests only.

Not included:

- Restaurant tables.
- External customer billing.
- Public restaurant POS.

Key features:

- Room service orders.
- Meal plans.
- Kitchen order status.
- Recipe-based inventory deduction.
- Kitchen cost reporting.

Meal plans:

- Room Only
- CP
- MAP
- AP

### 6.8 Recipe and Ingredient Management

Purpose:

Define recipes and automatically deduct ingredients from inventory.

Key features:

- Recipe ingredients.
- Recipe costing.
- Portion size.
- Wastage allowance.
- Ingredient substitution notes.
- Auto deduction on order completion.

Inventory hierarchy examples:

- Chicken -> Whole, Breast, Leg, Wings, Boneless.
- Milk -> Butter, Cream, Paneer.
- Rice -> Cooked Rice.

Inventory transformation must be supported.

### 6.9 Inventory

Purpose:

Track stock per hotel with batches, costing, expiry, and movement history.

Key features:

- Inventory items.
- Categories.
- Units of measure.
- Batch numbers.
- Expiry dates.
- FIFO.
- LIFO.
- Weighted average costing.
- Stock adjustment.
- Stock audit.
- Minimum stock alerts.
- Inventory transformation.
- Future stock transfer between hotels.

### 6.10 Purchase and Vendor Management

Purpose:

Manage vendors, purchase orders, goods receipts, and purchase reporting.

Key features:

- Vendors.
- Purchase orders.
- Goods receipt notes.
- Purchase returns.
- Batch capture.
- Expiry capture.
- Purchase tax details.
- Vendor reports.

### 6.11 Housekeeping

Purpose:

Track cleaning, laundry, and maintenance work.

Key features:

- Cleaning tasks.
- Laundry tracking.
- Maintenance requests.
- Room readiness updates.
- Staff assignment.
- Task status.
- Task history.

### 6.12 Employees

Purpose:

Manage employee information and basic HR data.

Key features:

- Employee profile.
- Department.
- Shift.
- Attendance.
- Salary details.
- Documents.
- Emergency contact.
- Active/inactive status.

### 6.13 Accounts and Payments

Purpose:

Record payments, taxes, settlements, refunds, and daily collections.

Supported payment methods:

- Cash
- Card
- UPI
- Bank
- Wallet
- Split payments

Key features:

- GST support.
- Payment allocation.
- Refunds.
- Advance payments.
- Final settlement.
- Daily collection report.
- Payment audit trail.

### 6.14 Reports

Purpose:

Provide operational and financial visibility.

Required reports:

- Occupancy.
- Revenue.
- Kitchen cost.
- Inventory.
- Check-ins.
- Check-outs.
- Purchases.
- Vendor reports.
- Daily collection.
- GST.

Reports must respect hotel access and permissions.

### 6.15 Integrations

Purpose:

Centralize third-party communication and make failures visible.

Initial integrations:

- sandbox.co.in for Aadhaar verification.
- MSG91 for SMS and WhatsApp.

Integration calls should be logged with:

- Provider.
- Request type.
- Related entity.
- Status.
- Response code.
- Response summary.
- Retry count.
- Created timestamp.
- Completed timestamp.

Do not store secrets in code.

---

## 7. Major Workflows

### 7.1 Reservation to Checkout

1. Reception creates reservation.
2. System checks room availability.
3. Guest details are added.
4. Identity document is captured.
5. Aadhaar verification is performed when applicable.
6. Advance payment is recorded if collected.
7. Reservation is confirmed.
8. Guest arrives.
9. Reception performs check-in.
10. Room status changes to Occupied.
11. Room service and other charges are added to the stay folio.
12. Room shift is recorded if needed.
13. Checkout is initiated.
14. Late checkout or overstay charges are calculated if applicable.
15. Final payment is collected.
16. Invoice or receipt is generated.
17. Room status changes to Cleaning.
18. Housekeeping completes cleaning.
19. Room status changes to Available.

### 7.2 Walk-in Check-in

1. Reception selects walk-in check-in.
2. System shows available rooms.
3. Guest details are captured.
4. Identity is captured and verified when applicable.
5. Room and rate are selected.
6. Payment or advance is collected if required.
7. Check-in is completed.
8. Room status changes to Occupied.

### 7.3 Room Shift

1. Reception opens active stay.
2. User selects room shift.
3. System shows available rooms.
4. User selects target room.
5. Reason is required.
6. System records old room, new room, user, timestamp, and reason.
7. Old room moves to Cleaning or Maintenance based on selection.
8. New room moves to Occupied.

### 7.4 Kitchen Room Service

1. Staff creates kitchen order for an active room or stay.
2. Kitchen accepts order.
3. Kitchen prepares item.
4. Order is marked served.
5. Recipe ingredients are deducted from inventory.
6. Charges are added to stay folio unless included in meal plan.
7. Kitchen cost report is updated.

### 7.5 Purchase to Inventory

1. Store Manager creates purchase order.
2. Purchase order is approved if approval is enabled.
3. Goods are received.
4. Batch, expiry, quantity, rate, and tax are captured.
5. Inventory stock is increased.
6. Vendor balance and purchase reports are updated.
7. Audit log is recorded.

### 7.6 Stock Adjustment

1. Store Manager opens stock adjustment.
2. Item, batch, and quantity change are selected.
3. Reason is required.
4. Adjustment is submitted.
5. Stock ledger is updated.
6. Audit log is recorded.

### 7.7 Housekeeping Cleaning Cycle

1. Checkout marks room as Cleaning.
2. Cleaning task is created.
3. Housekeeping staff is assigned.
4. Staff marks task in progress.
5. Staff marks task completed.
6. Manager or configured rule marks room Available.

---

## 8. Database Design Direction

### 8.1 Naming Rules

- Table names: plural snake_case.
- Column names: snake_case.
- Primary key: `id`.
- Foreign key: `{table_singular}_id`.
- Timestamps: `created_at`, `updated_at`, `deleted_at` when soft delete is used.
- Use UTC timestamps in storage where practical.

### 8.2 Required Common Columns

Operational hotel-scoped tables should usually include:

- `id`
- `hotel_id`
- `created_by`
- `updated_by`
- `created_at`
- `updated_at`
- `deleted_at`

Some tables may also include:

- `status`
- `notes`
- `metadata_json`

### 8.3 Suggested Core Tables

Identity and access:

- `users`
- `roles`
- `permissions`
- `role_permissions`
- `user_roles`
- `user_hotel_access`
- `login_logs`

Hotel structure:

- `hotel_groups`
- `hotels`
- `hotel_settings`

Rooms and pricing:

- `room_types`
- `rooms`
- `room_rates`
- `seasonal_rate_rules`
- `holiday_rate_rules`
- `room_status_logs`

Guests and identity:

- `guests`
- `guest_identity_documents`
- `guest_photos`
- `identity_verification_logs`

Reservations and stays:

- `reservations`
- `reservation_rooms`
- `reservation_guests`
- `stays`
- `stay_rooms`
- `stay_guests`
- `room_shift_logs`
- `folio_items`

Payments and accounting:

- `payments`
- `payment_allocations`
- `refunds`
- `tax_rates`
- `invoices`
- `invoice_items`

Kitchen:

- `meal_plans`
- `kitchen_items`
- `recipes`
- `recipe_items`
- `kitchen_orders`
- `kitchen_order_items`

Inventory and purchases:

- `inventory_categories`
- `inventory_items`
- `units`
- `stock_batches`
- `stock_ledger`
- `stock_adjustments`
- `stock_audits`
- `inventory_transformations`
- `vendors`
- `purchase_orders`
- `purchase_order_items`
- `goods_receipts`
- `goods_receipt_items`

Housekeeping:

- `housekeeping_tasks`
- `laundry_records`
- `maintenance_requests`

Employees:

- `employees`
- `employee_documents`
- `employee_attendance`
- `departments`
- `shifts`

Integrations and audit:

- `message_queue`
- `message_logs`
- `integration_logs`
- `audit_logs`

### 8.4 Stock Ledger Principle

Inventory must use a ledger model.

Stock quantity should be derived from stock movement history, with cached
balances allowed for performance if they can be reconciled.

Ledger movement examples:

- Purchase receipt.
- Kitchen consumption.
- Stock adjustment.
- Transformation input.
- Transformation output.
- Expiry write-off.
- Transfer out, future.
- Transfer in, future.

### 8.5 Financial Records Principle

Financial records should be append-friendly.

Corrections should generally create adjustment records instead of silently
overwriting collected payment history.

---

## 9. API Standards

### 9.1 General Rules

- Use JSON request and response bodies.
- Use consistent response envelopes.
- Use HTTP status codes correctly.
- Validate on the server.
- Enforce authorization on every protected endpoint.
- Never rely on frontend-only permissions.

### 9.2 Response Envelope

Success:

```json
{
  "success": true,
  "data": {},
  "message": "Saved successfully"
}
```

Validation error:

```json
{
  "success": false,
  "message": "Please correct the highlighted fields.",
  "errors": {
    "guest_name": ["Guest name is required."]
  }
}
```

System error:

```json
{
  "success": false,
  "message": "Something went wrong. Please try again."
}
```

### 9.3 HTTP Status Codes

- `200`: Success.
- `201`: Created.
- `204`: Success with no response body.
- `400`: Bad request.
- `401`: Not authenticated.
- `403`: Not authorized.
- `404`: Not found.
- `409`: Conflict, such as room no longer available.
- `422`: Validation failed.
- `429`: Rate limited.
- `500`: Server error.

### 9.4 Endpoint Naming

Use predictable REST-style routes:

- `GET /api/hotels`
- `POST /api/hotels`
- `GET /api/hotels/{hotelId}/rooms`
- `POST /api/hotels/{hotelId}/reservations`
- `POST /api/hotels/{hotelId}/reservations/{reservationId}/check-in`
- `POST /api/hotels/{hotelId}/stays/{stayId}/check-out`
- `POST /api/hotels/{hotelId}/stays/{stayId}/room-shifts`
- `GET /api/hotels/{hotelId}/reports/occupancy`

### 9.5 Pagination and Filtering

List endpoints should support:

- `page`
- `per_page`
- `search`
- `sort`
- `direction`
- Module-specific filters.

Default `per_page` should be conservative to support shared hosting.

---

## 10. Validation Rules

### 10.1 General Validation

- Required fields must be explicit.
- Dates must be valid and logical.
- Money values must be non-negative unless the workflow allows refunds or
  adjustments.
- IDs must exist and belong to the current hotel scope.
- Status transitions must be valid.
- File uploads must be size and type checked.

### 10.2 Reservation Validation

- Check-in date must be before checkout date.
- At least one room is required.
- Room must be available for requested dates.
- Guest contact should be captured when available.
- Source must be one of the configured booking sources.
- Cancellation must follow configured policy.

### 10.3 Check-in Validation

- Room must be available, reserved for the guest, or explicitly overridden by a
  permitted user.
- Required guest identity details must be captured.
- Check-in cannot be completed for a cancelled reservation.
- Advance payment rules must be enforced if configured.

### 10.4 Checkout Validation

- Stay must be active.
- All required charges must be posted.
- Payment settlement rules must be applied.
- Late checkout and overstay rules must be evaluated.
- Room status must move to Cleaning unless an authorized user selects another
  valid status.

### 10.5 Inventory Validation

- Stock cannot go negative unless a hotel setting explicitly allows temporary
  negative stock.
- Batch and expiry are required for configured item categories.
- Adjustment reason is required.
- Transformation output must be traceable to input items.
- Costing method must be configured and consistently applied.

### 10.6 Payment Validation

- Payment method is required.
- Split payment totals must equal payable amount.
- Refunds cannot exceed paid amount.
- GST fields must be valid where tax applies.
- Cash drawer or collection session rules should be applied if introduced.

---

## 11. UI and UX Standards

### 11.1 Experience Goal

A new receptionist should understand the basics within one hour.

### 11.2 Design Principles

- Simple workflows.
- Large buttons.
- Large touch targets.
- Clear language.
- Avoid technical terminology.
- One primary action per screen.
- Universal search.
- Auto-fill wherever possible.
- Confirmation before destructive actions.
- Role-specific dashboards.
- Automatic draft saving for longer forms.
- Minimal typing.
- Consistent colors.
- Easy correction of mistakes.
- Minimal training required.

### 11.3 Role Dashboards

Each role should see its most important actions first.

Examples:

Reception:

- New booking.
- Walk-in check-in.
- Today arrivals.
- Today departures.
- Available rooms.
- Pending payments.

Kitchen:

- New orders.
- Preparing orders.
- Served orders.
- Low stock alerts.

Housekeeping:

- Rooms to clean.
- Maintenance tasks.
- Laundry tasks.

Accountant:

- Daily collection.
- Pending payments.
- GST report.
- Refunds.

### 11.4 Mobile Behavior

Mobile screens must prioritize task completion.

Rules:

- Avoid dense tables on small screens.
- Use searchable lists and focused detail screens.
- Keep primary actions fixed or easy to reach when helpful.
- Ensure forms are usable on touch devices.
- Avoid tiny icon-only actions unless labels or tooltips are available.

### 11.5 Language

Use hotel-friendly labels.

Prefer:

- "Guest"
- "Room"
- "Booking"
- "Check-in"
- "Payment"
- "Kitchen order"
- "Stock"

Avoid exposing internal technical terms such as:

- Entity
- Payload
- Foreign key
- Mutation
- Boolean
- Queue job

---

## 12. Error Handling

### 12.1 User-Facing Errors

Error messages must be calm, clear, and actionable.

Examples:

- "This room is no longer available for the selected dates."
- "Please enter the guest's phone number."
- "Payment total must match the bill amount."
- "Aadhaar verification is temporarily unavailable. You can try again later."

### 12.2 Developer Logs

Logs may include technical details but must not expose sensitive data.

Log:

- Error type.
- Message.
- Stack trace where appropriate.
- User ID.
- Hotel ID.
- Request ID.
- Timestamp.

Do not log:

- Full Aadhaar numbers.
- Plain passwords.
- Payment secrets.
- API keys.

### 12.3 Recoverable Failures

Recoverable failures should allow the user to continue where possible.

Examples:

- Aadhaar API unavailable: allow manual identity capture with retry.
- MSG91 failure: queue retry and show message status.
- Printer failure: allow invoice download.

---

## 13. Integration Standards

### 13.1 Aadhaar Verification

Provider:

- sandbox.co.in

Store:

- Masked Aadhaar.
- Verification response.
- Verification timestamp.
- Provider reference ID when available.
- Status.

Do not store full Aadhaar unless there is a confirmed legal and business reason.

Aadhaar photo may be used instead of capturing a live guest photo.

### 13.2 MSG91

Channels:

- SMS.
- WhatsApp.

Messaging must be queue-based.

Message records should include:

- Channel.
- Recipient.
- Template or message type.
- Related hotel.
- Related reservation or stay when applicable.
- Status.
- Retry count.
- Provider response.

---

## 14. Audit Log Standards

Audit logs are required for important changes.

Must audit:

- User login failures where useful.
- Role and permission changes.
- Hotel settings changes.
- Room status manual changes.
- Reservation creation, update, cancellation.
- Check-in and checkout.
- Room shifts.
- Guest identity changes.
- Payment creation, refund, and adjustment.
- Stock adjustment.
- Purchase approval.
- Goods receipt.
- Employee salary changes.

Audit entries must be immutable for normal users.

---

## 15. Coding Standards

### 15.1 PHP Standards

- Use strict types where practical.
- Follow PSR-12 formatting.
- Use dependency injection.
- Use typed properties and return types where practical.
- Keep controllers thin.
- Keep business logic in services.
- Keep SQL in repositories.
- Use prepared statements through PDO.

### 15.2 Project Structure Direction

Suggested backend structure:

```text
app/
  Controllers/
  Services/
  Repositories/
  Validators/
  Middleware/
  Policies/
  DTO/
  Exceptions/
  Support/
config/
database/
  migrations/
  seeds/
public/
routes/
tests/
```

### 15.3 Service Rules

Services should:

- Orchestrate business workflows.
- Enforce business rules.
- Coordinate repositories.
- Start transactions for multi-step writes.
- Emit audit events.
- Queue integration jobs.

Services should not:

- Read raw request bodies.
- Echo responses.
- Contain SQL strings unless unavoidable.

### 15.4 Repository Rules

Repositories should:

- Own SQL queries.
- Use parameterized PDO statements.
- Apply hotel scope where applicable.
- Return arrays, DTOs, or domain records consistently.

Repositories should not:

- Decide user permissions.
- Perform workflow decisions.
- Send messages or call external APIs.

### 15.5 Transactions

Use database transactions for workflows that update multiple records.

Required examples:

- Check-in.
- Checkout.
- Room shift.
- Payment collection.
- Goods receipt.
- Stock adjustment.
- Inventory transformation.
- Kitchen order completion with stock deduction.

### 15.6 Testing Direction

Initial test priorities:

- Permission checks.
- Hotel scoping.
- Reservation availability.
- Check-in and checkout transitions.
- Room shifting.
- Payment calculations.
- Inventory costing and stock ledger.
- Aadhaar and messaging integration wrappers.

---

## 16. Development Phases

### Phase 0: Foundation Documentation

Deliverables:

- Master Development Blueprint.
- Project constitution for AI agents and developers.
- Initial module documentation outline.
- Initial database planning notes.

### Phase 1: Application Foundation

Deliverables:

- Slim Framework 4 project setup.
- Configuration system.
- Database connection.
- Migration approach.
- Error handling.
- API response format.
- Authentication foundation.
- RBAC foundation.
- Hotel scoping foundation.
- Audit log foundation.

### Phase 2: Hotel, User, and Room Core

Deliverables:

- Hotel groups and hotels.
- Users, roles, permissions.
- Room types.
- Rooms.
- Room status management.
- Basic pricing.

### Phase 3: Guest, Reservation, Check-in, Checkout

Deliverables:

- Guest profiles.
- Identity document capture.
- Reservation creation.
- Availability checking.
- Advance payments.
- Check-in.
- Checkout.
- Room shifting.
- Stay folio.

### Phase 4: Accounts and Messaging

Deliverables:

- Payment methods.
- Split payments.
- GST-ready invoice records.
- Daily collection report.
- MSG91 queue and logs.
- Basic guest notifications.

### Phase 5: Aadhaar Verification

Deliverables:

- sandbox.co.in integration wrapper.
- Aadhaar verification workflow.
- Masked Aadhaar storage.
- Verification logs.
- Failure and retry behavior.

### Phase 6: Housekeeping

Deliverables:

- Cleaning tasks.
- Maintenance requests.
- Laundry records.
- Room readiness flow.
- Housekeeping dashboard.

### Phase 7: Inventory and Purchase

Deliverables:

- Vendors.
- Inventory items.
- Units.
- Purchase orders.
- Goods receipts.
- Batches and expiry.
- Stock ledger.
- FIFO, LIFO, and weighted average support.
- Minimum stock alerts.
- Stock adjustment.
- Stock audit.

### Phase 8: Kitchen and Recipe Costing

Deliverables:

- Kitchen items.
- Recipes.
- Recipe costing.
- Room service orders.
- Meal plans.
- Inventory deduction.
- Kitchen cost reports.

### Phase 9: Employees

Deliverables:

- Employee records.
- Departments.
- Shifts.
- Attendance.
- Documents.
- Salary details.
- Emergency contacts.

### Phase 10: Reporting and Polish

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
- Responsive UX polish.
- PWA polish.

---

## 17. Future Expansion Notes

Supported future expansion:

- VPS migration.
- Dedicated queue workers.
- Stock transfer between hotels.
- More messaging templates.
- More reports.
- Mobile-app wrapper around the PWA.
- Advanced analytics.
- Channel manager integration.
- Payment gateway integration.

Not planned unless explicitly approved:

- Full restaurant POS.
- Table management.
- Food delivery marketplace integration.
- Cloud-provider-specific services.

---

## 18. Finalized Decisions and Remaining External Inputs

Owner-approved implementation decisions:

- Frontend approach: SPA.
- Authentication approach: tokens.
- Migration tool: Phinx.
- PDF/invoice generation approach: generic GST-ready invoice first, redesignable
  later.
- File storage location: public-webroot upload folder with access-control
  guardrails.
- Negative stock policy: disabled by default.
- Purchase approval workflow: configurable per hotel.
- Guest identity retention: indefinite, with owner-only audited deletion.

Remaining external inputs:

- Exact Aadhaar verification API flow and credentials from sandbox.co.in.
- Exact MSG91 template and approval requirements.

---

## 19. Documentation Map

Focused documents live under `docs/` and extend this blueprint without changing
its direction unless an approved decision record says so.

Current baseline structure:

```text
docs/
  README.md
  00-Master Development Blueprint.md
  01-Architecture.md
  02-Database Design.md
  03-API Standards.md
  04-UI UX Standards.md
  05-Permissions Security and Audit.md
  06-Development Roadmap.md
  07-Final Decision Questions.md
  08-Implementation Readiness Checklist.md
  09-Integrations.md
  10-Error Handling and Logging.md
  11-Coding Standards.md
  12-Report Catalogue.md
  13-Data Retention Backup and Restore.md
  14-Documentation Completion Audit.md
  modules/
    Rooms.md
    Guests.md
    Reservations.md
    Check-in Checkout.md
    Accounts.md
    Inventory.md
    Purchases.md
    Kitchen.md
    Housekeeping.md
    Employees.md
    Reports.md
  workflows/
    Reservation to Checkout.md
    Walk-in Check-in.md
    Room Shift.md
    Purchase to Inventory.md
    Kitchen Room Service.md
    Stock Adjustment and Audit.md
    Housekeeping Room Readiness.md
  decisions/
    ADR-0001-Architecture Defaults.md
    ADR-0002-Documentation Baseline Finalization.md
    ADR-0003-Owner Final Decision Answers.md
```

---

## 20. Baseline Acceptance Criteria

The project must not be considered implementation-ready until the following are
clear enough to build:

- Hotel scoping rules.
- Permission model.
- Core room lifecycle.
- Reservation lifecycle.
- Check-in and checkout lifecycle.
- Payment and refund basics.
- Inventory ledger model.
- Audit log rules.
- API response format.
- Error handling style.
- Development phase order.

This blueprint establishes the baseline for those items. Future module
documents should deepen the detail without changing the product direction unless
an approved decision record updates this document.
