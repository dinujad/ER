# Period Lock & Security

`AccountingPeriodService::assertOpenForDate` is called from `AccountingService::postEntry` (hence `postOnce` / `postMultiLine`).

## Verified

`test_closed_period_blocks_post_once` — closed November blocks posting on 2026-11-15.

## Notes

- Draft manual journals may still be saved; posting is blocked when period closed.
- ERP observers that call `postOnce` are covered by the same gate.
- Permissions remain at controller `can_access` / admin checks; no weakening in 2.1.
- Production auth matrix browser pass: NOT VERIFIED in this run.
