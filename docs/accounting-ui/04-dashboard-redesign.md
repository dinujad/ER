# Dashboard Redesign

View: `dashboard/index.blade.php`  
KPIs from `FinancialDashboardService` / workspace metrics (real posted journals — no demo amounts).

Concept layout (E-Media premium UI):
- Greeting header + date range filters + Refresh / P&L export links
- Hero KPI row: Revenue, Expenses, Net Profit, Available Cash (cash+bank)
- Full secondary KPI row preserved: Cash, Bank, AR, AP, Catalogue, Gross Profit, Working Capital, Current Ratio, Loans, Inventory, FA NBV
- Income & expenses Chart.js line chart (trusted monthly series)
- Cash position panel with drill to Cash Flow
- Recent journal activity table
- Quick actions (all existing routes kept + Saved reports)
- Reports panel (Phase 4 statements + classic report routes)

Nothing removed from controllers/services; UI restyle only.
