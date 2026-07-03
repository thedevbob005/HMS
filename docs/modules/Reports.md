# Reports Module

Status: Baseline finalized

---

## Purpose

Provide operational, financial, inventory, kitchen, purchase, vendor, and GST
visibility by hotel and date range.

---

## Required Reports

- Occupancy.
- Revenue.
- Kitchen cost.
- Inventory.
- Check-ins.
- Check-outs.
- Purchases.
- Vendor reports.
- Daily collection.
- GST.

---

## Core Rules

- Reports must respect hotel access.
- Financial reports require financial report permissions.
- Reports should support date filters.
- Export should be permission-controlled.
- Long-running exports may be queued.
- Report values must be traceable to source records.

---

## Permissions

- `reports.view_operational`
- `reports.view_financial`
- `reports.view_inventory`
- `reports.export`

---

## Audit Events

Report viewing is not always audited, but exports of sensitive reports should be
logged.

