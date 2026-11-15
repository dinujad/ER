# Custom Report Builder

`CustomReportBuilderService::ALLOWED_TYPES`:

- `profit_and_loss`, `balance_sheet`, `trial_balance`, `cash_flow_indirect`, `changes_in_equity`, `cost_center_pl`, `budget_vs_actual`

CRUD on `acct_saved_reports`. Invalid `report_type` throws `RuntimeException` (tested).

**Routes:** `accounting/phase4/custom-reports`