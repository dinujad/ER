# Phase 4.1 — Year-End Closing

## BUG-003

- `reopen`: reverses `year-end:{id}` and session `event_key`; sets `status=reopened`, `closing_txn=null`, `event_key=year-end:{id}:v{timestamp}`.
- `executeClosing`: uses session `event_key` when reopened or posting reversed; checks `accounting_postings.reversed` via `hasLivePosting`.
- Updates existing session row on re-close instead of orphan rows.

## BUG-006

After FY row lock, `buildChecklist` runs; if `ready` is false → `RuntimeException` with TB / unposted manual counts. No force flag.