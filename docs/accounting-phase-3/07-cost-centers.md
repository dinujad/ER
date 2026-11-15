# Phase 3 — Cost Centers

**Status:** IMPLEMENTED BUT PARTIALLY VERIFIED  
**Service:** `app/Services/Accounting/CostCenterService.php`  
**UI:** `modules/Accounting/Resources/views/phase3/cost_centers/*`, `allocations/*`

## Master data (`acct_cost_centers`)

- Code, name, description, optional `parent_id`, `department_id`, `manager_user_id`
- Status `active` / `inactive`
- `assertNoCircularParent()` prevents cycles (**logic implemented**, not feature-tested)

## Allocations (`acct_cost_allocations` + lines)

`createAllocation()`:

- Validates line amounts sum **exactly** to `source_amount` (2dp)
- Creates header with status `posted` and detail lines with optional `percentage`
- **Does not post GL journals** — analytical split for management reporting only (principle: no duplicate expense recognition)

**Tested:** mismatch throws; balanced allocation succeeds (`AccountingPhase3CoreTest`).

## Journal dimension

`journal_entries.cost_center_id` added by migration. Populated on:

- Fixed asset capitalization, depreciation, disposal (via posting meta)

Most Phase 1–2 ERP auto-postings **do not** stamp `cost_center_id` yet.

## Reports (brief vs actual)

| Report | Status |
|--------|--------|
| Cost Center P&L | **NOT IMPLEMENTED** (no report route) |
| Department expense | **NOT IMPLEMENTED** |
| Branch profitability | **NOT IMPLEMENTED** |
| CC budget vs actual | **Partial** — `AdvancedBudgetService::budgetVsActual` filters by CC when version/header has `cost_center_id` |
| Shared expense allocation report | **Partial** — allocation list UI only |

## UI

- CRUD cost centers
- List/create allocations with multi-line split form

## HRM integration

`department_id` is a nullable FK-style integer; no automatic sync from HR departments.