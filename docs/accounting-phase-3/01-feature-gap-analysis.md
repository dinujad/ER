# Phase 3 — Feature Gap Analysis (Post-Implementation)

**Branch:** `feature/accounting-phase-3`  
**HEAD commit:** `dcd01c530df09a3ba5a4162f444e13b5cf169391` (working tree has uncommitted Phase 3 files)  
**Push status:** NOT PUSHED  
**Currency:** LKR only  
**Method:** graphify + code inspection + `php artisan test --filter=Accounting`  
**Regression (post-implementation):** **64 passed (197 assertions)** — see `14-test-results.md`

---

## Status legend

| Status | Meaning |
|--------|---------|
| FULLY IMPLEMENTED & TESTED | Core paths covered by automated tests |
| IMPLEMENTED BUT PARTIALLY VERIFIED | Built in code/UI; limited or no E2E verification |
| NOT IMPLEMENTED | Not built |
| BLOCKED | Cannot proceed without dependency |

---

## Matrix (verified against current code)

| Area | Status | Evidence | Notes |
|------|--------|----------|-------|
| Fixed Asset Register | **IMPLEMENTED BUT PARTIALLY VERIFIED** | `fa_*` tables, `FixedAssetService`, `FixedAssetController`, Workspace views | PDF/Excel export, attachments, supplier/purchase ref UI thin |
| Asset categories + GL maps | **FULLY IMPLEMENTED & TESTED** | Migration seeds 7 categories + GL 1400–6500 | Category CRUD UI present |
| Purchase → capital asset | **IMPLEMENTED BUT PARTIALLY VERIFIED** | `purchase_reference`, `supplier_id` columns on `fa_assets` | No PurchaseObserver / purchase-line capitalization workflow |
| Depreciation engine | **FULLY IMPLEMENTED & TESTED** | `DepreciationEngine`, schedules, runs, reverse | Tax ≠ book not modeled |
| Asset disposal | **FULLY IMPLEMENTED & TESTED** | `AssetDisposalService`, disposal UI | Types: sale/scrap/write-off via `disposal_type` string |
| Finance loans | **FULLY IMPLEMENTED & TESTED** | `LoanAccountingService`, `acct_loan_*` | Interest accrual method exists; UI partial for accrual |
| Cost centers | **IMPLEMENTED BUT PARTIALLY VERIFIED** | `CostCenterService`, `acct_cost_centers` | No dedicated CC P&L report pages; allocation is tracking-only (no GL split) |
| Job / project costing | **IMPLEMENTED BUT PARTIALLY VERIFIED** | `JobCostAccountingService`, `job_id` on `journal_entries` | Summarizes existing `ManufacturingJob::costBreakdown()`; limited ledger linkage tests |
| Manufacturing WIP/FG GL | **IMPLEMENTED BUT PARTIALLY VERIFIED** | Phase 1 `InventoryAccountingService` + summary UI | WIP/FG balances shown on manufacturing summary page |
| Manufacturing overhead | **IMPLEMENTED BUT PARTIALLY VERIFIED** | `ManufacturingOverheadService::simpleAverageForPeriod` in summary controller | Configurable bases (DL hours, etc.) **NOT IMPLEMENTED** |
| Advanced budget versions | **FULLY IMPLEMENTED & TESTED** | `AdvancedBudgetService`, `acct_budget_versions` | Monthly lines; vs-actual from `journal_entries` |
| Budget vs Actual (ledger) | **FULLY IMPLEMENTED & TESTED** | `budgetVsActual()` in tests | Quarterly/yearly aggregate reports not separate pages |
| Accounting Workspace Phase 3 | **FULLY IMPLEMENTED & TESTED** | Sidebar + `AccountingPhase3WorkspaceUiTest` | No placeholder routes |
| `postOnce` / period locks | **FULLY IMPLEMENTED & TESTED** | Phase 1–2.1 regression in suite | Phase 3 postings use same engine |
| Phase 1–2.1 core | **FULLY IMPLEMENTED & TESTED** | 64-test regression filter | Preserved |
| MySQL staging | **NOT VERIFIED** | Tests run on SQLite in feature tests | See `15-remaining-issues.md` |
| Phase 4 | **NOT IMPLEMENTED** | — | Not started |

---

## Reuse decisions (actual)

| Reused | New in Phase 3 |
|--------|----------------|
| `AccountingService` (`postOnce`, `postMultiLine`, GL constants) | `fa_*`, `acct_loan_*`, `acct_cost_*`, `acct_budget_version_*` |
| `AccountingAuditService` | Seven service classes under `app/Services/Accounting/` |
| Workspace layout + permissions | 9 controllers, 27 Blade views under `phase3/` |
| `ManufacturingJob` cost breakdown | `JobCostAccountingService` |
| Existing `budgets` table (extended columns) | Versioned budget tables |

---

## Test coverage gaps (honest)

| Area | Covered by `AccountingPhase3CoreTest` | Not covered |
|------|--------------------------------------|-------------|
| Fixed assets | Capitalize idempotency | Transfer, approval workflow, GL register reconcile |
| Depreciation | SL, declining, run/reverse/idempotent | Closed-period rejection E2E |
| Loans | Disburse, payment, early settle, equal principal | Interest accrual UI, custom schedules |
| Cost centers | Allocation balance validation | CC P&L, GL dimension on all ERP posts |
| Budget | Version workflow + vs-actual | Revise/copy attachments |
| Jobs | `job_id` ledger debits | Full manufacturing job E2E |
| Manufacturing | — | Variance, overhead bases, inventory GL reconcile |

---

## MySQL/MariaDB

**NOT VERIFIED** on this branch unless an isolated MySQL environment is confirmed. SQLite feature-test success is documented only.