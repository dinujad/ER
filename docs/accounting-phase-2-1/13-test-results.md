# Phase 2.1 — Test Results

**Environment:** PHP 8.2.12, PHPUnit 11.5.53, sqlite `:memory:` (phpunit.xml)  
**Date:** 2026-11-15

## Command

```
php vendor/bin/phpunit ^
  tests/Feature/AccountingPhase21StabilityTest.php ^
  tests/Feature/AccountingPhase2CompletionTest.php ^
  tests/Feature/AccountingPhase2CoreTest.php ^
  tests/Feature/AccountingServiceTest.php ^
  tests/Feature/AccountingWorkspaceUiTest.php ^
  tests/Feature/AccountingPhase1VerificationTest.php
```

## Result

| Metric | Value |
|--------|-------|
| Tests | 48 |
| Assertions | 143 |
| Passed | 48 |
| Failed | 0 |
| Errors | 0 |
| Skipped | 0 |

## New class

`tests/Feature/AccountingPhase21StabilityTest.php` — 10 tests (COA hierarchy, AR reconcile, advances, sale-return COGS, inventory, cheque, period lock, TB).
