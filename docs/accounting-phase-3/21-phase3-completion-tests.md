# Phase 3 completion tests

**File:** `tests/Feature/AccountingPhase3CompletionTest.php`

| Test | Coverage |
|------|----------|
| Mixed purchase posting | RM + clearing + AP balanced multi-line |
| Capitalize from clearing | No extra AP credit |
| Duplicate capitalize | RuntimeException |
| Cost center P&amp;L | Income/expense math + unassigned |
| Overhead allocation | Production qty + duplicate posted run blocked |
| Attachment | Rejects exe mime (`Storage::fake`) |
| Export register | Totals match service arrays |

**Accounting filter (with Phase 3 completion):** `71 passed (219 assertions)` — see `16-final-phase-3-report.md`.