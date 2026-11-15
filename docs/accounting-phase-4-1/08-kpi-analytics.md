# Phase 4.1 — KPI Analytics

## BUG-014

`FinancialKpiAnalyticsService::analyze` adds `loan_outstanding` from `acct_loans.outstanding_principal` (active/disbursed) or GL loans payable — included in current/quick ratio denominators, aligned with `FinancialDashboardService` formula note.