# Configurable manufacturing overhead

Tables: `mfg_overhead_rules`, `mfg_overhead_runs`, `mfg_overhead_run_lines`.

`ManufacturingOverheadAllocationService`:

- Bases: `labor_hours` (derived from **labour** additional costs when no hour column), `machine_hours`, `production_qty` (`totalOutputQty`), `labor_cost`.
- Preview → post: Dr WIP Cr overhead pool per job (`mfg:oh:{run_id}:{job_id}` idempotent).
- `ManufacturingOverheadService::simpleAverage` unchanged for legacy cost sheets.

UI: `accounting/manufacturing-overhead/*` (rules, allocation preview, history).