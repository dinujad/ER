# Phase 4.1 — Tax Management

## BUG-008

`postAdjustment` throws `Tax period not found.` when `acct_tax_periods` row missing — before GL post.

## BUG-013

`TaxManagementController::summary` loads period and calls `assertBusinessScope` on `period.business_id`.