# Financial Dashboard

`FinancialDashboardService` extends `AccountingWorkspaceMetrics::summary()` without breaking keys (`revenue`, `expenses`, `net_profit`, `cash`, etc.).

Added KPIs: COGS, gross profit/margin, opex, net margin, inventory, FA NBV, loan outstanding, current ratio, working capital, monthly revenue vs expense chart (`meta.formulas` documents math).

**Routes:** `accounting/dashboard` (enhanced), `accounting/phase4/financial-dashboard`

Chart.js CDN on main workspace dashboard when chart data present.