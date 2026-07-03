# Architecture

Status: Baseline finalized  
Applies to: Backend, frontend, integrations, deployment

---

## 1. Architecture Summary

HMS uses a modular API-first architecture built on Slim Framework 4, PHP 8.3+,
MySQL 8+, and PDO.

The backend must be structured around services and repositories:

`Route -> Controller -> Validator -> Service -> Repository -> Database`

This keeps HTTP concerns, validation, business rules, and SQL separate.

---

## 2. Recommended Project Structure

```text
app/
  Controllers/
  DTO/
  Exceptions/
  Middleware/
  Policies/
  Repositories/
  Services/
  Support/
  Validators/
config/
database/
  migrations/
  seeds/
docs/
public/
  app/
  uploads/
routes/
storage/
  logs/
tests/
```

---

## 3. Layer Responsibilities

Routes:

- Define URL, HTTP method, middleware, and controller action.
- Must not contain business logic.

Controllers:

- Read request input.
- Call validators.
- Call services.
- Return standardized JSON responses.
- Must remain thin.

Validators:

- Validate input shape and business-safe field rules.
- Return clear field-level errors.

Services:

- Own business workflows.
- Start and commit transactions.
- Coordinate repositories.
- Enforce state transitions.
- Queue integrations.
- Write audit entries.

Repositories:

- Own SQL.
- Use PDO prepared statements.
- Apply hotel scoping where applicable.
- Return predictable arrays or DTOs.

Policies:

- Enforce authorization.
- Combine permission checks with hotel access checks.

Middleware:

- Authentication.
- Request ID.
- JSON parsing.
- Error handling.
- Rate limiting where required.

---

## 4. Module Boundaries

Each module owns its business rules and persistence access.

Core modules:

- Hotels
- Users and RBAC
- Rooms
- Guests
- Reservations
- Stays
- Accounts
- Kitchen
- Inventory
- Purchases
- Housekeeping
- Employees
- Reports
- Integrations
- Audit logs

Modules may call other modules through services, not directly through another
module's repository.

Example:

- Checkout service may call payment service and room service.
- Checkout service should not directly update payment SQL tables.

---

## 5. Frontend and Authentication Decision

Owner-approved frontend:

- Build the staff interface as an SPA.
- Keep the backend API-first.
- The SPA consumes JSON endpoints exposed by Slim Framework 4.
- The SPA must be deployable on shared hosting.

Owner-approved authentication:

- Use token-based authentication.
- Define short-lived access tokens.
- Define refresh token behavior before production hardening.
- Invalidate tokens on logout where practical.
- Store login events.
- Require HTTPS in production.

Security notes:

- Token storage must be deliberately chosen during implementation.
- Avoid exposing tokens through URLs.
- Do not store long-lived unrestricted tokens in browser storage.
- Consider refresh-token rotation or server-side token revocation storage.

---

## 6. Queue Recommendation

Recommended baseline:

- Use a database-backed queue table.
- Process with cron on shared hosting.
- Add retry count, status, scheduled time, and last error fields.

Use queue jobs for:

- SMS.
- WhatsApp.
- Aadhaar verification retries when applicable.
- Report exports if they become slow.

Future VPS deployments can replace this with a long-running worker.

---

## 7. Deployment Constraints

Initial deployment target is shared hosting.

Architecture must avoid requiring:

- Root access.
- Supervisor.
- Redis.
- Cloud queues.
- Object storage.
- Container orchestration.

Allowed if optional:

- CLI commands.
- Cron jobs.
- Composer.
- Writable storage directory.

---

## 8. Configuration

Configuration should come from environment variables or hosting-safe config
files that are not committed with secrets.

Required config areas:

- Database.
- App URL.
- Token authentication settings.
- Aadhaar provider credentials.
- MSG91 credentials.
- File storage path.
- Logging level.

Never commit production secrets.

---

## 9. Error Handling

All unhandled exceptions should be converted to the standard API error envelope.

Production responses must be safe:

```json
{
  "success": false,
  "message": "Something went wrong. Please try again."
}
```

Developer logs may contain stack traces but must not contain sensitive identity,
password, payment, or API secret data.
