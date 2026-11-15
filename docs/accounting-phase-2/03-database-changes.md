# Phase 2 — Database Changes

**Migration:** `database/migrations/2026_11_15_140000_create_accounting_phase2_tables.php`  
**Type:** Additive only (expand). No drops of historical `journal_entries`.

## New tables

| Table | Purpose |
|-------|---------|
| `accounting_periods` | Fiscal periods open/closed |
| `accounting_manual_journals` | Multi-line journal headers + workflow |
| `accounting_manual_journal_lines` | Debit/credit lines |
| `bank_statement_imports` | CSV import batches |
| `bank_statement_lines` | Statement rows + fingerprint dedupe |
| `accounting_recurring_templates` | Recurring schedules |
| `accounting_recurring_runs` | Idempotent occurrence log (`template_id`+`occurrence_date` unique) |
| `accounting_audit_events` | Accounting audit trail |
| `customer_payment_allocations` | AR payment→invoice allocation |
| `supplier_payment_allocations` | AP payment→bill allocation |

## Altered columns

| Table | Column | Notes |
|-------|--------|-------|
| `chart_of_accounts` | `normal_balance` | nullable `debit`/`credit` |

## Rollback

`down()` drops Phase 2 tables and `normal_balance` only. Does not touch journal history.
