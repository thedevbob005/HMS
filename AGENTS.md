# HMS Project Constitution

This file defines how AI agents and developers must work in this repository.
It is intentionally strict because HMS manages identity, payments, inventory,
and hotel operations.

The authoritative product document is:

- `docs/00-Master Development Blueprint.md`

When a task is ambiguous, follow the blueprint first, then the focused docs in
`docs/`, then the existing code patterns.

---

## 1. Non-Negotiable Product Rules

- HMS is for small and medium hotel operators.
- The UI must be simple enough for non-technical hotel staff.
- There is no restaurant POS module.
- Kitchen features are for in-house guest service only.
- Every hotel-operational record must be scoped by `hotel_id` unless the
  blueprint explicitly marks it global.
- Every protected backend action must enforce both authentication and
  authorization.
- Financial, identity, inventory, room movement, and permission changes must be
  auditable.
- Full Aadhaar numbers must not be logged or casually stored.
- Shared hosting is the initial deployment target.
- Avoid cloud-specific assumptions.

---

## 2. Architecture Rules

Backend flow:

`Route -> Controller -> Validator -> Service -> Repository -> Database`

Use:

- PHP 8.3+
- Slim Framework 4
- MySQL 8+
- PDO
- Service + Repository architecture
- API-first JSON backend

Do not introduce:

- ORM without explicit approval.
- Cloud-only services.
- Restaurant POS scope.
- Business logic inside controllers.
- Raw SQL outside repositories unless documented as an exception.

---

## 3. Hotel Scope Rule

Any query touching hotel-owned data must prove hotel access.

Required checks:

- The authenticated user can access the requested hotel.
- The record belongs to that hotel.
- The user has the required permission for the action.

Never trust a `hotel_id` from the client without checking it against the current
user's allowed hotels.

---

## 4. RBAC Rule

Use named permissions in the form:

`module.action`

Examples:

- `reservations.create`
- `checkin.perform`
- `payments.collect`
- `inventory.adjust`
- `reports.view_financial`

Roles are permission bundles. Do not hard-code behavior only by role name unless
the role itself is the business rule.

---

## 5. Audit Rule

Audit important changes with:

- Entity type.
- Entity ID.
- Hotel ID when applicable.
- Action.
- Old value.
- New value.
- User ID.
- Timestamp.
- IP address when available.

Audit entries should be append-only for normal application users.

---

## 6. Data and Privacy Rule

Sensitive data must be minimized.

Do not log:

- Full Aadhaar numbers.
- Plain passwords.
- API keys.
- Payment secrets.

Prefer:

- Masked Aadhaar.
- Provider reference IDs.
- Verification status.
- Verification timestamp.
- Restricted document access, even when files live under the public webroot.

---

## 7. UX Rule

The user experience should be calm, clear, and task-first.

Use hotel language:

- Guest
- Booking
- Room
- Check-in
- Payment
- Kitchen order
- Stock

Avoid technical language in the UI:

- Payload
- Entity
- Mutation
- Foreign key
- Queue job

One primary action per screen is preferred for operational workflows.

---

## 8. Documentation Rule

When a decision changes product behavior or architecture:

1. Update the relevant focused document.
2. Update the master blueprint if the change affects project direction.
3. Add an ADR under `docs/decisions/` for major or irreversible decisions.

Do not let implementation drift away from documentation.

---

## 9. Implementation Quality Rule

Use transactions for multi-step workflows, including:

- Check-in.
- Checkout.
- Room shift.
- Payment collection.
- Goods receipt.
- Stock adjustment.
- Inventory transformation.
- Kitchen order completion with stock deduction.

Tests should prioritize:

- Hotel scoping.
- Permission checks.
- Room availability.
- Status transitions.
- Payment calculations.
- Stock ledger correctness.
- Integration wrapper behavior.

---

## 10. Recommended Defaults

Until changed by an approved decision record:

- Frontend approach: SPA with API-first backend.
- Authentication: Token-based authentication.
- Queue: Database-backed queue processed by cron on shared hosting.
- Migrations: Phinx or a similar PHP migration tool.
- File storage: Public-webroot upload folder for SPA display, with
  non-guessable paths, blocked directory listing, upload validation, and
  permission-checked metadata/API access.
- Inventory costing default: Weighted average, with item-level FIFO/LIFO support
  where configured.
- Negative stock: Disabled by default.
