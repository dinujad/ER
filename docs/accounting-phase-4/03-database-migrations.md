# Phase 4 — Database Migrations

**File:** `database/migrations/2026_11_15_220000_create_accounting_phase4_tables.php`

Additive `Schema::hasTable` guards.

## Tables

| Table | Purpose |
|-------|---------|
| `acct_saved_reports` | Named report configs + favorites |
| `acct_report_schedules` | Frequency + `next_run_at` |
| `acct_report_executions` | Idempotent `event_key`, file path on `local` disk |
| `acct_cash_forecast_scenarios` | base / optimistic / conservative |
| `acct_cash_forecast_runs` | Run snapshot JSON |
| `acct_tax_codes` | Rate + GL mapping (no hardcoded statutory rates) |
| `acct_tax_periods` | open / closed |
| `acct_tax_adjustments` | Linked journals via `postOnce` |
| `acct_financial_years` | open / closing / closed |
| `acct_year_end_sessions` | Checklist, preview, `closing_txn`, `event_key` |

## CoA seed (if missing)

- `2300` Tax Payable (liability)
- `1160` Tax Receivable (asset)

Constants: `AccountingService::GL_TAX_PAYABLE`, `GL_TAX_RECEIVABLE`.