# Workflow: Kitchen Room Service

Status: Baseline finalized

---

## Purpose

Handle guest room service orders and recipe-based inventory deduction.

---

## Main Flow

1. Staff creates kitchen order for an active stay or room.
2. Kitchen accepts the order.
3. Kitchen prepares the order.
4. Order is marked served.
5. Recipe ingredients are deducted from inventory.
6. Chargeable items are added to stay folio.
7. Included meal plan items are marked as included, not charged again.
8. Kitchen cost report is updated.

---

## Required Validations

- Stay must be active.
- Ordered items must be active.
- Meal plan rules must be applied.
- Ingredient stock must be available unless negative stock is explicitly allowed.

---

## Audit Events

- Kitchen order created.
- Kitchen order status changed.
- Inventory deducted.
- Folio charge posted.

---

## Transaction Requirement

Completing a kitchen order should update order status, stock ledger, and folio
charges in one transaction where possible.

