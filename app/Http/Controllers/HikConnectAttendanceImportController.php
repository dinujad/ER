<?php

namespace App\Http\Controllers;

use App\Services\HikConnectAttendanceImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HikConnectAttendanceImportController extends Controller
{
    public const SESSION_KEY = 'hik_connect_attendance_import';

    public function __construct(
        protected HikConnectAttendanceImportService $import
    ) {}

    public function create()
    {
        return view('erp.attendance.hik-connect-import');
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:20480', $this->excelFileRule()],
        ]);

        $file = $request->file('file');
        $stored = $file->store('hik-connect-attendance');
        $fullPath = Storage::path($stored);

        try {
            $preview = $this->import->preview($fullPath);
        } catch (\Throwable $e) {
            Storage::delete($stored);

            return back()->with('error', 'Could not read this workbook. Use the Hik-Connect Total Time Card .xlsx export.');
        }

        if ($preview['rows'] === [] && $preview['errors'] !== []) {
            Storage::delete($stored);

            return back()->with('error', implode(' ', $preview['errors']));
        }

        session([
            self::SESSION_KEY => [
                'file' => $stored,
                'original_name' => $file->getClientOriginalName(),
                'rows' => array_map(fn ($row) => $this->sessionRow($row), $preview['rows']),
            ],
        ]);

        return view('erp.attendance.hik-connect-import-preview', [
            'rows' => $preview['rows'],
            'headerRow' => $preview['header_row'],
            'sheet' => $preview['sheet'],
            'originalName' => $file->getClientOriginalName(),
            'counts' => $this->statusCounts($preview['rows']),
        ]);
    }

    public function store(Request $request)
    {
        $payload = session(self::SESSION_KEY);
        if (! is_array($payload) || empty($payload['rows'])) {
            return redirect()
                ->route('attendance.hik-connect-import.create')
                ->with('error', 'Upload the time card again. The preview expired.');
        }

        $result = $this->import->confirm(
            $payload['rows'],
            auth()->id(),
            (string) ($payload['original_name'] ?? 'time-card.xlsx')
        );

        if (! empty($payload['file'])) {
            Storage::delete($payload['file']);
        }
        session()->forget(self::SESSION_KEY);

        $msg = sprintf(
            'Hik-Connect attendance import finished. Created %d, updated %d, unchanged %d, skipped %d, failed %d.',
            $result['created'],
            $result['updated'],
            $result['unchanged'],
            $result['skipped'],
            $result['failed']
        );
        if ($result['errors'] !== []) {
            $msg .= ' ' . implode(' ', array_slice($result['errors'], 0, 8));
            if (count($result['errors']) > 8) {
                $msg .= ' …';
            }
        }

        $level = ($result['created'] + $result['updated'] + $result['unchanged']) === 0 && $result['failed'] + $result['skipped'] > 0
            ? 'error'
            : 'success';

        return redirect()
            ->route('attendance.index')
            ->with($level, $msg);
    }

    protected function excelFileRule(): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) {
            $ext = strtolower((string) $value->getClientOriginalExtension());
            if (! in_array($ext, ['xlsx', 'xls'], true)) {
                $fail('Upload an Excel workbook (.xlsx or .xls).');
            }
        };
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function sessionRow(array $row): array
    {
        return [
            'excel_row' => $row['excel_row'] ?? null,
            'hikvision_person_id' => $row['hikvision_person_id'] ?? null,
            'first_name' => $row['first_name'] ?? null,
            'last_name' => $row['last_name'] ?? null,
            'department' => $row['department'] ?? null,
            'date' => $row['date'] ?? null,
            'timetable' => $row['timetable'] ?? null,
            'scheduled_start' => $row['scheduled_start'] ?? null,
            'scheduled_end' => $row['scheduled_end'] ?? null,
            'clock_in' => $row['clock_in'] ?? null,
            'clock_out' => $row['clock_out'] ?? null,
            'clock_in_source' => $row['clock_in_source'] ?? null,
            'clock_out_source' => $row['clock_out_source'] ?? null,
            'hikvision_status' => $row['hikvision_status'] ?? null,
            'worked_minutes' => $row['worked_minutes'] ?? null,
            'absent_minutes' => $row['absent_minutes'] ?? null,
            'late_minutes' => $row['late_minutes'] ?? null,
            'early_leave_minutes' => $row['early_leave_minutes'] ?? null,
            'break_minutes' => $row['break_minutes'] ?? null,
            'leave_minutes' => $row['leave_minutes'] ?? null,
            'overtime_minutes' => $row['overtime_minutes'] ?? null,
            'workday_overtime_minutes' => $row['workday_overtime_minutes'] ?? null,
            'weekend_overtime_minutes' => $row['weekend_overtime_minutes'] ?? null,
            'invalid_fields' => $row['invalid_fields'] ?? [],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, int>
     */
    protected function statusCounts(array $rows): array
    {
        $counts = [
            'new' => 0, 'update' => 0, 'unchanged' => 0,
            'unmatched' => 0, 'conflict' => 0, 'invalid' => 0,
        ];
        foreach ($rows as $row) {
            $status = $row['status'] ?? 'invalid';
            $counts[$status] = ($counts[$status] ?? 0) + 1;
        }

        return $counts;
    }
}
