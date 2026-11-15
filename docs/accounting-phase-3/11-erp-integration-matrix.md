# Phase 3 — ERP Integration Matrix

Legend: **FULLY IMPLEMENTED & TESTED** | **PARTIAL** = IMPLEMENTED BUT PARTIALLY VERIFIED | **NO** = NOT IMPLEMENTED

| ERP module | Phase 3 touchpoint | Integration | Posting / data |
|------------|-------------------|-------------|----------------|
| **Purchases** | Asset from purchase | **NO** | `purchase_reference` column only; purchases still expense/AP paths |
| **Purchases** | Capitalization credit AP | **PARTIAL** | Manual capitalize defaults credit to `GL_ACCOUNTS_PAY` |
| **Inventory** | MFG RM/WIP/FG | **PARTIAL** | Unchanged Phase 1 `InventoryAccountingService`; summary reads GL |
| **Manufacturing** | Job costs | **PARTIAL** | `JobCostAccountingService` + existing `costBreakdown()` |
| **Manufacturing** | Overhead | **PARTIAL** | Simple average overhead service |
| **Sales** | Job revenue | **PARTIAL** | Order total when `order_id` on job |
| **Sales** | Asset disposal proceeds | **PARTIAL** | Disposal credits `GL_BANK` if proceeds > 0 |
| **Banking** | Loan disburse/repay | **FULLY IMPLEMENTED & TESTED** | Uses configured bank GL on loan |
| **Payroll** | Job labor dimension | **NO** | No `job_id` on payroll posts in Phase 3 |
| **HRM** | Departments | **PARTIAL** | Optional `department_id` on cost center / budget version |
| **Accounting** | Single ledger | **FULLY IMPLEMENTED & TESTED** | All Phase 3 money via `AccountingService` |
| **Accounting** | Period locks | **FULLY IMPLEMENTED & TESTED** | Phase 2.1 tests still pass |
| **Accounting** | Audit | **PARTIAL** | `AccountingAuditService` on major FA/loan/budget events |
| **Accounting** | `cost_center_id` / `job_id` | **PARTIAL** | Columns exist; limited observer coverage |

## Double-posting controls

- Capitalization: `postOnce` per asset
- Depreciation: per asset-period keys + run-level `run_key`
- Loans: disburse + payment event keys
- Cost allocations: **no GL** — cannot double-expense at ledger level

## Stub modules not used

Legacy `modules/Loan` / `modules/EmployeeLoan` stubs remain; Phase 3 uses `acct_loans` instead.