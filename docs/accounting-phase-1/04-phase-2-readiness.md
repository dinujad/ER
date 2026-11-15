# 04 — Phase 2 readiness (do not implement now)

## Already present (enhance later)

- Balanced pair posting (`postOnce`) + idempotent event keys  
- Chart of accounts + manual journal UI  
- Bank reconcile screens  
- Budget monthly/quarterly/yearly  
- Transfers between accounts  
- AR/AP ageing report endpoints  
- ERP Transactions tabs (sales / purchases / expenses / inventory)  
- Backfill + diagnose commands  

## Needs enhancement (Phase 2+)

| Capability | Current state |
|------------|---------------|
| Multi-line journals beyond DR/CR pair | Pair-only engine; redesign deferred |
| Dedicated Payroll Payable naming / tax split | Uses GL 2100 “Other Current Liabilities” |
| Bank reconciliation matching to ERP payments | UI exists; deeper matching later |
| AR/AP subledgers | Ageing reports; not full open-item management |
| Recurring transactions | Absent |
| Fixed assets / depreciation | Absent |
| Cost centers | Absent |
| Forecasting / AI / OCR | Absent |
| Approval workflows for journals | Absent |
| Tax management (VAT/SSCL books) | Invoice tax UI exists; full tax ledger later |
| Multi-currency | **Out of scope forever for this product (LKR only)** |

## Architectural notes for Phase 2

1. Keep `accounting_postings.event_key` as the idempotency spine.  
2. Prefer reverse + re-post over mutating historical JE amounts.  
3. Unify dual report controllers (`AccountingController` vs `ReportController`).  
4. Clarify dual `AccountingService` classes (app vs module).  
5. Expand Transactions UI filters and drill-down to source ERP screens.
