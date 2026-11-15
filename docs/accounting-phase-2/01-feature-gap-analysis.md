# Phase 2 — Feature Gap Analysis

**Branch:** `feature/accounting-phase-2`  
**Currency:** LKR only  
**Rule:** Never recreate working Phase 1 features; classify then enhance.

| Feature | Classification | Notes |
|---------|----------------|-------|
| Chart of Accounts (codes, types, active, parent_id, opening_balance) | ALREADY IMPLEMENTED | Preserve; added `normal_balance`, delete guards, register link |
| Hierarchical parent-child | PARTIALLY IMPLEMENTED | `parent_id` existed; added `children()` + delete-with-children block |
| Journal pair posting (`AccountingService::postOnce`) | ALREADY IMPLEMENTED | Preserved; period lock + `postMultiLine` added |
| Legacy 1-debit/1-credit manual UI (`journal_entry`) | ALREADY IMPLEMENTED | Preserved |
| Multi-line journals + Draft→Approve→Post | MISSING → IMPLEMENTED | `accounting_manual_journals` + workflow |
| Account register (running balance) | MISSING → IMPLEMENTED | `AccountRegisterService` + UI/CSV |
| Banking reconcile UI | ALREADY IMPLEMENTED | Preserved at `/accounting/reconcile` |
| Bank statement CSV import | MISSING → IMPLEMENTED | Import only; no auto GL |
| Bank matching suggestions | PARTIALLY / LIMITED | Schema `match_status` ready; full matcher deferred |
| Customer/Supplier ledgers | MISSING → IMPLEMENTED | Read from orders/purchases/payments |
| Payment allocations | MISSING → FOUNDATION | Tables + `PaymentAllocationService` |
| AR/AP ageing reports | ALREADY IMPLEMENTED | Linked from workspace |
| Recurring journals | MISSING → IMPLEMENTED | Templates + idempotent runs + schedule |
| Accounting periods / lock | MISSING → IMPLEMENTED | Closed periods block `postEntry`/`postMultiLine` |
| Audit trail | PARTIALLY (activity log) → ENHANCED | `accounting_audit_events` |
| Accounting Workspace sidebar | ALREADY (1.5) → ENHANCED | Phase 2 nav items |
| Multi-currency | OUT OF SCOPE | LKR only |

## ERP auto-journals

POS/Sales/Purchase/Expense/Payroll/Inventory observers remain authoritative. Manual multi-line journals are separate; they use the same `journal_entries` ledger via `AccountingService`.
