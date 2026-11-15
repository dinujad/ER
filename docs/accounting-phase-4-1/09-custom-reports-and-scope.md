# Phase 4.1 — Custom Reports & Business Scope

## BUG-012

`CustomReportBuilderService::delete($id, $businessId)` and `update(..., $businessId)` verify row `business_id`.

## BUG-013

`RequiresAccountingPermission` adds `sessionBusinessId()` and `assertBusinessScope($resourceBusinessId)` (session `business.id` or `Auth::user()->business_id`).

Applied: `YearEndClosingController::show/execute`, `TaxManagementController::summary`, `CustomReportController::destroy`.