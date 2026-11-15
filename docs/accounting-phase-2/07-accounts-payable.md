# Phase 2 — Accounts Payable

## Existing

- Purchases + purchase_payments + AP ageing reports.
- Purchase observers post to AP control GL.

## New / enhanced

- **Supplier Ledger** UI from purchases/payments.
- **Supplier payment allocations** table + `PaymentAllocationService::allocateSupplierPayment`.
- Workspace Payables section.

## Limitations

- Dedicated bill attachment / payment allocation UI screens are foundational.
- Supplier statement PDF: not dedicated yet.
- Cheque payment path remains existing ERP cheque module.

## Classification

Supplier ledger: **IMPLEMENTED BUT PARTIALLY VERIFIED**  
AP ageing: **ALREADY IMPLEMENTED**
