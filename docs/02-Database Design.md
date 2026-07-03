# Database Design

Status: Baseline finalized  
Database: MySQL 8+

---

## 1. Design Principles

- Every operational record belongs to a hotel unless explicitly global.
- Use append-friendly records for financial and audit-sensitive data.
- Use a stock ledger for inventory.
- Use explicit status fields for lifecycle-driven records.
- Prefer clear relational tables over JSON blobs.
- Use JSON only for provider responses, metadata, or flexible settings.

---

## 2. Naming Rules

- Tables: plural snake_case.
- Columns: snake_case.
- Primary key: `id`.
- Foreign keys: `{singular_table_name}_id`.
- Timestamps: `created_at`, `updated_at`.
- Soft delete column: `deleted_at`.
- Money fields: decimal, never float.
- Boolean fields: `is_active`, `is_verified`, `is_cancelled`.

---

## 3. Common Columns

Most hotel-scoped tables should include:

- `id`
- `hotel_id`
- `created_by`
- `updated_by`
- `created_at`
- `updated_at`
- `deleted_at`

Lifecycle tables should also include:

- `status`
- `notes`

---

## 4. Hotel Scoping

Hotel-scoped tables must include `hotel_id` and must be queried with hotel
access checks.

Examples:

- `rooms`
- `reservations`
- `stays`
- `payments`
- `inventory_items`
- `stock_ledger`
- `purchase_orders`
- `housekeeping_tasks`

Global or owner-level tables:

- `hotel_groups`
- `permissions`
- System-level settings

---

## 5. Suggested Table Groups

Identity and access:

- `users`
- `roles`
- `permissions`
- `role_permissions`
- `user_roles`
- `user_hotel_access`
- `login_logs`

Hotels:

- `hotel_groups`
- `hotels`
- `hotel_settings`

Rooms:

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

Accounts:

- `payments`
- `payment_allocations`
- `refunds`
- `tax_rates`
- `invoices`
- `invoice_items`

Inventory and purchase:

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

Kitchen:

- `meal_plans`
- `kitchen_items`
- `recipes`
- `recipe_items`
- `kitchen_orders`
- `kitchen_order_items`

Operations:

- `housekeeping_tasks`
- `laundry_records`
- `maintenance_requests`
- `employees`
- `employee_documents`
- `employee_attendance`
- `departments`
- `shifts`

System:

- `message_queue`
- `message_logs`
- `integration_logs`
- `audit_logs`

---

## 6. Inventory Ledger

Inventory must use `stock_ledger` as the source of movement truth.

Movement types:

- `purchase_receipt`
- `kitchen_consumption`
- `stock_adjustment`
- `transformation_input`
- `transformation_output`
- `expiry_writeoff`
- `transfer_out`
- `transfer_in`

Recommended fields:

- `hotel_id`
- `inventory_item_id`
- `stock_batch_id`
- `movement_type`
- `quantity_in`
- `quantity_out`
- `unit_cost`
- `total_cost`
- `reference_type`
- `reference_id`
- `created_by`
- `created_at`

Recommended default costing method: weighted average.

FIFO and LIFO should be item-level configuration, not the global default.

---

## 7. Financial Data

Use decimal fields for money:

- `DECIMAL(12,2)` for normal amounts.
- Larger precision only where justified.

Corrections should create adjustment, refund, or reversal records. Do not
silently rewrite payment history after settlement.

---

## 8. Indexing Direction

Required index patterns:

- `hotel_id`
- `hotel_id, status`
- `hotel_id, created_at`
- `hotel_id, date`
- Foreign keys used in joins.
- Search fields such as room number, guest phone, reservation code.

Unique examples:

- Room number unique per hotel.
- Permission name globally unique.
- Reservation code globally unique or hotel-prefixed unique.

---

## 9. Soft Deletes

Use soft delete for business records that may be referenced later.

Do not hard-delete:

- Guests with stay history.
- Payments.
- Invoices.
- Stock ledger rows.
- Audit logs.
- Identity verification logs.

