# Phase 4.1 — Financial Statements

## BUG-001/002

- `CostCenterReportService::profitAndLoss(null, …)` remains **unassigned-only** for cost-center reports.
- New `companyWideProfitAndLoss($from, $to)` includes all journal lines.
- `FinancialStatementService::incomeExpenseTotals()` calls `companyWideProfitAndLoss`.

## BUG-004

`balanceSheet($asOf)` uses `acct_financial_years.starts_on` when a FY covers `$asOf`; else calendar `Y-01-01`.

## BUG-011

`cashFlowIndirect` meta now exposes `indirect_net_change`, `cash_gl_change` (GL 1000+1010), and `variance_vs_cash_gl` with explicit reconciliation note.