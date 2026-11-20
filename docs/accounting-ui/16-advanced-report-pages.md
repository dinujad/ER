# Advanced Report Pages

QB-inspired report shell for Phase 4 statutory statements and related analytics (LKR only).

## Shell

- Component: `modules/Accounting/Resources/views/components/advanced_report.blade.php`
- CSS: `.acct-adv-*` in `_acct_design_system.blade.php`
- Features: period filters, summary KPI cards, Chart.js visuals, collapsible customize drawer, interactive grouped tables, account-register drill-down, Insights/Q&A links, print + PDF/Excel exports

## Pages upgraded

| Report | Route | Export |
|--------|-------|--------|
| Profit & Loss | `/accounting/phase4/statements/profit-and-loss` | `/pdf`, `/xlsx` |
| Balance Sheet | `/accounting/phase4/statements/balance-sheet` | `/pdf`, `/xlsx` |
| Cash Flow | `/accounting/phase4/statements/cash-flow` | `/pdf`, `/xlsx` |
| Changes in Equity | `/accounting/phase4/statements/changes-in-equity` | `/pdf`, `/xlsx` |
| Trial Balance | `/accounting/phase4/statements/trial-balance` | `/pdf`, `/xlsx` |
| KPI Analytics | `/accounting/phase4/kpi-analytics` | print |
| Tax summary | `/accounting/phase4/tax/periods/{id}/summary` | print |
| Cost Center P&L | `/accounting/cost-center-reports/profit-and-loss` | existing pdf/xlsx |

## Integrity

- Amounts come only from `FinancialStatementService` / posted journals — no fabricated figures.
- Cash/accrual toggle not enabled (single LKR accrual ledger).
- Classic `/report/accounting/*` pages remain available under **Classic Reports**.

## Tests

`AccountingUiPhase55Test`: advanced shell assert + PDF/XLSX export smoke.
