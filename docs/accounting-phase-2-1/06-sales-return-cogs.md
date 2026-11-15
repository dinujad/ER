# Sales Return COGS Verification

**Source:** `SalesAccountingService::synchronizeSaleReturn` — revenue DR Sales Returns / CR AR; COGS reversal DR Finished Goods / CR COGS at `unitCost(product, variation)` (purchase/recipe cost, not selling price).

## Regression

`AccountingPhase21StabilityTest::test_sale_return_cogs_uses_item_cost_not_selling_price`

- Qty 2 × cost 200 = 400 posted to FG/COGS
- Selling price 250/line (500 return total) must NOT drive COGS amount

## Status

FULLY IMPLEMENTED & TESTED for item-cost path in isolated DB. Full multi-product HTTP E2E browser still optional.
