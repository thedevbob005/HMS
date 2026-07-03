# Check-in and Checkout Module

Status: Baseline finalized

---

## Purpose

Manage the active stay lifecycle from guest arrival to final settlement and room
release.

---

## Scope

Included:

- Walk-in check-in.
- Reservation check-in.
- Early check-in.
- Late checkout.
- Overstay calculation.
- Room shifting.
- Stay folio.
- Checkout settlement.
- Room status updates.

---

## Core Rules

- Check-in creates or activates a stay.
- A checked-in room becomes Occupied.
- Checkout requires an active stay.
- Checkout calculates pending room, kitchen, extra bed, late checkout, and
  overstay charges.
- Final settlement must follow payment rules.
- Checkout moves room to Cleaning unless an authorized user selects a valid
  alternative.
- Room movement must be traceable by guest, room, user, date, and reason.

---

## Permissions

- `checkin.perform`
- `checkout.perform`
- `stays.view`
- `stays.update`
- `stays.room_shift`
- `stays.override_charges`

---

## Audit Events

- Check-in performed.
- Checkout performed.
- Room shifted.
- Stay dates changed.
- Charge overridden.
- Room occupancy manually corrected.

