# 05 — Bug register (Phase 1)

## ACC-P0-001 — Bulk expense clear-all via GET

| Field | Value |
|-------|-------|
| Severity | **P0 Critical** |
| Module | Expenses / Accounting |
| Files | `routes/web.php` |
| Root cause | Anonymous GET route deleted all `Expense` and `SalesPersonExpense` rows (observers reversed journals), enabling mass financial wipe with a browser GET |
| Reproduction | Authenticated user with delete-class permission opens `/erp/expenses/clear-all` |
| Financial impact | Complete loss of expense history + journal reversals |
| Fix | Route body replaced with `abort(410)`; GET and POST both disabled; name retained |
| Test | `ActionPermissionsTest::test_expenses_clear_all_is_disabled_even_for_privileged_users` |
| Status | **Fixed** |

## ACC-P1-001 — Float amounts without 2dp normalization

| Field | Value |
|-------|-------|
| Severity | **P1 High** |
| Module | AccountingService |
| Files | `app/Services/AccountingService.php` |
| Root cause | `postEntry` / `postOnce` accepted raw floats; DB is decimal — risk of 0.01 imbalance |
| Reproduction | Post amount `10.125` without rounding |
| Financial impact | Possible unbalanced or drifted journal lines |
| Fix | `normalizeMoney()` rounds to 2 decimals and rejects ≤0 / non-numeric before insert |
| Test | `AccountingServiceTest` normalize + rounded postOnce tests |
| Status | **Fixed** |

## ACC-P1-002 — Unbalanced / orphan integrity visibility

| Field | Value |
|-------|-------|
| Severity | **P1 High** (observability) |
| Module | Ops / Accounting |
| Files | `app/Console/Commands/DiagnoseAccountingIntegrityCommand.php` |
| Root cause | No read-only tool to list unbalanced txn groups / orphan postings |
| Fix | `php artisan accounting:diagnose` (+ `--json`) |
| Test | Command registered; manual/local run in safe env |
| Status | **Fixed (tooling)** — does not rewrite history |

## ACC-P2-001 — PurchasePaymentObserver missing Purchase import

| Field | Value |
|-------|-------|
| Severity | P2 (was runtime break) |
| Files | `app/Observers/PurchasePaymentObserver.php` |
| Status | **Already fixed** prior to Phase 1 (`use App\Models\Purchase`) |

## ACC-P2-002 — Dual AccountingService classes

| Field | Value |
|-------|-------|
| Severity | P2 Risk |
| Status | **Documented** — no rename in Phase 1 (break risk) |

## ACC-P2-003 — collapseDuplicateJournals keep-first heuristic

| Field | Value |
|-------|-------|
| Severity | P2 Risk |
| Status | **Documented** for Phase 2; existing test covers keep-one-live-pair |

## ACC-P3-001 — Cash-only credits for SPE / vehicle / payroll pay / mfg extra cost

| Field | Value |
|-------|-------|
| Severity | P3 |
| Status | **Documented** — enhancement Phase 2 |

## ACC-DESIGN-001 — Order recognition afterchecking

| Field | Value |
|-------|-------|
| Severity | Design (not bug) |
| Status | Documented intentional gate; revenue after invoice complete |

## Verification pass (2026-11-15)

`AccountingPhase1VerificationTest` multi-module E2E on sqlite found **no new P0/P1** defects. Cheque bounce reverse, TB/BS/P&L equations, and diagnose clean on the synthetic books.
