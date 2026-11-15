# Phase 2.1 — Bug Register

| Bug ID | Module | Severity | Root Cause | Fix | Status |
|--------|--------|----------|------------|-----|--------|
| P21-001 | Dashboard / COA schema | P0 | Local `chart_of_accounts` missing `name` (stub schema) | Repair migration + defensive selects | FIXED |
| P21-002 | Chart of Accounts | P1 | No cycle/self-parent/cross-business parent checks on store/update | `ChartOfAccountHierarchyService` + controller | FIXED |
| P21-003 | AR reconciliation | P1 | Helpers ignored bounced cheques / drafts; no contributing detail | Enhanced `StatementPdfService::arControlReconciliation` | FIXED |
| P21-004 | AP reconciliation | P1 | Drafts included; thin output | Enhanced `apControlReconciliation` | FIXED |
| P21-005 | Allocations | P2 | Unallocated/advance not surfaced; bounced payments allocatable | Unallocated helpers + bounced/cancelled guards + UI | FIXED |
| P21-006 | Audit service | P2 | Crash when `accounting_audit_events` missing in tests | Schema guard in `AccountingAuditService` | FIXED |
| P21-007 | Sale return COGS | P1 (verification) | Previously PARTIAL — now regression asserts cost≠sell price | `AccountingPhase21StabilityTest` | VERIFIED |
| P21-008 | Inventory vs GL | P1 (verification) | Previously PARTIAL — diagnostic service + test | `InventoryGlReconciliationService` | VERIFIED (supported schema) |
| P21-009 | Cheque lifecycle | P1 (verification) | Previously PARTIAL Eloquent E2E | Stability test received→deposit→clear + duplicate clear reject | VERIFIED |
| P21-010 | Period lock | P1 | Ensure `postOnce` path blocked | Stability test closed period | VERIFIED |
| P21-011 | draft_notify email | P3 | Not wired | Deferred | OPEN |
| P21-012 | Production diagnose | Ops | No prod access this phase | Checklist only | NOT VERIFIED |

**Open P0/P1 after fixes:** none in development test scope.
