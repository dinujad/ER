# 08 — Data integrity report (Phase 1 final)

## Policy

Read-only diagnostics only. No automatic rewrite of historical production journals.

## Tool

```bash
php artisan accounting:diagnose
php artisan accounting:diagnose --json
```

## Isolated verification result

`AccountingPhase1VerificationTest` ran diagnose after a multi-module journal scenario:

- Unbalanced transaction groups: **0**  
- Duplicate active event keys: **0**  
- Books DR = CR: **pass**  
- Trial Balance DR = CR: **pass**  
- Assets = Liabilities + Equity + Net Income: **pass**  

## Production

Run diagnose on a **backup/copy** of production after deploy. Do not mass-repost without approval.

## Remaining integrity risks (documented, not auto-fixed)

| Risk | Severity | Notes |
|------|----------|-------|
| Historical float drift before `normalizeMoney` | P2 | New posts rounded; old rows unchanged |
| `location_id` null on ERP journals | P2 | Location-filtered reports may hide rows |
| Dual report controllers | P2 | Phase 2 unify |
| COA business_id 0 vs session 1 | P2 | Joins allow both |
