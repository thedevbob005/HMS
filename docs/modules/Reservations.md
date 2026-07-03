# Reservations Module

Status: Baseline finalized

---

## Purpose

Manage bookings, availability, advance payments, cancellation, and transition to
check-in.

---

## Booking Sources

- Walk-in.
- Phone.
- Other, with source details.

---

## Statuses

- Draft
- Confirmed
- Checked In
- Completed
- Cancelled
- No Show

---

## Core Rules

- Check-in date must be before checkout date.
- At least one room is required to confirm a reservation.
- Room availability must be checked before confirmation.
- A reservation may include multiple rooms.
- A room may include multiple guests.
- Partial payments are allowed.
- Cancellation follows hotel policy.
- Cancelled reservations cannot be checked in.

---

## Permissions

- `reservations.view`
- `reservations.create`
- `reservations.update`
- `reservations.cancel`
- `reservations.collect_advance`

---

## Audit Events

- Reservation created.
- Reservation confirmed.
- Reservation updated.
- Reservation cancelled.
- Reservation marked no-show.
- Advance payment recorded.

