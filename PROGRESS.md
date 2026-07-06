# HMS Project Progress Tracker

This document tracks the functional development of the HMS (Hospitality Management System) project.

---

## Roadmap Progress

### [x] Phase 0: Documentation Foundation
- [x] Master Development Blueprint
- [x] Project Constitution (`AGENTS.md`)
- [x] Architecture, Database, API, and UI/UX guidelines

### [x] Phase 1: Application Foundation
- [x] PHP 8.3.32 & WAMP environment setup
- [x] Slim Framework 4 foundation setup
- [x] Dependency injection container (PHP-DI)
- [x] Base schema migrations for tenancy and auth (`Phinx`)
- [x] Core Middleware (JSON parsing)
- [x] API Healthcheck endpoint
- [x] React + TypeScript + Vite SPA frontend skeleton
- [x] Global Git ignores and commit structures
- [x] Standard API response helpers & centralized JSON error handler
- [x] Database-backed Token Authentication middleware
- [x] RBAC policy permission checks
- [x] Hotel scoping middleware check
- [x] Audit logging service implementation
- [x] Protected sample endpoint verifying all layers

### [x] Phase 2: Hotel, User, and Room Core
- [x] Phinx database migrations for room structures
- [x] Hotel groups and hotels CRUD API
- [x] User listings, role allocation, and hotel access API
- [x] Room types CRUD and complex pricing rules calculation API
- [x] Rooms CRUD and status update with logging API
- [x] Vite SPA: premium login and dashboard interface
- [x] Vite SPA: multi-hotel context selector
- [x] Vite SPA: user and access configuration interface
- [x] Vite SPA: room type and rate rule configuration interface
- [x] Vite SPA: rooms status grid and manual transition controls

### [x] Phase 3: Guest, Reservation, Check-in, Checkout
- [x] Guest profile registration
  - [x] Migration for `guests`, `guest_identity_documents`
  - [x] Backend: Guest profile CRUD (Repository, Validator, Service, Controller)
- [x] Identity document uploads (access guarded)
  - [x] Backend: File upload handling with secure paths and permission checks
- [x] Reservation booking calendar
  - [x] Migration for `reservations`, `reservation_rooms`, `reservation_guests`
  - [x] Backend: Room availability checker service
  - [x] Backend: Reservation CRUD (Repository, Validator, Service, Controller)
- [x] Check-in flow and advance payments
  - [x] Migration for `stays`, `stay_rooms`, `stay_guests`, `payments`
  - [x] Backend: Check-in service for bookings and walk-ins
- [x] Checkout calculations, folios, and invoicing
  - [x] Migration for `room_shift_logs`, `folio_items`
  - [x] Backend: Room shift workflow and history tracking
  - [x] Backend: Folio items manager (charges, payments, adjustments)
  - [x] Backend: Checkout logic, settlement validator, and invoice generation
- [x] Vite SPA Frontend Integration for Phase 3
  - [x] Guest profile directory and identity document viewer
  - [x] Room availability grid and reservation manager
  - [x] Active stays dashboard, check-in, room shift trigger
  - [x] Folio details, charges sheet, checkout settlement UI

### [x] Phase 4: Accounts and Messaging
- [x] Database Schema & Migrations: `invoices`, `message_queue`
- [x] Split Payments & Refunds: support arrays of payment/refund splits
- [x] GST Invoice Generation: auto-generate unique sequential GST-ready invoices on checkout/settlement
- [x] Daily Collection Report: generate payments metrics grouped by date and method
- [x] MSG91 Messaging Queue:
  - [x] Notification template bindings (Booking Confirmation, Check-in, Checkout, Cancel)
  - [x] Enqueuing hooks upon reservation/stay updates
  - [x] Background worker script (cron task) for sending/retrying
- [x] Frontend Vite SPA Updates:
  - [x] Split payment intake form
  - [x] Invoice download / layout viewer with GST breakdowns
  - [x] Message queue visibility log
  - [x] Daily collections report metrics widget

### [x] Phase 5: Aadhaar Verification
- [x] Database Schema & Migrations: `identity_verification_logs` table & columns (`verification_status`, `verification_timestamp`, `provider_reference_id`) in `guest_identity_documents`
- [x] Integrations Client: Sandbox.co.in OTP verification API workflow service wrapper with environment configuration support
- [x] Manual Fallback Logic: audited manual identity capture override option with recorded supervisor reason and metadata logging
- [x] Controller & Routing Layer: verify OTP request, verify OTP submit, manual fallback override, and logs retrieval API endpoints
- [x] Frontend React SPA UI updates:
  - [x] OTP input confirmation modal and status badge updates next to guest identity documents
  - [x] Manual fallback reason form panel
  - [x] identity verification audit logs table widget

