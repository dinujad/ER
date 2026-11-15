# 10 — Accounting Workspace UI (Phase 1.5)

## Previous UI

- Main ERP sidebar linked to Accounting (already a direct link).
- Accounting pages `@extends('accounting::layouts.app')` which **extended `erp.layout`**, so the full ERP sidebar remained visible.
- Horizontal accounting nav (`layouts/nav.blade.php`) sat inside the ERP content area.

## New UI

- `accounting::layouts.app` is a **standalone Accounting Workspace** (own sidebar + header).
- ERP sidebar: Accounting → `/accounting/dashboard` (same tab) + “Open in new tab”.
- Workspace sidebar groups: Dashboard, Accounts, Transactions, Banking, Receivables, Payables, Reports, Settings.
- **Back to ERP** → `/erp/dashboard` (session preserved).
- `/accounting` redirects to `/accounting/dashboard`.

## Dashboard data

`App\Services\AccountingWorkspaceMetrics` reads **posted** `journal_entries` (non-reversed):

- Cash / Bank / AR / AP balances from GL codes via `AccountingService::accountId`
- Period revenue / expenses / net from journals
- Recent journal lines
- Shows “Not available” when COA/journals missing — **no hardcoded money**

## Routes preserved

Existing `/accounting/*` and `/report/accounting/*` controllers unchanged; only layout shell changed.

## Permissions

`accounting.session` + `accounting.access` unchanged. Sidebar Settings requires `accounting.manage`.

## Tests

```bash
php artisan test --filter="AccountingWorkspaceUiTest|AccountingServiceTest|AccountingPhase1VerificationTest|ActionPermissionsTest"
```

**31 passed / 0 failed** (273 assertions) — includes 5 new workspace UI tests.

## Known limitations

- Income vs expenses chart widget deferred (KPIs + recent journals shipped).
- Customer/supplier “statements” use existing AR/AP ageing reports (no new empty pages).
- Account Register as a separate page not invented — Chart of Accounts show remains the register.
- Phase 2 financial features not started.
