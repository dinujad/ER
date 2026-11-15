# Phase 3 — Accounting Workspace UI

**Status:** FULLY IMPLEMENTED & TESTED (route smoke + sidebar)  
**Layout:** `modules/Accounting/Resources/views/layouts/workspace_sidebar.blade.php`  
**Test:** `tests/Feature/AccountingPhase3WorkspaceUiTest.php`

## Sidebar sections added

| Section | Links |
|---------|-------|
| Fixed Assets | Asset Register, Categories, Depreciation Runs, Disposal |
| Loans | Loan Register |
| Cost Accounting | Cost Centers, Allocations, Job Costing |
| Manufacturing Accounting | Production Costing |
| Budgeting | Budget Versions, Budget vs Actual |

Phase 1–2 sections unchanged (Dashboard, CoA, journals, banking, AR/AP, reports, periods, audit).

## Controllers (9)

`FixedAssetController`, `FixedAssetCategoryController`, `DepreciationController`, `AssetDisposalController`, `LoanController`, `CostCenterController`, `JobCostController`, `ManufacturingAccountingController`, `AdvancedBudgetController`

Shared: `Concerns/RequiresAccountingPermission`

## Views (27 files)

Under `modules/Accounting/Resources/views/phase3/`:

- `fixed_assets/` (index, create, edit, show, _fields)
- `categories/index`
- `depreciation/` (runs, preview, asset_schedule)
- `disposals/create`
- `loans/` (index, create, show, payment, statement)
- `cost_centers/` (index, create, edit)
- `allocations/` (index, create)
- `job_costs/` (index, show)
- `manufacturing/summary`
- `budgets/` (index, create, show, vs_actual)

Pattern: extends Phase 2 workspace shell (`phase2/_page` style).

## Permissions

- `accounting.view` — all GET routes in UI test
- `accounting.manage` — POST actions (capitalize, dep run, loan disburse, etc.)

## UI test coverage

`phase3ReadRoutes()` asserts HTTP 200 or 302 for 12 GET paths.

Dashboard test asserts strings: Asset Register, Loan Register, Budget Versions, Production Costing.

## Not in sidebar (brief mentioned, not built)

- Asset Reports (PDF/Excel)
- Interest Reports
- Department / branch profitability pages
- WIP valuation standalone report

No placeholder menu items for missing features.