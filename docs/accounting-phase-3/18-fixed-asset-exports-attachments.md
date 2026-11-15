# Fixed asset PDF/Excel exports and attachments

## Reports (`FixedAssetReportService`)

Register, depreciation schedule, event history, disposal summary (array payloads for views).

## Exports

`FixedAssetExportController` — PDF (DomPDF), XLSX/XLS/CSV via `AccountingExport` + Blade under `phase3/reports/`.

Spreadsheet formula injection: cells starting with `=`, `+`, `-`, `@` prefixed with `'`.

## Attachments

`FixedAssetAttachmentService` — private disk `local`, path `fa-attachments/{asset_id}/`, max 10MB, allowed mime (pdf, images, office). UI on fixed-asset show page.