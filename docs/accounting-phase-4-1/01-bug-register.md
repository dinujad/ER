# Phase 4.1 — Bug Register

**Branch:** `feature/accounting-phase-4-1`  
**Status:** PHASE 4.1 DEVELOPMENT COMPLETE — PRODUCTION VERIFICATION PENDING

| ID | Priority | Summary | Fix location |
|----|----------|---------|--------------|
| BUG-001 | P0 | Statutory P&L used `profitAndLoss(null)` → unassigned lines only | `CostCenterReportService::companyWideProfitAndLoss`, `FinancialStatementService::incomeExpenseTotals` |
| BUG-002 | P0 | Same as 001 (company-wide vs CC-null semantics) | Same |
| BUG-003 | P0 | Year-end reopen blocked re-close (fixed event_key, stale closing_txn) | `YearEndClosingService::reopen`, `executeClosing` |
| BUG-004 | P1 | Balance sheet `current_earnings` always calendar YTD | `FinancialStatementService::balanceSheet` |
| BUG-006 | P0 | Year-end execute skipped checklist | `YearEndClosingService::executeClosing` |
| BUG-008 | P1 | Tax `postAdjustment` with missing period could null-deref | `TaxManagementService::postAdjustment` |
| BUG-009 | P1 | Forecast inflows used full `final_total` ignoring payments | `CashFlowForecastService::transactionRemainingAmount` |
| BUG-011 | P1 | Indirect cash flow meta lacked GL cash reconciliation fields | `FinancialStatementService::cashFlowIndirect` |
| BUG-012 | P1 | Custom report delete without business check | `CustomReportBuilderService::delete`, controller |
| BUG-013 | P1 | Year-end / tax summary without business scope | Controllers + `RequiresAccountingPermission::assertBusinessScope` |
| BUG-014 | P1 | KPI current ratio omitted loans vs dashboard | `FinancialKpiAnalyticsService::analyze` |