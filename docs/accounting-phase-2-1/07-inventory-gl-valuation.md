# Inventory ↔ GL Valuation

**Service:** `InventoryGlReconciliationService` (read-only diagnostic)

Compares product/variation qty × unit cost vs sum of GL inventory control closings (1300/1310/1320/1330).

## Test

`test_inventory_gl_reconciliation_report_structure` — 5 × 100 stock + GL inventory 500 → difference 0.00

## Limitations

- Uses available qty columns (`variations.qty_available` or `products.qty_available`)
- Does not invent a new costing method
- Production should also review `stock_ledgers` movements (noted when present)
- Does not auto-correct stock or GL
