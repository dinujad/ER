# Phase 2 — Accounting Architecture

## Single ledger

All financial postings continue to land in `journal_entries` (+ idempotency in `accounting_postings`). Phase 2 does **not** create a second GL.

## Layers

1. **ERP observers / services** → `AccountingService::postOnce` / `postEntry` (unchanged contract).
2. **Manual multi-line workflow** → `ManualJournalService` → `AccountingService::postMultiLine`.
3. **Controls** → `AccountingPeriodService::assertOpenForDate` (called from post APIs).
4. **Read models** → Account register, customer/supplier ledgers, bank statement lines (non-posting).
5. **Audit** → `AccountingAuditService` → `accounting_audit_events`.

## Money

- `normalizeMoney` / `round(..., 2)` — no float-unsafe aggregation in registers.
- Currency display: LKR only (`config/accounting.php`).

## Isolation

Tables carry `business_id` (default 0 for current single-business deployments). Period and journal queries filter by business when set.

## Approval

- Config: `accounting.manual_journal_skip_approval` (env `ACCOUNTING_MANUAL_JOURNAL_SKIP_APPROVAL`).
- Default: Draft → Submitted → Approved → Posted.
- ERP auto-postings never enter this workflow.
