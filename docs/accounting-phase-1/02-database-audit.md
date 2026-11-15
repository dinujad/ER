# 02 — Database audit (Phase 1)

**Mode:** Read-only inspection. No production data mutated during audit.

## Tables in scope

| Table | Notes |
|-------|-------|
| `chart_of_accounts` | GL codes; seed uses `business_id = 0` |
| `journal_entries` | Double-entry lines; `debit`/`credit` decimal; shared `transaction_number` |
| `accounting_postings` | Idempotency by unique `event_key` |
| `account_subtypes`, `account_detail_types` | COA classification |
| `payment_details` | Manual JE payment metadata |
| `budgets` | Budgeting module |
| Domain tables | `orders`, `payments`, `purchases`, `purchase_payments`, `expenses`, `sale_returns`, `payroll_runs`, stock/mfg tables |

## Verified strengths

1. JE amounts stored as decimal (module migration `decimal(65,4)` / test schema `20,4`).
2. `accounting_postings.event_key` unique + `postOnce` uses `lockForUpdate` — duplicate event protection.
3. Source traceability columns on JE (`source_type` / `source_id` / `payment_id` / `order_id`).
4. Reversal flags (`reversed`) keep history instead of hard-delete.

## Findings / risks

| ID | Risk | Level | Notes |
|----|------|-------|-------|
| DB-01 | Float PHP amounts before insert | P1 | Mitigated Phase 1 via `AccountingService::normalizeMoney` (round 2 dp) |
| DB-02 | COA `business_id` 0 vs session business 1 | P2 | Joins already allow 0/1; document for ops |
| DB-03 | ERP journals often `location_id` null | P2 | Location-filtered APIs may hide rows |
| DB-04 | Orphan postings / unbalanced groups | P1 | Detect with `php artisan accounting:diagnose` |
| DB-05 | Soft deletes on domain models | P2 | Observers reverse on delete; mass GET wipe was P0 (disabled) |
| DB-06 | No FK from JE to domain tables | Design | Soft refs by design; do not add cascading FKs in Phase 1 |

## Recommended indexes (document only — no Phase 1 migration unless approved)

- Already indexed: `accounting_postings.event_key`, JE `source_*`, `payment_id`, `order_id`
- Optional later: composite `(transaction_number, reversed)` if diagnose is slow on large DBs

## Safe diagnostics

```bash
php artisan accounting:diagnose
php artisan accounting:diagnose --json
```

## Backup / recovery before any future repair

1. mysqldump / cPanel backup of production DB  
2. Dry-run diagnose on copy  
3. Prefer reverse + re-post via `postOnce` event keys — never silent UPDATE of historical debit/credit  
4. Rollback: restore dump  

**No `migrate:fresh` / wipe on existing databases.**
