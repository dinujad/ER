# Controllers & Routes

Under `modules/Accounting/Http/routes.php` → `accounting/phase4/*`:

| Controller | Prefix |
|------------|--------|
| `FinancialDashboardController` | `financial-dashboard` |
| `FinancialStatementController` | `statements/*` |
| `CustomReportController` | `custom-reports` |
| `CashForecastController` | `cash-forecast` |
| `FinancialKpiController` | `kpi-analytics` |
| `TaxManagementController` | `tax` |
| `YearEndClosingController` | `year-end` |
| `ScheduledReportController` | `scheduled-reports` |

Legacy redirects: `accounting/profit_and_loss` → `report/accounting/profit_and_loss` (and TB/BS/cash flow/ledger).