### [x] Phase 6: Housekeeping
- [x] Database Schema & Migrations: create `housekeeping_tasks` table to track cleaning, laundry, and maintenance logs
- [x] Business Services: implement `HousekeepingService.php` to handle task creation, staff assignment, and status lifecycle updates
- [x] Checkout Integration: hook task creator inside `StayService->performCheckout` and room shifts to auto-trigger room cleaning tasks
- [x] Controller & Routes: endpoints to list tasks, add manual maintenance requests, assign workers, and transition room readiness
- [x] Frontend React UI tab:
  - [x] Room readiness validation dashboard showing dirty/cleaning/maintenance rooms list
  - [x] Staff assignment dropdowns and status toggle controls
  - [x] Custom maintenance log report form panel

### [x] Phase 7: Inventory and Purchase
- [x] Database Schema & Migrations: create tables for `vendors`, `inventory_items`, `purchase_orders`, `purchase_order_items`, `goods_receipts`, `goods_receipt_items`, `stock_batches`, and `stock_ledger`
- [x] Business Services:
  - [x] Implement `InventoryService.php` to handle vendor registration, inventory item creation, and stock adjustments
  - [x] Implement `PurchaseService.php` to handle PO creation, PO approval lifecycle, GRN logging, and stock ledger inserts
  - [x] Weighted Average Costing logic: calculate new average unit costs dynamically when new stock is received or adjusted
- [x] Controller & Routing Layer: endpoints for vendors, inventory items, stock adjustments, purchase orders, approvals, and goods receipts
- [x] Frontend React UI tabs inside main panel:
  - [x] Stock Inventory grid with Valuation calculations and minimum stock alert badges
  - [x] Vendor registration directory
  - [x] Purchase orders planner with item additions and manager approval toggles
  - [x] Goods Receipt logger linked to approved POs with batch/expiry capture fields

### [x] Phase 8: Kitchen and Recipe Costing
- [x] Database Schema & Migrations: create tables for `kitchen_items`, `recipes`, `recipe_items`, `kitchen_orders`, and `kitchen_order_items`
- [x] Business Services:
  - [x] Implement `KitchenService.php` to manage menu items, recipes, costing sheets, and order status transitions
  - [x] Implement automated ingredient inventory deduction through the Stock Ledger when kitchen orders are served
  - [x] Hook folio charge insertion to automatically post room service expenses to the guest stay folio (or check meal plan inclusion exclusions)
- [x] Controller & Routing Layer: endpoints for kitchen items, recipes, order placers, served state updates, and costing sheet metrics
- [x] Frontend React UI tab:
  - [x] Menu items directory and Recipe ingredient builder panels
  - [x] Room service active orders tracker with quick-status (pending, preparing, served, cancelled) selectors
  - [x] Recipe Costing & Margin Sheet showing ingredient costs and gross profit percentage valuations

### [x] Phase 9: Employees
- [x] Database Schema & Migrations: create tables for `departments`, `shifts`, `employees`, `employee_attendance`, and `employee_documents`
- [x] Business Services:
  - [x] Implement `EmployeeService.php` to handle employee profiles, department/shift management, and file storage links
  - [x] Implement `AttendanceService.php` for clock-in/clock-out tracking, bulk daily rosters, and status corrections
  - [x] RBAC Salary protections: restrict visibility of base salary fields based on `employees.view_salary` and `employees.manage_salary` permissions
- [x] Controller & Routing Layer: endpoints for employee CRUD, clock operations, bulk attendance logs, departments, and shifts
- [x] Frontend React UI tab:
  - [x] Employee list grid with add/edit drawers and emergency contact details
  - [x] Attendance roster tracking daily status (Present, Absent, Leave, Late) and inline clock toggles
  - [x] Restricted salary details block showing pay fields to authorized staff only

### [x] Phase 10: Reporting and Release Hardening
- [x] Daily collection, occupancy, GST, and revenue reports
- [x] Progressive Web App (PWA) polish
- [x] System hardening (security headers and locked directory indexes) & backup configuration (managed by hosting provider as requested)
