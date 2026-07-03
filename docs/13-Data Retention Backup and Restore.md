# Data Retention, Backup, and Restore

Status: Owner-approved baseline

---

## 1. Purpose

Define baseline expectations for retaining data, protecting documents, and
recovering from data loss.

Hosting-provider backup automation is outside HMS scope, but HMS must still be
designed so backup and restore are practical.

---

## 2. Data Retention Principles

- Keep operational records needed for hotel history and reporting.
- Minimize sensitive identity data.
- Do not keep full Aadhaar casually.
- Do not hard-delete audit logs, payments, invoices, or stock ledger records.
- Retain guest identity records indefinitely for police investigation
  compliance, with owner-only audited deletion.

---

## 3. Recommended Retention Defaults

Owner-approved retention baseline:

- Audit logs: retain long term.
- Payments and invoices: retain according to tax/accounting requirements.
- Stay history: retain long term for operational history.
- Guest identity documents: retain indefinitely, with deletion only by the owner
  and with an audit log.
- Employee records: retain according to HR/legal requirements.
- Integration logs: retain enough for troubleshooting, then archive or purge.

---

## 4. Backup Expectations

Hosting provider handles daily backup automation.

HMS should still document:

- Database name and backup method.
- Upload/document storage path.
- Config files required for restore.
- Cron jobs required after restore.
- How to verify restored login and hotel data.

---

## 5. Restore Checklist

Minimum restore verification:

- Application loads.
- Admin login works.
- Hotels are visible.
- Rooms are visible.
- Recent reservations are visible.
- Payments and invoices are visible.
- Uploaded documents are accessible through protected routes.
- Cron queue processing works.
- Logs are writable.

---

## 6. File Storage

Guest and employee documents will be stored under a public webroot folder so
the SPA can display them.

Required safeguards:

- Use non-guessable paths.
- Block direct listing.
- Validate file type and size on upload.
- Keep metadata in the database with hotel scope and permission checks.
- Prefer permission-checked file URLs or signed/temporary access patterns where
  practical.
- Never expose identity documents through predictable URLs.
