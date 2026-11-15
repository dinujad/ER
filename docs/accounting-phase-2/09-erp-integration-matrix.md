# Phase 2 — ERP Integration Matrix

| Module | Source of truth | GL path | Phase 2 impact |
|--------|-----------------|---------|----------------|
| POS / Sales | Orders | `SalesAccountingService` / observers → `postOnce` | Unchanged; period lock applies |
| Customer payments | Payments | Settlement + AR | Ledger/allocation read models |
| Sale returns | SaleReturn | Existing reverse/post | Unchanged |
| Purchases | Purchases | PurchaseObserver | Unchanged |
| Supplier payments | PurchasePayment | PurchasePaymentObserver | Allocation foundation |
| Expenses | Expense | ExpenseObserver | Unchanged |
| Inventory | Stock moves | Inventory observers | Unchanged |
| Manufacturing | Existing | Existing | Unchanged |
| Payroll | PayrollRun | Payroll observers | Unchanged |
| Banking / cheques | Existing | Settlement accounts | Statement import additive |
| Manual journals | New headers | `postMultiLine` | New |
| Transfers | AccountingController | Existing | Preserved |

**Principle:** No duplicate revenue/COGS/AR/AP postings from Phase 2 screens.
