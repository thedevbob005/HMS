# Guests Module

Status: Baseline finalized

---

## Purpose

Maintain guest profiles, identity document records, Aadhaar verification data,
and stay history.

---

## Scope

Included:

- Guest profile.
- Contact details.
- Identity documents.
- Aadhaar verification.
- Guest photo when available.
- Stay history.
- Notes and flags.

---

## Supported Identity Documents

- Aadhaar.
- Passport.
- Driving License.
- Voter ID.

---

## Core Rules

- Guest history must remain available after checkout.
- Aadhaar must be stored masked unless a later legal review approves otherwise.
- Aadhaar verification responses must be protected.
- Guest photo is optional when Aadhaar photo is available.
- Identity document changes must be audited.

---

## Permissions

- `guests.view`
- `guests.create`
- `guests.update`
- `guests.verify_identity`
- `guests.view_sensitive`

---

## Audit Events

- Guest created.
- Guest updated.
- Identity document added.
- Identity document changed.
- Aadhaar verification attempted.
- Sensitive identity data viewed, if implemented.

