# Error Handling and Logging

Status: Baseline finalized

---

## 1. Purpose

Define how HMS reports errors to users and records diagnostic information for
developers and operators.

---

## 2. User-Facing Error Principles

Errors shown to hotel staff must be:

- Clear.
- Calm.
- Actionable.
- Non-technical.
- Safe.

Examples:

- "This room is no longer available for the selected dates."
- "Please enter the guest's phone number."
- "Payment total must match the bill amount."
- "Aadhaar verification is temporarily unavailable. You can try again later."

Avoid:

- Stack traces.
- SQL messages.
- Provider raw errors.
- Internal class names.

---

## 3. API Error Envelope

Validation error:

```json
{
  "success": false,
  "message": "Please correct the highlighted fields.",
  "errors": {
    "field_name": ["Readable message."]
  }
}
```

System error:

```json
{
  "success": false,
  "message": "Something went wrong. Please try again."
}
```

---

## 4. Log Content

Logs may include:

- Request ID.
- User ID.
- Hotel ID.
- Route.
- Error class.
- Error message.
- Stack trace in non-production or protected logs.
- Timestamp.

Logs must not include:

- Full Aadhaar numbers.
- Plain passwords.
- API keys.
- Payment secrets.
- Full identity document images.

---

## 5. Request IDs

Every request should receive a request ID.

The request ID should be:

- Returned in error responses where useful.
- Written to logs.
- Used to trace provider calls and queue jobs.

---

## 6. Recoverable Failures

Examples:

- Aadhaar provider unavailable: allow audited manual fallback.
- MSG91 send failure: queue retry and show message status.
- Printer unavailable: allow invoice download.
- Report export slow: queue export where needed.

Recoverable failures should not corrupt business state.

---

## 7. Transaction Failure Rule

When a multi-step workflow fails, partial writes must be rolled back unless the
workflow is explicitly designed as eventually consistent.

Transaction-required workflows:

- Check-in.
- Checkout.
- Room shift.
- Payment collection.
- Goods receipt.
- Stock adjustment.
- Inventory transformation.
- Kitchen order completion.

