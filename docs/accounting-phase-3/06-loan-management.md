# Phase 3 — Loan Management

**Status:** FULLY IMPLEMENTED & TESTED (core flows)  
**Service:** `app/Services/Accounting/LoanAccountingService.php`  
**UI:** `modules/Accounting/Resources/views/phase3/loans/*`

## Register (`acct_loans`)

- Auto number `LN-{YEAR}-{seq}`
- Lender, type, principal, outstanding principal, annual rate, interest type, frequency, term
- GL links: bank, loans payable, interest expense, interest payable (defaults from CoA)
- Status: `draft` → `active` → `closed`
- `accrued_interest` column present; accrual via `accrueInterest()` posts Dr expense / Cr interest payable

## Schedules

| Builder | Algorithm |
|---------|-----------|
| `buildEqualInstallmentSchedule` | Standard amortizing payment |
| `buildEqualPrincipalSchedule` | Fixed principal + interest on balance (**tested** 12 lines) |
| `buildInterestOnlySchedule` | Interest each period; principal on final line |

Custom schedules beyond these builders: **NOT IMPLEMENTED**.

## Disbursement

`disburse()` — Dr Bank / Cr Loans Payable, `postOnce` `loan:disburse:{id}`. Auto-builds equal installment schedule if empty.

## Payments

`recordPayment()` — multi-line: Dr loan liability (+ Dr interest expense), Cr bank. Idempotent on `event_key` (**tested** duplicate payment).

`earlySettle()` — pays full outstanding + optional interest (**tested**).

Interest on payment books to **expense** directly, not interest payable (accrual path is separate).

## UI

- Register, create, show, payment form, statement view
- Actions: generate schedule, disburse, record payment

## Gaps vs brief

| Item | Status |
|------|--------|
| Supporting documents | **NOT IMPLEMENTED** |
| Interest expense report page | **NOT IMPLEMENTED** (data in JEs only) |
| Loan liability GL reconcile report | **NOT IMPLEMENTED** |
| Partial repayment | **IMPLEMENTED** (manual principal/interest amounts) |
| Accrual UI workflow | **IMPLEMENTED BUT PARTIALLY VERIFIED** (service only) |

## Permissions

`accounting.manage` required for create/disburse/pay.