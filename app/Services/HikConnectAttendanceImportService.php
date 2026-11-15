<?php

namespace App\Services;

use App\Models\AttendanceDaily;
use App\Models\AttendanceImport;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class HikConnectAttendanceImportService
{
    public const STATUS_NEW = 'new';
    public const STATUS_UPDATE = 'update';
    public const STATUS_UNCHANGED = 'unchanged';
    public const STATUS_UNMATCHED = 'unmatched';
    public const STATUS_CONFLICT = 'conflict';
    public const STATUS_INVALID = 'invalid';

    public function __construct(
        protected HikConnectAttendanceImportParser $parser
    ) {}

    /**
     * @return array{rows: list<array<string, mixed>>, header_row: int, sheet: string, errors: list<string>}
     */
    public function preview(string $path): array
    {
        $parsed = $this->parser->parse($path);
        $rows = $this->classifyAll($parsed['rows']);

        return [
            'rows' => $rows,
            'header_row' => $parsed['header_row'],
            'sheet' => $parsed['sheet'],
            'errors' => $parsed['errors'],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{created: int, updated: int, unchanged: int, skipped: int, failed: int, errors: list<string>, import_id: int|null}
     */
    public function confirm(array $rows, ?int $userId, string $filename): array
    {
        $classified = $this->classifyAll($rows);
        $created = $updated = $unchanged = $skipped = $failed = 0;
        $errors = [];

        DB::transaction(function () use ($classified, $userId, $filename, &$created, &$updated, &$unchanged, &$skipped, &$failed, &$errors) {
            foreach ($classified as $row) {
                $excelRow = (int) ($row['excel_row'] ?? 0);
                $status = $row['status'] ?? self::STATUS_INVALID;

                if ($status === self::STATUS_UNCHANGED) {
                    $unchanged++;
                    continue;
                }
                if ($status === self::STATUS_UNMATCHED) {
                    $skipped++;
                    $errors[] = 'Row ' . $excelRow . ': ' . ($row['message'] ?? 'Unmatched Hikvision ID');
                    continue;
                }
                if (in_array($status, [self::STATUS_INVALID, self::STATUS_CONFLICT], true)) {
                    $failed++;
                    $errors[] = 'Row ' . $excelRow . ': ' . ($row['message'] ?? $status);
                    continue;
                }

                try {
                    if ($status === self::STATUS_NEW) {
                        $this->createRecord($row);
                        $created++;
                    } elseif ($status === self::STATUS_UPDATE) {
                        $daily = AttendanceDaily::query()->find($row['attendance_id'] ?? 0);
                        if (! $daily || $this->isProtected($daily)) {
                            $failed++;
                            $errors[] = 'Row ' . $excelRow . ': Record is locked or manually corrected.';
                            continue;
                        }
                        $this->updateRecord($daily, $row);
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    $errors[] = 'Row ' . $excelRow . ': ' . $e->getMessage();
                }
            }

            $import = AttendanceImport::create([
                'user_id' => $userId,
                'filename' => $filename,
                'source' => Employee::HIK_SOURCE_HIK_CONNECT,
                'created_count' => $created,
                'updated_count' => $updated,
                'unchanged_count' => $unchanged,
                'skipped_count' => $skipped,
                'failed_count' => $failed,
            ]);
            $this->lastImportId = $import->id;
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'unchanged' => $unchanged,
            'skipped' => $skipped,
            'failed' => $failed,
            'errors' => $errors,
            'import_id' => $this->lastImportId,
        ];
    }

    protected ?int $lastImportId = null;

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public function classifyAll(array $rows): array
    {
        $classified = [];
        foreach ($rows as $row) {
            $classified[] = $this->classifyRow($row);
        }

        $groups = [];
        foreach ($classified as $i => $row) {
            if (! in_array($row['status'], [self::STATUS_NEW, self::STATUS_UPDATE, self::STATUS_UNCHANGED], true)) {
                continue;
            }
            $key = ($row['employee_id'] ?? '') . '|' . ($row['date'] ?? '') . '|' . ($row['session_key'] ?? '');
            $groups[$key][] = $i;
        }
        foreach ($groups as $indexes) {
            if (count($indexes) < 2) {
                continue;
            }
            $signatures = [];
            foreach ($indexes as $i) {
                $signatures[] = $this->signature($classified[$i]);
            }
            $unique = array_unique($signatures);
            if (count($unique) > 1) {
                foreach ($indexes as $i) {
                    $classified[$i]['status'] = self::STATUS_CONFLICT;
                    $classified[$i]['message'] = 'Ambiguous: more than one different session row for this employee, date and timetable.';
                    $classified[$i]['attendance_id'] = null;
                }
            } else {
                $first = true;
                foreach ($indexes as $i) {
                    if ($first) {
                        $first = false;
                        continue;
                    }
                    $classified[$i]['status'] = self::STATUS_UNCHANGED;
                    $classified[$i]['message'] = 'Duplicate identical row in this file.';
                }
            }
        }

        return $classified;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function classifyRow(array $row): array
    {
        $row['status'] = self::STATUS_NEW;
        $row['message'] = null;
        $row['employee_id'] = null;
        $row['employee_name'] = null;
        $row['attendance_id'] = null;
        $row['hrm_status'] = $this->mapHrmStatus($row);
        $row['session_key'] = $this->sessionKey($row);

        $personId = Employee::normalizeHikvisionPersonId($row['hikvision_person_id'] ?? null);
        $row['hikvision_person_id'] = $personId;

        if ($personId === null) {
            $row['status'] = self::STATUS_INVALID;
            $row['message'] = 'Missing Hikvision ID.';

            return $row;
        }
        if (empty($row['date'])) {
            $row['status'] = self::STATUS_INVALID;
            $row['message'] = 'Missing or invalid attendance date.';

            return $row;
        }
        if (! empty($row['invalid_fields'])) {
            $row['status'] = self::STATUS_INVALID;
            $row['message'] = 'Invalid value in ' . implode(', ', $row['invalid_fields']) . '.';

            return $row;
        }

        $matches = Employee::query()
            ->where('hikvision_source', Employee::HIK_SOURCE_HIK_CONNECT)
            ->where('hikvision_person_id', $personId)
            ->get();

        if ($matches->count() > 1) {
            $row['status'] = self::STATUS_CONFLICT;
            $row['message'] = 'Ambiguous mapping: Hikvision ID ' . $personId . ' matches ' . $matches->count() . ' employees.';

            return $row;
        }
        if ($matches->isEmpty()) {
            $row['status'] = self::STATUS_UNMATCHED;
            $row['message'] = 'Unmatched Hikvision ID ' . $personId . '.';

            return $row;
        }

        $employee = $matches->first();
        $row['employee_id'] = $employee->id;
        $row['employee_name'] = $employee->display_name;

        $existing = AttendanceDaily::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', $row['date'])
            ->where('session_key', $row['session_key'])
            ->first();

        $protected = AttendanceDaily::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', $row['date'])
            ->withCount('adjustments')
            ->get()
            ->first(fn (AttendanceDaily $daily) => $this->isProtected($daily));

        if ($protected && (! $existing || $this->isProtected($existing) || (int) $protected->id !== (int) ($existing->id ?? 0))) {
            if ($existing && $this->samePayload($existing, $row)) {
                $row['status'] = self::STATUS_UNCHANGED;
                $row['attendance_id'] = $existing->id;
                $row['message'] = 'Identical to the saved record.';

                return $row;
            }
            $row['status'] = self::STATUS_CONFLICT;
            $row['attendance_id'] = $protected->id;
            $row['message'] = 'Manual correction or locked attendance for this employee and date was not overwritten.';

            return $row;
        }

        if (! $existing) {
            $row['status'] = self::STATUS_NEW;
            $row['message'] = 'New attendance row.';

            return $row;
        }

        $row['attendance_id'] = $existing->id;
        if ($this->samePayload($existing, $row)) {
            $row['status'] = self::STATUS_UNCHANGED;
            $row['message'] = 'Identical to the saved record.';

            return $row;
        }

        $row['status'] = self::STATUS_UPDATE;
        $row['message'] = $existing->first_in && ! $existing->last_out && ! empty($row['clock_out'])
            ? 'Will fill the missing checkout.'
            : 'Will update the imported attendance row.';

        return $row;
    }

    public function mapHrmStatus(array $row): string
    {
        $source = strtolower(trim((string) ($row['hikvision_status'] ?? '')));
        $hasIn = ! empty($row['clock_in']);
        $hasOut = ! empty($row['clock_out']);

        if (! $hasIn) {
            if ($source === 'leave' || str_contains($source, 'on leave')) {
                return 'leave';
            }

            return 'absent';
        }
        if ($hasIn && ! $hasOut) {
            return 'incomplete';
        }
        if (str_contains($source, 'absent') && ! str_contains($source, 'late')) {
            return 'absent';
        }
        if ($source === 'leave' || (str_contains($source, 'leave') && ! str_contains($source, 'early') && ! str_contains($source, 'late'))) {
            return 'leave';
        }
        if (str_contains($source, 'late')) {
            return 'late';
        }

        return 'present';
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function sessionKey(array $row): string
    {
        $timetable = strtolower(trim((string) ($row['timetable'] ?? '')));
        $start = ! empty($row['scheduled_start']) ? substr((string) $row['scheduled_start'], 0, 16) : '';
        $key = $timetable . '|' . $start;
        if ($key === '|') {
            $key = 'default';
        }

        return mb_substr($key, 0, 191);
    }

    protected function isProtected(AttendanceDaily $daily): bool
    {
        if ($daily->is_finalized || $daily->manually_corrected || $daily->source === 'manual') {
            return true;
        }
        if ($daily->relationLoaded('adjustments')) {
            return $daily->adjustments->isNotEmpty();
        }
        if (isset($daily->adjustments_count)) {
            return (int) $daily->adjustments_count > 0;
        }

        return $daily->adjustments()->exists();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function samePayload(AttendanceDaily $daily, array $row): bool
    {
        $left = [
            'first_in' => optional($daily->first_in)?->format('Y-m-d H:i:s'),
            'last_out' => optional($daily->last_out)?->format('Y-m-d H:i:s'),
            'scheduled_start' => optional($daily->scheduled_start)?->format('Y-m-d H:i:s'),
            'scheduled_end' => optional($daily->scheduled_end)?->format('Y-m-d H:i:s'),
            'worked_minutes' => $daily->worked_minutes,
            'late_minutes' => $daily->late_minutes,
            'overtime_minutes' => $daily->overtime_minutes,
            'absent_minutes' => $daily->absent_minutes,
            'early_leave_minutes' => $daily->early_leave_minutes,
            'break_minutes' => $daily->break_minutes,
            'leave_minutes' => $daily->leave_minutes,
            'workday_overtime_minutes' => $daily->workday_overtime_minutes,
            'weekend_overtime_minutes' => $daily->weekend_overtime_minutes,
            'hikvision_status' => $daily->hikvision_status,
            'timetable' => $daily->timetable,
            'clock_in_source' => $daily->clock_in_source,
            'clock_out_source' => $daily->clock_out_source,
            'status' => $daily->status,
        ];
        $right = $this->payload($row);
        foreach ($left as $key => $value) {
            $other = $right[$key] ?? null;
            if ($value === null && $other === null) {
                continue;
            }
            if ((string) $value !== (string) $other) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function signature(array $row): string
    {
        return md5(json_encode($this->payload($row)));
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function payload(array $row): array
    {
        return [
            'first_in' => $row['clock_in'] ?? null,
            'last_out' => $row['clock_out'] ?? null,
            'scheduled_start' => $row['scheduled_start'] ?? null,
            'scheduled_end' => $row['scheduled_end'] ?? null,
            'worked_minutes' => $row['worked_minutes'] ?? null,
            'late_minutes' => $row['late_minutes'] ?? null,
            'overtime_minutes' => $row['overtime_minutes'] ?? null,
            'absent_minutes' => $row['absent_minutes'] ?? null,
            'early_leave_minutes' => $row['early_leave_minutes'] ?? null,
            'break_minutes' => $row['break_minutes'] ?? null,
            'leave_minutes' => $row['leave_minutes'] ?? null,
            'workday_overtime_minutes' => $row['workday_overtime_minutes'] ?? null,
            'weekend_overtime_minutes' => $row['weekend_overtime_minutes'] ?? null,
            'hikvision_status' => $row['hikvision_status'] ?? null,
            'timetable' => $row['timetable'] ?? null,
            'clock_in_source' => $row['clock_in_source'] ?? null,
            'clock_out_source' => $row['clock_out_source'] ?? null,
            'status' => $row['hrm_status'] ?? $this->mapHrmStatus($row),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function createRecord(array $row): AttendanceDaily
    {
        return AttendanceDaily::create(array_merge($this->payload($row), [
            'employee_id' => $row['employee_id'],
            'date' => $row['date'],
            'session_key' => $row['session_key'],
            'source' => Employee::HIK_SOURCE_HIK_CONNECT,
            'manually_corrected' => false,
            'is_finalized' => false,
        ]));
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function updateRecord(AttendanceDaily $daily, array $row): void
    {
        $daily->update(array_merge($this->payload($row), [
            'session_key' => $row['session_key'],
            'source' => Employee::HIK_SOURCE_HIK_CONNECT,
        ]));
    }
}
