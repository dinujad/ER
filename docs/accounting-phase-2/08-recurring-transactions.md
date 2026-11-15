# Phase 2 — Recurring Transactions

## Implemented

- Templates: daily/weekly/monthly/quarterly/yearly.
- Modes: `draft`, `draft_notify` (draft; notify hook placeholder), `auto_post`.
- Idempotent runs: unique `(template_id, occurrence_date)`.
- Artisan: `accounting:process-recurring` scheduled daily 01:15.
- UI: `/accounting/recurring-journals`.

## Not fully implemented

- Recurring supplier bills / customer invoices as separate document generators (journals only in Phase 2).
- Email notify for `draft_notify`.
- Large historical backfill (explicitly avoided).

## Tests

Idempotent occurrence in `AccountingPhase2CoreTest`.
