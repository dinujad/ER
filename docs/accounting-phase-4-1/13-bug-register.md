# Phase 4.1 — Bug Register

| ID | Sev | Status | Summary |
|----|-----|--------|---------|
| BUG-001 | P0 | FIXED | Statutory P&L omitted cost-centered journals |
| BUG-002 | P0 | FIXED | Year-end preview net ≠ execute (same root as 001) |
| BUG-003 | P0 | FIXED | Reopen then re-close returned stale txn |
| BUG-004 | P1 | FIXED | BS current_earnings used calendar YTD |
| BUG-005 | P1 | FIXED | Dashboard vs statements aligned via company-wide P&L |
| BUG-006 | P1 | FIXED | executeClosing ignored checklist ready |
| BUG-007 | P1 | FIXED | Closed FY with reversed posting now re-closeable |
| BUG-008 | P1 | FIXED | Tax adjustment NPE on missing period |
| BUG-009 | P1 | FIXED | Forecast used full invoice on partial pay |
| BUG-011 | P1 | MITIGATED | CF meta adds cash_gl_change + variance (heuristic retained) |
| BUG-012 | P1 | FIXED | Cross-business IDOR scoping on year-end/tax/reports |
| BUG-013 | P1 | FIXED | Custom report delete/update business scope |
| BUG-014 | P1 | FIXED | KPI current ratio includes loans |

P2 items (TB opening omission, forecast limit 50) documented as remaining limitations — not blocking DEVELOPMENT COMPLETE.
