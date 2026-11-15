# Phase 3 — Architecture

**Branch:** `feature/accounting-phase-3`  
**Baseline:** Phase 2.1 on `dcd01c53`

## Design principle

Phase 3 adds **management accounting modules** on top of the existing single authoritative ledger. All monetary flows still pass through `App\Services\AccountingService` (`postOnce`, `postMultiLine`). No parallel sub-ledger.

```
┌─────────────────────────────────────────────────────────────┐
│  Accounting Workspace UI (modules/Accounting/Http/...)      │
│  phase3/* Blade views · RequiresAccountingPermission          │
└───────────────────────────┬─────────────────────────────────┘
                            │
┌───────────────────────────▼─────────────────────────────────┐
│  Domain services (app/Services/Accounting/)                   │
│  FixedAssetService · DepreciationEngine · AssetDisposalService│
│  LoanAccountingService · CostCenterService                    │
│  JobCostAccountingService · AdvancedBudgetService             │
└───────────────────────────┬─────────────────────────────────┘
                            │
┌───────────────────────────▼─────────────────────────────────┐
│  AccountingService + AccountingAuditService + Period locks   │
│  journal_entries (+ cost_center_id, job_id dimensions)        │
│  accounting_postings (event_key idempotency)                  │
└───────────────────────────┬─────────────────────────────────┘
                            │
┌───────────────────────────▼─────────────────────────────────┐
│  ERP sources (unchanged Phase 1–2.1)                        │
│  Sales · Purchases · Inventory/MFG · Payroll · Banking        │
└─────────────────────────────────────────────────────────────┘
```

## Service responsibilities

| Service | Responsibility |
|---------|----------------|
| `FixedAssetService` | Draft register, capitalization journal, asset events, audit |
| `DepreciationEngine` | Schedule generation, monthly run, preview, reverse run |
| `AssetDisposalService` | Multi-line disposal GL (accum/cash/gain/loss/asset) |
| `LoanAccountingService` | Loan lifecycle, schedules (equal installment/principal/interest-only), disburse, pay, accrue, early settle |
| `CostCenterService` | Master data, circular-parent guard, allocation header/lines (exact total validation) |
| `JobCostAccountingService` | Manufacturing job summary + `job_id` journal aggregation |
| `AdvancedBudgetService` | Version CRUD workflow, activate/supersede, budget vs actual from posted JEs |

## Models

All under `app/Models/Accounting/`:

- **Fixed assets:** `FaAssetCategory`, `FaAsset`, `FaAssetEvent`, `FaDepreciationSchedule`, `FaDepreciationRun`, `FaDisposal`
- **Loans:** `AcctLoan`, `AcctLoanScheduleLine`, `AcctLoanPayment`
- **Cost:** `AcctCostCenter`, `AcctCostAllocation`, `AcctCostAllocationLine`
- **Budget:** `AcctBudgetVersion`, `AcctBudgetVersionLine`

## Controllers and routes

Registered in `modules/Accounting/Http/routes.php` inside the existing accounting middleware group (lines 160–232). Controllers live in `modules/Accounting/Http/Controllers/` with shared `RequiresAccountingPermission` trait (`accounting.view` / `accounting.manage`).

## GL constants

Added to `AccountingService` (examples): `GL_FIXED_ASSETS` (1400), `GL_ACCUMULATED_DEPRECIATION` (1450), `GL_LOANS_PAYABLE` (2200), `GL_INTEREST_PAYABLE` (2210), `GL_DEPRECIATION_EXPENSE` (6400), `GL_INTEREST_EXPENSE` (6410), gain/loss on disposal (4500 / 6500).

Migration `2026_11_15_200000_create_accounting_phase3_tables.php` also `updateOrInsert`s matching CoA rows at `business_id = 0`.

## Idempotency keys (examples)

| Event | Key pattern |
|-------|-------------|
| Capitalize | `fa:capitalize:{asset_id}` |
| Depreciation | `fa:depr:{asset_id}:{year}-{month}` |
| Disposal | `fa:dispose:{asset_id}` |
| Loan disburse | `loan:disburse:{loan_id}` |
| Loan payment | `loan:payment:{loan_id}:{date}:{amount}` |
| Depreciation run | `run_key` `depr-run:{business}:{year}-{month}` (unique per run; suffix after reverse) |

## Journal dimensions

Migration adds nullable `cost_center_id` and `job_id` on `journal_entries`. Phase 3 postings pass `cost_center_id` where applicable (capitalization, depreciation, disposal). Broader ERP observers do not yet stamp dimensions on all expense posts.

## What was intentionally not built

- Separate loan or asset payment engines
- Tax depreciation ledger
- Purchase-line auto-capitalization observer
- Cost allocation GL reclassification (allocations are analytical records only)

See `15-remaining-issues.md` for full gap list.