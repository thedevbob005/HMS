# Workflow: Purchase to Inventory

Status: Baseline finalized

---

## Purpose

Convert purchased goods into traceable hotel stock.

---

## Main Flow

1. Store Manager creates purchase order.
2. Purchase is approved if approval is enabled.
3. Goods are received.
4. Received quantity, batch, expiry, rate, and tax are captured.
5. Goods receipt is saved.
6. Stock batches are created or updated.
7. Stock ledger records quantity in.
8. Vendor and purchase reports are updated.
9. Audit log is recorded.

---

## Required Validations

- Vendor is active.
- Items belong to the hotel.
- Received quantity is positive.
- Batch and expiry are captured where required.
- User has purchase receiving permission.

---

## Audit Events

- Purchase order created.
- Purchase order approved.
- Goods receipt created.
- Stock ledger movement created.

---

## Failure Handling

If stock update fails, the goods receipt must not be partially completed. Use a
database transaction.

