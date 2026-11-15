# 06 — Fix changelog (Phase 1)

## Code changes

| Change | Impact |
|--------|--------|
| Disabled `expenses.clear-all` mass delete (`routes/web.php`) | Prevents GET/POST bulk wipe of expenses + journal reversals |
| `AccountingService::normalizeMoney` + use in `postEntry` / `postOnce` | All auto-posted amounts rounded to 2 dp (LKR) |
| `accounting:diagnose` command | Read-only integrity scan for unbalanced / orphan / duplicate keys |
| Tests in `AccountingServiceTest` + `ActionPermissionsTest` | Regression for money rounding and clear-all 410 |

## Documentation added

All under `docs/accounting-phase-1/` (01–09).

## Not changed (by design)

- No production data rewrite  
- No multi-line journal engine  
- No multi-currency  
- No Phase 2–5 features  
- Historical journals left intact (diagnose only)  
