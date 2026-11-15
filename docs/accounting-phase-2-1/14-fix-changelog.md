# Phase 2.1 Fix Changelog

| Change | Files |
|--------|-------|
| COA column repair migration | `database/migrations/2026_11_15_170000_repair_chart_of_accounts_missing_columns.php` |
| Defensive dashboard/ledger account name | `AccountingWorkspaceMetrics.php`, `AccountingTransactionController.php` |
| Hierarchy cycle validation | `ChartOfAccountHierarchyService.php`, `ChartOfAccountController.php` |
| AR/AP reconcile enrichment | `StatementPdfService.php` |
| Allocation unallocated + guards | `PaymentAllocationService.php`, allocation controller/view |
| Inventory GL diagnostic | `InventoryGlReconciliationService.php` |
| Audit table guard | `AccountingAuditService.php` |
| Stability tests | `AccountingPhase21StabilityTest.php` |
| Docs | `docs/accounting-phase-2-1/*` |
