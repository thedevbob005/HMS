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

### [ ] Phase 6: Housekeeping
- [ ] Checkout cleaning task auto-generation
- [ ] Room service readiness validation dashboard
- [ ] Maintenance request logging

### [ ] Phase 7: Inventory and Purchase
- [ ] Vendors registration
- [ ] Inventory items tracking with Weighted Average costing
- [ ] Stock batches and purchase order approvals

### [ ] Phase 8: Kitchen and Recipe Costing
- [ ] Room service kitchen orders
- [ ] Recipe costing sheets and auto inventory deduction

### [ ] Phase 9: Employees
- [ ] Staff profile records, department routing, and shift schedules
- [ ] Attendance tracking

### [ ] Phase 10: Reporting and Release Hardening
- [ ] Daily collection, occupancy, GST, and revenue reports
- [ ] Progressive Web App (PWA) polish
- [ ] System hardening and backup scripting
