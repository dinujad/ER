# Phase 4.1 — Cash Forecast

## BUG-009

`transactionRemainingAmount()`:

1. `final_total - amount_paid` when `amount_paid` column exists.
2. Zero when `payment_status=paid`.
3. Else sum `transaction_payments` when table exists.
4. Fallback: full `final_total` (documented in code).