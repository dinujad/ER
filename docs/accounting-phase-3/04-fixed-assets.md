# Phase 3 — Fixed Assets

**Status:** IMPLEMENTED BUT PARTIALLY VERIFIED  
**Service:** `app/Services/Accounting/FixedAssetService.php`  
**UI:** `modules/Accounting/Resources/views/phase3/fixed_assets/*`

## Lifecycle

| Status | Meaning |
|--------|---------|
| `draft` | Editable; not yet capitalized |
| `submitted` | Awaiting approval (controller only) |
| `approved` | Ready to capitalize |
| `active` | Capitalized; depreciating |
| `fully_depreciated` | NBV at salvage |
| `disposed` | Disposal posted |
| `inactive` | Deactivated without disposal |

Workflow endpoints: `submit`, `approve`, `capitalize`, `transfer` (cost center only), `deactivate`.

## Register fields

Implemented in DB/model:

- Asset code auto: `FA-{YEAR}-{seq}` via `nextAssetCode()`
- Category, name, description, dates (purchase, acquisition, in-service)
- `asset_cost` + `capitalized_costs` → `totalCapitalizedCost()`
- Salvage, useful life months, depreciation method override
- `cost_center_id` on asset and capitalization journal meta
- `serial_number`, `warranty_info`, `location_id`, `supplier_id`, `purchase_reference` — **DB only**; create/edit Blade `_fields` does not expose purchase/supplier/serial/warranty
- Events in `fa_asset_events`; mirror audit via `AccountingAuditService`

## Capitalization

`FixedAssetService::capitalize()`:

- Debit category asset GL (fallback `GL_FIXED_ASSETS`)
- Credit AP default (`GL_ACCOUNTS_PAY`) or chosen credit GL
- `postOnce` key `fa:capitalize:{id}`
- Sets status `active`, stores `capitalization_txn`, triggers `DepreciationEngine::generateSchedule()`

**Tested:** balanced journal, idempotent retry (`AccountingPhase3CoreTest`).

## Categories

Seeded in migration + editable via `FixedAssetCategoryController` (`accounting/fixed-asset-categories`). Each category stores GL IDs for asset, accum dep, dep expense, gain, loss.

## Gaps vs brief

| Requirement | Status |
|-------------|--------|
| PDF/Excel asset register export | **NOT IMPLEMENTED** (no export routes) |
| Attachments | **NOT IMPLEMENTED** |
| Purchase workflow capitalization | **IMPLEMENTED BUT PARTIALLY VERIFIED** (columns only) |
| Asset Reports menu | **NOT IMPLEMENTED** (list/show/schedule only) |
| Branch / assigned employee | **NOT IMPLEMENTED** |

## Permissions

- View: `accounting.view`
- Create/capitalize/approve: `accounting.manage` via `RequiresAccountingPermission`