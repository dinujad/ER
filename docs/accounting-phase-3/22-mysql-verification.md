# MySQL verification — Phase 3 completion

**`.env`:** `DB_CONNECTION=mysql`, host `127.0.0.1`, database `dermas` (local XAMPP-style)

## Status

| Check | Result |
|-------|--------|
| Config present | YES (`mysql`) |
| `php artisan migrate --pretend --path=...210000_phase3_completion_tables.php` | **PASS** — MariaDB/MySQL DDL generated (decimal(20,4), unique purchase_item_id, indexes) |
| Live migrate + financial mutation on staging | **NOT RUN** from agent (avoid mutating shared local DB without operator OK) |
| `php artisan db:show` / performance_schema | Previously failed on this instance — treat as environment quirk |
| Production | **NOT TOUCHED** |

**Overall:** **PARTIALLY VERIFIED** (SQL dialect / pretend migration) — **STAGING MUTATE NOT VERIFIED**.

## Recommended staging commands (operator)

```bash
# Confirm NOT production
php artisan db:show
php artisan migrate --path=database/migrations/2026_11_15_200000_create_accounting_phase3_tables.php
php artisan migrate --path=database/migrations/2026_11_15_210000_phase3_completion_tables.php
php artisan test --filter=Accounting
```

Confirm `1410` Fixed Asset Clearing exists in `chart_of_accounts` after migrate/seed.
