# 08 — Data integrity report (Phase 1)

## Policy

- No automatic rewrite of historical financial records.  
- No production diagnose/repair executed from this Phase 1 commit.  
- Operators must backup before any future repair.

## Tooling

```bash
php artisan accounting:diagnose
php artisan accounting:diagnose --json
```

Reports (read-only):

1. Unbalanced `transaction_number` groups (DR ≠ CR among non-reversed lines)  
2. Orphan `accounting_postings` (no matching JE rows)  
3. Duplicate active `event_key` counts  
4. Zero-amount active JE lines  

## Expected healthy state after COA + backfill

- Unbalanced groups: 0 (or explained manual edits)  
- Orphan postings: 0  
- Duplicate active event keys: 0 (unique index should prevent)  

## Proposed controlled repair (approval required)

| Issue | Procedure |
|-------|-----------|
| Duplicate live journals for same source | Prefer `AccountingService::collapseDuplicateJournals()` after backup |
| Missing journals for historical ERP rows | `accounting:backfill --force` after COA seed |
| Unbalanced single TXN | Investigate; reverse malformed TXN; re-post via event key — do not UPDATE amounts in place |

## Production note

Live site previously ran `/deploy/{key}/accounting-repair` (COA seed + backfill). Re-run diagnose on a DB copy before any further mass correction.
