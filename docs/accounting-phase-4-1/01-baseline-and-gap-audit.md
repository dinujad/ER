# Phase 4.1 — Baseline and Gap Audit

**Branch:** `feature/accounting-phase-4-1`  
**Baseline main:** `e60f379c` (Phase 4 on main)  
**Working tree:** clean at branch creation; Phase 4.1 fixes uncommitted until push approval  

## Baseline tests (independent)

```
php artisan test --filter=Accounting
Tests:    81 passed (240 assertions)   # before Phase 4.1 fixes
```

After fixes:

```
Tests:    87 passed (251 assertions)
```

## Gaps found (evidence-based)

See `01-bug-register.md` / `13-bug-register.md` for P0–P2 register.

Critical: statutory P&L excluded cost-centered journals; year-end reopen blocked re-close; checklist not enforced on execute.

## Approach

Reproduce → classify → fix with smallest change → regression test → re-run Accounting suite. No Phase 5. No push without approval.
