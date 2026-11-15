# Tax Management

Configurable `acct_tax_codes` (rate + effective dates + GL mapping). No hardcoded Sri Lanka statutory rates.

- `taxSummary(period)` aggregates journals on mapped tax accounts
- `postAdjustment` uses `postOnce` with unique `event_key`
- `closePeriod` locks `acct_tax_periods`

CoA: 2300 Payable, 1160 Receivable (seeded in migration).