# Phase 2 — Advanced Journal Implementation

## Existing behaviour (preserved)

- `JournalEntryController` pair-based manual entries.
- `AccountingService::postEntry` / `postOnce` for ERP events.

## New behaviour

- Multi-line balanced journals via `AccountingService::postMultiLine`.
- Workflow in `ManualJournalService`: draft → submit → approve/reject → post → reverse.
- Reversal posts opposite lines (`reverseTransaction`) then marks original `reversed=1`.
- Idempotency key `manual_journal:{id}` / `reversal:{txn}`.
- Posted journals are not editable; corrections via reverse/adjust.

## Key files

- `app/Services/AccountingService.php` (`postMultiLine`, `reverseTransaction`, period assert)
- `app/Services/Accounting/ManualJournalService.php`
- `modules/Accounting/Http/Controllers/ManualJournalController.php`
- Views under `modules/Accounting/Resources/views/phase2/manual_journals/`
- Routes: `/accounting/manual-journals/*`

## Tests

`AccountingPhase2CoreTest`: balanced, unbalanced reject, lifecycle, duplicate post, reversal.
