# Integrations

Status: Baseline finalized

---

## 1. Purpose

Define how HMS communicates with external providers while protecting hotel
operations from provider failures.

Initial providers:

- sandbox.co.in for Aadhaar verification.
- MSG91 for SMS and WhatsApp.

---

## 2. Integration Principles

- Provider calls must be wrapped behind application services.
- Controllers must not call providers directly.
- Integration credentials must never be committed.
- Provider responses must be logged carefully without exposing secrets.
- Failed provider calls should be visible to authorized staff.
- Recoverable provider failures should not stop hotel operations where a manual
  fallback is acceptable.

---

## 3. Aadhaar Verification

Provider:

- sandbox.co.in

Use cases:

- Verify guest Aadhaar during reservation or check-in.
- Store masked Aadhaar.
- Store verification status.
- Store verification timestamp.
- Store provider reference ID where available.
- Store protected response summary or JSON.

Do not:

- Log full Aadhaar.
- Display full Aadhaar broadly.
- Require Aadhaar verification to be successful when the provider is down if
  audited manual identity capture is allowed.

Recommended statuses:

- `not_started`
- `pending`
- `verified`
- `failed`
- `manual_fallback`

Required audit events:

- Verification attempted.
- Verification succeeded.
- Verification failed.
- Manual fallback used.

---

## 4. MSG91 Messaging

Channels:

- SMS.
- WhatsApp.

Messaging must be queue-based.

Recommended message types:

- Booking confirmation.
- Check-in confirmation.
- Checkout/payment receipt.
- Payment reminder.
- Cancellation confirmation.

Recommended statuses:

- `queued`
- `processing`
- `sent`
- `failed`
- `cancelled`

Required fields:

- Hotel ID.
- Channel.
- Recipient.
- Message type.
- Template ID where applicable.
- Related reservation or stay.
- Status.
- Retry count.
- Provider response summary.

---

## 5. Retry Rules

Recommended retry policy:

- Retry transient failures up to 3 times.
- Use delayed retry intervals.
- Stop immediately for invalid recipient or invalid template errors.
- Keep final failure visible in integration logs.

---

## 6. Configuration

Required environment/config values:

- Aadhaar provider base URL.
- Aadhaar provider credentials.
- MSG91 base URL.
- MSG91 auth key or credentials.
- MSG91 approved template identifiers.
- Integration timeout values.

Production credentials must be separate from sandbox credentials.

