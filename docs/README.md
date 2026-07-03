# HMS Documentation Index

![HMS icon](icon.png)

This directory contains the authoritative development documentation for HMS.

Start here:

1. `00-Master Development Blueprint.md`
2. `01-Architecture.md`
3. `02-Database Design.md`
4. `03-API Standards.md`
5. `04-UI UX Standards.md`
6. `05-Permissions Security and Audit.md`
7. `06-Development Roadmap.md`
8. `07-Final Decision Questions.md`
9. `08-Implementation Readiness Checklist.md`
10. `09-Integrations.md`
11. `10-Error Handling and Logging.md`
12. `11-Coding Standards.md`
13. `12-Report Catalogue.md`
14. `13-Data Retention Backup and Restore.md`
15. `14-Documentation Completion Audit.md`

Module specifications live in `modules/`.

Workflow specifications live in `workflows/`.

Architecture decision records live in `decisions/`.

Current ADRs:

- `decisions/ADR-0001-Architecture Defaults.md`
- `decisions/ADR-0002-Documentation Baseline Finalization.md`
- `decisions/ADR-0003-Owner Final Decision Answers.md`

---

## Standards Docs

- `01-Architecture.md`
- `02-Database Design.md`
- `03-API Standards.md`
- `04-UI UX Standards.md`
- `05-Permissions Security and Audit.md`
- `09-Integrations.md`
- `10-Error Handling and Logging.md`
- `11-Coding Standards.md`
- `12-Report Catalogue.md`
- `13-Data Retention Backup and Restore.md`

---

## Module Specs

- `modules/Rooms.md`
- `modules/Guests.md`
- `modules/Reservations.md`
- `modules/Check-in Checkout.md`
- `modules/Accounts.md`
- `modules/Inventory.md`
- `modules/Purchases.md`
- `modules/Kitchen.md`
- `modules/Housekeeping.md`
- `modules/Employees.md`
- `modules/Reports.md`

---

## Workflow Specs

- `workflows/Reservation to Checkout.md`
- `workflows/Walk-in Check-in.md`
- `workflows/Room Shift.md`
- `workflows/Purchase to Inventory.md`
- `workflows/Kitchen Room Service.md`
- `workflows/Stock Adjustment and Audit.md`
- `workflows/Housekeeping Room Readiness.md`

---

## Documentation Status

Current status: baseline finalized for implementation planning.

These documents are detailed enough to guide scaffolding, database design, API
design, and module-by-module implementation. Any future product change should be
reflected here before or alongside code changes.

---

## Reading Order for Developers

New contributors should read:

1. `../AGENTS.md`
2. `00-Master Development Blueprint.md`
3. `01-Architecture.md`
4. `02-Database Design.md`
5. The module document related to their task.
6. The workflow document related to their task.
