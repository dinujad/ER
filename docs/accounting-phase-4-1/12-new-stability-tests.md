# Phase 4.1 — New Stability Tests

**Class:** `Tests\Feature\AccountingPhase41StabilityTest`

| Test | Covers |
|------|--------|
| `test_company_wide_pl_includes_cost_centered_income` | BUG-001/002 |
| `test_year_end_reopen_then_execute_closes_again` | BUG-003 |
| `test_execute_closing_blocked_when_unposted_manual_exists` | BUG-006 |
| `test_tax_post_adjustment_missing_period_throws` | BUG-008 |
| `test_custom_report_delete_scoped_by_business` | BUG-012 |
| `test_forecast_uses_remaining_when_amount_paid_present` | BUG-009 |