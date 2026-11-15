# Phase 3 — Advanced Budgeting

**Status:** FULLY IMPLEMENTED & TESTED (version workflow + vs-actual)  
**Service:** `app/Services/Accounting/AdvancedBudgetService.php`  
**UI:** `modules/Accounting/Resources/views/phase3/budgets/*`

## Data model

- `acct_budget_versions` — header: name, financial_year, status, optional `cost_center_id`, `department_id`, `job_id`, approval fields
- `acct_budget_version_lines` — one row per GL account, `month_1` … `month_12`, `annual_total`

Extended legacy `budgets` table: `status`, `version_no`, `active_version_id` (additive migration).

## Workflow

| Step | Method | Status transition |
|------|--------|---------------------|
| Create | `createVersion()` | `draft` |
| Submit | `submit()` | `submitted` |
| Approve | `approve()` | `approved` |
| Activate | `activate()` | `active` (supersedes other active same year) |

`AdvancedBudgetController@revise` clones lines into new draft version (UI workflow).

## Budget vs actual

`budgetVsActual($version, $month, $costCenterId?)`:

- Budget from version line `month_{n}`
- Actual from **posted** `journal_entries` filtered by account, `year`, `month`, optional `cost_center_id`
- Expense accounts: actual = debits − credits; income: credits − debits
- Favorable flag differs by account type

**Tested:** 1000 budget vs 250 actual expense, favorable (`AccountingPhase3CoreTest`).

## Gaps vs brief

| Item | Status |
|------|--------|
| Quarterly/yearly compare views | **NOT IMPLEMENTED** (monthly only) |
| Budget notes/attachments | Notes column only; attachments **NOT IMPLEMENTED** |
| Copy budget | **IMPLEMENTED BUT PARTIALLY VERIFIED** via revise |
| Department/project budget reports as PDF | **NOT IMPLEMENTED** |

## Legacy budget UI

Phase 2 `accounting/budget` and overview remain; Phase 3 adds versioned path under `accounting/budget-versions`.