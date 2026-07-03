# Rooms Module

Status: Baseline finalized

---

## Purpose

Manage room types, rooms, pricing, room status, and room movement for each
hotel.

---

## Scope

Included:

- Room types.
- Rooms.
- Room status lifecycle.
- Base, seasonal, weekend, and holiday pricing.
- Extra bed configuration.
- Room shift support.
- Room status logs.

Excluded:

- Restaurant table management.
- External channel inventory sync until a future integration phase.

---

## Statuses

- Available
- Reserved
- Occupied
- Cleaning
- Maintenance
- Blocked

Status changes must be logged.

---

## Core Rules

- Room numbers are unique per hotel.
- A room belongs to exactly one hotel.
- A room can have one active status at a time.
- Reserved and occupied dates must be protected by availability checks.
- Manual status changes require permission and may require a reason.
- Checkout normally moves a room to Cleaning.
- Housekeeping completion normally moves a room to Available.

---

## Pricing Rules

Pricing priority, highest first:

1. Holiday price.
2. Seasonal price.
3. Weekend price.
4. Base price.

Extra bed charges are configured per room type unless overridden by hotel
settings.

---

## Permissions

- `rooms.view`
- `rooms.manage`
- `rooms.change_status`
- `rooms.manage_rates`

---

## Audit Events

- Room created.
- Room updated.
- Room status changed.
- Room blocked or unblocked.
- Pricing changed.

