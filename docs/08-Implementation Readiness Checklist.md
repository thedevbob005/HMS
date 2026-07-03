# Implementation Readiness Checklist

Status: Documentation baseline finalized

Use this checklist before scaffolding the application.

---

## Documentation Baseline

- [x] Master Development Blueprint exists.
- [x] Project constitution exists.
- [x] Architecture standards exist.
- [x] Database design direction exists.
- [x] API standards exist.
- [x] UI and UX standards exist.
- [x] Permissions, security, and audit standards exist.
- [x] Development roadmap exists.
- [x] Integration standards exist.
- [x] Error handling and logging standards exist.
- [x] Coding standards exist.
- [x] Report catalogue exists.
- [x] Data retention, backup, and restore guidance exists.
- [x] Core module specs exist.
- [x] Core workflow specs exist.
- [x] Architecture decision baseline exists.
- [x] Final decision questions are answered and recorded.

---

## Owner-Approved Decisions for Scaffolding

- [x] SPA frontend approach.
- [x] Token-based authentication.
- [x] Phinx for migrations.
- [x] Public-webroot upload folder with access-control guardrails.
- [x] Weighted average as default inventory costing.
- [x] Negative stock disabled by default.
- [x] Purchase approval configurable per hotel.
- [x] Aadhaar verification can fall back to audited manual capture.

---

## External Integration Inputs Needed

- [x] Generic GST-ready invoice format approved for first version.
- [x] Identity records retained indefinitely with owner-only audited deletion.
- [ ] Confirm final sandbox.co.in Aadhaar flow and credentials.
- [ ] Confirm final MSG91 approved templates.

---

## Implementation Start Gate

Implementation scaffolding may begin using the owner-approved decisions in
`07-Final Decision Questions.md` and the accepted ADRs.

Provider-specific features must wait for the external inputs listed above.

Recommended first implementation task:

Scaffold the Slim Framework 4 application foundation with configuration,
database connection, standardized API responses, authentication shell, RBAC
tables, hotel scoping middleware or policy helpers, and audit log foundation.
