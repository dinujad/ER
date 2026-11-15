# Phase 4.1 — Fix Changelog

| File | Change |
|------|--------|
| `CostCenterReportService.php` | `companyWideProfitAndLoss()` |
| `FinancialStatementService.php` | company-wide P&L; FY-scoped BS earnings; CF cash_gl meta |
| `YearEndClosingService.php` | checklist gate; reopen clears txn + versioned event_key; live posting check |
| `TaxManagementService.php` | require tax period before adjust |
| `CashFlowForecastService.php` | remaining amount helper |
| `CustomReportBuilderService.php` | business scope on update/delete |
| `FinancialKpiAnalyticsService.php` | loans in current ratio |
| `RequiresAccountingPermission.php` | `assertBusinessScope()` |
| Controllers year-end / tax / custom report | scope checks |
| `AccountingPhase41StabilityTest.php` | 6 new regression cases |
