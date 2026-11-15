# Phase 4 — Architecture

**Branch:** `feature/accounting-phase-4` · **Currency:** LKR only

## Layering

| Layer | Responsibility |
|-------|----------------|
| `FinancialStatementService` | P&amp;L, balance sheet, indirect cash flow, equity changes, trial balance from `journal_entries` + `chart_of_accounts` |
| `FinancialDashboardService` | Wraps `AccountingWorkspaceMetrics` (backward compatible) + extended KPIs + Chart.js series |
| `CustomReportBuilderService` | Saved reports allowlist; generates via statement / CC / budget services — no raw SQL |
| `CashFlowForecastService` | Read-only scenario runs; never posts journals |
| `FinancialKpiAnalyticsService` | Margins, liquidity ratios, DSO/DPO (null on div/0) |
| `TaxManagementService` | Configurable tax codes + period summary + `postOnce` adjustments |
| `YearEndClosingService` | Checklist, preview, `postMultiLine` close (`year-end:{fy_id}`), reopen via `reverseEvent` |
| `ScheduledFinancialReportService` | Due schedules → `acct_report_executions` + `storage/app/acct-reports/` |

Posting remains `AccountingService::postOnce` / `postMultiLine` only.

## UI

- Routes under `accounting/phase4/*`
- Views: `modules/Accounting/Resources/views/phase4/`
- Sidebar section **Phase 4 Analytics**
- Legacy `accounting/profit_and_loss` etc. redirect to `report/accounting/*`

## Scheduler

`accounting:process-scheduled-reports` daily 01:30 (`routes/console.php`).