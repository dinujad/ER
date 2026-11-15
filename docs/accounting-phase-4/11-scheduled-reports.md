# Scheduled Financial Reports

Tables: `acct_report_schedules`, `acct_report_executions`.

`ScheduledFinancialReportService::executeSchedule()` writes `acct-reports/schedule-{id}-{date}.csv` on `local` disk; duplicate `event_key` returns existing execution.

**Artisan:** `accounting:process-scheduled-reports`  
**Schedule:** daily 01:30