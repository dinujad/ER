# Cost center P&amp;L (statutory)

`CostCenterReportService::profitAndLoss($costCenterId|null, $from, $to)`:

- **Income:** credits − debits on income accounts.
- **Expense:** debits − credits on expense accounts.
- **`null` cost center:** unassigned (`journal_entries.cost_center_id` IS NULL).

`acct_cost_allocations` are **management-only** and are **not** included in statutory P&amp;L (no double-count unless a future `require_gl_reclass` workflow posts journals).

Workspace: `accounting/cost-center-reports/profit-and-loss` with PDF/Excel and journal drill-down.