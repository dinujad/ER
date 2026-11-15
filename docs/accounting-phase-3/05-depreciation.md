# Phase 3 — Depreciation

**Status:** FULLY IMPLEMENTED & TESTED (core engine)  
**Service:** `app/Services/Accounting/DepreciationEngine.php`  
**UI:** `modules/Accounting/Resources/views/phase3/depreciation/*`

## Methods

| Method key | Behavior |
|------------|----------|
| `straight_line` | `(cost − salvage) / life`; last period takes remainder |
| `declining_balance` | Double-declining monthly rate `2/years/12` on NBV, capped by salvage |
| `none` | No schedule lines |

Category default on `fa_asset_categories.default_depreciation_method`; asset-level override.

## Schedule generation

`generateSchedule()`:

- Requires `in_service_date`
- One row per month in `fa_depreciation_schedules` with `scheduled_amount`, `accumulated_after`, `nbv_after`
- Stops when NBV ≤ salvage

## Monthly run

`runPeriod($businessId, $year, $month, $runDate)`:

- Selects `scheduled` lines for active assets
- Posts per asset: Debit dep expense / Credit accum dep (`postOnce` per asset-period)
- Updates asset accum/NBV; may set `fully_depreciated`
- Creates `fa_depreciation_runs` with unique `run_key` — **second call returns same run** (tested)

## Preview

`previewPeriod()` — totals and line list without posting (`DepreciationController@preview`).

## Reversal

`reverseRun()`:

- `AccountingService::reverseEvent()` per schedule line
- Resets schedules to `scheduled`, restores asset accum/NBV
- Marks run `reversed`; mutates `run_key` with `:rev:{id}` so period can be re-run

**Tested:** reverse then re-run restores 1000 dep (`AccountingPhase3CoreTest`).

## Disposal integration

`AssetDisposalService` uses accumulated depreciation at disposal; gain/loss = proceeds − NBV.

## Special cases

| Case | Handling |
|------|----------|
| Partial-year / mid-month | Schedule starts at in-service month (calendar months, not day-proration) |
| Zero salvage | Supported |
| Fully depreciated | Status flip on run |
| Tax depreciation | **Not modeled** — book only |
| Closed period on dep run | **IMPLEMENTED BUT PARTIALLY VERIFIED** — relies on global period lock in `AccountingService`; no dedicated dep-run test |

## UI routes

- `accounting/depreciation/runs` — post + history
- `accounting/depreciation/preview`
- `accounting/depreciation/assets/{assetId}/schedule`