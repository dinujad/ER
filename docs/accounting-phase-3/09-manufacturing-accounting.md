# Phase 3 — Manufacturing Accounting

**Status:** IMPLEMENTED BUT PARTIALLY VERIFIED  
**Controller:** `modules/Accounting/Http/Controllers/ManufacturingAccountingController.php`  
**View:** `modules/Accounting/Resources/views/phase3/manufacturing/summary.blade.php`

## What Phase 3 added

Single **Production Costing** summary page in the Accounting Workspace:

- GL net balances for WIP, Finished Goods, Raw Materials via `AccountingService` GL codes
- Job count from `manufacturing_jobs`
- Overhead snippet from `ManufacturingOverheadService::simpleAverageForPeriod($from, $to)` when `expenses` table exists

## What already existed (Phase 1)

`InventoryAccountingService` posts RM → WIP → FG → COGS on manufacturing/inventory events. Phase 3 does **not** change that engine.

## Overhead

Brief asked for configurable bases (DL hours, machine hours, quantity, DL cost).

**Actual:** `simpleAverageForPeriod` — total expenses / finished pieces in period. **No user-configurable allocation bases.**

## Reports named in brief

| Report | Status |
|--------|--------|
| Manufacturing cost sheet | **Partial** (job cost + summary only) |
| WIP report | **Partial** (GL balance on summary) |
| FG cost / material consumption | **NOT IMPLEMENTED** as Phase 3 pages |
| Manufacturing variance | **NOT IMPLEMENTED** |
| Product profitability | **NOT IMPLEMENTED** |

## Tests

No dedicated manufacturing Phase 3 feature test. Phase 1 inventory accounting tests still pass in broader accounting filter.

## Classification

**IMPLEMENTED BUT PARTIALLY VERIFIED** — visibility layer only; costing integrity relies on pre-existing inventory GL posting.