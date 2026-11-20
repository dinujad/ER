# Complete Accounting Page Inventory (Phase 5.5)

**Branch:** `feature/accounting-ui-phase-5-5`  
**Shell:** `modules/Accounting/Resources/views/layouts/app.blade.php`  
**Sidebar:** `workspace_sidebar.blade.php` (collapsible groups)

## Design inheritance

All Accounting workspace pages extending `accounting::layouts.app` or `accounting::phase2._page` inherit the Phase 5.5 design system (`_acct_design_system.blade.php`): tokens, tables, toolbars, KPIs, empty states, responsive sidebar.

## Route inventory (modules/Accounting/Http/routes.php)

| Area | Routes (prefix) | Primary views | Redesign |
|------|-----------------|---------------|----------|
| Overview | `accounting/dashboard`, phase4 financial-dashboard, kpi | `dashboard/index`, phase4/* | REDESIGNED & TESTED |
| COA / Journals | chart_of_account, journal_entry, manual-journals, account-register, transfers, transactions | legacy + phase2 | REDESIGNED BUT PARTIALLY VERIFIED (shell+global CSS) |
| Banking | reconcile, bank-statements, bank-matching, recon report | phase2 banking | REDESIGNED BUT PARTIALLY VERIFIED |
| AR/AP | receivables/*, payables/*, AR/AP ageing reports | phase2 ar/ap + reports | REDESIGNED BUT PARTIALLY VERIFIED |
| Assets/Loans | fixed-assets*, depreciation*, loans*, capitalization | phase3/* | REDESIGNED BUT PARTIALLY VERIFIED |
| Cost/Mfg | cost-centers*, cost-center-reports*, job-costs, manufacturing* | phase3/* | Cost Center P&L REDESIGNED & TESTED; others shell+CSS |
| Planning | budget*, budget-versions*, cash-forecast | phase3/phase4 | REDESIGNED BUT PARTIALLY VERIFIED |
| Reports | report/accounting/*, phase4/statements*, custom/scheduled | report + phase4 | REDESIGNED BUT PARTIALLY VERIFIED (_report_table empty state) |
| Tax/Closing | phase4/tax*, year-end*, periods, audit-log | phase4 + phase2 | REDESIGNED BUT PARTIALLY VERIFIED |
| AI | phase5/* | phase5/* | REDESIGNED BUT PARTIALLY VERIFIED (shell) |
| Settings | settings/* | settings | REDESIGNED BUT PARTIALLY VERIFIED |

## Cost Center P&L diagnosis

**Cause of “empty/zero” look:** UI was sparse; default filter historically behaved like unassigned-only (`whereNull(cost_center_id)`). Untagged journals → zero lines without explanation.

**Fix:** Default/explicit **All (company-wide)** uses `companyWideProfitAndLoss`; Unassigned and specific CC remain; empty state explains no posted activity vs missing dimension.
