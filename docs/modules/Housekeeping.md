# Housekeeping Module

Status: Baseline finalized

---

## Purpose

Manage cleaning, laundry, maintenance tasks, and room readiness.

---

## Scope

Included:

- Cleaning tasks.
- Laundry records.
- Maintenance requests.
- Staff assignment.
- Task status.
- Room readiness updates.

---

## Task Statuses

- Open
- Assigned
- In Progress
- Completed
- Cancelled

---

## Core Rules

- Checkout should create or trigger a cleaning task.
- Cleaning completion can return a room to Available.
- Maintenance can move a room to Maintenance status.
- Task assignment and completion should be tracked by user and time.
- Critical maintenance should block room assignment.

---

## Permissions

- `housekeeping.view`
- `housekeeping.assign`
- `housekeeping.update_task`
- `maintenance.manage`

---

## Audit Events

- Cleaning task created.
- Task assigned.
- Task completed.
- Maintenance request created.
- Room marked available after cleaning.

