# Phase 3 — Test Results

**Date:** 2026-11-15  
**Command:** `php artisan test --filter=Accounting`  
**Working directory:** `c:\dev\dermas`  
**Database:** SQLite (Laravel feature test harness)

## Exact summary line

```
Tests:    64 passed (197 assertions)
  Duration: 8.60s
```

## Test classes executed

| Class | Tests | Phase |
|-------|-------|-------|
| `AccountingPhase1VerificationTest` | 4 | 1 |
| `AccountingPhase21StabilityTest` | 10 | 2.1 |
| `AccountingPhase2CompletionTest` | 7 | 2 |
| `AccountingPhase2CoreTest` | 9 | 2 |
| `AccountingPhase3CoreTest` | 14 | **3 new** |
| `AccountingPhase3WorkspaceUiTest` | 2 | **3 new** |
| `AccountingServiceTest` | 13 | 1 |
| `AccountingWorkspaceUiTest` | 5 | 1.5/2 |

**Phase 3 new tests:** 16 (14 core + 2 UI)

## Phase 3 core scenarios covered

- Asset capitalization balanced + idempotent
- Straight-line and declining-balance amounts
- Depreciation run idempotent + reverse + re-run
- Disposal with gain
- Loan disburse, scheduled payment, duplicate payment guard
- Loan early settle + equal principal schedule
- Cost allocation validation
- Budget version submit/approve/activate + vs actual
- Job ledger debits by `job_id`

## Phase 3 UI scenarios

- 12 workspace GET routes return 200/302 for `accounting.view` user
- Dashboard renders Phase 3 sidebar labels

## Pre-Phase 3 baseline (documented in gap analysis)

Before Phase 3 changes: **48 passed (143 assertions)** — same filter.

## Not run / not in filter

- Full PHPUnit suite (non-Accounting)
- POS/Sales/Purchase/Manufacturing integration suites outside filter
- MySQL-specific migration tests

## Failures

None in this run (exit code 0).