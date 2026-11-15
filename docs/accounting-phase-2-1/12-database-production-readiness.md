# Database & Production Readiness

## Local MySQL

- Repair migration applied: `2026_11_15_170000_repair_chart_of_accounts_missing_columns`
- Phase 2 migrations already applied earlier

## SQLite tests

48/48 pass in phpunit `:memory:` — not a substitute for production MariaDB/MySQL.

## Staging / Production

| Status | Result |
|--------|--------|
| DEVELOPMENT | COMPLETE (this branch) |
| STAGING VERIFIED | NOT VERIFIED (no staging run this phase) |
| PRODUCTION VERIFIED | NOT VERIFIED |

### Operator checklist (read-only prod)

1. Confirm environment + backup.
2. `php artisan migrate --pretend` then migrate on staging first.
3. `php artisan accounting:diagnose` (if present) read-only.
4. Spot-check AR/AP reconcile helpers vs ageing.
5. Do not bulk-correct journals without approval.
