# Final Decision Questions

Status: Owner answered and accepted  
Purpose: Capture final choices, recommendations, and production-sensitive
confirmations.

These questions were answered by the owner and are now project decisions unless
changed by a later ADR.

---

## 1. Frontend Approach

Question:

Should HMS start as a server-rendered app, SPA, or hybrid app?

Owner decision:

Build the staff application as an SPA.

Implementation note:

The backend remains API-first using Slim Framework 4. The SPA consumes JSON APIs
and should be deployable on shared hosting.

Final decision:

SPA.

---

## 2. Authentication

Question:

Should staff login use sessions, tokens, or both?

Owner decision:

Use token-based authentication.

Implementation note:

Tokens must be stored and handled securely by the SPA. The implementation should
define access token lifetime, refresh behavior, logout invalidation, and token
revocation before production hardening.

Final decision:

Tokens.

---

## 3. Migration Tool

Question:

Which database migration tool should be used?

Recommendation:

Use Phinx.

Reason:

It is PHP-friendly, works well with MySQL, and does not require a full framework
ORM.

Final decision:

Phinx.

---

## 4. File Storage

Question:

Where should guest and employee documents be stored?

Owner decision:

Store uploaded guest and employee documents in a folder under the public web
root because the SPA needs to display them.

Implementation note:

Files must still use non-guessable paths, strict upload validation, and
permission-checked metadata/API access. Direct directory listing must be blocked.
Sensitive document URLs should not be predictable.

Final decision:

Public-webroot folder with access-control guardrails.

---

## 5. Inventory Costing

Question:

Which costing method should be the default?

Recommendation:

Weighted average by default, with FIFO or LIFO configurable per item.

Reason:

Weighted average is easier for small hotels while still supporting stricter
batch methods when needed.

Final decision:

Weighted average.

---

## 6. Negative Stock

Question:

Can inventory go negative?

Recommendation:

Disable negative stock by default.

Reason:

It prevents silent stock mistakes. Authorized adjustments can correct real-world
differences.

Final decision:

Negative stock disabled.

---

## 7. Purchase Approval

Question:

Should purchase orders require approval?

Recommendation:

Make approval configurable per hotel. Default it off for very small hotels and
available for hotels with managers.

Reason:

Small properties need speed; larger operations need control.

Final decision:

Configurable approval.

---

## 8. Aadhaar Failure Handling

Question:

Can check-in continue if Aadhaar verification is unavailable?

Recommendation:

Yes. Allow manual identity capture with audit when the provider is unavailable
or verification cannot be completed.

Reason:

Hotel operations cannot stop because a third-party API is down.

Final decision:

Allow audited manual fallback.

---

## 9. GST Invoice Format

Question:

Who will approve the final invoice format?

Owner decision:

Use a generic invoice format initially.

Implementation note:

The invoice design may be redesigned later as an update. The first version
should be clean, GST-ready, and configurable enough for normal hotel billing.

Final decision:

Generic invoice format first; redesign later if needed.

---

## 10. Data Retention

Question:

How long should identity records be retained?

Owner decision:

Keep guest identity records indefinitely for police investigation compliance,
with deletion available only to the owner.

Implementation note:

Because identity data is sensitive, owner-only deletion must be audited. The
system should support restricted viewing, immutable audit logs, and careful file
access controls.

Final decision:

Indefinite retention with owner-only audited deletion.
