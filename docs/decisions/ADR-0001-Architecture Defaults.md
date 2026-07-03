# ADR-0001: Architecture Defaults

Status: Superseded in part by ADR-0003  
Date: 2026-07-03

---

## Context

HMS targets small and medium hotels and must run first on shared hosting. The
system also needs reliable hotel scoping, RBAC, audit logs, integrations,
inventory, payments, and future VPS migration.

---

## Decision

Use these baseline defaults:

- Backend: PHP 8.3+ with Slim Framework 4.
- Database: MySQL 8+.
- Database access: PDO without ORM.
- Architecture: Service + Repository.
- API style: API-first JSON.
- Frontend: Hybrid staff web app. Superseded by ADR-0003.
- Authentication: Secure server-side sessions first. Superseded by ADR-0003.
- Queue: Database-backed queue processed by cron.
- Migration tool: Phinx.
- Inventory costing default: Weighted average.
- Negative stock: Disabled by default.
- Deployment: Shared-hosting compatible, VPS-ready.

---

## Consequences

Benefits:

- Simple shared-hosting deployment.
- Clear backend boundaries.
- Lower operational complexity.
- Future migration path to VPS.
- Easier enforcement of hotel scope and audit logging.

Tradeoffs:

- Database-backed queues are less powerful than dedicated queue systems.
- The original session-first and hybrid frontend defaults were replaced by
  owner-approved SPA and token decisions in ADR-0003.
- No ORM means repositories must be disciplined and well-tested.
