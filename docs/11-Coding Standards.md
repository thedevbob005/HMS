# Coding Standards

Status: Baseline finalized

---

## 1. PHP Standards

- Use PHP 8.3 or newer.
- Follow PSR-12 formatting.
- Use strict types where practical.
- Use typed properties and return types where practical.
- Use Composer autoloading.
- Keep classes focused.
- Prefer dependency injection over service location.

---

## 2. Backend Layering

Follow:

`Route -> Controller -> Validator -> Service -> Repository -> Database`

Controllers must:

- Parse request data.
- Call validators.
- Call services.
- Return responses.

Controllers must not:

- Contain SQL.
- Contain complex business workflows.
- Call external providers directly.

Services must:

- Own business rules.
- Start transactions.
- Coordinate repositories.
- Enforce status transitions.
- Create audit logs.
- Queue integration jobs.

Repositories must:

- Own SQL.
- Use PDO prepared statements.
- Apply hotel scope.
- Return predictable data structures.

---

## 3. SQL Rules

- Use parameterized queries.
- Never concatenate untrusted input into SQL.
- Keep query methods explicit and readable.
- Include hotel scope in hotel-owned data queries.
- Prefer transaction boundaries in services.

---

## 4. Validation Rules

Validate:

- Required fields.
- Data types.
- Date ordering.
- Money values.
- IDs and hotel ownership.
- Status transitions.
- File type and size.

Validation messages should be user-friendly.

---

## 5. Tests

Priority test areas:

- Hotel scoping.
- Permission checks.
- Availability calculation.
- Check-in and checkout transitions.
- Room shifts.
- Payment totals and refunds.
- Stock ledger correctness.
- Integration wrapper behavior.

Test names should describe the business behavior being protected.

---

## 6. Documentation While Coding

When implementation changes behavior:

- Update the related module or workflow doc.
- Update the blueprint for direction-level changes.
- Add an ADR for major architecture decisions.

Documentation drift is treated as a bug.

