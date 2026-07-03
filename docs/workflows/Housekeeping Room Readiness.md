# Workflow: Housekeeping Room Readiness

Status: Baseline finalized

---

## Purpose

Move a room from checkout to cleaned and available status.

---

## Main Flow

1. Guest checks out.
2. Room status changes to Cleaning.
3. Cleaning task is created.
4. Housekeeping staff is assigned or self-selects the task.
5. Task is marked In Progress.
6. Task is marked Completed.
7. Room is marked Available by rule or manager confirmation.

---

## Required Validations

- Room must belong to the hotel.
- Task completion requires housekeeping permission.
- Maintenance issue should prevent automatic Available status if reported.

---

## Audit Events

- Cleaning task created.
- Task assigned.
- Task completed.
- Room marked available.

