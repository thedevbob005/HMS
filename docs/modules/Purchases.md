# Purchases Module

Status: Baseline finalized

---

## Purpose

Manage vendors, purchase orders, goods receipts, purchase returns, and purchase
reporting.

---

## Scope

Included:

- Vendors.
- Purchase orders.
- Purchase approvals when enabled.
- Goods receipt notes.
- Batch and expiry capture.
- Tax details.
- Purchase returns.
- Vendor reports.

---

## Core Rules

- Goods receipt increases stock through the inventory ledger.
- Purchase approval is configurable.
- Received quantity may differ from ordered quantity and must be recorded.
- Batch, expiry, rate, and tax are captured during receipt where applicable.
- Vendor balances should be traceable through purchase and payment records.

---

## Permissions

- `vendors.view`
- `vendors.manage`
- `purchases.view`
- `purchases.create`
- `purchases.approve`
- `purchases.receive`
- `purchases.return`

---

## Audit Events

- Vendor created or updated.
- Purchase order created.
- Purchase order approved.
- Goods received.
- Purchase return created.
- Purchase cancelled.

