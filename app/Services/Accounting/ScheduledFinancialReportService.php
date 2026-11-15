<?php

namespace App\Services\Accounting;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ScheduledFinancialReportService
{
    public static function processDue(?string $asOf = null): int
    {
        if (! Schema::hasTable('acct_report_schedules')) {
            return 0;
        }

        $now = $asOf ? strtotime($asOf) : time();
        $due = DB::table('acct_report_schedules')
            ->where('status', 'active')
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', date('Y-m-d H:i:s', $now))
            ->get();

        $count = 0;
        foreach ($due as $schedule) {
            self::executeSchedule((int) $schedule->id, date('Y-m-d', $now));
            $count++;
        }

        return $count;
    }

    public static function executeSchedule(int $scheduleId, string $runDate): int
    {
        $schedule = DB::table('acct_report_schedules')->where('id', $scheduleId)->first();
        if (! $schedule) {
            throw new RuntimeException('Schedule not found.');
        }

        $eventKey = 'acct-report-schedule:'.$scheduleId.':'.$runDate;
        $existing = Schema::hasTable('acct_report_executions')
            ? DB::table('acct_report_executions')->where('event_key', $eventKey)->first()
            : null;
        if ($existing && $existing->status === 'success') {
            return (int) $existing->id;
        }

        $config = json_decode($schedule->config ?? '{}', true) ?: [];
        $report = CustomReportBuilderService::generate([
            'report_type' => $schedule->report_type,
            'config' => $config,
        ]);

        $format = $config['format'] ?? 'csv';
        $relativePath = 'acct-reports/schedule-'.$scheduleId.'-'.$runDate.'.'.$format;
        $content = self::serializeReport($report, $format);
        Storage::disk('local')->put($relativePath, $content);

        $executionId = 0;
        if (Schema::hasTable('acct_report_executions')) {
            if ($existing) {
                DB::table('acct_report_executions')->where('id', $existing->id)->update([
                    'status' => 'success',
                    'storage_disk' => 'local',
                    'storage_path' => $relativePath,
                    'format' => $format,
                    'finished_at' => now(),
                    'updated_at' => now(),
                ]);
                $executionId = (int) $existing->id;
            } else {
                $executionId = (int) DB::table('acct_report_executions')->insertGetId([
                    'schedule_id' => $scheduleId,
                    'report_type' => $schedule->report_type,
                    'status' => 'success',
                    'storage_disk' => 'local',
                    'storage_path' => $relativePath,
                    'format' => $format,
                    'started_at' => now(),
                    'finished_at' => now(),
                    'event_key' => $eventKey,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        self::advanceNextRun($schedule);

        return $executionId;
    }

    private static function serializeReport(array $report, string $format): string
    {
        if ($format === 'json') {
            return json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        }

        return json_encode($report, JSON_THROW_ON_ERROR);
    }

    private static function advanceNextRun(object $schedule): void
    {
        $next = match ($schedule->frequency) {
            'daily' => date('Y-m-d H:i:s', strtotime(($schedule->next_run_at ?? 'now').' +1 day')),
            'weekly' => date('Y-m-d H:i:s', strtotime(($schedule->next_run_at ?? 'now').' +1 week')),
            'monthly' => date('Y-m-d H:i:s', strtotime(($schedule->next_run_at ?? 'now').' +1 month')),
            'quarterly' => date('Y-m-d H:i:s', strtotime(($schedule->next_run_at ?? 'now').' +3 months')),
            'yearly' => date('Y-m-d H:i:s', strtotime(($schedule->next_run_at ?? 'now').' +1 year')),
            default => date('Y-m-d H:i:s', strtotime(($schedule->next_run_at ?? 'now').' +1 month')),
        };

        DB::table('acct_report_schedules')->where('id', $schedule->id)->update([
            'last_run_at' => now(),
            'next_run_at' => $next,
            'updated_at' => now(),
        ]);
    }
}