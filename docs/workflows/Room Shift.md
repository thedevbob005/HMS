# Workflow: Room Shift

Status: Baseline finalized

---

## Purpose

Move an active guest stay from one room to another while preserving movement
history.

---

## Main Flow

1. Reception opens the active stay.
2. User selects Room Shift.
3. System shows available target rooms.
4. User selects target room.
5. User enters shift reason.
6. System records old room, new room, user, timestamp, and reason.
7. Old room changes to Cleaning, Maintenance, or another valid selected status.
8. New room changes to Occupied.
9. Stay folio remains attached to the stay.

---

## Required Validations

- Stay must be active.
- Target room must be available.
- User must have `stays.room_shift`.
- Shift reason is required.
- Source and target room must belong to the same hotel.

---

## Audit Events

- Room shift created.
- Old room status changed.
- New room status changed.

---

## Reporting Requirement

The system must be able to report the full room movement history for a stay.

