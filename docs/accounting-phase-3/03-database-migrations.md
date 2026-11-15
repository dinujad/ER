# Phase 3 — Database Migrations

## Primary migration

**File:** `database/migrations/2026_11_15_200000_create_accounting_phase3_tables.php`  
**Type:** Additive only (create-if-not-exists patterns)

### Execution order in `up()`

1. `seedPhase3GlAccounts()` — CoA rows 1400, 1450, 1500, 2200, 2210, 4500, 6400, 6410, 6500  
2. `createFixedAssetTables()`  
3. `createLoanTables()`  
4. `createCostCenterTables()`  
5. `createAdvancedBudgetTables()`  
6. `extendJournalAndBudgetTables()` — `journal_entries.cost_center_id`, `journal_entries.job_id`; `budgets.status`, `version_no`, `active_version_id`  
7. `seedAssetCategories()` — COMPUTERS, FURNITURE, MACHINERY, VEHICLES, OFFICE, BUILDINGS, OTHER

### Tables created

| Table | Purpose |
|-------|---------|
| `fa_asset_categories` | Category master + per-category GL account IDs |
| `fa_assets` | Asset register |
| `fa_asset_events` | Status/audit timeline (JSON before/after) |
| `fa_depreciation_schedules` | Per asset/month schedule lines |
| `fa_depreciation_runs` | Posted monthly run header |
| `fa_disposals` | Disposal transactions |
| `acct_loans` | Loan register |
| `acct_loan_schedule_lines` | Amortization schedule |
| `acct_loan_payments` | Posted payments (`event_key` unique) |
| `acct_cost_centers` | Cost center hierarchy |
| `acct_cost_allocations` | Overhead allocation header |
| `acct_cost_allocation_lines` | Split lines |
| `acct_budget_versions` | Budget version header |
| `acct_budget_version_lines` | Monthly budget by GL account |

### Key columns (fixed assets)

- Monetary: `decimal(20,4)` on costs, NBV, accumulated depreciation  
- Workflow: `status` (draft → submitted → approved → active → fully_depreciated → disposed / inactive)  
- Integration stubs: `supplier_id`, `purchase_reference`, `location_id`, `serial_number`, `warranty_info`  
- Posting: `capitalization_txn`, `capitalized_at`

### Indexes and constraints

- Unique: `(business_id, asset_code)`, `(business_id, code)` on categories and cost centers  
- Unique depreciation period per asset: `(asset_id, period_year, period_month)`  
- Unique loan schedule line: `(loan_id, line_no)`  
- Unique loan number, depreciation `run_key`, payment `event_key`

### `down()`

Drops Phase 3 tables in reverse dependency order; removes added columns from `journal_entries` and `budgets` if present.

### CoA seeder alignment

`database/seeders/ChartOfAccountsSeeder.php` was modified on this branch to align Phase 3 GL codes (verify on deploy).

### Test schema

`AccountingPhase3CoreTest` builds a minimal SQLite subset of these tables inline (does not run the real migration file). Production/staging must run `php artisan migrate`.

### MySQL verification

**NOT VERIFIED** — see `15-remaining-issues.md`.