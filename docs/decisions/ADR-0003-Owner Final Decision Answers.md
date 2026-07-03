# ADR-0003: Owner Final Decision Answers

Status: Accepted  
Date: 2026-07-03

---

## Context

The owner answered the final decision questions in
`docs/07-Final Decision Questions.md`. These answers replace earlier baseline
recommendations where they differ.

---

## Decision

Use these owner-approved decisions:

- Frontend: SPA.
- Authentication: token-based authentication.
- Migration tool: Phinx.
- File storage: folder under the public webroot so documents can be displayed in
  the SPA.
- Inventory costing: weighted average by default, with FIFO or LIFO configurable
  per item.
- Negative stock: disabled by default.
- Purchase approval: configurable per hotel, default off for very small hotels
  and available for hotels with managers.
- Aadhaar failure handling: allow manual identity capture with audit when the
  provider is unavailable or verification cannot be completed.
- Invoice format: generic GST-ready format first, redesignable later.
- Identity retention: keep guest identity records indefinitely for police
  investigation compliance, with deletion only by owner.

---

## Security Consequences

Token authentication requires:

- Short-lived access tokens.
- Defined refresh behavior.
- Logout invalidation where practical.
- No tokens in URLs.
- HTTPS in production.

Public-webroot document storage requires:

- Non-guessable file paths.
- Blocked directory listing.
- Strict upload validation.
- Hotel-scoped metadata.
- Permission-checked metadata/API access.
- Auditing for owner-only identity document deletion.

---

## Supersedes

This ADR supersedes the earlier hybrid frontend and session-first authentication
defaults in ADR-0001.
