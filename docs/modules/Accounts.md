# Accounts Module

Status: Baseline finalized

---

## Purpose

Manage guest payments, refunds, invoices, GST-ready records, and daily
collections.

---

## Payment Methods

- Cash.
- Card.
- UPI.
- Bank.
- Wallet.
- Split payments.

---

## Core Rules

- Money must use decimal storage.
- Payments belong to a hotel.
- Payments should be allocated to reservation, stay, invoice, or folio records.
- Split payment totals must equal the payable amount.
- Refunds cannot exceed collected amount.
- Financial corrections should be adjustments or reversals, not silent edits.
- GST details must be captured where tax applies.

---

## Permissions

- `payments.view`
- `payments.collect`
- `payments.refund`
- `payments.adjust`
- `invoices.view`
- `invoices.generate`
- `reports.view_financial`

---

## Audit Events

- Payment collected.
- Payment edited before settlement.
- Refund created.
- Adjustment created.
- Invoice generated.
- Tax setting changed.

