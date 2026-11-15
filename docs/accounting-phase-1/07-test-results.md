# 07 — Test results (Phase 1)

**Environment:** Local PHPUnit via `php artisan test` (not production).  
**Command:**

```bash
php artisan test --filter="AccountingServiceTest|ActionPermissionsTest"
```

## Result

**22 passed** (226 assertions). Duration ~14s. Exit code 0.

### AccountingServiceTest (13)

- postOnce idempotency / balanced pair  
- reverseEvent idempotent  
- completed order AR/Sales  
- cash payment  
- expense GL mapping  
- cheque settlement PDC vs Bank  
- refund cheque → Bank  
- collapseDuplicateJournals  
- PENDING recovery  
- reverseEntry marks postings  
- **NEW** normalizeMoney rounding  
- **NEW** reject zero/negative  
- **NEW** rounded amount on both JE sides + posting  

### ActionPermissionsTest (9)

- edit/delete independence  
- admin access  
- forbidden probes including clear-all  
- **NEW** clear-all returns **410** even with expenses.delete  
- portal delete guards  
- role grant restriction  

## Not claimed

Full suite (`composer test`) was not required for this Phase 1 slice; critical accounting + clear-all regressions above were executed successfully.
