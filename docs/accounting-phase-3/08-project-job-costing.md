# Phase 3 — Project & Job Costing

**Status:** IMPLEMENTED BUT PARTIALLY VERIFIED  
**Service:** `app/Services/Accounting/JobCostAccountingService.php`  
**UI:** `modules/Accounting/Resources/views/phase3/job_costs/*`

## Approach

Phase 3 **does not** create a parallel job system. It wraps existing `App\Models\ManufacturingJob` and `ManufacturingJob::costBreakdown()`.

## `summarizeJob()`

Returns:

- Material, additional, overhead totals from existing breakdown
- `revenue` from linked `order_id` total when column exists (else 0 — no invented sales)
- Gross profit and margin %
- `ledger_debits` — sum of `journal_entries.debit` where `job_id` matches
- Full breakdown passthrough

## Ledger dimension

Migration adds `journal_entries.job_id`. `AccountingService::postEntry` accepts optional `job_id` in meta (used in tests).

Production ERP observers **may not** consistently stamp `job_id` on manufacturing-related journals — only verified in isolated test post.

## UI

- `accounting/job-costs` — job list (manufacturing jobs)
- `accounting/job-costs/{id}` — cost sheet view combining operational breakdown + ledger lines

## Gaps vs brief

| Item | Status |
|------|--------|
| Estimated vs actual report | **Partial** (show page only) |
| Labor from payroll allocation | **NOT IMPLEMENTED** in Phase 3 |
| Committed costs | **NOT IMPLEMENTED** |
| Job closure accounting | **NOT IMPLEMENTED** |
| Material usage / overhead allocation reports | **NOT IMPLEMENTED** as dedicated reports |

## Tests

`test_job_ledger_debits_by_job_id` — posts manual job_cost entry with `job_id=42`, asserts debit sum and line count.