# Phase 2.1 — Baseline Audit

**Branch:** `fix/accounting-phase-2-1-stability`  
**Baseline HEAD before work:** `049569d7`  
**Currency:** LKR  

## Uncommitted carry-in (preserved)

- `2026_11_15_170000_repair_chart_of_accounts_missing_columns.php` — local COA stub schema repair (`name` missing caused dashboard 500).
- Defensive `AccountingWorkspaceMetrics` / ERP ledger select when `name` absent.

## Baseline tests (re-executed)

```
php vendor/bin/phpunit tests/Feature/AccountingPhase2CompletionTest.php tests/Feature/AccountingPhase2CoreTest.php tests/Feature/AccountingServiceTest.php tests/Feature/AccountingWorkspaceUiTest.php tests/Feature/AccountingPhase1VerificationTest.php
```

**Result:** OK — **38 tests, 122 assertions**

## Inherited gaps (from Phase 2 docs)

- COA circular parent validation incomplete
- AR/AP GL reconcile helpers partial
- Sale return COGS / inventory qty vs GL / cheque Eloquent E2E PARTIAL
- Production diagnose NOT VERIFIED
- `draft_notify` email not wired

## Approach

Verify with database-backed tests; fix P0/P1; document remaining operational gaps. No Phase 3 features.
