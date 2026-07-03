# Inventory Module

Status: Baseline finalized

---

## Purpose

Track hotel-specific stock, batches, expiry, costing, adjustments, audits, and
ingredient transformations.

---

## Scope

Included:

- Inventory categories.
- Items and units.
- Batches.
- Expiry dates.
- FIFO, LIFO, and weighted average costing.
- Stock ledger.
- Stock adjustment.
- Stock audit.
- Minimum stock alerts.
- Inventory transformation.

---

## Core Rules

- Inventory belongs to individual hotels.
- Stock movements must be ledger-based.
- Negative stock is disabled by default.
- Batch and expiry are required for configured item categories.
- Adjustments require a reason.
- Transformations must trace input items to output items.
- Future stock transfer between hotels must be supported by the model.

---

## Costing Recommendation

Default to weighted average costing.

Allow FIFO or LIFO at item level where the hotel needs stricter batch costing.

---

## Permissions

- `inventory.view`
- `inventory.manage`
- `inventory.adjust`
- `inventory.audit`
- `inventory.transform`

---

## Audit Events

- Item created.
- Item updated.
- Stock adjusted.
- Stock audit completed.
- Transformation performed.
- Minimum stock setting changed.

