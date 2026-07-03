# Report Catalogue

Status: Baseline finalized

---

## 1. Purpose

Define the baseline reports HMS must provide.

All reports must respect hotel access and permissions.

---

## 2. Occupancy Report

Shows:

- Total rooms.
- Occupied rooms.
- Available rooms.
- Reserved rooms.
- Cleaning rooms.
- Maintenance or blocked rooms.
- Occupancy percentage.

Filters:

- Hotel.
- Date or date range.
- Room type.

---

## 3. Revenue Report

Shows:

- Room revenue.
- Kitchen revenue.
- Extra bed revenue.
- Other charges.
- Discounts.
- Taxes.
- Refunds.
- Net revenue.

Permissions:

- `reports.view_financial`

---

## 4. Kitchen Cost Report

Shows:

- Kitchen order revenue.
- Ingredient cost.
- Recipe cost.
- Wastage where recorded.
- Gross margin estimate.

Permissions:

- `kitchen.view_costs`
- `reports.view_financial` when margin or revenue is included.

---

## 5. Inventory Report

Shows:

- Current stock.
- Stock value.
- Minimum stock alerts.
- Expiring stock.
- Batch-level stock.
- Recent movements.

Permissions:

- `reports.view_inventory`

---

## 6. Check-in Report

Shows:

- Guests checked in.
- Rooms occupied.
- Check-in time.
- Reception user.
- Booking source.
- Identity verification status.

---

## 7. Checkout Report

Shows:

- Guests checked out.
- Checkout time.
- Settlement status.
- Final amount.
- Room moved to cleaning.
- Late checkout or overstay charges.

---

## 8. Purchase Report

Shows:

- Purchase orders.
- Goods receipts.
- Vendor.
- Item totals.
- Tax.
- Pending receipts.
- Purchase returns.

---

## 9. Vendor Report

Shows:

- Vendor purchases.
- Pending payments if vendor accounting is implemented.
- Return history.
- Item history.

---

## 10. Daily Collection Report

Shows:

- Cash.
- Card.
- UPI.
- Bank.
- Wallet.
- Split payments.
- Refunds.
- Net collection.
- Collected by user.

Permissions:

- `reports.view_financial`

---

## 11. GST Report

Shows:

- Taxable amount.
- GST amount.
- Invoice numbers.
- Refunds or credit notes when implemented.
- Date range totals.

The first GST invoice/report format should be generic and GST-ready. It may be
redesigned later as an update.

---

## 12. Export Rules

- Exports require `reports.export`.
- Financial exports require financial report permission.
- Sensitive exports should be logged.
- Large exports may be queued.
