# Workflow: Reservation to Checkout

Status: Baseline finalized

---

## Purpose

Define the complete guest lifecycle from booking to final room release.

---

## Main Flow

1. Reception creates a reservation.
2. System checks room availability.
3. Guest details are captured.
4. Identity document is captured.
5. Aadhaar verification is performed when applicable.
6. Advance payment is recorded if collected.
7. Reservation is confirmed.
8. Guest arrives.
9. Reception performs check-in.
10. Room status changes to Occupied.
11. Stay folio is opened.
12. Room service and extra charges are added during the stay.
13. Room shift is recorded if needed.
14. Checkout is initiated.
15. Late checkout or overstay charges are calculated.
16. Final settlement is collected.
17. Invoice or receipt is generated.
18. Room status changes to Cleaning.
19. Housekeeping completes cleaning.
20. Room status changes to Available.

---

## Required Validations

- Room is available for the selected dates.
- Guest identity requirements are met.
- Reservation is not cancelled.
- Payment totals are valid.
- Checkout cannot happen without an active stay.

---

## Required Audit Events

- Reservation confirmed.
- Check-in performed.
- Room shift, if any.
- Payment collected.
- Checkout performed.
- Room status changed.

---

## Failure Handling

- If room availability changes before confirmation, show a conflict and ask the
  user to select another room.
- If Aadhaar verification fails or provider is unavailable, allow manual capture
  with permission and audit.
- If final payment is incomplete, keep checkout pending unless an authorized
  override is used.

