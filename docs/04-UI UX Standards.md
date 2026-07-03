# UI and UX Standards

Status: Baseline finalized  
Audience: Hotel staff with mixed technical comfort

---

## 1. Experience Goal

A new receptionist should understand the basics within one hour.

The interface must feel simple even when the backend workflow is complex.

---

## 2. Core Principles

- Use clear hotel language.
- Prefer one primary action per screen.
- Use large buttons and touch targets.
- Minimize typing.
- Auto-fill where possible.
- Save drafts for long workflows.
- Confirm destructive actions.
- Make corrections easy.
- Show role-specific dashboards.
- Keep important actions visible.

---

## 3. Language Rules

Use:

- Guest
- Booking
- Room
- Check-in
- Checkout
- Payment
- Kitchen order
- Stock
- Purchase

Avoid in the UI:

- Entity
- Payload
- Mutation
- Boolean
- Foreign key
- Queue
- Endpoint

---

## 4. Navigation

Recommended primary navigation:

- Dashboard
- Bookings
- Rooms
- Guests
- Kitchen
- Stock
- Purchases
- Housekeeping
- Accounts
- Reports
- Settings

Navigation must adapt to permissions. Users should not see modules they cannot
access.

---

## 5. Role Dashboards

Reception dashboard:

- Walk-in check-in.
- New booking.
- Today arrivals.
- Today departures.
- Available rooms.
- Pending payments.

Kitchen dashboard:

- New orders.
- Preparing orders.
- Served orders.
- Low stock alerts.

Housekeeping dashboard:

- Rooms to clean.
- Maintenance tasks.
- Laundry tasks.

Accountant dashboard:

- Daily collection.
- Pending payments.
- Refunds.
- GST report.

---

## 6. Forms

Form rules:

- Group fields by real workflow.
- Mark required fields clearly.
- Validate inline where helpful.
- Keep error messages human.
- Avoid asking for the same data twice.
- Use defaults based on hotel settings.
- Keep submit buttons easy to reach.

Destructive actions:

- Require confirmation.
- Explain the consequence.
- Require a reason for audited changes where useful.

---

## 7. Mobile

Mobile screens should use:

- Searchable lists.
- Focused detail pages.
- Sticky primary actions where helpful.
- Large touch targets.
- Minimal table layouts.

Dense financial and inventory reports may be optimized for desktop but must
remain readable on mobile.

---

## 8. Status Colors

Use consistent status colors:

- Available: green.
- Reserved: blue.
- Occupied: red or strong accent.
- Cleaning: amber.
- Maintenance: orange.
- Blocked: gray.
- Paid: green.
- Pending: amber.
- Failed: red.

Do not rely on color alone. Include labels.

---

## 9. Empty and Error States

Empty states should tell the user what to do next.

Examples:

- "No arrivals today."
- "No rooms are available for these dates."
- "No kitchen orders are waiting."

Error states should avoid blame and explain recovery.

