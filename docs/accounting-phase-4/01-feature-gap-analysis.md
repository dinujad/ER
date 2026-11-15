# Phase 4 — Feature Gap Analysis

**Branch:** `feature/accounting-phase-4`  
**Baseline:** `d8c9d6ef` (Phase 3 completion on `main`)  
**Currency:** LKR only  
**Baseline tests:** `php artisan test --filter=Accounting` → **72 passed (224 assertions)**

## Matrix

| Area | Status | Action |
|------|--------|--------|
| Accounting Workspace metrics/dashboard | FULLY IMPLEMENTED | Extend KPIs + charts |
| Module ReportController P&L/BS/TB/Ledger | FULLY IMPLEMENTED | Enhance comparative + equity/CF classification |
| Cash flow (classified indirect) | PARTIALLY IMPLEMENTED | Enhance classification |
| Statement of changes in equity | MISSING | Build |
| Custom report builder / saved reports | MISSING | Build |
| Cash flow forecasting (rule-based) | MISSING | Build |
| Financial KPI analytics section | PARTIALLY (dashboard cards) | Build dedicated analytics |
| TaxRate entity | PARTIALLY IMPLEMENTED | Configurable tax + reports (no hardcode rates) |
| Tax GL posting on all sales | MISSING / partial | Config + reports; avoid duplicate postings |
| Year-end closing / retained earnings | MISSING | Build on AccountingPeriod |
| Period locks | FULLY IMPLEMENTED | Preserve |
| Scheduled financial reports | MISSING | Build (reuse scheduler pattern) |
| Budget vs Actual | FULLY IMPLEMENTED | Enhance analytics dashboard |
| Recurring journals | FULLY IMPLEMENTED | Preserve (not report schedules) |
| PDF/Excel patterns | FULLY IMPLEMENTED | Reuse |
| Charts in Accounting workspace | MISSING | Add Chart.js like ERP |
| Legacy AccountingController P&L routes | BROKEN | Repair aliases or redirects |
| MySQL staging | NOT VERIFIED | Document / pretend |

## Reuse

- `AccountingWorkspaceMetrics`, `AccountingService`, `AccountingPeriodService`
- `modules/Accounting/Http/Controllers/ReportController`
- `AdvancedBudgetService`, `CostCenterReportService`
- `AccountingExport`, DomPDF, scheduler (`accounting:process-recurring` pattern)
- Phase 3 FA/loan/CC GL accounts

## Do not

- Separate reporting ledger
- Phase 5 AI/OCR
- Push without approval
- Hardcode Sri Lankan statutory tax rates without config + effective dates
