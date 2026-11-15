# Phase 2 — Test Results

**Environment:** PHPUnit + sqlite `:memory:` (phpunit.xml). Never production.

## Phase 2 core

```
php vendor/bin/phpunit tests/Feature/AccountingPhase2CoreTest.php
```

**Result:** OK — **9 tests, 20 assertions** (2026-11-15 local run)

Coverage includes:

1. Multi-line balanced post  
2. Unbalanced rejection  
3. Manual lifecycle + duplicate post protection  
4. Reversal opposite entries  
5. Account register opening/running/closing  
6. Closed period blocks posting  
7. Period reopen allows posting  
8. Bank statement import dedupe  
9. Recurring idempotent execution  

## Regression

| Suite | Result |
|-------|--------|
| `AccountingServiceTest` | OK (13 tests) |
| `AccountingPhase1VerificationTest` | OK (4 tests) |
| `AccountingWorkspaceUiTest` | OK (5 tests) |

## Not automated in this pass

- HTTP browser E2E for every Phase 2 Blade page  
- Concurrent worker stress beyond `postOnce` locking  
- Full AR/AP allocation HTTP flows  
- Cheque bounce E2E  
- Excel statement import  

Those remain in `11-remaining-issues.md`.
