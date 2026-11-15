# Phase 4.1 — Test Results

**Command:** `php artisan test --filter=Accounting`

**Summary line (after Phase 4.1):**

```
Tests:    87 passed (251 assertions)
Duration: 12.72s
```

| Class | Tests |
|-------|-------|
| `AccountingPhase41StabilityTest` | 6 (new) |
| `AccountingPhase4CoreTest` | 8 |
| Phase 1–3 + service/UI suites | 73 |

**Exact stability assertion highlights:**

- Company-wide P&L income `500.0` with CC-assigned journals.
- Re-close produces distinct `closing_txn` and `closed` FY status.
- Forecast inflow `600.0` when `amount_paid=400` on `1000` total.