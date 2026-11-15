# Phase 2 — Accounts Receivable

## Existing

- Sales/POS/orders/payments remain source documents.
- AR ageing reports under `/report/accounting/accounts_receivable_*`.
- GL AR control via `AccountingService::GL_ACCOUNTS_RECV` + observers.

## New / enhanced

- **Customer Ledger** UI: invoices, payments, outstanding (LKR).
- **Payment allocation** foundation: `customer_payment_allocations` + `PaymentAllocationService::allocateCustomerPayment` (over-allocation blocked).
- Workspace link + ageing redirect.

## Limitations

- Statement PDF/Excel for customers: not a dedicated DomPDF yet (ledger screen + ageing export path).
- Full unapplied credit / advance UI: service-level rules started; dedicated screens partial.
- Control-account automated reconciliation report: deferred to follow-up.

## Classification

Customer ledger: **IMPLEMENTED BUT PARTIALLY VERIFIED**  
Allocations API: **IMPLEMENTED BUT PARTIALLY VERIFIED** (unit path; limited HTTP UI)  
Ageing: **ALREADY IMPLEMENTED** (Phase 1)
