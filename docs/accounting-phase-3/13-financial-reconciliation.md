# Phase 3 — Financial Reconciliation

## Rules from brief (§13) vs verification

| # | Rule | Verification |
|---|------|--------------|
| 1 | Journals balance | **Verified** in Phase 3 tests (capitalize, disposal) + Phase 1–2 suites |
| 2 | Asset register ↔ fixed asset GL | **NOT VERIFIED** — no automate register-to-GL tie-out test |
| 3 | Accumulated dep ↔ GL | **NOT VERIFIED** |
| 4 | Loan outstanding ↔ loan payable GL | **NOT VERIFIED** |
| 5 | Job costs ↔ sources | **PARTIAL** — operational breakdown vs `job_id` debits (test posts manual JE only) |
| 6 | WIP ↔ WIP GL | **PARTIAL** — summary page displays balance; no reconcile assert |
| 7 | FG ↔ inventory GL | **PARTIAL** — same as WIP |
| 8 | CC allocations do not inflate expenses | **Verified by design** — allocations do not post JEs |
| 9 | Budget actuals = ledger | **Verified** in `test_budget_version_workflow_and_vs_actual` |
| 10 | AR/AP reconciliation | **Verified** — Phase 2.1 tests still pass |
| 11 | Period locks | **Verified** — Phase 2 core tests |
| 12 | Audit trail | **PARTIAL** — audit service calls on key events; not full UI audit review |
| 13 | Idempotent background-style posts | **Verified** — dep run, loan payment, capitalize |
| 14 | Cross-business isolation | **NOT VERIFIED** in Phase 3 tests |
| 15 | Source document trace | **PARTIAL** — `source_type` / `source_id` on posts where set |

## Practical reconciliation steps (staging)

After `php artisan migrate`:

1. Capitalize one test asset → confirm two-line JE, FA GL debit = asset cost.
2. Run one depreciation month → dep expense / accum dep pair equals schedule.
3. Dispose asset → multi-line JE balances; asset status `disposed`.
4. Disburse and pay loan → bank and liability nets move as expected.
5. Activate budget version → vs-actual month matches trial of JEs for same account/month.

## Tax vs book

**Not assumed.** Single book depreciation schedule only.

## MySQL

Staging reconcile on MySQL: **NOT VERIFIED**.