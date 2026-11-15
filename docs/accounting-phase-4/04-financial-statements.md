# Financial Statements Service

`App\Services\Accounting\FinancialStatementService`

| Method | Notes |
|--------|-------|
| `profitAndLoss(from,to,compare?)` | Delegates income/expense net to `CostCenterReportService` (company-wide when `cost_center_id` null; skips CC filter if column missing) |
| `balanceSheet(asOf)` | Asset/liability/equity balances + current-period earnings in equity |
| `cashFlowIndirect(from,to)` | Heuristic operating/investing/financing; documents assumptions in `meta` |
| `changesInEquity(from,to)` | Equity GL movement + current earnings |
| `trialBalance(from,to)` | Period debits/credits per account |

**UI:** `accounting/phase4/statements/*`

**Legacy reports:** `report/accounting/*` unchanged.