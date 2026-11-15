# 07 — Test results (Phase 1 final verification)

**Environment:** Local PHPUnit, sqlite `:memory:` (`phpunit.xml`). Not production.

## Command

```bash
php artisan test --filter="AccountingServiceTest|ActionPermissionsTest|AccountingPhase1VerificationTest"
```

## Result

| Suite | Tests | Result |
|-------|-------|--------|
| AccountingPhase1VerificationTest | 4 | PASS |
| AccountingServiceTest | 13 | PASS |
| ActionPermissionsTest | 9 | PASS |
| **Total** | **26** | **26 passed / 0 failed** (257 assertions) |

Duration ~7.6s.

## Verification coverage exercised

- Sale (completed) + COGS + mixed cash/bank payments + refund  
- Cheque receipt + clearance + bounce reverse  
- Sale return  
- Purchase + supplier payment + purchase return  
- Expense + salesperson expense  
- Payroll accrual + payment  
- Manufacturing material issue / additional cost / FG receipt  
- Cash→Bank transfer  
- Quotation/pending order does **not** post revenue  
- Duplicate `postOnce` does not double TB  
- P&L income/expense/net  
- TB DR=CR, BS Assets = Liabilities + Equity + Net Income  
- `accounting:diagnose` unbalanced/duplicate empty after scenario  
- clear-all 410, normalizeMoney  

## Untested / partial (honest)

- Full HTTP POS cart → register close UI  
- Live ReportController PDF/Excel export rendering  
- Production DB diagnose scan  
- Observer wiring with full Eloquent persist (tests call services/`postOnce` on sqlite stubs)  
- Concurrent multi-process race beyond `lockForUpdate` design review  
