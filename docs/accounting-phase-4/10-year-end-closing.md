# Year-End Closing

`YearEndClosingService`:

1. `buildChecklist` — TB balance, open periods, unposted manual journals (if table exists), AR/AP balances
2. `previewClosing` — income/expense account balances
3. `executeClosing` — `postMultiLine` with `event_key` `year-end:{financial_year_id}` (idempotent)
4. `reopen` — `AccountingService::reverseEvent` + FY status open (does not delete history)

Default retained earnings: GL 3000 (`GL_OWNER_EQUITY`).