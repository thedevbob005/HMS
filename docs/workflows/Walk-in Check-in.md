# Workflow: Walk-in Check-in

Status: Baseline finalized

---

## Purpose

Support guests arriving without an advance reservation.

---

## Main Flow

1. Reception selects Walk-in Check-in.
2. System shows available rooms.
3. Reception selects room and rate.
4. Guest details are captured.
5. Identity document is captured.
6. Aadhaar verification is performed when applicable.
7. Advance or full payment is collected if required.
8. Stay is created.
9. Room status changes to Occupied.
10. Stay folio is opened.

---

## Required Validations

- Room must be available.
- Check-in and expected checkout dates must be valid.
- Required guest fields must be completed.
- Required payment policy must be satisfied.

---

## Recommended UX

The walk-in screen should be fast and focused:

- Available rooms first.
- Guest essentials second.
- Payment third.
- Confirm check-in as the single primary action.

---

## Audit Events

- Walk-in stay created.
- Identity captured.
- Payment collected.
- Room marked occupied.

