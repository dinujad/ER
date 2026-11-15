# Phase 2 — Banking & Reconciliation

## Existing (preserved)

- Bank/cash COA accounts, transfers, cheque settlement mapping, `/accounting/reconcile` workflow.

## New

- **Statement CSV import** (`BankStatementImportService`): date, description, reference, debit/credit or amount.
- Fingerprint-based **duplicate detection** across imports for same GL account.
- Import does **not** create journal entries.
- Workspace: Banking → Statement Imports; existing Reconciliation link retained.

## Partial / remaining

- Suggested matching UI (customer/supplier payments, transfers, cheques) — schema fields ready (`match_status`, `matched_source_*`); interactive matcher not fully built.
- Excel import: CSV path implemented; XLSX parser not added.
- Reconciliation difference finalization with authorized adjustment journal — relies on existing ReconcileController + new manual journals.

## Tests

Import + dedupe in `AccountingPhase2CoreTest`.
