# Phase 4.1 — Cost Center Semantics

| API | `cost_center_id` filter |
|-----|-------------------------|
| `CostCenterReportService::profitAndLoss(null, …)` | Unassigned only |
| `CostCenterReportService::profitAndLoss($id, …)` | Specific CC |
| `CostCenterReportService::companyWideProfitAndLoss` | None (all lines) |
| `FinancialStatementService::profitAndLoss` | Company-wide via `incomeExpenseTotals` |

`companyTotals()` unchanged: still reports unassigned bucket plus assigned aggregates.