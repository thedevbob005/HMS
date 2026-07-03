# Permissions, Security, and Audit

Status: Baseline finalized

---

## 1. Default Roles

- Super Admin
- Group Manager
- Hotel Manager
- Reception
- Kitchen Manager
- Store Manager
- Housekeeping
- Accountant

Roles are permission bundles, not the only authorization mechanism.

---

## 2. Permission Naming

Use:

`module.action`

Examples:

- `hotels.manage`
- `users.manage`
- `rooms.view`
- `rooms.manage`
- `reservations.create`
- `reservations.cancel`
- `checkin.perform`
- `checkout.perform`
- `guests.verify_identity`
- `payments.collect`
- `payments.refund`
- `inventory.adjust`
- `purchases.approve`
- `reports.view_financial`

---

## 3. Authorization Rule

An action is allowed only when all are true:

- User is authenticated.
- User is active.
- User has access to the hotel.
- User has the required permission.
- The target record belongs to the hotel or is global by design.

---

## 4. Recommended Role Matrix

Super Admin:

- All permissions.

Group Manager:

- Manage assigned hotels.
- View all operational and financial reports for assigned hotels.
- Manage hotel managers.

Hotel Manager:

- Manage rooms, reservations, stays, housekeeping, and local staff.
- View operational reports.
- Approve sensitive local changes if configured.

Reception:

- Create bookings.
- Check in guests.
- Check out guests.
- Collect payments.
- View guests.
- Request identity verification.

Kitchen Manager:

- Manage kitchen orders.
- Manage recipes.
- View kitchen stock and cost reports.

Store Manager:

- Manage inventory.
- Create purchases.
- Receive goods.
- Perform stock adjustments.

Housekeeping:

- View and update assigned cleaning, laundry, and maintenance tasks.

Accountant:

- Manage payments, invoices, refunds, GST, and financial reports.

---

## 5. Security Requirements

- Hash passwords with PHP password APIs.
- Use prepared statements.
- Use token-based authentication for the SPA.
- Use short-lived access tokens.
- Define refresh token rotation or revocation before production hardening.
- Never expose tokens in URLs.
- Require HTTPS in production.
- Restrict file uploads.
- Never expose stack traces in production responses.
- Never commit secrets.
- Rate-limit login and sensitive verification endpoints where practical.

---

## 6. Sensitive Data

Do not log:

- Full Aadhaar numbers.
- Passwords.
- API keys.
- Payment secrets.

Store:

- Masked Aadhaar.
- Verification status.
- Provider reference ID.
- Verification response summary or protected response JSON.
- Verification timestamp.

---

## 7. Audit Requirements

Audit these events:

- Role and permission changes.
- Hotel setting changes.
- Room manual status changes.
- Booking creation, update, cancellation.
- Check-in.
- Checkout.
- Room shift.
- Guest identity changes.
- Aadhaar verification attempts.
- Payment, refund, and adjustment.
- Purchase approval.
- Goods receipt.
- Stock adjustment.
- Inventory transformation.
- Employee salary changes.

Audit entries should include:

- Entity type.
- Entity ID.
- Hotel ID.
- Action.
- Old value.
- New value.
- User ID.
- IP address.
- Timestamp.
