# Workflow: Stock Adjustment and Audit

Status: Baseline finalized

---

## Purpose

Correct stock differences while preserving a clear audit trail.

---

## Stock Adjustment Flow

1. Store Manager selects item and batch.
2. Current system stock is shown.
3. User enters corrected quantity or adjustment quantity.
4. User selects reason.
5. User submits adjustment.
6. Stock ledger records the movement.
7. Audit log is recorded.

---

## Stock Audit Flow

1. User starts stock audit for selected categories or all stock.
2. System captures expected quantities.
3. Physical counts are entered.
4. Differences are reviewed.
5. Authorized user approves adjustments.
6. Stock ledger records approved changes.
7. Audit report is saved.

---

## Required Validations

- Reason is required.
- User must have inventory adjustment or audit permission.
- Batch-level adjustments must reference the batch.
- Negative result is blocked unless allowed by hotel settings.

---

## Audit Events

- Stock adjustment submitted.
- Stock audit started.
- Stock audit approved.
- Stock ledger movement created.

