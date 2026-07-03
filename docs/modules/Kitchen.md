# Kitchen Module

Status: Baseline finalized

---

## Purpose

Support hotel kitchen operations for guests through room service, meal plans,
recipes, and inventory deduction.

---

## Scope

Included:

- Room service orders.
- Meal plans.
- Kitchen order status.
- Recipes.
- Recipe costing.
- Automatic inventory deduction.
- Kitchen cost reporting.

Excluded:

- Restaurant POS.
- Table service.
- External customer billing.

---

## Meal Plans

- Room Only.
- CP.
- MAP.
- AP.

---

## Core Rules

- Kitchen orders must link to a stay or room.
- Served chargeable items are added to the stay folio.
- Included meal plan items should not double-charge the guest.
- Recipe ingredients are deducted when the order is completed or served,
  depending on final hotel configuration.
- Kitchen inventory deduction must use the stock ledger.

---

## Permissions

- `kitchen.view`
- `kitchen.manage_orders`
- `kitchen.manage_items`
- `kitchen.manage_recipes`
- `kitchen.view_costs`

---

## Audit Events

- Kitchen item created or updated.
- Recipe changed.
- Order created.
- Order cancelled.
- Order marked served.
- Inventory deducted.

