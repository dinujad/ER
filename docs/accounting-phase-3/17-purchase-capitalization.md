# Purchase-line auto-capitalization

**Branch:** `feature/accounting-phase-3`  
**Migration:** `database/migrations/2026_11_15_210000_phase3_completion_tables.php`

## Flow

1. Confirmed purchase with `is_fixed_asset` lines posts **one** balanced `postMultiLine` journal: Dr RM (inventory lines), Dr **1410 FA Clearing** (asset lines), Cr AP (total).
2. `PurchaseAssetCapitalizationService::syncCandidatesFromPurchase` upserts `fa_capitalization_candidates` per fixed-asset line (`pending_review`).
3. Approve → Capitalize: Dr **1400 FA** Cr **1410 Clearing** (no duplicate AP).

## GL

| Code | Constant | Role |
|------|----------|------|
| 1410 | `AccountingService::GL_FA_CLEARING` | Clearing on PO confirm |
| 1400 | `GL_FIXED_ASSETS` | Capitalize from clearing |

## UI

`accounting/purchase-capitalization` — workspace sidebar **Purchase-linked Assets**.