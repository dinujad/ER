# Phase 7 — Xero / QuickBooks workflows (2026-11-21)

Tests: `tests/Feature/AccountingPhase7Test.php` (9 tests). Full suite: 202 passing.
Migration: `2026_11_21_100000_create_reconciliation_payment_runs_close_tables.php`.

| Priority | Feature | Screen (`/accounting/phase7/...`) |
|---|---|---|
| P1 | Bank reconciliation workspace: saved column mapping per bank account, statement closing balance vs books with "explained by open items", queue of unmatched lines, one bank line ↔ many payments, net settlement → bank fee posted to 6050 Bank Charges, unmatch reverses the fee, books-not-on-statement list | `reconcile` |
| P1 | Supplier payment runs: tick bills (partial amounts allowed) → draft run → approve (optional four-eyes) → bank file (generic CSV / SLIPS CSV) → mark failed lines → confirm paid. One payment per bill, capped at the balance; a bill paid elsewhere meanwhile is marked failed, never paid twice; a bill can't be in two open runs. Supplier bank details added to the supplier form | `payment-runs`, `/accounting/phase6/bills/payment-run` |
| P1 | Month-end close: checks (bank reconciled, unposted documents, AR & AP sub-ledger vs GL, VAT/SSCL return vs GL, trial balance) → sign-off (exceptions need a note) → lock the period (blocks posting) → reopen with reason | `close` |
| P2 | Supplier invoice OCR: text from `pdftotext`, built-in PHP PDF reader, or `tesseract` (images); field parser for Sri Lankan invoices (invoice no., date day-first, supplier by VAT no./name, amount excl. VAT, VAT, total, consistency check, per-field confidence); paste-text fallback; draft bill created with the real purchase columns + duplicate supplier-invoice check | `/accounting/phase5/ocr/{id}` |
| P2 | Reminder schedule (days late steps, minimum gap, off by default) + daily command `accounting:send-payment-reminders`; delivery history (sent / failed / skipped, manual / automatic); recurring queue with failed-run retry and next-step links for generated drafts | `reminders`, `recurring-queue` |

## Bugs fixed on the way
- Bank statement dates `dd/mm/yyyy` were read as US `mm/dd/yyyy` (`strtotime`).
- A failed recurring run blocked its template permanently (next run found the failed row and stopped) → Retry.
- Recurring invoice drafts omitted `order_number` / `user_id` (required in DERMAS) → every recurring invoice failed.
- OCR "create draft" wrote non-existent purchase columns (`ref_no`, `purchase_date`, `final_total`).

## Not done
- Live bank feeds (no Sri Lankan provider confirmed) and customer payment links (needs a payment gateway).
- OCR of scanned PDFs needs the page converted to an image (or pasted text); handwriting is not supported.
