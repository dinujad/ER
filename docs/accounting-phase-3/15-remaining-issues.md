# Phase 3 — Remaining Issues

**Overall Phase 3 acceptance:** **PARTIALLY COMPLETE** — core backend + workspace UI exist; several mandatory brief items remain open.

## P1 — Functional gaps

| Issue | Classification |
|-------|----------------|
| Purchase-line capital asset workflow | **NOT IMPLEMENTED** (`purchase_reference` only) |
| Asset register PDF/Excel export | **NOT IMPLEMENTED** |
| Asset attachments storage | **NOT IMPLEMENTED** |
| Cost center P&L / department / branch reports | **NOT IMPLEMENTED** |
| Manufacturing configurable overhead bases | **NOT IMPLEMENTED** (simple average remains) |
| Interest accrual UI + payable-on-repayment pattern | **IMPLEMENTED BUT PARTIALLY VERIFIED** |
| Custom loan repayment schedules | **NOT IMPLEMENTED** |
| GL register reconciliations (FA, loan, WIP) | **NOT IMPLEMENTED** (automated) |
| ERP-wide `cost_center_id` / `job_id` on auto-posts | **NOT IMPLEMENTED** |

## P2 — Verification gaps

| Issue | Classification |
|-------|----------------|
| MySQL/MariaDB staging migrate + finance queries | **NOT VERIFIED** |
| Closed-period rejection on depreciation run (dedicated test) | **IMPLEMENTED BUT PARTIALLY VERIFIED** |
| Asset approval/submit/transfer/deactivate flows | **IMPLEMENTED BUT PARTIALLY VERIFIED** (no tests) |
| Tax depreciation separate from book | **NOT IMPLEMENTED** (documented as out of scope) |

## Process / deployment

| Item | Status |
|------|--------|
| Git push | **NOT PUSHED** |
| Working tree | Uncommitted Phase 3 files on `feature/accounting-phase-3` |
| Phase 4 | **NOT STARTED** |
| Phase 5 | **NOT STARTED** |

## Inherited Phase 1–2 residuals

Unchanged: draft_notify email, bank import column mapper UI, some PARTIAL Phase 2 items — see Phase 2 docs.

## No open P0 in Phase 3 regression filter

All 64 Accounting-filter tests pass on SQLite.