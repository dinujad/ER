# Cash Flow Forecast

`CashFlowForecastService::runScenario()` — read-only.

- Opening cash: GL 1000 + 1010
- Inflows: unpaid `sell` transactions (if `transactions` table exists) × `collection_pct` + `delay_days`
- Outflows: unpaid purchases + due `acct_loan_schedule_lines`
- Persists run to `acct_cash_forecast_runs` (no `journal_entries` inserts)

**Test:** `test_forecast_does_not_create_journals`