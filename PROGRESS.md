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

### [/] Phase 2: Hotel, User, and Room Core
- [ ] Phinx database migrations for room structures
- [ ] Hotel groups and hotels CRUD API
- [ ] User listings, role allocation, and hotel access API
- [ ] Room types CRUD and complex pricing rules calculation API
- [ ] Rooms CRUD and status update with logging API
- [ ] Vite SPA: premium login and dashboard interface
- [ ] Vite SPA: multi-hotel context selector
- [ ] Vite SPA: user and access configuration interface
- [ ] Vite SPA: room type and rate rule configuration interface
- [ ] Vite SPA: rooms status grid and manual transition controls

### [ ] Phase 3: Guest, Reservation, Check-in, Checkout
- [ ] Guest profile registration
- [ ] Identity document uploads (access guarded)
- [ ] Reservation booking calendar
- [ ] Check-in flow and advance payments
- [ ] Checkout calculations, folios, and invoicing

### [ ] Phase 4: Accounts and Messaging
- [ ] Payment methods (Cash, Card, UPI, etc.)
- [ ] Split payment allocations
- [ ] GST invoice PDF generation
- [ ] MSG91 SMS/WhatsApp notifications integration

### [ ] Phase 5: Aadhaar Verification
- [ ] Sandbox.co.in verification workflow
- [ ] Masked Aadhaar storage logic
- [ ] Audited manual identity capture fallback

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
